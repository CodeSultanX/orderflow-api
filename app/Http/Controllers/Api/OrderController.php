<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ListOrdersRequest;
use App\Http\Requests\StoreOrderRequest;
use App\Http\Requests\UpdateOrderRequest;
use App\Models\Order;
use App\Http\Resources\OrderResource;

class OrderController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(ListOrdersRequest $request)
    {
        $filters = $request->validated();
        $status = $filters['status'] ?? null;
        $user_id = $filters['user_id'] ?? null;
        $query = Order::query();
        if($status) $query->where('status','=',$status);
        if($user_id)$query->where('user_id','=',$user_id);
        $orders = $query->with('user:id,name')->latest('created_at');
        return OrderResource::collection($orders->paginate(15));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreOrderRequest $request)
    {
        $data = $request->validated();
        $order = Order::create($data)->refresh()->load('user:id,name');
        return OrderResource::make($order)->response();
    }

    /**
     * Display the specified resource.
     */
    public function show(Order $order)
    {
       return OrderResource::make($order->load('user:id,name'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateOrderRequest $request, Order $order)
    {
        $data = $request->validated();
        $order->update($data);
        return OrderResource::make($order->refresh()->load('user:id,name'));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Order $order)
    {
        $order->delete();
        return response()->noContent(204);
    }
}
