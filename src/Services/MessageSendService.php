<?php

declare(strict_types=1);

namespace Techigh\CreditMessaging\Services;

use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use libphonenumber\NumberParseException;
use libphonenumber\PhoneNumberFormat;
use libphonenumber\PhoneNumberUtil;
use Techigh\CreditMessaging\Settings\Entities\SiteCampaign\SiteCampaign;
use Techigh\CreditMessaging\Settings\Entities\SiteCampaignMessage\SiteCampaignMessage;
use Techigh\CreditMessaging\Settings\Entities\SiteCredit\SiteCredit;

class MessageSendService
{
    /**
     * 알림톡 발송 요청
     */
    public function sendAlimtalk(array $inputs)
    {
        return DB::transaction(function () use ($inputs) {

            // 크레딧 차감 유형 결정
            $creditType = $this->getCreditType($inputs);

            // 크레딧 검증
            $this->validateCredits($creditType, count($inputs['contacts']));

            // 캠페인 생성
            $campaign = $this->createCampaign($inputs);

            // 크레딧 차감
            $this->deductCredits($creditType, count($inputs['contacts']), $campaign->getKey());

            // 개별 메시지 생성
            $this->createCampaignMessages($campaign->getKey(), $inputs['contacts']);

            // 5. 메시지 플랫폼에 발송 요청
            $this->requestToMessagePlatform($campaign, $campaign->siteCampaignMessages);
        });
    }

    /**
     * 크레딧 차감 유형 결정
     * @param $inputs
     * @return string
     */
    private function getCreditType($inputs): string
    {
        $replaceSms = Arr::get($inputs, 'type', 'alimtalk') === 'alimtalk' ? Arr::get($inputs, 'replaceSms', true) : false;
        return match (true) {
            $replaceSms && strlen($inputs['smsContent']) >= 90 => 'lms',
            $replaceSms => 'sms',
            default => 'alimtalk',
        };
    }

    /**
     * 크레딧 검증
     * @throws \Exception
     */
    private function validateCredits(string $messageType, int $targetCount): void
    {
        // 사용 가능한 siteCredit 가져오기
        $availableSiteCredits = SiteCredit::query()
            ->where('status', 'SUCCESS')
            ->where('balance_credits', '>', 0)
            ->get();

        // 발송 가능한 수량
        $totalSendableCount = 0;
        $availableSiteCredits->each(function (SiteCredit $siteCredit) use (&$totalSendableCount, $messageType) {
            $balance = $siteCredit->balance_credits;
            $creditCost = $siteCredit->{"{$messageType}_credits_cost"} ?? 0;

            if ($creditCost > 0) $sendableCount = $balance / $creditCost;
            else $sendableCount = 0;

            $totalSendableCount += $sendableCount;
        });

        // 숫자 내림 처리
        $totalSendableCount = floor($totalSendableCount);
        // 발송 해야할 수량과 발송 가능한 수량 비교
        if ($targetCount > $totalSendableCount) {
            throw new \Exception("크레딧이 부족합니다. 발송 가능 수량: {$totalSendableCount}");
        }
    }


    /**
     * 캠페인 생성
     */
    private function createCampaign(array $inputs): SiteCampaign
    {

        // 캠페인 유형
        $messageType = Arr::get($inputs, 'type', 'alimtalk');

        // 대체 문자 사용 여부
        $replaceSms = $messageType === 'alimtalk' ? Arr::get($inputs, 'replaceSms', true) : false;

        // count
        $targetCount = count(Arr::get($inputs, 'contacts', []));

        //전송일자 지정
        $sendAt = Arr::get($inputs, 'sendAt', '') ?? '';
        $sendAt = $sendAt === '' ? Carbon::now() : Carbon::parse($sendAt);
        if ($sendAt < Carbon::now()) $sendAt = Carbon::now();

        // SiteCampaign 생성 로직
        $campaignData = [
            'type' => $messageType,
            'status' => 'PENDING',
            'total_count' => $targetCount,
            'pending_count' => $targetCount,
            'replace_sms' => $replaceSms,
            'send_at' => $sendAt,
        ];

        // 알림톡인 경우 알림톡 관련 정보 추가
        if ($messageType === 'alimtalk') {
            $campaignData['template_code'] = $inputs['templateCode'];
        }

        // 알림톡인데 대체 문자 사용하거나 알림톡이 아닌 경우 문자 내용 추가
        // todo 템플릿 관리 필요
        if (($messageType === 'alimtalk' && $replaceSms) || $messageType !== 'alimtalk') {
            $campaignData['sms_title'] = Arr::get($inputs, 'smsTitle');
            $campaignData['sms_content'] = Arr::get($inputs, 'smsContent');
        }

        return SiteCampaign::query()->create($campaignData);
    }


    /**
     * 크레딧 차감
     * @throws \Exception
     */
    private function deductCredits(string $messageType, int $targetCount, int $siteCampaignId): float|int
    {
        // 사용 가능한 크레딧 가져와서
        $availableSiteCredits = SiteCredit::query()
            ->where('status', 'SUCCESS')
            ->where('balance_credits', '>', 0)
            ->get();

        DB::beginTransaction();

        // 총 차감된 크레딧 개수
        $totalDeductedCredits = 0;

        // 차감해야하는 개수
        $remainingCount = $targetCount;

        /** @var SiteCredit $siteCredit */
        foreach ($availableSiteCredits as $siteCredit) {
            $balance = $siteCredit->balance_credits;
            $creditCost = $siteCredit->{"{$messageType}_credits_cost"} ?? 0;

            // 크레딧 비용이 0보다 작으면 건너 뛴다
            if ($creditCost <= 0) {
                Log::channel('credit')->warning('[크레딧 차감/사용] - 스킵(단가 이상)', [
                    'credit_id' => $siteCredit->getKey(),
                    'cost' => $creditCost
                ]);
                continue;
            }

            // 해당 크레딧으로 발송 가능한 최대 메세지 건수 - 내림으로 계산
            $maxSendableCount = floor($balance / $creditCost);

            // 발송 가능한 최대 메세지 건수가 1보다 작을 경우 건너 띈다
            if ($maxSendableCount < 1) {
                // todo 나중에 집계해서 1크레딧으로 환급
                Log::channel('credit')->warning('[크레딧 차감/사용] - 불가(잔액 부족)', [
                    'credit_id' => $siteCredit->getKey(),
                    'balance' => $balance,
                    'cost' => $creditCost
                ]);
                continue;
            }

            // 차감 개수 = 발송 가능한 최대 메세지 건수, 남은 발송 건수 비교하여 작은 건수 사용
            $deductCount = min($maxSendableCount, $remainingCount);
            // 차감 크레딧 = 차감 개수 * 해당 크레딧의 type 1건 발송 비용
            $deductCredits = $deductCount * $creditCost;
            try {
                // 해당 크레딧을 업데이트 한다
                $siteCredit->update([
                    'used_credits' => $siteCredit->used_credits + $deductCredits,
                    'balance_credits' => $siteCredit->balance_credits - $deductCredits,
                ]);

                // 사용 금액 = 1크레딧 비용 * 차감된 크레딧 개수
                $usedCost = $siteCredit->getAttribute('cost_per_credit') * $deductCredits;

                // 크레딧 사용량 생성(사용)
                $siteCredit->siteCreditUsages()->create([
                    'site_campaign_id' => $siteCampaignId,
                    'type' => 1,
                    'credit_type' => $creditCost,
                    'used_count' => $deductCount,
                    'used_credits' => $deductCredits,
                    'used_cost' => $usedCost
                ]);
            } catch (\Exception $exception) {
                throw new \Exception(__('Credit Exception'));
            }
            // 남아있는 개수 = 기존 남아있는 개수 - 차감된 개수
            $remainingCount -= $deductCount;
            // 총 차감 크레딧 = 기존 총 차감 크레딧 + 차감 크레딧
            $totalDeductedCredits += $deductCredits;

            if ($remainingCount < 1) break;
        }
        DB::commit();

        return $totalDeductedCredits;
    }

    /**
     * 개별 메시지 생성
     */
    private function createCampaignMessages(int $campaignId, array $contacts): void
    {
        $phoneUtil = PhoneNumberUtil::getInstance();

        $messages = [];
        foreach ($contacts as $contact) {
            $message = [
                'site_campaign_id' => $campaignId,
                'name' => Arr::get($contact, 'name'),
            ];
            $phone = $contact['phone'];
            try {
                if (strlen($phone) > 16) $phone = substr($phone, -11);
                $numberProto = $phoneUtil->parse($phone, siteConfigs('default_country', 'KR'));
                $message['phone_e164'] = $phoneUtil->format($numberProto, PhoneNumberFormat::E164);
            } catch (NumberParseException $e) {
            }
            $messages[] = $message;
        }
        foreach ($messages as $messageData) {
            SiteCampaignMessage::query()->create($messageData); // creating, created 이벤트 발생
        }
    }

    /**
     * 메시지 플랫폼에 발송 요청
     * @throws \Exception
     */
    private function requestToMessagePlatform(SiteCampaign $campaign, array $contacts): void
    {
        $webhookConfig = $this->buildWebhookConfig($campaign);

        $result = (new MessagePlatformService())->requestToMessagePlatform($campaign, $contacts, $webhookConfig);
        $status = 'PROGRESS';
        if (Arr::get($result, "message", 'Success') !== "Success") $status = 'FAILED';
        $campaign->update(['status' => $status]);
    }

    /**
     * 웹훅 설정 구성
     */
    private function buildWebhookConfig(SiteCampaign $campaign): array
    {
        $domain = request()->getHost();
        $scheme = request()->isSecure() ? 'https' : 'http';
        $basePath = config('credit-messaging.webhook.base_path', '/api/webhooks/credit-messaging');

        return [
            'webhook_url' => "{$scheme}://{$domain}{$basePath}/delivery-status",
            'webhook_secret' => config('credit-messaging.webhook.secret'),
            'original_domain' => $domain,
            'external_campaign_id' => $campaign->id,
        ];
    }
}
