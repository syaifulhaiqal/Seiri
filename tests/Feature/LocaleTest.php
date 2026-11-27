<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LocaleTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_language_switch_translates_the_app_and_is_saved_on_the_account(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user, 'web')->from(route('dashboard'))->post(route('locale.update', 'ms'))
            ->assertRedirect(route('dashboard'))
            ->assertSessionHas('locale', 'ms');

        $this->assertSame('ms', $user->fresh()->locale);
        $this->actingAs($user, 'web')->get(route('dashboard'))->assertOk()->assertSee('Papan pemuka')->assertSee('lang="ms"', false);

        // A later session without the switch still uses the saved language.
        $this->flushSession();
        $this->actingAs($user->fresh(), 'web')->get(route('dashboard'))->assertSee('Papan pemuka');
    }

    public function test_guests_can_switch_language_on_the_sign_in_page(): void
    {
        $this->from(route('login'))->post(route('locale.update', 'ms'))->assertRedirect(route('login'));

        $this->get(route('login'))->assertOk()->assertSee('Log masuk untuk ke papan pemuka anda.');

        $this->post('locale/fr')->assertNotFound();
    }
}
