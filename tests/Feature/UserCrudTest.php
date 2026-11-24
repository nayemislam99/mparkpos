<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class UserCrudTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->app['env'] = 'local';
        $this->seed();
        $this->app['env'] = 'testing';
    }

    public function test_store_admin_can_view_users_list_and_menu_is_active(): void
    {
        $admin = User::where('email', 'admin@mpos.local')->firstOrFail();

        $response = $this->actingAs($admin)->get(route('users.index'))->assertOk();

        $content = $response->getContent();
        // Check sidebar active state
        $this->assertStringContainsString('class="nav-group show"', $content);
        $this->assertStringContainsString('href="http://localhost/dashboard/users"', $content);
        $this->assertStringContainsString('User Management', $content);
        $this->assertStringContainsString('Add New User', $content);
    }

    public function test_store_admin_can_view_users_create_page_and_menu_is_active(): void
    {
        $admin = User::where('email', 'admin@mpos.local')->firstOrFail();

        $response = $this->actingAs($admin)->get(route('users.create'))->assertOk();

        $content = $response->getContent();
        // Check sidebar: parent nav-group is show, and create nav-item is active
        $this->assertStringContainsString('class="nav-group show"', $content);
        $this->assertStringContainsString('href="http://localhost/dashboard/users/create"', $content);
        $this->assertStringContainsString('Create User', $content);
        $this->assertStringContainsString('name="password_confirmation"', $content);
    }

    public function test_store_admin_can_create_new_user_with_full_details(): void
    {
        $admin = User::where('email', 'admin@mpos.local')->firstOrFail();
        $branch = Branch::where('company_id', $admin->company_id)->first();

        $response = $this->actingAs($admin)->post(route('users.store'), [
            'name' => 'Kader Molla',
            'email' => 'kader@mpos.local',
            'phone' => '01812345678',
            'address' => 'Mirpur-10, Dhaka',
            'type' => 'cashier',
            'branch_id' => $branch?->id,
            'password' => 'secret123',
            'password_confirmation' => 'secret123',
            'is_active' => '1',
        ]);

        $response->assertRedirect(route('users.index'));
        $response->assertSessionHas('success');

        $newUser = User::where('email', 'kader@mpos.local')->firstOrFail();
        $this->assertEquals($admin->company_id, $newUser->company_id);
        $this->assertEquals('Kader Molla', $newUser->name);
        $this->assertEquals('01812345678', $newUser->phone);
        $this->assertEquals('cashier', $newUser->type);
        $this->assertTrue($newUser->is_active);
        $this->assertTrue(Hash::check('secret123', $newUser->password));
    }

    public function test_store_admin_can_edit_and_update_user(): void
    {
        $admin = User::where('email', 'admin@mpos.local')->firstOrFail();

        $user = User::create([
            'company_id' => $admin->company_id,
            'name' => 'Salam Mia',
            'email' => 'salam@mpos.local',
            'phone' => '01711111111',
            'type' => 'cashier',
            'password' => Hash::make('password123'),
            'is_active' => true,
        ]);

        // View edit page
        $this->actingAs($admin)
            ->get(route('users.edit', $user->id))
            ->assertOk()
            ->assertSee('Salam Mia');

        // Update user
        $response = $this->actingAs($admin)->put(route('users.update', $user->id), [
            'name' => 'Salam Uddin',
            'email' => 'salam_updated@mpos.local',
            'phone' => '01722222222',
            'type' => 'manager',
            'is_active' => '1',
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123',
        ]);

        $response->assertRedirect(route('users.index'));
        $response->assertSessionHas('success');

        $user->refresh();
        $this->assertEquals('Salam Uddin', $user->name);
        $this->assertEquals('salam_updated@mpos.local', $user->email);
        $this->assertEquals('manager', $user->type);
        $this->assertTrue(Hash::check('newpassword123', $user->password));
    }

    public function test_store_admin_can_toggle_user_status_and_cannot_toggle_self(): void
    {
        $admin = User::where('email', 'admin@mpos.local')->firstOrFail();

        $user = User::create([
            'company_id' => $admin->company_id,
            'name' => 'Rafiq Islam',
            'email' => 'rafiq@mpos.local',
            'type' => 'cashier',
            'password' => Hash::make('password123'),
            'is_active' => true,
        ]);

        // Toggle user status
        $this->actingAs($admin)
            ->post(route('users.toggle', $user->id))
            ->assertRedirect();

        $user->refresh();
        $this->assertFalse($user->is_active);

        // Cannot toggle self
        $this->actingAs($admin)
            ->post(route('users.toggle', $admin->id))
            ->assertSessionHas('error');
    }

    public function test_store_admin_can_delete_user_and_cannot_delete_self(): void
    {
        $admin = User::where('email', 'admin@mpos.local')->firstOrFail();

        $user = User::create([
            'company_id' => $admin->company_id,
            'name' => 'Barkat Khan',
            'email' => 'barkat@mpos.local',
            'type' => 'cashier',
            'password' => Hash::make('password123'),
            'is_active' => true,
        ]);

        // Delete user
        $this->actingAs($admin)
            ->delete(route('users.destroy', $user->id))
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertSoftDeleted('users', ['id' => $user->id]);

        // Cannot delete self
        $this->actingAs($admin)
            ->delete(route('users.destroy', $admin->id))
            ->assertSessionHas('error');
    }

    public function test_users_data_table_endpoint_returns_valid_data_and_actions(): void
    {
        $admin = User::where('email', 'admin@mpos.local')->firstOrFail();

        $response = $this->actingAs($admin)
            ->getJson('/dashboard/dt/users')
            ->assertOk();

        $this->assertArrayHasKey('draw', $response->json());
        $this->assertArrayHasKey('recordsTotal', $response->json());
        $this->assertNotEmpty($response->json('data'));
        $firstRow = $response->json('data.0');
        $this->assertArrayHasKey('name_display', $firstRow);
        $this->assertArrayHasKey('actions', $firstRow);
    }

    public function test_bilingual_support_for_users_module(): void
    {
        $admin = User::where('email', 'admin@mpos.local')->firstOrFail();

        $this->withSession(['locale' => 'bn']);

        $resIndex = $this->actingAs($admin)->get(route('users.index'))->assertOk();
        $this->assertStringContainsString('ব্যবহারকারী ব্যবস্থাপনা', $resIndex->getContent());
        $this->assertStringContainsString('নতুন ব্যবহারকারী তৈরি করুন', $resIndex->getContent());

        $resCreate = $this->actingAs($admin)->get(route('users.create'))->assertOk();
        $this->assertStringContainsString('নতুন ব্যবহারকারী তৈরি করুন', $resCreate->getContent());
        $this->assertStringContainsString('ব্যবহারকারীর তথ্য', $resCreate->getContent());
        $this->assertStringContainsString('তালিকায় ফিরে যান', $resCreate->getContent());
    }
}
