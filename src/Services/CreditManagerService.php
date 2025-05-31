<?php

namespace Techigh\CreditMessaging\Services;

use Techigh\CreditMessaging\Models\SiteCredit;
use Techigh\CreditMessaging\Models\SiteCreditPayment;
use Techigh\CreditMessaging\Models\SiteCreditUsage;
use Techigh\CreditMessaging\Models\MessageSendLog;
use App\Services\Credits\CreditDeductService;
use App\Services\Credits\CreditFactoryService;
use App\Settings\Entities\Team\Team;
use App\Settings\Entities\User\User;
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

    /**
     * 기존 smpp-provider Credit 시스템과 연동
     * 사용자/팀의 기존 크레딧으로 site 크레딧을 충전
     */
    public function chargeFromOwnerCredit(string $siteId, User|Team $owner, float $amount): SiteCreditPayment
    {
        return DB::transaction(function () use ($siteId, $owner, $amount) {
            // 기존 시스템에서 잔액 확인
            $availableBalance = $owner->sumOfBalanceCredits();
            
            if ($availableBalance < $amount) {
                throw new \Exception("크레딧이 부족합니다. 필요: {$amount}, 보유: {$availableBalance}");
            }

            // 기존 크레딧에서 차감
            $creditDeductService = new CreditDeductService();
            $creditDeductService->owner($owner)
                ->type('site_charge')
                ->targetCount($amount)
                ->replaceReason('사이트 크레딧 충전')
                ->deduct();

            // 사이트 크레딧 결제 기록 생성
            $payment = $this->addPayment($siteId, $amount, 'owner_credit', [
                'owner_type' => get_class($owner),
                'owner_id' => $owner->uuid,
                'source' => 'owner_credit_transfer'
            ]);

            // 결제 완료 처리
            $this->completePayment($payment);

            return $payment;
        });
    }

    /**
     * 사이트 크레딧을 소유자 크레딧으로 환급
     */
    public function refundToOwnerCredit(string $siteId, User|Team $owner, float $amount): void
    {
        DB::transaction(function () use ($siteId, $owner, $amount) {
            $siteCredit = $this->getSiteCredit($siteId);
            
            if ($siteCredit->balance < $amount) {
                throw new \Exception("사이트 크레딧이 부족합니다. 요청: {$amount}, 보유: {$siteCredit->balance}");
            }

            // 사이트 크레딧에서 차감
            $siteCredit->decrement('balance', $amount);

            // 소유자에게 크레딧 환급
            $creditFactoryService = new CreditFactoryService();
            $creditFactoryService::create([
                'owner' => $owner,
                'type' => 'SITE_REFUND',
                'status' => 'SUCCESS',
                'memo' => ['reason' => '사이트 크레딧 환급', 'site_id' => $siteId],
                'cost_per_credit' => 1.0, // 1:1 환급
                'purchase_amount' => $amount,
                'credits_amount' => $amount
            ]);

            Log::info("사이트 크레딧 환급 완료", [
                'site_id' => $siteId,
                'owner_type' => get_class($owner),
                'owner_id' => $owner->uuid,
                'amount' => $amount
            ]);
        });
    }

    /**
     * 기존 크레딧 시스템의 단가 정보를 사이트 크레딧에 동기화
     */
    public function syncCostFromOwnerGrade(string $siteId, User|Team $owner): void
    {
        $siteCredit = $this->getSiteCredit($siteId);
        
        if ($owner instanceof User && $owner->grade) {
            $grade = $owner->grade;
            $baseCostPerCredit = $grade->cost_per_credit;
            
            // 기본 설정에서 메시지 타입별 크레딧 비용을 가져와서 실제 금액으로 변환
            $siteCredit->update([
                'sms_cost' => $baseCostPerCredit * siteConfigs('sms_per_cost', 20),
                'lms_cost' => $baseCostPerCredit * siteConfigs('lms_per_cost', 40),
                'mms_cost' => $baseCostPerCredit * siteConfigs('mms_per_cost', 70),
                'alimtalk_cost' => $baseCostPerCredit * siteConfigs('kakao_no_per_cost', 15)
            ]);
        }
    }

    /**
     * 테넌트/사이트 식별자로부터 소유자 찾기
     */
    public function getOwnerBySiteId(string $siteId): User|Team|null
    {
        // 테넌트 시스템을 사용하는 경우
        if (class_exists('\Stancl\Tenancy\Database\Models\Tenant')) {
            $tenant = \Stancl\Tenancy\Database\Models\Tenant::find($siteId);
            if ($tenant && isset($tenant->data['owner_type']) && isset($tenant->data['owner_id'])) {
                $ownerType = $tenant->data['owner_type'];
                $ownerId = $tenant->data['owner_id'];
                
                if ($ownerType === 'App\\Settings\\Entities\\User\\User') {
                    return User::where('uuid', $ownerId)->first();
                } elseif ($ownerType === 'App\\Settings\\Entities\\Team\\Team') {
                    return Team::where('uuid', $ownerId)->first();
                }
            }
        }

        // 기본적으로 site_id를 team uuid로 간주
        return Team::where('uuid', $siteId)->first() ?? User::where('uuid', $siteId)->first();
    }
}
