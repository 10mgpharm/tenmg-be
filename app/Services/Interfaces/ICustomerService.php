<?php

namespace App\Services\Interfaces;

use App\Models\Business;
use App\Models\Customer;
use File;
use Illuminate\Http\UploadedFile;

interface ICustomerService
{
    public function createCustomer(array $data, File|UploadedFile|string|null $file = null, ?Business $mocked = null): Customer;

    public function getCustomerById(int $id): ?Customer;

    public function getCustomerByEmail(string $email): ?Customer;

    public function updateCustomer(int $id, array $data): ?Customer;

    public function deleteCustomer(int $id): bool;

    public function listCustomers(array $filters, int $perPage): \Illuminate\Contracts\Pagination\LengthAwarePaginator;

    public function toggleCustomerActiveStatus(int $id): ?Customer;

    public function getAllCustomers(): ?array;

    public function checkIfVendor(): bool;
}
