<?php

namespace Techigh\CreditMessaging\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Techigh\CreditMessaging\Settings\Entities\SiteCredit\SiteCredit;
use Techigh\CreditMessaging\Settings\Entities\SiteCreditUsage\SiteCreditUsage;
use Techigh\CreditMessaging\Settings\Entities\SiteCreditPayment\SiteCreditPayment;
use Techigh\CreditMessaging\Facades\CreditManager;

class SiteCreditController extends Controller
{
    public function index(Request $request, string $siteId): JsonResponse
    {
        $credits = SiteCredit::where('site_id', $siteId)->paginate(15);
        return response()->json(['status' => 'success', 'data' => $credits]);
    }

    public function store(Request $request, string $siteId): JsonResponse
    {
        $request->validate([
            'balance' => 'sometimes|numeric|min:0',
            'alimtalk_cost' => 'required|numeric|min:0',
            'sms_cost' => 'required|numeric|min:0',
            'lms_cost' => 'required|numeric|min:0',
            'mms_cost' => 'required|numeric|min:0',
            'auto_charge_enabled' => 'sometimes|boolean',
            'auto_charge_threshold' => 'sometimes|numeric|min:0',
            'auto_charge_amount' => 'sometimes|numeric|min:0',
        ]);

        $creditData = array_merge($request->all(), ['site_id' => $siteId]);
        $credit = SiteCredit::updateOrCreate(['site_id' => $siteId], $creditData);

        return response()->json(['status' => 'success', 'data' => $credit], 201);
    }

    public function getBalance(Request $request, string $siteId): JsonResponse
    {
        try {
            $balance = CreditManager::getBalance($siteId);
            return response()->json(['status' => 'success', 'data' => ['balance' => $balance]]);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    public function getConfig(Request $request, string $siteId): JsonResponse
    {
        try {
            $siteCredit = CreditManager::getSiteCredit($siteId);
            return response()->json(['status' => 'success', 'data' => $siteCredit]);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    public function updateConfig(Request $request, string $siteId): JsonResponse
    {
        $request->validate([
            'alimtalk_cost' => 'sometimes|numeric|min:0',
            'sms_cost' => 'sometimes|numeric|min:0',
            'lms_cost' => 'sometimes|numeric|min:0',
            'mms_cost' => 'sometimes|numeric|min:0',
            'auto_charge_enabled' => 'sometimes|boolean',
            'auto_charge_threshold' => 'sometimes|numeric|min:0',
            'auto_charge_amount' => 'sometimes|numeric|min:0',
        ]);

        try {
            $siteCredit = CreditManager::getSiteCredit($siteId);
            $siteCredit->update($request->only([
                'alimtalk_cost',
                'sms_cost',
                'lms_cost',
                'mms_cost',
                'auto_charge_enabled',
                'auto_charge_threshold',
                'auto_charge_amount'
            ]));

            return response()->json(['status' => 'success', 'data' => $siteCredit]);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    public function charge(Request $request, string $siteId): JsonResponse
    {
        $request->validate([
            'message_type' => 'required|string|in:alimtalk,sms,lms,mms',
            'quantity' => 'required|integer|min:1',
            'metadata' => 'sometimes|array',
        ]);

        try {
            $usage = CreditManager::chargeCredits(
                $siteId,
                $request->message_type,
                $request->quantity
            );

            if ($request->has('metadata')) {
                $usage->update(['metadata' => $request->metadata]);
            }

            return response()->json(['status' => 'success', 'data' => $usage]);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    public function refund(Request $request, string $siteId): JsonResponse
    {
        $request->validate([
            'usage_id' => 'required|integer',
            'amount' => 'required|numeric|min:0',
            'reason' => 'required|string',
        ]);

        try {
            $usage = SiteCreditUsage::where('site_id', $siteId)
                ->where('id', $request->usage_id)
                ->firstOrFail();

            $success = CreditManager::refundCredits($usage, $request->amount, $request->reason);

            if ($success) {
                return response()->json(['status' => 'success', 'message' => 'Refund processed successfully']);
            } else {
                return response()->json(['status' => 'error', 'message' => 'Refund processing failed'], 500);
            }
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    public function statistics(Request $request, string $siteId): JsonResponse
    {
        $request->validate([
            'start_date' => 'sometimes|date',
            'end_date' => 'sometimes|date|after_or_equal:start_date',
        ]);

        $startDate = $request->start_date ?? now()->subDays(30);
        $endDate = $request->end_date ?? now();

        try {
            $stats = CreditManager::getUsageStats($siteId, $startDate, $endDate);

            // Additional statistics
            $siteCredit = CreditManager::getSiteCredit($siteId);

            $totalPayments = SiteCreditPayment::where('site_id', $siteId)
                ->where('status', 'completed')
                ->sum('amount');

            $totalUsages = SiteCreditUsage::where('site_id', $siteId)
                ->sum('total_cost');

            $totalRefunds = SiteCreditUsage::where('site_id', $siteId)
                ->sum('refund_amount');

            $statistics = array_merge($stats, [
                'current_balance' => $siteCredit->balance,
                'total_payments' => $totalPayments,
                'total_usages' => $totalUsages,
                'total_refunds' => $totalRefunds,
                'net_spending' => $totalUsages - $totalRefunds,
                'auto_charge_enabled' => $siteCredit->auto_charge_enabled,
                'auto_charge_threshold' => $siteCredit->auto_charge_threshold,
            ]);

            return response()->json(['status' => 'success', 'data' => $statistics]);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }
}
