<?php

namespace App\Http\Requests\Product;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', 'unique:products,slug'],
            'description' => ['nullable', 'string'],
            'price' => ['required', 'numeric', 'min:0'],
            'compare_price' => ['nullable', 'numeric', 'min:0'],
            'stock' => ['required', 'integer', 'min:0'],
            'category_id' => ['required', 'integer', 'exists:categories,id'],
            'images' => ['nullable', 'array'],
            'images.*' => ['nullable', 'url', 'max:2048'],
            'status' => ['nullable', Rule::in(['active', 'inactive', 'out_of_stock'])],
            'is_featured' => ['nullable', 'boolean'],
            'weight' => ['nullable', 'numeric', 'min:0'],
        ];
    }
}
