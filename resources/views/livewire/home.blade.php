<?php

use App\Enums\ItemStatus;
use App\Enums\ItemType;
use App\Models\LostFoundItem;
use Livewire\Attributes\Title;
use Livewire\Volt\Component;

new #[Title('Início')] class extends Component {
    /**
     * Objetos aprovados e disponíveis mais recentes de um tipo.
     */
    private function recent(ItemType $type, int $limit)
    {
        return LostFoundItem::approved()
            ->ofType($type)
            ->where('status', ItemStatus::Active)
            ->with(['category', 'location', 'photos'])
            ->latest()
            ->limit($limit)
            ->get();
    }

    public function with(): array
    {
        return [
            'foundItems' => $this->recent(ItemType::Found, 8),
            'lostItems' => $this->recent(ItemType::Lost, 4),
        ];
    }
}; ?>

<div class="flex flex-col gap-10">
    {{-- Destaque principal --}}
    <section class="rounded-2xl bg-linear-to-br from-blue-700 to-blue-900 px-6 py-10 text-white shadow-sm sm:px-10 sm:py-14">
        <div class="mx-auto flex max-w-3xl flex-col items-center gap-6 text-center">
            <div class="flex items-center gap-3">
                <x-app-logo-icon class="size-10" />
                <h1 class="text-3xl font-bold tracking-wide sm:text-4xl">UNIPAR ENCONTRA</h1>
            </div>

            <p class="text-lg text-blue-100 sm:text-xl">
                Perdeu alguma coisa? Encontrou um objeto? Nós ajudamos a conectar.
            </p>

            <form method="GET" action="{{ route('items.search') }}" class="flex w-full max-w-xl gap-2">
                <label for="home-search" class="sr-only">Buscar objetos</label>
                <input
                    id="home-search"
                    type="search"
                    name="q"
                    placeholder="Ex.: carteira, chave, celular..."
                    class="w-full rounded-lg border-0 bg-white px-4 py-3 text-zinc-900 placeholder-zinc-400 shadow-sm focus:ring-2 focus:ring-blue-300 focus:outline-none"
                >
                <button type="submit" class="flex items-center gap-2 rounded-lg bg-blue-950 px-5 font-medium text-white hover:bg-black">
                    <flux:icon.magnifying-glass variant="mini" />
                    <span class="max-sm:hidden">Buscar</span>
                </button>
            </form>

            <div class="grid w-full max-w-xl gap-3 sm:grid-cols-2">
                <a href="{{ route('items.create.lost') }}" wire:navigate
                   class="flex items-center justify-center gap-2 rounded-lg bg-white px-5 py-3 font-semibold text-blue-800 shadow-sm transition hover:bg-blue-50">
                    <flux:icon.exclamation-circle variant="mini" /> PERDI UM OBJETO
                </a>
                <a href="{{ route('items.create.found') }}" wire:navigate
                   class="flex items-center justify-center gap-2 rounded-lg border-2 border-white px-5 py-3 font-semibold text-white transition hover:bg-white/10">
                    <flux:icon.hand-raised variant="mini" /> ENCONTREI UM OBJETO
                </a>
            </div>
        </div>
    </section>

    {{-- Objetos encontrados recentemente --}}
    <section>
        <div class="mb-4 flex items-end justify-between gap-4">
            <div>
                <flux:heading size="lg" level="2">Objetos encontrados recentemente</flux:heading>
                <flux:text>Reconheceu algum? Abra os detalhes e solicite a devolução.</flux:text>
            </div>
            <flux:link :href="route('items.search', ['tipo' => 'found'])" wire:navigate class="shrink-0 text-sm">Ver todos</flux:link>
        </div>

        @if ($foundItems->isEmpty())
            <flux:card class="text-center">
                <flux:text>Nenhum objeto encontrado cadastrado no momento.</flux:text>
            </flux:card>
        @else
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
                @foreach ($foundItems as $item)
                    <x-item-card :item="$item" wire:key="found-{{ $item->id }}" />
                @endforeach
            </div>
        @endif
    </section>

    {{-- Objetos perdidos recentemente (só aparece se houver) --}}
    @if ($lostItems->isNotEmpty())
        <section>
            <div class="mb-4 flex items-end justify-between gap-4">
                <div>
                    <flux:heading size="lg" level="2">Objetos perdidos recentemente</flux:heading>
                    <flux:text>Viu algum destes por aí? Cadastre como encontrado.</flux:text>
                </div>
                <flux:link :href="route('items.search', ['tipo' => 'lost'])" wire:navigate class="shrink-0 text-sm">Ver todos</flux:link>
            </div>

            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
                @foreach ($lostItems as $item)
                    <x-item-card :item="$item" wire:key="lost-{{ $item->id }}" />
                @endforeach
            </div>
        </section>
    @endif
</div>
