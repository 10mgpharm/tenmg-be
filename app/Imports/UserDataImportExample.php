<?php

namespace App\Imports;

use App\Models\Business;
use App\Models\BusinessUser;
use App\Models\Role;
use App\Models\User;
use Exception;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\RemembersRowNumber;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithBatchInserts;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithUpsertColumns;
use Maatwebsite\Excel\Concerns\WithUpserts;
use Maatwebsite\Excel\Imports\HeadingRowFormatter;

class UserDataImport implements ToModel, WithBatchInserts, WithChunkReading, WithHeadingRow, WithUpsertColumns, WithUpserts
{
    use Importable, RemembersRowNumber;

    private $companySettings;

    /**
     * asset import dependency
     *
     * @param  Facility  $facility
     * @param  AssetReportService  $assetReportService
     */
    public function __construct(public Business $business)
    {
        HeadingRowFormatter::default('none');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Model|null
     */
    public function model(array $row)
    {
        if (! array_filter($row)) {
            return null;
        }

        //Excel Columns: Timestamp, Name, Email, Phone, Status, Role
        $currentRowNumber = $this->getRowNumber();
        if ($currentRowNumber == 1) {
            return null;
        }

        if (! $this->business) {
            return throw new Exception('Invalid request: Business not specified');
        }

        $roleName = trim($row['Role']);
        $roleName = preg_replace('/\x{2060}/u', '', $roleName);

        $role = Role::where('name', 'like', "%$roleName%")->first();

        $user = User::create([
            'name' => $row['Name'],
            'email' => $row['Email'],
            'active' => $row['Status'] == 'Active' ? true : false,
        ]);
        if ($role) {
            $user->assignRole($role);
        }

        $busessUser = new BusinessUser;
        $busessUser->user_id = $user->id;
        $busessUser->business_id = $this->business->id;
        $busessUser->save();

        return $user;
    }

    /**
     * specify unique columns
     *
     * @return string|array
     */
    public function uniqueBy()
    {
        return ['email'];
    }

    /**
     * specify column to be update
     * if unique value exist
     *
     * @return array
     */
    public function upsertColumns()
    {
        return [];
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
}
