<?php

namespace Tests\Feature\Api;

use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class OrderApiTest extends TestCase
{
    use RefreshDatabase;

    /**
     * A basic feature test example.
     */
    public function test_orders_index_returns_paginated_orders(): void
    {
        $orders = Order::factory()->count(20);
        $user = User::factory()->has($orders)->create();
        Sanctum::actingAs($user);
        $data = $this->getJson('/api/orders');
        $data->assertOk();
        $data->assertJsonCount(15, 'data');
        $data->assertJsonPath('meta.total', 20);
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
        Sanctum::actingAs($user);

        $data = [
            'amount' => 12500.50,
        ];
        $response = $this->postJson('/api/orders', $data);
        $response->assertCreated();
        $response->assertJsonPath('data.status', 'new');
        $response->assertJsonPath('data.user.id', $user->id);
        $this->assertDatabaseHas('orders', [
            'user_id' => $user->id,
            'amount' => 12500.50,
            'status' => 'new',
        ]);
    }

    public function test_invalid_order_data_returns_validation_errors(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);
        $data = [
            'status' => 'broken',
            'amount' => -12500.50,
        ];
        $response = $this->postJson('/api/orders', $data);
        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(array_keys($data));
        $this->assertDatabaseCount('orders', 0);
    }

    public function test_order_can_be_updated(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);
        $order = Order::factory()->for($user)->create(['status' => 'new', 'amount' => 1000]);
        $updateData = ['amount' => 2000.50];
        $response = $this->patchJson("/api/orders/{$order->id}", $updateData);
        $response->assertOk();
        $response->assertJsonPath('data.status', 'new');
        $response->assertJsonPath('data.amount', '2000.50');
        $this->assertDatabaseHas('orders', [
            'user_id' => $user->id,
            'status' => 'new',
            'amount' => 2000.50,
        ]);
    }

    public function test_order_can_be_deleted(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);
        $order = Order::factory()->for($user)->create(['status' => 'new', 'amount' => 1000]);
        $response = $this->deleteJson("/api/orders/{$order->id}");
        $response->assertNoContent();
        $this->assertDatabaseMissing('orders', [
            'id' => $order->id,
        ]);

    }

    public function test_user_cannot_view_another_users_order(): void
    {
        $owner = User::factory()->create();
        $user2 = User::factory()->create();
        $order = Order::factory()->for($owner)->create(['status' => 'new', 'amount' => 1000]);
        Sanctum::actingAs($user2);
        $response = $this->getJson("/api/orders/{$order->id}");
        $response->assertForbidden();

    }

    public function test_user_cannot_update_or_delete_another_users_order(): void
    {
        $owner = User::factory()->create();
        $user2 = User::factory()->create();
        $order = Order::factory()->for($owner)->create(['status' => 'new', 'amount' => 1500]);
        Sanctum::actingAs($user2);
        $updateData = [
            'status' => 'paid', 'amount' => 2000.50,
        ];
        $response = $this->patchJson("/api/orders/{$order->id}", $updateData);
        $response->assertForbidden();
        $response = $this->deleteJson("/api/orders/{$order->id}");
        $response->assertForbidden();
        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => 'new',
        ]);
    }

    public function test_user_cannot_pay_or_cancel_another_users_order(): void
    {
        $owner = User::factory()->create();
        $user2 = User::factory()->create();
        $order = Order::factory()->for($owner)->create(['status' => 'new', 'amount' => 1500]);
        Sanctum::actingAs($user2);
        $responsePay = $this->patchJson("/api/orders/{$order->id}/pay");
        $responseCancel = $this->patchJson("/api/orders/{$order->id}/cancel");
        $responsePay->assertForbidden();
        $responseCancel->assertForbidden();
        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => 'new',
        ]);
    }

    public function test_new_order_can_be_paid(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);
        $order = Order::factory()->for($user)->create(['status' => 'new', 'amount' => 1500]);
        $response = $this->patchJson("/api/orders/{$order->id}/pay");
        $response->assertOk();
        $response->assertJsonPath('data.status', 'paid');
        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => 'paid',
        ]);
    }

    public function test_new_order_can_be_cancelled(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);
        $order = Order::factory()->for($user)->create(['status' => 'new', 'amount' => 1500]);
        $response = $this->patchJson("/api/orders/{$order->id}/cancel");
        $response->assertOk();
        $response->assertJsonPath('data.status', 'cancelled');
        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => 'cancelled',
        ]);
    }

    public function test_paid_order_cannot_be_cancelled(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);
        $order = Order::factory()->for($user)->create(['status' => 'paid', 'amount' => 1500]);
        $response = $this->patchJson("/api/orders/{$order->id}/cancel");
        $response->assertUnprocessable();
        $response->assertJsonValidationErrors('status');
        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => 'paid',
        ]);
    }
}
