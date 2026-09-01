<?php

namespace App\Http\Requests\Product;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'string', 'max:255'],
            'slug' => ['sometimes', 'string', 'max:255', Rule::unique('products', 'slug')->ignore($this->product)],
            'description' => ['sometimes', 'nullable', 'string'],
            'price' => ['sometimes', 'numeric', 'min:0'],
            'compare_price' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'stock' => ['sometimes', 'integer', 'min:0'],
            'category_id' => ['sometimes', 'integer', 'exists:categories,id'],
            'images' => ['sometimes', 'nullable', 'array'],
            'images.*' => ['nullable', 'url', 'max:2048'],
            'status' => ['sometimes', Rule::in(['active', 'inactive', 'out_of_stock'])],
            'is_featured' => ['sometimes', 'boolean'],
            'weight' => ['sometimes', 'nullable', 'numeric', 'min:0'],
        ];
    }
}
