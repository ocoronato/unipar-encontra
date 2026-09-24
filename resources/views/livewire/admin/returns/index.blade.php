<?php

use App\Models\ItemReturn;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Volt\Component;
use Livewire\WithPagination;

/*
| Histórico de devoluções confirmadas (item_returns). Nada aqui é excluído.
*/
new #[Title('Devoluções')] class extends Component {
    use WithPagination;

    #[Url(as: 'busca')]
    public string $search = '';

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function with(): array
    {
        return [
            'returns' => ItemReturn::query()
                ->with(['lostFoundItem.photos', 'returnRequest.user', 'administrator'])
                ->when($this->search, fn ($query) => $query->where(function ($query) {
                    $query->whereHas('lostFoundItem', fn ($q) => $q->where('title', 'like', "%{$this->search}%"))
                        ->orWhereHas('returnRequest.user', fn ($q) => $q->where('name', 'like', "%{$this->search}%"));
                }))
                ->latest('returned_at')
                ->paginate(15),
        ];
    }
}; ?>

<x-admin.layout heading="Devoluções" subheading="Histórico de objetos entregues aos donos">
    <div class="mb-4 max-w-md">
        <flux:input wire:model.live.debounce.400ms="search" icon="magnifying-glass" placeholder="Buscar por objeto ou por quem recebeu" clearable />
    </div>

    <flux:table :paginate="$returns">
        <flux:table.columns>
            <flux:table.column>Objeto</flux:table.column>
            <flux:table.column>Entregue a</flux:table.column>
            <flux:table.column>Data da entrega</flux:table.column>
            <flux:table.column>Registrado por</flux:table.column>
            <flux:table.column>Observações</flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @forelse ($returns as $return)
                <flux:table.row :key="$return->id">
                    <flux:table.cell>
                        <div class="flex items-center gap-3">
                            <x-item-thumbnail :item="$return->lostFoundItem" />
                            <a href="{{ route('items.show', $return->lostFoundItem) }}" wire:navigate class="max-w-48 truncate font-medium hover:underline">
                                {{ $return->lostFoundItem->title }}
                            </a>
                        </div>
                    </flux:table.cell>
                    <flux:table.cell>
                        <p>{{ $return->returnRequest->user->name }}</p>
                        <p class="text-xs text-zinc-500">{{ $return->returnRequest->user->email }}</p>
                    </flux:table.cell>
                    <flux:table.cell>{{ $return->returned_at->format('d/m/Y H:i') }}</flux:table.cell>
                    <flux:table.cell>{{ $return->administrator->name }}</flux:table.cell>
                    <flux:table.cell>
                        <p class="max-w-xs text-sm whitespace-normal">{{ $return->notes ?? '—' }}</p>
                    </flux:table.cell>
                </flux:table.row>
            @empty
                <flux:table.row>
                    <flux:table.cell colspan="5" class="text-center">Nenhuma devolução registrada.</flux:table.cell>
                </flux:table.row>
            @endforelse
        </flux:table.rows>
    </flux:table>
</x-admin.layout>
