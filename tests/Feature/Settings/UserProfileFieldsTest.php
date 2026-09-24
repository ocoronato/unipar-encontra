<?php

namespace Tests\Feature\Settings;

use App\Enums\UserRole;
use App\Models\LostFoundItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Volt\Volt;
use Tests\TestCase;

class UserProfileFieldsTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_saves_ra_and_course_as_regular_user(): void
    {
        Volt::test('auth.register')
            ->set('name', 'Maria')
            ->set('email', 'maria@example.com')
            ->set('registration_number', '12345678')
            ->set('course', 'Sistemas de Informação')
            ->set('password', 'password')
            ->set('password_confirmation', 'password')
            ->call('register')
            ->assertHasNoErrors();

        $user = User::where('email', 'maria@example.com')->first();
        $this->assertSame('12345678', $user->registration_number);
        $this->assertSame(UserRole::User, $user->role);
    }

    public function test_registration_number_must_be_unique(): void
    {
        User::factory()->create(['registration_number' => '12345678']);

        Volt::test('auth.register')
            ->set('name', 'Maria')
            ->set('email', 'maria@example.com')
            ->set('registration_number', '12345678')
            ->set('password', 'password')
            ->set('password_confirmation', 'password')
            ->call('register')
            ->assertHasErrors(['registration_number' => 'unique']);
    }

    public function test_two_users_can_register_without_ra(): void
    {
        foreach (['a@example.com', 'b@example.com'] as $email) {
            Volt::test('auth.register')
                ->set('name', 'Sem RA')
                ->set('email', $email)
                ->set('password', 'password')
                ->set('password_confirmation', 'password')
                ->call('register')
                ->assertHasNoErrors();

            auth()->logout();
        }

        $this->assertSame(2, User::whereNull('registration_number')->count());
    }

    public function test_profile_updates_ra_and_course(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user);

        Volt::test('settings.profile')
            ->set('name', $user->name)
            ->set('email', $user->email)
            ->set('registration_number', '87654321')
            ->set('course', 'Direito')
            ->call('updateProfileInformation')
            ->assertHasNoErrors();

        $this->assertSame('87654321', $user->fresh()->registration_number);
        $this->assertSame('Direito', $user->fresh()->course);
    }

    public function test_user_with_history_cannot_delete_account(): void
    {
        $item = LostFoundItem::factory()->create();

        $this->actingAs($item->user);

        Volt::test('settings.delete-user-form')
            ->set('password', 'password')
            ->call('deleteUser')
            ->assertHasErrors(['password']);

        $this->assertNotNull($item->user->fresh());
    }
}
