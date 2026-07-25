<?php

namespace Tests\Feature;

use App\Livewire\Auth\Login;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_can_view_login_page(): void
    {
        $this->get('/login')
            ->assertOk()
            ->assertSee('Bei MiniBib anmelden')
            ->assertSeeLivewire(Login::class);
    }

    public function test_active_user_can_log_in(): void
    {
        $user = User::factory()->create([
            'email' => 'person@example.test',
            'password' => Hash::make('very-secure-password'),
            'is_active' => true,
        ]);

        Livewire::test(Login::class)
            ->set('email', 'PERSON@example.test')
            ->set('password', 'very-secure-password')
            ->call('login')
            ->assertRedirect(route('dashboard'));

        $this->assertAuthenticatedAs($user);
        $this->assertNotNull($user->fresh()->last_login_at);
    }

    public function test_inactive_user_cannot_log_in(): void
    {
        User::factory()->create([
            'email' => 'inactive@example.test',
            'password' => Hash::make('very-secure-password'),
            'is_active' => false,
        ]);

        Livewire::test(Login::class)
            ->set('email', 'inactive@example.test')
            ->set('password', 'very-secure-password')
            ->call('login')
            ->assertHasErrors('email');

        $this->assertGuest();
    }

    public function test_guest_is_redirected_from_dashboard(): void
    {
        $this->get('/dashboard')
            ->assertRedirect(route('login'));
    }

    public function test_deactivated_authenticated_user_is_logged_out(): void
    {
        $user = User::factory()->create([
            'is_active' => false,
        ]);

        $this->actingAs($user)
            ->get('/dashboard')
            ->assertRedirect(route('login'));

        $this->assertGuest();
    }

    public function test_authenticated_user_can_log_out(): void
    {
        $user = User::factory()->create([
            'is_active' => true,
        ]);

        $this->actingAs($user)
            ->post('/logout')
            ->assertRedirect(route('login'));

        $this->assertGuest();
    }

    public function test_public_registration_is_not_available(): void
    {
        $this->get('/register')->assertNotFound();
    }
}
