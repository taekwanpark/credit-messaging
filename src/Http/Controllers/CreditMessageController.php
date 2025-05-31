<?php

namespace Techigh\CreditMessaging\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Techigh\CreditMessaging\Models\CreditMessage;
use Techigh\CreditMessaging\Models\SiteCredit;
use Techigh\CreditMessaging\Facades\MessageRouter;
use Techigh\CreditMessaging\Facades\CreditManager;
use Techigh\CreditMessaging\Jobs\SendScheduledMessageJob;

class CreditMessageController extends Controller
{
    /**
     * Get credit messages with filtering and pagination
     */
    public function index(Request $request, string $siteId): JsonResponse
    {
        try {
            $request->validate([
                'per_page' => 'integer|min:1|max:100',
                'page' => 'integer|min:1',
                'search' => 'string|max:255',
                'status' => 'string|in:draft,scheduled,sending,completed,failed,cancelled',
                'message_type' => 'string|in:alimtalk,sms,lms,mms',
                'sort_by' => 'string|in:id,status,message_type,scheduled_at,sent_at,created_at,updated_at',
                'sort_order' => 'string|in:asc,desc'
            ]);

            $query = CreditMessage::where('site_id', $siteId);

            // Apply filters
            if ($request->filled('search')) {
                $search = $request->input('search');
                $query->where(function ($q) use ($search) {
                    $q->where('title', 'like', "%{$search}%")
                      ->orWhere('message_content', 'like', "%{$search}%");
                });
            }

            if ($request->filled('status')) {
                $query->where('status', $request->input('status'));
            }

            if ($request->filled('message_type')) {
                $query->where('message_type', $request->input('message_type'));
            }

            // Apply sorting
            $sortBy = $request->input('sort_by', 'created_at');
            $sortOrder = $request->input('sort_order', 'desc');
            $query->orderBy($sortBy, $sortOrder);

            // Apply pagination
            $perPage = $request->input('per_page', 15);
            $messages = $query->with('siteCredit')->paginate($perPage);

            return response()->json([
                'status' => 'success',
                'data' => $messages
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Failed to get credit messages', [
                'site_id' => $siteId,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve messages'
            ], 500);
        }
    }

    /**
     * Create a new credit message
     */
    public function store(Request $request, string $siteId): JsonResponse
    {
        try {
            $request->validate([
                'title' => 'required|string|max:255',
                'message_content' => 'required|string',
                'message_type' => 'required|string|in:alimtalk,sms,lms,mms',
                'routing_strategy' => 'required|string|in:alimtalk_first,sms_only,cost_optimized',
                'recipients' => 'required|array|min:1',
                'recipients.*' => 'required|string',
                'scheduled_at' => 'nullable|date|after:now',
                'template_code' => 'nullable|string|max:255',
                'variables' => 'nullable|array'
            ]);

            // Check if site has credit configuration
            $siteCredit = SiteCredit::where('site_id', $siteId)->first();
            if (!$siteCredit) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Site credit configuration not found'
                ], 404);
            }

            DB::beginTransaction();

            // Calculate estimated cost
            $recipients = $request->input('recipients');
            $messageType = $request->input('message_type');
            $costPerMessage = $siteCredit->getCostForMessageType($messageType);
            $estimatedCost = count($recipients) * $costPerMessage;

            $message = CreditMessage::create([
                'site_id' => $siteId,
                'title' => $request->input('title'),
                'message_content' => $request->input('message_content'),
                'message_type' => $messageType,
                'routing_strategy' => $request->input('routing_strategy'),
                'recipients' => $recipients,
                'estimated_cost' => $estimatedCost,
                'total_recipients' => count($recipients),
                'scheduled_at' => $request->input('scheduled_at'),
                'template_code' => $request->input('template_code'),
                'variables' => $request->input('variables', []),
                'status' => $request->filled('scheduled_at') ? 'scheduled' : 'draft'
            ]);

            // If scheduled, queue the job
            if ($message->isScheduled()) {
                SendScheduledMessageJob::dispatch($message)
                    ->delay($message->scheduled_at);
            }

            DB::commit();

            Log::info('Credit message created', [
                'site_id' => $siteId,
                'message_id' => $message->id,
                'status' => $message->status
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Message created successfully',
                'data' => $message->load('siteCredit')
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
            Log::error('Failed to create credit message', [
                'site_id' => $siteId,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to create message'
            ], 500);
        }
    }

    /**
     * Get a specific credit message
     */
    public function show(Request $request, string $siteId, CreditMessage $message): JsonResponse
    {
        try {
            // Ensure message belongs to the site
            if ($message->site_id !== $siteId) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Message not found'
                ], 404);
            }

            return response()->json([
                'status' => 'success',
                'data' => $message->load('siteCredit')
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to get credit message', [
                'site_id' => $siteId,
                'message_id' => $message->id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve message'
            ], 500);
        }
    }

    /**
     * Update a credit message
     */
    public function update(Request $request, string $siteId, CreditMessage $message): JsonResponse
    {
        try {
            // Ensure message belongs to the site
            if ($message->site_id !== $siteId) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Message not found'
                ], 404);
            }

            // Only allow updates to draft messages
            if (!$message->isDraft()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Only draft messages can be updated'
                ], 400);
            }

            $request->validate([
                'title' => 'string|max:255',
                'message_content' => 'string',
                'message_type' => 'string|in:alimtalk,sms,lms,mms',
                'routing_strategy' => 'string|in:alimtalk_first,sms_only,cost_optimized',
                'recipients' => 'array|min:1',
                'recipients.*' => 'string',
                'template_code' => 'nullable|string|max:255',
                'variables' => 'nullable|array'
            ]);

            DB::beginTransaction();

            $updateData = $request->only([
                'title', 'message_content', 'message_type', 'routing_strategy',
                'recipients', 'template_code', 'variables'
            ]);

            // Recalculate estimated cost if recipients or message type changed
            if ($request->filled('recipients') || $request->filled('message_type')) {
                $siteCredit = $message->siteCredit;
                $recipients = $request->input('recipients', $message->recipients);
                $messageType = $request->input('message_type', $message->message_type);
                $costPerMessage = $siteCredit->getCostForMessageType($messageType);
                
                $updateData['estimated_cost'] = count($recipients) * $costPerMessage;
                $updateData['total_recipients'] = count($recipients);
            }

            $message->update($updateData);

            DB::commit();

            Log::info('Credit message updated', [
                'site_id' => $siteId,
                'message_id' => $message->id
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Message updated successfully',
                'data' => $message->load('siteCredit')
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
            Log::error('Failed to update credit message', [
                'site_id' => $siteId,
                'message_id' => $message->id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to update message'
            ], 500);
        }
    }

    /**
     * Delete a credit message
     */
    public function destroy(Request $request, string $siteId, CreditMessage $message): JsonResponse
    {
        try {
            // Ensure message belongs to the site
            if ($message->site_id !== $siteId) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Message not found'
                ], 404);
            }

            // Only allow deletion of draft or failed messages
            if (!in_array($message->status, ['draft', 'failed', 'cancelled'])) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Only draft, failed, or cancelled messages can be deleted'
                ], 400);
            }

            $message->delete();

            Log::info('Credit message deleted', [
                'site_id' => $siteId,
                'message_id' => $message->id
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Message deleted successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to delete credit message', [
                'site_id' => $siteId,
                'message_id' => $message->id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to delete message'
            ], 500);
        }
    }

    /**
     * Send a message immediately
     */
    public function send(Request $request, string $siteId, CreditMessage $message): JsonResponse
    {
        try {
            // Ensure message belongs to the site
            if ($message->site_id !== $siteId) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Message not found'
                ], 404);
            }

            // Only allow sending draft messages
            if (!$message->isDraft()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Only draft messages can be sent'
                ], 400);
            }

            // Check if site has enough credit
            $siteCredit = $message->siteCredit;
            if (!$siteCredit->hasEnoughBalance($message->estimated_cost)) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Insufficient credits to send message'
                ], 400);
            }

            DB::beginTransaction();

            // Mark as sending
            $message->markAsSending();

            // Process the message through MessageRouter
            $result = MessageRouter::sendMessage($message);

            if ($result['success']) {
                $message->markAsCompleted(
                    $result['success_count'],
                    $result['failed_count'],
                    $result['actual_cost']
                );
            } else {
                $message->markAsFailed();
            }

            DB::commit();

            Log::info('Credit message sent', [
                'site_id' => $siteId,
                'message_id' => $message->id,
                'result' => $result
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Message sent successfully',
                'data' => [
                    'message' => $message->load('siteCredit'),
                    'send_result' => $result
                ]
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            $message->markAsFailed();
            
            Log::error('Failed to send credit message', [
                'site_id' => $siteId,
                'message_id' => $message->id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to send message'
            ], 500);
        }
    }

    /**
     * Schedule a message for later sending
     */
    public function schedule(Request $request, string $siteId, CreditMessage $message): JsonResponse
    {
        try {
            // Ensure message belongs to the site
            if ($message->site_id !== $siteId) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Message not found'
                ], 404);
            }

            // Only allow scheduling draft messages
            if (!$message->isDraft()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Only draft messages can be scheduled'
                ], 400);
            }

            $request->validate([
                'scheduled_at' => 'required|date|after:now'
            ]);

            DB::beginTransaction();

            $message->update([
                'scheduled_at' => $request->input('scheduled_at'),
                'status' => 'scheduled'
            ]);

            // Queue the job
            SendScheduledMessageJob::dispatch($message)
                ->delay($message->scheduled_at);

            DB::commit();

            Log::info('Credit message scheduled', [
                'site_id' => $siteId,
                'message_id' => $message->id,
                'scheduled_at' => $message->scheduled_at
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Message scheduled successfully',
                'data' => $message->load('siteCredit')
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
            Log::error('Failed to schedule credit message', [
                'site_id' => $siteId,
                'message_id' => $message->id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to schedule message'
            ], 500);
        }
    }

    /**
     * Cancel a scheduled message
     */
    public function cancel(Request $request, string $siteId, CreditMessage $message): JsonResponse
    {
        try {
            // Ensure message belongs to the site
            if ($message->site_id !== $siteId) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Message not found'
                ], 404);
            }

            // Only allow canceling scheduled messages
            if (!$message->isScheduled()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Only scheduled messages can be cancelled'
                ], 400);
            }

            DB::beginTransaction();

            $message->update([
                'status' => 'cancelled',
                'scheduled_at' => null
            ]);

            DB::commit();

            Log::info('Credit message cancelled', [
                'site_id' => $siteId,
                'message_id' => $message->id
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Message cancelled successfully',
                'data' => $message->load('siteCredit')
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to cancel credit message', [
                'site_id' => $siteId,
                'message_id' => $message->id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to cancel message'
            ], 500);
        }
    }

    /**
     * Get message status and delivery information
     */
    public function getStatus(Request $request, string $siteId, CreditMessage $message): JsonResponse
    {
        try {
            // Ensure message belongs to the site
            if ($message->site_id !== $siteId) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Message not found'
                ], 404);
            }

            return response()->json([
                'status' => 'success',
                'data' => [
                    'message_id' => $message->id,
                    'status' => $message->status,
                    'total_recipients' => $message->total_recipients,
                    'success_count' => $message->success_count,
                    'failed_count' => $message->failed_count,
                    'success_rate' => $message->success_rate,
                    'estimated_cost' => $message->estimated_cost,
                    'actual_cost' => $message->actual_cost,
                    'scheduled_at' => $message->scheduled_at,
                    'sent_at' => $message->sent_at,
                    'created_at' => $message->created_at,
                    'updated_at' => $message->updated_at
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to get credit message status', [
                'site_id' => $siteId,
                'message_id' => $message->id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve message status'
            ], 500);
        }
    }
}