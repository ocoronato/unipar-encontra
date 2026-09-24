<?php

namespace Tests\Feature\Admin;

use App\Enums\ItemType;
use App\Models\Category;
use App\Models\Location;
use App\Models\LostFoundItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Volt\Volt;
use Tests\TestCase;

class CatalogTest extends TestCase
{
    use RefreshDatabase;

    public function test_only_admins_can_open_categories_and_locations(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->get(route('admin.categories.index'))->assertForbidden();
        $this->actingAs($user)->get(route('admin.locations.index'))->assertForbidden();

        $admin = User::factory()->admin()->create();
        $this->actingAs($admin)->get(route('admin.categories.index'))->assertOk()->assertSee('Nova categoria');
        $this->actingAs($admin)->get(route('admin.locations.index'))->assertOk()->assertSee('Novo local');
    }

    public function test_admin_creates_and_edits_a_category(): void
    {
        $this->actingAs(User::factory()->admin()->create());

        Volt::test('admin.catalog', ['resource' => 'categories'])
            ->call('create')
            ->set('name', 'Instrumentos musicais')
            ->set('description', 'Violões, flautas etc.')
            ->call('save')
            ->assertHasNoErrors();

        $category = Category::where('name', 'Instrumentos musicais')->firstOrFail();
        $this->assertTrue($category->active);

        Volt::test('admin.catalog', ['resource' => 'categories'])
            ->call('edit', $category->id)
            ->assertSet('name', 'Instrumentos musicais')
            ->set('name', 'Instrumentos')
            ->call('save');

        $this->assertSame('Instrumentos', $category->fresh()->name);
    }

    public function test_admin_creates_a_location(): void
    {
        $this->actingAs(User::factory()->admin()->create());

        Volt::test('admin.catalog', ['resource' => 'locations'])
            ->call('create')
            ->set('name', 'Ginásio')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('locations', ['name' => 'Ginásio', 'active' => true]);
    }

    public function test_names_must_be_unique(): void
    {
        Category::factory()->create(['name' => 'Chaves']);
        $this->actingAs(User::factory()->admin()->create());

        Volt::test('admin.catalog', ['resource' => 'categories'])
            ->call('create')
            ->set('name', 'Chaves')
            ->call('save')
            ->assertHasErrors(['name' => 'unique']);
    }

    public function test_inactive_categories_disappear_from_the_item_form(): void
    {
        $category = Category::factory()->create(['name' => 'Categoria antiga']);
        $this->actingAs(User::factory()->admin()->create());

        Volt::test('admin.catalog', ['resource' => 'categories'])->call('toggleActive', $category->id);

        $this->assertFalse($category->fresh()->active);

        Volt::test('items.form', ['type' => ItemType::Lost])->assertDontSee('Categoria antiga');
    }

    public function test_unused_records_can_be_deleted_but_used_ones_cannot(): void
    {
        $unused = Location::factory()->create();
        $used = LostFoundItem::factory()->create()->location;

        $this->actingAs(User::factory()->admin()->create());

        Volt::test('admin.catalog', ['resource' => 'locations'])
            ->call('delete', $unused->id)
            ->call('delete', $used->id);

        $this->assertModelMissing($unused);
        $this->assertModelExists($used);
    }

    public function test_unknown_resource_returns_404(): void
    {
        $this->actingAs(User::factory()->admin()->create());

        Volt::test('admin.catalog', ['resource' => 'users'])->assertNotFound();
    }

    public function test_regular_users_cannot_use_the_component_actions(): void
    {
        $this->actingAs(User::factory()->create());

        Volt::test('admin.catalog', ['resource' => 'categories'])->assertForbidden();
    }
}
