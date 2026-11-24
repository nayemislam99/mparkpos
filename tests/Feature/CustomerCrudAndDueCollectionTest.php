<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Customer;
use App\Models\CustomerPayment;
use App\Models\Sale;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class CustomerCrudAndDueCollectionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->app['env'] = 'local';
        $this->seed();
        $this->app['env'] = 'testing';
    }

    public function test_user_can_view_customers_list_and_menu_is_active(): void
    {
        $admin = User::where('email', 'admin@mpos.local')->firstOrFail();

        $response = $this->actingAs($admin)->get(route('customers.index'))->assertOk();

        $content = $response->getContent();
        // Check sidebar active state
        $this->assertStringContainsString('class="nav-group show"', $content);
        $this->assertStringContainsString('href="http://localhost/dashboard/customers"', $content);
        $this->assertStringContainsString('Customer List', $content);
        $this->assertStringContainsString('List', $content);
        $this->assertStringContainsString('Add Customer', $content);

        // Check top cards & modal
        $this->assertStringContainsString('Total Customers', $content);
        $this->assertStringContainsString('Total Outstanding Due', $content);
        $this->assertStringContainsString('Total Advance Balance', $content);
        $this->assertStringContainsString('id="collectDueModal"', $content);
        $this->assertStringContainsString('id="dt-customers"', $content);
    }

    public function test_user_can_view_customers_create_page_and_menu_is_active(): void
    {
        $admin = User::where('email', 'admin@mpos.local')->firstOrFail();

        $response = $this->actingAs($admin)->get(route('customers.create'))->assertOk();

        $content = $response->getContent();
        // Parent Customers menu is open
        $this->assertStringContainsString('class="nav-group show"', $content);
        $this->assertStringContainsString('href="http://localhost/dashboard/customers/create"', $content);
        $this->assertStringContainsString('Add Customer', $content);
        $this->assertStringContainsString('name="phone"', $content);
        $this->assertStringContainsString('name="nid"', $content);
        $this->assertStringContainsString('name="opening_balance"', $content);
        $this->assertStringContainsString('name="advance_balance"', $content);
        $this->assertStringContainsString('name="due_limit"', $content);
        $this->assertStringContainsString('name="customer_type"', $content);
        $this->assertStringContainsString('name="customer_group"', $content);
        $this->assertStringContainsString('name="address_line_2"', $content);
        $this->assertStringContainsString('name="city"', $content);
    }

    public function test_customer_store_requires_name_and_phone(): void
    {
        $admin = User::where('email', 'admin@mpos.local')->firstOrFail();

        $response = $this->actingAs($admin)->post(route('customers.store'), [
            'name' => '',
            'phone' => '',
        ]);

        $response->assertSessionHasErrors(['name', 'phone']);
    }

    public function test_admin_can_create_customer_with_opening_balance_and_nid(): void
    {
        $admin = User::where('email', 'admin@mpos.local')->firstOrFail();
        $branch = Branch::where('company_id', $admin->company_id)->first();

        $response = $this->actingAs($admin)->post(route('customers.store'), [
            'name' => 'Moon Ahmed',
            'phone' => '01849807777',
            'email' => 'moon@ehisab.com',
            'nid' => '19901234567890123',
            'address' => 'House 12, Road 5, Block B',
            'address_line_2' => 'Flat 4A, Green Tower',
            'city' => 'Dhaka',
            'customer_group' => 'wholesale',
            'customer_type' => 'wholesale',
            'opening_balance' => 4500.50,
            'advance_balance' => 0.00,
            'due_limit' => 20000.00,
            'branch_id' => $branch?->id,
            'notes' => 'VIP Regular Customer',
            'is_active' => '1',
        ]);

        $response->assertRedirect(route('customers.index'));
        $response->assertSessionHas('success');

        $customer = Customer::where('phone', '01849807777')->firstOrFail();
        $this->assertEquals($admin->company_id, $customer->company_id);
        $this->assertEquals('Moon Ahmed', $customer->name);
        $this->assertEquals('19901234567890123', $customer->nid);
        $this->assertEquals('Flat 4A, Green Tower', $customer->address_line_2);
        $this->assertEquals('Dhaka', $customer->city);
        $this->assertEquals('wholesale', $customer->customer_group);
        $this->assertEquals('wholesale', $customer->customer_type);
        $this->assertEquals(4500.50, (float) $customer->opening_balance);
        $this->assertEquals(20000.00, (float) $customer->due_limit);
        $this->assertTrue($customer->is_active);
        $this->assertEquals(4500.50, $customer->outstanding_due);
    }

    public function test_admin_can_edit_and_update_customer(): void
    {
        $admin = User::where('email', 'admin@mpos.local')->firstOrFail();

        $customer = Customer::create([
            'company_id' => $admin->company_id,
            'name' => 'Original Customer',
            'phone' => '01700112233',
            'opening_balance' => 1000.00,
            'is_active' => true,
        ]);

        // Edit page
        $this->actingAs($admin)
            ->get(route('customers.edit', $customer->id))
            ->assertOk()
            ->assertSee('Original Customer');

        // Update
        $response = $this->actingAs($admin)->put(route('customers.update', $customer->id), [
            'name' => 'Updated Customer Name',
            'phone' => '01700112233',
            'email' => 'updated@pos.local',
            'nid' => '987654321',
            'address' => 'Updated Address 1',
            'address_line_2' => 'Updated Line 2',
            'city' => 'Chittagong',
            'customer_group' => 'corporate',
            'customer_type' => 'corporate',
            'opening_balance' => 1500.00,
            'advance_balance' => 200.00,
            'due_limit' => 50000.00,
            'is_active' => '1',
        ]);

        $response->assertRedirect(route('customers.index'));
        $response->assertSessionHas('success');

        $customer->refresh();
        $this->assertEquals('Updated Customer Name', $customer->name);
        $this->assertEquals('987654321', $customer->nid);
        $this->assertEquals('Chittagong', $customer->city);
        $this->assertEquals('corporate', $customer->customer_type);
        $this->assertEquals(1500.00, (float) $customer->opening_balance);
        $this->assertEquals(200.00, (float) $customer->advance_balance);
    }

    public function test_admin_can_toggle_customer_status(): void
    {
        $admin = User::where('email', 'admin@mpos.local')->firstOrFail();

        $customer = Customer::create([
            'company_id' => $admin->company_id,
            'name' => 'Active Customer',
            'phone' => '01911223344',
            'is_active' => true,
        ]);

        $this->actingAs($admin)->post(route('customers.toggle', $customer->id));
        $this->assertFalse($customer->fresh()->is_active);

        $this->actingAs($admin)->post(route('customers.toggle', $customer->id));
        $this->assertTrue($customer->fresh()->is_active);
    }

    public function test_admin_can_soft_delete_customer(): void
    {
        $admin = User::where('email', 'admin@mpos.local')->firstOrFail();

        $customer = Customer::create([
            'company_id' => $admin->company_id,
            'name' => 'Delete Me',
            'phone' => '01511223344',
            'is_active' => true,
        ]);

        $response = $this->actingAs($admin)->delete(route('customers.destroy', $customer->id));
        $response->assertSessionHas('success');

        $this->assertSoftDeleted('customers', ['id' => $customer->id]);
    }

    public function test_due_collection_reduces_opening_balance_and_records_customer_payment(): void
    {
        $admin = User::where('email', 'admin@mpos.local')->firstOrFail();

        $customer = Customer::create([
            'company_id' => $admin->company_id,
            'name' => 'Due Test Customer',
            'phone' => '01899112233',
            'opening_balance' => 5000.00,
            'advance_balance' => 0.00,
            'is_active' => true,
        ]);

        $this->assertEquals(5000.00, $customer->outstanding_due);

        $response = $this->actingAs($admin)->post(route('customers.collect-due', $customer->id), [
            'amount' => 3000.00,
            'method' => 'cash',
            'reference_no' => 'REC-001',
            'note' => 'Partial cash settlement',
        ]);

        $response->assertSessionHas('success');

        $customer->refresh();
        $this->assertEquals(2000.00, (float) $customer->opening_balance);
        $this->assertEquals(0.00, (float) $customer->advance_balance);
        $this->assertEquals(2000.00, $customer->outstanding_due);

        $this->assertDatabaseHas('customer_payments', [
            'company_id' => $admin->company_id,
            'customer_id' => $customer->id,
            'received_by' => $admin->id,
            'amount' => 3000.00,
            'method' => 'cash',
            'reference_no' => 'REC-001',
        ]);
    }

    public function test_due_collection_settles_unpaid_sales_and_excess_to_advance(): void
    {
        $admin = User::where('email', 'admin@mpos.local')->firstOrFail();
        $branch = Branch::where('company_id', $admin->company_id)->firstOrFail();

        $customer = Customer::create([
            'company_id' => $admin->company_id,
            'branch_id' => $branch->id,
            'name' => 'Multi Invoice Due Customer',
            'phone' => '01611223344',
            'opening_balance' => 1000.00,
            'advance_balance' => 0.00,
            'is_active' => true,
        ]);

        // Create an unpaid sale
        $sale = Sale::create([
            'company_id' => $admin->company_id,
            'branch_id' => $branch->id,
            'customer_id' => $customer->id,
            'created_by' => $admin->id,
            'invoice_number' => 'INV-TEST-0099',
            'customer_name' => $customer->name,
            'customer_phone' => $customer->phone,
            'subtotal' => 2000.00,
            'grand_total' => 2000.00,
            'paid_total' => 500.00,
            'due_total' => 1500.00,
            'payment_status' => 'partial',
            'status' => 'completed',
            'sold_at' => now(),
        ]);

        // Total due before payment: 1000 (opening) + 1500 (sale) = 2500
        $this->assertEquals(2500.00, $customer->outstanding_due);

        // Pay 3000 (1000 opening + 1500 sale + 500 excess -> advance)
        $response = $this->actingAs($admin)->post(route('customers.collect-due', $customer->id), [
            'amount' => 3000.00,
            'method' => 'bkash',
            'reference_no' => 'BKASH-TRX-9988',
            'note' => 'Full clearance + 500 advance',
        ]);

        $response->assertSessionHas('success');

        $customer->refresh();
        $this->assertEquals(0.00, (float) $customer->opening_balance);
        $this->assertEquals(500.00, (float) $customer->advance_balance);
        $this->assertEquals(0.00, $customer->outstanding_due);

        $sale->refresh();
        $this->assertEquals(2000.00, (float) $sale->paid_total);
        $this->assertEquals(0.00, (float) $sale->due_total);
        $this->assertEquals('paid', $sale->payment_status);

        $this->assertDatabaseHas('sale_payments', [
            'company_id' => $admin->company_id,
            'sale_id' => $sale->id,
            'amount' => 1500.00,
            'method' => 'bkash',
        ]);

        $this->assertDatabaseHas('customer_payments', [
            'company_id' => $admin->company_id,
            'customer_id' => $customer->id,
            'amount' => 3000.00,
            'method' => 'bkash',
        ]);
    }

    public function test_customers_datatable_endpoint_returns_json(): void
    {
        $admin = User::where('email', 'admin@mpos.local')->firstOrFail();

        Customer::create([
            'company_id' => $admin->company_id,
            'name' => 'DT Table Customer',
            'phone' => '01999887766',
            'opening_balance' => 2500.00,
            'is_active' => true,
        ]);

        $response = $this->actingAs($admin)
            ->getJson(route('dt.handle', ['key' => 'customers']))
            ->assertOk();

        $response->assertJsonStructure([
            'data',
            'recordsTotal',
            'recordsFiltered',
        ]);

        $json = $response->json();
        $this->assertGreaterThan(0, $json['recordsTotal']);
        $this->assertStringContainsString('DT Table Customer', json_encode($json['data']));
        $this->assertStringContainsString('js-collect-due-btn', json_encode($json['data']));
    }

    public function test_customer_views_render_in_bengali_locale(): void
    {
        $admin = User::where('email', 'admin@mpos.local')->firstOrFail();

        $this->actingAs($admin)
            ->withSession(['locale' => 'bn'])
            ->get(route('customers.index'))
            ->assertOk()
            ->assertSee('গ্রাহক তালিকা')
            ->assertSee('নতুন গ্রাহক যোগ করুন')
            ->assertSee('মোট গ্রাহক')
            ->assertSee('বকেয়া আদায়');

        $this->actingAs($admin)
            ->withSession(['locale' => 'bn'])
            ->get(route('customers.create'))
            ->assertOk()
            ->assertSee('নতুন গ্রাহক যোগ করুন')
            ->assertSee('গ্রাহকের নাম')
            ->assertSee('গ্রাহকের ধরন')
            ->assertSee('মোবাইল নম্বর')
            ->assertSee('প্রারম্ভিক বকেয়া')
            ->assertSee('গ্রাহক সংরক্ষণ করুন');
    }
}
