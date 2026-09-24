{{-- Conteúdo do menu do usuário (reutilizado no desktop e no celular). --}}
<flux:menu class="w-[230px]">
    <div class="flex items-center gap-2 px-1 py-1.5 text-left text-sm">
        <span class="flex size-8 shrink-0 items-center justify-center rounded-lg bg-zinc-200 font-medium text-zinc-900 dark:bg-zinc-700 dark:text-white">
            {{ auth()->user()->initials() }}
        </span>
        <div class="grid flex-1 leading-tight">
            <span class="truncate font-semibold">{{ auth()->user()->name }}</span>
            <span class="truncate text-xs text-zinc-500">{{ auth()->user()->email }}</span>
        </div>
    </div>

    <flux:menu.separator />

    <flux:menu.item :href="route('settings.profile')" icon="user-circle" wire:navigate>Meu perfil</flux:menu.item>

    <flux:menu.separator />

    <form method="POST" action="{{ route('logout') }}" class="w-full">
        @csrf
        <flux:menu.item as="button" type="submit" icon="arrow-right-start-on-rectangle" class="w-full">Sair</flux:menu.item>
    </form>
</flux:menu>
