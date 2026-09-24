<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-zinc-50 dark:bg-zinc-800">
        <flux:sidebar sticky stashable class="border-r border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900 print:hidden">
            <flux:sidebar.toggle class="lg:hidden" icon="x-mark" />

            <a href="{{ route('home') }}" class="mr-5 flex items-center gap-2" wire:navigate>
                <x-app-logo />
            </a>

            <flux:navlist variant="outline">
                <flux:navlist.group heading="Achados e Perdidos" class="grid">
                    <flux:navlist.item icon="home" :href="route('home')" :current="request()->routeIs('home')" wire:navigate>Início</flux:navlist.item>
                    <flux:navlist.item icon="magnifying-glass" :href="route('items.search')" :current="request()->routeIs('items.search', 'items.show')" wire:navigate>Buscar Objetos</flux:navlist.item>
                </flux:navlist.group>

                @auth
                    <flux:navlist.group heading="Cadastrar" class="grid">
                        <flux:navlist.item icon="exclamation-circle" :href="route('items.create.lost')" :current="request()->routeIs('items.create.lost')" wire:navigate>Cadastrar Perdido</flux:navlist.item>
                        <flux:navlist.item icon="hand-raised" :href="route('items.create.found')" :current="request()->routeIs('items.create.found')" wire:navigate>Cadastrar Encontrado</flux:navlist.item>
                    </flux:navlist.group>

                    <flux:navlist.group heading="Minha conta" class="grid">
                        <flux:navlist.item icon="archive-box" :href="route('items.mine')" :current="request()->routeIs('items.mine')" wire:navigate>Meus Objetos</flux:navlist.item>
                        <flux:navlist.item icon="inbox" :href="route('return-requests.mine')" :current="request()->routeIs('return-requests.mine')" wire:navigate>Minhas Solicitações</flux:navlist.item>
                        <flux:navlist.item icon="user-circle" :href="route('settings.profile')" :current="request()->routeIs('settings.*')" wire:navigate>Perfil</flux:navlist.item>
                    </flux:navlist.group>

                    @can('access-admin')
                        <flux:navlist.group heading="Gestão" class="grid">
                            <flux:navlist.item icon="shield-check" :href="route('admin.dashboard')" :current="request()->routeIs('admin.*')" wire:navigate>Administração</flux:navlist.item>
                        </flux:navlist.group>
                    @endcan
                @endauth
            </flux:navlist>

            <flux:spacer />

            @auth
                <flux:dropdown position="bottom" align="start" class="max-lg:hidden">
                    <flux:profile :name="auth()->user()->name" :initials="auth()->user()->initials()" icon-trailing="chevrons-up-down" />
                    @include('partials.user-menu')
                </flux:dropdown>
            @else
                <div class="grid gap-2">
                    <flux:button variant="primary" :href="route('login')" wire:navigate>Entrar</flux:button>
                    <flux:button variant="ghost" :href="route('register')" wire:navigate>Criar conta</flux:button>
                </div>
            @endauth
        </flux:sidebar>

        {{-- Cabeçalho do celular --}}
        <flux:header class="border-b border-zinc-200 bg-white lg:hidden dark:border-zinc-700 dark:bg-zinc-900 print:hidden">
            <flux:sidebar.toggle class="lg:hidden" icon="bars-2" inset="left" />

            <a href="{{ route('home') }}" class="ml-2 flex items-center gap-2" wire:navigate>
                <x-app-logo />
            </a>

            <flux:spacer />

            @auth
                <flux:dropdown position="top" align="end">
                    <flux:profile :initials="auth()->user()->initials()" icon-trailing="chevron-down" />
                    @include('partials.user-menu')
                </flux:dropdown>
            @else
                <flux:button size="sm" variant="primary" :href="route('login')" wire:navigate>Entrar</flux:button>
            @endauth
        </flux:header>

        {{ $slot }}

        @fluxScripts
    </body>
</html>
