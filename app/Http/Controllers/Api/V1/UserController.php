<?php

namespace App\Http\Controllers\Api\V1;

use OpenApi\Annotations as OA;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\User\UserAssignRolesRequest;
use App\Http\Requests\V1\User\UserIndexRequest;
use App\Http\Requests\V1\User\UserStoreRequest;
use App\Http\Requests\V1\User\UserUpdateRequest;
use App\Http\Resources\V1\UserResource;
use App\Models\User;
use App\Services\UserService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;

class UserController extends Controller
{
    public function __construct(private readonly UserService $userService) {}

    /**
     * @OA\Get(
     *     path="/api/v1/users",
     *     summary="Get all users",
     *     tags={"Users"},
     *     @OA\Response(
     *         response=200,
     *         description="Success"
     *     )
     * )
     */

    public function index(UserIndexRequest $request): JsonResponse
    {
        $this->authorize('viewAny', User::class);

        $search = $request->validated('search');
        $perPage = $request->validated('per_page', 15);
        $paginator = $this->userService->paginate($search, $perPage);

        $data = UserResource::collection($paginator)->resolve($request);

        return ApiResponse::success(
            $data,
            'Users retrieved successfully.',
            200,
            ['pagination' => ApiResponse::paginationMeta($paginator)]
        );
    }

    public function store(UserStoreRequest $request): JsonResponse
    {
        $this->authorize('create', User::class);

        $user = $this->userService->create($request->validated(), $request->user());

        return ApiResponse::success(
            new UserResource($user),
            'User created successfully.',
            201
        );
    }

    public function show(User $user): JsonResponse
    {
        $this->authorize('view', $user);

        return ApiResponse::success(
            new UserResource($user->load(['roles'])),
            'User retrieved successfully.'
        );
    }

    public function update(UserUpdateRequest $request, User $user): JsonResponse
    {
        $this->authorize('update', $user);

        $user = $this->userService->update($user, $request->validated(), $request->user());

        return ApiResponse::success(
            new UserResource($user),
            'User updated successfully.'
        );
    }

    public function destroy(User $user): JsonResponse
    {
        $this->authorize('delete', $user);

        $this->userService->delete($user, $this->user());

        return ApiResponse::success(null, 'User deleted successfully.');
    }

    public function assignRoles(UserAssignRolesRequest $request, User $user): JsonResponse
    {
        $this->authorize('assignRoles', $user);

        $user = $this->userService->assignRoles($user, $request->validated('roles', []), $request->user());

        return ApiResponse::success(
            new UserResource($user),
            'Roles assigned successfully.'
        );
    }

    private function user(): ?User
    {
        /** @var User|null $user */
        $user = request()->user();

        return $user;
    }
}
