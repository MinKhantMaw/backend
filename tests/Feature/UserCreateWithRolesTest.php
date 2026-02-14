<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserCreateWithRolesTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_a_user_and_assigns_multiple_roles_in_one_request(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->withoutMiddleware();

        $response = $this->postJson('/api/v1/users', [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'secret123',
            'roles' => ['admin', 'Manager'],
        ]);

        $response->assertCreated()
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.email', 'john@example.com');

        $user = User::where('email', 'john@example.com')->firstOrFail();

        $this->assertTrue($user->hasRole('admin'));
        $this->assertTrue($user->hasRole('Manager'));
    }

    public function test_it_rejects_invalid_roles_when_creating_a_user(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->withoutMiddleware();

        $response = $this->postJson('/api/v1/users', [
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
            'password' => 'secret123',
            'roles' => ['not-a-real-role'],
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('status', 'error')
            ->assertJsonStructure(['errors' => ['roles.0']]);
    }
}
