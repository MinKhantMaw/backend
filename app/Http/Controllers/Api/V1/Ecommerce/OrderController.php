<?php

namespace App\Http\Controllers\Api\V1\Ecommerce;

use App\Contracts\Http\Controllers\Api\V1\Ecommerce\OrderControllerContract;
use App\Http\Controllers\Controller;
use App\Models\Ecommerce\Order;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OrderController extends Controller implements OrderControllerContract
{
    public function index(Request $request): JsonResponse
    {
        $orders = Order::query()
            ->with(['items', 'payment'])
            ->where('user_id', $request->user()->id)
            ->latest()
            ->paginate(20);

        return ApiResponse::success($orders->items(), 'Orders fetched successfully.', 200, [
            'pagination' => ApiResponse::paginationMeta($orders),
        ]);
    }

    public function show(Request $request, int $order): JsonResponse
    {
        $model = Order::query()
            ->with(['items', 'payment'])
            ->where('user_id', $request->user()->id)
            ->findOrFail($order);

        return ApiResponse::success($model, 'Order fetched successfully.');
    }
}
