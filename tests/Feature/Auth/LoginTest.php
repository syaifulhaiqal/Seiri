<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_page_renders(): void
    {
        $this->get(route('login'))->assertOk()->assertSee('Welcome back');
    }

    public function test_home_shows_landing_page_with_both_logins(): void
    {
        $this->get('/')->assertOk()
            ->assertSee(route('login'))
            ->assertSee(route('admin.login'));
    }

    public function test_active_user_can_log_in_and_lands_on_dashboard(): void
    {
        $user = User::factory()->create();

        $this->post(route('login.store'), [
            'email' => $user->email,
            'password' => 'password123',
        ])->assertRedirect(route('dashboard', absolute: false));

        $this->assertAuthenticatedAs($user);
        $this->get(route('dashboard'))->assertOk()->assertSee($user->email);
    }

    public function test_inactive_user_cannot_log_in(): void
    {
        $user = User::factory()->unverified()->create();

        $this->from(route('login'))->post(route('login.store'), [
            'email' => $user->email,
            'password' => 'password123',
        ])
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors('email')
            ->assertSessionHas('unverified_email', $user->email);

        $this->assertGuest();
    }

    public function test_wrong_password_is_rejected(): void
    {
        $user = User::factory()->create();

        $this->post(route('login.store'), [
            'email' => $user->email,
            'password' => 'wrong-password1',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_user_can_log_out(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('logout'))->assertRedirect(route('login'));

        $this->assertGuest();
    }

    public function test_guests_cannot_reach_the_dashboard(): void
    {
        $this->get(route('dashboard'))->assertRedirect(route('login'));
    }

    public function test_admin_login_page_shows_admin_badge(): void
    {
        $this->get(route('admin.login'))->assertOk()->assertSee('Admin')->assertDontSee(route('register'));
        $this->get(route('login'))->assertOk()->assertSee(route('register'));
    }

    public function test_guests_hitting_admin_pages_go_to_admin_login(): void
    {
        $this->get(route('admin.users.index'))->assertRedirect(route('admin.login'));
    }

    public function test_admin_logs_in_only_on_admin_page(): void
    {
        $admin = User::factory()->admin()->create();
        $credentials = ['email' => $admin->email, 'password' => 'password123'];

        $this->post(route('login.store'), $credentials)->assertSessionHasErrors('email');
        $this->assertGuest();

        $this->post(route('admin.login.store'), $credentials)->assertRedirect(route('admin.dashboard', absolute: false));
        $this->assertAuthenticatedAs($admin, 'admin');

        $this->post(route('admin.logout'))->assertRedirect(route('admin.login'));
        $this->assertGuest('admin');
    }

    public function test_user_and_admin_can_be_signed_in_at_the_same_time(): void
    {
        $user = User::factory()->create();
        $admin = User::factory()->admin()->create();

        $this->post(route('login.store'), ['email' => $user->email, 'password' => 'password123']);
        $this->post(route('admin.login.store'), ['email' => $admin->email, 'password' => 'password123']);

        $this->assertAuthenticatedAs($user, 'web');
        $this->assertAuthenticatedAs($admin, 'admin');
        $this->get(route('dashboard'))->assertOk()->assertSee($user->email);
        $this->get(route('admin.users.index'))->assertOk();

        // Logging out of one area leaves the other signed in.
        $this->post(route('logout'));
        $this->assertGuest('web');
        $this->assertAuthenticatedAs($admin, 'admin');
        $this->get(route('admin.dashboard'))->assertOk()->assertSee($admin->email);
    }

    public function test_user_cannot_log_in_on_admin_page(): void
    {
        $user = User::factory()->create();

        $this->post(route('admin.login.store'), [
            'email' => $user->email,
            'password' => 'password123',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
    }
}
