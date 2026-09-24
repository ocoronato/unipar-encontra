<?php

use App\Enums\ReturnRequestStatus;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Title;
use Livewire\Volt\Component;
use Livewire\WithPagination;

new #[Title('Minhas Solicitações')] class extends Component {
    use WithPagination;

    /** Solicitação escolhida no modal de cancelamento. */
    public ?int $cancellingId = null;

    public function confirmCancel(int $id): void
    {
        $this->cancellingId = $id;

        Flux::modal('cancel-request')->show();
    }

    public function cancel(): void
    {
        // Procura somente entre as solicitações do próprio usuário.
        $request = Auth::user()->returnRequests()->findOrFail($this->cancellingId);

        $this->authorize('cancel', $request);

        $request->status = ReturnRequestStatus::Cancelled;
        $request->save();

        $this->reset('cancellingId');
        Flux::modal('cancel-request')->close();
        Flux::toast(variant: 'success', text: 'Solicitação cancelada.');
    }

    public function with(): array
    {
        return [
            'requests' => Auth::user()->returnRequests()
                ->with(['lostFoundItem.photos', 'itemReturn'])
                ->latest()
                ->paginate(10),
        ];
    }
}; ?>

<div class="flex flex-col gap-6">
    <div>
        <flux:heading size="xl" level="1">Minhas solicitações</flux:heading>
        <flux:subheading>Pedidos de devolução que você fez para objetos encontrados.</flux:subheading>
    </div>

    @if ($requests->isEmpty())
        <flux:card class="flex flex-col items-center gap-3 py-12 text-center">
            <flux:icon.inbox class="size-10 text-zinc-400" />
            <flux:heading>Você ainda não fez solicitações</flux:heading>
            <flux:text>Encontrou algo seu na busca? Abra o objeto e clique em "Este objeto é meu".</flux:text>
            <flux:button size="sm" icon="magnifying-glass" :href="route('items.search', ['tipo' => 'found'])" wire:navigate>Buscar objetos encontrados</flux:button>
        </flux:card>
    @else
        <div class="space-y-4">
            @foreach ($requests as $request)
                @php $item = $request->lostFoundItem; @endphp

                <flux:card wire:key="request-{{ $request->id }}" class="flex flex-col gap-4 sm:flex-row sm:items-start">
                    <x-item-thumbnail :item="$item" size="lg" />

                    <div class="min-w-0 flex-1 space-y-2">
                        <div class="flex flex-wrap items-center gap-2">
                            @can('view', $item)
                                <flux:link :href="route('items.show', $item)" wire:navigate class="font-semibold">{{ $item->title }}</flux:link>
                            @else
                                <span class="font-semibold">{{ $item->title }}</span>
                            @endcan
                            <x-status-badge :status="$request->status" />
                        </div>

                        <flux:text class="text-sm">
                            Solicitada em {{ $request->created_at->format('d/m/Y') }} às {{ $request->created_at->format('H:i') }}
                        </flux:text>

                        <p class="line-clamp-2 text-sm text-zinc-600 italic dark:text-zinc-400">“{{ $request->message }}”</p>

                        @if ($request->itemReturn)
                            <flux:callout variant="success" icon="check-circle" :heading="'Objeto entregue em '.$request->itemReturn->returned_at->format('d/m/Y').'.'" />
                        @elseif ($request->status === \App\Enums\ReturnRequestStatus::Approved)
                            <flux:callout color="blue" icon="information-circle" heading="Solicitação aprovada!">
                                <flux:callout.text>Procure a administração do UNIPAR ENCONTRA para retirar o objeto.</flux:callout.text>
                            </flux:callout>
                        @endif

                        @if ($request->admin_notes)
                            <div class="rounded-lg bg-zinc-100 p-3 text-sm dark:bg-zinc-700/50">
                                <span class="font-medium">Resposta da administração:</span> {{ $request->admin_notes }}
                            </div>
                        @endif
                    </div>

                    @can('cancel', $request)
                        <flux:button size="sm" variant="ghost" icon="x-circle" wire:click="confirmCancel({{ $request->id }})">Cancelar</flux:button>
                    @endcan
                </flux:card>
            @endforeach
        </div>

        <flux:pagination :paginator="$requests" />
    @endif

    <flux:modal name="cancel-request" class="max-w-md">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">Cancelar solicitação?</flux:heading>
                <flux:text class="mt-2">A administração não analisará mais este pedido. Você poderá fazer uma nova solicitação depois, se o objeto continuar disponível.</flux:text>
            </div>

            <div class="flex justify-end gap-2">
                <flux:modal.close>
                    <flux:button variant="ghost">Voltar</flux:button>
                </flux:modal.close>
                <flux:button variant="danger" wire:click="cancel">Cancelar solicitação</flux:button>
            </div>
        </div>
    </flux:modal>
</div>
