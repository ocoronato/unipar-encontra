<?php

namespace Tests\Feature\Admin;

use App\Enums\ItemStatus;
use App\Enums\ReturnRequestStatus;
use App\Models\ItemReturn;
use App\Models\ReturnRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Volt\Volt;
use Tests\TestCase;

class ReturnFlowTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->admin()->create();
    }

    public function test_only_admins_can_open_the_pages(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('admin.return-requests.index'))->assertForbidden();
        $this->actingAs($user)->get(route('admin.returns.index'))->assertForbidden();

        $this->actingAs($this->admin)->get(route('admin.return-requests.index'))->assertOk();
        $this->actingAs($this->admin)->get(route('admin.returns.index'))->assertOk();
    }

    public function test_list_shows_user_item_date_message_and_status(): void
    {
        $request = ReturnRequest::factory()->create(['message' => 'Tem meu nome escrito por dentro.']);

        $this->actingAs($this->admin)
            ->get(route('admin.return-requests.index'))
            ->assertSee($request->user->name)
            ->assertSee($request->user->email)
            ->assertSee($request->lostFoundItem->title)
            ->assertSee($request->created_at->format('d/m/Y'))
            ->assertSee('Tem meu nome escrito por dentro.')
            ->assertSee('Pendente');
    }

    public function test_admin_can_approve_a_request(): void
    {
        $request = ReturnRequest::factory()->create();

        $this->actingAs($this->admin);

        Volt::test('admin.return-requests.index')
            ->call('open', $request->id)
            ->set('adminNotes', 'Retire na secretaria do Bloco 1.')
            ->call('approve')
            ->assertHasNoErrors();

        $request->refresh();
        $this->assertSame(ReturnRequestStatus::Approved, $request->status);
        $this->assertSame('Retire na secretaria do Bloco 1.', $request->admin_notes);
        $this->assertSame(ItemStatus::InReturnProcess, $request->lostFoundItem->status);

        // Objeto em processo de devolução não aceita novas solicitações.
        $this->assertFalse(User::factory()->create()->can('create', [ReturnRequest::class, $request->lostFoundItem]));
    }

    public function test_admin_can_reject_a_request(): void
    {
        $request = ReturnRequest::factory()->create();

        $this->actingAs($this->admin);

        Volt::test('admin.return-requests.index')
            ->call('open', $request->id)
            ->set('adminNotes', 'Descrição não confere.')
            ->call('reject');

        $this->assertSame(ReturnRequestStatus::Rejected, $request->fresh()->status);
        $this->assertSame(ItemStatus::Active, $request->lostFoundItem->fresh()->status);
    }

    public function test_cancelling_an_approval_makes_the_item_available_again(): void
    {
        $request = ReturnRequest::factory()->create();
        $request->approve();

        $this->actingAs($this->admin);

        Volt::test('admin.return-requests.index')->call('open', $request->id)->call('reject');

        $this->assertSame(ReturnRequestStatus::Rejected, $request->fresh()->status);
        $this->assertSame(ItemStatus::Active, $request->lostFoundItem->fresh()->status);
    }

    public function test_only_one_request_can_be_approved_per_item(): void
    {
        $first = ReturnRequest::factory()->create();
        $second = ReturnRequest::factory()->for($first->lostFoundItem)->create();
        $first->approve();

        $this->actingAs($this->admin);

        Volt::test('admin.return-requests.index')->call('open', $second->id)->call('approve')->assertForbidden();

        $this->assertSame(ReturnRequestStatus::Pending, $second->fresh()->status);
    }

    public function test_admin_confirms_the_return(): void
    {
        $request = ReturnRequest::factory()->create();
        $other = ReturnRequest::factory()->for($request->lostFoundItem)->create();
        $request->approve();

        $this->actingAs($this->admin);

        Volt::test('admin.return-requests.index')
            ->call('open', $request->id)
            ->set('returnedAt', '2026-09-20T15:30')
            ->set('returnNotes', 'Conferido o documento com foto.')
            ->call('confirmReturn')
            ->assertHasNoErrors();

        $itemReturn = ItemReturn::first();
        $this->assertTrue($itemReturn->lostFoundItem->is($request->lostFoundItem));
        $this->assertTrue($itemReturn->returnRequest->is($request));
        $this->assertTrue($itemReturn->administrator->is($this->admin));
        $this->assertSame('2026-09-20 15:30', $itemReturn->returned_at->format('Y-m-d H:i'));
        $this->assertSame('Conferido o documento com foto.', $itemReturn->notes);

        $this->assertSame(ItemStatus::Returned, $request->lostFoundItem->fresh()->status);
        $this->assertSame(ReturnRequestStatus::Approved, $request->fresh()->status); // histórico mantido
        $this->assertSame(ReturnRequestStatus::Rejected, $other->fresh()->status);

        // Objeto devolvido não aceita novas solicitações.
        $this->assertFalse(User::factory()->create()->can('create', [ReturnRequest::class, $request->lostFoundItem]));
    }

    public function test_return_cannot_be_confirmed_twice_or_before_approval(): void
    {
        $pending = ReturnRequest::factory()->create();

        $this->actingAs($this->admin);

        Volt::test('admin.return-requests.index')->call('open', $pending->id)->call('confirmReturn')->assertForbidden();

        $pending->approve();
        $pending->confirmReturn($this->admin, now());

        Volt::test('admin.return-requests.index')->call('open', $pending->id)->call('confirmReturn')->assertForbidden();
        $this->assertSame(1, ItemReturn::count());
    }

    public function test_return_date_cannot_be_in_the_future(): void
    {
        $request = ReturnRequest::factory()->create();
        $request->approve();

        $this->actingAs($this->admin);

        Volt::test('admin.return-requests.index')
            ->call('open', $request->id)
            ->set('returnedAt', now()->addDay()->format('Y-m-d\TH:i'))
            ->call('confirmReturn')
            ->assertHasErrors(['returnedAt']);

        $this->assertSame(0, ItemReturn::count());
    }

    public function test_admin_cannot_decide_own_request(): void
    {
        $request = ReturnRequest::factory()->for($this->admin)->create();

        $this->actingAs($this->admin);

        Volt::test('admin.return-requests.index')->call('open', $request->id)->call('approve')->assertForbidden();
    }

    public function test_regular_users_cannot_run_admin_actions(): void
    {
        $request = ReturnRequest::factory()->create();

        $this->actingAs(User::factory()->create());

        Volt::test('admin.return-requests.index')->set('selectedId', $request->id)->call('approve')->assertForbidden();

        $this->assertSame(ReturnRequestStatus::Pending, $request->fresh()->status);
    }

    public function test_returns_page_lists_history(): void
    {
        $itemReturn = ItemReturn::factory()->create(['notes' => 'Entregue na biblioteca.']);

        $this->actingAs($this->admin)
            ->get(route('admin.returns.index'))
            ->assertSee($itemReturn->lostFoundItem->title)
            ->assertSee($itemReturn->returnRequest->user->name)
            ->assertSee($itemReturn->administrator->name)
            ->assertSee('Entregue na biblioteca.');
    }
}
