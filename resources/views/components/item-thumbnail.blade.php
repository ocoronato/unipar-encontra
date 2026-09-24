{{-- Miniatura da primeira foto do objeto (ou ícone). Espera "photos" carregado. --}}
@props(['item', 'size' => 'sm'])

@php $photo = $item->photos->first(); @endphp

<div {{ $attributes->class([
    'flex shrink-0 items-center justify-center overflow-hidden rounded-lg bg-zinc-100 text-zinc-400 dark:bg-zinc-700',
    'size-10' => $size === 'sm',
    'size-16' => $size === 'lg',
]) }}>
    @if ($photo)
        <img src="{{ $photo->url() }}" alt="" class="size-full object-cover" loading="lazy">
    @else
        <flux:icon.photo variant="mini" />
    @endif
</div>
