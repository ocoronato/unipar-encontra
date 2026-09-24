<?php

use App\Enums\ApprovalStatus;
use App\Enums\ItemStatus;
use App\Enums\ItemType;
use App\Enums\ReturnRequestStatus;
use App\Models\LostFoundItem;
use App\Models\ReturnRequest;
use App\Models\User;
use Livewire\Attributes\Title;
use Livewire\Volt\Component;

new #[Title('Administração')] class extends Component {
    public function with(): array
    {
        return [
            'cards' => [
                ['label' => 'Total de usuários', 'value' => User::count(), 'icon' => 'users'],
                ['label' => 'Total de objetos', 'value' => LostFoundItem::count(), 'icon' => 'cube'],
                ['label' => 'Objetos perdidos', 'value' => LostFoundItem::ofType(ItemType::Lost)->count(), 'icon' => 'magnifying-glass'],
                ['label' => 'Objetos encontrados', 'value' => LostFoundItem::ofType(ItemType::Found)->count(), 'icon' => 'hand-raised'],
                ['label' => 'Aguardando aprovação', 'value' => LostFoundItem::where('approval_status', ApprovalStatus::Pending)->count(), 'icon' => 'clock'],
                ['label' => 'Solicitações pendentes', 'value' => ReturnRequest::where('status', ReturnRequestStatus::Pending)->count(), 'icon' => 'inbox'],
                ['label' => 'Objetos devolvidos', 'value' => LostFoundItem::where('status', ItemStatus::Returned)->count(), 'icon' => 'check-badge'],
            ],
        ];
    }
}; ?>

<x-admin.layout heading="Dashboard" subheading="Visão geral do UNIPAR ENCONTRA">
    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        @foreach ($cards as $card)
            <flux:card class="flex items-center gap-4">
                <div class="rounded-lg bg-zinc-100 p-3 dark:bg-zinc-700">
                    <flux:icon :name="$card['icon']" class="size-6 text-zinc-600 dark:text-zinc-300" />
                </div>
                <div>
                    <flux:text>{{ $card['label'] }}</flux:text>
                    <flux:heading size="xl">{{ $card['value'] }}</flux:heading>
                </div>
            </flux:card>
        @endforeach
    </div>
</x-admin.layout>
