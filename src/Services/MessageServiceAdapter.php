<?php

namespace Techigh\CreditMessaging\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MessageServiceAdapter
{
    private string $baseUrl;
    private string $apiKey;
    private CreditManagerService $creditManager;

    public function __construct(CreditManagerService $creditManager)
    {
        $this->baseUrl = config('credit-messaging.message_service.base_url', '');
        $this->apiKey = config('credit-messaging.message_service.api_key', '');
        $this->creditManager = $creditManager;
    }

    public function sendAlimtalk(string $siteId, array $recipients, string $templateCode, array $templateData = []): array
    {
        // 크레딧 선차감
        $usage = $this->creditManager->chargeCredits($siteId, 'alimtalk', count($recipients));

        try {
            $response = Http::withHeaders([
                'Authorization' => "Bearer {$this->apiKey}",
                'Content-Type' => 'application/json'
            ])->post("{$this->baseUrl}/alimtalk/send", [
                'template_code' => $templateCode,
                'recipients' => $recipients,
                'template_data' => $templateData,
                'site_id' => $siteId,
                'batch_id' => $usage->batch_id
            ]);

            if ($response->successful()) {
                $result = $response->json();

                // 전송 로그 기록
                $sendLog = $this->creditManager->recordUsage(
                    $usage,
                    $result['message_ids'] ?? [],
                    'alimtalk',
                    count($recipients)
                );

                Log::info("알림톡 전송 성공", [
                    'site_id' => $siteId,
                    'usage_id' => $usage->id,
                    'send_log_id' => $sendLog->id,
                    'recipient_count' => count($recipients)
                ]);

                return [
                    'success' => true,
                    'usage_id' => $usage->id,
                    'send_log_id' => $sendLog->id,
                    'message_ids' => $result['message_ids'] ?? [],
                    'batch_id' => $usage->batch_id
                ];
            } else {
                // 전송 실패시 크레딧 환불
                $this->creditManager->refundCredits($usage, $usage->total_cost, 'API 전송 실패');

                throw new \Exception("알림톡 전송 실패: " . $response->body());
            }
        } catch (\Exception $e) {
            // 예외 발생시 크레딧 환불
            $this->creditManager->refundCredits($usage, $usage->total_cost, 'API 호출 예외: ' . $e->getMessage());

            Log::error("알림톡 전송 예외", [
                'site_id' => $siteId,
                'usage_id' => $usage->id,
                'error' => $e->getMessage()
            ]);

            throw $e;
        }
    }

    public function sendSms(string $siteId, array $recipients, string $message, ?string $senderId = null): array
    {
        // SMS 길이에 따라 LMS/MMS 판단
        $messageType = $this->determineMessageType($message);

        // 크레딧 선차감
        $usage = $this->creditManager->chargeCredits($siteId, $messageType, count($recipients));

        try {
            $response = Http::withHeaders([
                'Authorization' => "Bearer {$this->apiKey}",
                'Content-Type' => 'application/json'
            ])->post("{$this->baseUrl}/sms/send", [
                'message' => $message,
                'recipients' => $recipients,
                'sender_id' => $senderId,
                'site_id' => $siteId,
                'batch_id' => $usage->batch_id,
                'message_type' => $messageType
            ]);

            if ($response->successful()) {
                $result = $response->json();

                // 전송 로그 기록
                $sendLog = $this->creditManager->recordUsage(
                    $usage,
                    $result['message_ids'] ?? [],
                    $messageType,
                    count($recipients)
                );

                Log::info("SMS/LMS/MMS 전송 성공", [
                    'site_id' => $siteId,
                    'usage_id' => $usage->id,
                    'send_log_id' => $sendLog->id,
                    'message_type' => $messageType,
                    'recipient_count' => count($recipients)
                ]);

                return [
                    'success' => true,
                    'usage_id' => $usage->id,
                    'send_log_id' => $sendLog->id,
                    'message_ids' => $result['message_ids'] ?? [],
                    'message_type' => $messageType,
                    'batch_id' => $usage->batch_id
                ];
            } else {
                // 전송 실패시 크레딧 환불
                $this->creditManager->refundCredits($usage, $usage->total_cost, 'API 전송 실패');

                throw new \Exception("SMS 전송 실패: " . $response->body());
            }
        } catch (\Exception $e) {
            // 예외 발생시 크레딧 환불
            $this->creditManager->refundCredits($usage, $usage->total_cost, 'API 호출 예외: ' . $e->getMessage());

            Log::error("SMS 전송 예외", [
                'site_id' => $siteId,
                'usage_id' => $usage->id,
                'message_type' => $messageType,
                'error' => $e->getMessage()
            ]);

            throw $e;
        }
    }

    public function sendWithFallback(string $siteId, array $recipients, string $message, array $alimtalkOptions = []): array
    {
        // 알림톡 우선 시도
        if (!empty($alimtalkOptions['template_code'])) {
            try {
                return $this->sendAlimtalk(
                    $siteId,
                    $recipients,
                    $alimtalkOptions['template_code'],
                    $alimtalkOptions['template_data'] ?? []
                );
            } catch (\Exception $e) {
                Log::warning("알림톡 전송 실패, SMS로 폴백", [
                    'site_id' => $siteId,
                    'error' => $e->getMessage()
                ]);
            }
        }

        // SMS 폴백
        return $this->sendSms($siteId, $recipients, $message, $alimtalkOptions['sender_id'] ?? null);
    }

    public function getBulkMessageStatus(array $messageIds): array
    {
        $response = Http::withHeaders([
            'Authorization' => "Bearer {$this->apiKey}",
            'Content-Type' => 'application/json'
        ])->post("{$this->baseUrl}/status/bulk", [
            'message_ids' => $messageIds
        ]);

        if ($response->successful()) {
            return $response->json();
        }

        throw new \Exception("메시지 상태 조회 실패: " . $response->body());
    }

    private function determineMessageType(string $message): string
    {
        $length = mb_strlen($message);

        if ($length <= 90) {
            return 'sms';
        } elseif ($length <= 2000) {
            return 'lms';
        } else {
            return 'mms';
        }
    }

    public function estimateCost(string $siteId, string $messageType, int $quantity): float
    {
        $siteCredit = $this->creditManager->getSiteCredit($siteId);
        return $siteCredit->getCostForMessageType($messageType) * $quantity;
    }

    public function preflightCheck(string $siteId, string $messageType, int $quantity): array
    {
        $siteCredit = $this->creditManager->getSiteCredit($siteId);
        $estimatedCost = $this->estimateCost($siteId, $messageType, $quantity);
        $hasEnoughBalance = $siteCredit->hasEnoughBalance($estimatedCost);

        return [
            'can_send' => $hasEnoughBalance,
            'estimated_cost' => $estimatedCost,
            'current_balance' => $siteCredit->balance,
            'shortage' => $hasEnoughBalance ? 0 : ($estimatedCost - $siteCredit->balance),
            'cost_per_unit' => $siteCredit->getCostForMessageType($messageType)
        ];
    }
}
