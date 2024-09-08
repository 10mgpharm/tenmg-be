<?php

namespace Tests\Unit\Services;

use App\Models\Customer;
use App\Models\User;
use App\Repositories\Interfaces\ICustomerRepository;
use App\Services\ActivityLogService;
use App\Services\AttachmentService;
use App\Services\AuthService;
use App\Services\CustomerService;
use Illuminate\Pagination\LengthAwarePaginator;
use Mockery;

// Mocks and service initialization
beforeEach(function () {
    $this->customerRepositoryMock = Mockery::mock(ICustomerRepository::class);
    $this->attachmentServiceMock = Mockery::mock(AttachmentService::class);
    $this->authServiceMock = Mockery::mock(AuthService::class);
    $this->activityLogServiceMock = Mockery::mock(ActivityLogService::class);

    $this->authUserMock = Mockery::mock(User::class)->makePartial();
    $this->authUserMock->id = 1;

    $this->authServiceMock
        ->shouldReceive('getUser')
        ->andReturn($this->authUserMock);

    $this->customerService = new CustomerService(
        $this->customerRepositoryMock,
        $this->attachmentServiceMock,
        $this->authServiceMock,
        $this->activityLogServiceMock
    );
});

// Cleanup after tests
afterEach(function () {
    Mockery::close();
});

test('it can create a customer', function () {
    $data = [
        'vendorId' => 1,
        'name' => 'John Doe',
        'email' => 'john.doe@example.com',
        'phone' => '123456789',
    ];

    $paginationMock = Mockery::mock(LengthAwarePaginator::class);
    $paginationMock->shouldReceive('total')->andReturn(5);

    $customerMock = Mockery::mock(Customer::class)->makePartial();
    $customerMock->id = 1;
    $customerMock->name = 'John Doe';

    $this->customerRepositoryMock
        ->shouldReceive('paginate')
        ->once()
        ->with(['vendorId' => $data['vendorId']], 1)
        ->andReturn($paginationMock);

    $this->customerRepositoryMock
        ->shouldReceive('create')
        ->once()
        ->with(Mockery::on(function ($arg) use ($data) {
            return isset($arg['identifier'], $arg['created_by']) &&
                $arg['name'] === $data['name'];
        }))
        ->andReturn($customerMock);

    $this->activityLogServiceMock
        ->shouldReceive('logActivity')
        ->once();

    $createdCustomer = $this->customerService->createCustomer($data);

    expect($createdCustomer)->toBeInstanceOf(Customer::class);
    expect($createdCustomer->name)->toBe('John Doe');
});

test('it can update a customer', function () {
    $data = ['name' => 'John Doe', 'avatar' => 'new-avatar.png'];

    $customerMock = Mockery::mock(Customer::class)->makePartial();
    $customerMock->id = 1;
    $customerMock->name = 'John Doe';
    $customerMock->avatar = 'old-avatar.png';

    $this->customerRepositoryMock
        ->shouldReceive('findById')
        ->once()
        ->with(1)
        ->andReturn($customerMock);

    $this->customerRepositoryMock
        ->shouldReceive('update')
        ->once();

    $this->attachmentServiceMock
        ->shouldReceive('updateFile')
        ->once()
        ->with($customerMock->avatar, 'new-avatar.png');

    $this->activityLogServiceMock
        ->shouldReceive('logActivity')
        ->once();

    $updatedCustomer = $this->customerService->updateCustomer(1, $data);

    expect($updatedCustomer->name)->toBe('John Doe');
});

test('it can delete a customer', function () {
    $customerMock = Mockery::mock(Customer::class)->makePartial();
    $customerMock->id = 1;
    $customerMock->avatar = 'avatar.png';

    $this->customerRepositoryMock
        ->shouldReceive('findById')
        ->once()
        ->with(1)
        ->andReturn($customerMock);

    $this->attachmentServiceMock
        ->shouldReceive('deleteFile')
        ->once()
        ->with($customerMock->avatar);

    $this->customerRepositoryMock
        ->shouldReceive('delete')
        ->once()
        ->with($customerMock);

    $this->activityLogServiceMock
        ->shouldReceive('logActivity')
        ->once();

    $deleted = $this->customerService->deleteCustomer(1);

    expect($deleted)->toBeTrue();
});

test('it can list customers with pagination', function () {
    $filters = ['name' => 'Jane Doe'];
    $paginationResult = Mockery::mock(LengthAwarePaginator::class);

    $this->customerRepositoryMock
        ->shouldReceive('paginate')
        ->once()
        ->with($filters, 10)
        ->andReturn($paginationResult);

    $result = $this->customerService->listCustomers($filters, 10);

    expect($result)->toBe($paginationResult);
});

test('it can toggle customer active status', function () {
    $customerMock = Mockery::mock(Customer::class)->makePartial();
    $customerMock->id = 1;
    $customerMock->active = true;

    $this->customerRepositoryMock
        ->shouldReceive('findById')
        ->once()
        ->with(1)
        ->andReturn($customerMock);

    $this->customerRepositoryMock
        ->shouldReceive('update')
        ->once()
        ->with($customerMock, ['active' => false]);

    $toggledCustomer = $this->customerService->toggleCustomerActiveStatus(1);

    expect($toggledCustomer->active)->toBeFalse();
});
