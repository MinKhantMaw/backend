<?php

namespace App\Contracts\Http\Controllers\Api\V1\Ecommerce;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

interface CategoryControllerContract
{
    public function index(): JsonResponse;

    public function store(Request $request): JsonResponse;
}
