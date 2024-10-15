<?php

namespace App\Services\Admin;

use App\Enums\BusinessStatus;
use App\Enums\BusinessType;
use App\Enums\MailType;
use App\Helpers\UtilityHelper;
use App\Mail\Mailer;
use App\Models\Business;
use App\Models\BusinessUser;
use App\Models\Role;
use App\Models\User;
use App\Services\Interfaces\IUserService;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

use Illuminate\Support\Str;


class UserService implements IUserService
{

    public function store(array $validated): ?User
    {
        try {
            // Start a database transaction
            return DB::transaction(function () use ($validated) {

                $businessType = BusinessType::from(strtoupper($validated['business_type']));
                $password = Str::random(15);

                // create user
                $user = User::create([
                    'name' => $validated['business_name'],
                    'email' => $validated['email'],
                    'password' => Hash::make($password),
                ]);

                $userRole = $this->resolveRole(type: $businessType);
                $user->assignRole($userRole);

                // create business
                $businessCode = UtilityHelper::generateBusinessCode($validated['business_name']);
                $adminBusiness = Business::create([
                    'name' => $validated['business_name'],
                    'code' => $businessCode,
                    'short_name' => $businessCode,
                    'owner_id' => $user->id,
                    'type' => $businessType,
                    'status' => BusinessStatus::PENDING_VERIFICATION->value,
                ]);

                // map user to business
                BusinessUser::firstOrCreate(
                    [
                        'user_id' => $user->id,
                        'business_id' => $adminBusiness->id,
                    ],
                    ['role_id' => $userRole->id]
                );


                if ($user) {
                    $this->sendMail($user, password: $password);
                    return $user;
                }
                return null; // Return null on failure
            });
        } catch (Exception $e) {
            throw new Exception('Failed to create invite: ' . $e->getMessage());
        }
    }

    /**
     * Send an email invitation to the user after they are successfully created.
     * The invitation email includes relevant information for the user.
     *
     * @param User $user The newly created user to whom the email will be sent.
     * @return void
     */
    protected function sendMail(User $user, string $password = null)
    {

        $data = [
            'user' => $user,
            'password' =>  $password
        ];
        // Use appropriate email method to send out (Mailer)
        Mail::to($user->email)->send(new Mailer(MailType::ADMIN_CREATE_USER, $data));
    }

    public function resolveRole(BusinessType $type): ?Role
    {
        switch ($type) {
            case BusinessType::SUPPLIER:
                return Role::where('name', 'supplier')->first();

            case BusinessType::VENDOR:
                return Role::where('name', 'vendor')->first();

            default:
                return Role::where('name', 'vendor')->first();
        }
    }
}
