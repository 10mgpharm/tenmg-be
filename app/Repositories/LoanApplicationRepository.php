<?php

namespace App\Repositories;

use App\Helpers\UtilityHelper;
use App\Http\Resources\BusinessLimitedRecordResource;
use App\Http\Resources\CreditCustomerResource;
use App\Http\Resources\LoadApplicationResource;
use App\Models\Business;
use App\Models\CreditCustomerBank;
use App\Models\LoanApplication;
use App\Settings\CreditSettings;
use Exception;

class LoanApplicationRepository
{
    public function create(array $data)
    {
        $creditSettings = new CreditSettings;

        return LoanApplication::create([
            'business_id' => $data['businessId'],
            'identifier' => UtilityHelper::generateSlug('APP'),
            'customer_id' => $data['customerId'],
            'requested_amount' => $data['requestedAmount'] ?? null,
            'interest_amount' => $data['interestAmount'] ?? 0,
            'total_amount' => $data['totalAmount'] ?? 0,
            'interest_rate' => $creditSettings->interest_config,
            'duration_in_months' => $data['durationInMonths'] ?? null,
            'source' => $data['source'] ?? 'DASHBOARD',
            'status' => 'INITIATED',
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
        $query = LoanApplication::query();

        if (isset($criteria['search'])) {
            $query->whereHas('customer', function ($q) use ($criteria) {
                $q->where('email', 'like', '%'.$criteria['search'].'%');
            });
        }

        if (isset($criteria['search'])) {
            $query->where('identifier', 'like', '%'.$criteria['search'].'%');
        }

        if (isset($criteria['dateFrom']) && isset($criteria['dateTo'])) {
            $query->whereBetween('created_at', [$criteria['dateFrom'], $criteria['dateTo']]);
        }
        if (isset($criteria['status'])) {
            $query->where('status', $criteria['status']);
        }
        // if (isset($criteria['businessId'])) {
        //     $query->where('business_id', $criteria['businessId']);
        // }

        $query->orderBy('created_at', 'desc');

        return $query->paginate($perPage);
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

    const LINK_EXPIRED = 24;

    public function verifyApplicationLink($reference)
    {
        $application = LoanApplication::where('identifier', $reference)->first();

        if (! $application) {
            throw new Exception('Provided application link does not exist');
        }

        if ($application->created_at->diffInHours(now()) > $this::LINK_EXPIRED) {
            $application->status = 'EXPIRED';
            $application->save();

            throw new Exception('Application link expired');
        }

        $vendor = $application->business;
        $customer = $application->customer;

        $creditSettings = new CreditSettings;

        $defaultBank = CreditCustomerBank::where('customer_id', $customer->id)
            ->where('business_id', $vendor->id)
            ->where('is_default', 1)
            ->where('active', 1)
            ->first();

        $data = [
            'customer' => new CreditCustomerResource($customer),
            'business' => new BusinessLimitedRecordResource($vendor),
            'interestConfig' => [
                'rate' => $creditSettings->interest_config,
            ],
            'application' => new LoadApplicationResource($application),
            'defaultBank' => $defaultBank, //default bank for mandate authorisation
        ];

        return $data;

    }
}
