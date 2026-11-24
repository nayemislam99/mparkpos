<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class UserProfileAndCacheTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->app['env'] = 'local';
        $this->seed();
        $this->app['env'] = 'testing';
    }

    public function test_user_profile_dropdown_contains_profile_change_password_and_clear_cache(): void
    {
        $admin = User::where('email', 'admin@mpos.local')->firstOrFail();

        $response = $this->actingAs($admin)->get(route('dashboard.index'))->assertOk();
        $content = $response->getContent();

        // Check header dropdown items
        $this->assertStringContainsString(route('profile.index'), $content);
        $this->assertStringContainsString(route('profile.password'), $content);
        $this->assertStringContainsString(route('profile.clear-cache'), $content);
        $this->assertStringContainsString('Profile', $content);
        $this->assertStringContainsString('Change Password', $content);
        $this->assertStringContainsString('Clear Cache', $content);
    }

    public function test_authenticated_user_can_view_profile_page(): void
    {
        $admin = User::where('email', 'admin@mpos.local')->firstOrFail();

        $response = $this->actingAs($admin)->get(route('profile.index'))->assertOk();
        $response->assertSee($admin->name);
        $response->assertSee($admin->email);
        $response->assertSee('Profile Information');
    }

    public function test_authenticated_user_can_update_profile_info(): void
    {
        $admin = User::where('email', 'admin@mpos.local')->firstOrFail();

        $response = $this->actingAs($admin)->put(route('profile.update'), [
            'name' => 'Updated Admin Name',
            'email' => 'updated_admin@mpos.local',
            'phone' => '01711223344',
            'address' => 'Mirpur DOHS, Dhaka',
        ]);

        $response->assertRedirect(route('profile.index'));
        $response->assertSessionHas('success');

        $admin->refresh();
        $this->assertSame('Updated Admin Name', $admin->name);
        $this->assertSame('updated_admin@mpos.local', $admin->email);
        $this->assertSame('01711223344', $admin->phone);
        $this->assertSame('Mirpur DOHS, Dhaka', $admin->address);
    }

    public function test_authenticated_user_can_view_change_password_page(): void
    {
        $admin = User::where('email', 'admin@mpos.local')->firstOrFail();

        $response = $this->actingAs($admin)->get(route('profile.password'))->assertOk();
        $response->assertSee('Change Password');
        $response->assertSee('Current Password');
        $response->assertSee('New Password');
    }

    public function test_authenticated_user_cannot_change_password_with_wrong_current_password(): void
    {
        $admin = User::where('email', 'admin@mpos.local')->firstOrFail();

        $response = $this->actingAs($admin)->from(route('profile.password'))->post(route('profile.password.update'), [
            'current_password' => 'wrong-current-password',
            'new_password' => 'new-secret-password-123',
            'new_password_confirmation' => 'new-secret-password-123',
        ]);

        $response->assertSessionHasErrors(['current_password']);
        $admin->refresh();
        $this->assertTrue(Hash::check('12345678', $admin->password));
    }

    public function test_authenticated_user_can_successfully_change_password(): void
    {
        $admin = User::where('email', 'admin@mpos.local')->firstOrFail();

        $response = $this->actingAs($admin)->from(route('profile.password'))->post(route('profile.password.update'), [
            'current_password' => '12345678',
            'new_password' => 'new-secret-password-123',
            'new_password_confirmation' => 'new-secret-password-123',
        ]);

        $response->assertRedirect(route('profile.password'));
        $response->assertSessionHas('success');

        $admin->refresh();
        $this->assertTrue(Hash::check('new-secret-password-123', $admin->password));
        $this->assertFalse($admin->force_password_change);
    }

    public function test_authenticated_user_can_clear_cache(): void
    {
        $admin = User::where('email', 'admin@mpos.local')->firstOrFail();

        $response = $this->actingAs($admin)->from(route('dashboard.index'))->post(route('profile.clear-cache'));

        $response->assertRedirect(route('dashboard.index'));
        $response->assertSessionHas('success');
    }
}
