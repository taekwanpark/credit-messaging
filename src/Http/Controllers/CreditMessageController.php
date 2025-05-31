<?php

namespace Techigh\CreditMessaging\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Techigh\CreditMessaging\Settings\Entities\CreditMessage\CreditMessage;
use Techigh\CreditMessaging\Facades\MessageRouter;

class CreditMessageController extends Controller
{
    public function index(Request $request, string $siteId): JsonResponse
    {
        $query = CreditMessage::where('site_id', $siteId);

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Filter by message type
        if ($request->has('message_type')) {
            $query->where('message_type', $request->message_type);
        }

        // Filter by routing strategy
        if ($request->has('routing_strategy')) {
            $query->where('routing_strategy', $request->routing_strategy);
        }

        $messages = $query->orderBy('created_at', 'desc')->paginate(15);
        return response()->json(['status' => 'success', 'data' => $messages]);
    }

    public function store(Request $request, string $siteId): JsonResponse
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'message_content' => 'required|string',
            'message_type' => 'required|string|in:alimtalk,sms,lms,mms',
            'routing_strategy' => 'required|string|in:alimtalk_first,sms_only,cost_optimized',
            'recipients' => 'required|array|min:1',
            'scheduled_at' => 'sometimes|date|after:now',
            'metadata' => 'sometimes|array',
        ]);

        $messageData = array_merge($request->all(), [
            'site_id' => $siteId,
            'status' => 'draft'
        ]);

        $message = CreditMessage::create($messageData);
        return response()->json(['status' => 'success', 'data' => $message], 201);
    }

    public function show(Request $request, string $siteId, CreditMessage $message): JsonResponse
    {
        if ($message->site_id !== $siteId) {
            return response()->json(['status' => 'error', 'message' => 'Message not found'], 404);
        }

        return response()->json(['status' => 'success', 'data' => $message]);
    }

    public function update(Request $request, string $siteId, CreditMessage $message): JsonResponse
    {
        if ($message->site_id !== $siteId) {
            return response()->json(['status' => 'error', 'message' => 'Message not found'], 404);
        }

        if (!$message->isDraft()) {
            return response()->json(['status' => 'error', 'message' => 'Only draft messages can be updated'], 400);
        }

        $request->validate([
            'title' => 'sometimes|string|max:255',
            'message_content' => 'sometimes|string',
            'message_type' => 'sometimes|string|in:alimtalk,sms,lms,mms',
            'routing_strategy' => 'sometimes|string|in:alimtalk_first,sms_only,cost_optimized',
            'recipients' => 'sometimes|array|min:1',
            'scheduled_at' => 'sometimes|date|after:now',
            'metadata' => 'sometimes|array',
        ]);

        $message->update($request->only([
            'title',
            'message_content',
            'message_type',
            'routing_strategy',
            'recipients',
            'scheduled_at',
            'metadata'
        ]));

        return response()->json(['status' => 'success', 'data' => $message]);
    }

    public function destroy(Request $request, string $siteId, CreditMessage $message): JsonResponse
    {
        if ($message->site_id !== $siteId) {
            return response()->json(['status' => 'error', 'message' => 'Message not found'], 404);
        }

        if (!$message->isDraft() && !$message->isScheduled()) {
            return response()->json(['status' => 'error', 'message' => 'Only draft or scheduled messages can be deleted'], 400);
        }

        $message->delete();

        return response()->json(['status' => 'success', 'message' => 'Message deleted successfully']);
    }

    public function send(Request $request, string $siteId, CreditMessage $message): JsonResponse
    {
        if ($message->site_id !== $siteId) {
            return response()->json(['status' => 'error', 'message' => 'Message not found'], 404);
        }

        if (!$message->isDraft()) {
            return response()->json(['status' => 'error', 'message' => 'Only draft messages can be sent'], 400);
        }

        try {
            $result = MessageRouter::sendMessage($message);
            return response()->json(['status' => 'success', 'data' => $result]);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    public function schedule(Request $request, string $siteId, CreditMessage $message): JsonResponse
    {
        if ($message->site_id !== $siteId) {
            return response()->json(['status' => 'error', 'message' => 'Message not found'], 404);
        }

        if (!$message->isDraft()) {
            return response()->json(['status' => 'error', 'message' => 'Only draft messages can be scheduled'], 400);
        }

        $request->validate([
            'scheduled_at' => 'required|date|after:now',
        ]);

        $message->update([
            'scheduled_at' => $request->scheduled_at,
        ]);

        try {
            MessageRouter::scheduleMessage($message);
            return response()->json(['status' => 'success', 'message' => 'Message scheduled successfully']);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    public function cancel(Request $request, string $siteId, CreditMessage $message): JsonResponse
    {
        if ($message->site_id !== $siteId) {
            return response()->json(['status' => 'error', 'message' => 'Message not found'], 404);
        }

        if (!$message->isScheduled()) {
            return response()->json(['status' => 'error', 'message' => 'Only scheduled messages can be cancelled'], 400);
        }

        $message->update(['status' => 'cancelled']);

        return response()->json(['status' => 'success', 'message' => 'Message cancelled successfully']);
    }

    public function estimate(Request $request, string $siteId, CreditMessage $message): JsonResponse
    {
        if ($message->site_id !== $siteId) {
            return response()->json(['status' => 'error', 'message' => 'Message not found'], 404);
        }

        try {
            $estimation = MessageRouter::estimateMessageCost(
                $siteId,
                $message->message_type,
                count($message->recipients ?? []),
                $message->message_content
            );

            return response()->json(['status' => 'success', 'data' => $estimation]);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    public function status(Request $request, string $siteId, CreditMessage $message): JsonResponse
    {
        if ($message->site_id !== $siteId) {
            return response()->json(['status' => 'error', 'message' => 'Message not found'], 404);
        }

        $status = [
            'id' => $message->id,
            'status' => $message->status,
            'total_recipients' => $message->total_recipients,
            'success_count' => $message->success_count,
            'failed_count' => $message->failed_count,
            'success_rate' => $message->success_rate,
            'estimated_cost' => $message->estimated_cost,
            'actual_cost' => $message->actual_cost,
            'sent_at' => $message->sent_at,
            'scheduled_at' => $message->scheduled_at,
        ];

        return response()->json(['status' => 'success', 'data' => $status]);
    }
}
