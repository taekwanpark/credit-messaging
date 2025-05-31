<?php

namespace Techigh\CreditMessaging\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static \Techigh\CreditMessaging\Settings\Entities\SiteCredit\SiteCredit getSiteCredit(string $siteId)
 * @method static float getBalance(string $siteId)
 * @method static \Techigh\CreditMessaging\Settings\Entities\SiteCreditUsage\SiteCreditUsage chargeCredits(string $siteId, float $amount, array $metadata = [])
 * @method static \Techigh\CreditMessaging\Settings\Entities\SiteCreditUsage\SiteCreditUsage refundCredits(\Techigh\CreditMessaging\Settings\Entities\SiteCreditUsage\SiteCreditUsage $usage, float $amount, string $reason)
 * @method static \Techigh\CreditMessaging\Settings\Entities\SiteCreditPayment\SiteCreditPayment addPayment(string $siteId, float $amount, string $method, array $data = [])
 * @method static bool completePayment(\Techigh\CreditMessaging\Settings\Entities\SiteCreditPayment\SiteCreditPayment $payment)
 * @method static array getUsageStats(string $siteId, $startDate, $endDate)
 * @method static void checkAutoChargeAndProcess(string $siteId)
 *
 * @see \Techigh\CreditMessaging\Services\CreditManagerService
 */
class CreditManager extends Facade
{
    /**
     * Get the registered name of the component.
     */
    protected static function getFacadeAccessor(): string
    {
        return 'credit-manager';
    }
}
