<?php

namespace Tests\Feature\Items;

use App\Models\ItemPhoto;
use App\Models\LostFoundItem;
use App\Models\ReturnRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Volt\Volt;
use Tests\TestCase;

class ShowItemTest extends TestCase
{
    use RefreshDatabase;

    public function test_details_show_item_data_without_owner_personal_data(): void
    {
        $item = LostFoundItem::factory()->found()->approved()->create([
            'title' => 'Calculadora científica',
            'description' => 'Calculadora com capa verde.',
        ]);

        $this->actingAs(User::factory()->create())
            ->get(route('items.show', $item))
            ->assertOk()
            ->assertSee('Calculadora científica')
            ->assertSee('Calculadora com capa verde.')
            ->assertSee($item->category->name)
            ->assertSee($item->location->name)
            ->assertSee($item->occurred_at->format('d/m/Y'))
            ->assertSee('Encontrado')
            ->assertDontSee($item->user->name)
            ->assertDontSee($item->user->email)
            ->assertDontSee($item->user->registration_number);
    }

    public function test_pending_items_are_hidden_from_other_users(): void
    {
        $item = LostFoundItem::factory()->create();

        $this->actingAs(User::factory()->create())
            ->get(route('items.show', $item))
            ->assertNotFound();
    }

    public function test_owner_and_admin_can_see_pending_items(): void
    {
        $item = LostFoundItem::factory()->create();

        $this->actingAs($item->user)
            ->get(route('items.show', $item))
            ->assertOk()
            ->assertSee('Aguardando aprovação')
            ->assertSee('Esta publicação é sua.');

        $this->actingAs(User::factory()->admin()->create())
            ->get(route('items.show', $item))
            ->assertOk()
            ->assertSee('Aguardando aprovação');
    }

    public function test_approval_status_is_not_shown_to_other_users(): void
    {
        $item = LostFoundItem::factory()->approved()->create();

        $this->actingAs(User::factory()->create())
            ->get(route('items.show', $item))
            ->assertDontSee('Aprovado');
    }

    public function test_missing_item_returns_404(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('items.show', 999))
            ->assertNotFound();
    }

    public function test_claim_button_is_shown_for_available_found_items(): void
    {
        $item = LostFoundItem::factory()->found()->approved()->create();

        $this->actingAs(User::factory()->create())
            ->get(route('items.show', $item))
            ->assertSee('Este objeto é meu')
            ->assertSee(route('return-requests.create', $item));
    }

    public function test_claim_button_is_hidden_when_not_allowed(): void
    {
        $viewer = User::factory()->create();
        $this->actingAs($viewer);

        // Objeto perdido: mostra convite para cadastrar como encontrado.
        $lost = LostFoundItem::factory()->lost()->approved()->create();
        $this->get(route('items.show', $lost))
            ->assertDontSee('Este objeto é meu')
            ->assertSee('Cadastrar como encontrado');

        // Objeto já devolvido.
        $returned = LostFoundItem::factory()->found()->returned()->create();
        $this->get(route('items.show', $returned))
            ->assertDontSee('Este objeto é meu')
            ->assertSee('Este objeto já foi devolvido ao dono.');

        // O próprio autor da publicação.
        $mine = LostFoundItem::factory()->found()->approved()->for($viewer)->create();
        $this->get(route('items.show', $mine))
            ->assertDontSee('Este objeto é meu')
            ->assertSee('Esta publicação é sua.');

        // Já existe solicitação em aberto do usuário.
        $requested = LostFoundItem::factory()->found()->approved()->create();
        ReturnRequest::factory()->for($viewer)->create(['lost_found_item_id' => $requested->id]);
        $this->get(route('items.show', $requested))
            ->assertDontSee('Este objeto é meu')
            ->assertSee('Você já solicitou a devolução deste objeto.');
    }

    public function test_gallery_switches_photos(): void
    {
        $item = LostFoundItem::factory()->approved()->create();
        [$first, $second] = ItemPhoto::factory(2)->for($item)->create();

        $this->actingAs(User::factory()->create());

        Volt::test('items.show', ['item' => $item])
            ->assertSeeHtml('src="'.$first->url().'" alt="Foto de')
            ->set('photoIndex', 1)
            ->assertSeeHtml('src="'.$second->url().'" alt="Foto de');
    }
}
