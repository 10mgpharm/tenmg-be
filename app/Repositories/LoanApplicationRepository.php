<?php

namespace App\Repositories;

use App\Helpers\UtilityHelper;
use App\Http\Controllers\API\Credit\LoanOfferController;
use App\Http\Resources\BusinessLimitedRecordResource;
use App\Http\Resources\CreditCustomerResource;
use App\Http\Resources\LoadApplicationResource;
use App\Models\Business;
use App\Models\CreditCustomerBank;
use App\Models\CreditLenderPreference;
use App\Models\CreditLendersWallet;
use App\Models\CreditOffer;
use App\Models\Customer;
use App\Models\DebitMandate;
use App\Models\LoanApplication;
use App\Services\ActivityLogService;
use App\Services\AuditLogService;
use App\Settings\CreditSettings;
use App\Settings\LoanSettings;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class LoanApplicationRepository
{

    function __construct(private FincraMandateRepository $fincraMandateRepository, private ActivityLogService $activityLogService){

    }

    public function create(array $data)
    {
        $loanSettings = new LoanSettings();

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
                $q->where('email', 'like', '%'.$searchTerm.'%')->orWhere('name', 'like', '%'.$searchTerm.'%');
            });
        }

        if (isset($criteria['dateFrom']) && isset($criteria['dateTo'])) {
            $query->whereBetween('created_at', [$criteria['dateFrom'], $criteria['dateTo']]);
        }
        if (isset($criteria['status'])) {
            $query->where('status', $criteria['status']);
        }

        if($business->type == "LENDER"){

            $ignoredIds = CreditLenderPreference::where('lender_id', $business->id)->first()->ignored_applications_id;
            $query->when(!empty($ignoredIds ?? []), function ($querySub) use ($ignoredIds) {
                $querySub->whereNotIn('id', $ignoredIds);
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
        $depositWallet = CreditLendersWallet::where('lender_id', $business_id)->where('type', 'deposit')->first();;
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

    const LINK_EXPIRED = 24*7; // 7 days

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

        $data = [
            'customer' => new CreditCustomerResource($customer),
            'business' => new BusinessLimitedRecordResource($vendor),
            'interestConfig' => [
                'rate' => $loanSettings->lenders_interest,
            ],
            'application' => new LoadApplicationResource($application),
            'defaultBank' => $defaultBank, //default bank for mandate authorisation
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

            //update load duration
            $loanApplication->duration_in_months = $request->duration;
            $loanApplication->interest_amount = $totalInterest;
            $loanApplication->total_amount = $totalRepayment;
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
                    'amount' => (int)$totalRepayment/(int)$request->duration,
                    'description' => 'debit_mandate.',
                    'responseDescription' => 'Welcome to NIBSS e-mandate authentication service, a seamless and convenient authentication experience. Kindly proceed with a token payment of N50:00 into account number \"0008787867\" with GTBank. This payment will trigger the  authentication of your mandate. Thank You',
                    'startDate' => $loadStartDate,
                    'endDate' => $loanEndDate,
                    'status' => 'initiated',
                    'reference' => $reference,
                    'createdAt' => $loanDate
                ];

                $this->createOrUpdateMandateRecord($request, $mandateResponseInitResponse);
                return $mandateResponseInitResponse;
            }

            $mandateResponseInitResponse = $this->fincraMandateRepository->generateMandateForCustomerClientMain($request);

            $this->createOrUpdateMandateRecord($request, $mandateResponseInitResponse);
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
                'amount' => (int)$request->amount/(int)$request->duration,
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
            description: $customer->name." of ".$business->name." initiated mandate",
            crud_type: 'CREATE',
            properties: []
        );

    }

    public function verifyMandateStatus($reference)
    {

        return $this->fincraMandateRepository->verifyMandateStatus($reference);
    }


}
