<?php

namespace App\Http\Controllers\Api;

use App\Events\OrderPaid;
use App\Http\Controllers\Controller;
use App\Http\Requests\ListOrdersRequest;
use App\Http\Requests\StoreOrderRequest;
use App\Http\Requests\UpdateOrderRequest;
use App\Http\Resources\OrderResource;
use App\Http\Resources\OrderStatusHistoryResource;
use App\Models\Order;
use App\OrderStatus;
use App\Services\OrderStatusService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Gate;

class OrderController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function __construct(private OrderStatusService $statusService) {}

    public function history(Order $order)
    {
        Gate::authorize('view', $order);
        $histories = $order->statusHistories()->oldest('created_at')->get();

        return OrderStatusHistoryResource::collection($histories);
    }

    public function pay(Order $order)
    {
        Gate::authorize('update', $order);
        $order = $this->statusService->transition($order, OrderStatus::Paid);
        $this->forgetSummaryCache($order->user_id);
        OrderPaid::dispatch($order);

        return OrderResource::make($order->load('user:id,name'));
    }

    private function forgetSummaryCache(int $user_id): void
    {
        Cache::forget("orders:summary:{$user_id}");
    }

    public function summary(Request $request)
    {
        $user_id = $request->user()->id;

        $data = Cache::remember("orders:summary:{$user_id}", 60, function () use ($user_id) {
            $total_orders = Order::query()->where('user_id', '=', $user_id)->count();
            $paid_orders = Order::query()->where('user_id', '=', $user_id)->where('status', '=', OrderStatus::Paid)->count();
            $paid_amount = Order::query()->where('user_id', '=', $user_id)->where('status', '=', OrderStatus::Paid)->sum('amount');

            return [
                'total_orders' => $total_orders,
                'paid_orders' => $paid_orders,
                'paid_amount' => $paid_amount,
            ];
        });

        return response()->json($data, 200);

    }

    public function cancel(Order $order)
    {
        Gate::authorize('update', $order);
        $order = $this->statusService->transition($order, OrderStatus::Cancelled);
        $this->forgetSummaryCache($order->user_id);

        return OrderResource::make($order->load('user:id,name'));
    }

    public function index(ListOrdersRequest $request)
    {
        $filters = $request->validated();
        $status = $filters['status'] ?? null;
        $user_id = $request->user()->id;
        $query = Order::query();
        if ($status) {
            $query->where('status', '=', $status);
        }
        if ($user_id) {
            $query->where('user_id', '=', $user_id);
        }
        $orders = $query->with('user:id,name')->latest('created_at');

        return OrderResource::collection($orders->paginate(15));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreOrderRequest $request)
    {
        $data = $request->validated();
        $data['user_id'] = $request->user()->id;
        $order = Order::create($data)->refresh()->load('user:id,name');
        $this->forgetSummaryCache($data['user_id']);

        return OrderResource::make($order)->response();
    }

    /**
     * Display the specified resource.
     */
    public function show(Order $order)
    {
        Gate::authorize('view', $order);

        return OrderResource::make($order->load('user:id,name'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateOrderRequest $request, Order $order)
    {
        Gate::authorize('update', $order);
        $data = $request->validated();
        $order->update($data);
        $this->forgetSummaryCache($order->user_id);

        return OrderResource::make($order->refresh()->load('user:id,name'));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Order $order)
    {
        Gate::authorize('delete', $order);
        $order->delete();
        $this->forgetSummaryCache($order->user_id);

        return response()->noContent(204);
    }
}
