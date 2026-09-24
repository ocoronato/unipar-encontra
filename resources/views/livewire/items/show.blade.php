<?php

use App\Enums\ItemStatus;
use App\Enums\ItemType;
use App\Enums\ReturnRequestStatus;
use App\Models\LostFoundItem;
use App\Models\ReturnRequest;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Locked;
use Livewire\Volt\Component;

/*
| Detalhes de um objeto. Mostra apenas dados do objeto:
| nome, e-mail e RA de quem publicou nunca são exibidos.
*/
new class extends Component {
    #[Locked]
    public LostFoundItem $item;

    /** Foto exibida em destaque na galeria. */
    public int $photoIndex = 0;

    public function mount(LostFoundItem $item): void
    {
        // Publicação não aprovada de outra pessoa: 404 (ver LostFoundItemPolicy).
        $this->authorize('view', $item);
    }

    public function rendering($view): void
    {
        $view->title($this->item->title);
    }

    public function with(): array
    {
        $user = Auth::user();
        $this->item->load(['category', 'location', 'photos']);

        return [
            'photos' => $this->item->photos,
            'isLost' => $this->item->type === ItemType::Lost,
            // Autor e administração também veem a situação da moderação.
            'showApproval' => $this->item->user_id === $user->id || $user->isAdmin(),
            'action' => $this->action($user),
        ];
    }

    /**
     * Qual mensagem/ação mostrar para o usuário atual.
     */
    private function action(User $user): ?string
    {
        return match (true) {
            $this->item->status === ItemStatus::Returned => 'returned',
            $this->item->user_id === $user->id => 'owner',
            ! $this->item->isPubliclyVisible() => null,
            $this->item->type === ItemType::Lost => 'report-found',
            $this->item->returnRequests()->open()->where('user_id', $user->id)->exists() => 'requested',
            $this->item->returnRequests()->where('user_id', $user->id)->where('status', ReturnRequestStatus::Rejected)->exists() => 'rejected',
            $user->can('create', [ReturnRequest::class, $this->item]) => 'claim',
            $this->item->status === ItemStatus::InReturnProcess => 'in-process',
            default => null,
        };
    }
}; ?>

<div class="flex flex-col gap-6">
    <div>
        <flux:button variant="ghost" size="sm" icon="arrow-left" :href="route('items.search')" wire:navigate>
            Voltar para a busca
        </flux:button>
    </div>

    <div class="grid gap-8 lg:grid-cols-2">
        {{-- Galeria --}}
        <div class="space-y-3">
            @php $current = $photos->get($photoIndex) ?? $photos->first(); @endphp

            <div class="aspect-[4/3] overflow-hidden rounded-xl border border-zinc-200 bg-zinc-100 dark:border-zinc-700 dark:bg-zinc-900">
                @if ($current)
                    <a href="{{ $current->url() }}" target="_blank" title="Abrir foto em tamanho real">
                        <img src="{{ $current->url() }}" alt="Foto de {{ $item->title }}" class="size-full object-contain">
                    </a>
                @else
                    <div class="flex size-full flex-col items-center justify-center gap-2 text-zinc-400">
                        <flux:icon.photo class="size-12" />
                        <span class="text-sm">Sem fotos</span>
                    </div>
                @endif
            </div>

            @if ($photos->count() > 1)
                <div class="grid grid-cols-5 gap-2">
                    @foreach ($photos as $index => $photo)
                        <button
                            type="button"
                            wire:click="$set('photoIndex', {{ $index }})"
                            wire:key="thumb-{{ $photo->id }}"
                            aria-label="Ver foto {{ $index + 1 }}"
                            @class([
                                'overflow-hidden rounded-lg border-2 transition',
                                'border-accent' => $photo->is($current),
                                'border-transparent opacity-70 hover:opacity-100' => ! $photo->is($current),
                            ])
                        >
                            <img src="{{ $photo->url() }}" alt="" class="aspect-square w-full object-cover">
                        </button>
                    @endforeach
                </div>
            @endif
        </div>

        {{-- Informações --}}
        <div class="flex flex-col gap-6">
            <div class="space-y-3">
                <div class="flex flex-wrap gap-2">
                    <x-status-badge :status="$item->type" />
                    <x-status-badge :status="$item->status" />
                    @if ($showApproval)
                        <x-status-badge :status="$item->approval_status" />
                    @endif
                </div>

                <flux:heading size="xl" level="1">{{ $item->title }}</flux:heading>
            </div>

            <dl class="grid grid-cols-2 gap-4 rounded-xl border border-zinc-200 p-4 dark:border-zinc-700">
                <div>
                    <dt class="text-sm text-zinc-500 dark:text-zinc-400">Categoria</dt>
                    <dd class="font-medium">{{ $item->category->name }}</dd>
                </div>
                <div>
                    <dt class="text-sm text-zinc-500 dark:text-zinc-400">Local</dt>
                    <dd class="font-medium">{{ $item->location->name }}</dd>
                </div>
                <div>
                    <dt class="text-sm text-zinc-500 dark:text-zinc-400">{{ $isLost ? 'Perdido em' : 'Encontrado em' }}</dt>
                    <dd class="font-medium">{{ $item->occurred_at->format('d/m/Y') }}</dd>
                </div>
                <div>
                    <dt class="text-sm text-zinc-500 dark:text-zinc-400">Publicado em</dt>
                    <dd class="font-medium">{{ $item->created_at->format('d/m/Y') }}</dd>
                </div>
            </dl>

            <div>
                <flux:heading>Descrição</flux:heading>
                <p class="mt-2 whitespace-pre-line text-zinc-700 dark:text-zinc-300">{{ $item->description }}</p>
            </div>

            {{-- Ação disponível para o usuário atual --}}
            @switch($action)
                @case('claim')
                    <flux:card class="space-y-3">
                        <flux:heading>Reconheceu este objeto?</flux:heading>
                        <flux:text>Solicite a devolução informando detalhes que comprovem que ele é seu. A administração analisará o pedido.</flux:text>
                        <flux:button variant="primary" icon="hand-raised" :href="route('return-requests.create', $item)" wire:navigate>
                            Este objeto é meu
                        </flux:button>
                    </flux:card>
                    @break

                @case('requested')
                    <flux:callout icon="clock" color="blue" heading="Você já solicitou a devolução deste objeto.">
                        <flux:callout.text>
                            Acompanhe o andamento em <flux:link :href="route('return-requests.mine')" wire:navigate>Minhas Solicitações</flux:link>.
                        </flux:callout.text>
                    </flux:callout>
                    @break

                @case('rejected')
                    <flux:callout icon="x-circle" color="red" heading="Sua solicitação para este objeto foi rejeitada.">
                        <flux:callout.text>
                            Veja a resposta da administração em <flux:link :href="route('return-requests.mine')" wire:navigate>Minhas Solicitações</flux:link>.
                        </flux:callout.text>
                    </flux:callout>
                    @break

                @case('in-process')
                    <flux:callout icon="clock" color="amber" heading="Este objeto está em processo de devolução." />
                    @break

                @case('returned')
                    <flux:callout icon="check-circle" variant="success" heading="Este objeto já foi devolvido ao dono." />
                    @break

                @case('owner')
                    <flux:callout icon="user" color="zinc" heading="Esta publicação é sua.">
                        <flux:callout.text>
                            @if ($item->isPubliclyVisible())
                                Ela está visível na busca para outros usuários.
                            @elseif ($item->approval_status === \App\Enums\ApprovalStatus::Rejected)
                                Ela foi rejeitada pela administração{{ $item->moderation_notes ? ': '.$item->moderation_notes : '.' }}
                            @else
                                Ela não aparece na busca enquanto não for aprovada pela administração.
                            @endif
                            Gerencie suas publicações em <flux:link :href="route('items.mine')" wire:navigate>Meus Objetos</flux:link>.
                        </flux:callout.text>
                    </flux:callout>
                    @break

                @case('report-found')
                    <flux:card class="space-y-3">
                        <flux:heading>Encontrou este objeto?</flux:heading>
                        <flux:text>Cadastre-o como encontrado. Assim o dono poderá solicitar a devolução.</flux:text>
                        <flux:button icon="hand-raised" :href="route('items.create.found')" wire:navigate>Cadastrar como encontrado</flux:button>
                    </flux:card>
                    @break
            @endswitch
        </div>
    </div>
</div>
