<?php

namespace App\Jobs\Ecommerce;

use App\Models\Ecommerce\Order;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class SendOrderConfirmationEmailJob implements ShouldQueue
{
    use Queueable;

    public function __construct(public readonly int $orderId)
    {
    }

    public function handle(): void
    {
        $order = Order::query()->find($this->orderId);

        if (! $order) {
            return;
        }

        // Replace with Mailable dispatch in production.
        Log::info('Order confirmation email queued.', ['order_id' => $order->id]);
    }
}
