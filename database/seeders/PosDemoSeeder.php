<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\Company;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PosDemoSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->isLocal()) {
            return;
        }

        $company = Company::query()->firstOrFail();
        $branch = Branch::query()
            ->where('company_id', $company->id)
            ->where('is_head_office', true)
            ->firstOrFail();

        $categories = [];
        foreach (['Grocery', 'Beverages', 'Household'] as $name) {
            $existing = DB::table('product_categories')
                ->where('company_id', $company->id)
                ->where('name', $name)
                ->first();
            $categories[$name] = $existing->id ?? DB::table('product_categories')->insertGetId([
                'company_id' => $company->id,
                'name' => $name,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $products = [
            ['sku' => 'GRC-1001', 'barcode' => '8901000000011', 'name' => 'Premium Basmati Rice 1kg', 'brand' => 'Riverstone', 'category' => 'Grocery', 'price' => 180, 'cost' => 145, 'unit' => 'pack', 'stock' => 42],
            ['sku' => 'GRC-1002', 'barcode' => '8901000000028', 'name' => 'Arabica Coffee 250g', 'brand' => 'Northstar', 'category' => 'Beverages', 'price' => 520, 'cost' => 410, 'unit' => 'pack', 'stock' => 14],
            ['sku' => 'DRK-2001', 'barcode' => '8901000000035', 'name' => 'Whole Milk 1L', 'brand' => 'Daily Farm', 'category' => 'Beverages', 'price' => 95, 'cost' => 78, 'unit' => 'bottle', 'stock' => 24],
            ['sku' => 'BKY-3001', 'barcode' => '8901000000042', 'name' => 'Brown Bread', 'brand' => 'Daily Farm', 'category' => 'Grocery', 'price' => 80, 'cost' => 58, 'unit' => 'pcs', 'stock' => 18],
            ['sku' => 'HOM-4001', 'barcode' => '8901000000059', 'name' => 'Dishwashing Liquid 500ml', 'brand' => 'ClearHome', 'category' => 'Household', 'price' => 145, 'cost' => 102, 'unit' => 'bottle', 'stock' => 9],
            ['sku' => 'GRC-1003', 'barcode' => '8901000000066', 'name' => 'Free-range Eggs (12 pcs)', 'brand' => 'Daily Farm', 'category' => 'Grocery', 'price' => 210, 'cost' => 175, 'unit' => 'box', 'stock' => 12],
        ];

        foreach ($products as $product) {
            $existing = DB::table('products')
                ->where('company_id', $company->id)
                ->where('sku', $product['sku'])
                ->first();
            $values = [
                'company_id' => $company->id,
                'category_id' => $categories[$product['category']],
                'name' => $product['name'],
                'brand' => $product['brand'],
                'sku' => $product['sku'],
                'barcode' => $product['barcode'],
                'unit' => $product['unit'],
                'sale_price' => $product['price'],
                'cost_price' => $product['cost'],
                'track_stock' => true,
                'is_active' => true,
                'updated_at' => now(),
            ];
            if ($existing) {
                DB::table('products')->where('id', $existing->id)->update($values);
                $productId = $existing->id;
            } else {
                $productId = DB::table('products')->insertGetId($values + [
                    'created_at' => now(),
                ]);
            }

            DB::table('branch_stocks')->updateOrInsert(
                ['company_id' => $company->id, 'branch_id' => $branch->id, 'product_id' => $productId],
                ['quantity' => $product['stock'], 'updated_at' => now(), 'created_at' => now()],
            );
        }

        foreach ([
            ['name' => 'Nusrat Jahan', 'phone' => '01700000001', 'email' => 'nusrat@example.test'],
            ['name' => 'Rahim Uddin', 'phone' => '01700000002', 'email' => 'rahim@example.test'],
        ] as $customer) {
            DB::table('customers')->updateOrInsert(
                ['company_id' => $company->id, 'phone' => $customer['phone']],
                $customer + ['is_active' => true, 'updated_at' => now(), 'created_at' => now()],
            );
        }
    }
}
