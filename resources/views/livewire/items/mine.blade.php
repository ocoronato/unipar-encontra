<?php

use App\Enums\ItemStatus;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Title;
use Livewire\Volt\Component;
use Livewire\WithPagination;

new #[Title('Meus Objetos')] class extends Component {
    use WithPagination;

    /** Objeto escolhido no modal de cancelamento. */
    public ?int $cancellingId = null;

    public function confirmCancel(int $id): void
    {
        $this->cancellingId = $id;

        Flux::modal('cancel-item')->show();
    }

    public function cancel(): void
    {
        // Procura somente entre os objetos do próprio usuário.
        $item = Auth::user()->lostFoundItems()->findOrFail($this->cancellingId);

        $this->authorize('cancel', $item);

        $item->status = ItemStatus::Cancelled;
        $item->save();

        $this->reset('cancellingId');
        Flux::modal('cancel-item')->close();
        Flux::toast(variant: 'success', text: 'Publicação cancelada.');
    }

    public function with(): array
    {
        return [
            'items' => Auth::user()->lostFoundItems()
                ->with('photos')
                ->withOpenReturnRequestsFlag() // evita uma consulta por linha nas permissões
                ->latest()
                ->paginate(10),
        ];
    }
}; ?>

<div class="flex flex-col gap-6">
    <div class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <flux:heading size="xl" level="1">Meus objetos</flux:heading>
            <flux:subheading>Acompanhe e gerencie as suas publicações.</flux:subheading>
        </div>

        <div class="flex flex-wrap gap-2">
            <flux:button size="sm" icon="exclamation-circle" :href="route('items.create.lost')" wire:navigate>Cadastrar perdido</flux:button>
            <flux:button size="sm" icon="hand-raised" :href="route('items.create.found')" wire:navigate>Cadastrar encontrado</flux:button>
        </div>
    </div>

    @if ($items->isEmpty())
        <flux:card class="flex flex-col items-center gap-3 py-12 text-center">
            <flux:icon.archive-box class="size-10 text-zinc-400" />
            <flux:heading>Você ainda não cadastrou objetos</flux:heading>
            <flux:text>Perdeu ou encontrou algo? Use os botões acima para cadastrar.</flux:text>
        </flux:card>
    @else
        <flux:table :paginate="$items">
            {{-- No celular (max-sm) as colunas extras somem e os dados aparecem sob o título. --}}
            <flux:table.columns>
                <flux:table.column>Objeto</flux:table.column>
                <flux:table.column class="max-sm:hidden">Tipo</flux:table.column>
                <flux:table.column class="max-sm:hidden">Data</flux:table.column>
                <flux:table.column class="max-sm:hidden">Status</flux:table.column>
                <flux:table.column class="max-sm:hidden">Aprovação</flux:table.column>
                <flux:table.column align="end">Ações</flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @foreach ($items as $item)
                    <flux:table.row :key="$item->id">
                        <flux:table.cell>
                            <div class="flex items-center gap-3">
                                <x-item-thumbnail :item="$item" class="max-sm:hidden" />
                                <div class="min-w-0 whitespace-normal">
                                    <span class="font-medium text-zinc-800 dark:text-white">{{ $item->title }}</span>

                                    <div class="mt-1 flex flex-wrap items-center gap-1 sm:hidden">
                                        <x-status-badge :status="$item->type" />
                                        <x-status-badge :status="$item->status" />
                                        <x-status-badge :status="$item->approval_status" />
                                        <span class="text-xs text-zinc-500">{{ $item->occurred_at->format('d/m/Y') }}</span>
                                    </div>

                                    @if ($item->approval_status === \App\Enums\ApprovalStatus::Rejected && $item->moderation_notes)
                                        <p class="max-w-xs text-xs text-red-600 dark:text-red-400">
                                            Motivo: {{ $item->moderation_notes }} Edite para corrigir e reenviar.
                                        </p>
                                    @endif
                                </div>
                            </div>
                        </flux:table.cell>
                        <flux:table.cell class="max-sm:hidden"><x-status-badge :status="$item->type" /></flux:table.cell>
                        <flux:table.cell class="max-sm:hidden">{{ $item->occurred_at->format('d/m/Y') }}</flux:table.cell>
                        <flux:table.cell class="max-sm:hidden"><x-status-badge :status="$item->status" /></flux:table.cell>
                        <flux:table.cell class="max-sm:hidden"><x-status-badge :status="$item->approval_status" /></flux:table.cell>
                        <flux:table.cell align="end">
                            {{-- No celular os botões mostram só o ícone (o texto continua para leitores de tela). --}}
                            <div class="flex justify-end gap-1">
                                <flux:button size="sm" variant="ghost" icon="eye" :href="route('items.show', $item)" wire:navigate>
                                    <span class="max-sm:sr-only">Ver</span>
                                </flux:button>

                                @can('update', $item)
                                    <flux:button size="sm" variant="ghost" icon="pencil-square" :href="route('items.edit', $item)" wire:navigate>
                                        <span class="max-sm:sr-only">Editar</span>
                                    </flux:button>
                                @endcan

                                @can('cancel', $item)
                                    <flux:button size="sm" variant="ghost" icon="x-circle" wire:click="confirmCancel({{ $item->id }})">
                                        <span class="max-sm:sr-only">Cancelar</span>
                                    </flux:button>
                                @endcan
                            </div>
                        </flux:table.cell>
                    </flux:table.row>
                @endforeach
            </flux:table.rows>
        </flux:table>

        <flux:text class="text-xs">
            Publicações com solicitação de devolução em andamento, devolvidas ou canceladas não podem ser editadas.
        </flux:text>
    @endif

    <flux:modal name="cancel-item" class="max-w-md">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">Cancelar publicação?</flux:heading>
                <flux:text class="mt-2">
                    A publicação deixará de aparecer na busca. Se você perdeu o objeto e já o encontrou, esta é a opção certa.
                    Esta ação não pode ser desfeita.
                </flux:text>
            </div>

            <div class="flex justify-end gap-2">
                <flux:modal.close>
                    <flux:button variant="ghost">Voltar</flux:button>
                </flux:modal.close>
                <flux:button variant="danger" wire:click="cancel">Cancelar publicação</flux:button>
            </div>
        </div>
    </flux:modal>
</div>
