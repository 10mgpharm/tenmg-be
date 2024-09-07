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

class CustomerController extends Controller
{
    public function __construct(private ICustomerService $customerService)
    {
        $this->customerService = $customerService;
    }

    public function index(ListCustomersRequest $request): JsonResponse
    {
        $customers = $this->customerService->listCustomers($request->all(), $request->perPage ?? 10);

        return response()->json($customers);
    }

    public function store(StoreCustomerRequest $request): JsonResponse
    {
        $customer = $this->customerService->createCustomer($request->validated());

        return response()->json($customer, 201);
    }

    public function show($id)
    {
        $customer = $this->customerService->getCustomerById($id);

        return response()->json($customer);
    }

    public function update(UpdateCustomerRequest $request, $id): JsonResponse
    {
        $customer = $this->customerService->updateCustomer($id, $request->all());

        return response()->json($customer);
    }

    public function destroy(int $id): JsonResponse
    {
        $deleted = $this->customerService->deleteCustomer($id);

        return response()->json(['message' => 'Customer deleted successfully'], $deleted ? Response::HTTP_OK : Response::HTTP_NOT_FOUND);
    }

    public function toggleActive(int $id): JsonResponse
    {
        $customer = $this->customerService->toggleCustomerActiveStatus($id);

        return response()->json($customer);
    }

    public function export()
    {
        return Excel::download(new CustomersExport, 'customers.xlsx');
    }

    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx|max:20024',
        ]);

        Excel::import(new CustomersImport, $request->file('file'));

        return response()->json(['message' => 'Customers imported successfully']);
    }
}
