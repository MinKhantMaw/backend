<?php

namespace App\Http\Requests\V1\Ecommerce\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AdminProductUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $productId = (int) $this->route('product');

        return [
            'category_id' => ['sometimes', 'integer', 'exists:categories,id'],
            'sku' => ['sometimes', 'string', 'max:100', Rule::unique('products', 'sku')->ignore($productId)],
            'name' => ['sometimes', 'string', 'max:255'],
            'slug' => ['sometimes', 'string', 'max:255', Rule::unique('products', 'slug')->ignore($productId)],
            'description' => ['nullable', 'string'],
            'price' => ['sometimes', 'numeric', 'min:0'],
            'compare_at_price' => ['nullable', 'numeric', 'min:0'],
            'stock' => ['sometimes', 'integer', 'min:0'],
            'is_active' => ['sometimes', 'boolean'],
            'published_at' => ['nullable', 'date'],
        ];
    }
}
