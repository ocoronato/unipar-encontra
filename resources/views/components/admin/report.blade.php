{{--
    Estrutura comum dos relatórios:
    filtros (fora da impressão) · filtros aplicados · resumo · tabela.
--}}
@props([
    'printing' => false,
    'printUrl',
    'applied' => [],   // textos dos filtros aplicados
    'shown',           // linhas exibidas
    'total',           // linhas encontradas
])

<div class="space-y-6">
    @unless ($printing)
        <flux:card class="space-y-4 print:hidden">
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                {{ $filters }}
            </div>

            <div class="flex flex-wrap justify-end gap-2">
                <flux:button size="sm" variant="ghost" icon="x-mark" wire:click="clearFilters">Limpar filtros</flux:button>
                <flux:button size="sm" variant="primary" icon="printer" :href="$printUrl">Versão para impressão</flux:button>
            </div>
        </flux:card>
    @endunless

    <p class="text-sm text-zinc-600 dark:text-zinc-300">
        <span class="font-medium">Filtros:</span> {{ $applied ? implode(' · ', $applied) : 'nenhum (todos os registros)' }}
    </p>

    @isset($summary)
        <div class="flex flex-wrap gap-3">{{ $summary }}</div>
    @endisset

    @if ($total > $shown)
        <flux:callout icon="exclamation-triangle" color="amber" :heading="'Exibindo os primeiros '.$shown.' de '.$total.' registros. Use os filtros para reduzir o resultado.'" />
    @endif

    <div wire:loading.delay.class="opacity-50" class="overflow-x-auto">
        @if ($shown === 0)
            <p class="py-8 text-center text-sm text-zinc-500">Nenhum registro encontrado para os filtros escolhidos.</p>
        @else
            {{ $slot }}
        @endif
    </div>
</div>
