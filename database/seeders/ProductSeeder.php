<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        $parentCategories = [
            'Electronics' => ['Phones', 'Laptops', 'Accessories'],
            'Fashion' => ['Men', 'Women', 'Kids'],
            'Home & Living' => ['Furniture', 'Kitchen', 'Decor'],
            'Sports & Outdoors' => ['Fitness', 'Camping', 'Cycling'],
            'Books' => ['Fiction', 'Technology', 'Business'],
        ];

        foreach ($parentCategories as $parentName => $children) {
            $parent = Category::create([
                'name' => $parentName,
                'slug' => Str::slug($parentName),
                'description' => "Products related to {$parentName}",
                'is_active' => true,
                'sort_order' => 0,
            ]);

            foreach ($children as $childName) {
                Category::create([
                    'name' => $childName,
                    'slug' => Str::slug("{$parentName} {$childName}"),
                    'description' => "Products related to {$childName}",
                    'parent_id' => $parent->id,
                    'is_active' => true,
                    'sort_order' => 0,
                ]);
            }
        }

        $categories = Category::whereNotNull('parent_id')->get();

        Product::factory(60)->create([
            'category_id' => $categories->random()->id,
        ]);

        $this->command?->info('Products and categories seeded.');
    }
}
