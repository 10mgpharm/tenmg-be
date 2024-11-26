<?php

namespace App\Repositories;

use App\Models\Customer;

class CustomerRepository
{
    public function create(array $data): Customer
    {
        return Customer::create([
            'business_id' => $data['vendorId'],
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'],
            'active' => $data['active'] ?? true,
        ]);
    }

    public function findById(int $id): ?Customer
    {
        return Customer::whereId($id)->with('lastEvaluationHistory.creditScore')->first();
    }

    public function update(Customer $customer, array $data): bool
    {
        $payload = [];
        isset($data['vendorId']) && $payload['business_id'] = $data['vendorId'];
        isset($data['name']) && $payload['name'] = $data['name'];
        isset($data['email']) && $payload['email'] = $data['email'];
        isset($data['phone']) && $payload['phone'] = $data['phone'];
        isset($data['identifier']) && $payload['identifier'] = $data['identifier'];
        isset($data['active']) && $payload['active'] = $data['active'];

        return $customer->update($payload);
    }

    public function delete(Customer $customer): bool
    {
        return $customer->delete();
    }

    public function paginate(array $filters, int $perPage = 15): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        $query = Customer::query();

        $query->when(isset($filters['search']), function ($query) use ($filters) {
            return $query
                ->where('name', 'like', "%{$filters['search']}%")
                ->orWhere('identifier', 'like', "%{$filters['search']}%")
                ->orWhere('email', 'like', "%{$filters['search']}%")
                ->orWhere('phone', 'like', "%{$filters['search']}%");
        });

        $query->when(isset($filters['status']), function ($query) use ($filters) {
            return $query->where('active', $filters['status'] === 'active' ? 1 : 0);
        });

        $query->when(isset($filters['vendorId']), function ($query) use ($filters) {
            return $query->where('business_id', $filters['vendorId']);
        });

        $query->when(isset($filters['createdAtStart']) && isset($filters['createdAtEnd']), function ($query) use ($filters) {
            return $query->whereBetween('created_at', [$filters['createdAtStart'], $filters['createdAtEnd']]);
        });

        $query->when(isset($filters['vendorId']), function ($query) use ($filters) {
            return $query->where('business_id', $filters['vendorId']);
        });

        return $query->paginate($perPage);
    }

    function getAllCustomers():array
    {
        $customers = Customer::all()->toArray();
        return $customers;
    }
}
