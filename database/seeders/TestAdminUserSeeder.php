<?php

namespace Database\Seeders;

use App\Constants\RoleConstant;
use App\Models\Business;
use App\Models\BusinessUser;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class TestAdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * Creates a test admin user with password "pass wrld" for testing purposes.
     */
    public function run(): void
    {
        $administrator = RoleConstant::ADMIN;

        // Get admin role
        $administratorRole = Role::where('name', $administrator)->first();

        if (! $administratorRole) {
            $this->command->warn('Admin role not found. Please run RolePermissionSeeder first.');

            return;
        }

        // Create or update test admin user
        $user = User::updateOrCreate(
            ['email' => 'testadmin@10mg.com'],
            [
                'name' => 'Test Admin User',
                'phone' => '08012345678',
                'gender' => 'male',
                'force_password_change' => false,
                'email_verified_at' => now(), // Email verified
                'active' => 1, // User is active
                'password' => Hash::make('pass wrld'),
            ]
        );

        // Ensure admin role is assigned
        if (! $user->hasRole($administratorRole->name)) {
            $user->assignRole($administratorRole->name);
        }

        // Create or update admin business (required for roleCheck middleware)
        $business = Business::updateOrCreate(
            ['owner_id' => $user->id, 'type' => 'ADMIN'],
            [
                'name' => '10MG Admin Business',
                'short_name' => '10MGADMIN',
                'code' => '10MGADMIN',
                'type' => 'ADMIN',
                'status' => 'VERIFIED',
                'active' => true,
            ]
        );

        // Link user to business via business_users table
        BusinessUser::firstOrCreate(
            [
                'user_id' => $user->id,
                'business_id' => $business->id,
            ],
            ['role_id' => $administratorRole->id, 'active' => true]
        );

        $this->command->info('Test admin user created/updated successfully!');
        $this->command->info('Email: testadmin@10mg.com');
        $this->command->info('Password: pass wrld');
        $this->command->info('Role: '.$administratorRole->name);
        $this->command->info('Business Type: ADMIN');
        $this->command->info('Email Verified: Yes');
        $this->command->info('Status: Active');
    }
}
