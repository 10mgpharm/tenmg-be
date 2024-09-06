<?php

namespace Database\Seeders;

use App\Constants\PermissionConstants;
use App\Constants\RoleConstant;
use App\Models\Application;
use App\Models\Permission;
use App\Models\PermissionGroup;
use App\Models\Role;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\PermissionRegistrar;

class RolePermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // delete and reset current permissions and its dependencies
        $this->deleteExistingPermissions();

        // clear spatie cache
        Artisan::call('cache:forget spatie.permission.cache');

        // reset cached roles and permissions
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // Applications
        $applications = [
            'Global Access Control',
            'Ecommerce Access Control',
            'Credit Access Control',
        ];

        $applicationRecords = [];
        foreach ($applications as $applicationName) {
            $applicationRecords[$applicationName] = Application::firstOrCreate(
                ['name' => $applicationName],
                ['description' => $applicationName]
            );
        }

        // Pages
        $pages = [
            'Global Access Control' => [
                'User Management',
                'Miscellaneous',
            ],
            'Ecommerce Access Control' => [
                'Application Setup',
                'Product Management',
                'Order Management',
            ],
            'Credit Access Control' => [
                'Customer Management',
                'Credit Management',
            ],
        ];

        // Permissions
        $permissions = [
            'User Management' => [
                PermissionConstants::VIEW_ROLES,
                PermissionConstants::CREATE_ROLES,
                PermissionConstants::UPDATE_ROLES,
                PermissionConstants::DELETE_ROLES,
                PermissionConstants::VIEW_ADMIN_USERS,
                PermissionConstants::CREATE_ADMIN_USER,
                PermissionConstants::UPDATE_ADMIN_USER,
            ],
            'Miscellaneous' => [
                PermissionConstants::VENDOR_DASHBOARD,
                PermissionConstants::SUPPLIER_DASHBOARD,
                PermissionConstants::ADMIN_DASHBOARD,
                PermissionConstants::STOREFRONT,
                PermissionConstants::VIEW_MY_AUDIT_LOG,
                PermissionConstants::VIEW_SYSTEM_LOG,
                PermissionConstants::VIEW_MESSAGES,
                PermissionConstants::SEND_MESSAGES,
            ],
            'Application Setup' => [
                PermissionConstants::VIEW_APP_SETUP,
                PermissionConstants::CREATE_APP_SETUP,
                PermissionConstants::UPDATE_APP_SETUP,
                PermissionConstants::DELETE_APP_SETUP,
                PermissionConstants::PUBLISH_APP_SETUP,
            ],
            'Product Management' => [
                PermissionConstants::VIEW_PRODUCT_LIST,
                PermissionConstants::CREATE_PRODUCT,
                PermissionConstants::UPDATE_PRODUCT,
                PermissionConstants::DELETE_PRODUCT,
                PermissionConstants::PRODUCT_INSIGHTS,
            ],
            'Order Management' => [
                PermissionConstants::VIEW_ORDERS,
                PermissionConstants::CANCEL_ORDER,
            ],
            'Customer Management' => [
                PermissionConstants::VIEW_CUSTOMER_LIST,
                PermissionConstants::CREATE_CUSTOMER,
                PermissionConstants::UPDATE_CUSTOMER,
                PermissionConstants::DELETE_CUSTOMER,
            ],
            'Credit Management' => [
                PermissionConstants::VIEW_APPLICATIONS_LIST,
                PermissionConstants::CREATE_APPLICATIONS,
                PermissionConstants::UPDATE_APPLICATIONS,
                PermissionConstants::DELETE_APPLICATIONS,
                PermissionConstants::VIEW_TRANSACTION_HISTORY_LIST,
                PermissionConstants::UPLOAD_TRANSACTION_HISTORY,
                PermissionConstants::RUN_CREDIT_SCORE,
                PermissionConstants::VIEW_OFFER_LIST,
                PermissionConstants::CREATE_OFFER,
                PermissionConstants::UPDATE_OFFER,
                PermissionConstants::DELETE_OFFER,
                PermissionConstants::VIEW_LOAN_LIST,
                PermissionConstants::POST_CASH_REPAYMENT,
                PermissionConstants::CLOSE_LOAN,
                PermissionConstants::VIEW_REPAYMENTS,
            ],
        ];

        // Create Permission Groups and Permissions
        foreach ($pages as $applicationName => $groups) {
            $application = $applicationRecords[$applicationName];

            foreach ($groups as $groupName) {
                $permissionGroup = PermissionGroup::firstOrCreate([
                    'name' => $groupName,
                    'description' => $groupName,
                    'application_id' => $application->id,
                ]);

                foreach ($permissions[$groupName] as $permissionName) {
                    Permission::firstOrCreate([
                        'name' => $permissionName,
                        'alias' => "$groupName: $permissionName",
                        'guard_name' => 'api',
                        'application_id' => $application->id,
                        'permission_group_id' => $permissionGroup->id,
                    ]);
                }
            }
        }

        // define default roles
        $administrator = RoleConstant::ADMIN;
        $operation = RoleConstant::OPERATION;
        $support = RoleConstant::SUPPORT;
        $supplier = RoleConstant::SUPPLIER;
        $customer = RoleConstant::CUSTOMER;
        $vendor = RoleConstant::VENDOR;

        $roles = [$administrator, $operation, $support, $supplier, $customer, $vendor];

        // define default permissions for each role
        $rolePermissionList = [

            $administrator => [
                // Miscellaneous
                PermissionConstants::ADMIN_DASHBOARD,

                PermissionConstants::VIEW_MY_AUDIT_LOG,
                PermissionConstants::VIEW_SYSTEM_LOG,

                PermissionConstants::VIEW_MESSAGES,
                PermissionConstants::SEND_MESSAGES,

                PermissionConstants::VIEW_ROLES,
                PermissionConstants::CREATE_ROLES,
                PermissionConstants::UPDATE_ROLES,
                PermissionConstants::DELETE_ROLES,
                PermissionConstants::VIEW_ADMIN_USERS,
                PermissionConstants::CREATE_ADMIN_USER,
                PermissionConstants::UPDATE_ADMIN_USER,

                // E-Commerce
                PermissionConstants::VIEW_APP_SETUP,
                PermissionConstants::CREATE_APP_SETUP,
                PermissionConstants::UPDATE_APP_SETUP,
                PermissionConstants::DELETE_APP_SETUP,
                PermissionConstants::PUBLISH_APP_SETUP,

                PermissionConstants::VIEW_PRODUCT_LIST,
                PermissionConstants::CREATE_PRODUCT,
                PermissionConstants::UPDATE_PRODUCT,
                PermissionConstants::DELETE_PRODUCT,
                PermissionConstants::PRODUCT_INSIGHTS,

                PermissionConstants::VIEW_ORDERS,
                PermissionConstants::CANCEL_ORDER,

                // Credit Application
                PermissionConstants::VIEW_CUSTOMER_LIST,
                PermissionConstants::CREATE_CUSTOMER,
                PermissionConstants::UPDATE_CUSTOMER,
                PermissionConstants::DELETE_CUSTOMER,

                PermissionConstants::VIEW_APPLICATIONS_LIST,
                PermissionConstants::CREATE_APPLICATIONS,
                PermissionConstants::UPDATE_APPLICATIONS,
                PermissionConstants::DELETE_APPLICATIONS,

                PermissionConstants::VIEW_TRANSACTION_HISTORY_LIST,
                PermissionConstants::UPLOAD_TRANSACTION_HISTORY,
                PermissionConstants::RUN_CREDIT_SCORE,

                PermissionConstants::VIEW_OFFER_LIST,
                PermissionConstants::CREATE_OFFER,
                PermissionConstants::UPDATE_OFFER,
                PermissionConstants::DELETE_OFFER,

                PermissionConstants::VIEW_LOAN_LIST,
                PermissionConstants::POST_CASH_REPAYMENT,
                PermissionConstants::CLOSE_LOAN,
                PermissionConstants::VIEW_REPAYMENTS,
            ],

            $operation => [
                // Miscellaneous
                PermissionConstants::ADMIN_DASHBOARD,

                PermissionConstants::VIEW_MY_AUDIT_LOG,
                PermissionConstants::VIEW_SYSTEM_LOG,

                PermissionConstants::VIEW_MESSAGES,
                PermissionConstants::SEND_MESSAGES,

                PermissionConstants::VIEW_ROLES,
                PermissionConstants::VIEW_ADMIN_USERS,

                // E-Commerce
                PermissionConstants::VIEW_APP_SETUP,
                PermissionConstants::UPDATE_APP_SETUP,
                PermissionConstants::PUBLISH_APP_SETUP,

                PermissionConstants::VIEW_PRODUCT_LIST,
                PermissionConstants::CREATE_PRODUCT,
                PermissionConstants::UPDATE_PRODUCT,
                PermissionConstants::PRODUCT_INSIGHTS,

                PermissionConstants::VIEW_ORDERS,
                PermissionConstants::CANCEL_ORDER,

                // Credit Application
                PermissionConstants::VIEW_CUSTOMER_LIST,
                PermissionConstants::CREATE_CUSTOMER,
                PermissionConstants::UPDATE_CUSTOMER,

                PermissionConstants::VIEW_APPLICATIONS_LIST,
                PermissionConstants::CREATE_APPLICATIONS,
                PermissionConstants::UPDATE_APPLICATIONS,

                PermissionConstants::VIEW_TRANSACTION_HISTORY_LIST,
                PermissionConstants::UPLOAD_TRANSACTION_HISTORY,
                PermissionConstants::RUN_CREDIT_SCORE,

                PermissionConstants::VIEW_OFFER_LIST,
                PermissionConstants::CREATE_OFFER,
                PermissionConstants::UPDATE_OFFER,

                PermissionConstants::VIEW_LOAN_LIST,
                PermissionConstants::POST_CASH_REPAYMENT,
                PermissionConstants::VIEW_REPAYMENTS,
            ],

            $support => [
                // Miscellaneous
                PermissionConstants::ADMIN_DASHBOARD,
                PermissionConstants::VIEW_MY_AUDIT_LOG,
                PermissionConstants::VIEW_SYSTEM_LOG,
                PermissionConstants::VIEW_MESSAGES,
                PermissionConstants::SEND_MESSAGES,

                // E-Commerce
                PermissionConstants::VIEW_APP_SETUP,
                PermissionConstants::VIEW_PRODUCT_LIST,
                PermissionConstants::PRODUCT_INSIGHTS,
                PermissionConstants::VIEW_ORDERS,
                PermissionConstants::CANCEL_ORDER,

                // Credit Application
                PermissionConstants::VIEW_CUSTOMER_LIST,
                PermissionConstants::VIEW_APPLICATIONS_LIST,
                PermissionConstants::VIEW_TRANSACTION_HISTORY_LIST,
                PermissionConstants::VIEW_OFFER_LIST,
                PermissionConstants::VIEW_LOAN_LIST,
                PermissionConstants::VIEW_REPAYMENTS,
            ],

            $supplier => [
                // Miscellaneous
                PermissionConstants::SUPPLIER_DASHBOARD,

                // E-Commerce
                PermissionConstants::VIEW_APP_SETUP,
                PermissionConstants::CREATE_APP_SETUP,
                PermissionConstants::UPDATE_APP_SETUP,
                PermissionConstants::DELETE_APP_SETUP,

                PermissionConstants::VIEW_PRODUCT_LIST,
                PermissionConstants::CREATE_PRODUCT,
                PermissionConstants::UPDATE_PRODUCT,
                PermissionConstants::PRODUCT_INSIGHTS,

                PermissionConstants::VIEW_ORDERS,
            ],

            $customer => [
                // Miscellaneous
                PermissionConstants::STOREFRONT,
                PermissionConstants::VIEW_ORDERS,
            ],

            $vendor => [
                // Miscellaneous
                PermissionConstants::VENDOR_DASHBOARD,

                // Credit Application
                PermissionConstants::VIEW_CUSTOMER_LIST,
                PermissionConstants::CREATE_CUSTOMER,
                PermissionConstants::UPDATE_CUSTOMER,
                PermissionConstants::DELETE_CUSTOMER,

                PermissionConstants::VIEW_APPLICATIONS_LIST,
                PermissionConstants::CREATE_APPLICATIONS,
                PermissionConstants::UPDATE_APPLICATIONS,
                PermissionConstants::DELETE_APPLICATIONS,

                PermissionConstants::VIEW_TRANSACTION_HISTORY_LIST,
                PermissionConstants::UPLOAD_TRANSACTION_HISTORY,
                PermissionConstants::RUN_CREDIT_SCORE,

                PermissionConstants::VIEW_OFFER_LIST,
                PermissionConstants::CREATE_OFFER,
                PermissionConstants::UPDATE_OFFER,
                PermissionConstants::DELETE_OFFER,

                PermissionConstants::VIEW_LOAN_LIST,
                PermissionConstants::POST_CASH_REPAYMENT,
                PermissionConstants::CLOSE_LOAN,
                PermissionConstants::VIEW_REPAYMENTS,
            ],
        ];

        // create all roles and assign their default permissions
        foreach ($roles as $roleName) {
            $role = Role::firstOrCreate([
                'name' => $roleName,
                'guard_name' => 'api',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $permissionList = $rolePermissionList[$role->name];
            $role->syncPermissions($permissionList);
        }
    }

    protected function deleteExistingPermissions()
    {
        $tables = [
            'model_has_permissions',
            'role_has_permissions',
            'model_has_roles',
            'permissions',
            'permission_groups',
        ];

        foreach ($tables as $table) {
            if (Schema::hasTable($table)) {
                DB::statement("DELETE FROM $table");
            }
        }
    }
}
