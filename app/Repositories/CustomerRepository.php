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

        $query->when(isset($filters['search']), function ($query, $search) {
            return $query
                ->where('name', 'like', "%{$search}%")
                ->orWhere('identifier', 'like', "%{$search}%")
                ->orWhere('email', 'like', "%{$search}%")
                ->orWhere('phone', 'like', "%{$search}%");
        });

        $query->when(isset($filters['status']), function ($query, $statusFilter) {
            return $query->where('active', $statusFilter === 'active' ? 1 : 0);
        });

        $query->when(isset($filters['vendorId']), function ($query, $vendorId) {
            return $query->where('business_id', $vendorId);
        });

        $query->when(isset($filters['createdAtStart']) && isset($filters['createdAtEnd']), function ($query) use ($filters) {
            return $query->whereBetween('created_at', [$filters['createdAtStart'], $filters['createdAtEnd']]);
        });

        return $query->paginate($perPage);
    }
}
