<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Auth\Events\Verified;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class EmailVerificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_link_in_the_email_activates_the_account_without_logging_in(): void
    {
        Notification::fake();
        Event::fake([Verified::class]);

        $user = User::factory()->unverified()->create();
        $user->sendEmailVerificationNotification();

        $url = null;
        Notification::assertSentTo($user, VerifyEmail::class, function (VerifyEmail $notification) use ($user, &$url) {
            $url = $notification->toMail($user)->actionUrl;

            return true;
        });

        $this->get($url)
            ->assertRedirect(route('login'))
            ->assertSessionHas('status');

        $this->assertTrue($user->fresh()->hasVerifiedEmail());
        $this->assertGuest();
        Event::assertDispatched(Verified::class);
    }

    public function test_the_activation_email_uses_the_custom_subject(): void
    {
        $user = User::factory()->unverified()->create();

        $mail = (new VerifyEmail)->toMail($user);

        $this->assertStringContainsString('Activate your', $mail->subject);
    }

    public function test_an_invalid_hash_is_rejected(): void
    {
        $user = User::factory()->unverified()->create();

        $url = URL::temporarySignedRoute('verification.verify', now()->addHour(), [
            'user' => $user->id,
            'hash' => sha1('someone-else@example.com'),
        ]);

        $this->get($url)->assertForbidden();
        $this->assertFalse($user->fresh()->hasVerifiedEmail());
    }

    public function test_an_unsigned_link_is_rejected(): void
    {
        $user = User::factory()->unverified()->create();

        $this->get(route('verification.verify', ['user' => $user->id, 'hash' => sha1($user->email)]))
            ->assertForbidden();
    }

    public function test_activation_link_can_be_resent_to_inactive_accounts(): void
    {
        Notification::fake();

        $user = User::factory()->unverified()->create();

        $this->post(route('verification.resend'), ['email' => $user->email])
            ->assertRedirect(route('login'))
            ->assertSessionHas('status');

        Notification::assertSentTo($user, VerifyEmail::class);
    }

    public function test_resend_does_nothing_for_active_or_unknown_accounts(): void
    {
        Notification::fake();

        $active = User::factory()->create();

        $this->post(route('verification.resend'), ['email' => $active->email])->assertSessionHas('status');
        $this->post(route('verification.resend'), ['email' => 'nobody@example.com'])->assertSessionHas('status');

        Notification::assertNothingSent();
    }
}
