<?php

namespace App\Contracts\Http\Controllers\Api\V1\Ecommerce;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

interface CartControllerContract
{
    public function show(Request $request): JsonResponse;

    public function store(Request $request): JsonResponse;

    public function update(Request $request, int $item): JsonResponse;

    public function destroy(Request $request, int $item): JsonResponse;

    public function mergeGuest(Request $request): JsonResponse;
}
