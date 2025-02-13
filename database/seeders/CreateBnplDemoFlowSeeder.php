<?php

namespace Database\Seeders;

use App\Enums\BusinessStatus;
use App\Enums\BusinessType;
use App\Helpers\UtilityHelper;
use App\Models\Business;
use App\Models\BusinessUser;
use App\Models\User;
use App\Repositories\ApiKeyRepository;
use App\Services\AttachmentService;
use App\Services\Interfaces\IAuthService;
use App\Services\Interfaces\ICustomerService;
use Illuminate\Database\Seeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

class CreateBnplDemoFlowSeeder extends Seeder
{
    /**
     * signup user contructor
     */
    public function __construct(
        private IAuthService $authService,
        private ICustomerService $customerService,
        private AttachmentService $attachmentService,
        private ApiKeyRepository $apiKeyRepository,
    ) {
        $this->authService = $authService;
        $this->customerService = $customerService;
        $this->attachmentService = $attachmentService;
        $this->apiKeyRepository = $apiKeyRepository;
    }

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('Creating Demo Records ........');

        // create demo lender
        $lender = $this->createDemoUser(BusinessType::LENDER, 'Kachi Solomon', 'lender@demo.com', 'Demo Lender');
        //TODO: update lender business info

        // create demo vendor
        $vendor = $this->createDemoUser(BusinessType::VENDOR, 'Danjuma Kehinde', 'vendor@demo.com', 'Demo Vendor');

        $this->apiKeyRepository->updateVendorKey($vendor, [
            'key' => 'pk_live_DEMO_REMOVED_PLACEHOLDER',
            'secret' => 'sk_live_DEMO_REMOVED_PLACEHOLDER',
            'test_key' => 'pk_test_DEMO_REMOVED_PLACEHOLDER',
            'test_secret' => 'sk_test_DEMO_REMOVED_PLACEHOLDER',
        ]);

        // create vendor customers with transaction history
        $filePath = 'mock/txn_history_sample.csv';
        $file = null; //attachmentService
        if (Storage::disk('local')->get($filePath)) {
            $file = new UploadedFile(
                path: Storage::disk('local')->path($filePath),
                originalName: basename($filePath),
                mimeType: 'text/csv',
                error: null,
                test: true
            );
        }
        $customer1 = $this->customerService->getCustomerByEmail('customer1@demo.com');
        if (! $customer1) {
            $customer1 = $this->customerService->createCustomer(
                data: [
                    'name' => 'Daniel Aminat - Verified',
                    'email' => 'customer1@demo.com',
                    'phone' => '08093570288',
                    'reference' => UtilityHelper::generateSlug('CUS'),
                ],
                file: $file,
                mocked: $vendor
            );
        }

        // create vendor customers
        $customer2 = $this->customerService->getCustomerByEmail('customer2@demo.com');
        if (! $customer2) {
            $customer2 = $this->customerService->createCustomer(
                data: [
                    'name' => 'Saadat Adeola - UnVerified',
                    'email' => 'customer2@demo.com',
                    'phone' => '08093570280',
                    'reference' => UtilityHelper::generateSlug('CUS'),
                ],
                file: null,
                mocked: $vendor
            );
        }

        // create demo customer credit score
        $this->command->info('Demo Records created successfully');
    }

    private function createDemoUser(BusinessType $businessType, string $name, string $email, string $businessName): Business
    {
        // create user
        $user = User::firstOrCreate(
            [
                'email' => $email,
            ],
            [
                'name' => $name,
                'password' => Hash::make('password'),
            ]
        );
        $userRole = $this->authService->resolveSignupRole(type: $businessType);
        $user->assignRole($userRole);

        // create business
        $businessCode = UtilityHelper::generateBusinessCode($businessName);
        $business = Business::firstOrCreate([
            'name' => $businessName,
            'code' => $businessCode,
            'short_name' => $businessCode,
        ],
            [
                'owner_id' => $user->id,
                'type' => $businessType,
                'status' => BusinessStatus::VERIFIED->value,
            ]);

        // map user to business
        BusinessUser::firstOrCreate(
            [
                'user_id' => $user->id,
                'business_id' => $business->id,
            ],
            ['role_id' => $userRole->id]
        );

        // handle dependency signups step
        $this->authService->handleAccountSetup($business, $businessType);

        return $business;
    }
}
