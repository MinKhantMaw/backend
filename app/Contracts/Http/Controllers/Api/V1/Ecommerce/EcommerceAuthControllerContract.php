<?php

namespace App\Contracts\Http\Controllers\Api\V1\Ecommerce;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

interface EcommerceAuthControllerContract
{
    public function registerCustomer(Request $request): JsonResponse;

    public function loginCustomer(Request $request): JsonResponse;

    public function loginAdmin(Request $request): JsonResponse;

    public function refresh(Request $request): JsonResponse;

    public function profile(Request $request): JsonResponse;

    public function updateProfile(Request $request): JsonResponse;

    public function logout(Request $request): JsonResponse;

    public function logoutAll(Request $request): JsonResponse;
}
