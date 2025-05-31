<?php

namespace Techigh\CreditMessaging\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Techigh\CreditMessaging\Models\MessageSendLog;
use Techigh\CreditMessaging\Facades\MessageRouter;
use Carbon\Carbon;

class MessageSendLogController extends Controller
{
    /**
     * Get message send logs with filtering and pagination
     */
    public function index(Request $request, string $siteId): JsonResponse
    {
        try {
            $request->validate([
                'per_page' => 'integer|min:1|max:100',
                'page' => 'integer|min:1',
                'search' => 'string|max:255',
                'message_type' => 'string|in:alimtalk,sms,lms,mms',
                'status' => 'string|in:pending,sent,delivered,failed,cancelled',
                'provider' => 'string|max:50',
                'start_date' => 'date',
                'end_date' => 'date|after_or_equal:start_date',
                'sort_by' => 'string|in:id,message_type,status,provider,sent_at,delivered_at,created_at',
                'sort_order' => 'string|in:asc,desc'
            ]);

            $query = MessageSendLog::where('site_id', $siteId);

            // Apply filters
            if ($request->filled('search')) {
                $search = $request->input('search');
                $query->where(function ($q) use ($search) {
                    $q->where('recipient', 'like', "%{$search}%")
                      ->orWhere('message_content', 'like', "%{$search}%")
                      ->orWhere('provider_message_id', 'like', "%{$search}%")
                      ->orWhere('reference_id', 'like', "%{$search}%");
                });
            }

            if ($request->filled('message_type')) {
                $query->where('message_type', $request->input('message_type'));
            }

            if ($request->filled('status')) {
                $query->where('status', $request->input('status'));
            }

            if ($request->filled('provider')) {
                $query->where('provider', $request->input('provider'));
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
            $logs = $query->paginate($perPage);

            return response()->json([
                'status' => 'success',
                'data' => $logs
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Failed to get message send logs', [
                'site_id' => $siteId,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve send logs'
            ], 500);
        }
    }

    /**
     * Get a specific message send log
     */
    public function show(Request $request, string $siteId, MessageSendLog $log): JsonResponse
    {
        try {
            // Ensure log belongs to the site
            if ($log->site_id !== $siteId) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Send log not found'
                ], 404);
            }

            // Include related message details if available
            $logData = $log->toArray();
            
            // Add delivery timeline if available
            $timeline = $this->buildDeliveryTimeline($log);
            $logData['delivery_timeline'] = $timeline;

            // Add cost information
            if ($log->cost > 0) {
                $logData['cost_breakdown'] = [
                    'base_cost' => $log->cost,
                    'message_type' => $log->message_type,
                    'provider' => $log->provider
                ];
            }

            return response()->json([
                'status' => 'success',
                'data' => $logData
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to get message send log', [
                'site_id' => $siteId,
                'log_id' => $log->id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve send log'
            ], 500);
        }
    }

    /**
     * Retry settlement for a failed send log
     */
    public function retrySettlement(Request $request, string $siteId, MessageSendLog $log): JsonResponse
    {
        try {
            // Ensure log belongs to the site
            if ($log->site_id !== $siteId) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Send log not found'
                ], 404);
            }

            // Only allow retry for failed or pending settlement logs
            if (!in_array($log->settlement_status, ['failed', 'pending', null])) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Settlement retry not allowed for current status'
                ], 400);
            }

            $request->validate([
                'force_retry' => 'boolean'
            ]);

            $forceRetry = $request->input('force_retry', false);

            // Check retry limit unless force retry is enabled
            if (!$forceRetry && $log->settlement_retry_count >= 3) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Maximum retry attempts reached. Use force_retry to override.'
                ], 400);
            }

            DB::beginTransaction();

            // Update retry information
            $log->update([
                'settlement_status' => 'retrying',
                'settlement_retry_count' => $forceRetry ? 0 : ($log->settlement_retry_count + 1),
                'last_settlement_attempt' => now()
            ]);

            try {
                // Attempt to process settlement through MessageRouter
                $result = MessageRouter::processSettlement($log);

                if ($result['success']) {
                    $log->update([
                        'settlement_status' => 'completed',
                        'settlement_completed_at' => now(),
                        'settlement_response' => $result['data']
                    ]);

                    $message = 'Settlement retry completed successfully';
                } else {
                    $log->update([
                        'settlement_status' => 'failed',
                        'settlement_error' => $result['message'],
                        'settlement_response' => $result['data'] ?? []
                    ]);

                    $message = 'Settlement retry failed: ' . $result['message'];
                }

                DB::commit();

                Log::info('Settlement retry processed', [
                    'site_id' => $siteId,
                    'log_id' => $log->id,
                    'success' => $result['success'],
                    'retry_count' => $log->settlement_retry_count
                ]);

                return response()->json([
                    'status' => $result['success'] ? 'success' : 'error',
                    'message' => $message,
                    'data' => [
                        'log' => $log->fresh(),
                        'settlement_result' => $result
                    ]
                ], $result['success'] ? 200 : 400);
            } catch (\Exception $settlementException) {
                $log->update([
                    'settlement_status' => 'failed',
                    'settlement_error' => $settlementException->getMessage()
                ]);

                DB::commit();

                Log::error('Settlement retry exception', [
                    'site_id' => $siteId,
                    'log_id' => $log->id,
                    'error' => $settlementException->getMessage()
                ]);

                return response()->json([
                    'status' => 'error',
                    'message' => 'Settlement retry failed due to system error',
                    'data' => [
                        'log' => $log->fresh(),
                        'error' => $settlementException->getMessage()
                    ]
                ], 500);
            }
        } catch (ValidationException $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to retry settlement', [
                'site_id' => $siteId,
                'log_id' => $log->id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retry settlement'
            ], 500);
        }
    }

    /**
     * Build delivery timeline for a message send log
     */
    private function buildDeliveryTimeline(MessageSendLog $log): array
    {
        $timeline = [];

        // Created
        $timeline[] = [
            'event' => 'created',
            'status' => 'completed',
            'timestamp' => $log->created_at,
            'description' => 'Message log created'
        ];

        // Sent
        if ($log->sent_at) {
            $timeline[] = [
                'event' => 'sent',
                'status' => 'completed',
                'timestamp' => $log->sent_at,
                'description' => 'Message sent to provider',
                'provider' => $log->provider,
                'provider_message_id' => $log->provider_message_id
            ];
        } else {
            $timeline[] = [
                'event' => 'sent',
                'status' => in_array($log->status, ['failed', 'cancelled']) ? 'failed' : 'pending',
                'timestamp' => null,
                'description' => 'Message sending'
            ];
        }

        // Delivered
        if ($log->delivered_at) {
            $timeline[] = [
                'event' => 'delivered',
                'status' => 'completed',
                'timestamp' => $log->delivered_at,
                'description' => 'Message delivered to recipient'
            ];
        } else {
            $deliveryStatus = match ($log->status) {
                'delivered' => 'completed',
                'failed', 'cancelled' => 'failed',
                default => 'pending'
            };

            $timeline[] = [
                'event' => 'delivered',
                'status' => $deliveryStatus,
                'timestamp' => null,
                'description' => 'Message delivery'
            ];
        }

        // Settlement
        if ($log->settlement_completed_at) {
            $timeline[] = [
                'event' => 'settlement',
                'status' => 'completed',
                'timestamp' => $log->settlement_completed_at,
                'description' => 'Cost settlement completed'
            ];
        } else {
            $settlementStatus = match ($log->settlement_status) {
                'completed' => 'completed',
                'failed' => 'failed',
                'retrying' => 'processing',
                default => 'pending'
            };

            $timeline[] = [
                'event' => 'settlement',
                'status' => $settlementStatus,
                'timestamp' => null,
                'description' => 'Cost settlement'
            ];
        }

        return $timeline;
    }

    /**
     * Get send log statistics
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

            $query = MessageSendLog::where('site_id', $siteId)
                ->whereBetween('created_at', [$startDate, $endDate]);

            // Overall statistics
            $totalMessages = $query->clone()->count();
            $sentMessages = $query->clone()->whereNotNull('sent_at')->count();
            $deliveredMessages = $query->clone()->where('status', 'delivered')->count();
            $failedMessages = $query->clone()->where('status', 'failed')->count();
            $totalCost = $query->clone()->sum('cost');

            // Success rates
            $sendSuccessRate = $totalMessages > 0 ? round(($sentMessages / $totalMessages) * 100, 2) : 0;
            $deliveryRate = $sentMessages > 0 ? round(($deliveredMessages / $sentMessages) * 100, 2) : 0;

            // Message type breakdown
            $messageTypeStats = $query->clone()
                ->groupBy('message_type')
                ->selectRaw('message_type, COUNT(*) as count, SUM(cost) as total_cost')
                ->get()
                ->keyBy('message_type');

            // Provider breakdown
            $providerStats = $query->clone()
                ->groupBy('provider')
                ->selectRaw('provider, COUNT(*) as count, SUM(cost) as total_cost')
                ->get()
                ->keyBy('provider');

            // Daily trends
            $dailyStats = $query->clone()
                ->selectRaw('DATE(created_at) as date, COUNT(*) as count, SUM(cost) as total_cost')
                ->groupBy('date')
                ->orderBy('date')
                ->get()
                ->keyBy('date');

            return response()->json([
                'status' => 'success',
                'data' => [
                    'period' => [
                        'type' => $period,
                        'start_date' => $startDate->format('Y-m-d'),
                        'end_date' => $endDate->format('Y-m-d')
                    ],
                    'overview' => [
                        'total_messages' => $totalMessages,
                        'sent_messages' => $sentMessages,
                        'delivered_messages' => $deliveredMessages,
                        'failed_messages' => $failedMessages,
                        'total_cost' => round($totalCost, 2),
                        'send_success_rate' => $sendSuccessRate,
                        'delivery_rate' => $deliveryRate,
                        'average_cost_per_message' => $totalMessages > 0 ? round($totalCost / $totalMessages, 4) : 0
                    ],
                    'message_type_breakdown' => $messageTypeStats->map(function ($stat) {
                        return [
                            'count' => $stat->count,
                            'total_cost' => round($stat->total_cost, 2),
                            'average_cost' => round($stat->total_cost / $stat->count, 4)
                        ];
                    }),
                    'provider_breakdown' => $providerStats->map(function ($stat) {
                        return [
                            'count' => $stat->count,
                            'total_cost' => round($stat->total_cost, 2),
                            'average_cost' => round($stat->total_cost / $stat->count, 4)
                        ];
                    }),
                    'daily_trends' => $dailyStats->map(function ($stat) {
                        return [
                            'count' => $stat->count,
                            'total_cost' => round($stat->total_cost, 2)
                        ];
                    })
                ]
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Failed to get send log statistics', [
                'site_id' => $siteId,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve statistics'
            ], 500);
        }
    }
}