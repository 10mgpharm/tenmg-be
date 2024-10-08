<?php

use App\Models\Business;
use App\Models\Customer;
use App\Models\FileUpload;
use App\Models\User;
use App\Repositories\CustomerRepository;
use App\Services\ActivityLogService;
use App\Services\AttachmentService;
use App\Services\AuthService;
use App\Services\CustomerService;
use Illuminate\Pagination\LengthAwarePaginator;

// Set up mocks for the dependencies
beforeEach(function () {
    // Mocks for external dependencies
    $this->customerRepositoryMock = Mockery::mock(CustomerRepository::class);
    $this->attachmentServiceMock = Mockery::mock(AttachmentService::class);
    $this->authServiceMock = Mockery::mock(AuthService::class);
    $this->activityLogServiceMock = Mockery::mock(ActivityLogService::class);

    $this->businessMock = Mockery::mock(Business::class)->makePartial();
    $this->businessMock->id = 1;
    $this->authServiceMock
        ->shouldReceive('getBusiness')
        ->andReturn($this->businessMock);

    // Mock authenticated user
    $this->authUserMock = Mockery::mock(User::class)->makePartial();
    $this->authUserMock->id = 1;

    $this->authServiceMock
        ->shouldReceive('getUser')
        ->andReturn($this->authUserMock);

    // Initialize the service being tested
    $this->customerService = new CustomerService(
        $this->customerRepositoryMock,
        $this->attachmentServiceMock,
        $this->authServiceMock,
        $this->activityLogServiceMock
    );
});

// Test: it can create a customer
test('it can create a customer', function () {
    $data = [
        'name' => 'John Doe',
        'email' => 'john.doe@example.com',
        'phone' => '123456789',
    ];

    // Expected data after the service modifies it
    $expectedData = array_merge($data, [
        'vendorId' => 1,
        'created_by' => 1,
    ]);

    // Mock pagination to get the total customer count
    $paginationMock = Mockery::mock(LengthAwarePaginator::class);
    $paginationMock->shouldReceive('total')->andReturn(5);

    // Mock the Customer model and repository behavior
    $customerMock = Mockery::mock(Customer::class)->makePartial();
    $customerMock->id = 1;
    $customerMock->name = 'John Doe';

    // Adjust the mock for `create` to match the expected arguments
    $this->customerRepositoryMock
        ->shouldReceive('create')
        ->once()
        ->with(Mockery::on(function ($arg) use ($expectedData) {
            return $arg['vendorId'] === $expectedData['vendorId'] &&
                $arg['created_by'] === $expectedData['created_by'] &&
                $arg['name'] === $expectedData['name'] &&
                $arg['email'] === $expectedData['email'] &&
                $arg['phone'] === $expectedData['phone'];
        }))
        ->andReturn($customerMock);

    $this->activityLogServiceMock
        ->shouldReceive('logActivity')
        ->once();

    // Call the service method
    $createdCustomer = $this->customerService->createCustomer($data);

    // Assert the returned result
    expect($createdCustomer)->toBeInstanceOf(Customer::class);
    expect($createdCustomer->name)->toBe('John Doe');
});

// Test: it can update a customer
test('it can update a customer', function () {
    $data = ['name' => 'John Doe', 'avatar' => ['url' => 'John Doe', 'path' => 'new-avatar.png']];

    // Mock the existing customer
    $customerMock = Mockery::mock(Customer::class)->makePartial();
    $customerMock->id = 1;
    $customerMock->name = 'John Doe';

    $this->customerRepositoryMock
        ->shouldReceive('findById')
        ->once()
        ->with(1)
        ->andReturn($customerMock);

    $this->customerRepositoryMock
        ->shouldReceive('update')
        ->once();

    $this->activityLogServiceMock
        ->shouldReceive('logActivity')
        ->once();

    $updatedCustomer = $this->customerService->updateCustomer(1, $data);

    expect($updatedCustomer->name)->toBe('John Doe');
});

// Test: it can delete a customer
test('it can delete a customer', function () {
    // Mock the existing customer
    $customerMock = Mockery::mock(Customer::class)->makePartial();
    $customerMock->id = 1;

    // Mock the avatar as an instance of FileUpload
    $fileUploadMock = Mockery::mock(FileUpload::class)->makePartial();
    $customerMock->avatar = $fileUploadMock;

    $this->customerRepositoryMock
        ->shouldReceive('findById')
        ->once()
        ->with(1)
        ->andReturn($customerMock);

    // Ensure that the attachment service gets the correct model for deletion
    $this->attachmentServiceMock
        ->shouldReceive('deleteFile')
        ->once()
        ->with($fileUploadMock);

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

// Test: it can list customers with pagination
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

// Test: it can toggle customer active status
test('it can toggle customer active status', function () {
    // Mock the existing customer
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

// Clean up mockery after each test
afterEach(function () {
    Mockery::close();
});
