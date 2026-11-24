<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\UnitType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class CsvImportExportAndDarkModeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->app['env'] = 'local';
        $this->seed();
        $this->app['env'] = 'testing';
    }

    public function test_brand_csv_export_sample_and_import(): void
    {
        $admin = User::where('email', 'admin@mpos.local')->firstOrFail();

        // 1. Export CSV
        $exportResponse = $this->actingAs($admin)->get(route('brands.export.csv'));
        $exportResponse->assertOk();
        $this->assertStringContainsString('text/csv', (string) $exportResponse->headers->get('Content-Type'));

        // 2. Sample Template
        $sampleResponse = $this->actingAs($admin)->get(route('brands.sample.csv'));
        $sampleResponse->assertOk();

        // 3. Import CSV
        $csvContent = "name,is_active\nNike Sports,1\nAdidas Shoes,1\n";
        $file = UploadedFile::fake()->createWithContent('brands.csv', $csvContent);

        $importResponse = $this->actingAs($admin)->post(route('brands.import.csv'), [
            'csv_file' => $file,
        ]);

        $importResponse->assertRedirect(route('brands.index'));
        $importResponse->assertSessionHas('success');

        $this->assertDatabaseHas('brands', [
            'company_id' => $admin->company_id,
            'name' => 'Nike Sports',
            'is_active' => true,
        ]);

        $this->assertDatabaseHas('brands', [
            'company_id' => $admin->company_id,
            'name' => 'Adidas Shoes',
            'is_active' => true,
        ]);
    }

    public function test_category_csv_export_sample_and_import(): void
    {
        $admin = User::where('email', 'admin@mpos.local')->firstOrFail();

        // 1. Export CSV
        $exportResponse = $this->actingAs($admin)->get(route('categories.export.csv'));
        $exportResponse->assertOk();

        // 2. Sample Template
        $sampleResponse = $this->actingAs($admin)->get(route('categories.sample.csv'));
        $sampleResponse->assertOk();

        // 3. Import CSV
        $csvContent = "name,is_active\nMotorcycle Helmets,1\nRiding Jackets,0\n";
        $file = UploadedFile::fake()->createWithContent('categories.csv', $csvContent);

        $importResponse = $this->actingAs($admin)->post(route('categories.import.csv'), [
            'csv_file' => $file,
        ]);

        $importResponse->assertRedirect(route('categories.index'));
        $importResponse->assertSessionHas('success');

        $this->assertDatabaseHas('product_categories', [
            'company_id' => $admin->company_id,
            'name' => 'Motorcycle Helmets',
            'is_active' => true,
        ]);

        $this->assertDatabaseHas('product_categories', [
            'company_id' => $admin->company_id,
            'name' => 'Riding Jackets',
            'is_active' => false,
        ]);
    }

    public function test_unit_type_csv_export_sample_and_import(): void
    {
        $admin = User::where('email', 'admin@mpos.local')->firstOrFail();

        // 1. Export CSV
        $exportResponse = $this->actingAs($admin)->get(route('unit-types.export.csv'));
        $exportResponse->assertOk();

        // 2. Sample Template
        $sampleResponse = $this->actingAs($admin)->get(route('unit-types.sample.csv'));
        $sampleResponse->assertOk();

        // 3. Import CSV
        $csvContent = "name,code,is_active\nDozen,dz,1\nMilliliter,ml,1\n";
        $file = UploadedFile::fake()->createWithContent('units.csv', $csvContent);

        $importResponse = $this->actingAs($admin)->post(route('unit-types.import.csv'), [
            'csv_file' => $file,
        ]);

        $importResponse->assertRedirect(route('unit-types.index'));
        $importResponse->assertSessionHas('success');

        $this->assertDatabaseHas('unit_types', [
            'company_id' => $admin->company_id,
            'name' => 'Dozen',
            'code' => 'dz',
            'is_active' => true,
        ]);
    }

    public function test_product_bulk_upload_via_csv(): void
    {
        $admin = User::where('email', 'admin@mpos.local')->firstOrFail();

        // 1. Upload CSV View
        $viewResponse = $this->actingAs($admin)->get(route('products.csv-upload'));
        $viewResponse->assertOk();
        $viewResponse->assertSee('Upload Products By CSV');

        // 2. Sample Template
        $sampleResponse = $this->actingAs($admin)->get(route('products.sample.csv'));
        $sampleResponse->assertOk();

        // 3. Import CSV
        $csvContent = "name,barcode,sku,brand_name,category_name,unit_name,cost_price,sale_price,alert_quantity,is_active\n"
            . "Steelbird Helmet,8909988776655,SB-HELM-01,Steelbird,Helmets,Piece,1800,2400,5,1\n";
        $file = UploadedFile::fake()->createWithContent('products.csv', $csvContent);

        $importResponse = $this->actingAs($admin)->post(route('products.import.csv'), [
            'csv_file' => $file,
        ]);

        $importResponse->assertRedirect(route('products.index'));
        $importResponse->assertSessionHas('success');

        $this->assertDatabaseHas('products', [
            'company_id' => $admin->company_id,
            'name' => 'Steelbird Helmet',
            'barcode' => '8909988776655',
            'sku' => 'SB-HELM-01',
            'cost_price' => 1800,
            'sale_price' => 2400,
        ]);

        $this->assertDatabaseHas('brands', [
            'company_id' => $admin->company_id,
            'name' => 'Steelbird',
        ]);

        $this->assertDatabaseHas('product_categories', [
            'company_id' => $admin->company_id,
            'name' => 'Helmets',
        ]);

        $this->assertDatabaseHas('unit_types', [
            'company_id' => $admin->company_id,
            'name' => 'Piece',
        ]);
    }

    public function test_all_list_pages_render_and_have_dark_mode_compatible_structure(): void
    {
        $admin = User::where('email', 'admin@mpos.local')->firstOrFail();

        $routes = [
            route('brands.index'),
            route('categories.index'),
            route('unit-types.index'),
            route('products.index'),
            route('variations.index'),
            route('users.index'),
            route('crm.index'),
        ];

        foreach ($routes as $url) {
            $response = $this->actingAs($admin)->get($url);
            $response->assertOk();
            // Ensure none of them contain hardcoded card-header bg-white
            $content = $response->getContent();
            $this->assertStringNotContainsString('card-header bg-white', $content, "Route {$url} contains card-header bg-white");
        }
    }
}
