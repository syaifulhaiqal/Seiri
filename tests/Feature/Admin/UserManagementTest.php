<?php

namespace Tests\Feature\Admin;

use App\Enums\UserRole;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_regular_users_cannot_access_admin_pages(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();

        // A user session never reaches the admin area; it's sent to the admin login.
        $this->actingAs($user)->get(route('admin.users.index'))->assertRedirect(route('admin.login'));

        // Even on the admin guard (e.g. an admin demoted mid-session), non-admins are refused.
        $this->actingAs($user, 'admin')->get(route('admin.users.index'))->assertForbidden();
        $this->actingAs($user, 'admin')->get(route('admin.users.edit', $other))->assertForbidden();
        $this->actingAs($user, 'admin')->delete(route('admin.users.destroy', $other))->assertForbidden();

        $this->assertModelExists($other);
    }

    public function test_admin_dashboard_shows_stats_and_the_users_link(): void
    {
        $admin = User::factory()->superAdmin()->create();
        User::factory(3)->create();
        User::factory()->unverified()->create();

        $this->actingAs($admin, 'admin')->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('Manage users')
            ->assertSee('Pending email');
    }

    public function test_regular_dashboard_hides_admin_links(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('dashboard'))
            ->assertOk()
            ->assertDontSee(route('admin.users.index'));
    }

    public function test_admin_can_list_search_and_filter_users(): void
    {
        $admin = User::factory()->superAdmin()->create();
        User::factory()->create(['name' => 'Haziq Iskandar', 'email' => 'haziq@example.com']);
        User::factory()->unverified()->create(['name' => 'Mei Ling', 'email' => 'mei@example.com']);

        $this->actingAs($admin, 'admin')->get(route('admin.users.index'))
            ->assertOk()
            ->assertSee('Haziq Iskandar')
            ->assertSee('Mei Ling');

        $this->actingAs($admin, 'admin')->get(route('admin.users.index', ['search' => 'haziq']))
            ->assertSee('Haziq Iskandar')
            ->assertDontSee('Mei Ling');

        $this->actingAs($admin, 'admin')->get(route('admin.users.index', ['status' => 'inactive']))
            ->assertSee('Mei Ling')
            ->assertDontSee('Haziq Iskandar');
    }

    public function test_admin_can_edit_a_user(): void
    {
        $admin = User::factory()->superAdmin()->create();
        $user = User::factory()->unverified()->create();
        $role = Role::factory()->create();

        $this->actingAs($admin, 'admin')->get(route('admin.users.edit', $user))->assertOk()->assertSee($user->email);

        $this->actingAs($admin, 'admin')->put(route('admin.users.update', $user), [
            'name' => 'Updated Name',
            'email' => $user->email,
            'phone_number' => '011-2222 3333',
            'role' => 'admin',
            'admin_role_id' => $role->id,
            'is_active' => '1',
            'password' => 'resetpass99',
            'password_confirmation' => 'resetpass99',
        ])->assertRedirect(route('admin.users.edit', $user))->assertSessionHasNoErrors();

        $user->refresh();
        $this->assertSame('Updated Name', $user->name);
        $this->assertSame(UserRole::Admin, $user->role);
        $this->assertTrue($user->adminRole->is($role));
        $this->assertTrue($user->hasVerifiedEmail());
        $this->assertTrue(Hash::check('resetpass99', $user->password));
    }

    public function test_leaving_password_blank_keeps_the_old_one(): void
    {
        $admin = User::factory()->superAdmin()->create();
        $user = User::factory()->create();

        $this->actingAs($admin, 'admin')->put(route('admin.users.update', $user), [
            'name' => $user->name,
            'email' => $user->email,
            'role' => 'user',
            'is_active' => '1',
            'password' => '',
            'password_confirmation' => '',
        ])->assertSessionHasNoErrors();

        $this->assertTrue(Hash::check('password123', $user->fresh()->password));
    }

    public function test_deactivated_user_is_signed_out_on_next_request(): void
    {
        $admin = User::factory()->superAdmin()->create();
        $user = User::factory()->create();

        $this->actingAs($admin, 'admin')->put(route('admin.users.update', $user), [
            'name' => $user->name,
            'email' => $user->email,
            'role' => 'user',
            'is_active' => '0',
        ])->assertSessionHasNoErrors();

        $this->actingAs($user->fresh(), 'web')->get(route('dashboard'))
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_admin_cannot_demote_or_deactivate_themselves(): void
    {
        $admin = User::factory()->superAdmin()->create();

        $this->actingAs($admin, 'admin')->put(route('admin.users.update', $admin), [
            'name' => $admin->name,
            'email' => $admin->email,
            'role' => 'user',
            'is_active' => '0',
        ])->assertSessionHasErrors(['role', 'is_active']);

        $this->assertSame(UserRole::SuperAdmin, $admin->fresh()->role);
    }

    public function test_admin_can_delete_a_user(): void
    {
        $admin = User::factory()->superAdmin()->create();
        $user = User::factory()->create();

        $this->actingAs($admin, 'admin')->delete(route('admin.users.destroy', $user))
            ->assertRedirect(route('admin.users.index'));

        $this->assertModelMissing($user);
    }

    public function test_admin_cannot_delete_themselves(): void
    {
        $admin = User::factory()->superAdmin()->create();

        $this->actingAs($admin, 'admin')->delete(route('admin.users.destroy', $admin))
            ->assertSessionHasErrors('delete');

        $this->assertModelExists($admin);
    }
}
