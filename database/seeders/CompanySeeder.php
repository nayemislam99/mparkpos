<?php

namespace Database\Seeders;

use App\Models\Company;
use Illuminate\Database\Seeder;

class CompanySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Company::updateOrCreate(
            ['bin' => '001234567-0101'], // UNIQUE KEY
            [
                'name' => 'Micro POS Demo Store',
                'trade_name' => 'Micro POS',
                'tin' => '123456789012',
                'business_type' => 'service',
                'email' => 'store@mpos.local',
                'phone' => '01700000000',
                'address' => 'Dhaka, Bangladesh',
                'is_active' => true,
                'created_by' => null,
            ]
        );
    }
}
