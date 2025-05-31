<?php

namespace Techigh\CreditMessaging\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Techigh\CreditMessaging\Settings\Entities\SiteCreditPayment\SiteCreditPayment;
use Techigh\CreditMessaging\Facades\CreditManager;

class SiteCreditPaymentController extends Controller
{
    public function index(Request $request, string $siteId): JsonResponse
    {
        $payments = SiteCreditPayment::where('site_id', $siteId)
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return response()->json(['status' => 'success', 'data' => $payments]);
    }

    public function store(Request $request, string $siteId): JsonResponse
    {
        $request->validate([
            'amount' => 'required|numeric|min:0',
            'payment_method' => 'required|string',
            'metadata' => 'sometimes|array',
        ]);

        $payment = SiteCreditPayment::create([
            'site_id' => $siteId,
            'amount' => $request->amount,
            'payment_method' => $request->payment_method,
            'status' => 'pending',
            'metadata' => $request->metadata ?? [],
        ]);

        return response()->json(['status' => 'success', 'data' => $payment], 201);
    }

    public function show(Request $request, string $siteId, SiteCreditPayment $payment): JsonResponse
    {
        if ($payment->site_id !== $siteId) {
            return response()->json(['status' => 'error', 'message' => 'Payment not found'], 404);
        }

        return response()->json(['status' => 'success', 'data' => $payment]);
    }

    public function update(Request $request, string $siteId, SiteCreditPayment $payment): JsonResponse
    {
        if ($payment->site_id !== $siteId) {
            return response()->json(['status' => 'error', 'message' => 'Payment not found'], 404);
        }

        $request->validate([
            'status' => 'sometimes|string|in:pending,completed,failed,cancelled',
            'metadata' => 'sometimes|array',
        ]);

        $payment->update($request->only(['status', 'metadata']));

        return response()->json(['status' => 'success', 'data' => $payment]);
    }

    public function complete(Request $request, string $siteId, SiteCreditPayment $payment): JsonResponse
    {
        if ($payment->site_id !== $siteId) {
            return response()->json(['status' => 'error', 'message' => 'Payment not found'], 404);
        }

        if ($payment->status !== 'pending') {
            return response()->json(['status' => 'error', 'message' => 'Payment cannot be completed'], 400);
        }

        try {
            $success = CreditManager::completePayment($payment);

            if ($success) {
                return response()->json(['status' => 'success', 'message' => 'Payment completed successfully']);
            } else {
                return response()->json(['status' => 'error', 'message' => 'Payment completion failed'], 500);
            }
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    public function cancel(Request $request, string $siteId, SiteCreditPayment $payment): JsonResponse
    {
        if ($payment->site_id !== $siteId) {
            return response()->json(['status' => 'error', 'message' => 'Payment not found'], 404);
        }

        if ($payment->status !== 'pending') {
            return response()->json(['status' => 'error', 'message' => 'Payment cannot be cancelled'], 400);
        }

        $payment->update(['status' => 'cancelled']);

        return response()->json(['status' => 'success', 'message' => 'Payment cancelled successfully']);
    }

    public function refund(Request $request, string $siteId, SiteCreditPayment $payment): JsonResponse
    {
        if ($payment->site_id !== $siteId) {
            return response()->json(['status' => 'error', 'message' => 'Payment not found'], 404);
        }

        if ($payment->status !== 'completed') {
            return response()->json(['status' => 'error', 'message' => 'Only completed payments can be refunded'], 400);
        }

        $request->validate([
            'amount' => 'required|numeric|min:0|max:' . $payment->amount,
            'reason' => 'required|string',
        ]);

        try {
            // Create refund record
            $refund = SiteCreditPayment::create([
                'site_id' => $siteId,
                'amount' => -$request->amount,
                'payment_method' => $payment->payment_method,
                'status' => 'completed',
                'metadata' => [
                    'type' => 'refund',
                    'original_payment_id' => $payment->id,
                    'reason' => $request->reason,
                ],
            ]);

            // Update site credit balance
            $siteCredit = CreditManager::getSiteCredit($siteId);
            $siteCredit->decrement('balance', $request->amount);

            return response()->json(['status' => 'success', 'data' => $refund]);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }
}
