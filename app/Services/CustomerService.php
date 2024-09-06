<?php

namespace App\Services;

use App\Models\Customer;
use App\Repositories\Interfaces\CustomerRepositoryInterface;
use App\Services\Interfaces\CustomerServiceInterface;

class CustomerService implements CustomerServiceInterface
{
    public function __construct(
        private CustomerRepositoryInterface $customerRepository,
        private AttachmentService $attachmentService,
        private AuthService $authService,
        private ActivityLogService $activityLogService,
    ) {}

    public function createCustomer(array $data): Customer
    {
        $businessCode = '10MG'; //todo: find business using business_id using business repo when its ready
        $count = $this->customerRepository->paginate(['vendorId' => $data['vendorId']], 1)->total() + 1;

        $data['identifier'] = strtoupper($businessCode).'-CUS-'.str_pad($count, 3, '0', STR_PAD_LEFT);
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
            $this->customerRepository->update($customer, $data);

            if (isset($data['avatar'])) {
                $this->attachmentService->updateFile($customer->avatar, $data['avatar']);
            }

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
