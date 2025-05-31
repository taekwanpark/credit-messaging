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
        $messages = CreditMessage::where('site_id', $siteId)->paginate(15);
        return response()->json(['status' => 'success', 'data' => $messages]);
    }

    public function store(Request $request, string $siteId): JsonResponse
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'message_content' => 'required|string',
            'message_type' => 'required|string|in:alimtalk,sms,lms,mms',
            'recipients' => 'required|array|min:1',
        ]);

        $message = CreditMessage::create(array_merge($request->all(), ['site_id' => $siteId]));
        return response()->json(['status' => 'success', 'data' => $message], 201);
    }

    public function show(Request $request, string $siteId, CreditMessage $message): JsonResponse
    {
        if ($message->site_id !== $siteId) {
            return response()->json(['status' => 'error', 'message' => 'Message not found'], 404);
        }
        
        return response()->json(['status' => 'success', 'data' => $message]);
    }
}