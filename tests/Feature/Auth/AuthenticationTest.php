<?php

namespace Tests\Feature\Auth;

use App\Enums\RoleSlug;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_visitor_can_register_as_a_customer(): void
    {
        $response = $this->post(route('register'), [
            'name' => 'Kouadio Yao',
            'email' => 'Kouadio@Example.com',
            'phone' => '+225 07 11 22 33 44',
            'password' => 'secret123',
            'password_confirmation' => 'secret123',
            'terms' => '1',
        ]);

        $response->assertRedirect(route('account.dashboard'));
        $this->assertAuthenticated();
        $user = User::query()->firstWhere('email', 'kouadio@example.com');
        $this->assertSame(RoleSlug::Customer, $user->roleSlug());
    }

    public function test_registration_cannot_escalate_the_role(): void
    {
        $this->post(route('register'), [
            'name' => 'Pirate', 'email' => 'pirate@example.com', 'phone' => '+225 07 11 22 33 44',
            'password' => 'secret123', 'password_confirmation' => 'secret123', 'terms' => '1',
            'role_id' => 1, 'is_active' => 1,
        ]);

        $this->assertSame(RoleSlug::Customer, User::query()->firstWhere('email', 'pirate@example.com')->roleSlug());
    }

    public function test_registration_requires_a_strong_password_and_terms(): void
    {
        $this->post(route('register'), ['name' => 'X', 'email' => 'bad', 'phone' => 'abc', 'password' => '123', 'password_confirmation' => '456'])
            ->assertSessionHasErrors(['name', 'email', 'phone', 'password', 'terms']);

        $this->assertGuest();
    }

    public function test_a_user_can_log_in_and_out(): void
    {
        $user = $this->customer(['email' => 'aya@example.com']);

        $this->post(route('login'), ['email' => 'aya@example.com', 'password' => 'password'])
            ->assertRedirect(route('account.dashboard'));
        $this->assertAuthenticatedAs($user);

        $this->post(route('logout'))->assertRedirect(route('home'));
        $this->assertGuest();
    }

    public function test_login_fails_with_a_wrong_password(): void
    {
        $this->customer(['email' => 'aya@example.com']);

        $this->post(route('login'), ['email' => 'aya@example.com', 'password' => 'wrong'])
            ->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_a_deactivated_account_cannot_log_in(): void
    {
        User::factory()->inactive()->create(['email' => 'off@example.com']);

        $this->post(route('login'), ['email' => 'off@example.com', 'password' => 'password'])->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_a_deactivated_user_is_logged_out_on_next_request(): void
    {
        $user = $this->customer();
        $this->actingAs($user);
        $user->forceFill(['is_active' => false])->save();

        $this->get(route('account.dashboard'))->assertRedirect(route('login'));
        $this->assertGuest();
    }

    public function test_login_is_rate_limited(): void
    {
        $this->customer(['email' => 'aya@example.com']);

        foreach (range(1, 5) as $i) {
            $this->post(route('login'), ['email' => 'aya@example.com', 'password' => 'wrong']);
        }

        $this->post(route('login'), ['email' => 'aya@example.com', 'password' => 'password'])->assertStatus(429);
    }
}
