<?php

namespace Database\Seeders;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class OrderSeeder extends Seeder
{
    public function run(): void
    {
        $customers = User::where('role', 'customer')->get();
        $products = Product::take(20)->get();

        if ($customers->isEmpty() || $products->isEmpty()) {
            $this->command?->warn('No customers or products found. Skipping order seeding.');

            return;
        }

        for ($i = 0; $i < 10; $i++) {
            $customer = $customers->random();
            $selectedProducts = $products->random(fake()->numberBetween(1, 4));
            $selectedProducts = $selectedProducts instanceof Product
                ? [$selectedProducts]
                : $selectedProducts->all();

            $subtotal = 0;
            $orderItems = [];
            foreach ($selectedProducts as $product) {
                $quantity = fake()->numberBetween(1, 3);
                $lineTotal = $product->price * $quantity;
                $subtotal += $lineTotal;
                $orderItems[] = [
                    'product' => $product,
                    'quantity' => $quantity,
                    'total' => $lineTotal,
                ];
            }

            $shippingCost = 50;
            $tax = round($subtotal * 0.10, 2);
            $total = round($subtotal + $tax + $shippingCost, 2);
            $status = fake()->randomElement(['pending', 'pending', 'paid', 'paid', 'shipped', 'completed', 'cancelled']);

            $order = Order::create([
                'order_number' => 'ORD-'.strtoupper(Str::random(10)),
                'user_id' => $customer->id,
                'status' => $status,
                'subtotal' => round($subtotal, 2),
                'tax' => $tax,
                'shipping_cost' => $shippingCost,
                'total' => $total,
                'shipping_address' => $customer->name.', 123 Example Street, Manila',
                'billing_address' => $customer->name.', 123 Example Street, Manila',
                'notes' => fake()->sentence(),
                'paid_at' => in_array($status, ['paid', 'shipped', 'completed']) ? now()->subDays(fake()->numberBetween(1, 20)) : null,
                'shipped_at' => in_array($status, ['shipped', 'completed']) ? now()->subDays(fake()->numberBetween(1, 10)) : null,
                'completed_at' => $status === 'completed' ? now()->subDays(fake()->numberBetween(1, 5)) : null,
            ]);

            foreach ($orderItems as $item) {
                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $item['product']->id,
                    'product_name' => $item['product']->name,
                    'price' => $item['product']->price,
                    'quantity' => $item['quantity'],
                    'total' => round($item['total'], 2),
                ]);
            }

            Payment::create([
                'order_id' => $order->id,
                'transaction_id' => 'TXN-'.strtoupper(Str::random(10)),
                'method' => fake()->randomElement(['cod', 'bank_transfer', 'credit_card', 'paypal']),
                'status' => in_array($status, ['paid', 'shipped', 'completed']) ? 'completed' : 'pending',
                'amount' => $total,
                'paid_at' => in_array($status, ['paid', 'shipped', 'completed']) ? now()->subDays(fake()->numberBetween(1, 20)) : null,
            ]);
        }

        $this->command?->info('Sample orders seeded.');
    }
}
