<?php

namespace Tests\Feature\Items;

use App\Enums\ItemStatus;
use App\Models\LostFoundItem;
use App\Models\ReturnRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Volt\Volt;
use Tests\TestCase;

class MyItemsTest extends TestCase
{
    use RefreshDatabase;

    public function test_page_requires_login(): void
    {
        $this->get(route('items.mine'))->assertRedirect(route('login'));
    }

    public function test_user_sees_only_own_items_with_their_statuses(): void
    {
        $user = User::factory()->create();
        LostFoundItem::factory()->lost()->for($user)->create(['title' => 'Meu guarda-chuva']);
        LostFoundItem::factory()->create(['title' => 'Objeto de outra pessoa']);

        $this->actingAs($user)
            ->get(route('items.mine'))
            ->assertOk()
            ->assertSee('Meu guarda-chuva')
            ->assertSee('Perdido')
            ->assertSee('Ativo')
            ->assertSee('Aguardando aprovação')
            ->assertDontSee('Objeto de outra pessoa');
    }

    public function test_edit_and_cancel_are_only_offered_when_allowed(): void
    {
        $user = User::factory()->create();
        $editable = LostFoundItem::factory()->for($user)->create();
        $returned = LostFoundItem::factory()->found()->returned()->for($user)->create();
        $withRequest = LostFoundItem::factory()->found()->approved()->for($user)->create();
        ReturnRequest::factory()->create(['lost_found_item_id' => $withRequest->id]);

        $this->actingAs($user)
            ->get(route('items.mine'))
            ->assertSee(route('items.edit', $editable))
            ->assertDontSee(route('items.edit', $returned))
            ->assertDontSee(route('items.edit', $withRequest));
    }

    public function test_user_can_cancel_own_item(): void
    {
        $item = LostFoundItem::factory()->approved()->create();

        $this->actingAs($item->user);

        Volt::test('items.mine')
            ->call('confirmCancel', $item->id)
            ->call('cancel')
            ->assertHasNoErrors();

        $this->assertSame(ItemStatus::Cancelled, $item->fresh()->status);
    }

    public function test_item_with_open_request_cannot_be_cancelled(): void
    {
        $request = ReturnRequest::factory()->create();
        $item = $request->lostFoundItem;

        $this->actingAs($item->user);

        Volt::test('items.mine')
            ->call('confirmCancel', $item->id)
            ->call('cancel')
            ->assertForbidden();

        $this->assertSame(ItemStatus::Active, $item->fresh()->status);
    }

    public function test_user_cannot_cancel_someone_elses_item(): void
    {
        $item = LostFoundItem::factory()->create();

        $this->actingAs(User::factory()->create());

        Volt::test('items.mine')->set('cancellingId', $item->id)->call('cancel')->assertNotFound();
    }
}
