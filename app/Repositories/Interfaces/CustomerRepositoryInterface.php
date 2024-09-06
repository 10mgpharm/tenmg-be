<?php

namespace App\Repositories\Interfaces;

use App\Models\Customer;

interface CustomerRepositoryInterface
{
    public function create(array $data): Customer;

    public function findById(int $id): ?Customer;

    public function update(Customer $customer, array $data): bool;

    public function delete(Customer $customer): bool;

    public function paginate(array $filters, int $perPage = 15): \Illuminate\Contracts\Pagination\LengthAwarePaginator;
}
