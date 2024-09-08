<?php

namespace App\Http\Controllers\API\Credit;

use App\Exports\CustomersExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\ListCustomersRequest;
use App\Http\Requests\StoreCustomerRequest;
use App\Http\Requests\UpdateCustomerRequest;
use App\Imports\CustomersImport;
use App\Services\Interfaces\ICustomerService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class CustomerController extends Controller
{
    public function __construct(private ICustomerService $customerService)
    {
        $this->customerService = $customerService;
    }

    public function index(ListCustomersRequest $request): JsonResponse
    {
        $customers = $this->customerService->listCustomers($request->all(), $request->perPage ?? 10);

        return $this->returnJsonResponse(
            data: $customers
        );
    }

    public function store(StoreCustomerRequest $request): JsonResponse
    {
        $customer = $this->customerService->createCustomer($request->validated());

        return $this->returnJsonResponse(
            data: $customer,
            statusCode: Response::HTTP_CREATED,
        );
    }

    public function show(int $id)
    {
        $customer = $this->customerService->getCustomerById($id);

        return $this->returnJsonResponse(
            data: $customer,
        );
    }

    public function update(UpdateCustomerRequest $request, int $id): JsonResponse
    {
        $customer = $this->customerService->updateCustomer($id, $request->all());

        if (! $customer) {
            return $this->returnJsonResponse(
                message: 'Customer not found',
                statusCode: Response::HTTP_NOT_FOUND
            );
        }

        // Return the updated customer
        return $this->returnJsonResponse(
            data: $customer,
        );
    }

    public function destroy(int $id): JsonResponse
    {
        $deleted = $this->customerService->deleteCustomer($id);

        return $this->returnJsonResponse(
            message: 'Customer deleted successfully',
            statusCode: $deleted ? Response::HTTP_OK : Response::HTTP_NOT_FOUND,
        );
    }

    public function toggleActive(int $id): JsonResponse
    {
        $customer = $this->customerService->toggleCustomerActiveStatus($id);
        if (! $customer) {
            return response()->json(['message' => 'Customer not found'], Response::HTTP_NOT_FOUND);
        }

        return $this->returnJsonResponse(
            data: $customer,
        );
    }

    public function export(): BinaryFileResponse
    {
        return Excel::download(new CustomersExport, 'customers.xlsx');
    }

    public function import(Request $request): JsonResponse
    {
        $request->validate([
            'file' => 'required|mimes:xlsx|max:20024',
        ]);

        Excel::import(new CustomersImport, $request->file('file'));

        return $this->returnJsonResponse(
            message: 'Customers imported successfully',
        );
    }
}
