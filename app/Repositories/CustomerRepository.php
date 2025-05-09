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
            'reference' => array_key_exists('reference', $data) ? $data['reference'] : null,
            'active' => true,
        ]);
    }

    public function findById(int $id): ?Customer
    {
        return Customer::whereId($id)->with('lastEvaluationHistory.creditScore')->first();
    }

    public function findWhere(array $data): ?Customer
    {
        return Customer::where($data)->with('lastEvaluationHistory.creditScore')->first();
    }

    public function update(Customer $customer, array $data): bool
    {
        $payload = [];
        isset($data['vendorId']) && $payload['business_id'] = $data['vendorId'];
        isset($data['name']) && $payload['name'] = $data['name'];
        isset($data['email']) && $payload['email'] = $data['email'];
        isset($data['phone']) && $payload['phone'] = $data['phone'];
        isset($data['identifier']) && $payload['identifier'] = $data['identifier'];
        isset($data['reference']) && $payload['reference'] = $data['reference'];
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

        $query->when(isset($filters['createdAtStart']) && isset($filters['createdAtEnd']), function ($query) use ($filters) {
            $createdAtStart = \Carbon\Carbon::parse($filters['createdAtStart'])->startOfDay();
            $createdAtEnd = \Carbon\Carbon::parse($filters['createdAtEnd'])->endOfDay();
            return $query->whereBetween('created_at', [$createdAtStart, $createdAtEnd]);
        });

        $query->when(isset($filters['vendorId']), function ($query) use ($filters) {
            return $query->where('business_id', $filters['vendorId']);
        });

        $query->orderBy('created_at', 'desc');

        return $query->paginate($perPage);
    }

    public function getAllCustomers($vendorId): array
    {
        $customers = Customer::where('business_id', $vendorId)->get()->toArray();

        return $customers;
    }
}
