<?php

namespace Tests\Feature\Items;

use App\Enums\ItemStatus;
use App\Models\Category;
use App\Models\Location;
use App\Models\LostFoundItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Volt\Volt;
use Tests\TestCase;

class SearchItemsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs(User::factory()->create());
    }

    public function test_search_requires_login(): void
    {
        auth()->logout();

        $this->get(route('items.search'))->assertRedirect(route('login'));
    }

    public function test_only_public_items_are_listed(): void
    {
        LostFoundItem::factory()->approved()->create(['title' => 'Item aprovado']);
        LostFoundItem::factory()->create(['title' => 'Item pendente']);
        LostFoundItem::factory()->rejected()->create(['title' => 'Item rejeitado']);
        LostFoundItem::factory()->approved()->create(['title' => 'Item cancelado', 'status' => ItemStatus::Cancelled]);

        $this->get(route('items.search'))
            ->assertOk()
            ->assertSee('Item aprovado')
            ->assertDontSee('Item pendente')
            ->assertDontSee('Item rejeitado')
            ->assertDontSee('Item cancelado');
    }

    public function test_text_filter_searches_title_and_description(): void
    {
        LostFoundItem::factory()->approved()->create(['title' => 'Carteira marrom']);
        LostFoundItem::factory()->approved()->create(['title' => 'Caderno', 'description' => 'Tem uma carteira desenhada na capa.']);
        LostFoundItem::factory()->approved()->create(['title' => 'Guarda-chuva', 'description' => 'Preto e grande.']);

        Volt::test('items.search')
            ->set('search', 'carteira')
            ->assertSee('Carteira marrom')
            ->assertSee('Caderno')
            ->assertDontSee('Guarda-chuva');
    }

    public function test_home_search_arrives_prefilled_by_query_string(): void
    {
        LostFoundItem::factory()->approved()->create(['title' => 'Fone de ouvido']);
        LostFoundItem::factory()->approved()->create(['title' => 'Mochila']);

        $this->get(route('items.search', ['q' => 'fone']))
            ->assertSee('Fone de ouvido')
            ->assertDontSee('Mochila');
    }

    public function test_type_category_location_and_status_filters(): void
    {
        $category = Category::factory()->create();
        $location = Location::factory()->create();

        LostFoundItem::factory()->found()->approved()->create(['title' => 'Alvo', 'category_id' => $category->id, 'location_id' => $location->id]);
        LostFoundItem::factory()->lost()->approved()->create(['title' => 'Outro tipo', 'category_id' => $category->id, 'location_id' => $location->id]);
        LostFoundItem::factory()->found()->approved()->create(['title' => 'Outra categoria', 'location_id' => $location->id]);
        LostFoundItem::factory()->found()->approved()->create(['title' => 'Outro local', 'category_id' => $category->id]);
        LostFoundItem::factory()->found()->returned()->create(['title' => 'Outro status', 'category_id' => $category->id, 'location_id' => $location->id]);

        Volt::test('items.search')
            ->set('type', 'found')
            ->set('category', (string) $category->id)
            ->set('location', (string) $location->id)
            ->set('status', 'active')
            ->assertSee('Alvo')
            ->assertDontSee('Outro tipo')
            ->assertDontSee('Outra categoria')
            ->assertDontSee('Outro local')
            ->assertDontSee('Outro status');
    }

    public function test_date_filters(): void
    {
        LostFoundItem::factory()->approved()->create(['title' => 'Antigo', 'occurred_at' => '2026-01-10']);
        LostFoundItem::factory()->approved()->create(['title' => 'Recente', 'occurred_at' => '2026-03-20']);

        Volt::test('items.search')
            ->set('dateFrom', '2026-03-01')
            ->assertSee('Recente')
            ->assertDontSee('Antigo')
            ->set('dateFrom', '')
            ->set('dateTo', '2026-02-01')
            ->assertSee('Antigo')
            ->assertDontSee('Recente');
    }

    public function test_invalid_date_in_url_is_ignored(): void
    {
        LostFoundItem::factory()->approved()->create(['title' => 'Qualquer']);

        $this->get(route('items.search', ['de' => 'data-invalida']))
            ->assertOk()
            ->assertSee('Qualquer');
    }

    public function test_results_are_paginated(): void
    {
        LostFoundItem::factory(13)->approved()->create();

        Volt::test('items.search')
            ->assertViewHas('items', fn ($items) => $items->count() === 12 && $items->total() === 13)
            ->call('nextPage')
            ->assertViewHas('items', fn ($items) => $items->count() === 1);
    }

    public function test_clear_filters(): void
    {
        Volt::test('items.search')
            ->set('search', 'abc')
            ->set('type', 'lost')
            ->call('clearFilters')
            ->assertSet('search', '')
            ->assertSet('type', '');
    }

    public function test_cards_do_not_expose_owner_data(): void
    {
        $owner = User::factory()->create(['name' => 'Dono Sigiloso', 'email' => 'dono.sigiloso@example.com', 'registration_number' => 'RA998877']);
        $item = LostFoundItem::factory()->approved()->for($owner)->create();

        $this->get(route('items.search'))
            ->assertSee($item->title)
            ->assertDontSee('Dono Sigiloso')
            ->assertDontSee('dono.sigiloso@example.com')
            ->assertDontSee('RA998877');
    }
}
