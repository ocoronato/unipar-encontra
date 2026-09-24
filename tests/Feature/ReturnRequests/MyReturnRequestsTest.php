<?php

namespace Tests\Feature\ReturnRequests;

use App\Enums\ReturnRequestStatus;
use App\Models\ItemReturn;
use App\Models\ReturnRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Volt\Volt;
use Tests\TestCase;

class MyReturnRequestsTest extends TestCase
{
    use RefreshDatabase;

    public function test_page_requires_login(): void
    {
        $this->get(route('return-requests.mine'))->assertRedirect(route('login'));
    }

    public function test_user_sees_only_own_requests(): void
    {
        $user = User::factory()->create();
        $mine = ReturnRequest::factory()->for($user)->create();
        $mine->lostFoundItem->update(['title' => 'Objeto que eu pedi']);
        ReturnRequest::factory()->create()->lostFoundItem->update(['title' => 'Objeto pedido por outra pessoa']);

        $this->actingAs($user)
            ->get(route('return-requests.mine'))
            ->assertOk()
            ->assertSee('Objeto que eu pedi')
            ->assertSee($mine->created_at->format('d/m/Y'))
            ->assertSee('Pendente')
            ->assertDontSee('Objeto pedido por outra pessoa');
    }

    public function test_each_status_is_shown_with_its_label(): void
    {
        $user = User::factory()->create();

        foreach (ReturnRequestStatus::cases() as $status) {
            ReturnRequest::factory()->for($user)->create(['status' => $status]);
        }

        $this->actingAs($user)
            ->get(route('return-requests.mine'))
            ->assertSee(['Pendente', 'Aprovada', 'Rejeitada', 'Cancelada']);
    }

    public function test_approved_request_shows_pickup_instructions_and_admin_answer(): void
    {
        $request = ReturnRequest::factory()->approved()->create(['admin_notes' => 'Retire na secretaria.']);

        $this->actingAs($request->user)
            ->get(route('return-requests.mine'))
            ->assertSee('Solicitação aprovada!')
            ->assertSee('Retire na secretaria.');
    }

    public function test_returned_request_shows_delivery_date(): void
    {
        $itemReturn = ItemReturn::factory()->create(['returned_at' => '2026-09-01 10:00:00']);

        $this->actingAs($itemReturn->returnRequest->user)
            ->get(route('return-requests.mine'))
            ->assertSee('Objeto entregue em 01/09/2026.');
    }

    public function test_user_can_cancel_pending_request(): void
    {
        $request = ReturnRequest::factory()->create();

        $this->actingAs($request->user);

        Volt::test('return-requests.mine')
            ->call('confirmCancel', $request->id)
            ->call('cancel')
            ->assertHasNoErrors();

        $this->assertSame(ReturnRequestStatus::Cancelled, $request->fresh()->status);
    }

    public function test_approved_request_cannot_be_cancelled(): void
    {
        $request = ReturnRequest::factory()->approved()->create();

        $this->actingAs($request->user);

        Volt::test('return-requests.mine')
            ->call('confirmCancel', $request->id)
            ->call('cancel')
            ->assertForbidden();

        $this->assertSame(ReturnRequestStatus::Approved, $request->fresh()->status);
    }

    public function test_user_cannot_cancel_someone_elses_request(): void
    {
        $request = ReturnRequest::factory()->create();

        $this->actingAs(User::factory()->create());

        Volt::test('return-requests.mine')->set('cancellingId', $request->id)->call('cancel')->assertNotFound();
    }
}
