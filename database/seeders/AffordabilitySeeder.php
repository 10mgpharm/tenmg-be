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
                'upper_bound' => 10,
                'base_amount' => 10000,
                'max_amount' => 50000,
                'active' => true,
                'is_default' => false,
            ],
            [
                'lower_bound' => 11,
                'upper_bound' => 35,
                'base_amount' => 10000,
                'max_amount' => 100000,
                'active' => true,
                'is_default' => false,
            ],
            [
                'lower_bound' => 36,
                'upper_bound' => 49,
                'base_amount' => 10000,
                'max_amount' => 150000,
                'active' => true,
                'is_default' => false,
            ],
            [
                'lower_bound' => 50,
                'upper_bound' => 65,
                'base_amount' => 10000,
                'max_amount' => 500000,
                'active' => true,
                'is_default' => false,
            ],
            [
                'lower_bound' => 66,
                'upper_bound' => 70,
                'base_amount' => 10000,
                'max_amount' => 600000,
                'active' => true,
                'is_default' => false,
            ],
            [
                'lower_bound' => 71,
                'upper_bound' => 90,
                'base_amount' => 10000,
                'max_amount' => 1000000,
                'active' => true,
                'is_default' => false,
            ],
            [
                'lower_bound' => 90,
                'upper_bound' => 100,
                'base_amount' => 10000,
                'max_amount' => 5000000,
                'active' => true,
                'is_default' => false,
            ],
        ];

        Affordability::insert($data);
    }
}
