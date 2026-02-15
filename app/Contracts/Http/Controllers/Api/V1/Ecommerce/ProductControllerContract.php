<?php

namespace App\Contracts\Http\Controllers\Api\V1\Ecommerce;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

interface ProductControllerContract
{
    public function index(Request $request): JsonResponse;

    public function show(string $slug): JsonResponse;
}
