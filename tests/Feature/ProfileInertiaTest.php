<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ProfileInertiaTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Artisan::call('migrate:fresh', ['--force' => true]);
    }

    private function createUser(string $email = 'profile@example.test', string $name = 'Profile User'): User
    {
        return User::create([
            'name' => $name,
            'email' => $email,
            'password' => 'secret-password',
            'email_verified_at' => now(),
        ]);
    }

    public function test_guests_cannot_access_the_profile_page(): void
    {
        $this->get(route('profile.edit'))->assertRedirect('/login');
    }

    public function test_profile_edit_renders_the_inertia_component_with_links(): void
    {
        $this->actingAs($this->createUser())
            ->get(route('profile.edit'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Profile/Edit')
                ->where('mustVerifyEmail', false)
                ->where('status', null)
                ->where('links.updateProfile', route('profile.update'))
                ->where('links.updatePassword', route('password.update'))
                ->where('links.destroy', route('profile.destroy'))
                ->where('links.verificationSend', route('verification.send'))
            );
    }

    public function test_profile_edit_surfaces_the_session_status_prop(): void
    {
        $this->actingAs($this->createUser())
            ->withSession(['status' => 'profile-updated'])
            ->get(route('profile.edit'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Profile/Edit')
                ->where('status', 'profile-updated')
            );
    }

    public function test_profile_update_persists_and_redirects_with_status(): void
    {
        $user = $this->createUser();

        $this->actingAs($user)
            ->patch(route('profile.update'), [
                'name' => 'Updated Name',
                'email' => 'updated@example.test',
            ])
            ->assertRedirect(route('profile.edit'))
            ->assertSessionHas('status', 'profile-updated');

        $user->refresh();

        $this->assertSame('Updated Name', $user->name);
        $this->assertSame('updated@example.test', $user->email);
    }

    public function test_profile_update_validates_name_and_email(): void
    {
        $this->actingAs($this->createUser())
            ->patch(route('profile.update'), [
                'name' => '',
                'email' => 'not-an-email',
            ])
            ->assertSessionHasErrors(['name', 'email']);
    }

    public function test_profile_update_rejects_an_email_owned_by_another_user(): void
    {
        $user = $this->createUser('owner@example.test', 'Owner');
        $this->createUser('other@example.test', 'Other');

        $this->actingAs($user)
            ->patch(route('profile.update'), [
                'name' => 'Owner',
                'email' => 'other@example.test',
            ])
            ->assertSessionHasErrors('email');
    }

    public function test_password_update_persists_and_sets_status(): void
    {
        $user = $this->createUser();

        $this->actingAs($user)
            ->put(route('password.update'), [
                'current_password' => 'secret-password',
                'password' => 'new-secret-password',
            ])
            ->assertSessionHas('status', 'password-updated');

        $user->refresh();

        $this->assertTrue(Hash::check('new-secret-password', $user->password));
    }

    public function test_password_update_requires_the_current_password_in_the_update_password_bag(): void
    {
        $user = $this->createUser();

        $this->actingAs($user)
            ->put(route('password.update'), [
                'current_password' => 'wrong-password',
                'password' => 'new-secret-password',
            ])
            ->assertSessionHasErrors(['current_password'], null, 'updatePassword');

        $user->refresh();

        $this->assertTrue(Hash::check('secret-password', $user->password));
    }

    public function test_user_can_delete_their_account_with_the_correct_password(): void
    {
        $user = $this->createUser();

        $this->actingAs($user)
            ->delete(route('profile.destroy'), ['password' => 'secret-password'])
            ->assertRedirect('/');

        $this->assertDatabaseMissing('users', ['id' => $user->id]);
    }

    public function test_user_cannot_delete_their_account_with_a_wrong_password(): void
    {
        $user = $this->createUser();

        $this->actingAs($user)
            ->delete(route('profile.destroy'), ['password' => 'wrong-password'])
            ->assertSessionHasErrors(['password'], null, 'userDeletion');

        $this->assertDatabaseHas('users', ['id' => $user->id]);
    }
}
