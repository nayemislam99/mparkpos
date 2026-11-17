<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    /**
     * A basic test example.
     */
    public function test_the_homepage_redirects_to_login(): void
    {
        $response = $this->get('/');

        $response->assertRedirect('/login');
    }

    public function test_local_login_page_shows_prefilled_demo_credentials(): void
    {
        $this->app['env'] = 'local';

        $this->get('/login')
            ->assertOk()
            ->assertSee('value="admin@mpos.local"', false)
            ->assertSee('value="12345678"', false)
            ->assertSee('Local demo account');
    }

    public function test_non_local_login_page_does_not_expose_demo_credentials(): void
    {
        $this->app['env'] = 'production';

        $this->get('/login')
            ->assertOk()
            ->assertDontSee('value="admin@mpos.local"', false)
            ->assertDontSee('value="12345678"', false)
            ->assertDontSee('Local demo account');
    }

    public function test_local_demo_account_can_sign_in(): void
    {
        $this->app['env'] = 'local';
        $this->seed();
        $this->app['env'] = 'testing';

        $this->post('/login', [
            'email' => 'admin@mpos.local',
            'password' => '12345678',
        ])
            ->assertRedirect('/dashboard');

        $this->assertAuthenticatedAs(User::where('email', 'admin@mpos.local')->first());
    }

    public function test_dashboard_shows_pos_preview_tables_to_authenticated_users(): void
    {
        $this->app['env'] = 'local';
        $this->seed();
        $this->app['env'] = 'testing';

        $user = User::where('email', 'admin@mpos.local')->firstOrFail();

        $this->actingAs($user)
            ->get('/dashboard')
            ->assertOk()
            ->assertSee('Good morning, '.explode(' ', $user->name)[0])
            ->assertSee('Recent sales')
            ->assertSee('Low stock')
            ->assertSee('SALE-1048')
            ->assertSee('sample UI data only')
            ->assertSee('Users');

        $this->get(route('users.index'))
            ->assertOk()
            ->assertSee('nav-link nav-group-toggle active', false)
            ->assertSee('aria-expanded="true"', false)
            ->assertSee('nav-link active', false);
    }

    public function test_dashboard_requires_authentication(): void
    {
        $this->get('/dashboard')->assertRedirect('/login');
    }

    public function test_datatable_accepts_empty_search_and_filter_values(): void
    {
        $this->app['env'] = 'local';
        $this->seed();
        $this->app['env'] = 'testing';

        $user = User::where('email', 'admin@mpos.local')->firstOrFail();
        $columns = [[
            'data' => '',
            'name' => '',
            'searchable' => 'false',
            'orderable' => 'false',
            'search' => ['value' => '', 'regex' => 'false'],
        ], ...array_map(fn (string $column): array => [
            'data' => $column,
            'name' => $column,
            'searchable' => 'true',
            'orderable' => 'true',
            'search' => ['value' => '', 'regex' => 'false'],
        ], ['id', 'name', 'email', 'type', 'is_active'])];

        $this->actingAs($user)
            ->getJson('/dashboard/dt/users?'.http_build_query([
                'draw' => 1,
                'start' => 0,
                'length' => 25,
                'search' => ['value' => '', 'regex' => 'false'],
                'order' => [['column' => 1, 'dir' => 'asc']],
                'columns' => $columns,
                'filters' => [
                    'type' => '',
                    'is_active' => '',
                    'from_date' => '',
                    'to_date' => '',
                ],
            ]))
            ->assertOk()
            ->assertJsonPath('draw', 1)
            ->assertJsonPath('recordsTotal', 6)
            ->assertJsonPath('data.0.DT_RowIndex', 1)
            ->assertJsonPath('data.0.sn', 1);
    }

    public function test_users_datatable_searches_only_the_signed_in_company(): void
    {
        $this->app['env'] = 'local';
        $this->seed();
        $this->app['env'] = 'testing';

        $user = User::where('email', 'admin@mpos.local')->firstOrFail();
        $otherCompanyId = DB::table('companies')->insertGetId([
            'name' => 'Other Store',
            'bin' => '987654321-0101',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('users')->insert([
            'company_id' => $otherCompanyId,
            'name' => 'Other Store User',
            'email' => 'other@mpos.local',
            'password' => bcrypt('12345678'),
            'type' => 'cashier',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $query = http_build_query([
            'draw' => 2,
            'start' => 0,
            'length' => 10,
            'search' => ['value' => 'auditor'],
            'order' => [['column' => 1, 'dir' => 'asc']],
            'columns' => [['data' => 'id'], ['data' => 'name']],
        ]);

        $this->actingAs($user)
            ->getJson("/dashboard/dt/users?{$query}")
            ->assertOk()
            ->assertJsonPath('draw', 2)
            ->assertJsonPath('recordsTotal', 6)
            ->assertJsonPath('recordsFiltered', 1)
            ->assertJsonPath('data.0.email', 'auditor@mpos.local');
    }

    public function test_unknown_datatable_key_returns_not_found(): void
    {
        $this->actingAs(new User([
            'company_id' => 1,
        ]))
            ->getJson('/dashboard/dt/unknown')
            ->assertNotFound()
            ->assertJsonPath('error', 'Unknown table.');
    }

    public function test_configured_filters_are_applied_to_the_table_query(): void
    {
        $this->app['env'] = 'local';
        $this->seed();
        $this->app['env'] = 'testing';

        $user = User::where('email', 'admin@mpos.local')->firstOrFail();

        $this->actingAs($user)
            ->getJson('/dashboard/dt/users?'.http_build_query([
                'filters' => ['is_active' => '0'],
            ]))
            ->assertOk()
            ->assertJsonPath('recordsTotal', 6)
            ->assertJsonPath('recordsFiltered', 0);
    }

    public function test_invalid_date_filters_are_rejected(): void
    {
        $this->app['env'] = 'local';
        $this->seed();
        $this->app['env'] = 'testing';

        $user = User::where('email', 'admin@mpos.local')->firstOrFail();

        $this->actingAs($user)
            ->getJson('/dashboard/dt/users?'.http_build_query([
                'filters' => ['from_date' => '2026-02-31'],
            ]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('created_between');
    }

    public function test_cashier_cannot_read_the_users_table(): void
    {
        $this->app['env'] = 'local';
        $this->seed();
        $this->app['env'] = 'testing';

        $cashier = User::where('email', 'staff@mpos.local')->firstOrFail();

        $this->actingAs($cashier)
            ->getJson('/dashboard/dt/users')
            ->assertForbidden();
    }

    public function test_cashier_sees_only_menu_sections_allowed_by_role(): void
    {
        $this->app['env'] = 'local';
        $this->seed();
        $this->app['env'] = 'testing';

        $cashier = User::where('email', 'staff@mpos.local')->firstOrFail();

        $this->actingAs($cashier)
            ->get('/dashboard')
            ->assertOk()
            ->assertSee('Dashboard')
            ->assertSee('Products')
            ->assertSee('Sales')
            ->assertSee('href="'.route('pos.terminal').'"', false)
            ->assertDontSee('Inventory')
            ->assertDontSee('Users')
            ->assertDontSee('Reports');

        $this->get(route('users.index'))->assertForbidden();

        $this->get(route('pos.terminal'))
            ->assertOk()
            ->assertSee('id="posTerminal"', false)
            ->assertSee('class="pos-toolbar"', false)
            ->assertSee('id="posWalkInCustomer"', false)
            ->assertSee('id="posCategoryFilter"', false)
            ->assertSee('id="posBrandFilter"', false)
            ->assertSee('id="posBarcodeSearch"', false)
            ->assertSee('aria-label="Bill summary and payment"', false)
            ->assertSee('id="posPaymentList"', false)
            ->assertSee('data-payment-method="mobile_banking"', false)
            ->assertSee('Search registered customers by name or phone')
            ->assertSee('Received now')
            ->assertDontSee('id="posCustomer"', false)
            ->assertDontSee('id="sidebar"', false)
            ->assertDontSee('<header class="header', false);
    }

    public function test_auditor_role_can_read_users_but_cannot_manage_them(): void
    {
        $this->app['env'] = 'local';
        $this->seed();
        $this->app['env'] = 'testing';

        $auditor = User::where('email', 'auditor@mpos.local')->firstOrFail();

        $this->actingAs($auditor)
            ->get(route('users.index'))
            ->assertOk()
            ->assertSee('Users')
            ->assertSee('Manage your store team and access')
            ->assertSee('Store admin')
            ->assertSee('Store manager')
            ->assertSee('Cashier')
            ->assertSee('Inventory manager')
            ->assertDontSee('href="'.route('pos.terminal').'"', false)
            ->assertSee('data-dt-responsive-control="true"', false)
            ->assertDontSee('data-dt-selectable="true"', false)
            ->assertSee('"title":"SN"', false)
            ->assertDontSee('"title":"ID"', false)
            ->assertDontSee('Create <span class="badge');

        $this->getJson('/dashboard/dt/users')->assertOk();
        $this->get('/dashboard/dt/users/export/csv')->assertOk();

        $cashier = User::where('email', 'staff@mpos.local')->firstOrFail();
        $this->actingAs($cashier)
            ->get('/dashboard/dt/users/export/csv')
            ->assertForbidden();
    }

    public function test_pos_checkout_is_company_scoped_and_records_discount_partial_payment_and_stock(): void
    {
        $this->app['env'] = 'local';
        $this->seed();
        $this->app['env'] = 'testing';

        $cashier = User::where('email', 'staff@mpos.local')->firstOrFail();
        $product = DB::table('products')->where('company_id', $cashier->company_id)->where('sku', 'GRC-1001')->first();
        $customer = DB::table('customers')->where('company_id', $cashier->company_id)->first();
        $branch = DB::table('branches')->where('company_id', $cashier->company_id)->where('id', $cashier->branch_id)->first();
        $otherCompanyId = DB::table('companies')->insertGetId([
            'name' => 'Separate Tenant',
            'bin' => '123456789-0987',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('products')->insert([
            'company_id' => $otherCompanyId,
            'name' => 'Other tenant product',
            'sku' => 'OTHER-1',
            'sale_price' => 1,
            'cost_price' => 0,
            'track_stock' => false,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($cashier)
            ->getJson(route('pos.customers', ['q' => 'Nusrat']))
            ->assertOk()
            ->assertJsonPath('data.0.name', 'Nusrat Jahan');

        $this->getJson(route('pos.products', [
            'branch_id' => $branch->id,
            'q' => 'Northstar',
        ]))
            ->assertOk()
            ->assertJsonPath('data.0.name', 'Arabica Coffee 250g')
            ->assertJsonPath('data.0.brand', 'Northstar');

        $this->getJson(route('pos.products', [
            'branch_id' => $branch->id,
            'q' => 'Household',
        ]))
            ->assertOk()
            ->assertJsonPath('data.0.sku', 'HOM-4001');

        $this->postJson(route('pos.customers.store'), [
            'name' => 'POS Walk-in Contact',
            'phone' => '01700000999',
            'email' => 'pos-customer@example.test',
        ])->assertCreated()
            ->assertJsonPath('data.company_id', $cashier->company_id);

        $this->actingAs($cashier)
            ->getJson(route('pos.products', [
                'company_id' => $otherCompanyId,
                'branch_id' => $branch->id,
            ]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('company_id');

        $response = $this->postJson(route('pos.checkout'), [
            'company_id' => $cashier->company_id,
            'branch_id' => $branch->id,
            'customer_id' => $customer->id,
            'items' => [[
                'product_id' => $product->id,
                'quantity' => 2,
                'discount_type' => 'percent',
                'discount_value' => 10,
            ]],
            'order_discount_type' => 'flat',
            'order_discount_value' => 10,
            'payments' => [
                ['amount' => 60, 'method' => 'cash'],
                ['amount' => 40, 'method' => 'mobile_banking', 'reference' => 'TX-001'],
            ],
        ])->assertCreated()
            ->assertJsonPath('data.sale.grand_total', 314)
            ->assertJsonPath('data.sale.paid_total', 100)
            ->assertJsonPath('data.sale.due_total', 214)
            ->assertJsonPath('data.sale.payment_status', 'partial')
            ->assertJsonPath('data.payments.0.method', 'cash')
            ->assertJsonPath('data.payments.0.amount', 60)
            ->assertJsonPath('data.payments.1.method', 'mobile_banking')
            ->assertJsonPath('data.payments.1.amount', 40)
            ->assertJsonPath('data.payments.1.reference', 'TX-001');

        $saleId = $response->json('data.sale.id');
        $this->assertSame(
            40.0,
            (float) DB::table('branch_stocks')->where('branch_id', $branch->id)->where('product_id', $product->id)->value('quantity'),
        );
        $this->assertSame(1, DB::table('stock_movements')->where('sale_id', $saleId)->count());
        $this->assertSame($cashier->company_id, (int) DB::table('sales')->where('id', $saleId)->value('company_id'));
    }

    public function test_walk_in_sale_cannot_leave_due_and_cash_change_is_returned(): void
    {
        $this->app['env'] = 'local';
        $this->seed();
        $this->app['env'] = 'testing';

        $cashier = User::where('email', 'staff@mpos.local')->firstOrFail();
        $product = DB::table('products')->where('company_id', $cashier->company_id)->where('sku', 'GRC-1001')->first();
        $branchId = $cashier->branch_id;
        $payload = [
            'branch_id' => $branchId,
            'items' => [['product_id' => $product->id, 'quantity' => 1]],
            'payment' => ['amount' => 0, 'method' => 'cash'],
        ];

        $this->actingAs($cashier)
            ->postJson(route('pos.checkout'), $payload)
            ->assertUnprocessable()
            ->assertJsonValidationErrors('payment.amount');
        $this->assertSame(0, DB::table('sales')->count());

        $payload['payment']['amount'] = 200;
        $this->postJson(route('pos.checkout'), $payload)
            ->assertCreated()
            ->assertJsonPath('data.sale.payment_status', 'paid')
            ->assertJsonPath('data.change', 20);
    }

    public function test_customer_due_collection_changes_only_selected_invoice(): void
    {
        $this->app['env'] = 'local';
        $this->seed();
        $this->app['env'] = 'testing';

        $cashier = User::where('email', 'staff@mpos.local')->firstOrFail();
        $customer = DB::table('customers')->where('company_id', $cashier->company_id)->first();
        $now = now();
        $invoiceIds = [];
        foreach (['MP-DUE-001', 'MP-DUE-002'] as $invoice) {
            $invoiceIds[] = DB::table('sales')->insertGetId([
                'company_id' => $cashier->company_id,
                'branch_id' => $cashier->branch_id,
                'customer_id' => $customer->id,
                'created_by' => $cashier->id,
                'invoice_number' => $invoice,
                'customer_name' => $customer->name,
                'subtotal' => 100,
                'grand_total' => 100,
                'paid_total' => 0,
                'due_total' => 100,
                'payment_status' => 'unpaid',
                'status' => 'completed',
                'sold_at' => $now,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        $this->actingAs($cashier)
            ->postJson(route('pos.invoice-payments.store', ['sale' => $invoiceIds[0]]), [
                'amount' => 35,
                'method' => 'cash',
            ])
            ->assertOk()
            ->assertJsonPath('data.paid_total', 35)
            ->assertJsonPath('data.due_total', 65)
            ->assertJsonPath('data.payment_status', 'partial');

        $this->assertSame(100.0, (float) DB::table('sales')->where('id', $invoiceIds[1])->value('due_total'));
        $this->assertSame(1, DB::table('sale_payments')->where('sale_id', $invoiceIds[0])->count());
    }

    public function test_new_sale_does_not_apply_existing_customer_due_automatically(): void
    {
        $this->app['env'] = 'local';
        $this->seed();
        $this->app['env'] = 'testing';

        $cashier = User::where('email', 'staff@mpos.local')->firstOrFail();
        $customer = DB::table('customers')->where('company_id', $cashier->company_id)->first();
        $product = DB::table('products')->where('company_id', $cashier->company_id)->where('sku', 'GRC-1001')->first();
        $existingDueId = DB::table('sales')->insertGetId([
            'company_id' => $cashier->company_id,
            'branch_id' => $cashier->branch_id,
            'customer_id' => $customer->id,
            'created_by' => $cashier->id,
            'invoice_number' => 'MP-OLD-DUE-001',
            'customer_name' => $customer->name,
            'subtotal' => 80,
            'grand_total' => 80,
            'paid_total' => 0,
            'due_total' => 80,
            'payment_status' => 'unpaid',
            'status' => 'completed',
            'sold_at' => now()->subDay(),
            'created_at' => now()->subDay(),
            'updated_at' => now()->subDay(),
        ]);

        $this->actingAs($cashier)
            ->postJson(route('pos.checkout'), [
                'branch_id' => $cashier->branch_id,
                'customer_id' => $customer->id,
                'items' => [['product_id' => $product->id, 'quantity' => 1]],
                'payment' => ['amount' => 180, 'method' => 'cash'],
            ])
            ->assertCreated()
            ->assertJsonPath('data.sale.grand_total', 180)
            ->assertJsonPath('data.sale.paid_total', 180)
            ->assertJsonPath('data.sale.due_total', 0);

        $this->assertSame(80.0, (float) DB::table('sales')->where('id', $existingDueId)->value('due_total'));
        $this->assertSame(80.0, (float) DB::table('customers')
            ->leftJoin('sales', 'sales.customer_id', '=', 'customers.id')
            ->where('customers.id', $customer->id)
            ->sum('sales.due_total'));
    }

    public function test_super_admin_can_switch_tenant_but_catalog_stays_in_selected_company(): void
    {
        $this->app['env'] = 'local';
        $this->seed();
        $this->app['env'] = 'testing';

        $admin = User::where('email', 'admin@mpos.local')->firstOrFail();
        $companyId = DB::table('companies')->insertGetId([
            'name' => 'Second POS Tenant',
            'bin' => '222222222-2222',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $branchId = DB::table('branches')->insertGetId([
            'company_id' => $companyId,
            'name' => 'Second Head Office',
            'code' => 'HO',
            'is_head_office' => true,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('products')->insert([
            'company_id' => $companyId,
            'name' => 'Tenant Two Product',
            'sku' => 'TENANT-2',
            'sale_price' => 25,
            'cost_price' => 12,
            'track_stock' => false,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($admin)
            ->getJson(route('pos.context', ['company_id' => $companyId]))
            ->assertOk()
            ->assertJsonPath('company.id', $companyId)
            ->assertJsonPath('branch_id', $branchId);

        $this->getJson(route('pos.products', ['company_id' => $companyId, 'branch_id' => $branchId]))
            ->assertOk()
            ->assertJsonPath('data.0.sku', 'TENANT-2');

        $productId = DB::table('products')->where('company_id', $companyId)->value('id');
        $this->postJson(route('pos.checkout'), [
            'company_id' => $companyId,
            'branch_id' => $branchId,
            'items' => [['product_id' => $productId, 'quantity' => 1]],
            'payment' => ['amount' => 25, 'method' => 'cash'],
        ])
            ->assertCreated()
            ->assertJsonPath('data.sale.company_id', $companyId)
            ->assertJsonPath('data.sale.created_by', $admin->id);
    }

    public function test_csv_export_uses_the_table_search_and_prevents_formula_injection(): void
    {
        $this->app['env'] = 'local';
        $this->seed();
        $this->app['env'] = 'testing';

        $user = User::where('email', 'admin@mpos.local')->firstOrFail();
        DB::table('users')->insert([
            'company_id' => $user->company_id,
            'branch_id' => $user->branch_id,
            'name' => '=HYPERLINK("https://example.com")',
            'email' => 'formula@mpos.local',
            'password' => bcrypt('12345678'),
            'type' => 'cashier',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->actingAs($user)
            ->get('/dashboard/dt/users/export/csv?search%5Bvalue%5D=formula')
            ->assertOk()
            ->assertHeader('Content-Type', 'text/csv; charset=UTF-8');

        $content = $response->streamedContent();

        $this->assertStringContainsString("'=HYPERLINK", $content);
        $this->assertStringContainsString('formula@mpos.local', $content);
        $this->assertStringNotContainsString('admin@mpos.local', $content);
    }

    public function test_spreadsheet_and_pdf_exports_return_downloads(): void
    {
        $this->app['env'] = 'local';
        $this->seed();
        $this->app['env'] = 'testing';

        $user = User::where('email', 'admin@mpos.local')->firstOrFail();

        $spreadsheet = $this->actingAs($user)
            ->get('/dashboard/dt/users/export/xlsx')
            ->assertOk()
            ->assertHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $this->assertStringStartsWith('PK', $spreadsheet->streamedContent());

        $pdf = $this->actingAs($user)
            ->get('/dashboard/dt/users/export/pdf')
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf');
        $this->assertStringStartsWith('%PDF-', $pdf->getContent());
    }
}
