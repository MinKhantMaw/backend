<?php

namespace App\Http\Controllers\Api\V1\Ecommerce;

use App\Contracts\Http\Controllers\Api\V1\Ecommerce\ProductControllerContract;
use App\Http\Controllers\Controller;
use App\Services\Ecommerce\ProductCatalogService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProductController extends Controller implements ProductControllerContract
{
    public function __construct(private readonly ProductCatalogService $catalogService)
    {
    }

    public function index(Request $request): JsonResponse
    {
        $filters = $request->validate([
            'category_id' => ['nullable', 'integer', 'exists:categories,id'],
            'q' => ['nullable', 'string', 'max:255'],
            'min_price' => ['nullable', 'numeric', 'min:0'],
            'max_price' => ['nullable', 'numeric', 'min:0'],
            'sort' => ['nullable', 'string', 'in:created_at,-created_at,price,-price,name,-name'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);
        $products = $this->catalogService->paginate($filters);

        return ApiResponse::success($products->items(), 'Products fetched successfully.', 200, [
            'pagination' => ApiResponse::paginationMeta($products),
        ]);
    }

    public function show(string $slug): JsonResponse
    {
        $product = $this->catalogService->findBySlug($slug);

        return ApiResponse::success($product, 'Product fetched successfully.');
    }
}
