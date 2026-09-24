<?php

namespace Tests\Feature\Items;

use App\Enums\ApprovalStatus;
use App\Enums\ItemType;
use App\Models\Category;
use App\Models\ItemPhoto;
use App\Models\LostFoundItem;
use App\Models\ReturnRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Features\SupportLockedProperties\CannotUpdateLockedPropertyException;
use Livewire\Volt\Volt;
use Tests\TestCase;

class EditItemTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
    }

    /**
     * Cria uma foto "real" (arquivo no disco fake) para o objeto.
     */
    private function photoFor(LostFoundItem $item): ItemPhoto
    {
        $photo = ItemPhoto::factory()->for($item)->create();
        Storage::disk('public')->put($photo->path, 'conteúdo');

        return $photo;
    }

    public function test_owner_can_open_edit_page(): void
    {
        $item = LostFoundItem::factory()->found()->create(['title' => 'Mochila azul']);

        $this->actingAs($item->user)
            ->get(route('items.edit', $item))
            ->assertOk()
            ->assertSee('Editar objeto encontrado')
            ->assertSee('Mochila azul');
    }

    public function test_edit_page_is_forbidden_when_not_allowed(): void
    {
        $item = LostFoundItem::factory()->create();
        $this->actingAs(User::factory()->create())->get(route('items.edit', $item))->assertForbidden();

        $returned = LostFoundItem::factory()->returned()->create();
        $this->actingAs($returned->user)->get(route('items.edit', $returned))->assertForbidden();

        $request = ReturnRequest::factory()->create();
        $this->actingAs($request->lostFoundItem->user)->get(route('items.edit', $request->lostFoundItem))->assertForbidden();
    }

    public function test_editing_updates_item_and_sends_it_back_to_moderation(): void
    {
        $item = LostFoundItem::factory()->approved()->create();
        $category = Category::factory()->create();

        $this->actingAs($item->user);

        Volt::test('items.form', ['item' => $item])
            ->assertSet('title', $item->title)
            ->set('title', 'Título corrigido')
            ->set('category_id', (string) $category->id)
            ->call('save')
            ->assertHasNoErrors()
            ->assertRedirect(route('items.mine'));

        $item->refresh();
        $this->assertSame('Título corrigido', $item->title);
        $this->assertTrue($item->category->is($category));
        $this->assertSame(ApprovalStatus::Pending, $item->approval_status);
        $this->assertSame('Objeto atualizado e enviado novamente para análise.', session('status'));
    }

    public function test_photos_can_be_removed_and_added(): void
    {
        $item = LostFoundItem::factory()->create();
        $keep = $this->photoFor($item);
        $remove = $this->photoFor($item);

        $this->actingAs($item->user);

        Volt::test('items.form', ['item' => $item])
            ->call('removeExistingPhoto', $remove->id)
            ->set('newPhotos', [UploadedFile::fake()->image('nova.jpg')])
            ->call('save')
            ->assertHasNoErrors();

        $this->assertModelMissing($remove);
        Storage::disk('public')->assertMissing($remove->path);
        Storage::disk('public')->assertExists($keep->path);
        $this->assertSame(2, $item->photos()->count());
    }

    public function test_existing_photos_count_towards_the_limit(): void
    {
        $item = LostFoundItem::factory()->create();
        foreach (range(1, ItemPhoto::MAX_PER_ITEM) as $i) {
            $this->photoFor($item);
        }

        $this->actingAs($item->user);

        Volt::test('items.form', ['item' => $item])
            ->set('newPhotos', [UploadedFile::fake()->image('extra.jpg')])
            ->assertHasErrors(['newPhotos' => 'max'])
            ->assertCount('photos', 0);
    }

    public function test_cannot_remove_photos_from_another_item(): void
    {
        $item = LostFoundItem::factory()->create();
        $otherPhoto = $this->photoFor(LostFoundItem::factory()->create());

        $this->actingAs($item->user);

        Volt::test('items.form', ['item' => $item])
            ->call('removeExistingPhoto', $otherPhoto->id)
            ->call('save')
            ->assertHasNoErrors();

        $this->assertModelExists($otherPhoto);
        Storage::disk('public')->assertExists($otherPhoto->path);
    }

    public function test_item_being_edited_cannot_be_swapped_by_the_browser(): void
    {
        $item = LostFoundItem::factory()->create();

        $this->actingAs($item->user);
        $this->expectException(CannotUpdateLockedPropertyException::class);

        Volt::test('items.form', ['item' => $item])->set('item', LostFoundItem::factory()->create());
    }

    public function test_type_is_kept_when_editing(): void
    {
        $item = LostFoundItem::factory()->lost()->create();

        $this->actingAs($item->user);

        Volt::test('items.form', ['item' => $item])->assertSet('type', ItemType::Lost)->call('save');

        $this->assertSame(ItemType::Lost, $item->fresh()->type);
    }
}
