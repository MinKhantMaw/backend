<?php

namespace App\Http\Controllers\Api\V1\Ecommerce;

use App\Contracts\Http\Controllers\Api\V1\Ecommerce\PaymentWebhookControllerContract;
use App\Http\Controllers\Controller;
use App\Services\Ecommerce\PaymentService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PaymentWebhookController extends Controller implements PaymentWebhookControllerContract
{
    public function __construct(private readonly PaymentService $paymentService)
    {
    }

    public function handleStripe(Request $request): JsonResponse
    {
        $event = $request->json()->all();
        $this->paymentService->processWebhook($event);

        return ApiResponse::success(['received' => true], 'Webhook processed.');
    }
}
