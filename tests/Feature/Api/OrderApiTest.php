<?php

namespace Tests\Feature\Api;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\Order;
use App\Models\User;

class OrderApiTest extends TestCase
{

    use RefreshDatabase;
    /**
     * A basic feature test example.
     */

     public function test_orders_index_returns_paginated_orders(): void
     {
        $orders = Order::factory()->count(20);
        User::factory()->has($orders)->create();
        
        $data = $this->getJson('/api/orders');
        $data->assertOk();
        $data->assertJsonCount(15,'data');
        $data->assertJsonPath('meta.total',20);
        $data->assertJsonStructure([
            'data' => [
                '*' => [
                    'id',
                    'user_id',
                    'status',
                    'amount',
                    'user' => [
                        'id',
                        'name',
                    ],
                ],
            ],
        ]);
     }

     public function test_order_can_be_created(): void
     {
        $user = User::factory()->create();
        $data = [
            'user_id' => $user->id,
            'amount' => 12500.50,
        ];
        $response = $this->postJson('/api/orders',$data);
        $response->assertCreated();
        $response->assertJsonPath('data.status','new');
        $response->assertJsonPath('data.user.id',$user->id);
        $this->assertDatabaseHas('orders',[
            'user_id' => $user->id,
            'amount' => 12500.50,
            'status' => 'new'
        ]);
     }

     public function test_invalid_order_data_returns_validation_errors(): void
     {
        $data = [
            'user_id' => 999,
            'status' => 'broken',
            'amount' => -12500.50,
        ];
        $response = $this->postJson('/api/orders',$data);
        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(array_keys($data));
        $this->assertDatabaseCount('orders',0);
     }
     public function test_order_can_be_updated(): void
     {
        $user = User::factory()->create();
        $order = Order::factory()->for($user)->create(['status' => 'new', 'amount' => 1000]);
        $updateData = [
            'status' => 'paid', 'amount' => 2000.50
        ];
        $response = $this->patchJson("/api/orders/{$order->id}",$updateData);
        $response->assertOk();
        $response->assertJsonPath('data.status','paid');
        $response->assertJsonPath('data.amount','2000.50');
        $this->assertDatabaseHas('orders',[
            'user_id' => $user->id,
            'status' => 'paid', 
            'amount' => 2000.50
        ]);
     }
     public function test_order_can_be_deleted(): void
     {
        $user = User::factory()->create();
        $order = Order::factory()->for($user)->create(['status' => 'new', 'amount' => 1000]);
        $response = $this->deleteJson("/api/orders/{$order->id}");
        $response->assertNoContent();
        $this->assertDatabaseMissing('orders',[
            'id' => $order->id,
        ]);
      
     }
   
    

   
}
