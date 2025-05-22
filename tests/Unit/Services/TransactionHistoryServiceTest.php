<?php

namespace Tests\Unit\Services;

use App\Models\CreditTxnHistoryEvaluation;
use App\Models\Customer;
use App\Models\FileUpload;
use App\Models\User;
use App\Repositories\CreditBusinessRuleRepository;
use App\Repositories\CreditScoreRepository;
use App\Repositories\FileUploadRepository;
use App\Repositories\CustomerRepository;
use App\Repositories\LoanRepository;
use App\Repositories\RepaymentScheduleRepository;
use App\Repositories\TransactionHistoryRepository;
use App\Services\AttachmentService;
use App\Services\AuthService;
use App\Services\Interfaces\IActivityLogService;
use App\Services\Interfaces\IAffordabilityService;
use App\Services\Interfaces\IRuleEngineService;
use App\Services\TransactionHistoryService;
use Illuminate\Http\UploadedFile;

beforeEach(function () {
    $this->authServiceMock = mock(AuthService::class);
    $this->fileUploadRepositoryMock = mock(FileUploadRepository::class);
    $this->attachmentServiceMock = mock(AttachmentService::class);
    $this->ruleEngineServiceMock = mock(IRuleEngineService::class);
    $this->customerRepositoryMock = mock(CustomerRepository::class);
    $this->affordabilityServiceMock = mock(IAffordabilityService::class);
    $this->transactionHistoryRepositoryMock = mock(TransactionHistoryRepository::class);
    $this->creditScoreRepositoryMock = mock(CreditScoreRepository::class);
    $this->creditBusinessRuleRepositoryMock = mock(CreditBusinessRuleRepository::class);
    $this->loanRepositoryMock = mock(LoanRepository::class);
    $this->repaymentScheduleRepositoryMock = mock(RepaymentScheduleRepository::class);
    $this->activityLogServiceMock = mock(IActivityLogService::class);

    $this->transactionHistoryService = new TransactionHistoryService(
        $this->authServiceMock,
        $this->fileUploadRepositoryMock,
        $this->attachmentServiceMock,
        $this->ruleEngineServiceMock,
        $this->customerRepositoryMock,
        $this->affordabilityServiceMock,
        $this->transactionHistoryRepositoryMock,
        $this->creditScoreRepositoryMock,
        $this->creditBusinessRuleRepositoryMock,
        $this->repaymentScheduleRepositoryMock,
        $this->loanRepositoryMock,
        $this->activityLogServiceMock
    );
});

it('throws an exception if the customer is not found during transaction history upload', function () {
    $file = UploadedFile::fake()->create('txn.csv');
    $this->customerRepositoryMock->shouldReceive('findById')->once()->andReturn(null);

    expect(fn () => $this->transactionHistoryService->uploadTransactionHistory($file, 1))
        ->toThrow(\Exception::class, 'Customer not found');
});

it('uploads a transaction history successfully', function () {
    $file = UploadedFile::fake()->create('txn.csv');

    $customer = new Customer;
    $customer->id = 1;
    $customer->business_id = 1;

    $user = new User;
    $user->id = 123;

    $this->customerRepositoryMock->shouldReceive('findById')->once()->andReturn($customer);
    $this->transactionHistoryRepositoryMock->shouldReceive('createTransactionHistoryEvaluation')->once()->andReturn(new CreditTxnHistoryEvaluation(['id' => 1]));
    $this->attachmentServiceMock->shouldReceive('saveNewUpload')->once()->andReturn(new FileUpload(['id' => 1]));
    $this->transactionHistoryRepositoryMock->shouldReceive('updateTransactionHistoryEvaluation')->once();
    $this->authServiceMock->shouldReceive('getUser')->twice()->andReturn($user);
    $this->authServiceMock->shouldReceive('getId')->once()->andReturn(1);
    $this->activityLogServiceMock->shouldReceive('logActivity')->twice();

    $response = $this->transactionHistoryService->uploadTransactionHistory($file, 1);
    expect($response)->toHaveKeys(['file', 'txn_history_evaluation']);
});
