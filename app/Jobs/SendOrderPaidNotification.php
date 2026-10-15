<?php

namespace App\Jobs;

use App\Models\Order;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Throwable;

class SendOrderPaidNotification implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 30;

    /**
     * Create a new job instance.
     */
    public function __construct(public Order $order) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Log::info('Order paid notification sent', ['order_id' => $this->order->id, 'user_id' => $this->order->user_id, 'amount' => $this->order->amount]);
    }

    public function backoff(): array
    {
        return [5, 30];
    }

    public function failed(Throwable $exception): void
    {
        Log::error('Error notification', ['order_id' => $this->order->id, 'error' => $exception->getMessage()]);
    }
}
