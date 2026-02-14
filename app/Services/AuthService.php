<?php

namespace App\Services;

use App\Enums\UserStatus;
use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Laravel\Passport\PersonalAccessTokenResult;

class AuthService
{
    public function register(array $data): User
    {
        $payload = Arr::only($data, ['name', 'email', 'mobile_country_code', 'mobile_number', 'password']);
        $payload['password'] = Hash::make($payload['password']);
        $payload['status'] = UserStatus::ACTIVE->value;
        $payload['password_changed_at'] = now();

        return User::create($payload)->load(['roles']);
    }

    public function attemptLogin(array $credentials): ?array
    {
        $user = User::query()->where('email', $credentials['email'])->first();

        if (!$user || !Hash::check($credentials['password'], $user->password)) {
            return null;
        }

        if ($user->status !== UserStatus::ACTIVE) {
            return null;
        }

        $user->load(['roles']);
        $tokenResult = $this->createToken($user);

        return [
            'user' => $user,
            'access_token' => $tokenResult->accessToken,
            'token_type' => 'Bearer',
            'expires_at' => $tokenResult->token->expires_at,
        ];
    }

    public function logout(User $user): void
    {
        $token = $user->token();
        if ($token) {
            $token->revoke();
        }
    }

    private function createToken(User $user): PersonalAccessTokenResult
    {
        return $user->createToken('api-token');
    }

    public function changePassword(User $user, string $currentPassword, string $newPassword): void
    {
        if (!Hash::check($currentPassword, $user->password)) {
            throw ValidationException::withMessages([
                'current_password' => ['Current password is incorrect.'],
            ]);
        }

        $user->forceFill([
            'password' => Hash::make($newPassword),
            'password_changed_at' => now(),
            'updated_by' => $user->id,
        ])->save();
    }
}
