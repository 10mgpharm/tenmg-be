<?php

namespace Database\Seeders;

use App\Models\Affordability;
use Illuminate\Database\Seeder;

class AffordabilitySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $data = [
            [
                'lower_bound' => 0,
                'upper_bound' => 34,
                'base_amount' => 0,
                'max_amount' => 0,
                'category' => 'D',
                'active' => true,
                'is_default' => false,
            ],
            [
                'lower_bound' => 35,
                'upper_bound' => 49,
                'base_amount' => 10000,
                'max_amount' => 50000,
                'category' => 'C',
                'active' => true,
                'is_default' => false,
            ],
            [
                'lower_bound' => 50,
                'upper_bound' => 74,
                'base_amount' => 10000,
                'max_amount' => 100000,
                'category' => 'B',
                'active' => true,
                'is_default' => false,
            ],
            [
                'lower_bound' => 75,
                'upper_bound' => 100,
                'base_amount' => 10000,
                'max_amount' => 150000,
                'category' => 'A',
                'active' => true,
                'is_default' => false,
            ],
        ];

        Affordability::insert($data);
    }
}
