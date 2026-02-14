<?php

namespace App\Http\Requests\V1\Permission;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PermissionStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'max:255',
                'regex:/^[a-z]+(?:\.[a-z]+)+$/',
                Rule::unique('permissions', 'name')->where('guard_name', 'api'),
            ],
            'guard_name' => ['nullable', 'string', 'in:api'],
        ];
    }
}
