<?php

namespace Tests\Feature\Admin;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Volt\Volt;
use Tests\TestCase;

class AdminAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_login(): void
    {
        $this->get(route('admin.dashboard'))->assertRedirect(route('login'));
        $this->get(route('admin.users.index'))->assertRedirect(route('login'));
    }

    public function test_regular_users_cannot_access_admin_area_by_url(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('admin.dashboard'))->assertForbidden();
        $this->actingAs($user)->get(route('admin.users.index'))->assertForbidden();
    }

    public function test_admins_can_access_admin_area(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('Total de usuários');

        $this->actingAs($admin)->get(route('admin.users.index'))->assertOk();
    }

    public function test_admin_menu_link_is_only_shown_to_admins(): void
    {
        $this->actingAs(User::factory()->create())->get(route('dashboard'))
            ->assertDontSee(route('admin.dashboard'));

        $this->actingAs(User::factory()->admin()->create())->get(route('dashboard'))
            ->assertSee(route('admin.dashboard'));
    }

    public function test_admin_can_promote_a_user(): void
    {
        $admin = User::factory()->admin()->create();
        $user = User::factory()->create();

        $this->actingAs($admin);

        Volt::test('admin.users.index')->call('toggleRole', $user->id)->assertHasNoErrors();

        $this->assertSame(UserRole::Admin, $user->fresh()->role);
    }

    public function test_admin_cannot_change_own_role(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin);

        Volt::test('admin.users.index')->call('toggleRole', $admin->id)->assertForbidden();

        $this->assertTrue($admin->fresh()->isAdmin());
    }

    public function test_regular_user_cannot_run_admin_actions(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();

        $this->actingAs($user);

        Volt::test('admin.users.index')->call('toggleRole', $other->id)->assertForbidden();

        $this->assertFalse($other->fresh()->isAdmin());
    }
}
