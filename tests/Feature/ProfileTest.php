<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_profile_page_renders(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('profile.edit'))->assertOk()->assertSee($user->email);
    }

    public function test_user_can_update_name_and_phone(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->put(route('profile.update'), [
            'name' => 'New Name',
            'email' => $user->email,
            'phone_number' => '+60 12-111 2222',
        ])->assertRedirect(route('profile.edit'));

        $user->refresh();
        $this->assertSame('New Name', $user->name);
        $this->assertSame('+60 12-111 2222', $user->phone_number);
        $this->assertTrue($user->hasVerifiedEmail());
        $this->assertAuthenticatedAs($user);
    }

    public function test_changing_email_requires_confirming_it_again(): void
    {
        Notification::fake();

        $user = User::factory()->create();

        $this->actingAs($user)->put(route('profile.update'), [
            'name' => $user->name,
            'email' => 'changed@example.com',
        ])->assertRedirect(route('login'));

        $user->refresh();
        $this->assertSame('changed@example.com', $user->email);
        $this->assertFalse($user->hasVerifiedEmail());
        $this->assertGuest();
        Notification::assertSentTo($user, VerifyEmail::class);
    }

    public function test_email_must_be_unique(): void
    {
        User::factory()->create(['email' => 'taken@example.com']);
        $user = User::factory()->create();

        $this->actingAs($user)->put(route('profile.update'), [
            'name' => $user->name,
            'email' => 'taken@example.com',
        ])->assertSessionHasErrors('email');
    }

    public function test_user_can_change_password(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->put(route('profile.update_password'), [
            'current_password' => 'password123',
            'password' => 'newsecret456',
            'password_confirmation' => 'newsecret456',
        ])->assertRedirect(route('profile.edit'))->assertSessionHasNoErrors();

        $this->assertTrue(Hash::check('newsecret456', $user->fresh()->password));
    }

    public function test_wrong_current_password_is_rejected(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->put(route('profile.update_password'), [
            'current_password' => 'not-my-password1',
            'password' => 'newsecret456',
            'password_confirmation' => 'newsecret456',
        ])->assertSessionHasErrorsIn('updatePassword', 'current_password');

        $this->assertTrue(Hash::check('password123', $user->fresh()->password));
    }
}
