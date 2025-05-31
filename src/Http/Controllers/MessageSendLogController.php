<?php

namespace Techigh\CreditMessaging\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Techigh\CreditMessaging\Settings\Entities\MessageSendLog\MessageSendLog;

class MessageSendLogController extends Controller
{
    public function index(Request $request, string $siteId): JsonResponse
    {
        $query = MessageSendLog::where('site_id', $siteId);

        // Filter by message type
        if ($request->has('message_type')) {
            $query->where('message_type', $request->message_type);
        }

        // Filter by delivery status
        if ($request->has('delivery_status')) {
            $query->where('delivery_status', $request->delivery_status);
        }

        // Filter by date range
        if ($request->has('start_date')) {
            $query->whereDate('created_at', '>=', $request->start_date);
        }

        if ($request->has('end_date')) {
            $query->whereDate('created_at', '<=', $request->end_date);
        }

        $logs = $query->orderBy('created_at', 'desc')->paginate(15);

        return response()->json(['status' => 'success', 'data' => $logs]);
    }

    public function show(Request $request, string $siteId, MessageSendLog $log): JsonResponse
    {
        if ($log->site_id !== $siteId) {
            return response()->json(['status' => 'error', 'message' => 'Log not found'], 404);
        }

        return response()->json(['status' => 'success', 'data' => $log]);
    }

    public function getByMessage(Request $request, string $siteId, string $messageId): JsonResponse
    {
        $logs = MessageSendLog::where('site_id', $siteId)
            ->where('credit_message_id', $messageId)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json(['status' => 'success', 'data' => $logs]);
    }

    public function deliveryRate(Request $request, string $siteId): JsonResponse
    {
        $request->validate([
            'start_date' => 'sometimes|date',
            'end_date' => 'sometimes|date|after_or_equal:start_date',
            'message_type' => 'sometimes|string|in:alimtalk,sms,lms,mms',
        ]);

        $query = MessageSendLog::where('site_id', $siteId);

        if ($request->has('start_date')) {
            $query->whereDate('created_at', '>=', $request->start_date);
        }

        if ($request->has('end_date')) {
            $query->whereDate('created_at', '<=', $request->end_date);
        }

        if ($request->has('message_type')) {
            $query->where('message_type', $request->message_type);
        }

        $stats = $query->select([
            'message_type',
            DB::raw('COUNT(*) as total_sent'),
            DB::raw('SUM(CASE WHEN delivery_status = "delivered" THEN 1 ELSE 0 END) as delivered'),
            DB::raw('SUM(CASE WHEN delivery_status = "failed" THEN 1 ELSE 0 END) as failed'),
            DB::raw('SUM(CASE WHEN delivery_status = "pending" THEN 1 ELSE 0 END) as pending'),
            DB::raw('ROUND((SUM(CASE WHEN delivery_status = "delivered" THEN 1 ELSE 0 END) / COUNT(*)) * 100, 2) as delivery_rate')
        ])
            ->groupBy('message_type')
            ->get();

        return response()->json(['status' => 'success', 'data' => $stats]);
    }

    public function failureReasons(Request $request, string $siteId): JsonResponse
    {
        $request->validate([
            'start_date' => 'sometimes|date',
            'end_date' => 'sometimes|date|after_or_equal:start_date',
            'message_type' => 'sometimes|string|in:alimtalk,sms,lms,mms',
        ]);

        $query = MessageSendLog::where('site_id', $siteId)
            ->where('delivery_status', 'failed')
            ->whereNotNull('error_code');

        if ($request->has('start_date')) {
            $query->whereDate('created_at', '>=', $request->start_date);
        }

        if ($request->has('end_date')) {
            $query->whereDate('created_at', '<=', $request->end_date);
        }

        if ($request->has('message_type')) {
            $query->where('message_type', $request->message_type);
        }

        $failureReasons = $query->select([
            'error_code',
            'message_type',
            DB::raw('COUNT(*) as count'),
            DB::raw('ROUND((COUNT(*) / (SELECT COUNT(*) FROM message_send_logs WHERE site_id = ? AND delivery_status = "failed")) * 100, 2) as percentage')
        ])
            ->groupBy('error_code', 'message_type')
            ->orderBy('count', 'desc')
            ->setBindings([$siteId])
            ->get();

        return response()->json(['status' => 'success', 'data' => $failureReasons]);
    }
}
