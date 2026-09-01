<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Product\StoreProductRequest;
use App\Http\Requests\Product\UpdateProductRequest;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use App\Traits\ApiResponses;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    use ApiResponses;

    public function index(Request $request)
    {
        $query = Product::query()
            ->with('category')
            ->withCount('reviews')
            ->when($request->has('search'), function ($q) use ($request) {
                $q->where(function ($query) use ($request) {
                    $query->where('name', 'like', '%'.$request->search.'%')
                        ->orWhere('description', 'like', '%'.$request->search.'%');
                });
            })
            ->when($request->has('category_id'), fn ($q) => $q->where('category_id', $request->category_id))
            ->when($request->has('min_price'), fn ($q) => $q->where('price', '>=', $request->min_price))
            ->when($request->has('max_price'), fn ($q) => $q->where('price', '<=', $request->max_price))
            ->when($request->has('status'), fn ($q) => $q->where('status', $request->status))
            ->when($request->boolean('featured'), fn ($q) => $q->featured())
            ->when($request->boolean('active_only', true), fn ($q) => $q->active());

        $sortField = $request->input('sort_by', 'created_at');
        $sortDirection = $request->input('sort_direction', 'desc');

        $allowedSorts = ['name', 'price', 'stock', 'created_at', 'updated_at'];
        if (! in_array($sortField, $allowedSorts)) {
            $sortField = 'created_at';
        }
        if (! in_array($sortDirection, ['asc', 'desc'])) {
            $sortDirection = 'desc';
        }

        $products = $query->orderBy($sortField, $sortDirection)->paginate($request->integer('per_page', 15));

        return $this->paginatedResponse(ProductResource::collection($products), 'Products retrieved successfully');
    }

    public function store(StoreProductRequest $request)
    {
        $data = $request->validated();
        if (isset($data['images']) && is_string($data['images'])) {
            $data['images'] = json_decode($data['images'], true);
        }

        $product = Product::create($data);

        return $this->successResponse(new ProductResource($product->load('category')), 'Product created successfully', 201);
    }

    public function show(Product $product)
    {
        $product->load(['category', 'reviews.user']);

        return $this->successResponse(new ProductResource($product), 'Product retrieved successfully');
    }

    public function update(UpdateProductRequest $request, Product $product)
    {
        $product->update($request->validated());

        return $this->successResponse(new ProductResource($product->load('category')), 'Product updated successfully');
    }

    public function destroy(Product $product)
    {
        $product->delete();

        return $this->successResponse(null, 'Product deleted successfully');
    }
}
