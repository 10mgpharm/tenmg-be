<?php

namespace App\Repositories;

use App\Models\Loan;
use Illuminate\Database\Eloquent\Collection;

class LoanRepository
{
    public function store(array $data)
    {
        return Loan::create($data);
    }

    public function fetchLoansByCustomerId(int $customerId): Collection
    {
        return Loan::where('customer_id', $customerId)->get();
    }
}
