<?php

namespace App\Services\Ecommerce;

use App\Models\Ecommerce\Order;
use App\Models\Ecommerce\OrderItem;
use Illuminate\Support\Facades\Cache;

class AnalyticsService
{
    public function overview(): array
    {
        return Cache::remember('admin.analytics.overview', 300, function () {
            $revenue = Order::query()
                ->whereIn('status', ['paid', 'processing', 'shipped', 'delivered'])
                ->sum('grand_total');

            $dailySales = Order::query()
                ->selectRaw('DATE(placed_at) as sales_date, SUM(grand_total) as total')
                ->whereNotNull('placed_at')
                ->groupBy('sales_date')
                ->orderByDesc('sales_date')
                ->limit(30)
                ->get();

            $topProducts = OrderItem::query()
                ->selectRaw('product_id, SUM(quantity) as qty, SUM(line_total) as amount')
                ->groupBy('product_id')
                ->orderByDesc('qty')
                ->limit(10)
                ->get();

            return [
                'total_revenue' => $revenue,
                'daily_sales' => $dailySales,
                'top_products' => $topProducts,
            ];
        });
    }
}
