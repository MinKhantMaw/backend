<?php

namespace App\Services\Ecommerce;

use App\Jobs\Ecommerce\SendAdminNewOrderNotificationJob;
use App\Jobs\Ecommerce\SendOrderConfirmationEmailJob;
use App\Models\Ecommerce\Order;
use App\Models\Ecommerce\Product;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class OrderFulfillmentService
{
    public function finalizePaidOrder(Order $order): void
    {
        DB::transaction(function () use ($order) {
            $order->loadMissing(['items', 'user']);

            foreach ($order->items as $item) {
                if (! $item->product_id) {
                    continue;
                }

                $product = Product::query()->lockForUpdate()->findOrFail($item->product_id);

                if ($product->stock < $item->quantity) {
                    throw new RuntimeException('Insufficient stock during fulfillment.');
                }

                $product->decrement('stock', $item->quantity);
            }

            $order->update([
                'status' => 'processing',
            ]);

            $order->user->carts()->each(function ($cart) {
                $cart->items()->delete();
            });
        });

        SendOrderConfirmationEmailJob::dispatch($order->id)->onQueue('high');
        SendAdminNewOrderNotificationJob::dispatch($order->id)->onQueue('default');
    }
}
