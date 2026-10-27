{{--
    Card de objeto usado na Home e na Busca.
    Espera o item com "category", "location" e "photos" carregados (eager loading).
    Efeitos: brilho que segue o mouse (.spotlight + x-spotlight) e entrada suave (.fade-up, atraso em --delay).
--}}
@props(['item', 'showStatus' => false])

@php $photo = $item->photos->first(); @endphp

<a
    href="{{ route('items.show', $item) }}"
    wire:navigate
    x-data
    x-spotlight
    {{ $attributes->class('spotlight fade-up group flex flex-col overflow-hidden rounded-xl border border-zinc-200 bg-white transition duration-300 hover:-translate-y-1 hover:border-red-500/50 hover:shadow-[0_14px_40px_-16px_rgb(220_38_38/0.55)] dark:border-white/10 dark:bg-zinc-900/70') }}
>
    <div class="relative aspect-[4/3] overflow-hidden bg-zinc-100 dark:bg-zinc-800/60">
        @if ($photo)
            <img src="{{ $photo->url() }}" alt="Foto de {{ $item->title }}" class="size-full object-cover transition duration-500 group-hover:scale-105" loading="lazy">
        @else
            <div class="flex size-full items-center justify-center text-zinc-400 transition duration-500 group-hover:scale-110 dark:text-zinc-600">
                <flux:icon.photo class="size-10" />
            </div>
        @endif

        <div class="absolute top-2 left-2 flex gap-1">
            <x-status-badge :status="$item->type" />
            @if ($showStatus)
                <x-status-badge :status="$item->status" />
            @endif
        </div>
    </div>

    <div class="flex flex-1 flex-col gap-1.5 p-4">
        <h3 class="line-clamp-1 font-semibold text-zinc-900 transition group-hover:text-accent-content dark:text-white">{{ $item->title }}</h3>

        <div class="flex items-center gap-1.5 text-sm text-zinc-500 dark:text-zinc-400">
            <flux:icon.tag variant="micro" /> {{ $item->category->name }}
        </div>
        <div class="flex items-center gap-1.5 text-sm text-zinc-500 dark:text-zinc-400">
            <flux:icon.map-pin variant="micro" /> {{ $item->location->name }}
        </div>
        <div class="flex items-center gap-1.5 text-sm text-zinc-500 dark:text-zinc-400">
            <flux:icon.calendar variant="micro" /> {{ $item->occurred_at->format('d/m/Y') }}
        </div>
    </div>
</a>
