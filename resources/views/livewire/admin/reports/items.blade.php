<?php

use App\Enums\ApprovalStatus;
use App\Enums\ItemStatus;
use App\Enums\ItemType;
use App\Livewire\Concerns\WithReportFilters;
use App\Models\Category;
use App\Models\Location;
use App\Models\LostFoundItem;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Url;
use Livewire\Volt\Component;

/*
| Relatórios de Objetos Perdidos e de Objetos Encontrados (mesmos filtros e colunas).
| O tipo vem da rota. Publicações rejeitadas pela moderação não entram no relatório.
*/
new class extends Component {
    use WithReportFilters;

    #[Locked]
    public ?ItemType $type = null;

    #[Url(as: 'categoria')]
    public string $category = '';

    #[Url(as: 'local')]
    public string $location = '';

    #[Url]
    public string $status = '';

    public function clearFilters(): void
    {
        $this->reset('dateFrom', 'dateTo', 'category', 'location', 'status');
    }

    protected function reportTitle(): string
    {
        return $this->type === ItemType::Lost ? 'Relatório de Objetos Perdidos' : 'Relatório de Objetos Encontrados';
    }

    public function with(): array
    {
        $query = LostFoundItem::query()
            ->ofType($this->type)
            ->where('approval_status', '!=', ApprovalStatus::Rejected)
            ->when($this->category, fn ($query) => $query->where('category_id', $this->category))
            ->when($this->location, fn ($query) => $query->where('location_id', $this->location))
            ->when(ItemStatus::tryFrom($this->status), fn ($query, $status) => $query->where('status', $status));
        $this->applyPeriod($query, 'occurred_at');

        $byStatus = (clone $query)->toBase()->selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status');
        $items = $query->with(['category', 'location', 'user'])->orderBy('occurred_at')->orderBy('id')->limit(self::MAX_ROWS)->get();

        $categories = Category::orderBy('name')->get(['id', 'name']);
        $locations = Location::orderBy('name')->get(['id', 'name']);

        return [
            'heading' => $this->reportTitle(),
            'items' => $items,
            'total' => $byStatus->sum(),
            'byStatus' => $byStatus,
            'categories' => $categories,
            'locations' => $locations,
            'statuses' => ItemStatus::cases(),
            'dateLabel' => $this->type === ItemType::Lost ? 'Perdido em' : 'Encontrado em',
            'applied' => array_values(array_filter([
                $this->periodDescription(),
                $this->category ? 'Categoria: '.$categories->firstWhere('id', $this->category)?->name : null,
                $this->location ? 'Local: '.$locations->firstWhere('id', $this->location)?->name : null,
                ItemStatus::tryFrom($this->status) ? 'Status: '.ItemStatus::from($this->status)->label() : null,
            ])),
            'printUrl' => route('admin.reports.'.$this->type->value, array_filter([
                'de' => $this->dateFrom, 'ate' => $this->dateTo, 'categoria' => $this->category,
                'local' => $this->location, 'status' => $this->status, 'imprimir' => 1,
            ])),
        ];
    }
}; ?>

<x-admin.layout
    :heading="$heading"
    subheading="Não inclui publicações rejeitadas pela moderação. O período considera a data do ocorrido."
    :bare="$printing"
>
    <x-admin.report :printing="$printing" :print-url="$printUrl" :applied="$applied" :shown="$items->count()" :total="$total">
        <x-slot:filters>
            <flux:input type="date" wire:model.live="dateFrom" label="De" />
            <flux:input type="date" wire:model.live="dateTo" label="Até" />

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
        </x-slot:filters>

        <x-slot:summary>
            <x-admin.stat label="Total" :value="$total" />
            @foreach ($statuses as $option)
                <x-admin.stat :label="$option->label()" :value="$byStatus[$option->value] ?? 0" />
            @endforeach
        </x-slot:summary>

        <table class="report-table">
            <thead>
                <tr>
                    <th>{{ $dateLabel }}</th>
                    <th>Objeto</th>
                    <th>Categoria</th>
                    <th>Local</th>
                    <th>Cadastrado por</th>
                    <th>Status</th>
                    <th>Aprovação</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($items as $item)
                    <tr wire:key="row-{{ $item->id }}">
                        <td class="whitespace-nowrap">{{ $item->occurred_at->format('d/m/Y') }}</td>
                        <td class="font-medium">{{ $item->title }}</td>
                        <td>{{ $item->category->name }}</td>
                        <td>{{ $item->location->name }}</td>
                        <td>{{ $item->user->name }}</td>
                        <td><x-status-badge :status="$item->status" /></td>
                        <td><x-status-badge :status="$item->approval_status" /></td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </x-admin.report>
</x-admin.layout>
