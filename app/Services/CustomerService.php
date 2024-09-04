<?php

namespace App\Services;

use App\Models\Customer;
use App\Repositories\Interfaces\CustomerRepositoryInterface;
use App\Services\Interfaces\CustomerServiceInterface;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class CustomerService implements CustomerServiceInterface
{
    use LogsActivity;

    protected static $logName = 'customer';

    protected static $logOnlyDirty = true;

    protected static $logAttributes = ['name', 'email', 'phone', 'active'];

    public function __construct(
        private CustomerRepositoryInterface $customerRepository,
        private AttachmentService $attachmentService,
        private AuthService $authService
    ) {}

    public function createCustomer(array $data): Customer
    {
        $data['identifier'] = $this->generateCustomerIdentifier($data['business_id']);
        $data['created_by'] = $this->authService->getUser()->id;
        $customer = $this->customerRepository->create($data);

        activity()
            ->performedOn($customer)
            ->causedBy($this->authService->getUser())
            ->withProperties(['attributes' => $data])
            ->log('created');

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

            activity()
                ->performedOn($customer)
                ->causedBy($this->authService->getUser())
                ->withProperties(['attributes' => $data])
                ->log('updated');

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
            activity()
                ->performedOn($customer)
                ->causedBy($this->authService->getUser())
                ->log('deleted');

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

    private function generateCustomerIdentifier(int $business_id)
    {
        $businessCode = '10MG'; //todo: find business using business_id using business repo when its ready
        $count = $this->customerRepository->paginate(['business_id' => $business_id], 1)->total() + 1;

        return strtoupper($businessCode).'-CUS-'.str_pad($count, 3, '0', STR_PAD_LEFT);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'email', 'phone', 'active'])
            ->logOnlyDirty()
            ->useLogName('customer');
    }
}
