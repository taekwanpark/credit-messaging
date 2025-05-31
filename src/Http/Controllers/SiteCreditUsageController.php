<?php

namespace Techigh\CreditMessaging\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Techigh\CreditMessaging\Models\SiteCreditUsage;
use Techigh\CreditMessaging\Facades\CreditManager;
use Carbon\Carbon;

class SiteCreditUsageController extends Controller
{
    /**
     * Get credit usage records with filtering and pagination
     */
    public function index(Request $request, string $siteId): JsonResponse
    {
        try {
            $request->validate([
                'per_page' => 'integer|min:1|max:100',
                'page' => 'integer|min:1',
                'search' => 'string|max:255',
                'usage_type' => 'string|in:charge,refund,payment',
                'message_type' => 'string|in:alimtalk,sms,lms,mms',
                'start_date' => 'date',
                'end_date' => 'date|after_or_equal:start_date',
                'sort_by' => 'string|in:id,amount,usage_type,message_type,created_at,updated_at',
                'sort_order' => 'string|in:asc,desc'
            ]);

            $query = SiteCreditUsage::where('site_id', $siteId);

            // Apply filters
            if ($request->filled('search')) {
                $search = $request->input('search');
                $query->where(function ($q) use ($search) {
                    $q->where('description', 'like', "%{$search}%")
                      ->orWhere('reference_id', 'like', "%{$search}%");
                });
            }

            if ($request->filled('usage_type')) {
                $query->where('usage_type', $request->input('usage_type'));
            }

            if ($request->filled('message_type')) {
                $query->where('message_type', $request->input('message_type'));
            }

            if ($request->filled('start_date')) {
                $query->where('created_at', '>=', Carbon::parse($request->input('start_date'))->startOfDay());
            }

            if ($request->filled('end_date')) {
                $query->where('created_at', '<=', Carbon::parse($request->input('end_date'))->endOfDay());
            }

            // Apply sorting
            $sortBy = $request->input('sort_by', 'created_at');
            $sortOrder = $request->input('sort_order', 'desc');
            $query->orderBy($sortBy, $sortOrder);

            // Apply pagination
            $perPage = $request->input('per_page', 15);
            $usages = $query->paginate($perPage);

            return response()->json([
                'status' => 'success',
                'data' => $usages
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Failed to get site credit usages', [
                'site_id' => $siteId,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve usage records'
            ], 500);
        }
    }

    /**
     * Export usage records to CSV/Excel
     */
    public function export(Request $request, string $siteId): JsonResponse
    {
        try {
            $request->validate([
                'format' => 'required|string|in:csv,excel',
                'start_date' => 'required|date',
                'end_date' => 'required|date|after_or_equal:start_date',
                'usage_type' => 'nullable|string|in:charge,refund,payment',
                'message_type' => 'nullable|string|in:alimtalk,sms,lms,mms'
            ]);

            $query = SiteCreditUsage::where('site_id', $siteId)
                ->whereBetween('created_at', [
                    Carbon::parse($request->input('start_date'))->startOfDay(),
                    Carbon::parse($request->input('end_date'))->endOfDay()
                ]);

            // Apply filters
            if ($request->filled('usage_type')) {
                $query->where('usage_type', $request->input('usage_type'));
            }

            if ($request->filled('message_type')) {
                $query->where('message_type', $request->input('message_type'));
            }

            $usages = $query->orderBy('created_at', 'desc')->get();

            // Generate export data
            $exportData = $usages->map(function ($usage) {
                return [
                    'ID' => $usage->id,
                    'Site ID' => $usage->site_id,
                    'Usage Type' => ucfirst($usage->usage_type),
                    'Message Type' => strtoupper($usage->message_type ?? 'N/A'),
                    'Amount' => $usage->amount,
                    'Description' => $usage->description,
                    'Reference ID' => $usage->reference_id,
                    'Balance Before' => $usage->balance_before,
                    'Balance After' => $usage->balance_after,
                    'Created At' => $usage->created_at->format('Y-m-d H:i:s')
                ];
            });

            $format = $request->input('format');
            $filename = "credit_usage_{$siteId}_" . now()->format('Y-m-d_H-i-s') . ".{$format}";

            // For this implementation, we'll return the data as JSON
            // In a real implementation, you might want to use packages like 
            // Laravel Excel or generate actual file downloads
            return response()->json([
                'status' => 'success',
                'message' => 'Export data generated successfully',
                'data' => [
                    'filename' => $filename,
                    'format' => $format,
                    'total_records' => $exportData->count(),
                    'export_data' => $exportData,
                    'summary' => [
                        'total_charges' => $usages->where('usage_type', 'charge')->sum('amount'),
                        'total_refunds' => $usages->where('usage_type', 'refund')->sum('amount'),
                        'total_payments' => $usages->where('usage_type', 'payment')->sum('amount'),
                        'period' => [
                            'start' => $request->input('start_date'),
                            'end' => $request->input('end_date')
                        ]
                    ]
                ]
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Failed to export site credit usages', [
                'site_id' => $siteId,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to export usage records'
            ], 500);
        }
    }

    /**
     * Get usage statistics and analytics
     */
    public function getStats(Request $request, string $siteId): JsonResponse
    {
        try {
            $request->validate([
                'period' => 'string|in:today,week,month,year,custom',
                'start_date' => 'date|required_if:period,custom',
                'end_date' => 'date|required_if:period,custom|after_or_equal:start_date'
            ]);

            $period = $request->input('period', 'month');
            
            // Determine date range
            switch ($period) {
                case 'today':
                    $startDate = Carbon::today();
                    $endDate = Carbon::today()->endOfDay();
                    break;
                case 'week':
                    $startDate = Carbon::now()->startOfWeek();
                    $endDate = Carbon::now()->endOfWeek();
                    break;
                case 'month':
                    $startDate = Carbon::now()->startOfMonth();
                    $endDate = Carbon::now()->endOfMonth();
                    break;
                case 'year':
                    $startDate = Carbon::now()->startOfYear();
                    $endDate = Carbon::now()->endOfYear();
                    break;
                case 'custom':
                    $startDate = Carbon::parse($request->input('start_date'))->startOfDay();
                    $endDate = Carbon::parse($request->input('end_date'))->endOfDay();
                    break;
                default:
                    $startDate = Carbon::now()->startOfMonth();
                    $endDate = Carbon::now()->endOfMonth();
            }

            $query = SiteCreditUsage::where('site_id', $siteId)
                ->whereBetween('created_at', [$startDate, $endDate]);

            // Get overall statistics
            $totalCharges = $query->clone()->where('usage_type', 'charge')->sum('amount');
            $totalRefunds = $query->clone()->where('usage_type', 'refund')->sum('amount');
            $totalPayments = $query->clone()->where('usage_type', 'payment')->sum('amount');
            $totalTransactions = $query->clone()->count();

            // Get statistics by message type
            $messageTypeStats = $query->clone()
                ->where('usage_type', 'charge')
                ->whereNotNull('message_type')
                ->groupBy('message_type')
                ->selectRaw('message_type, COUNT(*) as count, SUM(amount) as total_amount')
                ->get()
                ->keyBy('message_type');

            // Get daily usage trend
            $dailyStats = $query->clone()
                ->selectRaw('DATE(created_at) as date, usage_type, SUM(amount) as total_amount, COUNT(*) as count')
                ->groupBy('date', 'usage_type')
                ->orderBy('date')
                ->get()
                ->groupBy('date');

            // Get hourly distribution for today
            $hourlyStats = [];
            if ($period === 'today') {
                $hourlyStats = $query->clone()
                    ->selectRaw('HOUR(created_at) as hour, usage_type, SUM(amount) as total_amount, COUNT(*) as count')
                    ->groupBy('hour', 'usage_type')
                    ->orderBy('hour')
                    ->get()
                    ->groupBy('hour');
            }

            // Calculate average transaction value
            $avgChargeAmount = $totalTransactions > 0 ? ($totalCharges / $query->clone()->where('usage_type', 'charge')->count()) : 0;

            return response()->json([
                'status' => 'success',
                'data' => [
                    'period' => [
                        'type' => $period,
                        'start_date' => $startDate->format('Y-m-d'),
                        'end_date' => $endDate->format('Y-m-d')
                    ],
                    'overview' => [
                        'total_charges' => round($totalCharges, 2),
                        'total_refunds' => round($totalRefunds, 2),
                        'total_payments' => round($totalPayments, 2),
                        'net_usage' => round($totalCharges - $totalRefunds, 2),
                        'total_transactions' => $totalTransactions,
                        'average_charge_amount' => round($avgChargeAmount, 2)
                    ],
                    'message_type_breakdown' => $messageTypeStats->map(function ($stat) {
                        return [
                            'count' => $stat->count,
                            'total_amount' => round($stat->total_amount, 2),
                            'average_amount' => round($stat->total_amount / $stat->count, 2)
                        ];
                    }),
                    'daily_trends' => $dailyStats->map(function ($dayStats) {
                        $trends = [];
                        foreach ($dayStats as $stat) {
                            $trends[$stat->usage_type] = [
                                'count' => $stat->count,
                                'total_amount' => round($stat->total_amount, 2)
                            ];
                        }
                        return $trends;
                    }),
                    'hourly_distribution' => $period === 'today' ? $hourlyStats->map(function ($hourStats) {
                        $distribution = [];
                        foreach ($hourStats as $stat) {
                            $distribution[$stat->usage_type] = [
                                'count' => $stat->count,
                                'total_amount' => round($stat->total_amount, 2)
                            ];
                        }
                        return $distribution;
                    }) : null
                ]
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Failed to get usage statistics', [
                'site_id' => $siteId,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve usage statistics'
            ], 500);
        }
    }

    /**
     * Refund a credit usage (create a refund entry)
     */
    public function refund(Request $request, string $siteId, SiteCreditUsage $usage): JsonResponse
    {
        try {
            // Ensure usage belongs to the site
            if ($usage->site_id !== $siteId) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Usage record not found'
                ], 404);
            }

            // Only allow refunding charge transactions
            if ($usage->usage_type !== 'charge') {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Only charge transactions can be refunded'
                ], 400);
            }

            // Check if already refunded
            $existingRefund = SiteCreditUsage::where('site_id', $siteId)
                ->where('usage_type', 'refund')
                ->where('reference_id', $usage->reference_id)
                ->first();

            if ($existingRefund) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'This transaction has already been refunded'
                ], 400);
            }

            $request->validate([
                'reason' => 'required|string|max:255',
                'amount' => 'nullable|numeric|min:0.01|max:' . $usage->amount
            ]);

            $refundAmount = $request->input('amount', $usage->amount);
            $reason = $request->input('reason');

            DB::beginTransaction();

            $result = CreditManager::refundCredits(
                $siteId,
                $refundAmount,
                $usage->message_type,
                $reason,
                $usage->reference_id
            );

            if (!$result['success']) {
                DB::rollBack();
                return response()->json([
                    'status' => 'error',
                    'message' => $result['message']
                ], 400);
            }

            DB::commit();

            Log::info('Credit usage refunded', [
                'site_id' => $siteId,
                'usage_id' => $usage->id,
                'refund_amount' => $refundAmount,
                'reason' => $reason
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Refund processed successfully',
                'data' => $result
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
            Log::error('Failed to refund credit usage', [
                'site_id' => $siteId,
                'usage_id' => $usage->id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to process refund'
            ], 500);
        }
    }
}