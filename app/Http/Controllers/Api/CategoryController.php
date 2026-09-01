<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Category\StoreCategoryRequest;
use App\Http\Requests\Category\UpdateCategoryRequest;
use App\Http\Resources\CategoryResource;
use App\Models\Category;
use App\Traits\ApiResponses;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    use ApiResponses;

    public function index(Request $request)
    {
        $query = Category::query()
            ->withCount('products')
            ->with('children')
            ->when($request->has('parent_id'), fn ($q) => $q->where('parent_id', $request->parent_id))
            ->when($request->boolean('active_only'), fn ($q) => $q->active())
            ->when($request->has('search'), fn ($q) => $q->where('name', 'like', '%'.$request->search.'%'));

        $categories = $query->orderBy('sort_order')->paginate($request->integer('per_page', 15));

        return $this->paginatedResponse(CategoryResource::collection($categories), 'Categories retrieved successfully');
    }

    public function store(StoreCategoryRequest $request)
    {
        $category = Category::create($request->validated());

        return $this->successResponse(new CategoryResource($category), 'Category created successfully', 201);
    }

    public function show(Category $category)
    {
        $category->load('parent', 'children', 'products');

        return $this->successResponse(new CategoryResource($category), 'Category retrieved successfully');
    }

    public function update(UpdateCategoryRequest $request, Category $category)
    {
        $category->update($request->validated());

        return $this->successResponse(new CategoryResource($category), 'Category updated successfully');
    }

    public function destroy(Category $category)
    {
        if ($category->products()->count() > 0) {
            return $this->errorResponse('Cannot delete category that has products', 409);
        }

        $category->delete();

        return $this->successResponse(null, 'Category deleted successfully');
    }
}
