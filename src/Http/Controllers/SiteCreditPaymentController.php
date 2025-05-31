<?php

namespace Techigh\CreditMessaging\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Techigh\CreditMessaging\Models\SiteCreditPayment;
use Techigh\CreditMessaging\Models\SiteCredit;
use Techigh\CreditMessaging\Facades\CreditManager;

class SiteCreditPaymentController extends Controller
{
    /**
     * Get payment records with filtering and pagination
     */
    public function index(Request $request, string $siteId): JsonResponse
    {
        try {
            $request->validate([
                'per_page' => 'integer|min:1|max:100',
                'page' => 'integer|min:1',
                'search' => 'string|max:255',
                'status' => 'string|in:pending,processing,completed,failed,cancelled',
                'payment_method' => 'string|in:card,bank_transfer,virtual_account,mobile',
                'sort_by' => 'string|in:id,amount,status,payment_method,created_at,completed_at',
                'sort_order' => 'string|in:asc,desc'
            ]);

            $query = SiteCreditPayment::where('site_id', $siteId);

            // Apply filters
            if ($request->filled('search')) {
                $search = $request->input('search');
                $query->where(function ($q) use ($search) {
                    $q->where('transaction_id', 'like', "%{$search}%")
                      ->orWhere('payment_gateway_transaction_id', 'like', "%{$search}%")
                      ->orWhere('amount', 'like', "%{$search}%");
                });
            }

            if ($request->filled('status')) {
                $query->where('status', $request->input('status'));
            }

            if ($request->filled('payment_method')) {
                $query->where('payment_method', $request->input('payment_method'));
            }

            // Apply sorting
            $sortBy = $request->input('sort_by', 'created_at');
            $sortOrder = $request->input('sort_order', 'desc');
            $query->orderBy($sortBy, $sortOrder);

            // Apply pagination
            $perPage = $request->input('per_page', 15);
            $payments = $query->paginate($perPage);

            return response()->json([
                'status' => 'success',
                'data' => $payments
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Failed to get site credit payments', [
                'site_id' => $siteId,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve payment records'
            ], 500);
        }
    }

    /**
     * Create a new payment request
     */
    public function store(Request $request, string $siteId): JsonResponse
    {
        try {
            $request->validate([
                'amount' => 'required|numeric|min:1000', // Minimum 1000 credits
                'payment_method' => 'required|string|in:card,bank_transfer,virtual_account,mobile',
                'return_url' => 'nullable|url',
                'webhook_url' => 'nullable|url',
                'customer_info' => 'nullable|array',
                'customer_info.name' => 'nullable|string|max:255',
                'customer_info.email' => 'nullable|email',
                'customer_info.phone' => 'nullable|string|max:20'
            ]);

            // Check if site exists
            $siteCredit = SiteCredit::where('site_id', $siteId)->first();
            if (!$siteCredit) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Site credit configuration not found'
                ], 404);
            }

            DB::beginTransaction();

            // Generate unique transaction ID
            $transactionId = 'PAY_' . strtoupper(uniqid());

            $payment = SiteCreditPayment::create([
                'site_id' => $siteId,
                'transaction_id' => $transactionId,
                'amount' => $request->input('amount'),
                'payment_method' => $request->input('payment_method'),
                'status' => 'pending',
                'return_url' => $request->input('return_url'),
                'webhook_url' => $request->input('webhook_url'),
                'customer_info' => $request->input('customer_info', []),
                'metadata' => [
                    'created_by_api' => true,
                    'user_agent' => $request->userAgent(),
                    'ip_address' => $request->ip()
                ]
            ]);

            // Here you would integrate with actual payment gateway
            // For this example, we'll simulate the payment gateway response
            $paymentGatewayResponse = $this->simulatePaymentGatewayInitiation($payment);

            $payment->update([
                'payment_gateway_transaction_id' => $paymentGatewayResponse['gateway_transaction_id'],
                'payment_url' => $paymentGatewayResponse['payment_url']
            ]);

            DB::commit();

            Log::info('Payment request created', [
                'site_id' => $siteId,
                'payment_id' => $payment->id,
                'transaction_id' => $transactionId,
                'amount' => $payment->amount
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Payment request created successfully',
                'data' => [
                    'payment' => $payment,
                    'payment_url' => $payment->payment_url,
                    'transaction_id' => $payment->transaction_id
                ]
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
            Log::error('Failed to create payment request', [
                'site_id' => $siteId,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to create payment request'
            ], 500);
        }
    }

    /**
     * Get a specific payment record
     */
    public function show(Request $request, string $siteId, SiteCreditPayment $payment): JsonResponse
    {
        try {
            // Ensure payment belongs to the site
            if ($payment->site_id !== $siteId) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Payment record not found'
                ], 404);
            }

            return response()->json([
                'status' => 'success',
                'data' => $payment
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to get payment record', [
                'site_id' => $siteId,
                'payment_id' => $payment->id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve payment record'
            ], 500);
        }
    }

    /**
     * Complete a payment (usually called by payment gateway webhook)
     */
    public function complete(Request $request, string $siteId, SiteCreditPayment $payment): JsonResponse
    {
        try {
            // Ensure payment belongs to the site
            if ($payment->site_id !== $siteId) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Payment record not found'
                ], 404);
            }

            // Only allow completing pending or processing payments
            if (!in_array($payment->status, ['pending', 'processing'])) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Payment cannot be completed in current status'
                ], 400);
            }

            $request->validate([
                'gateway_transaction_id' => 'nullable|string|max:255',
                'gateway_response' => 'nullable|array',
                'receipt_url' => 'nullable|url'
            ]);

            DB::beginTransaction();

            // Update payment status to completed
            $payment->update([
                'status' => 'completed',
                'completed_at' => now(),
                'payment_gateway_transaction_id' => $request->input('gateway_transaction_id', $payment->payment_gateway_transaction_id),
                'gateway_response' => $request->input('gateway_response', []),
                'receipt_url' => $request->input('receipt_url')
            ]);

            // Add credits to site balance
            $result = CreditManager::addCredits(
                $siteId,
                $payment->amount,
                'payment',
                "Payment completed - Transaction ID: {$payment->transaction_id}",
                $payment->transaction_id
            );

            if (!$result['success']) {
                DB::rollBack();
                Log::error('Failed to add credits after payment completion', [
                    'site_id' => $siteId,
                    'payment_id' => $payment->id,
                    'error' => $result['message']
                ]);

                return response()->json([
                    'status' => 'error',
                    'message' => 'Payment completed but failed to add credits'
                ], 500);
            }

            DB::commit();

            Log::info('Payment completed successfully', [
                'site_id' => $siteId,
                'payment_id' => $payment->id,
                'transaction_id' => $payment->transaction_id,
                'amount' => $payment->amount,
                'new_balance' => $result['new_balance']
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Payment completed successfully',
                'data' => [
                    'payment' => $payment->fresh(),
                    'credit_result' => $result
                ]
            ]);
        } catch (ValidationException $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to complete payment', [
                'site_id' => $siteId,
                'payment_id' => $payment->id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to complete payment'
            ], 500);
        }
    }

    /**
     * Mark a payment as failed
     */
    public function fail(Request $request, string $siteId, SiteCreditPayment $payment): JsonResponse
    {
        try {
            // Ensure payment belongs to the site
            if ($payment->site_id !== $siteId) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Payment record not found'
                ], 404);
            }

            // Only allow failing pending or processing payments
            if (!in_array($payment->status, ['pending', 'processing'])) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Payment cannot be failed in current status'
                ], 400);
            }

            $request->validate([
                'failure_reason' => 'required|string|max:255',
                'gateway_response' => 'nullable|array'
            ]);

            DB::beginTransaction();

            $payment->update([
                'status' => 'failed',
                'failure_reason' => $request->input('failure_reason'),
                'gateway_response' => $request->input('gateway_response', []),
                'failed_at' => now()
            ]);

            DB::commit();

            Log::info('Payment marked as failed', [
                'site_id' => $siteId,
                'payment_id' => $payment->id,
                'transaction_id' => $payment->transaction_id,
                'failure_reason' => $payment->failure_reason
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Payment marked as failed',
                'data' => $payment->fresh()
            ]);
        } catch (ValidationException $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to mark payment as failed', [
                'site_id' => $siteId,
                'payment_id' => $payment->id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to update payment status'
            ], 500);
        }
    }

    /**
     * Simulate payment gateway initiation (replace with real gateway integration)
     */
    private function simulatePaymentGatewayInitiation(SiteCreditPayment $payment): array
    {
        // This is a simulation - replace with actual payment gateway integration
        $gatewayTransactionId = 'GW_' . strtoupper(uniqid());
        
        // Generate a mock payment URL based on payment method
        $baseUrl = config('app.url');
        $paymentUrl = match ($payment->payment_method) {
            'card' => "{$baseUrl}/payment/card/{$gatewayTransactionId}",
            'bank_transfer' => "{$baseUrl}/payment/bank/{$gatewayTransactionId}",
            'virtual_account' => "{$baseUrl}/payment/va/{$gatewayTransactionId}",
            'mobile' => "{$baseUrl}/payment/mobile/{$gatewayTransactionId}",
            default => "{$baseUrl}/payment/{$gatewayTransactionId}"
        };

        return [
            'gateway_transaction_id' => $gatewayTransactionId,
            'payment_url' => $paymentUrl,
            'expires_at' => now()->addHours(1)->toISOString()
        ];
    }
}