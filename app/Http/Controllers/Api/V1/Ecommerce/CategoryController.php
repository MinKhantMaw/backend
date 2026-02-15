<?php

namespace App\Http\Controllers\Api\V1\Ecommerce;

use App\Contracts\Http\Controllers\Api\V1\Ecommerce\CategoryControllerContract;
use App\Http\Controllers\Controller;
use App\Models\Ecommerce\Category;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CategoryController extends Controller implements CategoryControllerContract
{
    public function index(): JsonResponse
    {
        $categories = Category::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return ApiResponse::success($categories, 'Categories fetched successfully.');
    }

    public function store(Request $request): JsonResponse
    {
        $payload = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', 'unique:categories,slug'],
            'parent_id' => ['nullable', 'integer', 'exists:categories,id'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $category = Category::query()->create([
            'name' => $payload['name'],
            'slug' => $payload['slug'],
            'parent_id' => $payload['parent_id'] ?? null,
            'is_active' => $payload['is_active'] ?? true,
        ]);

        return ApiResponse::success($category, 'Category created successfully.', 201);
    }
}
