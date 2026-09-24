<?php

use App\Enums\ItemStatus;
use App\Enums\ItemType;
use App\Models\Category;
use App\Models\Location;
use App\Models\LostFoundItem;
use Illuminate\Support\Carbon;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Volt\Component;
use Livewire\WithPagination;

/*
| Busca de objetos. Os filtros ficam na URL (#[Url]), então a busca
| pode ser compartilhada e a busca da página inicial chega preenchida.
*/
new #[Title('Buscar Objetos')] class extends Component {
    use WithPagination;

    #[Url(as: 'q')]
    public string $search = '';

    #[Url(as: 'tipo')]
    public string $type = '';

    #[Url(as: 'categoria')]
    public string $category = '';

    #[Url(as: 'local')]
    public string $location = '';

    #[Url]
    public string $status = '';

    #[Url(as: 'de')]
    public string $dateFrom = '';

    #[Url(as: 'ate')]
    public string $dateTo = '';

    /**
     * Qualquer filtro alterado volta para a primeira página de resultados.
     */
    public function updated(): void
    {
        $this->resetPage();
    }

    public function clearFilters(): void
    {
        $this->reset('search', 'type', 'category', 'location', 'status', 'dateFrom', 'dateTo');
        $this->resetPage();
    }

    public function with(): array
    {
        // Usuários veem apenas publicações aprovadas (e não canceladas).
        $items = LostFoundItem::publiclyVisible()
            ->when($this->search, fn ($query) => $query->where(function ($query) {
                $query->where('title', 'like', "%{$this->search}%")
                    ->orWhere('description', 'like', "%{$this->search}%");
            }))
            ->when(ItemType::tryFrom($this->type), fn ($query, $type) => $query->where('type', $type))
            ->when(ItemStatus::tryFrom($this->status), fn ($query, $status) => $query->where('status', $status))
            ->when($this->category, fn ($query) => $query->where('category_id', $this->category))
            ->when($this->location, fn ($query) => $query->where('location_id', $this->location))
            ->when($this->isDate($this->dateFrom), fn ($query) => $query->whereDate('occurred_at', '>=', $this->dateFrom))
            ->when($this->isDate($this->dateTo), fn ($query) => $query->whereDate('occurred_at', '<=', $this->dateTo))
            ->with(['category', 'location', 'photos'])
            ->orderByDesc('occurred_at')
            ->orderByDesc('id')
            ->paginate(12);

        return [
            'items' => $items,
            'categories' => Category::active()->orderBy('name')->get(['id', 'name']),
            'locations' => Location::active()->orderBy('name')->get(['id', 'name']),
            'types' => ItemType::cases(),
            // Publicações canceladas não aparecem na busca.
            'statuses' => [ItemStatus::Active, ItemStatus::InReturnProcess, ItemStatus::Returned],
            'hasFilters' => collect([$this->search, $this->type, $this->category, $this->location, $this->status, $this->dateFrom, $this->dateTo])->filter()->isNotEmpty(),
        ];
    }

    /**
     * Ignora datas inválidas digitadas diretamente na URL.
     */
    private function isDate(string $value): bool
    {
        return $value !== '' && Carbon::canBeCreatedFromFormat($value, 'Y-m-d');
    }
}; ?>

<div class="flex flex-col gap-6">
    <div>
        <flux:heading size="xl" level="1">Buscar objetos</flux:heading>
        <flux:subheading>Procure entre os objetos perdidos e encontrados na UNIPAR.</flux:subheading>
    </div>

    {{-- Filtros --}}
    <flux:card class="space-y-4">
        <flux:input
            wire:model.live.debounce.400ms="search"
            icon="magnifying-glass"
            placeholder="O que você está procurando? Ex.: carteira, chave, celular..."
            aria-label="Texto da busca"
            clearable
        />

        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6">
            <flux:select wire:model.live="type" label="Tipo">
                <flux:select.option value="">Todos</flux:select.option>
                @foreach ($types as $option)
                    <flux:select.option :value="$option->value">{{ $option->label() }}</flux:select.option>
                @endforeach
            </flux:select>

            <flux:select wire:model.live="category" label="Categoria">
                <flux:select.option value="">Todas</flux:select.option>
                @foreach ($categories as $option)
                    <flux:select.option :value="$option->id">{{ $option->name }}</flux:select.option>
                @endforeach
            </flux:select>

            <flux:select wire:model.live="location" label="Local">
                <flux:select.option value="">Todos</flux:select.option>
                @foreach ($locations as $option)
                    <flux:select.option :value="$option->id">{{ $option->name }}</flux:select.option>
                @endforeach
            </flux:select>

            <flux:select wire:model.live="status" label="Status">
                <flux:select.option value="">Todos</flux:select.option>
                @foreach ($statuses as $option)
                    <flux:select.option :value="$option->value">{{ $option->label() }}</flux:select.option>
                @endforeach
            </flux:select>

            <flux:input type="date" wire:model.live="dateFrom" label="Data (de)" max="{{ today()->toDateString() }}" />
            <flux:input type="date" wire:model.live="dateTo" label="Data (até)" max="{{ today()->toDateString() }}" />
        </div>
    </flux:card>

    {{-- Resultados --}}
    <div class="flex items-center justify-between gap-4">
        <flux:text>
            {{ $items->total() }} {{ $items->total() === 1 ? 'resultado' : 'resultados' }}
        </flux:text>

        @if ($hasFilters)
            <flux:button size="sm" variant="ghost" icon="x-mark" wire:click="clearFilters">Limpar filtros</flux:button>
        @endif
    </div>

    <div wire:loading.delay.class="opacity-50" class="transition-opacity">
        @if ($items->isEmpty())
            <flux:card class="flex flex-col items-center gap-3 py-12 text-center">
                <flux:icon.magnifying-glass class="size-10 text-zinc-400" />
                <flux:heading>Nenhum objeto encontrado</flux:heading>
                <flux:text>Tente outros termos ou remova alguns filtros.</flux:text>
                <flux:button size="sm" :href="route('items.create.lost')" wire:navigate>Cadastrar objeto perdido</flux:button>
            </flux:card>
        @else
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
                @foreach ($items as $item)
                    <x-item-card :item="$item" show-status wire:key="item-{{ $item->id }}" />
                @endforeach
            </div>

            <div class="mt-6">
                <flux:pagination :paginator="$items" />
            </div>
        @endif
    </div>
</div>
