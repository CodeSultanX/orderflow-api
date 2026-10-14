<?php

namespace App\Services;

use App\Models\Order;
use App\OrderStatus;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class OrderStatusService
{
    public function transition(Order $order, OrderStatus $status): Order
    {
        return DB::transaction(function () use ($order, $status) {
            $order = Order::query()->lockForUpdate()->findOrFail($order->id);
            $fromStatus = $order->status;

            if ($order->status->canTransitionTo($status)) {
                $order->status = $status;
            } else {
                throw ValidationException::withMessages(['status' => 'Этот переход статуса запрещён.']);
            }
            $order->save();
            $data = ['from_status' => $fromStatus, 'to_status' => $status];
            $order->statusHistories()->create($data);

            return $order->refresh();
        });
    }
}
