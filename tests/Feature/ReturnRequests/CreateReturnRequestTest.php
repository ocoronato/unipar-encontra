<?php

namespace Tests\Feature\ReturnRequests;

use App\Enums\ItemStatus;
use App\Enums\ReturnRequestStatus;
use App\Models\LostFoundItem;
use App\Models\ReturnRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Volt\Volt;
use Tests\TestCase;

class CreateReturnRequestTest extends TestCase
{
    use RefreshDatabase;

    private const MESSAGE = 'É minha: tem um adesivo da UNIPAR e perdi depois da aula no Bloco 2.';

    public function test_form_shows_the_item_and_the_question(): void
    {
        $item = LostFoundItem::factory()->found()->approved()->create(['title' => 'Garrafa verde']);

        $this->actingAs(User::factory()->create())
            ->get(route('return-requests.create', $item))
            ->assertOk()
            ->assertSee('Garrafa verde')
            ->assertSee('Por que você acredita que este objeto pertence a você?');
    }

    public function test_guests_are_redirected_to_login(): void
    {
        $item = LostFoundItem::factory()->found()->approved()->create();

        $this->get(route('return-requests.create', $item))->assertRedirect(route('login'));
    }

    public function test_user_can_request_the_return(): void
    {
        $item = LostFoundItem::factory()->found()->approved()->create();
        $user = User::factory()->create();

        $this->actingAs($user);

        Volt::test('return-requests.create', ['item' => $item])
            ->set('message', self::MESSAGE)
            ->call('save')
            ->assertHasNoErrors()
            ->assertRedirect(route('return-requests.mine'));

        $request = ReturnRequest::first();
        $this->assertTrue($request->user->is($user));
        $this->assertTrue($request->lostFoundItem->is($item));
        $this->assertSame(ReturnRequestStatus::Pending, $request->status);
        $this->assertSame(self::MESSAGE, $request->message);
        $this->assertSame(ItemStatus::Active, $item->fresh()->status);
        $this->assertStringStartsWith('Solicitação enviada com sucesso!', session('status'));
    }

    public function test_message_is_required_and_needs_details(): void
    {
        $item = LostFoundItem::factory()->found()->approved()->create();
        $this->actingAs(User::factory()->create());

        Volt::test('return-requests.create', ['item' => $item])
            ->set('message', 'É meu.')
            ->call('save')
            ->assertHasErrors(['message' => 'min']);

        $this->assertSame(0, ReturnRequest::count());
    }

    public function test_invalid_requests_are_blocked_with_an_explanation(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $unavailable = 'Este objeto não está disponível para solicitações de devolução.';

        $cases = [
            [LostFoundItem::factory()->lost()->approved()->create(), $unavailable],   // objeto perdido
            [LostFoundItem::factory()->found()->create(), $unavailable],              // ainda não aprovado
            [LostFoundItem::factory()->found()->returned()->create(), $unavailable],  // já devolvido
            [LostFoundItem::factory()->found()->approved()->for($user)->create(), 'Você não pode solicitar a devolução de um objeto que você mesmo cadastrou.'],
        ];

        foreach ($cases as [$item, $message]) {
            $this->get(route('return-requests.create', $item))
                ->assertForbidden()
                ->assertSee($message);
        }
    }

    public function test_duplicate_requests_are_blocked(): void
    {
        $item = LostFoundItem::factory()->found()->approved()->create();
        $user = User::factory()->create();
        ReturnRequest::factory()->for($user)->for($item)->create();

        $this->actingAs($user)
            ->get(route('return-requests.create', $item))
            ->assertForbidden()
            ->assertSee('Você já possui uma solicitação em andamento para este objeto.');
    }

    public function test_request_is_rechecked_when_submitting(): void
    {
        $item = LostFoundItem::factory()->found()->approved()->create();
        $this->actingAs(User::factory()->create());

        $component = Volt::test('return-requests.create', ['item' => $item])->set('message', self::MESSAGE);

        // Enquanto o formulário estava aberto, outra solicitação foi aprovada.
        $item->forceFill(['status' => ItemStatus::InReturnProcess])->save();

        $component->call('save')->assertForbidden();
        $this->assertSame(0, ReturnRequest::count());
    }
}
