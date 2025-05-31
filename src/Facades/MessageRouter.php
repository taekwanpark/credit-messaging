<?php

namespace Techigh\CreditMessaging\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static array sendMessage(\Techigh\CreditMessaging\Models\CreditMessage $creditMessage)
 * @method static bool scheduleMessage(\Techigh\CreditMessaging\Models\CreditMessage $creditMessage)
 * @method static array estimateMessageCost(string $siteId, string $messageType, int $recipientCount, string $content = null)
 * @method static array getBatchStatus(string $batchId)
 * @method static array processWebhook(string $provider, array $payload)
 *
 * @see \Techigh\CreditMessaging\Services\MessageRoutingService
 */
class MessageRouter extends Facade
{
    /**
     * Get the registered name of the component.
     */
    protected static function getFacadeAccessor(): string
    {
        return 'message-router';
    }
}
