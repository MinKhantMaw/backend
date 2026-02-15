<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\Ecommerce\Admin\AdminAnalyticsController;
use App\Http\Controllers\Api\V1\Ecommerce\Admin\AdminOrderController as EcommerceAdminOrderController;
use App\Http\Controllers\Api\V1\Ecommerce\Admin\AdminProductController;
use App\Http\Controllers\Api\V1\Ecommerce\CartController;
use App\Http\Controllers\Api\V1\Ecommerce\CategoryController as EcommerceCategoryController;
use App\Http\Controllers\Api\V1\Ecommerce\CheckoutController;
use App\Http\Controllers\Api\V1\Ecommerce\EcommerceAuthController;
use App\Http\Controllers\Api\V1\Ecommerce\OrderController as EcommerceOrderController;
use App\Http\Controllers\Api\V1\Ecommerce\PaymentWebhookController;
use App\Http\Controllers\Api\V1\Ecommerce\ProductController as EcommerceProductController;
use App\Http\Controllers\Api\V1\PermissionController;
use App\Http\Controllers\Api\V1\RoleController;
use App\Http\Controllers\Api\V1\SidebarController;
use App\Http\Controllers\Api\V1\UserController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::post('auth/register', [AuthController::class, 'register']);
    Route::post('auth/login', [AuthController::class, 'login']);

    Route::middleware('auth:api')->group(function () {
        Route::post('auth/logout', [AuthController::class, 'logout']);
        Route::get('auth/me', [AuthController::class, 'me']);
        Route::post('auth/change-password', [AuthController::class, 'changePassword']);
        Route::get('auth/sidebar', [SidebarController::class, 'index'])->middleware('permission:auth.sidebar.view');

        Route::middleware('role_or_permission:Super Admin|users.view|users.create|users.update|users.delete|users.assignRoles')
            ->group(function () {
                Route::get('users', [UserController::class, 'index'])->middleware('permission:users.view');
                Route::post('users', [UserController::class, 'store'])->middleware('permission:users.create');
                Route::get('users/{user}', [UserController::class, 'show'])->middleware('permission:users.view');
                Route::put('users/{user}', [UserController::class, 'update'])->middleware('permission:users.update');
                Route::delete('users/{user}', [UserController::class, 'destroy'])->middleware('permission:users.delete');
                Route::post('users/{user}/roles', [UserController::class, 'assignRoles'])->middleware('permission:users.assignRoles');
            });

        Route::middleware('role_or_permission:Super Admin|roles.view|roles.create|roles.update|roles.delete|roles.assignPermissions')
            ->group(function () {
                Route::get('roles', [RoleController::class, 'index'])->middleware('permission:roles.view');
                Route::post('roles', [RoleController::class, 'store'])->middleware('permission:roles.create');
                Route::get('roles/{role}', [RoleController::class, 'show'])->middleware('permission:roles.view');
                Route::put('roles/{role}', [RoleController::class, 'update'])->middleware('permission:roles.update');
                Route::delete('roles/{role}', [RoleController::class, 'destroy'])->middleware('permission:roles.delete');
                Route::put('roles/{role}/permissions', [RoleController::class, 'syncPermissions'])->middleware('permission:roles.assignPermissions');
            });

        Route::middleware('role_or_permission:Super Admin|permissions.view|permissions.create|permissions.update|permissions.delete')
            ->group(function () {
                Route::get('permissions', [PermissionController::class, 'index'])->middleware('permission:permissions.view');
                Route::post('permissions', [PermissionController::class, 'store'])->middleware('permission:permissions.create');
                Route::get('permissions/{permission}', [PermissionController::class, 'show'])->middleware('permission:permissions.view');
                Route::put('permissions/{permission}', [PermissionController::class, 'update'])->middleware('permission:permissions.update');
                Route::delete('permissions/{permission}', [PermissionController::class, 'destroy'])->middleware('permission:permissions.delete');
            });
    });

    // eCommerce API
    Route::post('auth/customer/register', [EcommerceAuthController::class, 'registerCustomer'])->middleware('throttle:auth-ecommerce');
    Route::post('auth/customer/login', [EcommerceAuthController::class, 'loginCustomer'])->middleware('throttle:auth-ecommerce');
    Route::post('auth/admin/login', [EcommerceAuthController::class, 'loginAdmin'])->middleware('throttle:auth-ecommerce');
    Route::post('auth/refresh', [EcommerceAuthController::class, 'refresh'])->middleware('throttle:auth-ecommerce');

    Route::get('products', [EcommerceProductController::class, 'index']);
    Route::get('products/{slug}', [EcommerceProductController::class, 'show']);
    Route::get('categories', [EcommerceCategoryController::class, 'index']);
    Route::post('categories', [EcommerceCategoryController::class, 'store'])
        ->middleware(['auth:api', 'ensure_role:admin,Super Admin']);

    Route::get('cart', [CartController::class, 'show']);
    Route::post('cart/items', [CartController::class, 'store']);
    Route::patch('cart/items/{item}', [CartController::class, 'update']);
    Route::delete('cart/items/{item}', [CartController::class, 'destroy']);

    Route::post('webhooks/payments/stripe', [PaymentWebhookController::class, 'handleStripe'])
        ->middleware(['throttle:webhooks', 'verify_webhook_signature']);

    Route::middleware('auth:api')->group(function () {
        Route::get('auth/customer/profile', [EcommerceAuthController::class, 'profile'])->middleware('ensure_role:customer');
        Route::put('auth/customer/profile', [EcommerceAuthController::class, 'updateProfile'])->middleware('ensure_role:customer');
        Route::post('auth/customer/logout', [EcommerceAuthController::class, 'logout']);
        Route::post('auth/logout-all', [EcommerceAuthController::class, 'logoutAll']);
        Route::post('cart/merge', [CartController::class, 'mergeGuest']);
        Route::post('checkout', [CheckoutController::class, 'store'])->middleware(['throttle:checkout', 'ensure_role:customer']);
        Route::post('orders', [CheckoutController::class, 'store'])->middleware(['throttle:checkout', 'ensure_role:customer']);
        Route::get('orders', [EcommerceOrderController::class, 'index'])->middleware('ensure_role:customer');
        Route::get('orders/{order}', [EcommerceOrderController::class, 'show'])->middleware('ensure_role:customer');
    });

    Route::middleware(['auth:api', 'ensure_role:admin,Super Admin'])->prefix('admin')->group(function () {
        Route::get('products', [AdminProductController::class, 'index']);
        Route::post('products', [AdminProductController::class, 'store']);
        Route::get('products/{product}', [AdminProductController::class, 'show']);
        Route::put('products/{product}', [AdminProductController::class, 'update']);
        Route::delete('products/{product}', [AdminProductController::class, 'destroy']);
        Route::post('products/{product}/images', [AdminProductController::class, 'uploadImage']);

        Route::get('orders', [EcommerceAdminOrderController::class, 'index']);
        Route::get('orders/{order}', [EcommerceAdminOrderController::class, 'show']);
        Route::patch('orders/{order}/status', [EcommerceAdminOrderController::class, 'updateStatus']);

        Route::get('analytics/overview', [AdminAnalyticsController::class, 'overview']);
    });
});
