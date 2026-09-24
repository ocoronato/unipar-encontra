{{-- Layout da versão para impressão dos relatórios: sem menus, sempre no tema claro. --}}
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1.0" />
        <title>{{ isset($title) ? $title.' | ' : '' }}{{ config('app.name') }}</title>
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="min-h-screen bg-white text-zinc-900 antialiased">
        <div class="mx-auto max-w-5xl p-6 sm:p-10 print:max-w-none print:p-0">
            {{-- Ações (não aparecem no papel) --}}
            <div x-data class="mb-6 flex justify-end gap-2 print:hidden">
                <flux:button variant="ghost" icon="arrow-left" x-on:click="history.back()">Voltar</flux:button>
                <flux:button variant="primary" icon="printer" x-on:click="window.print()">Imprimir</flux:button>
            </div>

            <header class="mb-6 flex items-center justify-between gap-4 border-b border-zinc-300 pb-4">
                <div class="flex items-center gap-2">
                    <x-app-logo />
                </div>
                <p class="text-right text-xs text-zinc-500">
                    Gerado em {{ now()->format('d/m/Y') }} às {{ now()->format('H:i') }}<br>
                    por {{ auth()->user()->name }}
                </p>
            </header>

            {{ $slot }}

            <footer class="mt-8 border-t border-zinc-200 pt-3 text-xs text-zinc-500">
                UNIPAR ENCONTRA · Sistema de Achados e Perdidos
            </footer>
        </div>

        @fluxScripts
    </body>
</html>
