<?php

namespace Database\Seeders;

use App\Constants\RoleConstant;
use App\Enums\BusinessType;
use App\Models\Business;
use App\Models\BusinessUser;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class TestVendorLenderSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * Creates 2 test vendors and 2 test lenders with roles and active status
     */
    public function run(): void
    {
        $this->command->info('🚀 Creating Test Vendors and Lenders...');

        // Get roles
        $vendorRole = Role::where('name', RoleConstant::VENDOR)->first();
        $lenderRole = Role::where('name', RoleConstant::LENDER)->first();

        if (! $vendorRole || ! $lenderRole) {
            $this->command->warn('⚠️  Vendor or Lender role not found. Please run RolePermissionSeeder first.');

            return;
        }

        // Create 2 Vendors
        $this->createVendor('testvendor1@10mg.com', 'Test Vendor 1', '+2348012345001', 'VENDOR001', $vendorRole);
        $this->createVendor('testvendor2@10mg.com', 'Test Vendor 2', '+2348012345002', 'VENDOR002', $vendorRole);

        // Create 2 Lenders
        $this->createLender('testlender1@10mg.com', 'Test Lender 1', '+2348022345001', 'LENDER001', $lenderRole);
        $this->createLender('testlender2@10mg.com', 'Test Lender 2', '+2348022345002', 'LENDER002', $lenderRole);

        $this->command->info('✅ Test users created successfully!');
        $this->command->info('');
        $this->command->info('Credentials:');
        $this->command->info('- testvendor1@10mg.com / password');
        $this->command->info('- testvendor2@10mg.com / password');
        $this->command->info('- testlender1@10mg.com / password');
        $this->command->info('- testlender2@10mg.com / password');
    }

    /**
     * Create a vendor user
     */
    private function createVendor(string $email, string $name, string $phone, string $code, Role $vendorRole): void
    {
        // Create user
        $user = User::updateOrCreate(
            ['email' => $email],
            [
                'name' => $name,
                'phone' => $phone,
                'email_verified_at' => now(),
                'active' => 1,
                'password' => Hash::make('password'),
            ]
        );

        // Assign vendor role
        if (! $user->hasRole(RoleConstant::VENDOR)) {
            $user->assignRole(RoleConstant::VENDOR);
        }

        // Create business
        $business = Business::updateOrCreate(
            ['code' => $code],
            [
                'name' => $name.' Business',
                'short_name' => $code,
                'owner_id' => $user->id,
                'type' => BusinessType::VENDOR->value,
                'status' => 'VERIFIED',
                'active' => true,
            ]
        );

        // Link user to business
        BusinessUser::firstOrCreate(
            ['user_id' => $user->id, 'business_id' => $business->id],
            ['role_id' => $vendorRole->id, 'active' => true]
        );

        $this->command->info("✓ Vendor: {$email}");
    }

    /**
     * Create a lender user
     */
    private function createLender(string $email, string $name, string $phone, string $code, Role $lenderRole): void
    {
        // Create user
        $user = User::updateOrCreate(
            ['email' => $email],
            [
                'name' => $name,
                'phone' => $phone,
                'email_verified_at' => now(),
                'active' => 1,
                'password' => Hash::make('password'),
            ]
        );

        // Assign lender role
        if (! $user->hasRole(RoleConstant::LENDER)) {
            $user->assignRole(RoleConstant::LENDER);
        }

        // Create business
        $business = Business::updateOrCreate(
            ['code' => $code],
            [
                'name' => $name.' Business',
                'short_name' => $code,
                'owner_id' => $user->id,
                'type' => BusinessType::LENDER->value,
                'status' => 'VERIFIED',
                'active' => true,
            ]
        );

        // Link user to business
        BusinessUser::firstOrCreate(
            ['user_id' => $user->id, 'business_id' => $business->id],
            ['role_id' => $lenderRole->id, 'active' => true]
        );

        $this->command->info("✓ Lender: {$email}");
    }
}
