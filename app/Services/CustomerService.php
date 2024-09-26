<?php

namespace App\Services;

use App\Models\Customer;
use App\Repositories\Interfaces\ICustomerRepository;
use App\Services\Interfaces\ICustomerService;

class CustomerService implements ICustomerService
{
    public function __construct(
        private ICustomerRepository $customerRepository,
        private AttachmentService $attachmentService,
        private AuthService $authService,
        private ActivityLogService $activityLogService,
    ) {}

    public function createCustomer(array $data): Customer
    {
        $data['vendorId'] = $this->authService->getBusiness()?->id;
        $data['created_by'] = $this->authService->getUser()->id;
        $customer = $this->customerRepository->create($data);

        $this->activityLogService->logActivity(model: $customer, causer: $this->authService->getUser(), action: 'created', properties: ['attributes' => $data]);

        return $customer;
    }

    public function getCustomerById(int $id): ?Customer
    {
        return $this->customerRepository->findById($id);
    }

    public function updateCustomer(int $id, array $data): ?Customer
    {
        $customer = $this->customerRepository->findById($id);

        if ($customer) {
            $data['vendorId'] = $this->authService->getBusiness()?->id;

            $this->customerRepository->update($customer, $data);

            $this->activityLogService->logActivity(model: $customer, causer: $this->authService->getUser(), action: 'updated', properties: ['attributes' => $data]);

            return $customer;
        }

        return null;
    }

    public function deleteCustomer(int $id): bool
    {
        $customer = $this->customerRepository->findById($id);

        if ($customer) {
            if ($customer->avatar) {
                $this->attachmentService->deleteFile($customer->avatar);
            }

            $this->customerRepository->delete($customer);
            $this->activityLogService->logActivity(model: $customer, causer: $this->authService->getUser(), action: 'deleted');

            return true;
        }

        return false;
    }

    public function listCustomers(array $filters, int $perPage): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        return $this->customerRepository->paginate($filters, $perPage);
    }

    public function toggleCustomerActiveStatus(int $id): ?Customer
    {
        $customer = $this->customerRepository->findById($id);

        if ($customer) {
            $customer->active = ! $customer->active;
            $this->customerRepository->update($customer, ['active' => $customer->active]);

            return $customer;
        }

        return null;
    }
}
