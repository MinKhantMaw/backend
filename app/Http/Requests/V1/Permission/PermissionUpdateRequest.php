<?php

namespace App\Http\Requests\V1\Permission;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PermissionUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $permissionId = $this->route('permission')?->id;

        return [
            'name' => [
                'sometimes',
                'string',
                'max:255',
                'regex:/^[a-z]+(?:\.[a-z]+)+$/',
                Rule::unique('permissions', 'name')
                    ->ignore($permissionId)
                    ->where('guard_name', 'api'),
            ],
            'guard_name' => ['nullable', 'string', 'in:api'],
        ];
    }
}
