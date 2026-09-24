{{-- Layout das páginas da administração. "bare" = sem menu (usado na versão para impressão). --}}
@props(['heading' => '', 'subheading' => '', 'bare' => false])

@unless ($bare)
    @php
        // Contadores de pendências exibidos ao lado dos itens do menu.
        $pendingItems = \App\Models\LostFoundItem::where('approval_status', \App\Enums\ApprovalStatus::Pending)->count();
        $pendingRequests = \App\Models\ReturnRequest::where('status', \App\Enums\ReturnRequestStatus::Pending)->count();

        $menu = [
            'Visão geral' => [
                ['label' => 'Dashboard', 'icon' => 'chart-bar', 'route' => 'admin.dashboard'],
            ],
            'Cadastros' => [
                ['label' => 'Usuários', 'icon' => 'users', 'route' => 'admin.users.index'],
                ['label' => 'Categorias', 'icon' => 'tag', 'route' => 'admin.categories.index'],
                ['label' => 'Locais', 'icon' => 'map-pin', 'route' => 'admin.locations.index'],
            ],
            'Movimentos' => [
                ['label' => 'Objetos', 'icon' => 'cube', 'route' => 'admin.items.index', 'badge' => $pendingItems ?: null],
                ['label' => 'Solicitações', 'icon' => 'inbox', 'route' => 'admin.return-requests.index', 'badge' => $pendingRequests ?: null],
                ['label' => 'Devoluções', 'icon' => 'check-badge', 'route' => 'admin.returns.index'],
            ],
            'Relatórios' => [
                ['label' => 'Objetos Perdidos', 'icon' => 'document-text', 'route' => 'admin.reports.lost'],
                ['label' => 'Objetos Encontrados', 'icon' => 'document-text', 'route' => 'admin.reports.found'],
                ['label' => 'Objetos Devolvidos', 'icon' => 'document-text', 'route' => 'admin.reports.returned'],
                ['label' => 'Solicitações', 'icon' => 'document-text', 'route' => 'admin.reports.requests'],
            ],
        ];
    @endphp
@endunless

<div class="flex items-start max-md:flex-col">
    @unless ($bare)
        <div class="mr-10 w-full pb-4 md:w-[230px] print:hidden">
            <flux:navlist>
                @foreach ($menu as $group => $items)
                    <flux:navlist.group :heading="$group" class="mb-3">
                        @foreach ($items as $item)
                            <flux:navlist.item
                                :icon="$item['icon']"
                                :href="route($item['route'])"
                                :current="request()->routeIs($item['route'])"
                                :badge="$item['badge'] ?? null"
                                wire:navigate
                            >{{ $item['label'] }}</flux:navlist.item>
                        @endforeach
                    </flux:navlist.group>
                @endforeach
            </flux:navlist>
        </div>

        <flux:separator class="md:hidden print:hidden" />
    @endunless

    <div class="w-full flex-1 self-stretch max-md:pt-6">
        <flux:heading size="xl" level="1">{{ $heading }}</flux:heading>
        @if ($subheading)
            <flux:subheading size="lg">{{ $subheading }}</flux:subheading>
        @endif

        <div class="mt-6 w-full">
            {{ $slot }}
        </div>
    </div>
</div>
