<?php

namespace App\Http\Controllers\Api\V1\Ecommerce\Admin;

use App\Contracts\Http\Controllers\Api\V1\Ecommerce\Admin\AdminOrderControllerContract;
use App\Http\Controllers\Controller;
use App\Models\Ecommerce\Order;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AdminOrderController extends Controller implements AdminOrderControllerContract
{
    public function index(Request $request): JsonResponse
    {
        $filters = $request->validate([
            'status' => ['nullable', 'string'],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $orders = Order::query()->with(['user', 'payment'])
            ->when($filters['status'] ?? null, fn ($q, $status) => $q->where('status', $status))
            ->when($filters['from'] ?? null, fn ($q, $from) => $q->whereDate('created_at', '>=', $from))
            ->when($filters['to'] ?? null, fn ($q, $to) => $q->whereDate('created_at', '<=', $to))
            ->latest()
            ->paginate((int) ($filters['per_page'] ?? 20));

        return ApiResponse::success($orders->items(), 'Orders fetched successfully.', 200, [
            'pagination' => ApiResponse::paginationMeta($orders),
        ]);
    }

    public function show(int $order): JsonResponse
    {
        $model = Order::query()->with(['items', 'payment', 'user'])->findOrFail($order);

        return ApiResponse::success($model, 'Order fetched successfully.');
    }

    public function updateStatus(Request $request, int $order): JsonResponse
    {
        $payload = $request->validate([
            'status' => [
                'required',
                Rule::in(['paid', 'processing', 'shipped', 'delivered', 'cancelled', 'refunded', 'payment_failed']),
            ],
        ]);

        $model = Order::query()->findOrFail($order);
        $model->update(['status' => $payload['status']]);

        return ApiResponse::success($model->fresh(), 'Order status updated successfully.');
    }
}
