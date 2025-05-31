<?php

namespace Techigh\CreditMessaging\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Techigh\CreditMessaging\Settings\Entities\SiteCredit\SiteCredit;
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
            'initial_balance' => 'required|numeric|min:0',
            'alimtalk_cost' => 'required|numeric|min:0',
            'sms_cost' => 'required|numeric|min:0',
            'lms_cost' => 'required|numeric|min:0',
            'mms_cost' => 'required|numeric|min:0',
        ]);

        $credit = SiteCredit::updateOrCreate(['site_id' => $siteId], $request->all());
        return response()->json(['status' => 'success', 'data' => $credit], 201);
    }

    public function getBalance(Request $request, string $siteId): JsonResponse
    {
        $credit = SiteCredit::where('site_id', $siteId)->first();
        if (!$credit) {
            return response()->json(['status' => 'error', 'message' => 'Site credit not found'], 404);
        }
        
        return response()->json(['status' => 'success', 'data' => ['balance' => $credit->balance]]);
    }
}