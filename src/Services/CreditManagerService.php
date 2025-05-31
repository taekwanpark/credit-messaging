<?php

namespace Techigh\CreditMessaging\Services;

use Techigh\CreditMessaging\Settings\Entities\SiteCredit\SiteCredit;
use Techigh\CreditMessaging\Settings\Entities\SiteCreditPayment\SiteCreditPayment;
use Techigh\CreditMessaging\Settings\Entities\SiteCreditUsage\SiteCreditUsage;
use Techigh\CreditMessaging\Settings\Entities\MessageSendLog\MessageSendLog;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CreditManagerService
{
    public function getSiteCredit(string $siteId): SiteCredit
    {
        return SiteCredit::firstOrCreate(
            ['site_id' => $siteId],
            [
                'alimtalk_cost' => config('credit-messaging.default_costs.alimtalk', 15.00),
                'sms_cost' => config('credit-messaging.default_costs.sms', 20.00),
                'lms_cost' => config('credit-messaging.default_costs.lms', 50.00),
                'mms_cost' => config('credit-messaging.default_costs.mms', 200.00),
                'balance' => 0.00,
                'auto_charge_enabled' => config('credit-messaging.auto_charge.enabled', false),
                'auto_charge_threshold' => config('credit-messaging.auto_charge.default_threshold'),
                'auto_charge_amount' => config('credit-messaging.auto_charge.default_amount')
            ]
        );
    }

    public function getBalance(string $siteId): float
    {
        $siteCredit = $this->getSiteCredit($siteId);
        return $siteCredit->balance;
    }

    public function chargeCredits(string $siteId, string $messageType, int $quantity): SiteCreditUsage
    {
        return DB::transaction(function () use ($siteId, $messageType, $quantity) {
            $siteCredit = $this->getSiteCredit($siteId);
            $costPerUnit = $siteCredit->getCostForMessageType($messageType);
            $totalCost = $costPerUnit * $quantity;

            if (!$siteCredit->hasEnoughBalance($totalCost)) {
                throw new \Exception("잔액이 부족합니다. 필요: {$totalCost}원, 보유: {$siteCredit->balance}원");
            }

            // 크레딧 사용 기록 생성
            $usage = SiteCreditUsage::create([
                'site_id' => $siteId,
                'message_type' => $messageType,
                'quantity' => $quantity,
                'cost_per_unit' => $costPerUnit,
                'total_cost' => $totalCost,
                'status' => 'reserved',
                'batch_id' => uniqid('batch_')
            ]);

            // 잔액 차감
            $siteCredit->decrement('balance', $totalCost);

            Log::info("크레딧 차감", [
                'site_id' => $siteId,
                'message_type' => $messageType,
                'quantity' => $quantity,
                'total_cost' => $totalCost,
                'remaining_balance' => $siteCredit->fresh()->balance
            ]);

            return $usage;
        });
    }

    public function refundCredits(SiteCreditUsage $usage, float $refundAmount, ?string $reason = null): void
    {
        DB::transaction(function () use ($usage, $refundAmount, $reason) {
            $siteCredit = $this->getSiteCredit($usage->site_id);

            // 환불 가능 금액 확인
            $refundableAmount = $usage->getRefundableAmount();
            if ($refundAmount > $refundableAmount) {
                throw new \Exception("환불 가능 금액을 초과했습니다. 최대: {$refundableAmount}원");
            }

            // 환불 처리
            $usage->refund($refundAmount, $reason);

            // 잔액 복구
            $siteCredit->increment('balance', $refundAmount);

            Log::info("크레딧 환불", [
                'usage_id' => $usage->id,
                'site_id' => $usage->site_id,
                'refund_amount' => $refundAmount,
                'reason' => $reason,
                'new_balance' => $siteCredit->fresh()->balance
            ]);
        });
    }

    public function addPayment(string $siteId, float $amount, string $paymentMethod, array $paymentData = []): SiteCreditPayment
    {
        return SiteCreditPayment::create([
            'site_id' => $siteId,
            'amount' => $amount,
            'payment_method' => $paymentMethod,
            'status' => 'pending',
            'payment_data' => $paymentData
        ]);
    }

    public function completePayment(SiteCreditPayment $payment): void
    {
        DB::transaction(function () use ($payment) {
            if ($payment->isCompleted()) {
                return;
            }

            $payment->markAsCompleted();

            // 잔액 추가
            $siteCredit = $this->getSiteCredit($payment->site_id);
            $siteCredit->increment('balance', $payment->amount);

            Log::info("결제 완료 및 크레딧 충전", [
                'payment_id' => $payment->id,
                'site_id' => $payment->site_id,
                'amount' => $payment->amount,
                'new_balance' => $siteCredit->fresh()->balance
            ]);
        });
    }

    public function recordUsage(SiteCreditUsage $usage, array $messageIds, string $actualMessageType, int $totalCount): MessageSendLog
    {
        $sendLog = MessageSendLog::create([
            'usage_id' => $usage->id,
            'message_ids' => $messageIds,
            'message_type' => $actualMessageType,
            'total_count' => $totalCount,
            'settlement_status' => 'pending'
        ]);

        $usage->markAsUsed();

        return $sendLog;
    }

    public function processWebhookResult(MessageSendLog $sendLog, array $webhookData): void
    {
        $sendLog->updateWebhookResult($webhookData);

        // 성공/실패 카운트 업데이트
        $successCount = $webhookData['success_count'] ?? 0;
        $failedCount = $webhookData['failed_count'] ?? 0;

        $sendLog->update([
            'success_count' => $successCount,
            'failed_count' => $failedCount
        ]);

        // 자동 정산 처리
        $this->autoSettlement($sendLog);
    }

    public function autoSettlement(MessageSendLog $sendLog): void
    {
        if ($sendLog->isSettled()) {
            return;
        }

        $usage = $sendLog->usage;
        $siteCredit = $this->getSiteCredit($usage->site_id);

        // 실제 전송된 메시지 기준으로 최종 비용 계산
        $actualCost = $siteCredit->getCostForMessageType($sendLog->message_type) * $sendLog->success_count;

        // 차액 환불 처리
        $refundAmount = $usage->total_cost - $actualCost;

        if ($refundAmount > 0) {
            $this->refundCredits($usage, $refundAmount, '전송 실패분 자동 환불');
        }

        $sendLog->markAsSettled($actualCost);

        Log::info("자동 정산 완료", [
            'send_log_id' => $sendLog->id,
            'usage_id' => $usage->id,
            'original_cost' => $usage->total_cost,
            'actual_cost' => $actualCost,
            'refund_amount' => $refundAmount
        ]);
    }

    public function checkAutoChargeAndProcess(string $siteId): void
    {
        $siteCredit = $this->getSiteCredit($siteId);

        if ($siteCredit->shouldAutoCharge()) {
            $this->processAutoCharge($siteCredit);
        }
    }

    private function processAutoCharge(SiteCredit $siteCredit): void
    {
        $payment = $this->addPayment(
            $siteCredit->site_id,
            $siteCredit->auto_charge_amount,
            'admin',
            ['type' => 'auto_charge', 'trigger_balance' => $siteCredit->balance]
        );

        // 여기서 실제 결제 처리 로직을 호출하거나
        // 관리자에게 알림을 보낼 수 있습니다

        Log::info("자동 충전 요청 생성", [
            'site_id' => $siteCredit->site_id,
            'current_balance' => $siteCredit->balance,
            'charge_amount' => $siteCredit->auto_charge_amount,
            'payment_id' => $payment->id
        ]);
    }

    public function getUsageStats(string $siteId, ?\DateTime $startDate = null, ?\DateTime $endDate = null): array
    {
        $query = SiteCreditUsage::where('site_id', $siteId);

        if ($startDate) {
            $query->where('created_at', '>=', $startDate);
        }

        if ($endDate) {
            $query->where('created_at', '<=', $endDate);
        }

        $usages = $query->get();

        return [
            'total_cost' => $usages->sum('total_cost'),
            'total_refund' => $usages->sum('refund_amount'),
            'net_cost' => $usages->sum('total_cost') - $usages->sum('refund_amount'),
            'message_counts' => $usages->groupBy('message_type')->map->sum('quantity'),
            'usage_count' => $usages->count()
        ];
    }
}
