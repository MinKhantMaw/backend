<?php

namespace App\Http\Requests\V1\User;

use App\Enums\UserStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Role;

class UserStoreRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $roles = $this->input('roles');
        if ($roles === null && $this->has('role')) {
            $roles = [$this->input('role')];
        }

        $this->merge([
            'roles' => $this->normalizeNames($roles, Role::class),
        ]);
    }

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'mobile_country_code' => ['nullable', 'string', 'max:10'],
            'mobile_number' => ['nullable', 'string', 'max:30'],
            'password' => ['required', 'string', 'min:8'],
            'status' => ['nullable', Rule::in(array_column(UserStatus::cases(), 'value'))],
            'roles' => ['required', 'array', 'min:1'],
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
