{{-- Página provisória para telas que serão implementadas nas próximas etapas. --}}
<x-layouts.app :title="$title">
    <div class="mx-auto flex max-w-md flex-col items-center gap-3 py-24 text-center">
        <div class="rounded-full bg-zinc-100 p-4 dark:bg-zinc-700">
            <flux:icon.wrench-screwdriver class="size-8 text-zinc-500" />
        </div>
        <flux:heading size="xl" level="1">{{ $title }}</flux:heading>
        <flux:text>Esta tela está em desenvolvimento e será disponibilizada em breve.</flux:text>
        <flux:button :href="route('home')" wire:navigate class="mt-2">Voltar ao início</flux:button>
    </div>
</x-layouts.app>
