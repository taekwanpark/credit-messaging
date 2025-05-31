<?php

namespace Techigh\CreditMessaging\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Techigh\CreditMessaging\Settings\Entities\CreditMessage\CreditMessage;
use Techigh\CreditMessaging\Facades\MessageRouter;

class SendScheduledMessageJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $timeout = 300; // 5 minutes

    /**
     * Create a new job instance.
     */
    public function __construct(
        public CreditMessage $creditMessage
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            Log::info('Processing scheduled message', [
                'message_id' => $this->creditMessage->id,
                'site_id' => $this->creditMessage->site_id,
                'scheduled_at' => $this->creditMessage->scheduled_at,
                'recipients' => count($this->creditMessage->recipients ?? [])
            ]);

            // Check if message is still scheduled
            if (!$this->creditMessage->isScheduled()) {
                Log::warning('Message is not in scheduled status, skipping', [
                    'message_id' => $this->creditMessage->id,
                    'current_status' => $this->creditMessage->status
                ]);
                return;
            }

            // Send the message
            $result = MessageRouter::sendMessage($this->creditMessage);

            Log::info('Scheduled message sent successfully', [
                'message_id' => $this->creditMessage->id,
                'result' => $result
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to send scheduled message', [
                'message_id' => $this->creditMessage->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            // Mark message as failed
            $this->creditMessage->markAsFailed();

            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('Scheduled message job failed permanently', [
            'message_id' => $this->creditMessage->id,
            'error' => $exception->getMessage(),
            'attempts' => $this->attempts()
        ]);

        // Mark message as failed
        $this->creditMessage->markAsFailed();
    }
}
