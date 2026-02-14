<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Role\RoleIndexRequest;
use App\Http\Requests\V1\Role\RoleStoreRequest;
use App\Http\Requests\V1\Role\RoleSyncPermissionsRequest;
use App\Http\Requests\V1\Role\RoleUpdateRequest;
use App\Http\Resources\V1\RoleResource;
use App\Services\RoleService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Spatie\Permission\Models\Role;

class RoleController extends Controller
{
    public function __construct(private readonly RoleService $roleService)
    {
    }

    public function index(RoleIndexRequest $request): JsonResponse
    {
        $this->authorize('viewAny', Role::class);

        $search = $request->validated('search');
        $perPage = $request->validated('per_page', 15);
        $paginator = $this->roleService->paginate($search, $perPage);

        $data = RoleResource::collection($paginator)->resolve($request);

        return ApiResponse::success(
            $data,
            'Roles retrieved successfully.',
            200,
            ['pagination' => ApiResponse::paginationMeta($paginator)]
        );
    }

    public function store(RoleStoreRequest $request): JsonResponse
    {
        $this->authorize('create', Role::class);

        $role = $this->roleService->create($request->validated());

        return ApiResponse::success(
            new RoleResource($role),
            'Role created successfully.',
            201
        );
    }

    public function show(Role $role): JsonResponse
    {
        $this->authorize('view', $role);

        return ApiResponse::success(
            new RoleResource($role->load('permissions')),
            'Role retrieved successfully.'
        );
    }

    public function update(RoleUpdateRequest $request, Role $role): JsonResponse
    {
        $this->authorize('update', $role);

        $role = $this->roleService->update($role, $request->validated());

        return ApiResponse::success(
            new RoleResource($role),
            'Role updated successfully.'
        );
    }

    public function destroy(Role $role): JsonResponse
    {
        $this->authorize('delete', $role);

        $this->roleService->delete($role);

        return ApiResponse::success(null, 'Role deleted successfully.');
    }

    public function syncPermissions(RoleSyncPermissionsRequest $request, Role $role): JsonResponse
    {
        $this->authorize('assignPermissions', $role);

        $role = $this->roleService->syncPermissions($role, $request->validated('permissions'));

        return ApiResponse::success(
            new RoleResource($role),
            'Permissions synced successfully.'
        );
    }
}
