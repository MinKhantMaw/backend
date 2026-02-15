<?php

namespace App\Http\Requests\V1\Ecommerce\Checkout;

use Illuminate\Foundation\Http\FormRequest;

class CheckoutRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'coupon_code' => ['nullable', 'string', 'max:100'],
            'shipping_address' => ['required', 'array'],
            'shipping_address.line1' => ['required', 'string', 'max:255'],
            'shipping_address.city' => ['required', 'string', 'max:100'],
            'shipping_address.country' => ['required', 'string', 'size:2'],
            'shipping_address.postal_code' => ['required', 'string', 'max:20'],
            'billing_address' => ['nullable', 'array'],
        ];
    }
}
