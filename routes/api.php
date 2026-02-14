<?php

use App\Http\Controllers\Api\V1\AuthController;
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
});
