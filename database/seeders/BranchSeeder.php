<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\Company;
use Illuminate\Database\Seeder;

class BranchSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $company = Company::firstOrFail();

        Branch::updateOrCreate(
            ['company_id' => $company->id, 'code' => 'HO'],
            [
                'name' => 'Head Office',
                'is_head_office' => true,
                'branch_bin' => '001234567-0101',
                'email' => 'ho@mpos.local',
                'phone' => '01700000001',
                'address' => 'Dhaka Head Office',
                'is_active' => true,
                'created_by' => null,
            ]
        );
    }
}
