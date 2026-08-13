<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Support\Facades\Artisan;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class AuthInertiaTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Artisan::call('migrate:fresh', ['--force' => true]);
    }

    private function createUser(
        string $email = 'auth@example.test',
        string $name = 'Auth User',
        bool $verified = true,
    ): User {
        $user = User::create([
            'name' => $name,
            'email' => $email,
            'password' => 'secret-password',
        ]);

        if ($verified) {
            $user->markEmailAsVerified();
        }

        return $user;
    }

    public function test_login_page_renders_the_inertia_component(): void
    {
        $this->get(route('login'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Auth/Login')
                ->where('status', null)
                ->where('canResetPassword', true)
                ->where('links.login', '/login')
                ->where('links.register', route('register'))
                ->where('links.passwordRequest', route('password.request'))
            );
    }

    public function test_register_page_renders_the_inertia_component(): void
    {
        $this->get(route('register'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Auth/Register')
                ->where('links.register', '/register')
                ->where('links.login', route('login'))
            );
    }

    public function test_forgot_password_page_renders_the_inertia_component(): void
    {
        $this->get(route('password.request'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Auth/ForgotPassword')
                ->where('status', null)
                ->where('links.login', route('login'))
                ->where('links.passwordEmail', route('password.email'))
            );
    }

    public function test_reset_password_page_renders_with_email_and_token(): void
    {
        $this->get(route('password.reset', 'TOKEN').'?email=user@example.test')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Auth/ResetPassword')
                ->where('email', 'user@example.test')
                ->where('token', 'TOKEN')
                ->where('links.passwordStore', route('password.store'))
            );
    }

    public function test_confirm_password_page_requires_authentication(): void
    {
        $this->get(route('password.confirm'))->assertRedirect('/login');
    }

    public function test_confirm_password_page_renders_the_inertia_component(): void
    {
        $this->actingAs($this->createUser())
            ->get(route('password.confirm'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Auth/ConfirmPassword')
                ->where('links.confirm', '/confirm-password')
            );
    }

    public function test_verified_user_is_redirected_to_dashboard_from_verify_email(): void
    {
        $this->actingAs($this->createUser())
            ->get(route('verification.notice'))
            ->assertRedirect(route('dashboard'));
    }

    public function test_unverified_user_renders_the_verify_email_component(): void
    {
        $this->actingAs($this->createUser(verified: false))
            ->get(route('verification.notice'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Auth/VerifyEmail')
                ->where('status', null)
                ->where('links.verificationSend', route('verification.send'))
                ->where('links.logout', route('logout'))
            );
    }

    public function test_guest_cannot_access_the_verify_email_page(): void
    {
        $this->get(route('verification.notice'))->assertRedirect('/login');
    }

    public function test_login_persists_session_and_redirects_to_dashboard(): void
    {
        $user = $this->createUser();

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'secret-password',
        ])
            ->assertRedirect(route('dashboard'));

        $this->assertAuthenticated();
    }

    public function test_login_fails_with_wrong_credentials(): void
    {
        $this->post('/login', [
            'email' => 'missing@example.test',
            'password' => 'wrong-password',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_register_creates_a_user_and_redirects_to_dashboard(): void
    {
        $this->post('/register', [
            'name' => 'New User',
            'email' => 'new@example.test',
            'password' => 'secret-password',
        ])->assertRedirect(route('dashboard'));

        $this->assertDatabaseHas('users', ['email' => 'new@example.test']);
        $this->assertAuthenticated();
    }

    public function test_register_validates_required_fields(): void
    {
        $this->post('/register', [
            'name' => '',
            'email' => 'new@example.test',
            'password' => 'secret-password',
        ])->assertSessionHasErrors('name');
    }

    public function test_confirm_password_with_correct_password_sets_confirmation_and_redirects(): void
    {
        $user = $this->createUser();

        $this->actingAs($user)
            ->post('/confirm-password', ['password' => 'secret-password'])
            ->assertRedirect(route('dashboard'))
            ->assertSessionHas('auth.password_confirmed_at');
    }

    public function test_confirm_password_with_wrong_password_shows_error(): void
    {
        $this->actingAs($this->createUser())
            ->post('/confirm-password', ['password' => 'wrong-password'])
            ->assertSessionHasErrors('password');
    }
}
