<?php

declare(strict_types=1);

namespace Techigh\CreditMessaging\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static mixed sendAlimtalk(array $inputs)
 * 
 * @see \Techigh\CreditMessaging\Services\MessageSendService
 */
class MessageSend extends Facade
{
    /**
     * Get the registered name of the component.
     */
    protected static function getFacadeAccessor(): string
    {
        return 'message-send';
    }
}
