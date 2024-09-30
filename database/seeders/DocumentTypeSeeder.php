<?php

namespace Database\Seeders;

use App\Models\DocumentType;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DocumentTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $documentTypes = [
            [
                'name' => 'Profile Picture',
                'code' => 'PROFILE_PICTURE',
                'description' => 'A profile picture document for user accounts',
            ],
            [
                'name' => 'Corporate Affairs Commission',
                'code' => 'CAC',
                'description' => 'Corporate Affairs Commission (CAC) document for business registration',
            ],
        ];
        foreach($documentTypes as $documentType){
            DocumentType::firstOrCreate($documentType);
        }
    }
}
