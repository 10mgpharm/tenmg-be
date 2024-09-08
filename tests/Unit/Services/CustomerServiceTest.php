<?php

namespace Tests\Unit\Services;

use App\Models\Customer;
use App\Models\FileUpload;
use App\Models\User;
use App\Repositories\Interfaces\ICustomerRepository;
use App\Services\ActivityLogService;
use App\Services\AttachmentService;
use App\Services\AuthService;
use App\Services\CustomerService;
use Illuminate\Pagination\LengthAwarePaginator;
use Mockery;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CustomerServiceTest extends TestCase
{
    protected $customerRepositoryMock;

    protected $attachmentServiceMock;

    protected $authServiceMock;

    protected $activityLogServiceMock;

    protected $customerService;

    protected $authUserMock;

    protected function setUp(): void
    {
        parent::setUp();

        // Mocks for external dependencies
        $this->customerRepositoryMock = Mockery::mock(ICustomerRepository::class);
        $this->attachmentServiceMock = Mockery::mock(AttachmentService::class);
        $this->authServiceMock = Mockery::mock(AuthService::class);
        $this->activityLogServiceMock = Mockery::mock(ActivityLogService::class);

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
    }

    #[Test]
    public function itCanCreateACustomer()
    {
        $data = [
            'vendorId' => 1,
            'name' => 'John Doe',
            'email' => 'john.doe@example.com',
            'phone' => '123456789',
        ];

        // Mock pagination to get the total customer count
        $paginationMock = Mockery::mock(LengthAwarePaginator::class);
        $paginationMock->shouldReceive('total')->andReturn(5);

        // Mock the Customer model and repository behavior
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

        $this->assertInstanceOf(Customer::class, $createdCustomer);
        $this->assertEquals('John Doe', $createdCustomer->name);
    }

    #[Test]
    public function itCanUpdateACustomer()
    {
        $data = ['name' => 'John Doe', 'avatar' => ['url' => 'John Doe', 'path' => 'new-avatar.png']];

        // Mock the existing customer
        $customerMock = Mockery::mock(Customer::class)->makePartial();
        $customerMock->id = 1;
        $customerMock->name = 'John Doe';

        // Mock the avatar as an instance of FileUpload
        $fileUploadMock = Mockery::mock(FileUpload::class)->makePartial();
        $customerMock->avatar = $fileUploadMock;

        $this->customerRepositoryMock
            ->shouldReceive('findById')
            ->once()
            ->with(1)
            ->andReturn($customerMock);

        $this->customerRepositoryMock
            ->shouldReceive('update')
            ->once();

        // Ensure that the attachment service gets the correct model and file path
        $this->attachmentServiceMock
            ->shouldReceive('updateFile')
            ->once()
            ->with($fileUploadMock, $data['avatar']);

        $this->activityLogServiceMock
            ->shouldReceive('logActivity')
            ->once();

        $updatedCustomer = $this->customerService->updateCustomer(1, $data);

        $this->assertEquals('John Doe', $updatedCustomer->name);
    }

    #[Test]
    public function itCanDeleteACustomer()
    {
        // Mock the existing customer
        $customerMock = Mockery::mock(Customer::class)->makePartial();
        $customerMock->id = 1;
        $customerMock->avatar = 'avatar.png';

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

        $this->assertTrue($deleted);
    }

    #[Test]
    public function itCanListCustomersWithPagination()
    {
        $filters = ['name' => 'Jane Doe'];
        $paginationResult = Mockery::mock(LengthAwarePaginator::class);

        $this->customerRepositoryMock
            ->shouldReceive('paginate')
            ->once()
            ->with($filters, 10)
            ->andReturn($paginationResult);

        $result = $this->customerService->listCustomers($filters, 10);

        $this->assertEquals($paginationResult, $result);
    }

    #[Test]
    public function itCanToggleCustomerActiveStatus()
    {
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

        $this->assertFalse($toggledCustomer->active);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
