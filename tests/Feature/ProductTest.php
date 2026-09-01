<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_can_list_products(): void
    {
        Product::factory()->count(15)->create(['status' => 'active']);

        $response = $this->getJson('/api/products');

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonStructure(['data', 'meta' => ['total']])
            ->assertJsonCount(15, 'data');
    }

    public function test_products_can_be_searched_and_filtered(): void
    {
        $category = Category::factory()->create();
        Product::factory()->create(['name' => 'Unique Widget X', 'price' => 50, 'category_id' => $category->id]);
        Product::factory()->create(['name' => 'Something Else', 'price' => 500]);

        $response = $this->getJson('/api/products?search=Unique+Widget&category_id='.$category->id.'&min_price=10&max_price=100');

        $response->assertStatus(200)
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.name', 'Unique Widget X');
    }

    public function test_admin_can_create_product(): void
    {
        $admin = User::factory()->admin()->create();
        $category = Category::factory()->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->postJson('/api/products', [
                'name' => 'New Product',
                'price' => 199.99,
                'stock' => 10,
                'category_id' => $category->id,
                'description' => 'A brand new product',
                'images' => ['https://example.com/one.jpg', 'https://example.com/two.jpg'],
                'status' => 'active',
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.name', 'New Product')
            ->assertJsonStructure(['data' => ['id', 'slug', 'images']]);

        $this->assertDatabaseHas('products', ['name' => 'New Product']);
    }

    public function test_customer_cannot_create_product(): void
    {
        $customer = User::factory()->create();
        $category = Category::factory()->create();

        $this->actingAs($customer, 'sanctum')
            ->postJson('/api/products', [
                'name' => 'Denied Product',
                'price' => 10,
                'stock' => 1,
                'category_id' => $category->id,
            ])
            ->assertStatus(403);
    }

    public function test_unauthenticated_cannot_create_product(): void
    {
        $category = Category::factory()->create();

        $this->postJson('/api/products', [
            'name' => 'No Token Product',
            'price' => 10,
            'category_id' => $category->id,
        ])->assertStatus(401);
    }

    public function test_creation_validates_required_fields(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin, 'sanctum')
            ->postJson('/api/products', [
                'name' => 'Missing Fields',
            ])
            ->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonStructure(['errors' => ['price', 'stock', 'category_id']]);
    }

    public function test_admin_can_update_and_delete_product(): void
    {
        $admin = User::factory()->admin()->create();
        $product = Product::factory()->create();

        $update = $this->actingAs($admin, 'sanctum')
            ->putJson("/api/products/{$product->id}", [
                'name' => 'Updated Product',
                'price' => 89.99,
            ]);

        $update->assertStatus(200)
            ->assertJsonPath('data.name', 'Updated Product')
            ->assertJsonPath('data.price', 89.99);

        $this->actingAs($admin, 'sanctum')
            ->deleteJson("/api/products/{$product->id}")
            ->assertStatus(200);

        $this->assertSoftDeleted('products', ['id' => $product->id]);
    }

    public function test_public_can_view_single_product(): void
    {
        $product = Product::factory()->create();

        $this->getJson("/api/products/{$product->id}")
            ->assertStatus(200)
            ->assertJsonPath('data.id', $product->id)
            ->assertJsonPath('data.name', $product->name);
    }

    public function test_missing_product_returns_404(): void
    {
        $this->getJson('/api/products/999999')
            ->assertStatus(404)
            ->assertJsonPath('success', false);
    }
}
