<?php

namespace App\Http\Requests\V1\Ecommerce;

use Illuminate\Foundation\Http\FormRequest;

class ProductIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'category_id' => ['nullable', 'integer', 'exists:categories,id'],
            'q' => ['nullable', 'string', 'max:255'],
            'min_price' => ['nullable', 'numeric', 'min:0'],
            'max_price' => ['nullable', 'numeric', 'min:0'],
            'sort' => ['nullable', 'string', 'in:created_at,-created_at,price,-price,name,-name'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }
}
