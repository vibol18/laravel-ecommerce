<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\Review;
use App\Models\User;
use Illuminate\Database\Seeder;

class ReviewSeeder extends Seeder
{
    public function run(): void
    {
        $customers = User::where('role', 'customer')->get();
        $products = Product::take(20)->get();

        if ($customers->isEmpty() || $products->isEmpty()) {
            $this->command?->warn('No customers or products found. Skipping review seeding.');

            return;
        }

        for ($i = 0; $i < 40; $i++) {
            $customer = $customers->random();
            $product = $products->random();

            Review::firstOrCreate(
                [
                    'user_id' => $customer->id,
                    'product_id' => $product->id,
                ],
                [
                    'rating' => fake()->numberBetween(3, 5),
                    'title' => fake()->sentence(4),
                    'comment' => fake()->paragraph(),
                    'is_approved' => true,
                ]
            );
        }

        $this->command?->info('Sample reviews seeded.');
    }
}
