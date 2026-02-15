<?php

namespace App\Services\Ecommerce;

use App\Models\Ecommerce\Cart;
use App\Models\Ecommerce\CartItem;
use App\Models\Ecommerce\Product;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CartService
{
    public function getForUser(User $user): Cart
    {
        return Cart::query()->firstOrCreate(
            ['user_id' => $user->id],
            ['currency' => 'USD']
        );
    }

    public function getForGuest(string $guestToken): Cart
    {
        return Cart::query()->firstOrCreate(
            ['guest_token' => $guestToken],
            ['currency' => 'USD']
        );
    }

    public function addOrUpdateItem(Cart $cart, int $productId, int $quantity): Cart
    {
        return DB::transaction(function () use ($cart, $productId, $quantity) {
            $product = Product::query()->lockForUpdate()->findOrFail($productId);

            if ($product->stock < $quantity) {
                throw ValidationException::withMessages([
                    'quantity' => ['Requested quantity exceeds available stock.'],
                ]);
            }

            CartItem::query()->updateOrCreate(
                ['cart_id' => $cart->id, 'product_id' => $productId],
                ['quantity' => $quantity]
            );

            return $cart->fresh(['items.product']);
        });
    }

    public function removeItem(Cart $cart, int $itemId): Cart
    {
        $cart->items()->whereKey($itemId)->delete();

        return $cart->fresh(['items.product']);
    }

    public function mergeGuestIntoUser(string $guestToken, User $user): Cart
    {
        return DB::transaction(function () use ($guestToken, $user) {
            $guestCart = Cart::query()->where('guest_token', $guestToken)->with('items')->first();
            $userCart = $this->getForUser($user);

            if (! $guestCart) {
                return $userCart->fresh(['items.product']);
            }

            foreach ($guestCart->items as $item) {
                $existing = $userCart->items()->where('product_id', $item->product_id)->first();
                $nextQty = ($existing?->quantity ?? 0) + $item->quantity;
                $this->addOrUpdateItem($userCart, $item->product_id, $nextQty);
            }

            $guestCart->delete();

            return $userCart->fresh(['items.product']);
        });
    }
}
