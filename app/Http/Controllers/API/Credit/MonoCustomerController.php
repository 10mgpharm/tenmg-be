<?php

namespace App\Http\Controllers\API\Credit;

use App\Http\Controllers\Controller;
use App\Services\Credit\MonoCustomerService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

class MonoCustomerController extends Controller
{
    public function __construct(
        private MonoCustomerService $monoCustomerService
    ) {}

    /**
     * Retrieve a Mono customer by ID
     * GET /api/v1/vendor/credit/mono-customers/{id}
     * https://docs.mono.co/api/customer/retrieve-a-customer
     */
    public function retrieve(string $id): JsonResponse
    {
        $result = $this->monoCustomerService->retrieveCustomer($id);

        if (! $result['success']) {
            return $this->returnJsonResponse(
                message: $result['error'] ?? 'Failed to retrieve Mono customer',
                data: $result,
                statusCode: $result['status_code'] ?? Response::HTTP_BAD_REQUEST,
                status: 'failed'
            );
        }

        return $this->returnJsonResponse(
            message: 'Mono customer retrieved successfully',
            data: $result['data'] ?? $result
        );
    }

    /**
     * List all Mono customers
     * GET /api/v1/vendor/credit/mono-customers
     * GET /api/v1/client/credit/mono-customers
     * https://docs.mono.co/api/customer/list-all-customers
     */
    public function list(): JsonResponse
    {
        $result = $this->monoCustomerService->listAllCustomers();

        if (! $result['success']) {
            return $this->returnJsonResponse(
                message: $result['error'] ?? 'Failed to list Mono customers',
                data: $result,
                statusCode: $result['status_code'] ?? Response::HTTP_BAD_REQUEST,
                status: 'failed'
            );
        }

        return $this->returnJsonResponse(
            message: 'Mono customers retrieved successfully',
            data: $result['data'] ?? $result
        );
    }

    /**
     * Delete a Mono customer by ID
     * DELETE /api/v1/vendor/credit/mono-customers/{id}
     * DELETE /api/v1/client/credit/mono-customers/{id}
     * https://docs.mono.co/api/customer/delete-customer
     */
    public function delete(string $id): JsonResponse
    {
        $result = $this->monoCustomerService->deleteCustomer($id);

        if (! $result['success']) {
            return $this->returnJsonResponse(
                message: $result['error'] ?? 'Failed to delete Mono customer',
                data: $result,
                statusCode: $result['status_code'] ?? Response::HTTP_BAD_REQUEST,
                status: 'failed'
            );
        }

        return $this->returnJsonResponse(
            message: $result['message'] ?? 'Mono customer deleted successfully',
            data: $result['data'] ?? null
        );
    }
}
