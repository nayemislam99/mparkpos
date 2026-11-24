<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\Company;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\UnitType;
use App\Models\User;
use App\Models\Variation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ProductManagementAndBarcodeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->app['env'] = 'local';
        $this->seed();
        $this->app['env'] = 'testing';
    }

    public function test_store_admin_can_view_all_product_submodule_pages(): void
    {
        $admin = User::where('email', 'admin@mpos.local')->firstOrFail();

        $this->actingAs($admin)->get(route('products.index'))->assertOk()->assertSee('All Products');
        $this->actingAs($admin)->get(route('products.create'))->assertOk()->assertSee('Add product');
        $this->actingAs($admin)->get(route('brands.index'))->assertOk()->assertSee('Brands');
        $this->actingAs($admin)->get(route('categories.index'))->assertOk()->assertSee('Categories');
        $this->actingAs($admin)->get(route('unit-types.index'))->assertOk()->assertSee('Unit Types');
        $this->actingAs($admin)->get(route('variations.index'))->assertOk()->assertSee('Variations');
        $this->actingAs($admin)->get(route('barcodes.print'))->assertOk()->assertSee('Print product barcode');
        $this->actingAs($admin)->get(route('barcodes.report'))->assertOk()->assertSee('Barcode Print Report');
    }

    public function test_universal_barcode_search_api_supports_scanner_and_multi_tenancy(): void
    {
        $admin = User::where('email', 'admin@mpos.local')->firstOrFail();

        // 1. Create product for Company 1
        $prod1 = Product::create([
            'company_id' => $admin->company_id,
            'name' => 'Yamaha Engine Oil Synthetic',
            'sku' => 'YMH-ENG-99',
            'barcode' => '8901234567890',
            'sale_price' => 750,
            'cost_price' => 600,
            'track_stock' => true,
            'is_active' => true,
        ]);

        // 2. Create product for another Company
        $company2Id = DB::table('companies')->insertGetId([
            'name' => 'Competitor Shop',
            'bin' => '9988776655-0001',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Product::create([
            'company_id' => $company2Id,
            'name' => 'Yamaha Secret Oil',
            'sku' => 'SEC-001',
            'barcode' => '8909999999999',
            'sale_price' => 999,
            'cost_price' => 800,
            'is_active' => true,
        ]);

        // 3. Search exact barcode (scanner mode)
        $resExact = $this->actingAs($admin)
            ->getJson('/dashboard/api/barcode-search?exact=1&q=8901234567890')
            ->assertOk();

        $this->assertEquals(1, $resExact->json('count'));
        $this->assertEquals('Yamaha Engine Oil Synthetic', $resExact->json('data.0.name'));

        // 4. Multi-tenancy: Cannot find Competitor's product
        $resComp = $this->actingAs($admin)
            ->getJson('/dashboard/api/barcode-search?exact=1&q=8909999999999')
            ->assertOk();

        $this->assertEquals(0, $resComp->json('count'));

        // 5. Fuzzy search by name
        $resFuzzy = $this->actingAs($admin)
            ->getJson('/dashboard/api/barcode-search?q=Synthetic')
            ->assertOk();

        $this->assertTrue(collect($resFuzzy->json('data'))->contains('name', 'Yamaha Engine Oil Synthetic'));
    }

    public function test_brands_crud_and_status_toggle(): void
    {
        $admin = User::where('email', 'admin@mpos.local')->firstOrFail();

        // Create Brand
        $this->actingAs($admin)
            ->post(route('brands.store'), ['name' => 'Honda Moto'])
            ->assertRedirect(route('brands.index'));

        $brand = Brand::where('name', 'Honda Moto')->firstOrFail();
        $this->assertEquals($admin->company_id, $brand->company_id);
        $this->assertTrue($brand->is_active);

        // Toggle Status
        $this->actingAs($admin)
            ->post(route('brands.toggle', $brand))
            ->assertRedirect(route('brands.index'));

        $this->assertFalse($brand->fresh()->is_active);
    }

    public function test_categories_crud(): void
    {
        $admin = User::where('email', 'admin@mpos.local')->firstOrFail();

        $this->actingAs($admin)
            ->post(route('categories.store'), ['name' => 'Brake Systems'])
            ->assertRedirect(route('categories.index'));

        $cat = ProductCategory::where('name', 'Brake Systems')->firstOrFail();
        $this->assertEquals($admin->company_id, $cat->company_id);
    }

    public function test_unit_types_crud(): void
    {
        $admin = User::where('email', 'admin@mpos.local')->firstOrFail();

        $this->actingAs($admin)
            ->post(route('unit-types.store'), ['name' => 'Packets', 'code' => 'pkt'])
            ->assertRedirect(route('unit-types.index'));

        $unit = UnitType::where('name', 'Packets')->firstOrFail();
        $this->assertEquals('pkt', $unit->code);
    }

    public function test_variations_crud_and_add_item(): void
    {
        $admin = User::where('email', 'admin@mpos.local')->firstOrFail();

        $this->actingAs($admin)
            ->post(route('variations.store'), [
                'name' => 'Color',
                'items_input' => 'Red, Blue, Green',
            ])
            ->assertRedirect(route('variations.index'));

        $var = Variation::where('name', 'Color')->firstOrFail();
        $this->assertEquals(['Red', 'Blue', 'Green'], $var->items);

        // Add single item
        $this->actingAs($admin)
            ->post(route('variations.add-item', $var), ['item' => 'Yellow'])
            ->assertRedirect(route('variations.index'));

        $this->assertContains('Yellow', $var->fresh()->items);
    }

    public function test_product_creation_with_barcode_and_alternative_pricing(): void
    {
        $admin = User::where('email', 'admin@mpos.local')->firstOrFail();

        $this->actingAs($admin)
            ->post(route('products.store'), [
                'name' => 'Helmet Pro X',
                'cost_price' => 1200,
                'sale_price' => 1800,
                'sku' => 'HLM-900',
                'barcode' => '890123999999',
                'alert_quantity' => 4,
                'discount_status' => 'flat',
                'alternative_labels' => ['Wholesale', 'Dealer'],
                'alternative_prices' => [1500, 1400],
            ])
            ->assertRedirect(route('products.index'));

        $product = Product::where('sku', 'HLM-900')->firstOrFail();
        $this->assertEquals($admin->company_id, $product->company_id);
        $this->assertEquals(1800, (float) $product->sale_price);
        $this->assertCount(2, $product->alternative_prices);
        $this->assertEquals('Wholesale', $product->alternative_prices[0]['label']);
    }

    public function test_barcode_print_logging(): void
    {
        $admin = User::where('email', 'admin@mpos.local')->firstOrFail();
        $product = Product::firstOrFail();

        $this->actingAs($admin)
            ->postJson(route('barcodes.log-print'), [
                'items' => [
                    [
                        'product_id' => $product->id,
                        'quantity' => 10,
                        'price' => $product->sale_price,
                        'lot' => 'LOT-2026-A',
                    ],
                ],
            ])
            ->assertOk()
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('barcode_print_logs', [
            'company_id' => $admin->company_id,
            'product_id' => $product->id,
            'quantity' => 10,
            'lot' => 'LOT-2026-A',
        ]);
    }

    public function test_custom_data_table_endpoints_for_products_submodules(): void
    {
        $admin = User::where('email', 'admin@mpos.local')->firstOrFail();

        // 1. Brands DataTable endpoint
        $resBrand = $this->actingAs($admin)
            ->getJson('/dashboard/dt/brands')
            ->assertOk();
        $this->assertArrayHasKey('draw', $resBrand->json());
        $this->assertArrayHasKey('recordsTotal', $resBrand->json());
        $this->assertArrayHasKey('data', $resBrand->json());

        // 2. Categories DataTable endpoint
        $resCat = $this->actingAs($admin)
            ->getJson('/dashboard/dt/categories')
            ->assertOk();
        $this->assertArrayHasKey('draw', $resCat->json());
        $this->assertArrayHasKey('data', $resCat->json());

        // 3. Unit Types DataTable endpoint
        $resUnit = $this->actingAs($admin)
            ->getJson('/dashboard/dt/unit_types')
            ->assertOk();
        $this->assertArrayHasKey('draw', $resUnit->json());
        $this->assertArrayHasKey('data', $resUnit->json());
    }

    public function test_sidebar_menu_active_state_and_bilingual_support(): void
    {
        $admin = User::where('email', 'admin@mpos.local')->firstOrFail();

        // 1. Visit brands index in English
        $resEn = $this->actingAs($admin)->get(route('brands.index'))->assertOk();
        // Check that products nav-group has show and brands link has active
        $contentEn = $resEn->getContent();
        $this->assertStringContainsString('class="nav-group show"', $contentEn);
        $this->assertStringContainsString('href="http://localhost/dashboard/brands"', $contentEn);

        // 2. Switch to Bengali and verify
        $this->withSession(['locale' => 'bn']);
        $resBn = $this->actingAs($admin)->get(route('brands.index'))->assertOk();
        $contentBn = $resBn->getContent();
        $this->assertStringContainsString('ব্র্যান্ডস', $contentBn);
        $this->assertStringContainsString('পণ্যসমূহ', $contentBn);
        $this->assertStringContainsString('class="nav-group show"', $contentBn);
    }
}
