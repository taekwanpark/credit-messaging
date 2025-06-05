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
use Techigh\CreditMessaging\Facades\CreditHandler;
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
            $creditType = CreditHandler::getCreditType($inputs);

            // 크레딧 검증
            CreditHandler::validateCredits($creditType, count($inputs['contacts']));

            // 캠페인 생성
            $campaign = $this->createCampaign($inputs);

            // 크레딧 차감
            CreditHandler::deductCredits($creditType, count($inputs['contacts']), $campaign->getKey());

            // 개별 메시지 생성
            $this->createCampaignMessages($campaign->getKey(), $inputs['contacts']);

            // 5. 메시지 플랫폼에 발송 요청
            $this->requestToMessagePlatform($campaign, $campaign->siteCampaignMessages);
        });
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
                Log::warning('[전화번호 파싱 실패]', [
                    'phone' => $phone,
                    'error' => $e->getMessage()
                ]);
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
    private function requestToMessagePlatform(SiteCampaign $campaign, \Illuminate\Database\Eloquent\Collection $messages): void
    {
        $webhookConfig = $this->buildWebhookConfig($campaign);

        $result = (new MessagePlatformService())->requestToMessagePlatform($campaign, $messages, $webhookConfig);
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
