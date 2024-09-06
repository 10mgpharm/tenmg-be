<?php

namespace App\Exports;

use App\Models\Customer;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class CustomersExport implements FromCollection, WithHeadings
{
    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        return Customer::all(['name', 'email', 'phone', 'identifier', 'active', 'created_at']);
    }

    public function headings(): array
    {
        return [
            'Name',
            'Email',
            'Phone',
            'Identifier',
            'Active',
            'Created At',
        ];
    }
}
