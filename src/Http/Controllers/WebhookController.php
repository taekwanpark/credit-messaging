<?php

namespace Techigh\CreditMessaging\Http\Controllers;

use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Log;
use Techigh\CreditMessaging\Facades\CreditHandler;
use Techigh\CreditMessaging\Services\CreditManager;
use Techigh\CreditMessaging\Services\WebhookService;

class WebhookController extends Controller
{
    public function __construct(
        private readonly WebhookService $webhookService
    )
    {
    }

    /**
     * 메시지 플랫폼으로부터 발송 결과 웹훅 수신
     */
    public function handleDeliveryStatus(Request $request): void
    {
        try {
            // 웹훅 서명 검증
            if (!$this->webhookService->verifySignature($request)) {
                Log::warning(__('웹훅 서명 검증 실패'), [
                    'ip' => $request->ip(),
                    'user_agent' => $request->userAgent()
                ]);
            }

            // 웹훅 데이터 처리
            $campaignId = $this->webhookService->processDeliveryStatus($request->all());

            CreditHandler::rechargeCredits($campaignId);

        } catch (Exception $e) {
            Log::error(__('웹훅 처리 실패'), [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'payload' => $request->all()
            ]);
        }
    }
}
