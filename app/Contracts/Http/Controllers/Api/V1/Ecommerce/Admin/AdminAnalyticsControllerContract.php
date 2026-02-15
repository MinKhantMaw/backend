<?php

namespace App\Contracts\Http\Controllers\Api\V1\Ecommerce\Admin;

use Illuminate\Http\JsonResponse;

interface AdminAnalyticsControllerContract
{
    public function overview(): JsonResponse;
}
