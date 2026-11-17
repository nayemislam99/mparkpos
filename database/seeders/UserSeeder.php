<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\Company;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->isLocal()) {
            return;
        }

        $company = Company::firstOrFail();
        $branch = Branch::where('company_id', $company->id)
            ->where('is_head_office', true)
            ->firstOrFail();

        $users = [
            [
                'name' => 'Super Admin',
                'email' => 'admin@mpos.local',
                'type' => 'super_admin',
            ],
            [
                'name' => 'Store Admin',
                'email' => 'manager@mpos.local',
                'type' => 'store_admin',
            ],
            [
                'name' => 'Cashier',
                'email' => 'staff@mpos.local',
                'type' => 'cashier',
            ],
            [
                'name' => 'Store Manager',
                'email' => 'store.manager@mpos.local',
                'type' => 'manager',
            ],
            [
                'name' => 'Inventory Manager',
                'email' => 'inventory.manager@mpos.local',
                'type' => 'inventory_manager',
            ],
            [
                'name' => 'Store Auditor',
                'email' => 'auditor@mpos.local',
                'type' => 'auditor',
            ],
        ];

        foreach ($users as $user) {
            User::updateOrCreate(
                ['company_id' => $company->id, 'email' => $user['email']],
                [
                    'branch_id' => $branch->id,
                    'name' => $user['name'],
                    'password' => Hash::make('12345678'),
                    'type' => $user['type'],
                    'is_active' => true,
                    'force_password_change' => true,
                    'created_by' => null,
                ]
            );
        }
    }
}
