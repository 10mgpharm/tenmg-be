<?php

namespace Database\Seeders;

use App\Models\Business;
use App\Models\BusinessUser;
use App\Models\CreditCustomer;
use App\Models\Role;
use App\Models\User;
use Faker\Factory as Faker;
use Illuminate\Database\Seeder;

class BusinessUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $faker = Faker::create();

        // Roles
        $adminRole = Role::where('name', 'admin')->first();
        $operationRole = Role::where('name', 'operation')->first();
        $supportRole = Role::where('name', 'support')->first();
        $supplierRole = Role::where('name', 'supplier')->first();
        $vendorRole = Role::where('name', 'vendor')->first();
        $customerRole = Role::where('name', 'customer')->first(); // Pharmacy / Hospital

        // 1. Setup Admin Business and Users
        $adminUser = User::firstOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name' => $faker->name(),
                'phone' => $faker->phoneNumber(),
                'email_verified_at' => now(),
                'password' => bcrypt('password'),
            ]
        );
        $adminUser->assignRole($adminRole);

        $adminBusiness = Business::firstOrCreate(
            [
                'code' => '10MG',
                'name' => '10MG Health',
                'short_name' => 'tenmg',
            ],
            [
                'owner_id' => $adminUser->id,
                'type' => 'ADMIN',
                'address' => $faker->address,
                'contact_person' => $faker->name(),
                'contact_phone' => $faker->phoneNumber,
                'contact_email' => $faker->email,
                'status' => 'VERIFIED',
            ]
        );

        BusinessUser::firstOrCreate(
            ['user_id' => $adminUser->id, 'business_id' => $adminBusiness->id],
            ['role_id' => $adminRole->id]
        );

        // Admin Staffs (Other users) - 2 operation, 2 support, 1 additional admin
        for ($i = 1; $i <= 5; $i++) {
            $user = User::firstOrCreate(
                ['email' => "admin_user_$i@example.com"],
                [
                    'name' => $faker->name(),
                    'phone' => $faker->phoneNumber(),
                    'email_verified_at' => now(),
                    'password' => bcrypt('password'),
                ]
            );

            $role = ($i <= 2) ? $supportRole :
                    (($i <= 4) ? $operationRole : $adminRole);

            BusinessUser::firstOrCreate(
                ['user_id' => $user->id, 'business_id' => $adminBusiness->id],
                ['role_id' => $role->id]
            );
        }

        // 2. Create 5 users with different businesses and type 'SUPPLIER'
        for ($i = 1; $i <= 5; $i++) {
            $supplierOwner = User::firstOrCreate(
                ['email' => "supplier_$i@example.com"],
                [
                    'name' => $faker->name(),
                    'phone' => $faker->phoneNumber(),
                    'email_verified_at' => now(),
                    'password' => bcrypt('password'),
                ]
            );
            $supplierOwner->assignRole($supplierRole);

            $supplierBusiness = Business::firstOrCreate(
                ['code' => "SUPPLIER00$i"],
                [
                    'name' => "Supplier Business $i",
                    'short_name' => "Supplier$i",
                    'owner_id' => $supplierOwner->id,
                    'type' => 'SUPPLIER',
                    'address' => $faker->address,
                    'contact_person' => $faker->name(),
                    'contact_phone' => $faker->phoneNumber,
                    'contact_email' => $faker->email,
                    'status' => 'PENDING_VERIFICATION',
                ]
            );

            BusinessUser::firstOrCreate(
                ['user_id' => $supplierOwner->id, 'business_id' => $supplierBusiness->id],
                ['role_id' => $supplierRole->id]
            );
        }

        // 3. Create 5 users with different businesses and type 'VENDOR'
        for ($i = 1; $i <= 5; $i++) {
            $vendorOwner = User::firstOrCreate(
                ['email' => "vendor_$i@example.com"],
                [
                    'name' => $faker->name(),
                    'phone' => $faker->phoneNumber(),
                    'email_verified_at' => now(),
                    'password' => bcrypt('password'),
                ]
            );
            $supplierOwner->assignRole($vendorRole);

            $vendorBusiness = Business::firstOrCreate(
                ['code' => "VENDOR00$i"],
                [
                    'name' => "Vendor Business $i",
                    'short_name' => "Vendor$i",
                    'owner_id' => $vendorOwner->id,
                    'type' => 'VENDOR',
                    'address' => $faker->address,
                    'contact_person' => $faker->name(),
                    'contact_phone' => $faker->phoneNumber,
                    'contact_email' => $faker->email,
                    'status' => 'PENDING_VERIFICATION',
                ]
            );

            BusinessUser::firstOrCreate(
                ['user_id' => $vendorOwner->id, 'business_id' => $vendorBusiness->id],
                ['role_id' => $vendorRole->id]
            );

            // Creating 5 customers for each vendor business
            for ($j = 1; $j <= 5; $j++) {
                $identifier = 'CUS-'.$vendorBusiness->code.'-'.now()->format('Ymd').'-'.($j + $vendorBusiness->id * 100);

                CreditCustomer::firstOrCreate(
                    [
                        'identifier' => $identifier,
                        'business_id' => $vendorBusiness->id,
                    ],
                    [
                        'name' => $faker->name(),
                        'email' => $faker->email(),
                        'phone' => $faker->phoneNumber(),
                        'active' => true,
                    ]
                );
            }
        }

        // 4. Create 5 users with different businesses and type 'CUSTOMER_PHARMACY'
        for ($i = 1; $i <= 5; $i++) {
            $customerPharm = User::firstOrCreate(
                ['email' => "customer_pharmacy_$i@example.com"],
                [
                    'name' => $faker->name(),
                    'phone' => $faker->phoneNumber(),
                    'email_verified_at' => now(),
                    'password' => bcrypt('password'),
                ]
            );
            $customerPharm->assignRole($customerRole);

            $customerPharmBusiness = Business::firstOrCreate(
                ['code' => "CUSTOMER_PHARM00$i"],
                [
                    'name' => "Pharmacy Business $i",
                    'short_name' => "Pharmacy$i",
                    'owner_id' => $customerPharm->id,
                    'type' => 'CUSTOMER_PHARMACY',
                    'address' => $faker->address,
                    'contact_person' => $faker->name(),
                    'contact_phone' => $faker->phoneNumber,
                    'contact_email' => $faker->email,
                    'status' => 'PENDING_VERIFICATION',
                ]
            );

            BusinessUser::firstOrCreate(
                ['user_id' => $customerPharm->id, 'business_id' => $customerPharmBusiness->id],
                ['role_id' => $customerRole->id]
            );
        }

        // 5. Create 5 users with different businesses and type 'CUSTOMER_HOSPITAL'
        for ($i = 1; $i <= 5; $i++) {
            $customerHost = User::firstOrCreate(
                ['email' => "customer_hospital_$i@example.com"],
                [
                    'name' => $faker->name(),
                    'phone' => $faker->phoneNumber(),
                    'email_verified_at' => now(),
                    'password' => bcrypt('password'),
                ]
            );
            $customerHost->assignRole($customerRole);

            $customerHostBusiness = Business::firstOrCreate(
                ['code' => "CUSTOMER_HOSP00$i"],
                [
                    'name' => "Hospital Business $i",
                    'short_name' => "Hospital$i",
                    'owner_id' => $customerHost->id,
                    'type' => 'CUSTOMER_HOSPITAL',
                    'address' => $faker->address,
                    'contact_person' => $faker->name(),
                    'contact_phone' => $faker->phoneNumber,
                    'contact_email' => $faker->email,
                    'status' => 'PENDING_VERIFICATION',
                ]
            );

            BusinessUser::firstOrCreate(
                ['user_id' => $customerHost->id, 'business_id' => $customerHostBusiness->id],
                ['role_id' => $customerRole->id]
            );
        }

    }
}
