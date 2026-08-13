<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Support\Facades\Artisan;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class WelcomeInertiaTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Artisan::call('migrate:fresh', ['--force' => true]);
    }

    private function createUser(): User
    {
        return User::create([
            'name' => 'Welcome User',
            'email' => 'welcome@example.test',
            'password' => 'secret-password',
            'email_verified_at' => now(),
        ]);
    }

    public function test_guest_landing_renders_the_welcome_inertia_component(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Welcome'));
    }

    public function test_authenticated_users_are_redirected_to_the_dashboard(): void
    {
        $this->actingAs($this->createUser())
            ->get('/')
            ->assertRedirect(route('dashboard'));
    }

    public function test_guest_landing_does_not_leak_authenticated_content(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Welcome')
                ->where('auth.user', null));
    }
}
