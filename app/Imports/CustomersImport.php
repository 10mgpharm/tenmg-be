<?php

namespace App\Imports;

use App\Models\Business;
use App\Models\Customer;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\HeadingRowImport;
use Illuminate\Validation\ValidationException;

class CustomersImport implements ToModel, WithHeadingRow
{

    /**
     * Define the required headers.
     *
     * @var array
     */
    protected $requiredHeaders = ['name', 'email', 'phone', 'reference'];

    /**
     * Validate the headers before import.
     *
     * @param  string  $filePath
     * @throws \Illuminate\Validation\ValidationException
     */
    public function validateHeaders($filePath)
    {
        $headings = (new HeadingRowImport)->toArray($filePath);
        $fileHeaders = array_map('strtolower', $headings[0][0]);

        $missingHeaders = array_diff($this->requiredHeaders, $fileHeaders);

        if (!empty($missingHeaders)) {
            throw ValidationException::withMessages([
                'file' => 'The uploaded Excel file is missing the following headers: ' . implode(', ', $missingHeaders),
            ]);
        }
    }


    /**
     * Specify the heading row number.
     *
     * @return int
     */
    public function headingRow(): int
    {
        return 1;
    }

    /**
     * @param  Collection  $collection
     */
    public function model(array $row)
    {
        $vendorId = auth()->user()->id;
        $business = Business::where('owner_id', $vendorId)->where('type', 'VENDOR')->first();
        $count = Customer::count() + 1;
        $code = strtoupper($business->code).'-CUS-'.str_pad($count, 3, '0', STR_PAD_LEFT);

        return new Customer([
            'business_id' => $business->id,
            'name' => $row['name'],
            'email' => $row['email'],
            'phone' => $row['phone'],
            'identifier' => $code,
            'active' => true,
            'reference' => $row['reference']
        ]);
    }
}
