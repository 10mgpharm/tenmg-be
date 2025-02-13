<?php

namespace App\Services;

use App\Models\Business;
use App\Repositories\ApiKeyRepository;
use App\Repositories\CustomerRepository;
use App\Services\Interfaces\IClientService;

class ClientService implements IClientService
{
    public function __construct(
        public CustomerRepository $customerRepository,
        public ApiKeyRepository $apiKeyRepository,
    ) {}

    public function getDemoCustomers(Business $business): ?array
    {
        return $this->customerRepository->getAllCustomers($business->id);
    }

    public function verifyPublicKey(string $publicKey): ?Business
    {
        return $this->apiKeyRepository->verifyPublicKeyExist($publicKey);
    }

    public function verifySecretKey(string $publicKey): ?Business
    {
        return $this->apiKeyRepository->verifySecretKeyExist($publicKey);
    }
}
