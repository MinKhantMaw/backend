<?php

namespace App\Http\Requests\V1\User;

use App\Enums\UserStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Role;

class UserUpdateRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $merge = [];

        if ($this->has('roles') || $this->has('role')) {
            $roles = $this->input('roles');
            if ($roles === null && $this->has('role')) {
                $roles = [$this->input('role')];
            }
            $merge['roles'] = $this->normalizeNames($roles, Role::class);
        }

        if (!empty($merge)) {
            $this->merge($merge);
        }
    }

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $userId = $this->route('user')?->id;

        return [
            'name' => ['sometimes', 'string', 'max:255'],
            'email' => ['sometimes', 'string', 'email', 'max:255', 'unique:users,email,' . $userId],
            'mobile_country_code' => ['sometimes', 'nullable', 'string', 'max:10'],
            'mobile_number' => ['sometimes', 'nullable', 'string', 'max:30'],
            'password' => ['nullable', 'string', 'min:8'],
            'status' => ['sometimes', Rule::in(array_column(UserStatus::cases(), 'value'))],
            'roles' => ['nullable', 'array'],
            'roles.*' => ['string', Rule::exists('roles', 'name')->where('guard_name', 'api')],
        ];
    }

    private function normalizeNames(mixed $items, string $modelClass): array
    {
        if ($items === null) {
            return [];
        }

        $items = is_array($items) ? $items : [$items];

        return collect($items)
            ->map(function (mixed $item) use ($modelClass): ?string {
                if (is_array($item)) {
                    $item = $item['name'] ?? $item['id'] ?? null;
                } elseif (is_object($item)) {
                    $item = $item->name ?? $item->id ?? null;
                }

                if (is_numeric($item)) {
                    return $modelClass::query()->whereKey((int) $item)->value('name');
                }

                if (is_string($item)) {
                    $item = trim($item);
                    return $item === '' ? null : $item;
                }

                return null;
            })
            ->filter()
            ->values()
            ->all();
    }
}
