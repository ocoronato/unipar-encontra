<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-white antialiased dark:bg-zinc-950">
        <div class="relative isolate flex min-h-svh flex-col items-center justify-center gap-6 overflow-hidden p-6 md:p-10">
            {{-- Fundo: brilho vermelho e grade (efeitos definidos no app.css) --}}
            <div aria-hidden="true" class="glow-red animate-glow pointer-events-none absolute inset-0 -z-10 opacity-50 dark:opacity-100"></div>
            <div aria-hidden="true" class="bg-grid pointer-events-none absolute inset-0 -z-10 [--grid-line:rgb(0_0_0/0.05)] dark:[--grid-line:rgb(255_255_255/0.06)]"></div>

            <div class="fade-up flex w-full max-w-sm flex-col gap-2">
                <a href="{{ route('home') }}" class="mx-auto mb-2 flex items-center gap-2" wire:navigate>
                    <x-app-logo />
                </a>
                <div class="flex flex-col gap-6 rounded-2xl border border-zinc-200 bg-white/80 p-6 shadow-xl backdrop-blur dark:border-white/10 dark:bg-zinc-900/70 dark:shadow-red-950/40">
                    {{ $slot }}
                </div>

                {{-- Instituição (logo com texto branco no tema escuro e original no claro) --}}
                <div class="mt-6 flex flex-col items-center gap-3">
                    <span class="text-xs tracking-wide text-zinc-500 dark:text-zinc-400">Projeto Integrador · Sistemas de Informação</span>
                    <img src="{{ asset('img/unipar/unipar-logo-branco.png') }}" alt="UNIPAR – Universidade Paranaense" class="hidden h-10 w-auto dark:block">
                    <img src="{{ asset('img/unipar/unipar-logo.png') }}" alt="UNIPAR – Universidade Paranaense" class="h-10 w-auto dark:hidden">
                </div>
            </div>
        </div>
        @fluxScripts
    </body>
</html>
