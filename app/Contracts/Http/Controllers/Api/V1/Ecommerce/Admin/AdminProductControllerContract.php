<?php

namespace App\Contracts\Http\Controllers\Api\V1\Ecommerce\Admin;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

interface AdminProductControllerContract
{
    public function index(Request $request): JsonResponse;

    public function store(Request $request): JsonResponse;

    public function show(int $product): JsonResponse;

    public function update(Request $request, int $product): JsonResponse;

    public function destroy(int $product): JsonResponse;

    public function uploadImage(Request $request, int $product): JsonResponse;
}
