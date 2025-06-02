<?php

namespace App\Repositories;

use App\Helpers\UtilityHelper;
use App\Http\Resources\BusinessLimitedRecordResource;
use App\Http\Resources\CreditCustomerResource;
use App\Http\Resources\LoadApplicationResource;
use App\Models\ApiCallLog;
use App\Models\ApiKey;
use App\Models\Business;
use App\Models\CreditCustomerBank;
use App\Models\CreditLenderPreference;
use App\Models\CreditLendersWallet;
use App\Models\CreditOffer;
use App\Models\Customer;
use App\Models\DebitMandate;
use App\Models\Loan;
use App\Models\LoanApplication;
use App\Models\User;
use App\Services\ActivityLogService;
use App\Services\AuditLogService;
use App\Settings\LoanSettings;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class LoanApplicationRepository
{

    function __construct(private FincraMandateRepository $fincraMandateRepository, private ActivityLogService $activityLogService) {}

    public function create(array $data)
    {
        $loanSettings = new LoanSettings();

        // external provided reference by vender (optional)
        $txnReference =  array_key_exists('txnReference', $data) ? $data['txnReference'] : null;

        return LoanApplication::create([
            'business_id' => $data['businessId'],
            'identifier' => UtilityHelper::generateSlug('APP'),
            'customer_id' => $data['customerId'],
            'requested_amount' => $data['requestedAmount'] ?? null,
            'interest_amount' => $data['interestAmount'] ?? 0,
            'total_amount' => $data['totalAmount'] ?? 0,
            'interest_rate' => $loanSettings->lenders_interest,
            'tenmg_interest' => $loanSettings->tenmg_interest,
            'duration_in_months' => $data['durationInMonths'] ?? null,
            'source' => $data['source'] ?? 'DASHBOARD',
            'status' => 'PENDING_MANDATE',
            'reference' => $txnReference,
        ]);
    }

    public function update(int $id, array $data): LoanApplication
    {
        $application = LoanApplication::findOrFail($id);
        $application->update([
            'requested_amount' => $data['requestedAmount'] ?? null,
            'interest_amount' => $data['interestAmount'] ?? 0,
            'total_amount' => $data['totalAmount'] ?? 0,
            'interest_rate' => $data['interestRate'] ?? config('app.interest_rate'),
            'duration_in_months' => $data['durationInMonths'] ?? null,
        ]);

        return $application;
    }

    public function findById(int $id): LoanApplication
    {
        $loanApp = LoanApplication::whereId($id)->with('customer.lastEvaluationHistory.creditScore')->first();
        if (! $loanApp) {
            throw new \Exception('Loan application not found', 404);
        }

        return $loanApp;
    }

    public function findByReference(string $reference): ?LoanApplication
    {
        return LoanApplication::whereIdentifier($reference)->with(['customer.lastEvaluationHistory.creditScore', 'business.logo'])->first();
    }

    public function getAll(array $criteria, int $perPage = 15): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {

        //get the business type
        $user = request()->user();
        $business = $user->ownerBusinessType
            ?: $user->businesses()->firstWhere('user_id', $user->id);

        $query = LoanApplication::query();

        if (isset($criteria['search'])) {
            $searchTerm = $criteria['search'];
            $query->where('credit_applications.identifier', 'like', $searchTerm)
                ->orWhereHas('customer', function ($q) use ($searchTerm) {
                    $q->where('email', 'like', '%' . $searchTerm . '%')->orWhere('name', 'like', '%' . $searchTerm . '%');
                });
        }

        if (isset($criteria['dateFrom']) && isset($criteria['dateTo'])) {
            $query->whereBetween('created_at', [$criteria['dateFrom'], $criteria['dateTo']]);
        }
        if (isset($criteria['status'])) {
            $query->where('status', $criteria['status']);
        }

        if ($business->type == "LENDER") {

            $lenderId = $business->id;
            $ignoredIds = CreditLenderPreference::where('lender_id', $business->id)->first()->ignored_applications_id;
            $query->when(!empty($ignoredIds ?? []), function ($querySub) use ($ignoredIds, $lenderId) {
                $querySub->whereNotIn('id', $ignoredIds)->whereHas('offers', function ($offerQuery) use ($lenderId) {
                    $offerQuery->where('lender_id', $lenderId);
                });;
            });

            $query->where('duration_in_months', '!=', null);
        }

        // if (isset($criteria['businessId'])) {
        //     $query->where('business_id', $criteria['businessId']);
        // }

        $query->orderBy('created_at', 'desc');

        return $query->paginate($perPage);
    }

    public function getLoanApplicationStats()
    {
        $user = request()->user();
        $business_id = $user->ownerBusinessType?->id
            ?: $user->businesses()->firstWhere('user_id', $user->id)?->id;

        $totalApplications = LoanApplication::count();
        $successfulApplications = LoanApplication::where('status', '!=', 'APPROVED')->count();
        $pendingApplications = CreditOffer::where('business_id', $business_id)->count();

        return [
            'totalApplications' => $totalApplications,
            'successfulApplications' => $successfulApplications,
            'pendingApplications' => $pendingApplications,
        ];
    }

    public function approveLoanApplicationManually(Request $request)
    {
        if($request->action == 'decline'){
            return $this->declineLoanApplicationByLender($request);
        }

        $user = request()->user();
        $business_id = $user->ownerBusinessType?->id
            ?: $user->businesses()->firstWhere('user_id', $user->id)?->id;

        $business = Business::find($business_id);
        $application = LoanApplication::where('identifier', $request->applicationId)->first();
        if ($application->status == "APPROVED") {
            throw new Exception('Loan already approved');
        }
        //check if lender has enough in his wallet to confirm the load
        $depositWallet = CreditLendersWallet::where('lender_id', $business_id)->where('type', 'deposit')->first();
        if ((int)$depositWallet->current_balance < (int)$application->requested_amount) {
            throw new Exception('Insufficient funds in lender\'s wallet to approve loan application.');
        }
        $offer = $this->fincraMandateRepository->createOffer($application, $business);
        $loan = $this->fincraMandateRepository->createLoan($offer, $application);

        return $loan;
    }

    public function declineLoanApplicationByLender(Request $request)
    {
        $user = request()->user();
        $business_id = $user->ownerBusinessType?->id
            ?: $user->businesses()->firstWhere('user_id', $user->id)?->id;

        $application = LoanApplication::where('identifier', $request->applicationId)->first();
        if ($application->status == "APPROVED") {
            throw new Exception('Loan already approved');
        }

        //add the loan application id to the loan preferences table ignored_applications_id
        $loanPreferences = CreditLenderPreference::where('lender_id', $business_id)->first();
        $loanPreferences->ignored_applications_id = array_merge($loanPreferences->ignored_applications_id ?? [], [$application->id]);
        $loanPreferences->save();

        return $application;
    }

    public function deleteById(int $id)
    {
        return LoanApplication::destroy($id);
    }

    public function filter(array $criteria, int $perPage = 15): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        $query = LoanApplication::query();

        $query->join('credit_customers', 'credit_customers.id', '=', 'credit_applications.customer_id');

        $query->when(isset($criteria['search']), function ($query) use ($criteria) {
            $searchTerm = "%{$criteria['search']}%";

            return $query->where(function ($query) use ($searchTerm) {
                $query->where('credit_applications.identifier', 'like', $searchTerm)
                    ->orWhereHas('customer', function ($q) use ($searchTerm) {
                        $q->where('email', 'like', $searchTerm)->orWhere('name', 'like', $searchTerm);
                    });
            });
        });

        $query->when(
            isset($criteria['dateFrom']) && isset($criteria['dateTo']),
            function ($query) use ($criteria) {
                // Parse dates with Carbon to ensure proper format
                $dateFrom = \Carbon\Carbon::parse($criteria['dateFrom'])->startOfDay();
                $dateTo = \Carbon\Carbon::parse($criteria['dateTo'])->endOfDay();

                return $query->whereBetween('credit_applications.created_at', [$dateFrom, $dateTo]);
            }
        );

        $query->when(isset($criteria['status']), function ($query) use ($criteria) {
            return $query->where('credit_applications.status', $criteria['status']);
        });

        $query->when(isset($criteria['businessId']), function ($query) use ($criteria) {
            return $query->where('credit_applications.business_id', $criteria['businessId']);
        });

        $query->orderBy('credit_applications.created_at', 'desc');

        $query->select('credit_applications.*');

        return $query->paginate($perPage);
    }

    public function review(int $id, string $status): LoanApplication
    {
        $application = LoanApplication::findOrFail($id);
        $application->status = strtoupper($status);
        $application->save();

        return $application;
    }

    public function getApplicationsByCustomer(int $customerId)
    {
        return LoanApplication::where('customer_id', $customerId)
            ->with(['business', 'customer'])
            ->get();
    }

    const LINK_EXPIRED = 24 * 7; // 7 days

    public function verifyApplicationLink($reference)
    {
        $application = LoanApplication::where('identifier', $reference)->first();

        if (! $application) {
            throw new Exception('Provided application link does not exist');
        }

        //check if application is approved, rejected or cancelled
        if ($application->status == 'APPROVED') {
            throw new Exception('Application has been approved');
        }
        if ($application->status == 'CANCELLED') {
            throw new Exception('Application has been cancelled');
        }

        if ($application->status == 'INITIATED') {
            throw new Exception('Your Application is still being processed. Please wait');
        }


        if ($application->created_at->diffInHours(now()) > $this::LINK_EXPIRED) {
            $application->status = 'EXPIRED';
            $application->save();

            throw new Exception('Application link expired');
        }

        $vendor = $application->business;
        $customer = $application->customer;

        $loanSettings = new LoanSettings();

        $defaultBank = CreditCustomerBank::where('customer_id', $customer->id)
            ->where('business_id', $vendor->id)
            ->where('is_default', 1)
            ->where('active', 1)
            ->first();

        $user = User::find($vendor->owner_id);

        $token = $user->createToken('Full Access Token', ['full']);

        // TODO: check if vendor has a Transaction URL set, if not, use the default one

        //get vendor apikey settings
        $apiKeyData = ApiKey::where('business_id', $vendor->id)->first();

        $transactionUrl = $apiKeyData->is_test ? $apiKeyData->test_transaction_url : $apiKeyData->transaction_url;

        if ($transactionUrl) {

            // Define the data to send in the POST request
            $data = [
                'email' => $application->customer->email,
            ];

            // Send the POST request
            $response = Http::withHeaders([
                'Secret-Key' => $apiKeyData->is_test ? $apiKeyData->test_secret : $apiKeyData->secret,
                'Accept' => 'application/json',
            ])->post($transactionUrl, $data);

            // Check if the request was successful
            if ($response->successful()) {

                $contentType = $response->header('Content-Type');

                // Check if the response is JSON
                if (str_contains($contentType, 'application/json')) {
                    // Attempt to parse as JSON
                    $jsonData = $response->json();

                    if (is_array($jsonData) || is_object($jsonData)) {
                        return response()->json([
                            'status' => 'success',
                            'type' => 'json',
                            'data' => $jsonData
                        ]);
                    }
                }

                // Check if the response is a JSON file
                $contentDisposition = $response->header('Content-Disposition');
                if ($contentDisposition && str_contains($contentDisposition, 'attachment') && str_contains($contentDisposition, '.json')) {
                    // Handle as a JSON file (e.g., save or process the file content)
                    $fileContent = $response->body();
                    return response()->json([
                        'status' => 'success',
                        'type' => 'json_file',
                        'filename' => $this->getFileNameFromDisposition($contentDisposition),
                        'content' => $fileContent
                    ]);
                }


            } else {
                // Handle the error
                // return response()->json([
                //     'status' => 'error',
                //     'message' => $response->status(),
                //     'errors' => $response->json()
                // ], $response->status());
            }
        }


        // If YES, call endpint to get the transaction hisoty of the customer'
        // Secret-Key: $vendor->api_key->secret
        // payload = [
        //     'email' => $customer->emau=il
        //     ]
        // ];
        // if response is successful, return the transaction history
        // call to evaluate txn and run credit score - reuse methods
        // else fall

        // $data = [
        //     'customer' => new CreditCustomerResource($customer),
        //     'business' => new BusinessLimitedRecordResource($vendor),
        //     'interestConfig' => [
        //         'rate' => $loanSettings->lenders_interest,
        //     ],
        //     'application' => new LoadApplicationResource($application),
        //     'defaultBank' => $defaultBank, //default bank for mandate authorisation
        //     'token' => $token->accessToken
        // ];

        $data = [
            'customer' => new CreditCustomerResource($customer),
            'business' => new BusinessLimitedRecordResource($vendor),
            'interestConfig' => [
                'rate' => $loanSettings->lenders_interest,
            ],
            'application' => new LoadApplicationResource($application),
            'defaultBank' => $defaultBank, //default bank for mandate authorisation
            'token' => $token->accessToken,
            // flag to indicate if the vendor is a demo vendor
            'isDemo' => str_contains($vendor->email, 'demo'),
        ];

        return $data;
    }

    public function generateMandateForCustomerClient(Request $request)
    {

        try {

            $loanApplication = LoanApplication::where('identifier', $request->loanApplicationIdentifier)->first();

            $requestedAmount = $loanApplication->requested_amount;
            $interestRate = $loanApplication->interest_rate;
            $totalInterest = $requestedAmount * ($interestRate / 100);
            $totalRepayment = $requestedAmount + $totalInterest;
            $tenmgAmount = $totalInterest * ($loanApplication->tenmg_interest / 100);

            //update load duration
            $loanApplication->duration_in_months = $request->duration;
            $loanApplication->interest_amount = $totalInterest;
            $loanApplication->total_amount = $totalRepayment;
            $loanApplication->tenmg_amount = $tenmgAmount;
            $loanApplication->actual_interest = $totalInterest - $tenmgAmount;
            $loanApplication->save();

            $customerId = $loanApplication->customer_id;
            $businessId = $loanApplication->business_id;

            $loanDate = Carbon::now();

            $loadStartDate = $loanDate->copy()->addMonth()->endOfMonth();

            $loanEndDate = $loadStartDate->copy()->addMonthsNoOverflow((int) $request->duration - 1);

            $customer = $loanApplication->customer;

            $request->merge(['customerId' => $customerId, 'businessId' => $businessId, 'startDate' => $loadStartDate, 'endDate' => $loanEndDate, 'amount' => $totalRepayment, 'loanAppId' => $loanApplication->id, 'customer' => $customer]);

            $mandateResponseInitResponse = null;


            if (config('app.env') != 'production') {

                $uuid = Str::uuid()->toString();
                $reference = 'mr_' . $uuid;

                $mandateResponseInitResponse =  [
                    'amount' => (int)$totalRepayment / (int)$request->duration,
                    'description' => 'debit_mandate.',
                    'responseDescription' => 'Welcome to NIBSS e-mandate authentication service, a seamless and convenient authentication experience. Kindly proceed with a token payment of N50:00 into account number \"0008787867\" with GTBank. This payment will trigger the  authentication of your mandate. Thank You',
                    'startDate' => $loadStartDate,
                    'endDate' => $loanEndDate,
                    'status' => 'initiated',
                    'reference' => $reference,
                    'createdAt' => $loanDate
                ];

                $this->createOrUpdateMandateRecord($request, $mandateResponseInitResponse);

                ApiCallLog::create([
                    'business_id' => $businessId,
                    'event' => 'Mandate generated',
                    'route' => request()->path(),
                    'request' => request()->method(),
                    'response' => '200',
                    'status' => 'successful',
                ]);

                return $mandateResponseInitResponse;
            }

            $mandateResponseInitResponse = $this->fincraMandateRepository->generateMandateForCustomerClientMain($request);

            $this->createOrUpdateMandateRecord($request, $mandateResponseInitResponse);

            ApiCallLog::create([
                'business_id' => $businessId,
                'event' => 'Mandate generated',
                'route' => request()->path(),
                'request' => request()->method(),
                'response' => '200',
                'status' => 'successful',
            ]);

            return $mandateResponseInitResponse;
        } catch (\Throwable $th) {
            throw $th;
        }
    }

    public function createOrUpdateMandateRecord(Request $request, $mandate)
    {

        $debitMandate = DebitMandate::updateOrCreate(
            [
                'business_id' => $request->businessId,
                'customer_id' => $request->customerId
            ],
            [
                'amount' => (int)$request->amount / (int)$request->duration,
                'application_id' => $request->loanAppId,
                'description' => 'debit_mandate',
                'start_date' => $mandate['startDate'],
                'end_date' => $mandate['endDate'],
                'customer_account_number' => $request->customerAccountNumber,
                'customer_account_name' => $request->customerAccountName,
                'customer_bank_code' => $request->customerBankCode,
                'customer_address' => $request->customerAddress,
                'customer_email' => $request->customer->email,
                'customer_phone' => $request->customer->phone,
                'response' => json_encode($mandate),
                'response_description' => $mandate['responseDescription'],
                'status' => 'initiated',
                'reference' => $mandate['reference'],
                'currency' => 'NGN'
            ]
        );

        $customer = Customer::find($request->customerId);
        $user = request()->user();
        $business = Business::find($request->businessId);

        AuditLogService::log(
            target: $debitMandate,
            event: 'create.mandate',
            action: 'Mandate Initiated',
            description: $customer->name . " of " . $business->name . " initiated mandate",
            crud_type: 'CREATE',
            properties: []
        );
    }

    public function verifyMandateStatus($reference)
    {

        return $this->fincraMandateRepository->verifyMandateStatus($reference);
    }

    public function verifyLoanApplicationStatus($reference)
    {
        $business = request()->business;
        $application = LoanApplication::where('reference', $reference)->where('business_id', $business->id)->first();
        if (!$application) {
            throw new Exception('Loan application not found');
        }

        return $application;
    }

    public function getApplicationStatus($reference)
    {
        $application = LoanApplication::where('identifier', $reference)
            ->orWhere('reference', $reference)
            ->first();

        if (!$application) {
            throw new Exception('Provided application does not exist');
        }

        $message = '';
        $orderStatus = '';

        switch ($application->status) {
            case 'CANCELLED':
                $message = 'Your Application has been cancelled';
                $orderStatus = 'CANCELLED';
                break;
            case 'INITIATED':
                $message = 'Your Application is still being processed. Please wait';
                $orderStatus = 'PENDING PAYMENT';
                break;
            case 'PENDING_MANDATE':
                $message = 'Your Application is incomplete, provide mandate to continue';
                $orderStatus = 'PENDING PAYMENT';
                break;
            case 'REJECTED':
                $message = 'Unfortunately, your credit application was rejected.';
                $orderStatus = 'CLOSED';
                break;
            default:
                $message = 'Application has been approved';
                $orderStatus = 'PAID';
                break;
        }

        $vendor = $application->business;
        $customer = $application->customer;

        $user = User::where("id", $vendor->owner_id)->first();
        $token = $user->createToken('Full Access Token', ['full']);
        $loan = Loan::where('application_id', $application->id)->first();
        $link = "";

        if($loan){
            $link = config('app.frontend_url') . '/widgets/repayments/' . $loan?->identifier.'?token='.$token->accessToken;
        }

        $data = [
            'customer' => new CreditCustomerResource($customer),
            'business' => new BusinessLimitedRecordResource($vendor),
            'application' => new LoadApplicationResource($application),
            'message' => $message,
            'orderStatus' => $orderStatus,
            'repaymentUrl' => $link,
        ];

        // only send property for demo instance
        if(str_contains($vendor->email, 'demo')){
            $data['isDemo'] = str_contains($vendor->email, 'demo');
        }

        return $data;
    }
}
