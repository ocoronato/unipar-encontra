<?php

namespace Tests\Feature\Admin;

use App\Enums\ItemType;
use App\Enums\ReturnRequestStatus;
use App\Models\Category;
use App\Models\ItemReturn;
use App\Models\Location;
use App\Models\LostFoundItem;
use App\Models\ReturnRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Volt\Volt;
use Tests\TestCase;

class ReportsTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->admin()->create();
    }

    public function test_reports_are_admin_only(): void
    {
        $user = User::factory()->create();

        foreach (['admin.reports.lost', 'admin.reports.found', 'admin.reports.returned', 'admin.reports.requests'] as $route) {
            $this->actingAs($user)->get(route($route))->assertForbidden();
            $this->actingAs($this->admin)->get(route($route))->assertOk();
        }
    }

    public function test_lost_report_lists_only_lost_items_and_skips_rejected(): void
    {
        LostFoundItem::factory()->lost()->approved()->create(['title' => 'Perdido aprovado']);
        LostFoundItem::factory()->lost()->create(['title' => 'Perdido pendente']);
        LostFoundItem::factory()->lost()->rejected()->create(['title' => 'Perdido rejeitado']);
        LostFoundItem::factory()->found()->approved()->create(['title' => 'Objeto encontrado']);

        $this->actingAs($this->admin)
            ->get(route('admin.reports.lost'))
            ->assertSee('Relatório de Objetos Perdidos')
            ->assertSee('Perdido aprovado')
            ->assertSee('Perdido pendente')
            ->assertDontSee('Perdido rejeitado')
            ->assertDontSee('Objeto encontrado');
    }

    public function test_items_report_filters_by_period_category_location_and_status(): void
    {
        $category = Category::factory()->create(['name' => 'Eletrônicos']);
        $location = Location::factory()->create(['name' => 'Biblioteca']);
        $base = ['category_id' => $category->id, 'location_id' => $location->id, 'occurred_at' => '2026-08-10'];

        LostFoundItem::factory()->found()->approved()->create($base + ['title' => 'Alvo']);
        // Com "+", valem as chaves da esquerda: por isso o valor diferente vem antes de $base.
        LostFoundItem::factory()->found()->approved()->create(['title' => 'Outra categoria', 'category_id' => Category::factory()->create()->id] + $base);
        LostFoundItem::factory()->found()->approved()->create(['title' => 'Outro local', 'location_id' => Location::factory()->create()->id] + $base);
        LostFoundItem::factory()->found()->returned()->create(['title' => 'Outro status'] + $base);
        LostFoundItem::factory()->found()->approved()->create(['title' => 'Fora do período', 'occurred_at' => '2026-06-01'] + $base);

        $this->actingAs($this->admin);

        Volt::test('admin.reports.items', ['type' => ItemType::Found])
            ->set('dateFrom', '2026-08-01')
            ->set('dateTo', '2026-08-31')
            ->set('category', (string) $category->id)
            ->set('location', (string) $location->id)
            ->set('status', 'active')
            ->assertSee('Alvo')
            ->assertDontSee('Outra categoria')
            ->assertDontSee('Outro local')
            ->assertDontSee('Outro status')
            ->assertDontSee('Fora do período')
            ->assertSee('Período: 01/08/2026 a 31/08/2026')
            ->assertSee('Categoria: Eletrônicos')
            ->assertSee('Local: Biblioteca')
            ->assertViewHas('total', 1);
    }

    public function test_returned_report_shows_item_category_location_date_and_admin(): void
    {
        $itemReturn = ItemReturn::factory()->create(['returned_at' => '2026-09-05 14:00:00']);
        $item = $itemReturn->lostFoundItem;

        $this->actingAs($this->admin)
            ->get(route('admin.reports.returned'))
            ->assertSee($item->title)
            ->assertSee($item->category->name)
            ->assertSee($item->location->name)
            ->assertSee('05/09/2026 14:00')
            ->assertSee($itemReturn->administrator->name)
            ->assertSee($itemReturn->returnRequest->user->name);
    }

    public function test_returned_report_filters_by_period(): void
    {
        ItemReturn::factory()->create(['returned_at' => '2026-09-05 10:00:00'])->lostFoundItem->update(['title' => 'Devolvido em setembro']);
        ItemReturn::factory()->create(['returned_at' => '2026-07-05 10:00:00'])->lostFoundItem->update(['title' => 'Devolvido em julho']);

        $this->actingAs($this->admin);

        Volt::test('admin.reports.returned')
            ->set('dateFrom', '2026-09-01')
            ->assertSee('Devolvido em setembro')
            ->assertDontSee('Devolvido em julho');
    }

    public function test_requests_report_filters_by_period_and_status_with_summary(): void
    {
        ReturnRequest::factory()->create(['created_at' => '2026-09-10'])->lostFoundItem->update(['title' => 'Pedido pendente']);
        ReturnRequest::factory()->create(['created_at' => '2026-09-11', 'status' => ReturnRequestStatus::Rejected])->lostFoundItem->update(['title' => 'Pedido rejeitado']);
        ReturnRequest::factory()->create(['created_at' => '2026-05-01'])->lostFoundItem->update(['title' => 'Pedido antigo']);

        $this->actingAs($this->admin);

        Volt::test('admin.reports.requests')
            ->set('dateFrom', '2026-09-01')
            ->assertViewHas('total', 2)
            ->assertViewHas('byStatus', fn ($byStatus) => $byStatus['pending'] == 1 && $byStatus['rejected'] == 1)
            ->assertDontSee('Pedido antigo')
            ->set('status', 'pending')
            ->assertSee('Pedido pendente')
            ->assertDontSee('Pedido rejeitado')
            ->assertSee('Status: Pendente');
    }

    public function test_print_version_has_no_menus_and_keeps_filters(): void
    {
        LostFoundItem::factory()->lost()->approved()->create(['title' => 'Mochila azul', 'occurred_at' => '2026-09-01']);

        $this->actingAs($this->admin)
            ->get(route('admin.reports.lost', ['de' => '2026-08-01', 'imprimir' => 1]))
            ->assertOk()
            ->assertSee('Imprimir')
            ->assertSee('Gerado em')
            ->assertSee('Período: a partir de 01/08/2026')
            ->assertSee('Mochila azul')
            ->assertDontSee('Versão para impressão')
            ->assertDontSee('Buscar Objetos') // menu principal
            ->assertDontSee(route('admin.users.index')); // menu da administração
    }

    public function test_normal_version_links_to_print_version_with_current_filters(): void
    {
        $this->actingAs($this->admin);

        Volt::test('admin.reports.requests')
            ->set('status', 'approved')
            ->assertSee(route('admin.reports.requests', ['status' => 'approved', 'imprimir' => 1]), escape: true);
    }
}
