<?php

namespace App\Jobs\Ecommerce;

use App\Models\Ecommerce\Order;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class SendAdminNewOrderNotificationJob implements ShouldQueue
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

        Log::info('Admin new order notification queued.', ['order_id' => $order->id]);
    }
}
