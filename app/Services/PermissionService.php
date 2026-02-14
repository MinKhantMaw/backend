<?php

namespace App\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Spatie\Permission\Models\Permission;

class PermissionService
{
    public function paginate(?string $search, int $perPage = 15): LengthAwarePaginator
    {
        return Permission::query()
            ->when($search, function ($query, $search) {
                $query->where('name', 'like', "%{$search}%");
            })
            ->orderBy('id', 'desc')
            ->paginate($perPage);
    }

    public function create(array $data): Permission
    {
        return Permission::create([
            'name' => $data['name'],
            'guard_name' => $data['guard_name'] ?? 'api',
        ]);
    }

    public function update(Permission $permission, array $data): Permission
    {
        if (!empty($data['name'])) {
            $permission->name = $data['name'];
        }

        if (!empty($data['guard_name'])) {
            $permission->guard_name = $data['guard_name'];
        }

        $permission->save();

        return $permission;
    }

    public function delete(Permission $permission): void
    {
        $permission->delete();
    }
}
