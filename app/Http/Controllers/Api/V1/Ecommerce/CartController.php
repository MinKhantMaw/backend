<?php

namespace App\Http\Controllers\Api\V1\Ecommerce;

use App\Contracts\Http\Controllers\Api\V1\Ecommerce\CartControllerContract;
use App\Http\Controllers\Controller;
use App\Services\Ecommerce\CartService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CartController extends Controller implements CartControllerContract
{
    public function __construct(private readonly CartService $cartService)
    {
    }

    public function show(Request $request): JsonResponse
    {
        $cart = $this->resolveCart($request);

        return ApiResponse::success($cart->load('items.product'), 'Cart fetched successfully.');
    }

    public function store(Request $request): JsonResponse
    {
        $payload = $request->validate([
            'product_id' => ['required', 'integer', 'exists:products,id'],
            'quantity' => ['required', 'integer', 'min:1'],
        ]);
        $cart = $this->resolveCart($request);
        $cart = $this->cartService->addOrUpdateItem($cart, $payload['product_id'], $payload['quantity']);

        return ApiResponse::success($cart, 'Cart item upserted successfully.');
    }

    public function update(Request $request, int $item): JsonResponse
    {
        $payload = $request->validate([
            'quantity' => ['required', 'integer', 'min:1'],
        ]);
        $cart = $this->resolveCart($request);
        $cartItem = $cart->items()->findOrFail($item);

        $cart = $this->cartService->addOrUpdateItem($cart, $cartItem->product_id, $payload['quantity']);

        return ApiResponse::success($cart, 'Cart item updated successfully.');
    }

    public function destroy(Request $request, int $item): JsonResponse
    {
        $cart = $this->resolveCart($request);
        $cart = $this->cartService->removeItem($cart, $item);

        return ApiResponse::success($cart, 'Cart item removed successfully.');
    }

    public function mergeGuest(Request $request): JsonResponse
    {
        $guestToken = (string) $request->cookie('guest_cart_token');

        if ($guestToken === '') {
            return ApiResponse::success($this->cartService->getForUser($request->user()), 'No guest cart to merge.');
        }

        $cart = $this->cartService->mergeGuestIntoUser($guestToken, $request->user());

        return ApiResponse::success($cart, 'Guest cart merged successfully.')->withoutCookie('guest_cart_token');
    }

    private function resolveCart(Request $request)
    {
        if ($request->user()) {
            return $this->cartService->getForUser($request->user());
        }

        $guestToken = $request->cookie('guest_cart_token', (string) Str::uuid());

        $cart = $this->cartService->getForGuest($guestToken);

        cookie()->queue(cookie('guest_cart_token', $guestToken, 60 * 24 * 30, null, null, true, true, false, 'Strict'));

        return $cart;
    }
}
