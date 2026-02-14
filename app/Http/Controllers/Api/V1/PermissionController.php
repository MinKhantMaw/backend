<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Permission\PermissionIndexRequest;
use App\Http\Requests\V1\Permission\PermissionStoreRequest;
use App\Http\Requests\V1\Permission\PermissionUpdateRequest;
use App\Http\Resources\V1\PermissionResource;
use App\Services\PermissionService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Spatie\Permission\Models\Permission;

class PermissionController extends Controller
{
    public function __construct(private readonly PermissionService $permissionService)
    {
    }

    public function index(PermissionIndexRequest $request): JsonResponse
    {
        $this->authorize('viewAny', Permission::class);

        $search = $request->validated('search');
        $perPage = $request->validated('per_page', 15);
        $paginator = $this->permissionService->paginate($search, $perPage);

        $data = PermissionResource::collection($paginator)->resolve($request);

        return ApiResponse::success(
            $data,
            'Permissions retrieved successfully.',
            200,
            ['pagination' => ApiResponse::paginationMeta($paginator)]
        );
    }

    public function store(PermissionStoreRequest $request): JsonResponse
    {
        $this->authorize('create', Permission::class);

        $permission = $this->permissionService->create($request->validated());

        return ApiResponse::success(
            new PermissionResource($permission),
            'Permission created successfully.',
            201
        );
    }

    public function show(Permission $permission): JsonResponse
    {
        $this->authorize('view', $permission);

        return ApiResponse::success(
            new PermissionResource($permission),
            'Permission retrieved successfully.'
        );
    }

    public function update(PermissionUpdateRequest $request, Permission $permission): JsonResponse
    {
        $this->authorize('update', $permission);

        $permission = $this->permissionService->update($permission, $request->validated());

        return ApiResponse::success(
            new PermissionResource($permission),
            'Permission updated successfully.'
        );
    }

    public function destroy(Permission $permission): JsonResponse
    {
        $this->authorize('delete', $permission);

        $this->permissionService->delete($permission);

        return ApiResponse::success(null, 'Permission deleted successfully.');
    }
}
