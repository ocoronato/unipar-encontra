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
    {{-- Destaque principal: sempre escuro, com brilho vermelho e grade (efeitos no app.css) --}}
    <section class="fade-up relative isolate overflow-hidden rounded-3xl border border-white/10 bg-zinc-950 px-6 py-12 text-white shadow-2xl shadow-red-950/30 sm:px-10 sm:py-16">
        <div aria-hidden="true" class="glow-red animate-glow pointer-events-none absolute inset-0 -z-10"></div>
        <div aria-hidden="true" class="bg-grid pointer-events-none absolute inset-0 -z-10"></div>

        <div class="mx-auto flex max-w-3xl flex-col items-center gap-6 text-center">
            <span class="inline-flex items-center gap-2 rounded-full border border-red-500/30 bg-red-500/10 py-1 pr-3 pl-2 text-xs font-medium tracking-wide text-red-200">
                <img src="{{ asset('img/unipar/unipar-simbolo.png') }}" alt="" class="h-4 w-auto">
                Achados e perdidos da UNIPAR
            </span>

            <div class="flex items-center gap-3">
                <x-app-logo-icon class="size-10 shrink-0 text-red-500 drop-shadow-[0_0_12px_rgb(239_68_68/0.6)]" />
                <h1 class="text-shine text-3xl font-bold tracking-tight sm:text-5xl">UNIPAR ENCONTRA</h1>
            </div>

            <p class="text-lg text-zinc-300 sm:text-xl">
                Perdeu alguma coisa? Encontrou um objeto? Nós ajudamos a conectar.
            </p>

            <form method="GET" action="{{ route('items.search') }}" class="flex w-full max-w-xl gap-2">
                <label for="home-search" class="sr-only">Buscar objetos</label>
                <input
                    id="home-search"
                    type="search"
                    name="q"
                    placeholder="Ex.: carteira, chave, celular..."
                    class="w-full rounded-xl border border-white/10 bg-white/5 px-4 py-3 text-white placeholder-zinc-400 backdrop-blur transition focus:border-red-500/60 focus:bg-white/10 focus:ring-4 focus:ring-red-500/20 focus:outline-none"
                >
                <button type="submit" class="btn-sheen flex shrink-0 items-center gap-2 rounded-xl bg-red-600 px-5 font-semibold text-white shadow-[0_0_24px_-6px_rgb(220_38_38/0.8)] transition hover:bg-red-700">
                    <flux:icon.magnifying-glass variant="mini" />
                    <span class="max-sm:hidden">Buscar</span>
                </button>
            </form>

            <div class="grid w-full max-w-xl gap-3 sm:grid-cols-2">
                <a href="{{ route('items.create.lost') }}" wire:navigate
                   class="btn-sheen flex items-center justify-center gap-2 rounded-xl bg-red-600 px-5 py-3 font-semibold text-white shadow-[0_0_30px_-8px_rgb(220_38_38/0.9)] transition hover:-translate-y-0.5 hover:bg-red-700">
                    <flux:icon.exclamation-circle variant="mini" /> PERDI UM OBJETO
                </a>
                <a href="{{ route('items.create.found') }}" wire:navigate
                   class="btn-sheen flex items-center justify-center gap-2 rounded-xl border border-white/15 bg-white/5 px-5 py-3 font-semibold text-white backdrop-blur transition hover:-translate-y-0.5 hover:border-red-500/50 hover:bg-white/10">
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
                    <x-item-card :item="$item" wire:key="found-{{ $item->id }}" style="--delay: {{ $loop->index * 60 }}ms" />
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
                    <x-item-card :item="$item" wire:key="lost-{{ $item->id }}" style="--delay: {{ $loop->index * 60 }}ms" />
                @endforeach
            </div>
        </section>
    @endif
</div>
