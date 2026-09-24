{{-- Miniatura de foto com botão de remover. Ex.: <x-removable-photo :src="$url" wire:click="removePhoto(0)" wire:key="..." /> --}}
@props(['src', 'label' => 'Remover foto'])

<div {{ $attributes->only('wire:key')->class('relative') }}>
    <img src="{{ $src }}" alt="" class="aspect-square w-full rounded-lg object-cover">
    <button
        type="button"
        {{ $attributes->only('wire:click') }}
        class="absolute top-1 right-1 rounded-full bg-black/60 p-1 text-white hover:bg-black"
        aria-label="{{ $label }}"
    >
        <flux:icon.x-mark variant="micro" />
    </button>
</div>
