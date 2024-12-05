<?php

namespace App\Imports;

use App\Enums\BusinessStatus;
use App\Helpers\UtilityHelper;
use App\Models\Business;
use App\Models\Customer;
use Exception;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\RemembersRowNumber;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithBatchInserts;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithUpsertColumns;
use Maatwebsite\Excel\Concerns\WithUpserts;
use Maatwebsite\Excel\HeadingRowImport;
use Maatwebsite\Excel\Imports\HeadingRowFormatter;

class CustomersImport implements ToModel, WithBatchInserts, WithChunkReading, WithHeadingRow, WithUpsertColumns, WithUpserts
{
    use Importable, RemembersRowNumber;

    private $vendorBusiness;

    protected $requiredHeaders = ['name', 'email', 'phone', 'reference'];

    /**
     * Undocumented function
     */
    public function __construct()
    {
        $vendorId = auth()->user()->id;
        $this->vendorBusiness = Business::where('owner_id', $vendorId)->where('type', 'VENDOR')->first();
        if (! $this->vendorBusiness) {
            throw new Exception('Invalid request: Vendor business not found');
        }

        // TODO: Uncomment this block once admin business approval is implemented
        // if(!$this->vendorBusiness->status !== BusinessStatus::VERIFIED)
        //     throw new Exception('Pending Business Status Approval. Please try again later');

        HeadingRowFormatter::default('none');
    }

    /**
     * @param  Collection  $collection
     * @return \Illuminate\Database\Eloquent\Model|null
     */
    public function model(array $row)
    {
        if (! array_filter($row)) {
            return null;
        }

        //Excel Columns: Name, Email, Phone, Reference
        $currentRowNumber = $this->getRowNumber();
        if ($currentRowNumber == 1) {
            return null;
        }

        // Validate email
        $email = trim($row['EMAIL']);
        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw throw new Exception("Invalid email format at row {$currentRowNumber}: {$email}");
        }

        // Validate phone number
        $phone = trim($row['PHONE']);
        if (! preg_match('/^\+?[1-9]\d{1,14}$/', $phone)) {
            throw throw new Exception("Invalid phone number format at row {$currentRowNumber}: {$phone}");
        }

        $code = UtilityHelper::generateSlug('CUS');

        return new Customer([
            'business_id' => $this->vendorBusiness?->id,
            'name' => trim($row['NAME']),
            'email' => trim($row['EMAIL']),
            'phone' => trim($row['PHONE']),
            'identifier' => $code,
            'active' => true,
            'reference' => trim($row['REFERENCE']),
        ]);
    }

    /**
     * specify unique columns
     *
     * @return string|array
     */
    public function uniqueBy()
    {
        return ['identifier', 'email', 'business_id'];
    }

    /**
     * specify column to be update
     * if unique value exist
     *
     * @return array
     */
    public function upsertColumns()
    {
        return ['name', 'phone', 'reference'];
    }

    /**
     * heading row
     */
    public function headingRow(): int
    {
        return 1;
    }

    /**
     * import batch by batch
     */
    public function batchSize(): int
    {
        return 1000;
    }

    /**
     * read file chunk to optimze memory usage
     */
    public function chunkSize(): int
    {
        return 1000;
    }

    /**
     * Validate the headers before import.
     *
     * @param  string  $filePath
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function validateHeaders($filePath)
    {
        $headings = (new HeadingRowImport)->toArray($filePath);
        $fileHeaders = array_map('strtolower', $headings[0][0]);

        $missingHeaders = array_diff($this->requiredHeaders, $fileHeaders);

        if (! empty($missingHeaders)) {
            throw ValidationException::withMessages([
                'file' => 'The uploaded Excel file is missing the following headers: '.implode(', ', $missingHeaders),
            ]);
        }
    }
}
