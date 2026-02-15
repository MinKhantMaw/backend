<?php

namespace App\Http\Controllers\Api\V1\Ecommerce\Admin;

use App\Contracts\Http\Controllers\Api\V1\Ecommerce\Admin\AdminAnalyticsControllerContract;
use App\Http\Controllers\Controller;
use App\Services\Ecommerce\AnalyticsService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;

class AdminAnalyticsController extends Controller implements AdminAnalyticsControllerContract
{
    public function __construct(private readonly AnalyticsService $analyticsService)
    {
    }

    public function overview(): JsonResponse
    {
        return ApiResponse::success(
            $this->analyticsService->overview(),
            'Analytics overview fetched successfully.'
        );
    }
}
