<?php

declare(strict_types=1);

namespace Techigh\CreditMessaging\Services;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Techigh\CreditMessaging\Settings\Entities\SiteCampaign\SiteCampaign;
use Techigh\CreditMessaging\Settings\Entities\SiteCredit\SiteCredit;
use Techigh\CreditMessaging\Settings\Entities\SiteCreditUsage\SiteCreditUsage;

class CreditManager
{

    /**
     * 크레딧 차감 유형 결정
     * @param $inputs
     * @return string
     */
    public function getCreditType($inputs): string
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
    public function validateCredits(string $creditType, int $targetCount): void
    {
        // 사용 가능한 siteCredit 가져오기
        $availableSiteCredits = SiteCredit::query()
            ->where('status', 'SUCCESS')
            ->where('balance_credits', '>', 0)
            ->get();

        // 발송 가능한 수량
        $totalSendableCount = 0;
        $availableSiteCredits->each(function (SiteCredit $siteCredit) use (&$totalSendableCount, $creditType) {
            $balance = $siteCredit->balance_credits;
            $creditCost = $siteCredit->{"{$creditType}_credits_cost"} ?? 0;

            if ($creditCost > 0) $sendableCount = $balance / $creditCost;
            else $sendableCount = 0;

            $totalSendableCount += $sendableCount;
        });

        // 숫자 내림 처리
        $totalSendableCount = floor($totalSendableCount);
        // 발송 해야할 수량과 발송 가능한 수량 비교
        if ($targetCount > $totalSendableCount) {
            throw new \Exception(__('크레딧이 부족합니다. 발송 가능 수량: :count', ['count' => $totalSendableCount]));
        }
    }


    /**
     * 크레딧 차감
     * @throws \Exception
     */
    public function deductCredits(string $creditType, int $targetCount, int $siteCampaignId): float|int
    {
        // 사용 가능한 크레딧 가져와서
        $availableSiteCredits = SiteCredit::query()
            ->where('status', 'SUCCESS')
            ->where('balance_credits', '>', 0)
            ->get();

        DB::beginTransaction();

        try {
            // 총 차감된 크레딧 개수
            $totalDeductedCredits = 0;

            // 차감해야하는 개수
            $remainingCount = $targetCount;

            /** @var SiteCredit $siteCredit */
            foreach ($availableSiteCredits as $siteCredit) {
                $balance = $siteCredit->balance_credits;
                $creditCost = $siteCredit->{"{$creditType}_credits_cost"} ?? 0;

                // 크레딧 비용이 0보다 작으면 건너 뛴다
                if ($creditCost <= 0) {
                    Log::warning('[크레딧 차감] 단가 이상으로 스킵', [
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
                    Log::warning('[크레딧 차감] 잔액 부족으로 불가', [
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
                    'credit_type' => $creditType,
                    'used_count' => $deductCount,
                    'used_credits' => $deductCredits,
                    'used_cost' => $usedCost
                ]);

                // 남아있는 개수 = 기존 남아있는 개수 - 차감된 개수
                $remainingCount -= $deductCount;
                // 총 차감 크레딧 = 기존 총 차감 크레딧 + 차감 크레딧
                $totalDeductedCredits += $deductCredits;

                if ($remainingCount < 1) break;
            }

            DB::commit();
            return $totalDeductedCredits;
        } catch (\Exception $exception) {
            DB::rollBack();
            Log::error('[크레딧 차감] 실패', [
                'error' => $exception->getMessage(),
                'site_campaign_id' => $siteCampaignId
            ]);
            throw new \Exception(__('크레딧 차감 중 오류가 발생했습니다.'));
        }
    }

    public function rechargeCredits(int $siteCampaignId): void
    {
        DB::transaction(function () use ($siteCampaignId) {

            $siteCampaign = SiteCampaign::query()->findOrFail($siteCampaignId);
            $totalCount = $siteCampaign->total_count;
            $successCount = $siteCampaign->success_count;
            $failedCount = $siteCampaign->failed_count;
            $smsFailedCount = $siteCampaign->sms_failed_count;

            // 차감 기록
            $siteCampaignUsage = $siteCampaign->siteCreditUsages()->where('type', 1)->first();

            $existingRefund = $siteCampaign->siteCreditUsages()->where('type', -1)->exists();
            if ($existingRefund) {
                Log::warning('[크레딧 환급] 중복 시도 차단', [
                    'site_campaign_id' => $siteCampaignId
                ]);
                return;
            }

            $totalRechargeCreditAmount = 0;
            $totalRechargeCount = 0;

            Log::debug($siteCampaign->type);
            // 알림톡인 경우
            if ($siteCampaign->type === 'alimtalk') {
                // 환급 및 차감
                Log::debug($siteCampaign->replace_sms . ' : 대체 문자');
                if ($siteCampaign->replace_sms) {
                    // 카카오 10건 중 카카오 성공 3개 카카오 실패 7개  / 대체 문자 7개 sms 성공 5개  sms 실패 2개

                    // sms 실패 2개 환급
                    $finalFailCount = $smsFailedCount;
                    if ($finalFailCount > 0) {
                        $totalRechargeCount += $finalFailCount;
                        $totalRechargeCreditAmount += round($siteCampaignUsage->used_credits / $siteCampaignUsage->used_count * $finalFailCount, 2);
                    }

                    // 카카오 성공 3개는 sms 3개 환급 후 alimtalk 3개 차감
                    $revertCount = $successCount;
                    Log::debug($revertCount . ' : 알림톡으로 다시 차감하고 환급해줘야해!!');
                    if ($revertCount > 0) {
                        Log::debug('here~~~~');
                        $totalRechargeCount += $revertCount;
                        $totalRechargeCreditAmount += round($siteCampaignUsage->used_credits / $siteCampaignUsage->used_count * $revertCount, 2);
                        //alimltalk으로 차감
                        $this->deductCredits('alimtalk', $revertCount, $siteCampaignId);
                    }
                } else {
                    // 카카오 10건 중 카카오 성공 3개 카카오 실패 7개

                    // 카카오 실패 7개 환급
                    $finalFailCount = $failedCount;
                    if ($finalFailCount > 0) {
                        $totalRechargeCount += $finalFailCount;
                        $totalRechargeCreditAmount += round($siteCampaignUsage->used_credits / $siteCampaignUsage->used_count * $finalFailCount, 2);
                    }
                }
            } else {
                // global, sms
                $finalFailCount = $totalCount - $successCount;
                if ($finalFailCount > 0) {
                    $totalRechargeCount += $finalFailCount;
                    $totalRechargeCreditAmount += round($siteCampaignUsage->used_credits / $siteCampaignUsage->used_count * $finalFailCount, 2);
                }
            }

            if ($totalRechargeCreditAmount > 0) {
                $rechargeSiteCredit = SiteCredit::query()->create([
                    'type' => 'RECHARGE',
                    'status' => 'SUCCESS',
                    'purchase_amount' => 0,
                    'credits_amount' => $totalRechargeCreditAmount,
                    'used_credits' => 0,
                    'balance_credits' => $totalRechargeCreditAmount,
                    'cost_per_credit' => siteConfigs('site_cost_per_credit'),
                    'site_alimtalk_credits_cost' => siteConfigs('site_alimtalk_credits_cost'),
                    'site_sms_credits_cost' => siteConfigs('site_sms_credits_cost'),
                    'site_lms_credits_cost' => siteConfigs('site_lms_credits_cost'),
                    'site_mms_credits_cost' => siteConfigs('site_mms_credits_cost'),
                ]);

                SiteCreditUsage::query()->create([
                    'type' => -1,
                    'credit_type' => 'sms',
                    'site_credit_id' => $rechargeSiteCredit->id,
                    'site_campaign_id' => $siteCampaign->id,
                    'used_count' => -$totalRechargeCount,
                    'used_credits' => -$totalRechargeCreditAmount,
                    'used_cost' => 0,
                ]);

                Log::info('[크레딧 환급] 완료', [
                    'site_campaign_id' => $siteCampaignId,
                    'recharge_count' => $totalRechargeCount,
                    'recharge_amount' => $totalRechargeCreditAmount
                ]);
            }
        });
    }
}
