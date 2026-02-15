<?php

namespace App\Http\Controllers\Api\V1\Ecommerce\Admin;

use App\Contracts\Http\Controllers\Api\V1\Ecommerce\Admin\AdminProductControllerContract;
use App\Http\Controllers\Controller;
use App\Jobs\Ecommerce\GenerateProductImageVariantsJob;
use App\Models\Ecommerce\Product;
use App\Services\Ecommerce\AdminProductService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminProductController extends Controller implements AdminProductControllerContract
{
    public function __construct(private readonly AdminProductService $productService) {}

    public function index(Request $request): JsonResponse
    {
        $products = $this->productService->paginate($request->all());

        return ApiResponse::success($products->items(), 'Products fetched successfully.', 200, [
            'pagination' => ApiResponse::paginationMeta($products),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $payload = $request->validate([
            'category_id' => ['required', 'integer', 'exists:categories,id'],
            'sku' => ['required', 'string', 'max:100', 'unique:products,sku'],
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', 'unique:products,slug'],
            'description' => ['nullable', 'string'],
            'price' => ['required', 'numeric', 'min:0'],
            'compare_at_price' => ['nullable', 'numeric', 'min:0'],
            'stock' => ['required', 'integer', 'min:0'],
            'is_active' => ['sometimes', 'boolean'],
            'status' => ['sometimes', 'string', 'in:active,inactive'],
            'published_at' => ['nullable', 'date'],
        ]);

        if (array_key_exists('status', $payload)) {
            $payload['is_active'] = $payload['status'] === 'active';
            unset($payload['status']);
        } elseif ($request->exists('is_active')) {
            $payload['is_active'] = $request->boolean('is_active');
        }

        $product = $this->productService->create($payload);

        return ApiResponse::success($product, 'Product created successfully.', 201);
    }

    public function show(int $product): JsonResponse
    {
        $model = Product::query()->with(['category', 'images'])->findOrFail($product);

        return ApiResponse::success($model, 'Product fetched successfully.');
    }

    public function update(Request $request, int $product): JsonResponse
    {
        $payload = $request->validate([
            'category_id' => ['sometimes', 'integer', 'exists:categories,id'],
            'sku' => ['sometimes', 'string', 'max:100', \Illuminate\Validation\Rule::unique('products', 'sku')->ignore($product)],
            'name' => ['sometimes', 'string', 'max:255'],
            'slug' => ['sometimes', 'string', 'max:255', \Illuminate\Validation\Rule::unique('products', 'slug')->ignore($product)],
            'description' => ['nullable', 'string'],
            'price' => ['sometimes', 'numeric', 'min:0'],
            'compare_at_price' => ['nullable', 'numeric', 'min:0'],
            'stock' => ['sometimes', 'integer', 'min:0'],
            'is_active' => ['sometimes', 'boolean'],
            'status' => ['sometimes', 'string', 'in:active,inactive'],
            'published_at' => ['nullable', 'date'],
        ]);

        if (array_key_exists('status', $payload)) {
            $payload['is_active'] = $payload['status'] === 'active';
            unset($payload['status']);
        } elseif ($request->exists('is_active')) {
            $payload['is_active'] = $request->boolean('is_active');
        }

        $model = Product::query()->findOrFail($product);

        $model = $this->productService->update($model, $payload);

        return ApiResponse::success($model, 'Product updated successfully.');
    }

    public function destroy(int $product): JsonResponse
    {
        $model = Product::query()->findOrFail($product);
        $this->productService->delete($model);

        return ApiResponse::success(null, 'Product deleted successfully.');
    }

    public function uploadImage(Request $request, int $product): JsonResponse
    {
        $request->validate([
            'image' => ['required', 'image', 'max:5120'],
            'is_primary' => ['sometimes', 'boolean'],
        ]);

        $model = Product::query()->findOrFail($product);
        $image = $this->productService->uploadImage(
            $model,
            $request->file('image'),
            (bool) $request->boolean('is_primary')
        );

        GenerateProductImageVariantsJob::dispatch($image->id)->onQueue('low');

        return ApiResponse::success($image, 'Product image uploaded successfully.', 201);
    }
}
