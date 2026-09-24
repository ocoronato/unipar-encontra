{{--
    Card de objeto usado na Home e na Busca.
    Espera o item com "category", "location" e "photos" carregados (eager loading).
--}}
@props(['item', 'showStatus' => false])

@php $photo = $item->photos->first(); @endphp

<a
    href="{{ route('items.show', $item) }}"
    wire:navigate
    class="group flex flex-col overflow-hidden rounded-xl border border-zinc-200 bg-white transition hover:border-accent hover:shadow-md dark:border-zinc-700 dark:bg-zinc-900"
>
    <div class="relative aspect-[4/3] bg-zinc-100 dark:bg-zinc-800">
        @if ($photo)
            <img src="{{ $photo->url() }}" alt="Foto de {{ $item->title }}" class="size-full object-cover" loading="lazy">
        @else
            <div class="flex size-full items-center justify-center text-zinc-400 dark:text-zinc-500">
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
        <h3 class="line-clamp-1 font-semibold text-zinc-900 group-hover:text-accent-content dark:text-white">{{ $item->title }}</h3>

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
