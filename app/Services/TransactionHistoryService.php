<?php

namespace App\Services;

use App\Helpers\UtilityHelper;
use App\Models\CreditScore;
use App\Models\CreditTxnHistoryEvaluation;
use App\Models\FileUpload;
use App\Models\User;
use App\Repositories\CreditBusinessRuleRepository;
use App\Repositories\CreditScoreRepository;
use App\Repositories\CustomerRepository;
use App\Repositories\FileUploadRepository;
use App\Repositories\LoanRepository;
use App\Repositories\RepaymentScheduleRepository;
use App\Repositories\TransactionHistoryRepository;
use App\Services\Interfaces\IActivityLogService;
use App\Services\Interfaces\IAffordabilityService;
use App\Services\Interfaces\IRuleEngineService;
use App\Services\Interfaces\ITxnHistoryService;
use Illuminate\Http\File;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;

// use Symfony\Component\HttpFoundation\StreamedResponse;

class TransactionHistoryService implements ITxnHistoryService
{
    public function __construct(
        private AuthService $authService,
        private FileUploadRepository $fileUploadRepository,
        private AttachmentService $attachmentService,
        private IRuleEngineService $ruleEngineService,
        private CustomerRepository $customerRepository,
        private IAffordabilityService $affordabilityService,
        private TransactionHistoryRepository $transactionHistoryRepository,
        private CreditScoreRepository $creditScoreRepository,
        private CreditBusinessRuleRepository $creditBusinessRuleRepository,
        private RepaymentScheduleRepository $repaymentScheduleRepository,
        private LoanRepository $loanRepository,
        private IActivityLogService $activityLogService,
    ) {}

    public function getTransactionHistories(int $customerId): array
    {
        $customer = $this->customerRepository->findById($customerId);

        if (! $customer) {
            throw new \Exception('Customer not found');
        }

        $transactionHistories = $this->transactionHistoryRepository->getTransactionHistoryEvaluationByCustomerId(customerId: $customerId);

        return $transactionHistories;
    }

    public function listAllTransactions(array $filters, int $perPage): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        return $this->transactionHistoryRepository->paginate($filters, $perPage);
    }

    public function listAllCreditScore(array $filters, int $perPage): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        return $this->transactionHistoryRepository->listAllCreditScore($filters, $perPage);
    }

    public function uploadTransactionHistory(File|UploadedFile|string $file, int $customerId, User $user): array
    {
        $customer = $this->customerRepository->findById($customerId);

        if (! $customer) {
            throw new \Exception('Customer not found');
        }

        // Logging the start of the upload process
        $this->activityLogService->logActivity(
            logName: 'TransactionHistory',
            model: $customer,
            causer: $user,
            action: 'Uploading transaction history for customer',
            properties: ['file_format' => strtoupper($file->getClientOriginalExtension())]
        );

        $evaluationData = [
            'identifier' => UtilityHelper::generateSlug('EVAL'),
            'business_id' => $customer->business_id,
            'customer_id' => $customer->id,
            'file_format' => strtoupper($file->getClientOriginalExtension()) == 'XLSX' ? 'EXCEL' : strtoupper($file->getClientOriginalExtension()),
            'source' => 'API',
            'status' => 'PENDING',
            'created_by_id' => $user->id,
        ];

        $txnHistoryEvaluation = $this->transactionHistoryRepository->createTransactionHistoryEvaluation($evaluationData);

        $fileUpload = $this->attachmentService->saveNewUpload(
            uploadedFile: $file,
            model_id: $txnHistoryEvaluation->id,
            model_type: CreditTxnHistoryEvaluation::class,
            basePath: 'uploads/transaction_history'
        );

        $txnHistoryEvaluation = $this->transactionHistoryRepository->updateTransactionHistoryEvaluation(
            id: $txnHistoryEvaluation->id,
            data: [
                'transaction_file_id' => $fileUpload->id,
            ]
        );

        // Logging successful upload
        $this->activityLogService->logActivity(
            logName: 'TransactionHistory',
            model: $txnHistoryEvaluation,
            causer: $user,
            action: 'Successfully uploaded transaction history',
            properties: ['file' => $fileUpload->url]
        );

        return [
            'file' => $fileUpload,
            'txn_history_evaluation' => $txnHistoryEvaluation,
        ];
    }

    public function viewTransactionHistory(FileUpload $fileUpload): array
    {
        return $this->transactionHistoryRepository->viewTransactionHistory($fileUpload);
    }

    public function evaluateTransactionHistory(int $transactionHistoryId, User $user): array
    {
        try {
            // 1. Fetch the transaction history evaluation entry by ID
            $txnHistoryEvaluation = $this->transactionHistoryRepository->getTxnHistoryEvaluationById($transactionHistoryId);

            // Logging evaluation start
            $this->activityLogService->logActivity(
                logName: 'TransactionHistory',
                model: $txnHistoryEvaluation,
                causer: $user,
                action: 'Evaluating transaction history for customer',
            );

            // 2. Read and evaluate the transaction file (mocking this process with random data)
            // You can implement actual parsing logic for CSV, Excel, JSON files here
            $fileUploadModel = $this->fileUploadRepository->getFileById($txnHistoryEvaluation->transaction_file_id);

            if (! $fileUploadModel) {
                throw new \Exception('File not found');
            }

            $fileContents = $this->attachmentService->getFIleFromStorage(attachment: $fileUploadModel);

            $extension = strtolower($fileUploadModel->extension);
            $transactions = [];
            // Parse the file based on its extension
            if (
                $extension === 'csv'
            ) {
                $transactions = $this->attachmentService->parseCsvFromContents($fileContents);
            } elseif (in_array($extension, ['xlsx', 'xls'])) {
                $transactions = $this->attachmentService->parseExcelFromContents(fileContents: $fileContents, fileName: $fileUploadModel->name); // Excel files need file path
            } elseif ($extension === 'json') {
                $transactions = $this->attachmentService->parseJsonFromContents($fileContents);
            } else {
                throw new \Exception('Unsupported file format.');
            }

            $repaymentSchedules = $this->repaymentScheduleRepository->fetchRepaymentScheduleByCustomerId($txnHistoryEvaluation->customer_id);
            $loans = $this->loanRepository->fetchLoansByCustomerId($txnHistoryEvaluation->customer_id);

            $evaluationResult = $this->ruleEngineService->evaluate(
                transactions: $transactions,
                loans: $loans,
                repayments: $repaymentSchedules,
            );

            // 3. Store the evaluation result in the txn_history_evaluation table
            $this->transactionHistoryRepository->updateTransactionHistoryEvaluation(
                id: $txnHistoryEvaluation->id,
                data: [
                    'evaluation_result' => json_encode($evaluationResult),
                    'status' => 'DONE',
                ]
            );

            // 4. Fetch active business rules
            $activeRules = $this->creditBusinessRuleRepository->getActiveRules();

            // 5. Apply business rules to evaluation result and calculate credit score
            $creditScore = $this->ruleEngineService->applyRules($evaluationResult, $activeRules->toArray());

            // 6. Calculate affordability using credit score percent
            $affordability = $this->affordabilityService->calculateAffordability($creditScore['score_percent']);

            // 7. Get affordability category
            $category = $this->affordabilityService->getAffordabilityCategories($creditScore['score_percent']);

            // 7. Store the credit score result in the credit_scores table
            $creditScore = $this->creditScoreRepository->store([
                'business_id' => $txnHistoryEvaluation->business_id,
                'customer_id' => $txnHistoryEvaluation->customer_id,
                'identifier' => UtilityHelper::generateSlug('CSC'),
                'txn_evaluation_id' => $txnHistoryEvaluation->id,
                'category' => $category,
                'business_rule_json' => json_encode($activeRules),
                'credit_score_result' => json_encode($creditScore),
                'score_percent' => $creditScore['score_percent'],
                'score_value' => $creditScore['score_value'],
                'score_total' => $creditScore['score_total'],
                'created_by_id' => $user->id,
                'source' => 'API',
                'affordability' => json_encode($affordability),
            ]);

            $this->creditScoreRepository->updateCreditScore($txnHistoryEvaluation->customer_id, $creditScore->id);

            // Logging evaluation success or failure
            $this->activityLogService->logActivity(
                logName: 'TransactionHistory',
                model: $txnHistoryEvaluation,
                causer: $user,
                action: 'Transaction history evaluation completed',
            );

            return [
                'creditScore' => $creditScore,
                'affordability' => $affordability,
            ];
        } catch (\Throwable $th) {
            Log::info($th);
            throw $th;
        }
    }

    public function creditScoreBreakDown(int $txnEvaluationId): ?CreditScore
    {
        return $this->transactionHistoryRepository->creditScoreBreakDown($txnEvaluationId);
    }

    public function uploadAndEvaluateTransactionHistory(File|UploadedFile|string $file, int $customerId, User $user): array
    {
        $evaluationData = $this->uploadTransactionHistory(file: $file, customerId: $customerId, user: $user);

        return $this->evaluateTransactionHistory(transactionHistoryId: $evaluationData['txn_history_evaluation']?->id, user: $user);
    }

    public function getTransactionStats()
    {
        return $this->transactionHistoryRepository->getTransactionStats();
    }

    public function getCreditTransactionHistories(array $filters, $perPage = 10): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        return $this->transactionHistoryRepository->getCreditTransactionHistories($filters, $perPage);
    }
}
