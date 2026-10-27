<?php

use App\Enums\ApprovalStatus;
use App\Enums\ItemStatus;
use App\Enums\ItemType;
use App\Enums\ReturnRequestStatus;
use App\Models\LostFoundItem;
use App\Models\ReturnRequest;
use App\Models\User;
use Livewire\Attributes\Title;
use Livewire\Volt\Component;

new #[Title('Administração')] class extends Component {
    public function with(): array
    {
        return [
            'cards' => [
                ['label' => 'Total de usuários', 'value' => User::count(), 'icon' => 'users', 'href' => route('admin.users.index')],
                ['label' => 'Total de objetos', 'value' => LostFoundItem::count(), 'icon' => 'cube', 'href' => route('admin.items.index', ['aprovacao' => ''])],
                ['label' => 'Objetos perdidos', 'value' => LostFoundItem::ofType(ItemType::Lost)->count(), 'icon' => 'exclamation-circle', 'href' => route('admin.items.index', ['aprovacao' => '', 'tipo' => 'lost'])],
                ['label' => 'Objetos encontrados', 'value' => LostFoundItem::ofType(ItemType::Found)->count(), 'icon' => 'hand-raised', 'href' => route('admin.items.index', ['aprovacao' => '', 'tipo' => 'found'])],
                ['label' => 'Aguardando aprovação', 'value' => LostFoundItem::where('approval_status', ApprovalStatus::Pending)->count(), 'icon' => 'clock', 'href' => route('admin.items.index')],
                ['label' => 'Solicitações pendentes', 'value' => ReturnRequest::where('status', ReturnRequestStatus::Pending)->count(), 'icon' => 'inbox', 'href' => route('admin.return-requests.index')],
                ['label' => 'Objetos devolvidos', 'value' => LostFoundItem::where('status', ItemStatus::Returned)->count(), 'icon' => 'check-badge', 'href' => route('admin.returns.index')],
            ],
            'chart' => $this->monthlyChart(),
        ];
    }

    /**
     * Objetos cadastrados nos últimos 6 meses, por mês e tipo.
     * O agrupamento é feito em PHP para funcionar igual no MySQL e no SQLite dos testes.
     */
    private function monthlyChart(): array
    {
        $start = now()->startOfMonth()->subMonths(5);
        $items = LostFoundItem::where('created_at', '>=', $start)->get(['type', 'created_at']);

        $months = collect(range(0, 5))->map(function (int $offset) use ($start, $items) {
            $month = $start->copy()->addMonths($offset);
            $inMonth = $items->filter(fn ($item) => $item->created_at->isSameMonth($month));

            return [
                'label' => ucfirst($month->translatedFormat('M')),
                'fullLabel' => $month->translatedFormat('F \d\e Y'),
                'lost' => $inMonth->where('type', ItemType::Lost)->count(),
                'found' => $inMonth->where('type', ItemType::Found)->count(),
            ];
        });

        // Escala "redonda" para o eixo Y (0, metade e máximo), ex.: 0 · 5 · 10.
        $max = max(1, $months->max(fn ($month) => max($month['lost'], $month['found'])));
        $step = collect([1, 2, 5, 10, 20, 50, 100, 200, 500, 1000])->first(fn ($step) => $step * 2 >= $max) ?? (int) ceil($max / 2);

        return ['months' => $months->all(), 'scale' => $step * 2, 'total' => $items->count()];
    }
}; ?>

<x-admin.layout heading="Dashboard" subheading="Visão geral do UNIPAR ENCONTRA">
    {{-- @container: as colunas dependem da largura do conteúdo (há dois menus laterais) --}}
    <div class="@container space-y-6">
        <div class="grid gap-4 @lg:grid-cols-2 @3xl:grid-cols-3 @5xl:grid-cols-4">
            @foreach ($cards as $card)
                <a href="{{ $card['href'] }}" wire:navigate class="fade-up group rounded-xl focus-visible:ring-2 focus-visible:ring-accent focus-visible:outline-none" style="--delay: {{ $loop->index * 50 }}ms">
                    <flux:card x-data x-spotlight class="spotlight flex h-full items-center gap-4 transition duration-300 group-hover:-translate-y-0.5 group-hover:border-red-500/50 group-hover:shadow-[0_14px_40px_-18px_rgb(220_38_38/0.6)]">
                        <div class="rounded-lg bg-red-500/10 p-3 text-red-600 ring-1 ring-red-500/20 dark:text-red-400">
                            <flux:icon :name="$card['icon']" class="size-6" />
                        </div>
                        <div>
                            <flux:text>{{ $card['label'] }}</flux:text>
                            {{-- Número que "sobe" até o valor (countUp no app.js); o HTML já traz o valor final. --}}
                            <flux:heading size="xl"><span x-data="countUp({{ (int) $card['value'] }})" x-text="current">{{ $card['value'] }}</span></flux:heading>
                        </div>
                    </flux:card>
                </a>
            @endforeach
        </div>

        <x-admin.monthly-chart :chart="$chart" />
    </div>
</x-admin.layout>
