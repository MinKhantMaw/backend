<?php

namespace App\Contracts\Http\Controllers\Api\V1\Ecommerce;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

interface PaymentWebhookControllerContract
{
    public function handleStripe(Request $request): JsonResponse;
}
