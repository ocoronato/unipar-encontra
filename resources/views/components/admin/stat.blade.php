{{-- Número de resumo dos relatórios. Ex.: <x-admin.stat label="Total" :value="12" /> --}}
@props(['label', 'value'])

<div class="min-w-28 rounded-lg border border-zinc-200 px-4 py-2 dark:border-zinc-700 print:border-zinc-300">
    <p class="text-xs text-zinc-500">{{ $label }}</p>
    <p class="text-lg font-semibold">{{ $value }}</p>
</div>
