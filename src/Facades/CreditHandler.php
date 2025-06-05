<?php

declare(strict_types=1);

namespace Techigh\CreditMessaging\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static mixed getCreditType(array $inputs)
 * @method static mixed validateCredits(string $messageType, int $targetCount)
 * @method static mixed deductCredits(string $messageType, int $targetCount, int $siteCampaignId)
 * @method static mixed rechargeCredits(int $siteCampaignId)
 *
 * @see \Techigh\CreditMessaging\Services\CreditManager
 */
class CreditHandler extends Facade
{
    /**
     * Get the registered name of the component.
     */
    protected static function getFacadeAccessor(): string
    {
        return 'credit-handler';
    }
}
