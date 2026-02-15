<?php

namespace App\Jobs\Ecommerce;

use App\Models\Ecommerce\Order;
use App\Services\Ecommerce\OrderFulfillmentService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class HandleOrderPaidJob implements ShouldQueue
{
    use Queueable;

    public function __construct(public readonly int $orderId)
    {
    }

    public function handle(OrderFulfillmentService $fulfillmentService): void
    {
        $order = Order::query()->find($this->orderId);

        if (! $order) {
            return;
        }

        $fulfillmentService->finalizePaidOrder($order);
    }
}
