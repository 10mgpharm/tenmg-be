<?php

namespace App\Repositories;

use App\Models\Customer;
use App\Repositories\Interfaces\CustomerRepositoryInterface;

class CustomerRepository implements CustomerRepositoryInterface
{
    public function create(array $data): Customer
    {
        return Customer::create($data);
    }

    public function findById(int $id): ?Customer
    {
        return Customer::find($id);
    }

    public function update(Customer $customer, array $data): bool
    {
        return $customer->update($data);
    }

    public function delete(Customer $customer): bool
    {
        return $customer->delete();
    }

    public function paginate(array $filters, int $perPage = 15): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        $query = Customer::query();

        if (isset($filters['name'])) {
            $query->where('name', 'like', '%'.$filters['name'].'%');
        }

        if (isset($filters['email'])) {
            $query->where('email', 'like', '%'.$filters['email'].'%');
        }

        if (isset($filters['vendor_id'])) {
            $query->where('business_id', $filters['vendor_id']);
        }

        if (isset($filters['created_at_start']) && isset($filters['created_at_end'])) {
            $query->whereBetween('created_at', [$filters['created_at_start'], $filters['created_at_end']]);
        }

        return $query->paginate($perPage);
    }
}
