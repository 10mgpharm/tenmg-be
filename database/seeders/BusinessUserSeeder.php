<?php

namespace Database\Seeders;

use App\Models\ApiKey;
use App\Models\Business;
use App\Models\BusinessUser;
use App\Models\Customer;
use App\Models\LenderSetting;
use App\Models\Role;
use App\Models\User;
use Faker\Factory as Faker;
use Illuminate\Database\QueryException;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class BusinessUserSeeder extends Seeder
{
    /**
     * Safely get or create a user, skipping if already exists
     */
    private function safeGetOrCreateUser(string $email, array $attributes): ?User
    {
        try {
            return User::firstOrCreate(['email' => $email], $attributes);
        } catch (QueryException $e) {
            // If user already exists, just return it
            if ($e->getCode() === '23000' || str_contains($e->getMessage(), 'Duplicate entry')) {
                return User::where('email', $email)->first();
            }
            throw $e;
        }
    }

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
        $lenderRole = Role::where('name', 'lender')->first();

        // 1. Setup Admin Business and Users
        $adminUser = $this->safeGetOrCreateUser('admin@example.com', [
            'name' => $faker->name(),
            'phone' => $faker->phoneNumber(),
            'email_verified_at' => now(),
            'password' => bcrypt('password'),
        ]);
        $adminUser->assignRole($adminRole);

        // Use short_name as unique identifier since it has a unique constraint
        $adminBusiness = Business::firstOrCreate(
            [
                'short_name' => 'tenmg',
            ],
            [
                'code' => '10MG',
                'name' => '10MG Health',
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
            $user = $this->safeGetOrCreateUser("admin_user_$i@example.com", [
                'name' => $faker->name(),
                'phone' => $faker->phoneNumber(),
                'email_verified_at' => now(),
                'password' => bcrypt('password'),
            ]);

            if (! $user) {
                continue; // Skip if user creation failed
            }

            $role = ($i <= 2) ? $supportRole :
                    (($i <= 4) ? $operationRole : $adminRole);

            $user->assignRole($role);

            BusinessUser::firstOrCreate(
                ['user_id' => $user->id, 'business_id' => $adminBusiness->id],
                ['role_id' => $role->id]
            );
        }

        // 2. Create 5 users with different businesses and type 'SUPPLIER'
        for ($i = 1; $i <= 5; $i++) {
            $supplierOwner = $this->safeGetOrCreateUser("supplier_$i@example.com", [
                'name' => $faker->name(),
                'phone' => $faker->phoneNumber(),
                'email_verified_at' => now(),
                'password' => bcrypt('password'),
            ]);

            if (! $supplierOwner) {
                continue; // Skip if user creation failed
            }
            $supplierOwner->assignRole($supplierRole);

            // Use short_name as unique identifier since it has a unique constraint
            $supplierBusiness = Business::firstOrCreate(
                ['short_name' => "Supplier$i"],
                [
                    'code' => "SUPPLIER00$i",
                    'name' => "Supplier Business $i",
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
            $vendorOwner = $this->safeGetOrCreateUser("vendor_$i@example.com", [
                'name' => $faker->name(),
                'phone' => $faker->phoneNumber(),
                'email_verified_at' => now(),
                'password' => bcrypt('password'),
            ]);

            if (! $vendorOwner) {
                continue; // Skip if user creation failed
            }
            $vendorOwner->assignRole($vendorRole);

            // Use short_name as unique identifier since it has a unique constraint
            $vendorBusiness = Business::firstOrCreate(
                ['short_name' => "Vendor$i"],
                [
                    'code' => "VENDOR00$i",
                    'name' => "Vendor Business $i",
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

            ApiKey::firstOrCreate(
                ['business_id' => $vendorBusiness->id],
                [
                    'key' => time().Str::random(5),
                    'secret' => time().Str::random(8),
                ]
            );

            // Creating 5 customers for each vendor business
            for ($j = 1; $j <= 5; $j++) {
                $identifier = 'CUS-'.$vendorBusiness->code.'-'.now()->format('Ymd').'-'.($j + $vendorBusiness->id * 100);

                Customer::firstOrCreate(
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
            $customerPharm = $this->safeGetOrCreateUser("customer_pharmacy_$i@example.com", [
                'name' => $faker->name(),
                'phone' => $faker->phoneNumber(),
                'email_verified_at' => now(),
                'password' => bcrypt('password'),
            ]);

            if (! $customerPharm) {
                continue; // Skip if user creation failed
            }
            $customerPharm->assignRole($customerRole);

            // Use short_name as unique identifier since it has a unique constraint
            $customerPharmBusiness = Business::firstOrCreate(
                ['short_name' => "Pharmacy$i"],
                [
                    'code' => "CUSTOMER_PHARM00$i",
                    'name' => "Pharmacy Business $i",
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
            $customerHost = $this->safeGetOrCreateUser("customer_hospital_$i@example.com", [
                'name' => $faker->name(),
                'phone' => $faker->phoneNumber(),
                'email_verified_at' => now(),
                'password' => bcrypt('password'),
            ]);

            if (! $customerHost) {
                continue; // Skip if user creation failed
            }
            $customerHost->assignRole($customerRole);

            // Use short_name as unique identifier since it has a unique constraint
            $customerHostBusiness = Business::firstOrCreate(
                ['short_name' => "Hospital$i"],
                [
                    'code' => "CUSTOMER_HOSP00$i",
                    'name' => "Hospital Business $i",
                    'owner_id' => $customerHost->id,
                    'type' => 'CUSTOMER_PHARMACY',
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

        // 6. Create 5 users with different businesses and type 'LENDER'
        $lenderRates = [5.0, 6.5, 7.0, 8.0, 8.5]; // Different rates between 3-9%
        $lenderInstructions = [
            // Lender 1: Focus on category and past performance
            'Approve loans only for customers in credit category B or above who have no previous defaulted or written-off loans.',
            // Lender 2: Strict on previous loans regardless of category
            'Do not approve any loan for customers with a history of defaulted, written-off, or restructured loans, even if they are in category A.',
            // Lender 3: Conservative towards first-time borrowers
            'Even if a customer is in credit category A, decline if this is their first-ever loan and there is no previous repayment history.',
            // Lender 4: Require proven repayment history
            'Only approve where the customer has at least one fully repaid loan in the last 12 months with a performing status throughout.',
            // Lender 5: Active loan and category-based rules
            'If the customer currently has an active loan, only approve a new one when they are in category B or above and the existing loan has been performing for at least 6 months.',
        ];

        for ($i = 1; $i <= 5; $i++) {
            $lenderHost = $this->safeGetOrCreateUser("lender_LEN$i@example.com", [
                'name' => $faker->name(),
                'phone' => $faker->phoneNumber(),
                'email_verified_at' => now(),
                'password' => bcrypt('password'),
            ]);

            if (! $lenderHost) {
                continue; // Skip if user creation failed
            }
            $lenderHost->assignRole($lenderRole);

            // Use short_name as unique identifier since it has a unique constraint
            $lenderHostBusiness = Business::firstOrCreate(
                ['short_name' => "lender$i"],
                [
                    'code' => "LENDER_LEND00$i",
                    'name' => "Lender Business $i",
                    'owner_id' => $lenderHost->id,
                    'type' => 'LENDER',
                    'address' => $faker->address,
                    'contact_person' => $faker->name(),
                    'contact_phone' => $faker->phoneNumber,
                    'contact_email' => $faker->email,
                    'status' => 'PENDING_VERIFICATION',
                ]
            );

            BusinessUser::firstOrCreate(
                ['user_id' => $lenderHost->id, 'business_id' => $lenderHostBusiness->id],
                ['role_id' => $lenderRole->id]
            );

            // Create lender settings for each lender
            LenderSetting::firstOrCreate(
                ['business_id' => $lenderHostBusiness->id],
                [
                    'rate' => $lenderRates[$i - 1],
                    'instruction' => $lenderInstructions[$i - 1],
                    'instruction_config' => [
                        'active' => true, // Lender is active and open to new loans
                        'supported_tenors' => [1, 2, 3, 4], // Support all system tenors (1-4 months)
                        'min_amount' => 20000, // Minimum loan amount (NGN 20,000)
                        'max_amount' => null, // No maximum (unlimited)
                        'disbursement_channel' => 'bank_transfer',
                        'require_manual_approval' => $i % 2 === 0, // Alternate between true/false
                        'verification_required' => true,
                    ],
                ]
            );
        }

    }
}
