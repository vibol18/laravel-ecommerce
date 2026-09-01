<?php

namespace Tests\Feature;

use App\Models\CartItem;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderTest extends TestCase
{
    use RefreshDatabase;

    private function makeUser()
    {
        return User::factory()->create();
    }

    private function makeActiveProduct(int $stock = 10)
    {
        return Product::factory()->create(['status' => 'active', 'stock' => $stock]);
    }

    public function test_add_item_to_cart(): void
    {
        $user = $this->makeUser();
        $product = $this->makeActiveProduct();

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/cart/add', [
                'product_id' => $product->id,
                'quantity' => 2,
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.quantity', 2)
            ->assertJsonPath('data.product_id', $product->id);

        $this->assertDatabaseHas('cart_items', [
            'user_id' => $user->id,
            'product_id' => $product->id,
            'quantity' => 2,
        ]);
    }

    public function test_cart_accumulates_quantity(): void
    {
        $user = $this->makeUser();
        $product = $this->makeActiveProduct();

        $this->actingAs($user, 'sanctum')->postJson('/api/cart/add', ['product_id' => $product->id, 'quantity' => 1]);
        $this->actingAs($user, 'sanctum')->postJson('/api/cart/add', ['product_id' => $product->id, 'quantity' => 2]);

        $this->assertDatabaseHas('cart_items', [
            'user_id' => $user->id,
            'product_id' => $product->id,
            'quantity' => 3,
        ]);
    }

    public function test_cannot_add_more_than_stock(): void
    {
        $user = $this->makeUser();
        $product = $this->makeActiveProduct(3);

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/cart/add', ['product_id' => $product->id, 'quantity' => 10])
            ->assertStatus(422);
    }

    public function test_checkout_creates_order_from_cart(): void
    {
        $user = $this->makeUser();
        $product1 = $this->makeActiveProduct();
        $product2 = $this->makeActiveProduct();

        CartItem::create(['user_id' => $user->id, 'product_id' => $product1->id, 'quantity' => 2]);
        CartItem::create(['user_id' => $user->id, 'product_id' => $product2->id, 'quantity' => 1]);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/orders/checkout', [
                'shipping_address' => '123 Test Street, Manila',
                'payment_method' => 'cod',
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonStructure([
                'data' => [
                    'order_number', 'status', 'subtotal', 'tax', 'total',
                    'items', 'payment',
                ],
            ]);

        $this->assertDatabaseCount('orders', 1);
        $this->assertDatabaseCount('order_items', 2);
        $this->assertDatabaseCount('payments', 1);
        $this->assertDatabaseCount('cart_items', 0);

        // Stock decremented
        $this->assertDatabaseHas('products', ['id' => $product1->id, 'stock' => 8]);
    }

    public function test_checkout_with_empty_cart_fails(): void
    {
        $user = $this->makeUser();

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/orders/checkout', [
                'shipping_address' => '123 Test Street',
                'payment_method' => 'cod',
            ])
            ->assertStatus(422)
            ->assertJsonPath('success', false);
    }

    public function test_customer_views_only_own_orders(): void
    {
        $user = $this->makeUser();
        $other = $this->makeUser();

        Order::create([
            'user_id' => $user->id,
            'order_number' => 'ORD-TEST-ONE',
            'status' => 'pending',
            'subtotal' => 100,
            'tax' => 10,
            'shipping_cost' => 0,
            'total' => 110,
            'shipping_address' => 'Addr A',
        ]);
        Order::create([
            'user_id' => $other->id,
            'order_number' => 'ORD-TEST-TWO',
            'status' => 'pending',
            'subtotal' => 50,
            'tax' => 5,
            'shipping_cost' => 0,
            'total' => 55,
            'shipping_address' => 'Addr B',
        ]);

        $response = $this->actingAs($user, 'sanctum')->getJson('/api/orders');

        $response->assertStatus(200)
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.order_number', 'ORD-TEST-ONE');
    }

    public function test_customer_cannot_view_another_users_order(): void
    {
        $user = $this->makeUser();
        $other = $this->makeUser();

        $order = Order::create([
            'user_id' => $other->id,
            'order_number' => 'ORD-SECRET',
            'status' => 'pending',
            'subtotal' => 100,
            'tax' => 10,
            'shipping_cost' => 0,
            'total' => 110,
            'shipping_address' => 'Addr',
        ]);

        $this->actingAs($user, 'sanctum')
            ->getJson("/api/orders/{$order->id}")
            ->assertStatus(403);
    }

    public function test_only_admin_can_update_order_status(): void
    {
        $customer = $this->makeUser();
        $admin = User::factory()->admin()->create();

        $order = Order::create([
            'user_id' => $customer->id,
            'order_number' => 'ORD-STATUS',
            'status' => 'pending',
            'subtotal' => 100,
            'tax' => 10,
            'shipping_cost' => 0,
            'total' => 110,
            'shipping_address' => 'Addr',
        ]);

        $this->actingAs($customer, 'sanctum')
            ->patchJson("/api/orders/{$order->id}/status", ['status' => 'paid'])
            ->assertStatus(403);

        $this->actingAs($admin, 'sanctum')
            ->patchJson("/api/orders/{$order->id}/status", ['status' => 'paid'])
            ->assertStatus(200)
            ->assertJsonPath('data.status', 'paid');
    }
}
