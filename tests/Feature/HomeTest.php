<?php

namespace Tests\Feature;

use App\Models\LostFoundItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Volt\Volt;
use Tests\TestCase;

class HomeTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_can_see_home(): void
    {
        $this->get(route('home'))
            ->assertOk()
            ->assertSee('UNIPAR ENCONTRA')
            ->assertSee('Perdeu alguma coisa? Encontrou um objeto? Nós ajudamos a conectar.')
            ->assertSee('PERDI UM OBJETO')
            ->assertSee('ENCONTREI UM OBJETO')
            ->assertSee('Entrar');
    }

    public function test_home_shows_only_approved_and_active_items(): void
    {
        $approved = LostFoundItem::factory()->found()->approved()->create(['title' => 'Guarda-chuva aprovado']);
        LostFoundItem::factory()->found()->create(['title' => 'Mochila pendente']);
        LostFoundItem::factory()->found()->rejected()->create(['title' => 'Blusa rejeitada']);
        LostFoundItem::factory()->found()->returned()->create(['title' => 'Relógio devolvido']);

        $this->get(route('home'))
            ->assertSee('Guarda-chuva aprovado')
            ->assertSee($approved->category->name)
            ->assertSee($approved->location->name)
            ->assertSee($approved->occurred_at->format('d/m/Y'))
            ->assertDontSee('Mochila pendente')
            ->assertDontSee('Blusa rejeitada')
            ->assertDontSee('Relógio devolvido');
    }

    public function test_home_does_not_expose_owner_personal_data(): void
    {
        $item = LostFoundItem::factory()->found()->approved()->create();

        $this->get(route('home'))
            ->assertDontSee($item->user->email)
            ->assertDontSee($item->user->registration_number)
            ->assertDontSee($item->user->name);
    }

    public function test_lost_section_only_appears_when_there_are_lost_items(): void
    {
        $this->get(route('home'))->assertDontSee('Objetos perdidos recentemente');

        LostFoundItem::factory()->lost()->approved()->create();

        $this->get(route('home'))->assertSee('Objetos perdidos recentemente');
    }

    public function test_authenticated_users_see_main_menu(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('home'))
            ->assertOk()
            ->assertSeeInOrder(['Início', 'Buscar Objetos', 'Cadastrar Perdido', 'Cadastrar Encontrado', 'Meus Objetos', 'Minhas Solicitações', 'Perfil'])
            ->assertDontSee('Administração');
    }

    public function test_member_pages_require_login(): void
    {
        foreach (['items.search', 'items.create.lost', 'items.create.found', 'items.mine', 'return-requests.mine'] as $route) {
            $this->get(route($route))->assertRedirect(route('login'));
        }
    }

    public function test_forbidden_page_is_in_portuguese(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('admin.dashboard'))
            ->assertForbidden()
            ->assertSee('Acesso negado');
    }

    public function test_validation_messages_are_in_portuguese(): void
    {
        $this->post('/logout');

        Volt::test('auth.login')
            ->set('email', '')
            ->call('login')
            ->assertSee('O campo e-mail é obrigatório.');
    }
}
