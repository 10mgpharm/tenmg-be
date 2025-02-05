<?php

namespace Database\Seeders;

use App\Constants\RoleConstant;
use App\Models\User;
use Illuminate\Database\Seeder;

class RemapExistingUserRoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Map business types to their corresponding roles
        $roleMap = [
            'VENDOR' => RoleConstant::VENDOR,
            'SUPPLIER' => RoleConstant::SUPPLIER,
            'CUSTOMER_PHARMACY' => RoleConstant::CUSTOMER,
            'LENDER' => RoleConstant::LENDER,
            'ADMIN' => RoleConstant::ADMIN,
        ];

        foreach ($roleMap as $businessType => $role) {
            // Get users mapped to the business type
            $users = User::whereIn('id', function ($query) use ($businessType) {
                $query->select('user_id')
                    ->from('business_users')
                    ->join('businesses', 'business_users.business_id', '=', 'businesses.id')
                    ->where('businesses.type', $businessType);
            })->get();

            // Assign roles to users
            foreach ($users as $user) {
                $user->assignRole($role);
            }
        }
    }
}
