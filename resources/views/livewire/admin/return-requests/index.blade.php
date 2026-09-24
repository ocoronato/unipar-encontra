<?php

use App\Enums\ReturnRequestStatus;
use App\Models\ReturnRequest;
use Flux\Flux;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Volt\Component;
use Livewire\WithPagination;

new #[Title('Solicitações')] class extends Component {
    use WithPagination;

    #[Url]
    public string $status = 'pending';

    #[Url(as: 'busca')]
    public string $search = '';

    /** Solicitação aberta no modal de análise. */
    public ?int $selectedId = null;

    /** Resposta ao solicitante (aprovação/rejeição). */
    public string $adminNotes = '';

    /** Dados da entrega (confirmação da devolução). */
    public string $returnedAt = '';
    public string $returnNotes = '';

    public function updated(string $property): void
    {
        if (in_array($property, ['status', 'search'])) {
            $this->resetPage();
        }
    }

    public function open(int $id): void
    {
        $this->selectedId = $id;
        $this->reset('adminNotes', 'returnNotes');
        $this->resetValidation();
        $this->returnedAt = now()->format('Y-m-d\TH:i');
        unset($this->selected); // descarta o valor em cache da propriedade computada

        Flux::modal('review-request')->show();
    }

    #[Computed]
    public function selected(): ?ReturnRequest
    {
        return ReturnRequest::with([
            'user', 'itemReturn.administrator',
            'lostFoundItem' => fn ($query) => $query->with(['photos', 'category', 'location', 'user'])
                ->withCount(['returnRequests as open_requests_count' => fn ($q) => $q->open()]),
        ])->find($this->selectedId);
    }

    public function approve(): void
    {
        $request = ReturnRequest::findOrFail($this->selectedId);
        $this->authorize('approve', $request);
        $this->validate(['adminNotes' => ['nullable', 'string', 'max:1000']]);

        $request->approve($this->adminNotes ?: null);

        $this->finish('Solicitação aprovada. O objeto está em processo de devolução.');
    }

    public function reject(): void
    {
        $request = ReturnRequest::findOrFail($this->selectedId);
        $this->authorize('reject', $request);
        $this->validate(['adminNotes' => ['nullable', 'string', 'max:1000']]);

        $request->reject($this->adminNotes ?: null);

        $this->finish('Solicitação rejeitada.');
    }

    public function confirmReturn(): void
    {
        $request = ReturnRequest::findOrFail($this->selectedId);
        $this->authorize('confirmReturn', $request);
        $this->validate([
            'returnedAt' => ['required', 'date', 'before_or_equal:now'],
            'returnNotes' => ['nullable', 'string', 'max:1000'],
        ]);

        $request->confirmReturn(Auth::user(), Carbon::parse($this->returnedAt), $this->returnNotes ?: null);

        $this->finish('Devolução registrada. O objeto foi marcado como devolvido.');
    }

    protected function validationAttributes(): array
    {
        return [
            'adminNotes' => 'resposta',
            'returnedAt' => 'data da entrega',
            'returnNotes' => 'observações',
        ];
    }

    protected function messages(): array
    {
        return ['returnedAt.before_or_equal' => 'A data da entrega não pode ser futura.'];
    }

    private function finish(string $message): void
    {
        unset($this->selected);
        Flux::modal('review-request')->close();
        Flux::toast(variant: 'success', text: $message);
    }

    public function with(): array
    {
        $requests = ReturnRequest::query()
            ->with(['user', 'lostFoundItem.photos', 'itemReturn'])
            ->when(ReturnRequestStatus::tryFrom($this->status), fn ($query, $status) => $query->where('status', $status))
            ->when($this->search, fn ($query) => $query->where(function ($query) {
                $query->whereHas('user', fn ($q) => $q->where('name', 'like', "%{$this->search}%")->orWhere('email', 'like', "%{$this->search}%"))
                    ->orWhereHas('lostFoundItem', fn ($q) => $q->where('title', 'like', "%{$this->search}%"));
            }))
            ->latest()
            ->paginate(15);

        return [
            'requests' => $requests,
            'statuses' => ReturnRequestStatus::cases(),
            'counts' => ReturnRequest::query()->selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status'),
        ];
    }
}; ?>

<x-admin.layout heading="Solicitações" subheading="Pedidos de devolução feitos pelos usuários">
    <div class="mb-4 grid gap-3 sm:grid-cols-3">
        <div class="sm:col-span-2">
            <flux:input wire:model.live.debounce.400ms="search" icon="magnifying-glass" placeholder="Buscar por solicitante, e-mail ou objeto" clearable />
        </div>
        <flux:select wire:model.live="status" aria-label="Status">
            @foreach ($statuses as $option)
                <flux:select.option :value="$option->value">{{ $option->label() }} ({{ $counts[$option->value] ?? 0 }})</flux:select.option>
            @endforeach
            <flux:select.option value="">Todas</flux:select.option>
        </flux:select>
    </div>

    <flux:table :paginate="$requests">
        <flux:table.columns>
            <flux:table.column>Usuário</flux:table.column>
            <flux:table.column>Objeto</flux:table.column>
            <flux:table.column>Data</flux:table.column>
            <flux:table.column>Mensagem</flux:table.column>
            <flux:table.column>Status</flux:table.column>
            <flux:table.column></flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @forelse ($requests as $request)
                <flux:table.row :key="$request->id">
                    <flux:table.cell>
                        <p class="font-medium text-zinc-800 dark:text-white">{{ $request->user->name }}</p>
                        <p class="text-xs text-zinc-500">{{ $request->user->email }}</p>
                    </flux:table.cell>
                    <flux:table.cell>
                        <div class="flex items-center gap-3">
                            <x-item-thumbnail :item="$request->lostFoundItem" />
                            <span class="max-w-40 truncate">{{ $request->lostFoundItem->title }}</span>
                        </div>
                    </flux:table.cell>
                    <flux:table.cell>{{ $request->created_at->format('d/m/Y H:i') }}</flux:table.cell>
                    <flux:table.cell>
                        <p class="line-clamp-2 max-w-xs text-sm whitespace-normal">{{ $request->message }}</p>
                    </flux:table.cell>
                    <flux:table.cell>
                        <div class="flex flex-wrap gap-1">
                            <x-status-badge :status="$request->status" />
                            @if ($request->itemReturn)
                                <flux:badge size="sm" color="green" icon="check">Entregue</flux:badge>
                            @endif
                        </div>
                    </flux:table.cell>
                    <flux:table.cell align="end">
                        <flux:button size="sm" variant="ghost" icon="eye" wire:click="open({{ $request->id }})">Analisar</flux:button>
                    </flux:table.cell>
                </flux:table.row>
            @empty
                <flux:table.row>
                    <flux:table.cell colspan="6" class="text-center">Nenhuma solicitação encontrada.</flux:table.cell>
                </flux:table.row>
            @endforelse
        </flux:table.rows>
    </flux:table>

    {{-- Modal de análise --}}
    <flux:modal name="review-request" class="w-full max-w-2xl">
        @if ($reviewing = $this->selected)
            @php $item = $reviewing->lostFoundItem; @endphp

            <div class="space-y-6">
                <div class="flex flex-wrap items-center gap-2">
                    <flux:heading size="lg">Solicitação #{{ $reviewing->id }}</flux:heading>
                    <x-status-badge :status="$reviewing->status" />
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <div class="space-y-1 rounded-lg border border-zinc-200 p-4 text-sm dark:border-zinc-700">
                        <flux:heading size="sm" class="mb-2">Solicitante</flux:heading>
                        <p class="font-medium">{{ $reviewing->user->name }}</p>
                        <p>{{ $reviewing->user->email }}</p>
                        <p>RA: {{ $reviewing->user->registration_number ?? '—' }} · {{ $reviewing->user->course ?? 'Curso não informado' }}</p>
                        <p class="text-zinc-500">Enviada em {{ $reviewing->created_at->format('d/m/Y') }} às {{ $reviewing->created_at->format('H:i') }}</p>
                    </div>

                    <div class="space-y-1 rounded-lg border border-zinc-200 p-4 text-sm dark:border-zinc-700">
                        <flux:heading size="sm" class="mb-2">Objeto</flux:heading>
                        <div class="flex gap-3">
                            <x-item-thumbnail :item="$item" size="lg" />
                            <div class="min-w-0 space-y-1">
                                <a href="{{ route('items.show', $item) }}" target="_blank" class="block truncate font-medium underline">{{ $item->title }}</a>
                                <p>{{ $item->category->name }} · {{ $item->location->name }}</p>
                                <x-status-badge :status="$item->status" />
                            </div>
                        </div>
                        <p class="pt-1 text-zinc-500">Cadastrado por {{ $item->user->name }} ({{ $item->user->email }})</p>
                    </div>
                </div>

                <div>
                    <flux:heading size="sm">Mensagem do solicitante</flux:heading>
                    <p class="mt-1 rounded-lg bg-zinc-50 p-3 text-sm whitespace-pre-line dark:bg-zinc-800">{{ $reviewing->message }}</p>
                </div>

                <div>
                    <flux:heading size="sm">Descrição do objeto (informada por quem encontrou)</flux:heading>
                    <p class="mt-1 text-sm whitespace-pre-line text-zinc-600 dark:text-zinc-300">{{ $item->description }}</p>
                </div>

                @if ($item->open_requests_count > 1)
                    <flux:callout icon="exclamation-triangle" color="amber" :heading="'Este objeto tem '.$item->open_requests_count.' solicitações em aberto. Compare-as antes de decidir.'" />
                @endif

                {{-- Situação final --}}
                @if ($reviewing->itemReturn)
                    <flux:callout variant="success" icon="check-circle"
                        :heading="'Entregue em '.$reviewing->itemReturn->returned_at->format('d/m/Y H:i').' — registrado por '.$reviewing->itemReturn->administrator->name">
                        @if ($reviewing->itemReturn->notes)
                            <flux:callout.text>{{ $reviewing->itemReturn->notes }}</flux:callout.text>
                        @endif
                    </flux:callout>
                @elseif ($reviewing->admin_notes)
                    <div class="rounded-lg bg-zinc-100 p-3 text-sm dark:bg-zinc-700/50">
                        <span class="font-medium">Resposta enviada:</span> {{ $reviewing->admin_notes }}
                    </div>
                @endif

                @if ($reviewing->user_id === auth()->id() && $reviewing->status === \App\Enums\ReturnRequestStatus::Pending)
                    <flux:callout icon="information-circle" color="zinc" heading="Esta solicitação é sua: outro administrador deve analisá-la." />
                @endif

                {{-- Aprovar / rejeitar --}}
                @if (auth()->user()->can('approve', $reviewing) || auth()->user()->can('reject', $reviewing))
                    <div class="space-y-4 border-t border-zinc-200 pt-4 dark:border-zinc-700">
                        <flux:textarea
                            wire:model="adminNotes"
                            :label="$reviewing->status === \App\Enums\ReturnRequestStatus::Approved ? 'Motivo do cancelamento da aprovação (visível ao solicitante)' : 'Resposta ao solicitante (opcional, visível para ele)'"
                            rows="2"
                            maxlength="1000"
                        />

                        <div class="flex flex-wrap justify-end gap-2">
                            @can('reject', $reviewing)
                                <flux:button variant="danger" icon="x-mark" wire:click="reject" wire:confirm="Confirmar a rejeição?">
                                    {{ $reviewing->status === \App\Enums\ReturnRequestStatus::Approved ? 'Cancelar aprovação' : 'Rejeitar' }}
                                </flux:button>
                            @endcan
                            @can('approve', $reviewing)
                                <flux:button variant="primary" icon="check" wire:click="approve">Aprovar</flux:button>
                            @endcan
                        </div>
                    </div>
                @endif

                {{-- Confirmar a entrega --}}
                @can('confirmReturn', $reviewing)
                    <div class="space-y-4 rounded-lg border border-green-200 bg-green-50/50 p-4 dark:border-green-400/30 dark:bg-green-400/5">
                        <div>
                            <flux:heading>Confirmar devolução</flux:heading>
                            <flux:text class="text-sm">Registre a entrega depois de conferir a identificação do solicitante.</flux:text>
                        </div>
                        <flux:input type="datetime-local" wire:model="returnedAt" label="Data e hora da entrega" max="{{ now()->format('Y-m-d\TH:i') }}" />
                        <flux:textarea wire:model="returnNotes" label="Observações (opcional)" rows="2" maxlength="1000" />
                        <div class="flex justify-end">
                            <flux:button variant="primary" icon="check-badge" wire:click="confirmReturn">Confirmar devolução</flux:button>
                        </div>
                    </div>
                @endcan
            </div>
        @endif
    </flux:modal>
</x-admin.layout>
