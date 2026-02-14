<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Str;

class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $roleName = $this->roles->first()?->name;

        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'mobile_country_code' => $this->mobile_country_code,
            'mobile_number' => $this->mobile_number,
            'status' => $this->status?->value ?? (string) $this->status,
            'role' => $roleName ? [
                'label' => Str::headline($roleName),
                'name' => $roleName,
            ] : null,
            'roles' => $this->whenLoaded('roles', fn () => $this->roles->pluck('name')->values()),
            'permissions' => $this->getPermissionsViaRoles()->pluck('name')->values(),
            'password_changed_at' => $this->password_changed_at,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
