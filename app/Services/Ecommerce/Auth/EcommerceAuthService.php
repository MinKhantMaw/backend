<?php

namespace App\Services\Ecommerce\Auth;

use App\Models\ApiRefreshToken;
use App\Enums\UserStatus;
use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Laravel\Passport\PersonalAccessTokenResult;
use Spatie\Permission\Models\Role;

class EcommerceAuthService
{
    public function registerCustomer(array $payload): array
    {
        $user = User::query()->create([
            'name' => $payload['name'],
            'email' => $payload['email'],
            'address' => $payload['address'],
            'password' => Hash::make($payload['password']),
            'status' => UserStatus::ACTIVE,
            'password_changed_at' => now(),
        ]);

        $customerRole = Role::query()->firstOrCreate([
            'name' => 'customer',
            'guard_name' => 'api',
        ]);

        $user->syncRoles([$customerRole]);

        return $this->issueTokenPair($user->load('roles'), 'storefront-token', $payload['device_name'] ?? 'storefront');
    }

    public function loginCustomer(array $payload): array
    {
        return $this->loginWithRole($payload, 'customer', 'storefront-token');
    }

    public function loginAdmin(array $payload): array
    {
        return $this->loginWithRole($payload, 'admin', 'admin-token');
    }

    public function refresh(string $refreshToken, ?string $ipAddress = null, ?string $userAgent = null): array
    {
        $hash = hash('sha256', $refreshToken);
        $refresh = ApiRefreshToken::query()
            ->where('token_hash', $hash)
            ->whereNull('revoked_at')
            ->first();

        if (! $refresh || $refresh->expires_at->isPast()) {
            throw ValidationException::withMessages([
                'refresh_token' => ['Refresh token is invalid or expired.'],
            ]);
        }

        $user = $refresh->user()->with('roles')->firstOrFail();
        $tokenName = $user->hasRole('admin') ? 'admin-token' : 'storefront-token';

        return DB::transaction(function () use ($refresh, $user, $tokenName, $ipAddress, $userAgent) {
            $refresh->update(['revoked_at' => now()]);

            return $this->issueTokenPair($user, $tokenName, $refresh->device_name ?? 'refreshed', $ipAddress, $userAgent);
        });
    }

    public function logoutAll(User $user): void
    {
        $user->tokens()->update(['revoked' => true]);
        $user->refreshTokens()->update(['revoked_at' => now()]);
    }

    public function logout(User $user): void
    {
        $token = $user->token();

        if ($token) {
            $token->revoke();
        }
    }

    private function loginWithRole(array $payload, string $requiredRole, string $tokenName): array
    {
        $user = User::query()->where('email', $payload['email'])->first();

        if (! $user || ! Hash::check($payload['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['Invalid credentials.'],
            ]);
        }

        if (! $user->hasRole($requiredRole)) {
            throw ValidationException::withMessages([
                'email' => ['You are not allowed to access this area.'],
            ]);
        }

        return $this->issueTokenPair(
            $user->load('roles'),
            $tokenName,
            $payload['device_name'] ?? $requiredRole,
            $payload['ip_address'] ?? null,
            $payload['user_agent'] ?? null
        );
    }

    private function issueTokenPair(
        User $user,
        string $tokenName,
        string $deviceName,
        ?string $ipAddress = null,
        ?string $userAgent = null
    ): array
    {
        $tokenResult = $this->createToken($user, $tokenName);
        $refreshToken = Str::random(80);

        ApiRefreshToken::query()->create([
            'user_id' => $user->id,
            'token_hash' => hash('sha256', $refreshToken),
            'device_name' => $deviceName,
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent,
            'expires_at' => now()->addDays(30),
        ]);

        return [
            'user' => $user,
            'access_token' => $tokenResult->accessToken,
            'token_type' => 'Bearer',
            'expires_at' => $tokenResult->token->expires_at,
            'refresh_token' => $refreshToken,
            'refresh_expires_at' => now()->addDays(30),
        ];
    }

    private function createToken(User $user, string $tokenName): PersonalAccessTokenResult
    {
        return $user->createToken($tokenName);
    }
}
