<?php

declare(strict_types=1);

namespace Techigh\CreditMessaging\Services;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Carbon;
use Techigh\CreditMessaging\Settings\Entities\SiteCampaign\SiteCampaign;
use Techigh\CreditMessaging\Settings\Entities\SiteCampaignMessage\SiteCampaignMessage;

class WebhookService
{
    /**
     * 웹훅 서명 검증
     */
    public function verifySignature(Request $request): bool
    {

        // 검증 비활성화된 경우 스킵
        if (!config('credit-messaging.webhook.verify_signature', true)) {
            return true;
        }

        $secret = config('credit-messaging.webhook.secret');

        if (empty($secret)) {
            Log::warning(__('웹훅 시크릿이 설정되지 않아 서명 검증을 건너뜁니다.'));
            return true;
        }

        $signatureHeader = config('credit-messaging.webhook.signature_header', 'X-Webhook-Signature');
        $signature = $request->header($signatureHeader);

        if (empty($signature)) {
            if (config('credit-messaging.webhook.log_errors', true)) {
                Log::warning(__('웹훅 서명이 없습니다.'), ['ip' => $request->ip()]);
            }
            return false;
        }

        $payload = $request->getContent();
        $expectedSignature = hash_hmac('sha256', $payload, $secret);

        $isValid = hash_equals($expectedSignature, $signature);


        if (!$isValid && config('credit-messaging.webhook.log_errors', true)) {
            Log::warning(__('웹훅 서명 검증 실패'), [
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent()
            ]);
        }

        return $isValid;
    }

    /**
     * 발송 결과 웹훅 처리
     */
    public function processDeliveryStatus(array $data): array
    {
        return DB::transaction(function () use ($data) {
            $campaignId = $data['campaign_id'] ?? null;
            $campaign = $data['campaign'] ?? [];
            $messages = $data['messages'] ?? [];

            if (!$campaignId) {
                throw new Exception(__('캠페인 ID가 없습니다.'));
            }

            $siteCampaign = SiteCampaign::query()->find($campaignId);
            if (!$siteCampaign) {
                throw new Exception(__('캠페인을 찾을 수 없습니다: :id', ['id' => $campaignId]));
            }

            $this->updateCampaignStats($siteCampaign, $campaign);

            // 개별 메시지 결과 처리
            foreach ($messages as $phone => $message) {
                $this->updateMessageStatus($siteCampaign, $phone, $message);
            }

            // 웹훅 수신 시간 기록
            $campaign->update(['webhook_received_at' => now()]);

            return [
                'campaign_id' => $campaignId
            ];
        });
    }

    /**
     * 개별 메시지 상태 업데이트
     */
    private function updateMessageStatus(SiteCampaign $siteCampaign, string $phone, array $message): void
    {
        // 메시지 찾기
        $message = SiteCampaignMessage::query()->where('site_campaign_id', $siteCampaign->id)
            ->where('phone_e164', $phone)
            ->first();

        if (!$message) {
            Log::warning(__('메시지를 찾을 수 없습니다'), [
                'campaign_id' => $siteCampaign->id,
                'phone' => $phone
            ]);
            return;
        }

        $message->setAttribute('kakao_status', Arr::get($message, 'kakao_status', null));
        $message->setAttribute('sms_status', Arr::get($message, 'sms_status', null));
        $message->setAttribute('kakao_result_code', Arr::get($message, 'kakao_result_code', null));
        $message->setAttribute('sms_result_code', Arr::get($message, 'sms_result_code', null));
        $message->save();
    }

    /**
     * 캠페인 통계 업데이트
     */
    private function updateCampaignStats(SiteCampaign $siteCampaign, array $campaign): void
    {
        $siteCampaign->setAttribute('status', Arr::get($campaign, 'status', 'SUCCESS'));
        $siteCampaign->setAttribute('total_count', Arr::get($campaign, 'total_count', $siteCampaign->total_count));
        $siteCampaign->setAttribute('pending_count', Arr::get($campaign, 'pending_count', $siteCampaign->pending_count));
        $siteCampaign->setAttribute('success_count', Arr::get($campaign, 'success_count', $siteCampaign->success_count));
        $siteCampaign->setAttribute('canceled_count', Arr::get($campaign, 'canceled_count', $siteCampaign->canceled_count));
        $siteCampaign->setAttribute('rejected_count', Arr::get($campaign, 'rejected_count', $siteCampaign->rejected_count));
        $siteCampaign->setAttribute('failed_count', Arr::get($campaign, 'failed_count', $siteCampaign->failed_count));

        $siteCampaign->setAttribute('sms_success_count', Arr::get($campaign, 'sms_success_count', $siteCampaign->sms_success_count));
        $siteCampaign->setAttribute('sms_failed_count', Arr::get($campaign, 'sms_failed_count', $siteCampaign->sms_failed_count));
        $siteCampaign->save();
    }

    /**
     * 웹훅 재시도 처리
     */
    public function handleRetry(int $campaignId, array $data, int $attemptNumber): bool
    {
        $maxAttempts = config('credit-messaging.webhook.retry_attempts', 3);

        if ($attemptNumber > $maxAttempts) {
            Log::error(__('웹훅 재시도 횟수 초과'), [
                'campaign_id' => $campaignId,
                'attempt' => $attemptNumber,
                'max_attempts' => $maxAttempts
            ]);
            return false;
        }

        try {
            $this->processDeliveryStatus($data);
            return true;
        } catch (Exception $e) {
            Log::error(__('웹훅 재시도 실패'), [
                'campaign_id' => $campaignId,
                'attempt' => $attemptNumber,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }
}
