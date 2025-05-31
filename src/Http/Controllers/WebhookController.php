<?php

namespace Techigh\CreditMessaging\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Log;
use Techigh\CreditMessaging\Facades\MessageRouter;

class WebhookController extends Controller
{
    /**
     * Handle webhook from message service providers
     */
    public function handle(Request $request, string $provider): JsonResponse
    {
        try {
            Log::info('Webhook received', [
                'provider' => $provider,
                'payload' => $request->all(),
                'headers' => $request->headers->all()
            ]);

            // Validate webhook signature if configured
            if (!$this->validateWebhookSignature($request, $provider)) {
                Log::warning('Invalid webhook signature', [
                    'provider' => $provider,
                    'ip' => $request->ip()
                ]);

                return response()->json(['error' => 'Invalid signature'], 401);
            }

            // Process the webhook
            $result = MessageRouter::processWebhook($provider, $request->all());

            Log::info('Webhook processed successfully', [
                'provider' => $provider,
                'result' => $result
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Webhook processed successfully',
                'data' => $result
            ]);
        } catch (\Exception $e) {
            Log::error('Webhook processing failed', [
                'provider' => $provider,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Webhook processing failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Validate webhook signature
     */
    private function validateWebhookSignature(Request $request, string $provider): bool
    {
        $secret = config("credit-messaging.webhook.{$provider}.secret");

        if (!$secret) {
            // If no secret is configured, skip validation
            return true;
        }

        $signature = $request->header('X-Webhook-Signature') ??
            $request->header('X-Hub-Signature-256') ??
            $request->header('Authorization');

        if (!$signature) {
            return false;
        }

        $payload = $request->getContent();
        $expectedSignature = hash_hmac('sha256', $payload, $secret);

        // Handle different signature formats
        if (str_starts_with($signature, 'sha256=')) {
            $signature = substr($signature, 7);
        }

        return hash_equals($expectedSignature, $signature);
    }
}
