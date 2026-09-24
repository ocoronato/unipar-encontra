{{-- Badge para qualquer enum com label() e color(). Ex.: <x-status-badge :status="$item->type" /> --}}
@props(['status'])

<flux:badge size="sm" :color="$status->color()" {{ $attributes }}>{{ $status->label() }}</flux:badge>
