<?php

namespace App\Http\Controllers\Api\V1\Ecommerce;

use App\Contracts\Http\Controllers\Api\V1\Ecommerce\CheckoutControllerContract;
use App\Http\Controllers\Controller;
use App\Services\Ecommerce\CartService;
use App\Services\Ecommerce\CheckoutService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CheckoutController extends Controller implements CheckoutControllerContract
{
    public function __construct(
        private readonly CartService $cartService,
        private readonly CheckoutService $checkoutService,
    ) {}

    public function store(Request $request): JsonResponse
    {
        $payload = $request->validate([
            'coupon_code' => ['nullable', 'string', 'max:100'],
            'items' => ['sometimes', 'array', 'min:1'],
            'items.*.product_id' => ['nullable', 'integer', 'exists:products,id'],
            'items.*.productId' => ['nullable', 'integer', 'exists:products,id'],
            'items.*.quantity' => ['required_with:items', 'integer', 'min:1'],
            'shipping_address' => ['nullable', 'array'],
            'shipping_address.line1' => ['required_with:shipping_address', 'string', 'max:255'],
            'shipping_address.city' => ['required_with:shipping_address', 'string', 'max:100'],
            'shipping_address.country' => ['required_with:shipping_address', 'string', 'size:2'],
            'shipping_address.postal_code' => ['required_with:shipping_address', 'string', 'max:20'],
            'address' => ['nullable', 'array'],
            'address.line1' => ['required_with:address', 'string', 'max:255'],
            'address.city' => ['required_with:address', 'string', 'max:100'],
            'address.country' => ['required_with:address', 'string', 'size:2'],
            'address.zip' => ['required_with:address', 'string', 'max:20'],
            'billing_address' => ['nullable', 'array'],
            'paymentId' => ['nullable', 'string', 'max:120'],
            'paymentMethod' => ['nullable', 'string', 'max:50'],
            'providerRef' => ['nullable', 'string', 'max:120'],
        ]);

        if (! empty($payload['items'])) {
            $result = $this->checkoutService->checkoutFromPayload($request->user(), $payload);
        } else {
            $cart = $this->cartService->getForUser($request->user());
            $result = $this->checkoutService->checkout($request->user(), $cart, $payload);
        }

        return ApiResponse::success([
            'order' => $result['order'],
            'payment' => $result['payment_intent'],
        ], 'Checkout initialized successfully.');
    }
}
