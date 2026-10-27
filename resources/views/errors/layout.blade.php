<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('partials.head', ['title' => $title])
    </head>
    <body class="relative isolate flex min-h-screen items-center justify-center overflow-hidden bg-zinc-50 p-6 dark:bg-zinc-950">
        <div aria-hidden="true" class="glow-red pointer-events-none absolute inset-0 -z-10 opacity-50 dark:opacity-100"></div>

        <div class="flex max-w-md flex-col items-center gap-4 text-center">
            <a href="{{ url('/') }}" class="flex items-center gap-2">
                <x-app-logo />
            </a>
            <p class="text-6xl font-bold text-accent-content">{{ $code }}</p>
            <h1 class="text-xl font-semibold text-zinc-900 dark:text-white">{{ $title }}</h1>
            <p class="text-zinc-600 dark:text-zinc-400">{{ $message }}</p>
            <a href="{{ url('/') }}" class="mt-2 rounded-lg bg-accent px-5 py-2.5 font-medium text-accent-foreground hover:opacity-90">
                Voltar ao início
            </a>
        </div>
    </body>
</html>
