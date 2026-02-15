<?php

namespace App\Contracts\Http\Controllers\Api\V1\Ecommerce\Admin;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

interface AdminOrderControllerContract
{
    public function index(Request $request): JsonResponse;

    public function show(int $order): JsonResponse;

    public function updateStatus(Request $request, int $order): JsonResponse;
}
