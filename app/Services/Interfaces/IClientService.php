<?php

namespace App\Services\Interfaces;

use App\Models\Business;

interface IClientService
{
    public function getDemoCustomers(Business $business): ?array;

    public function verifyPublicKey(string $publicKey): ?Business;

    public function verifySecretKey(string $publicKey): ?Business;
}
