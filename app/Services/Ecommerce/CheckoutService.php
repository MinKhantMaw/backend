<?php

namespace App\Services\Ecommerce;

use App\Models\Ecommerce\Cart;
use App\Models\Ecommerce\Coupon;
use App\Models\Ecommerce\Order;
use App\Models\Ecommerce\OrderItem;
use App\Models\Ecommerce\Payment;
use App\Models\Ecommerce\Product;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CheckoutService
{
    public function __construct(
        private readonly PaymentService $paymentService,
        private readonly OrderFulfillmentService $fulfillmentService,
    )
    {
    }

    public function checkout(User $user, Cart $cart, array $payload): array
    {
        $cart->load('items.product');

        if ($cart->items->isEmpty()) {
            throw ValidationException::withMessages(['cart' => ['Cart is empty.']]);
        }

        $items = $cart->items
            ->map(fn ($item) => [
                'product_id' => (int) $item->product_id,
                'quantity' => (int) $item->quantity,
            ])
            ->values()
            ->all();

        return $this->createOrder($user, $items, $payload);
    }

    public function checkoutFromPayload(User $user, array $payload): array
    {
        $items = collect($payload['items'] ?? [])
            ->map(function (array $item) {
                $productId = $item['product_id'] ?? $item['productId'] ?? null;

                return [
                    'product_id' => (int) $productId,
                    'quantity' => (int) ($item['quantity'] ?? 0),
                ];
            })
            ->filter(fn (array $item) => $item['product_id'] > 0 && $item['quantity'] > 0)
            ->values()
            ->all();

        if (empty($items)) {
            throw ValidationException::withMessages(['items' => ['At least one valid item is required.']]);
        }

        return $this->createOrder($user, $items, $payload);
    }

    private function createOrder(User $user, array $items, array $payload): array
    {
        $shippingAddress = $payload['shipping_address'] ?? $this->mapAddressPayload($payload['address'] ?? null);

        if (! is_array($shippingAddress)) {
            throw ValidationException::withMessages([
                'shipping_address' => ['Shipping address is required.'],
            ]);
        }

        $coupon = $this->resolveCoupon($payload['coupon_code'] ?? null);

        $order = DB::transaction(function () use ($user, $items, $shippingAddress, $payload, $coupon) {
            $subtotal = 0;
            $orderItems = [];

            foreach ($items as $item) {
                $product = Product::query()->lockForUpdate()->findOrFail($item['product_id']);

                if ($product->stock < $item['quantity']) {
                    throw ValidationException::withMessages([
                        'stock' => ["Insufficient stock for product {$product->id}."],
                    ]);
                }

                $lineTotal = bcmul((string) $product->price, (string) $item['quantity'], 2);
                $subtotal = bcadd((string) $subtotal, (string) $lineTotal, 2);

                $orderItems[] = [
                    'product_id' => $product->id,
                    'sku_snapshot' => $product->sku,
                    'name_snapshot' => $product->name,
                    'unit_price' => $product->price,
                    'quantity' => $item['quantity'],
                    'line_total' => $lineTotal,
                ];
            }

            $discount = $this->calculateDiscount((float) $subtotal, $coupon);
            $tax = 0;
            $shipping = 0;
            $grandTotal = bcsub((string) $subtotal, (string) $discount, 2);
            $grandTotal = bcadd((string) $grandTotal, (string) $tax, 2);
            $grandTotal = bcadd((string) $grandTotal, (string) $shipping, 2);

            $order = Order::query()->create([
                'order_number' => $this->generateOrderNumber(),
                'user_id' => $user->id,
                'status' => 'pending_payment',
                'subtotal' => $subtotal,
                'discount_total' => $discount,
                'tax_total' => $tax,
                'shipping_total' => $shipping,
                'grand_total' => $grandTotal,
                'coupon_id' => $coupon?->id,
                'currency' => 'USD',
                'shipping_address_json' => $shippingAddress,
                'billing_address_json' => $payload['billing_address'] ?? null,
            ]);

            foreach ($orderItems as $itemData) {
                $itemData['order_id'] = $order->id;
                OrderItem::query()->create($itemData);
            }

            $payment = Payment::query()->create([
                'order_id' => $order->id,
                'provider' => 'stripe',
                'status' => 'requires_payment_method',
                'amount' => $grandTotal,
                'currency' => 'USD',
                'idempotency_key' => (string) Str::uuid(),
                'provider_payload' => [
                    'payment_id' => $payload['paymentId'] ?? null,
                    'payment_method' => $payload['paymentMethod'] ?? null,
                    'provider_ref' => $payload['providerRef'] ?? null,
                ],
            ]);

            return $order->setRelation('payment', $payment)->fresh(['items', 'payment']);
        });

        $intent = $this->paymentService->createPaymentIntent($order);

        $order->payment()->update([
            'payment_intent_id' => $intent['payment_intent_id'],
        ]);

        if ($this->hasSuccessfulPaymentPayload($payload)) {
            $order->payment()->update([
                'status' => 'succeeded',
                'paid_at' => now(),
            ]);
            $order->update([
                'status' => 'paid',
                'placed_at' => now(),
            ]);
            $this->fulfillmentService->finalizePaidOrder($order->fresh(['items', 'user']));
        }

        return [
            'order' => $order->fresh('payment'),
            'payment_intent' => $intent,
        ];
    }

    private function mapAddressPayload(?array $address): ?array
    {
        if (! is_array($address)) {
            return null;
        }

        $line1 = $address['line1'] ?? null;
        $city = $address['city'] ?? null;
        $country = $address['country'] ?? null;
        $postalCode = $address['postal_code'] ?? $address['zip'] ?? null;

        if (! $line1 || ! $city || ! $country || ! $postalCode) {
            return null;
        }

        return [
            'line1' => $line1,
            'city' => $city,
            'country' => strtoupper((string) $country),
            'postal_code' => (string) $postalCode,
            'state' => $address['state'] ?? null,
            'recipient' => $address['recipient'] ?? null,
            'label' => $address['label'] ?? null,
        ];
    }

    private function hasSuccessfulPaymentPayload(array $payload): bool
    {
        return ! empty($payload['paymentId']) || ! empty($payload['providerRef']);
    }

    private function resolveCoupon(?string $couponCode): ?Coupon
    {
        if (! $couponCode) {
            return null;
        }

        $coupon = Coupon::query()->where('code', $couponCode)->first();

        if (! $coupon || ! $coupon->is_active) {
            throw ValidationException::withMessages(['coupon_code' => ['Invalid coupon code.']]);
        }

        if ($coupon->starts_at && now()->lt($coupon->starts_at)) {
            throw ValidationException::withMessages(['coupon_code' => ['Coupon is not active yet.']]);
        }

        if ($coupon->expires_at && now()->gt($coupon->expires_at)) {
            throw ValidationException::withMessages(['coupon_code' => ['Coupon has expired.']]);
        }

        if ($coupon->usage_limit !== null && $coupon->used_count >= $coupon->usage_limit) {
            throw ValidationException::withMessages(['coupon_code' => ['Coupon usage limit reached.']]);
        }

        return $coupon;
    }

    private function calculateDiscount(float $subtotal, ?Coupon $coupon): float
    {
        if (! $coupon) {
            return 0;
        }

        if ($coupon->min_order_amount !== null && $subtotal < (float) $coupon->min_order_amount) {
            return 0;
        }

        $discount = $coupon->type === 'percent'
            ? ($subtotal * ((float) $coupon->value / 100))
            : (float) $coupon->value;

        if ($coupon->max_discount_amount !== null) {
            $discount = min($discount, (float) $coupon->max_discount_amount);
        }

        return max(0, min($discount, $subtotal));
    }

    private function generateOrderNumber(): string
    {
        return 'ORD-'.now()->format('YmdHis').'-'.strtoupper(Str::random(6));
    }
}
