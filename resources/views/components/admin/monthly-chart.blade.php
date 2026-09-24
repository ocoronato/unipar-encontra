{{--
    Gráfico de colunas agrupadas: objetos perdidos x encontrados por mês.
    Feito só com HTML/CSS (sem biblioteca). O tooltip aparece no hover e no foco
    do teclado, e a tabela em "Ver dados em tabela" traz todos os valores.

    $chart = ['months' => [['label', 'fullLabel', 'lost', 'found'], ...], 'scale' => int, 'total' => int]
--}}
@props(['chart'])

@php
    $scale = $chart['scale'];
    $height = fn ($value) => round($value / $scale * 100, 2); // altura em % da área do gráfico
    $series = [
        'lost' => ['label' => 'Perdidos', 'one' => 'perdido', 'many' => 'perdidos', 'color' => 'var(--series-lost)'],
        'found' => ['label' => 'Encontrados', 'one' => 'encontrado', 'many' => 'encontrados', 'color' => 'var(--series-found)'],
    ];
    // Ex.: 1 perdido, 3 perdidos
    $count = fn (int $value, string $key) => $value.' '.($value === 1 ? $series[$key]['one'] : $series[$key]['many']);
@endphp

<flux:card class="viz-root">
    <div class="flex flex-wrap items-start justify-between gap-3">
        <div>
            <flux:heading>Objetos cadastrados por mês</flux:heading>
            <flux:text class="text-sm">Últimos 6 meses · {{ $chart['total'] }} no total</flux:text>
        </div>

        {{-- Legenda (a cor nunca é o único jeito de identificar a série) --}}
        <div class="flex gap-4 text-sm text-zinc-600 dark:text-zinc-300">
            @foreach ($series as $serie)
                <span class="flex items-center gap-1.5">
                    <span class="size-3 rounded-sm" style="background: {{ $serie['color'] }}"></span>
                    {{ $serie['label'] }}
                </span>
            @endforeach
        </div>
    </div>

    @if ($chart['total'] === 0)
        <flux:text class="py-12 text-center">Nenhum objeto cadastrado nos últimos 6 meses.</flux:text>
    @else
        <div class="mt-6 flex gap-2">
            {{-- Eixo Y: 0, metade e máximo --}}
            <div class="relative h-48 w-6 shrink-0 text-right text-xs text-zinc-500 tabular-nums">
                @foreach ([0, $scale / 2, $scale] as $tick)
                    <span class="absolute right-0 translate-y-1/2" style="bottom: {{ $height($tick) }}%">{{ $tick }}</span>
                @endforeach
            </div>

            <div class="min-w-0 flex-1">
                <div class="relative h-48">
                    {{-- Linhas de grade (hairline) --}}
                    @foreach ([0, 50, 100] as $position)
                        <div class="absolute inset-x-0 border-t" style="bottom: {{ $position }}%; border-color: var(--viz-grid)"></div>
                    @endforeach

                    <div class="absolute inset-0 flex">
                        @foreach ($chart['months'] as $month)
                            {{-- Faixa do mês inteira é a área de hover/foco (maior que as colunas) --}}
                            <div
                                tabindex="0"
                                aria-label="{{ $month['fullLabel'] }}: {{ $count($month['lost'], 'lost') }} e {{ $count($month['found'], 'found') }}"
                                class="group relative flex h-full flex-1 items-end justify-center gap-0.5 rounded outline-none focus-visible:ring-2 focus-visible:ring-accent"
                            >
                                @foreach ($series as $key => $serie)
                                    <div
                                        class="w-3 rounded-t-[4px] transition-opacity group-hover:opacity-75 group-focus:opacity-75 sm:w-4"
                                        style="height: {{ $height($month[$key]) }}%; background: {{ $serie['color'] }}"
                                    ></div>
                                @endforeach

                                {{-- Tooltip: valor em destaque, nome da série em segundo plano --}}
                                <div @class([
                                    'pointer-events-none absolute top-0 z-10 hidden rounded-lg border border-zinc-200 bg-white px-3 py-2 text-xs whitespace-nowrap shadow-lg group-hover:block group-focus:block dark:border-zinc-600 dark:bg-zinc-800',
                                    'left-0' => $loop->first,
                                    'right-0' => $loop->last,
                                    'left-1/2 -translate-x-1/2' => ! $loop->first && ! $loop->last,
                                ])>
                                    <p class="mb-1 text-zinc-500 dark:text-zinc-400">{{ ucfirst($month['fullLabel']) }}</p>
                                    @foreach ($series as $key => $serie)
                                        <p class="flex items-center gap-2">
                                            <span class="h-0.5 w-3 rounded" style="background: {{ $serie['color'] }}"></span>
                                            <strong class="text-zinc-900 dark:text-white">{{ $month[$key] }}</strong>
                                            <span class="text-zinc-500 dark:text-zinc-400">{{ $month[$key] === 1 ? $serie['one'] : $serie['many'] }}</span>
                                        </p>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

                {{-- Eixo X --}}
                <div class="mt-2 flex text-xs text-zinc-500">
                    @foreach ($chart['months'] as $month)
                        <span class="flex-1 text-center">{{ $month['label'] }}</span>
                    @endforeach
                </div>
            </div>
        </div>

        {{-- Mesmos dados em tabela (acessibilidade) --}}
        <details class="mt-4 text-sm">
            <summary class="cursor-pointer text-zinc-600 hover:text-zinc-900 dark:text-zinc-400 dark:hover:text-white">Ver dados em tabela</summary>
            <table class="mt-2 w-full max-w-md text-left tabular-nums">
                <thead class="text-zinc-500">
                    <tr><th class="py-1 font-medium">Mês</th><th class="py-1 font-medium">Perdidos</th><th class="py-1 font-medium">Encontrados</th></tr>
                </thead>
                <tbody>
                    @foreach ($chart['months'] as $month)
                        <tr class="border-t border-zinc-100 dark:border-zinc-700">
                            <td class="py-1">{{ ucfirst($month['fullLabel']) }}</td>
                            <td class="py-1">{{ $month['lost'] }}</td>
                            <td class="py-1">{{ $month['found'] }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </details>
    @endif
</flux:card>
