<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SidebarController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $items = [
            [
                'key' => 'users',
                'label' => 'Users',
                'path' => '/users',
                'required_permissions' => ['users.view'],
            ],
            [
                'key' => 'roles',
                'label' => 'Roles',
                'path' => '/roles',
                'required_permissions' => ['roles.view'],
            ],
            [
                'key' => 'permissions',
                'label' => 'Permissions',
                'path' => '/permissions',
                'required_permissions' => ['permissions.view'],
            ],
            [
                'key' => 'admins',
                'label' => 'Admins',
                'path' => '/admins',
                'required_permissions' => ['users.assignRoles'],
            ],
        ];

        if ($user->hasRole('Super Admin')) {
            return ApiResponse::success($items, 'Sidebar items retrieved successfully.');
        }

        $allowed = array_values(array_filter($items, function (array $item) use ($user) {
            foreach ($item['required_permissions'] as $permission) {
                if ($user->can($permission)) {
                    return true;
                }
            }

            return false;
        }));

        return ApiResponse::success($allowed, 'Sidebar items retrieved successfully.');
    }
}
