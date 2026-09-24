<?php

namespace Tests\Feature\Admin;

use App\Enums\ApprovalStatus;
use App\Models\LostFoundItem;
use App\Models\ReturnRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Volt\Volt;
use Tests\TestCase;

class ModerationTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->admin()->create();
    }

    public function test_only_admins_can_open_the_page(): void
    {
        $this->actingAs(User::factory()->create())->get(route('admin.items.index'))->assertForbidden();
        $this->actingAs($this->admin)->get(route('admin.items.index'))->assertOk();
    }

    public function test_pending_items_are_listed_by_default(): void
    {
        LostFoundItem::factory()->create(['title' => 'Mochila pendente']);
        LostFoundItem::factory()->approved()->create(['title' => 'Relógio aprovado']);

        $this->actingAs($this->admin)
            ->get(route('admin.items.index'))
            ->assertSee('Mochila pendente')
            ->assertDontSee('Relógio aprovado');
    }

    public function test_details_are_shown_before_deciding(): void
    {
        $item = LostFoundItem::factory()->create(['description' => 'Estojo azul com canetas.']);

        $this->actingAs($this->admin);

        Volt::test('admin.items.index')
            ->call('open', $item->id)
            ->assertSee('Estojo azul com canetas.')
            ->assertSee($item->category->name)
            ->assertSee($item->user->email)
            ->assertSee('Aprovar')
            ->assertSee('Rejeitar');
    }

    public function test_admin_approves_an_item_and_it_becomes_public(): void
    {
        $item = LostFoundItem::factory()->found()->create();

        $this->actingAs($this->admin);

        Volt::test('admin.items.index')->call('open', $item->id)->call('approve')->assertHasNoErrors();

        $this->assertSame(ApprovalStatus::Approved, $item->fresh()->approval_status);
        $this->assertTrue(LostFoundItem::publiclyVisible()->whereKey($item->id)->exists());
    }

    public function test_rejection_requires_a_reason_shown_to_the_author(): void
    {
        $item = LostFoundItem::factory()->create(['title' => 'Carteira preta']);

        $this->actingAs($this->admin);

        Volt::test('admin.items.index')
            ->call('open', $item->id)
            ->call('reject')
            ->assertHasErrors(['moderationNotes' => 'required'])
            ->set('moderationNotes', 'A foto mostra o número do documento.')
            ->call('reject')
            ->assertHasNoErrors();

        $item->refresh();
        $this->assertSame(ApprovalStatus::Rejected, $item->approval_status);
        $this->assertSame('A foto mostra o número do documento.', $item->moderation_notes);

        $this->actingAs($item->user)
            ->get(route('items.mine'))
            ->assertSee('Motivo: A foto mostra o número do documento.');
    }

    public function test_approving_clears_the_previous_rejection_reason(): void
    {
        $item = LostFoundItem::factory()->create();
        $item->reject('Faltam detalhes.');

        $item->approve();

        $this->assertNull($item->fresh()->moderation_notes);
    }

    public function test_items_with_return_in_progress_cannot_be_moderated(): void
    {
        $request = ReturnRequest::factory()->create();

        $this->actingAs($this->admin);

        Volt::test('admin.items.index')
            ->call('open', $request->lost_found_item_id)
            ->set('moderationNotes', 'Motivo qualquer')
            ->call('reject')
            ->assertForbidden();

        $this->assertSame(ApprovalStatus::Approved, $request->lostFoundItem->fresh()->approval_status);
    }

    public function test_regular_users_cannot_moderate(): void
    {
        $item = LostFoundItem::factory()->create();

        $this->actingAs(User::factory()->create());

        Volt::test('admin.items.index')->set('selectedId', $item->id)->call('approve')->assertForbidden();

        $this->assertSame(ApprovalStatus::Pending, $item->fresh()->approval_status);
    }
}
