<?php

namespace Tests\Feature\Admin;

use App\Models\LostFoundItem;
use App\Models\ReturnRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Volt\Volt;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_shows_all_cards_with_counts(): void
    {
        LostFoundItem::factory()->lost()->create();
        LostFoundItem::factory(2)->found()->approved()->create();
        LostFoundItem::factory()->found()->returned()->create();
        ReturnRequest::factory()->create();

        $this->actingAs(User::factory()->admin()->create());

        Volt::test('admin.dashboard')
            ->assertSee(['Total de usuários', 'Total de objetos', 'Objetos perdidos', 'Objetos encontrados', 'Aguardando aprovação', 'Solicitações pendentes', 'Objetos devolvidos'])
            ->assertViewHas('cards', function (array $cards) {
                $values = array_column($cards, 'value', 'label');

                return $values['Total de objetos'] === 5
                    && $values['Objetos perdidos'] === 1
                    && $values['Objetos encontrados'] === 4
                    && $values['Aguardando aprovação'] === 1
                    && $values['Solicitações pendentes'] === 1
                    && $values['Objetos devolvidos'] === 1;
            });
    }

    public function test_chart_groups_items_by_month_and_type(): void
    {
        $this->travelTo(now()->setDate(2026, 9, 15));

        LostFoundItem::factory()->lost()->create(['created_at' => '2026-09-02']);
        LostFoundItem::factory(2)->found()->create(['created_at' => '2026-09-10']);
        LostFoundItem::factory()->found()->create(['created_at' => '2026-07-20']);
        LostFoundItem::factory()->lost()->create(['created_at' => '2026-01-05']); // fora dos 6 meses

        $this->actingAs(User::factory()->admin()->create());

        Volt::test('admin.dashboard')->assertViewHas('chart', function (array $chart) {
            $september = end($chart['months']);
            $july = $chart['months'][3];

            return count($chart['months']) === 6
                && $chart['total'] === 4
                && [$september['lost'], $september['found']] === [1, 2]
                && [$july['lost'], $july['found']] === [0, 1]
                && $chart['scale'] === 2;
        });
    }

    public function test_chart_has_a_table_view(): void
    {
        LostFoundItem::factory()->create();

        $this->actingAs(User::factory()->admin()->create())
            ->get(route('admin.dashboard'))
            ->assertSee('Objetos cadastrados por mês')
            ->assertSee('Ver dados em tabela');
    }
}
