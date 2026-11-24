<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Company;
use App\Models\Customer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ShopBranchAndLocalizationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->app['env'] = 'local';
        $this->seed();
        $this->app['env'] = 'testing';
    }

    public function test_store_admin_can_view_branch_management_page(): void
    {
        $admin = User::where('email', 'admin@mpos.local')->firstOrFail();

        $this->actingAs($admin)
            ->get(route('branches.index'))
            ->assertOk()
            ->assertSee('All Branch')
            ->assertSee('Add New Branch');
    }

    public function test_branch_management_strictly_enforces_multi_tenancy(): void
    {
        $company1 = Company::firstOrFail();
        $user1 = User::where('email', 'manager@mpos.local')->firstOrFail();

        $company2Id = DB::table('companies')->insertGetId([
            'name' => 'Company Two',
            'bin' => '1122334455-9999',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $branch2 = Branch::create([
            'company_id' => $company2Id,
            'name' => 'Secret Branch Company 2',
            'code' => 'BR-C2-01',
            'is_active' => true,
        ]);

        // User 1 cannot view Branch 2
        $this->actingAs($user1)
            ->get(route('branches.show', $branch2))
            ->assertNotFound();

        // Branch datatable only returns Company 1 branches
        $response = $this->actingAs($user1)
            ->getJson('/dashboard/dt/branches?draw=1&start=0&length=25')
            ->assertOk();

        $names = collect($response->json('data'))->pluck('name');
        $this->assertFalse($names->contains('Secret Branch Company 2'));
    }

    public function test_branch_creation_and_update(): void
    {
        $admin = User::where('email', 'admin@mpos.local')->firstOrFail();

        $res = $this->actingAs($admin)
            ->post(route('branches.store'), [
                'name' => 'Dhanmondi Branch',
                'code' => 'DHN-01',
                'phone' => '01811223344',
                'email' => 'dhanmondi@store.com',
                'address' => 'Road 27, Dhanmondi',
                'is_active' => '1',
            ]);

        $res->assertRedirect(route('branches.index'));
        $this->assertDatabaseHas('branches', [
            'company_id' => $admin->company_id,
            'name' => 'Dhanmondi Branch',
            'code' => 'DHN-01',
        ]);

        $branch = Branch::where('code', 'DHN-01')->firstOrFail();

        $this->actingAs($admin)
            ->put(route('branches.update', $branch), [
                'name' => 'Dhanmondi Express Branch',
                'code' => 'DHN-01',
                'phone' => '01811223344',
                'is_active' => '1',
            ])
            ->assertRedirect(route('branches.index'));

        $this->assertDatabaseHas('branches', [
            'id' => $branch->id,
            'name' => 'Dhanmondi Express Branch',
        ]);
    }

    public function test_head_office_branch_cannot_be_deleted(): void
    {
        $admin = User::where('email', 'admin@mpos.local')->firstOrFail();
        $headBranch = Branch::where('company_id', $admin->company_id)->where('is_head_office', true)->firstOrFail();

        $this->actingAs($admin)
            ->delete(route('branches.destroy', $headBranch))
            ->assertSessionHas('error');

        $this->assertDatabaseHas('branches', [
            'id' => $headBranch->id,
            'deleted_at' => null,
        ]);
    }

    public function test_crm_page_and_user_crud_with_multi_tenancy(): void
    {
        $admin = User::where('email', 'admin@mpos.local')->firstOrFail();

        $this->actingAs($admin)
            ->get(route('crm.index'))
            ->assertOk()
            ->assertSee('All CRM')
            ->assertSee('Deactivation Report')
            ->assertSee('Add New CRM');

        // Create CRM user
        $this->actingAs($admin)
            ->post(route('crm.users.store'), [
                'name' => 'Moon Ahmed',
                'phone' => '01849807777',
                'email' => 'moon@mpos.local',
                'password' => 'secret123',
                'role' => 'manager',
                'address' => 'Stuff Quarter, Mirpur',
                'is_active' => '1',
            ])
            ->assertRedirect(route('crm.index'));

        $crmUser = User::where('email', 'moon@mpos.local')->firstOrFail();
        $this->assertEquals($admin->company_id, $crmUser->company_id);
        $this->assertEquals('manager', $crmUser->type);
        $this->assertEquals('Stuff Quarter, Mirpur', $crmUser->address);

        // Deactivate CRM user
        $this->actingAs($admin)
            ->post(route('crm.users.status', $crmUser))
            ->assertRedirect(route('crm.index'));

        $this->assertFalse($crmUser->fresh()->is_active);
        $this->assertNotNull($crmUser->fresh()->deactivated_at);

        // Impersonate / Login as CRM user
        $this->actingAs($admin)
            ->post(route('crm.users.login', $crmUser))
            ->assertRedirect(route('dashboard.index'));

        $this->assertEquals($crmUser->id, auth()->id());
    }

    public function test_branch_role_permission_customization(): void
    {
        $admin = User::where('email', 'admin@mpos.local')->firstOrFail();

        $this->actingAs($admin)
            ->get(route('branch-roles.index'))
            ->assertOk()
            ->assertSee('Branch User Role &amp; Permissions', false)
            ->assertSee('Add New Role')
            ->assertSee('Role Name');

        // Customise cashier to have branches.view
        $this->actingAs($admin)
            ->post(route('branch-roles.update'), [
                'role' => 'cashier',
                'permissions' => ['dashboard.view', 'branches.view', 'sales.create'],
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('company_role_permissions', [
            'company_id' => $admin->company_id,
            'role' => 'cashier',
        ]);

        $cashier = User::where('email', 'staff@mpos.local')->firstOrFail();
        $this->assertTrue($cashier->can('branches.view'));
    }

    public function test_language_switch_between_english_and_bangla(): void
    {
        $admin = User::where('email', 'admin@mpos.local')->firstOrFail();

        // Switch to Bangla
        $this->actingAs($admin)
            ->get(route('locale.switch', 'bn'))
            ->assertRedirect();

        $this->assertEquals('bn', session('locale'));

        // Visit dashboard in Bangla
        $this->actingAs($admin)
            ->get('/dashboard')
            ->assertOk()
            ->assertSee('ব্যবসার হিসাব, এখন হাতের মুঠোয়')
            ->assertSee('আজকের বিক্রয়')
            ->assertSee('ব্রাঞ্চ (শাখা)')
            ->assertSee('লাইভ ডেটা');

        // Switch back to English
        $this->actingAs($admin)
            ->get(route('locale.switch', 'en'))
            ->assertRedirect();

        $this->assertEquals('en', session('locale'));

        $this->actingAs($admin)
            ->get('/dashboard')
            ->assertOk()
            ->assertSee('Recent sales')
            ->assertSee("TODAY'S SALES")
            ->assertSee('Branch')
            ->assertSee('Business accounting at your fingertips')
            ->assertDontSee('ব্যবসার হিসাব, এখন হাতের মুঠোয়');
    }

    public function test_dashboard_displays_all_15_stat_cards(): void
    {
        $admin = User::where('email', 'admin@mpos.local')->firstOrFail();

        $this->actingAs($admin)
            ->get('/dashboard')
            ->assertOk()
            ->assertSee("TODAY'S SALES")
            ->assertSee("TODAY'S PAID SALES")
            ->assertSee("TODAY'S DUE SALES")
            ->assertSee("TODAY'S DUE COLLECTION")
            ->assertSee("TODAY'S SALES RETURN")
            ->assertSee("PREVIOUS SALES RETURN")
            ->assertSee("TODAY'S PURCHASES")
            ->assertSee("TODAY'S SUPPLIER RETURN")
            ->assertSee("TODAY'S PAYMENTS")
            ->assertSee("TODAY'S EXPENSES")
            ->assertSee("LOW STOCK ITEMS")
            ->assertSee("OVERDUE COLLECTION ALERT")
            ->assertSee("SUPPLIER ADVANCE BALANCE")
            ->assertSee("CUSTOMER ADVANCE BALANCE")
            ->assertSee("TODAY'S NET PROFIT");
    }

    public function test_dashboard_queries_satisfy_mysql_only_full_group_by(): void
    {
        $admin = User::where('email', 'admin@mpos.local')->firstOrFail();

        $this->actingAs($admin)
            ->getJson('/dashboard')
            ->assertOk()
            ->assertJsonStructure([
                'metrics' => [
                    'todays_sales',
                    'low_stock_items',
                ],
            ]);
    }
}
