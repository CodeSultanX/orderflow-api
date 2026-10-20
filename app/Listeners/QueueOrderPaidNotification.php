<?php

namespace App\Listeners;

use App\Events\OrderPaid;
use App\Jobs\SendOrderPaidNotification;

class QueueOrderPaidNotification
{
    /**
     * Handle the event.
     */
    public function handle(OrderPaid $event): void
    {
        SendOrderPaidNotification::dispatch($event->order);
    }
}
