<?php

namespace App\Contracts\Http\Controllers\Api\V1\Ecommerce;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

interface OrderControllerContract
{
    public function index(Request $request): JsonResponse;

    public function show(Request $request, int $order): JsonResponse;
}
