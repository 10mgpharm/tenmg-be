<?php

namespace App\Repositories;

use App\Models\Customer;
use App\Repositories\Interfaces\ICustomerRepository;

class CustomerRepository implements ICustomerRepository
{
    public function create(array $data): Customer
    {
        return Customer::create([
            'business_id' => $data['vendorId'],
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'],
            'identifier' => $data['identifier'],
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

        $query->when(isset($filters['name']), function ($query) use ($filters) {
            return $query->where('name', 'like', '%'.$filters['name'].'%');
        });

        $query->when(isset($filters['email']), function ($query) use ($filters) {
            return $query->where('email', 'like', '%'.$filters['email'].'%');
        });

        $query->when(isset($filters['vendorId']), function ($query) use ($filters) {
            return $query->where('business_id', $filters['vendorId']);
        });

        $query->when(isset($filters['createdAtStart']) && isset($filters['createdAtEnd']), function ($query) use ($filters) {
            return $query->whereBetween('created_at', [$filters['createdAtStart'], $filters['createdAtEnd']]);
        });

        return $query->paginate($perPage);
    }
}
