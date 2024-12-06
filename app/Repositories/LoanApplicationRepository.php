<?php

namespace App\Repositories;

use App\Helpers\UtilityHelper;
use App\Models\LoanApplication;
use Illuminate\Http\Request;
use Laravel\Passport\Token;
use Lcobucci\JWT\Parser;

class LoanApplicationRepository
{
    public function create(array $data)
    {
        return LoanApplication::create([
            'business_id' => $data['businessId'],
            'identifier' => UtilityHelper::generateSlug('APP'),
            'customer_id' => $data['customerId'],
            'requested_amount' => $data['requestedAmount'] ?? null,
            'interest_amount' => $data['interestAmount'] ?? 0,
            'total_amount' => $data['totalAmount'] ?? 0,
            'interest_rate' => $data['interestRate'] ?? config('app.interest_rate'),
            'duration_in_months' => $data['durationInMonths'] ?? null,
            'source' => $data['source'] ?? 'DASHBOARD',
            'status' => 'PENDING',
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

    public function getAll(array $criteria, int $perPage = 15):\Illuminate\Contracts\Pagination\LengthAwarePaginator
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

    public function filter(array $criteria, int $perPage = 15):\Illuminate\Contracts\Pagination\LengthAwarePaginator
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
        if (isset($criteria['businessId'])) {
            $query->where('business_id', $criteria['businessId']);
        }

        $query->orderBy('created_at', 'desc');

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

    public function verifyApplicationLink(Request $request)
    {

        $token = $request->bearerToken();

        if($token){
            $tokenId = (new Parser())->parse($token)->getClaim('jti');
        }

        $validToken = Token::where('id', $token)
        ->where('revoked', false) // Ensure the token is not revoked
        ->first();

        return $validToken;


    }
}
