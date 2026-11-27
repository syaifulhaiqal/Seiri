<?php

namespace Tests\Feature\Auth;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_register_page_renders(): void
    {
        $this->get(route('register'))->assertOk()->assertSee('Create your account');
    }

    public function test_registering_creates_an_inactive_user_sends_email_and_redirects_to_login(): void
    {
        Notification::fake();

        $response = $this->post(route('register.store'), [
            'name' => 'Aina Rahman',
            'email' => 'aina@example.com',
            'phone_number' => '012-345 6789',
            'password' => 'secret123',
            'password_confirmation' => 'secret123',
        ]);

        $response->assertRedirect(route('login'));
        $response->assertSessionHas('status');
        $this->assertGuest();

        $user = User::query()->where('email', 'aina@example.com')->firstOrFail();
        $this->assertSame(UserRole::User, $user->role);
        $this->assertFalse($user->hasVerifiedEmail());
        $this->assertSame('012-345 6789', $user->phone_number);

        Notification::assertSentTo($user, VerifyEmail::class);
    }

    public function test_a_registration_cannot_choose_its_own_role(): void
    {
        Notification::fake();

        $this->post(route('register.store'), [
            'name' => 'Sneaky',
            'email' => 'sneaky@example.com',
            'password' => 'secret123',
            'password_confirmation' => 'secret123',
            'role' => 'admin',
        ]);

        $this->assertSame(UserRole::User, User::query()->where('email', 'sneaky@example.com')->value('role'));
    }

    public function test_registration_validates_input(): void
    {
        User::factory()->create(['email' => 'taken@example.com']);

        $this->post(route('register.store'), [
            'name' => '',
            'email' => 'taken@example.com',
            'password' => 'short',
            'password_confirmation' => 'different',
        ])->assertSessionHasErrors(['name', 'email', 'password']);
    }
}
