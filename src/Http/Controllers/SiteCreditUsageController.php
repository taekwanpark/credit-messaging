<?php

namespace Techigh\CreditMessaging\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Techigh\CreditMessaging\Settings\Entities\SiteCreditUsage\SiteCreditUsage;
use Techigh\CreditMessaging\Facades\CreditManager;

class SiteCreditUsageController extends Controller
{
    public function index(Request $request, string $siteId): JsonResponse
    {
        $usages = SiteCreditUsage::where('site_id', $siteId)
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return response()->json(['status' => 'success', 'data' => $usages]);
    }

    public function show(Request $request, string $siteId, SiteCreditUsage $usage): JsonResponse
    {
        if ($usage->site_id !== $siteId) {
            return response()->json(['status' => 'error', 'message' => 'Usage not found'], 404);
        }

        return response()->json(['status' => 'success', 'data' => $usage]);
    }

    public function refund(Request $request, string $siteId, SiteCreditUsage $usage): JsonResponse
    {
        if ($usage->site_id !== $siteId) {
            return response()->json(['status' => 'error', 'message' => 'Usage not found'], 404);
        }

        $request->validate([
            'amount' => 'required|numeric|min:0',
            'reason' => 'required|string',
        ]);

        $amount = $request->amount;
        $reason = $request->reason;

        // Check if refund amount is valid
        $availableRefund = $usage->total_cost - ($usage->refund_amount ?? 0);
        if ($amount > $availableRefund) {
            return response()->json([
                'status' => 'error',
                'message' => 'Refund amount exceeds available refund amount',
                'available_refund' => $availableRefund
            ], 400);
        }

        try {
            $success = CreditManager::refundCredits($usage, $amount, $reason);

            if ($success) {
                return response()->json(['status' => 'success', 'message' => 'Refund processed successfully']);
            } else {
                return response()->json(['status' => 'error', 'message' => 'Refund processing failed'], 500);
            }
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    public function dailySummary(Request $request, string $siteId): JsonResponse
    {
        $request->validate([
            'start_date' => 'sometimes|date',
            'end_date' => 'sometimes|date|after_or_equal:start_date',
        ]);

        $startDate = $request->start_date ?? now()->subDays(30)->format('Y-m-d');
        $endDate = $request->end_date ?? now()->format('Y-m-d');

        $summary = SiteCreditUsage::where('site_id', $siteId)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->select([
                DB::raw('DATE(created_at) as date'),
                DB::raw('COUNT(*) as total_usages'),
                DB::raw('SUM(total_cost) as total_cost'),
                DB::raw('SUM(refund_amount) as total_refunds'),
                DB::raw('SUM(total_cost - COALESCE(refund_amount, 0)) as net_cost'),
                'message_type'
            ])
            ->groupBy('date', 'message_type')
            ->orderBy('date', 'desc')
            ->get();

        return response()->json(['status' => 'success', 'data' => $summary]);
    }

    public function monthlySummary(Request $request, string $siteId): JsonResponse
    {
        $request->validate([
            'start_month' => 'sometimes|date_format:Y-m',
            'end_month' => 'sometimes|date_format:Y-m|after_or_equal:start_month',
        ]);

        $startMonth = $request->start_month ?? now()->subMonths(12)->format('Y-m');
        $endMonth = $request->end_month ?? now()->format('Y-m');

        $summary = SiteCreditUsage::where('site_id', $siteId)
            ->whereRaw('DATE_FORMAT(created_at, "%Y-%m") BETWEEN ? AND ?', [$startMonth, $endMonth])
            ->select([
                DB::raw('DATE_FORMAT(created_at, "%Y-%m") as month'),
                DB::raw('COUNT(*) as total_usages'),
                DB::raw('SUM(total_cost) as total_cost'),
                DB::raw('SUM(refund_amount) as total_refunds'),
                DB::raw('SUM(total_cost - COALESCE(refund_amount, 0)) as net_cost'),
                'message_type'
            ])
            ->groupBy('month', 'message_type')
            ->orderBy('month', 'desc')
            ->get();

        return response()->json(['status' => 'success', 'data' => $summary]);
    }
}
