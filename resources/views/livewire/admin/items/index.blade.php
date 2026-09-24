<?php

use App\Enums\ApprovalStatus;
use App\Enums\ItemStatus;
use App\Enums\ItemType;
use App\Models\LostFoundItem;
use Flux\Flux;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Volt\Component;
use Livewire\WithPagination;

/*
| Moderação de objetos: a administração vê os detalhes e aprova ou rejeita.
*/
new #[Title('Objetos')] class extends Component {
    use WithPagination;

    #[Url(as: 'aprovacao')]
    public string $approval = 'pending';

    #[Url(as: 'tipo')]
    public string $type = '';

    #[Url]
    public string $status = '';

    #[Url(as: 'busca')]
    public string $search = '';

    /** Objeto aberto no modal de moderação. */
    public ?int $selectedId = null;

    /** Motivo da rejeição (visível para o autor). */
    public string $moderationNotes = '';

    public function updated(string $property): void
    {
        if (in_array($property, ['approval', 'type', 'status', 'search'])) {
            $this->resetPage();
        }
    }

    public function open(int $id): void
    {
        $this->selectedId = $id;
        $this->reset('moderationNotes');
        $this->resetValidation();
        unset($this->selected);

        Flux::modal('moderate-item')->show();
    }

    #[Computed]
    public function selected(): ?LostFoundItem
    {
        return LostFoundItem::with(['user', 'category', 'location', 'photos'])
            ->withOpenReturnRequestsFlag()
            ->find($this->selectedId);
    }

    public function approve(): void
    {
        $item = LostFoundItem::findOrFail($this->selectedId);
        $this->authorize('moderate', $item);

        $item->approve();

        $this->finish('Publicação aprovada. Ela já aparece na busca.');
    }

    public function reject(): void
    {
        $item = LostFoundItem::findOrFail($this->selectedId);
        $this->authorize('moderate', $item);

        $this->validate(
            ['moderationNotes' => ['required', 'string', 'min:5', 'max:1000']],
            attributes: ['moderationNotes' => 'motivo da rejeição'],
        );

        $item->reject($this->moderationNotes);

        $this->finish('Publicação rejeitada. O autor verá o motivo em Meus Objetos.');
    }

    private function finish(string $message): void
    {
        unset($this->selected);
        Flux::modal('moderate-item')->close();
        Flux::toast(variant: 'success', text: $message);
    }

    public function with(): array
    {
        $items = LostFoundItem::query()
            ->with(['user', 'photos'])
            ->when(ApprovalStatus::tryFrom($this->approval), fn ($query, $approval) => $query->where('approval_status', $approval))
            ->when(ItemType::tryFrom($this->type), fn ($query, $type) => $query->where('type', $type))
            ->when(ItemStatus::tryFrom($this->status), fn ($query, $status) => $query->where('status', $status))
            ->when($this->search, fn ($query) => $query->where(function ($query) {
                $query->where('title', 'like', "%{$this->search}%")
                    ->orWhereHas('user', fn ($q) => $q->where('name', 'like', "%{$this->search}%"));
            }))
            ->oldest() // os pendentes mais antigos primeiro
            ->paginate(15);

        return [
            'items' => $items,
            'approvals' => ApprovalStatus::cases(),
            'types' => ItemType::cases(),
            'statuses' => ItemStatus::cases(),
            'counts' => LostFoundItem::query()->selectRaw('approval_status, count(*) as total')->groupBy('approval_status')->pluck('total', 'approval_status'),
        ];
    }
}; ?>

<x-admin.layout heading="Objetos" subheading="Modere as publicações antes que apareçam na busca">
    <div class="mb-4 grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
        <flux:input wire:model.live.debounce.400ms="search" icon="magnifying-glass" placeholder="Título ou autor" clearable aria-label="Buscar" />

        <flux:select wire:model.live="approval" aria-label="Aprovação">
            @foreach ($approvals as $option)
                <flux:select.option :value="$option->value">{{ $option->label() }} ({{ $counts[$option->value] ?? 0 }})</flux:select.option>
            @endforeach
            <flux:select.option value="">Todas as situações</flux:select.option>
        </flux:select>

        <flux:select wire:model.live="type" aria-label="Tipo">
            <flux:select.option value="">Todos os tipos</flux:select.option>
            @foreach ($types as $option)
                <flux:select.option :value="$option->value">{{ $option->label() }}</flux:select.option>
            @endforeach
        </flux:select>

        <flux:select wire:model.live="status" aria-label="Status">
            <flux:select.option value="">Todos os status</flux:select.option>
            @foreach ($statuses as $option)
                <flux:select.option :value="$option->value">{{ $option->label() }}</flux:select.option>
            @endforeach
        </flux:select>
    </div>

    <flux:table :paginate="$items">
        <flux:table.columns>
            <flux:table.column>Objeto</flux:table.column>
            <flux:table.column>Tipo</flux:table.column>
            <flux:table.column>Autor</flux:table.column>
            <flux:table.column>Cadastrado em</flux:table.column>
            <flux:table.column>Status</flux:table.column>
            <flux:table.column>Aprovação</flux:table.column>
            <flux:table.column></flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @forelse ($items as $item)
                <flux:table.row :key="$item->id">
                    <flux:table.cell>
                        <div class="flex items-center gap-3">
                            <x-item-thumbnail :item="$item" />
                            <span class="max-w-48 truncate font-medium text-zinc-800 dark:text-white">{{ $item->title }}</span>
                        </div>
                    </flux:table.cell>
                    <flux:table.cell><x-status-badge :status="$item->type" /></flux:table.cell>
                    <flux:table.cell>{{ $item->user->name }}</flux:table.cell>
                    <flux:table.cell>{{ $item->created_at->format('d/m/Y H:i') }}</flux:table.cell>
                    <flux:table.cell><x-status-badge :status="$item->status" /></flux:table.cell>
                    <flux:table.cell><x-status-badge :status="$item->approval_status" /></flux:table.cell>
                    <flux:table.cell align="end">
                        <flux:button size="sm" variant="ghost" icon="eye" wire:click="open({{ $item->id }})">Analisar</flux:button>
                    </flux:table.cell>
                </flux:table.row>
            @empty
                <flux:table.row>
                    <flux:table.cell colspan="7" class="text-center">Nenhum objeto encontrado.</flux:table.cell>
                </flux:table.row>
            @endforelse
        </flux:table.rows>
    </flux:table>

    {{-- Modal de moderação: detalhes antes da decisão --}}
    <flux:modal name="moderate-item" class="w-full max-w-2xl">
        @if ($reviewing = $this->selected)
            <div class="space-y-6">
                <div class="space-y-2">
                    <div class="flex flex-wrap gap-2">
                        <x-status-badge :status="$reviewing->type" />
                        <x-status-badge :status="$reviewing->status" />
                        <x-status-badge :status="$reviewing->approval_status" />
                    </div>
                    <flux:heading size="lg">{{ $reviewing->title }}</flux:heading>
                </div>

                @if ($reviewing->photos->isNotEmpty())
                    <div class="grid grid-cols-3 gap-2 sm:grid-cols-5">
                        @foreach ($reviewing->photos as $photo)
                            <a href="{{ $photo->url() }}" target="_blank" title="Abrir foto em tamanho real" wire:key="mod-photo-{{ $photo->id }}">
                                <img src="{{ $photo->url() }}" alt="Foto {{ $loop->iteration }}" class="aspect-square w-full rounded-lg object-cover">
                            </a>
                        @endforeach
                    </div>
                @else
                    <flux:text class="text-sm">Publicação sem fotos.</flux:text>
                @endif

                <dl class="grid grid-cols-2 gap-3 rounded-lg border border-zinc-200 p-4 text-sm sm:grid-cols-4 dark:border-zinc-700">
                    <div><dt class="text-zinc-500">Categoria</dt><dd class="font-medium">{{ $reviewing->category->name }}</dd></div>
                    <div><dt class="text-zinc-500">Local</dt><dd class="font-medium">{{ $reviewing->location->name }}</dd></div>
                    <div><dt class="text-zinc-500">Data</dt><dd class="font-medium">{{ $reviewing->occurred_at->format('d/m/Y') }}</dd></div>
                    <div><dt class="text-zinc-500">Cadastrado em</dt><dd class="font-medium">{{ $reviewing->created_at->format('d/m/Y') }}</dd></div>
                </dl>

                <div>
                    <flux:heading size="sm">Descrição</flux:heading>
                    <p class="mt-1 text-sm whitespace-pre-line text-zinc-700 dark:text-zinc-300">{{ $reviewing->description }}</p>
                </div>

                <div class="rounded-lg bg-zinc-50 p-3 text-sm dark:bg-zinc-800">
                    <span class="font-medium">Autor:</span> {{ $reviewing->user->name }} · {{ $reviewing->user->email }}
                    · RA {{ $reviewing->user->registration_number ?? '—' }}
                </div>

                @if ($reviewing->moderation_notes)
                    <flux:callout icon="x-circle" color="red" :heading="'Motivo da rejeição anterior: '.$reviewing->moderation_notes" />
                @endif

                @can('moderate', $reviewing)
                    <div class="space-y-4 border-t border-zinc-200 pt-4 dark:border-zinc-700">
                        @unless ($reviewing->approval_status === \App\Enums\ApprovalStatus::Rejected)
                            <flux:textarea
                                wire:model="moderationNotes"
                                label="Motivo da rejeição (obrigatório para rejeitar, visível para o autor)"
                                rows="2"
                                maxlength="1000"
                                placeholder="Ex.: A foto mostra o número do documento. Envie uma foto sem dados pessoais."
                            />
                        @endunless

                        <div class="flex flex-wrap justify-end gap-2">
                            <flux:button :href="route('items.show', $reviewing)" target="_blank" variant="ghost" icon="arrow-top-right-on-square">Ver página</flux:button>

                            @unless ($reviewing->approval_status === \App\Enums\ApprovalStatus::Rejected)
                                <flux:button variant="danger" icon="x-mark" wire:click="reject">Rejeitar</flux:button>
                            @endunless

                            @unless ($reviewing->approval_status === \App\Enums\ApprovalStatus::Approved)
                                <flux:button variant="primary" icon="check" wire:click="approve">Aprovar</flux:button>
                            @endunless
                        </div>
                    </div>
                @else
                    <flux:callout icon="information-circle" color="zinc" heading="Este objeto não pode ser moderado agora (há devolução em andamento, ou ele foi devolvido ou cancelado)." />
                @endcan
            </div>
        @endif
    </flux:modal>
</x-admin.layout>
