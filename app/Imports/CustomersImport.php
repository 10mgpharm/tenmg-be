<?php

namespace App\Imports;

use App\Models\Business;
use App\Models\Customer;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class CustomersImport implements ToModel, WithHeadingRow
{
    /**
     * @param  Collection  $collection
     */
    public function model(array $row)
    {
        $business = Business::where('code', $row['vendor_code'])->first();
        $count = Customer::paginate(['business_id' => $$business->id], 1)->total() + 1;
        $code = strtoupper($business->code).'-CUS-'.str_pad($count, 3, '0', STR_PAD_LEFT);

        return new Customer([
            'business_id' => $business->id,
            'name' => $row['name'],
            'email' => $row['email'],
            'phone' => $row['phone'],
            'identifier' => $code,
            'active' => $row['active'],
        ]);
    }
}
