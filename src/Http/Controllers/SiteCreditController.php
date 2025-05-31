<?php

namespace Techigh\CreditMessaging\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Techigh\CreditMessaging\Models\SiteCredit;
use Techigh\CreditMessaging\Facades\CreditManager;

class SiteCreditController extends Controller
{
    /**
     * Get site credits with filtering and pagination
     */
    public function index(Request $request, string $siteId): JsonResponse
    {
        try {
            $request->validate([
                'per_page' => 'integer|min:1|max:100',
                'page' => 'integer|min:1',
                'search' => 'string|max:255',
                'sort_by' => 'string|in:id,balance,created_at,updated_at',
                'sort_order' => 'string|in:asc,desc'
            ]);

            $query = SiteCredit::where('site_id', $siteId);

            // Apply search filter
            if ($request->filled('search')) {
                $search = $request->input('search');
                $query->where(function ($q) use ($search) {
                    $q->where('site_id', 'like', "%{$search}%")
                      ->orWhere('balance', 'like', "%{$search}%");
                });
            }

            // Apply sorting
            $sortBy = $request->input('sort_by', 'created_at');
            $sortOrder = $request->input('sort_order', 'desc');
            $query->orderBy($sortBy, $sortOrder);

            // Apply pagination
            $perPage = $request->input('per_page', 15);
            $credits = $query->paginate($perPage);

            return response()->json([
                'status' => 'success',
                'data' => $credits
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Failed to get site credits', [
                'site_id' => $siteId,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve credits'
            ], 500);
        }
    }

    /**
     * Create or update site credit configuration
     */
    public function store(Request $request, string $siteId): JsonResponse
    {
        try {
            $request->validate([
                'initial_balance' => 'required|numeric|min:0',
                'alimtalk_cost' => 'required|numeric|min:0',
                'sms_cost' => 'required|numeric|min:0',
                'lms_cost' => 'required|numeric|min:0',
                'mms_cost' => 'required|numeric|min:0',
                'auto_charge_enabled' => 'boolean',
                'auto_charge_threshold' => 'nullable|numeric|min:0',
                'auto_charge_amount' => 'nullable|numeric|min:0'
            ]);

            DB::beginTransaction();

            $credit = SiteCredit::updateOrCreate(
                ['site_id' => $siteId],
                [
                    'balance' => $request->input('initial_balance'),
                    'alimtalk_cost' => $request->input('alimtalk_cost'),
                    'sms_cost' => $request->input('sms_cost'),
                    'lms_cost' => $request->input('lms_cost'),
                    'mms_cost' => $request->input('mms_cost'),
                    'auto_charge_enabled' => $request->input('auto_charge_enabled', false),
                    'auto_charge_threshold' => $request->input('auto_charge_threshold'),
                    'auto_charge_amount' => $request->input('auto_charge_amount')
                ]
            );

            DB::commit();

            Log::info('Site credit created/updated', [
                'site_id' => $siteId,
                'credit_id' => $credit->id
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Site credit configuration saved successfully',
                'data' => $credit
            ], 201);
        } catch (ValidationException $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to create/update site credit', [
                'site_id' => $siteId,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to save credit configuration'
            ], 500);
        }
    }

    /**
     * Get current balance for a site
     */
    public function getBalance(Request $request, string $siteId): JsonResponse
    {
        try {
            $credit = SiteCredit::where('site_id', $siteId)->first();

            if (!$credit) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Site credit not found'
                ], 404);
            }

            return response()->json([
                'status' => 'success',
                'data' => [
                    'site_id' => $siteId,
                    'balance' => $credit->balance,
                    'auto_charge_enabled' => $credit->auto_charge_enabled,
                    'should_auto_charge' => $credit->shouldAutoCharge(),
                    'costs' => [
                        'alimtalk' => $credit->alimtalk_cost,
                        'sms' => $credit->sms_cost,
                        'lms' => $credit->lms_cost,
                        'mms' => $credit->mms_cost
                    ]
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to get balance', [
                'site_id' => $siteId,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve balance'
            ], 500);
        }
    }

    /**
     * Charge credits for message sending
     */
    public function charge(Request $request, string $siteId): JsonResponse
    {
        try {
            $request->validate([
                'amount' => 'required|numeric|min:0.01',
                'message_type' => 'required|string|in:alimtalk,sms,lms,mms',
                'description' => 'string|max:255',
                'message_id' => 'string|max:255'
            ]);

            $amount = $request->input('amount');
            $messageType = $request->input('message_type');
            $description = $request->input('description', "Charge for {$messageType} message");
            $messageId = $request->input('message_id');

            $result = CreditManager::chargeCredits(
                $siteId,
                $amount,
                $messageType,
                $description,
                $messageId
            );

            if (!$result['success']) {
                return response()->json([
                    'status' => 'error',
                    'message' => $result['message']
                ], 400);
            }

            Log::info('Credits charged successfully', [
                'site_id' => $siteId,
                'amount' => $amount,
                'message_type' => $messageType,
                'remaining_balance' => $result['remaining_balance']
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Credits charged successfully',
                'data' => $result
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Failed to charge credits', [
                'site_id' => $siteId,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to charge credits'
            ], 500);
        }
    }

    /**
     * Get usage statistics for a site
     */
    public function getUsageStats(Request $request, string $siteId): JsonResponse
    {
        try {
            $request->validate([
                'period' => 'string|in:today,week,month,year,custom',
                'start_date' => 'date|required_if:period,custom',
                'end_date' => 'date|required_if:period,custom|after_or_equal:start_date'
            ]);

            $period = $request->input('period', 'month');
            $startDate = $request->input('start_date');
            $endDate = $request->input('end_date');

            $stats = CreditManager::getUsageStats($siteId, $period, $startDate, $endDate);

            return response()->json([
                'status' => 'success',
                'data' => $stats
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Failed to get usage stats', [
                'site_id' => $siteId,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve usage statistics'
            ], 500);
        }
    }
}