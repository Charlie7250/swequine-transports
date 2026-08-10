<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class StaffAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_from_dashboard_to_login(): void
    {
        $this->get('/dashboard')
            ->assertRedirect('/login');
    }

    public function test_staff_user_can_sign_in_and_sign_out(): void
    {
        $user = User::factory()->create([
            'password' => 'secret-password',
        ]);

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'secret-password',
        ])->assertRedirect('/dashboard');

        $this->assertAuthenticatedAs($user);

        $this->post('/logout')
            ->assertRedirect('/login');

        $this->assertGuest();
    }

    public function test_login_is_throttled_after_five_failed_attempts(): void
    {
        $user = User::factory()->create([
            'password' => 'secret-password',
        ]);

        foreach (range(1, 5) as $attempt) {
            $this->from('/login')
                ->post('/login', [
                    'email' => $user->email,
                    'password' => 'wrong-password',
                ])
                ->assertRedirect('/login')
                ->assertSessionHasErrors('email');
        }

        $response = $this->from('/login')
            ->post('/login', [
                'email' => $user->email,
                'password' => 'wrong-password',
            ])
            ->assertRedirect('/login')
            ->assertSessionHasErrors('email');

        $this->assertStringStartsWith(
            'Too many login attempts. Please try again in ',
            $response->getSession()->get('errors')->first('email'),
        );

        RateLimiter::clear(strtolower($user->email).'|127.0.0.1');
    }
}
