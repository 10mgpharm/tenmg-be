<?php

namespace Database\Seeders;

use App\Constants\RoleConstant;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserRolePermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $administrator = RoleConstant::ADMIN;

        // admin role and assign permissions
        $administratorRole = Role::where('name', $administrator)->first();
        if ($administratorRole) {
            $user = User::first();
            if ($user) {
                $user->assignRole($administratorRole->name);
            } else {
                $user = User::factory()->create([
                    'name' => 'Christian Praise',
                    'email' => 'admin@10mg.com',
                    'phone' => '09031461447',
                    'gender' => 'male',
                    'force_password_change' => false,
                    'email_verified_at' => now(),
                    'password' => Hash::make('password'),
                ]);
                //assign role
                $user->assignRole($administratorRole->name);
            }
        }
    }
}
