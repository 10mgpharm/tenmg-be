<?php

namespace App\Services\Interfaces;

use App\Models\Customer;

interface CustomerServiceInterface
{
    public function createCustomer(array $data): Customer;

    public function getCustomerById(int $id): ?Customer;

    public function updateCustomer(int $id, array $data): ?Customer;

    public function deleteCustomer(int $id): bool;

    public function listCustomers(array $filters, int $perPage): \Illuminate\Contracts\Pagination\LengthAwarePaginator;

    public function toggleCustomerActiveStatus(int $id): ?Customer;
}
