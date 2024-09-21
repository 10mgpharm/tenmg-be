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

    public function updateOrCreate(array $where, array $data)
    {
        return Loan::updateOrCreate($where, $data);
    }

    public function fetchLoansByCustomerId(int $customerId): Collection
    {
        return Loan::where('customer_id', $customerId)->get();
    }

    public function findById(int $id): ?Loan
    {
        return Loan::whereId($id)->with('repayment_schedule')->first();
    }

    public function update(int $id, array $data): bool
    {
        return Loan::where('id', $id)->update($data);
    }

    public function fetchAllLoans(): Collection
    {
        return Loan::all();
    }
}
