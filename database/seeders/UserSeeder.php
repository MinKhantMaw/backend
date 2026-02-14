<?php

namespace Database\Seeders;

use App\Enums\UserStatus;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $superAdminRole = Role::where('name', 'Super Admin')->first();
        $managerRole = Role::where('name', 'Manager')->first();

        $admin = User::firstOrCreate([
            'email' => 'admin@example.com',
        ], [
            'name' => 'Super Admin',
            'mobile_country_code' => '+95',
            'mobile_number' => '9000000000',
            'password' => Hash::make('password'),
            'status' => UserStatus::ACTIVE->value,
            'password_changed_at' => now(),
        ]);

        if ($superAdminRole) {
            $admin->syncRoles([$superAdminRole->name]);
        }
        $admin->forceFill([
            'created_by' => $admin->id,
            'updated_by' => $admin->id,
        ])->save();

        $manager = User::firstOrCreate([
            'email' => 'manager@example.com',
        ], [
            'name' => 'Manager User',
            'mobile_country_code' => '+95',
            'mobile_number' => '9111111111',
            'password' => Hash::make('password'),
            'status' => UserStatus::ACTIVE->value,
            'password_changed_at' => now(),
            'created_by' => $admin->id,
            'updated_by' => $admin->id,
        ]);

        if ($managerRole) {
            $manager->assignRole($managerRole);
        }

        User::factory(5)->create();
    }
}
