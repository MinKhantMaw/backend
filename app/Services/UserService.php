<?php

namespace App\Services;

use App\Enums\UserStatus;
use App\Models\User;
use App\Repositories\Contracts\UserRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class UserService
{
    public function __construct(private readonly UserRepositoryInterface $users)
    {
    }

    public function paginate(?string $search, int $perPage = 15): LengthAwarePaginator
    {
        return $this->users->paginate($search, $perPage);
    }

    public function create(array $data, ?User $actor = null): User
    {
        return DB::transaction(function () use ($data, $actor): User {
            $payload = Arr::only($data, [
                'name',
                'email',
                'address',
                'mobile_country_code',
                'mobile_number',
                'password',
                'status',
            ]);
            $payload['password'] = Hash::make($payload['password']);
            $payload['status'] = $payload['status'] ?? UserStatus::ACTIVE->value;
            $payload['password_changed_at'] = now();
            $payload['created_by'] = $actor?->id;
            $payload['updated_by'] = $actor?->id;

            $user = $this->users->create($payload);

            $this->assertRoleAssignmentAllowed($data['roles'] ?? [], $actor);
            $user->syncRoles($data['roles'] ?? []);
            $user->syncPermissions([]);

            return $user->load(['roles']);
        });
    }

    public function update(User $user, array $data, ?User $actor = null): User
    {
        return DB::transaction(function () use ($user, $data, $actor): User {
            $payload = Arr::only($data, ['name', 'email', 'address', 'mobile_country_code', 'mobile_number', 'status']);

            if (!empty($data['password'])) {
                $payload['password'] = Hash::make($data['password']);
                $payload['password_changed_at'] = now();
            }
            $payload['updated_by'] = $actor?->id;

            $this->users->update($user, $payload);

            if (array_key_exists('roles', $data)) {
                $this->assertRoleAssignmentAllowed($data['roles'] ?? [], $actor);
                $user->syncRoles($data['roles'] ?? []);
            }

            // Enforce role-only authorization model.
            $user->syncPermissions([]);

            return $user->load(['roles']);
        });
    }

    public function delete(User $user, ?User $actor = null): void
    {
        $user->deleted_by = $actor?->id;
        $user->updated_by = $actor?->id;
        $user->save();
        $this->users->delete($user);
    }

    public function assignRoles(User $user, array $roles, ?User $actor = null): User
    {
        $this->assertRoleAssignmentAllowed($roles, $actor);
        $user->syncRoles($roles);
        $user->syncPermissions([]);

        return $user->load(['roles']);
    }

    private function assertRoleAssignmentAllowed(array $roles, ?User $actor): void
    {
        if ($actor === null || $actor->hasRole('Super Admin')) {
            return;
        }

        if (in_array('Super Admin', $roles, true)) {
            throw ValidationException::withMessages([
                'roles' => ['Only Super Admin can assign the Super Admin role.'],
            ]);
        }
    }
}
