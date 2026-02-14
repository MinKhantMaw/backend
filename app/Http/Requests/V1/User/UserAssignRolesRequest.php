<?php

namespace App\Http\Requests\V1\User;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Role;

class UserAssignRolesRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $roles = $this->input('roles');
        if ($roles === null && $this->has('role')) {
            $roles = [$this->input('role')];
        }

        if ($this->has('roles') || $this->has('role')) {
            $this->merge([
                'roles' => $this->normalizeRoles($roles),
            ]);
        }
    }

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'roles' => ['nullable', 'array'],
            'roles.*' => ['string', Rule::exists('roles', 'name')->where('guard_name', 'api')],
        ];
    }

    private function normalizeRoles(mixed $items): array
    {
        if ($items === null) {
            return [];
        }

        $items = is_array($items) ? $items : [$items];

        return collect($items)
            ->map(function (mixed $item): ?string {
                if (is_array($item)) {
                    $item = $item['name'] ?? $item['id'] ?? null;
                } elseif (is_object($item)) {
                    $item = $item->name ?? $item->id ?? null;
                }

                if (is_numeric($item)) {
                    return Role::query()->whereKey((int) $item)->value('name');
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
