<?php

namespace Techigh\CreditMessaging\Services;

use Techigh\CreditMessaging\Settings\Entities\CreditMessage\CreditMessage;
use Illuminate\Support\Facades\Log;

class MessageRoutingService
{
    private MessageServiceAdapter $messageAdapter;
    private CreditManagerService $creditManager;

    public function __construct(MessageServiceAdapter $messageAdapter, CreditManagerService $creditManager)
    {
        $this->messageAdapter = $messageAdapter;
        $this->creditManager = $creditManager;
    }

    public function sendMessage(CreditMessage $creditMessage): array
    {
        $siteId = $creditMessage->site_id;
        $recipients = $creditMessage->recipients;
        $content = $creditMessage->message_content;
        $strategy = $creditMessage->routing_strategy;

        // 수신자 전화번호 유효성 검사
        $validRecipients = $this->validateRecipients($recipients);
        if (empty($validRecipients)) {
            throw new \Exception('유효한 수신자가 없습니다.');
        }

        // 라우팅 전략에 따른 전송
        return match ($strategy) {
            'alimtalk_first' => $this->sendWithAlimtalkFirst($creditMessage, $validRecipients),
            'sms_only' => $this->sendSmsOnly($creditMessage, $validRecipients),
            'cost_optimized' => $this->sendCostOptimized($creditMessage, $validRecipients),
            default => throw new \Exception('지원하지 않는 라우팅 전략입니다.')
        };
    }

    private function sendWithAlimtalkFirst(CreditMessage $creditMessage, array $recipients): array
    {
        $siteId = $creditMessage->site_id;
        $results = [];

        // 알림톡 전송 시도
        try {
            // 알림톡 템플릿 정보가 있는지 확인
            $metadata = $creditMessage->metadata ?? [];
            if (isset($metadata['alimtalk_template_code'])) {
                Log::info("알림톡 우선 전송 시작", [
                    'credit_message_id' => $creditMessage->id,
                    'recipient_count' => count($recipients)
                ]);

                $alimtalkResult = $this->messageAdapter->sendAlimtalk(
                    $siteId,
                    $recipients,
                    $metadata['alimtalk_template_code'],
                    $metadata['alimtalk_template_data'] ?? []
                );

                $results[] = [
                    'method' => 'alimtalk',
                    'result' => $alimtalkResult,
                    'recipients' => $recipients
                ];

                $this->updateCreditMessageAfterSend($creditMessage, $alimtalkResult);

                return $results;
            }
        } catch (\Exception $e) {
            Log::warning("알림톡 전송 실패, SMS로 폴백", [
                'credit_message_id' => $creditMessage->id,
                'error' => $e->getMessage()
            ]);
        }

        // SMS 폴백
        $smsResult = $this->messageAdapter->sendSms(
            $siteId,
            $recipients,
            $creditMessage->message_content,
            $metadata['sender_id'] ?? null
        );

        $results[] = [
            'method' => 'sms_fallback',
            'result' => $smsResult,
            'recipients' => $recipients
        ];

        $this->updateCreditMessageAfterSend($creditMessage, $smsResult);

        return $results;
    }

    private function sendSmsOnly(CreditMessage $creditMessage, array $recipients): array
    {
        $metadata = $creditMessage->metadata ?? [];

        $smsResult = $this->messageAdapter->sendSms(
            $creditMessage->site_id,
            $recipients,
            $creditMessage->message_content,
            $metadata['sender_id'] ?? null
        );

        $results = [[
            'method' => 'sms_only',
            'result' => $smsResult,
            'recipients' => $recipients
        ]];

        $this->updateCreditMessageAfterSend($creditMessage, $smsResult);

        return $results;
    }

    private function sendCostOptimized(CreditMessage $creditMessage, array $recipients): array
    {
        $siteId = $creditMessage->site_id;
        $siteCredit = $this->creditManager->getSiteCredit($siteId);

        // 비용 비교
        $alimtalkCost = $siteCredit->getCostForMessageType('alimtalk') * count($recipients);
        $smsCost = $this->calculateSmsCost($creditMessage->message_content, count($recipients), $siteCredit);

        Log::info("비용 최적화 라우팅", [
            'credit_message_id' => $creditMessage->id,
            'alimtalk_cost' => $alimtalkCost,
            'sms_cost' => $smsCost
        ]);

        // 더 저렴한 방법 선택
        if ($alimtalkCost <= $smsCost) {
            return $this->sendWithAlimtalkFirst($creditMessage, $recipients);
        } else {
            return $this->sendSmsOnly($creditMessage, $recipients);
        }
    }

    private function calculateSmsCost(string $message, int $recipientCount, $siteCredit): float
    {
        $length = mb_strlen($message);

        if ($length <= 90) {
            return $siteCredit->getCostForMessageType('sms') * $recipientCount;
        } elseif ($length <= 2000) {
            return $siteCredit->getCostForMessageType('lms') * $recipientCount;
        } else {
            return $siteCredit->getCostForMessageType('mms') * $recipientCount;
        }
    }

    private function validateRecipients(array $recipients): array
    {
        $validRecipients = [];

        foreach ($recipients as $recipient) {
            // 전화번호 형식 검증 (한국 번호 기준)
            $phoneNumber = preg_replace('/[^\d]/', '', $recipient);

            if (preg_match('/^(010|011|016|017|018|019)\d{7,8}$/', $phoneNumber)) {
                $validRecipients[] = $phoneNumber;
            } else {
                Log::warning("유효하지 않은 전화번호", ['recipient' => $recipient]);
            }
        }

        return array_unique($validRecipients);
    }

    private function updateCreditMessageAfterSend(CreditMessage $creditMessage, array $sendResult): void
    {
        $creditMessage->update([
            'status' => 'sending',
            'sent_at' => now(),
            'total_recipients' => count($creditMessage->recipients)
        ]);

        Log::info("크레딧 메시지 상태 업데이트", [
            'credit_message_id' => $creditMessage->id,
            'send_result' => $sendResult
        ]);
    }

    public function scheduleMessage(CreditMessage $creditMessage): void
    {
        if ($creditMessage->scheduled_at && $creditMessage->scheduled_at->isFuture()) {
            // 스케줄된 시간이 미래인 경우 큐에 예약
            \Techigh\CreditMessaging\Jobs\SendScheduledMessageJob::dispatch($creditMessage)
                ->delay($creditMessage->scheduled_at);

            $creditMessage->update(['status' => 'scheduled']);

            Log::info("메시지 전송 예약", [
                'credit_message_id' => $creditMessage->id,
                'scheduled_at' => $creditMessage->scheduled_at
            ]);
        } else {
            // 즉시 전송
            $this->sendMessage($creditMessage);
        }
    }

    public function cancelScheduledMessage(CreditMessage $creditMessage): void
    {
        if ($creditMessage->status === 'scheduled') {
            $creditMessage->update(['status' => 'cancelled']);

            Log::info("예약 메시지 취소", [
                'credit_message_id' => $creditMessage->id
            ]);
        }
    }

    public function estimateMessageCost(string $siteId, string $messageType, int $recipientCount, string $content = ''): array
    {
        $siteCredit = $this->creditManager->getSiteCredit($siteId);

        if ($messageType === 'auto_detect' && !empty($content)) {
            $messageType = $this->detectMessageTypeFromContent($content);
        }

        $costPerUnit = $siteCredit->getCostForMessageType($messageType);
        $totalCost = $costPerUnit * $recipientCount;

        return [
            'message_type' => $messageType,
            'cost_per_unit' => $costPerUnit,
            'total_cost' => $totalCost,
            'recipient_count' => $recipientCount,
            'current_balance' => $siteCredit->balance,
            'can_afford' => $siteCredit->hasEnoughBalance($totalCost)
        ];
    }

    private function detectMessageTypeFromContent(string $content): string
    {
        $length = mb_strlen($content);

        if ($length <= 90) {
            return 'sms';
        } elseif ($length <= 2000) {
            return 'lms';
        } else {
            return 'mms';
        }
    }

    public function getBatchStatus(string $batchId): array
    {
        // 배치 ID로 전송 상태 조회
        $usages = \Techigh\CreditMessaging\Models\SiteCreditUsage::where('batch_id', $batchId)->get();
        $sendLogs = collect([]);

        foreach ($usages as $usage) {
            $logs = $usage->messageSendLogs;
            $sendLogs = $sendLogs->merge($logs);
        }

        $totalSent = $sendLogs->sum('total_count');
        $totalSuccess = $sendLogs->sum('success_count');
        $totalFailed = $sendLogs->sum('failed_count');

        return [
            'batch_id' => $batchId,
            'total_sent' => $totalSent,
            'total_success' => $totalSuccess,
            'total_failed' => $totalFailed,
            'success_rate' => $totalSent > 0 ? ($totalSuccess / $totalSent) * 100 : 0,
            'status' => $this->determineBatchStatus($sendLogs),
            'logs' => $sendLogs->toArray()
        ];
    }

    private function determineBatchStatus($sendLogs): string
    {
        $statuses = $sendLogs->pluck('settlement_status')->unique();

        if ($statuses->contains('failed')) {
            return 'failed';
        } elseif ($statuses->every(fn($status) => $status === 'completed')) {
            return 'completed';
        } elseif ($statuses->contains('processing')) {
            return 'processing';
        } else {
            return 'pending';
        }
    }
}
