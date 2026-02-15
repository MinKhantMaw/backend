<?php

namespace App\Contracts\Http\Controllers\Api\V1\Ecommerce;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

interface CheckoutControllerContract
{
    public function store(Request $request): JsonResponse;
}
