<?php

namespace App\Http\Controllers\Api\V1\Ecommerce;

use App\Contracts\Http\Controllers\Api\V1\Ecommerce\EcommerceAuthControllerContract;
use App\Http\Controllers\Controller;
use App\Services\Ecommerce\Auth\EcommerceAuthService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class EcommerceAuthController extends Controller implements EcommerceAuthControllerContract
{
    public function __construct(private readonly EcommerceAuthService $authService) {}

    public function registerCustomer(Request $request): JsonResponse
    {
        $payload = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'address' => ['required', 'string', 'min:8', 'max:255']
        ]);
        $payload['device_name'] = (string) $request->input('device_name', 'storefront');
        $result = $this->authService->registerCustomer($payload);

        return ApiResponse::success($result, 'Customer registered successfully.', 201);
    }

    public function loginCustomer(Request $request): JsonResponse
    {
        $payload = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
            'device_name' => ['sometimes', 'string', 'max:120'],
        ]);
        $payload['ip_address'] = $request->ip();
        $payload['user_agent'] = (string) $request->userAgent();
        $result = $this->authService->loginCustomer($payload);

        return ApiResponse::success($result, 'Login successful.');
    }

    public function loginAdmin(Request $request): JsonResponse
    {
        $payload = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
            'device_name' => ['sometimes', 'string', 'max:120'],
        ]);
        $payload['ip_address'] = $request->ip();
        $payload['user_agent'] = (string) $request->userAgent();
        $result = $this->authService->loginAdmin($payload);

        return ApiResponse::success($result, 'Admin login successful.');
    }

    public function refresh(Request $request): JsonResponse
    {
        $payload = $request->validate([
            'refresh_token' => ['required', 'string'],
        ]);

        $result = $this->authService->refresh(
            $payload['refresh_token'],
            $request->ip(),
            (string) $request->userAgent()
        );

        return ApiResponse::success($result, 'Token refreshed successfully.');
    }

    public function profile(Request $request): JsonResponse
    {
        $user = $request->user()->loadMissing('roles');

        return ApiResponse::success($user, 'Customer profile fetched successfully.');
    }

    public function updateProfile(Request $request): JsonResponse
    {
        $user = $request->user();

        $payload = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'email' => ['sometimes', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'address' => ['sometimes', 'string', 'max:255'],
            'mobile_country_code' => ['sometimes', 'nullable', 'string', 'max:10'],
            'mobile_number' => ['sometimes', 'nullable', 'string', 'max:30'],
            'profile_image' => ['sometimes', 'image', 'max:5120'],
        ]);

        if ($request->hasFile('profile_image')) {
            if (! empty($user->profile_image)) {
                $oldPath = Str::after($user->profile_image, '/storage/');

                if ($oldPath !== '' && Storage::disk('public')->exists($oldPath)) {
                    Storage::disk('public')->delete($oldPath);
                }
            }

            $file = $request->file('profile_image');
            $path = $file->storeAs(
                'users/'.$user->id,
                Str::uuid()->toString().'.'.$file->getClientOriginalExtension(),
                'public'
            );
            $payload['profile_image'] = Storage::disk('public')->url($path);
        }

        if (empty($payload)) {
            return ApiResponse::success($user->loadMissing('roles'), 'No profile changes provided.');
        }

        $payload['updated_by'] = $user->id;
        $user->fill($payload)->save();

        return ApiResponse::success($user->fresh()->loadMissing('roles'), 'Customer profile updated successfully.');
    }

    public function logout(Request $request): JsonResponse
    {
        $this->authService->logout($request->user());

        return ApiResponse::success(null, 'Logged out successfully.');
    }

    public function logoutAll(Request $request): JsonResponse
    {
        $this->authService->logoutAll($request->user());

        return ApiResponse::success(null, 'Logged out from all devices successfully.');
    }
}
