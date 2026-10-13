<?php

namespace App\Services;

use App\Models\Order;
use App\OrderStatus;
use Illuminate\Validation\ValidationException;

class OrderStatusService
{
    public function transition(Order $order, OrderStatus $status): Order
    {
        if ($order->status->canTransitionTo($status)) {
            $order->status = $status;
        } else {
            throw ValidationException::withMessages(['status' => 'Этот переход статуса запрещён.']);
        }
        $order->save();

        return $order->refresh();
    }
}
