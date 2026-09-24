<?php

use App\Livewire\Concerns\WithReportFilters;
use App\Models\Category;
use App\Models\ItemReturn;
use App\Models\Location;
use Livewire\Attributes\Url;
use Livewire\Volt\Component;

/*
| Relatório de Objetos Devolvidos: objeto, categoria, local, data e administrador.
| O período considera a data da entrega.
*/
new class extends Component {
    use WithReportFilters;

    #[Url(as: 'categoria')]
    public string $category = '';

    #[Url(as: 'local')]
    public string $location = '';

    public function clearFilters(): void
    {
        $this->reset('dateFrom', 'dateTo', 'category', 'location');
    }

    protected function reportTitle(): string
    {
        return 'Relatório de Objetos Devolvidos';
    }

    public function with(): array
    {
        $query = ItemReturn::query()
            ->when($this->category, fn ($query) => $query->whereHas('lostFoundItem', fn ($q) => $q->where('category_id', $this->category)))
            ->when($this->location, fn ($query) => $query->whereHas('lostFoundItem', fn ($q) => $q->where('location_id', $this->location)));
        $this->applyPeriod($query, 'returned_at');

        $total = (clone $query)->count();
        $returns = $query
            ->with(['lostFoundItem.category', 'lostFoundItem.location', 'returnRequest.user', 'administrator'])
            ->orderBy('returned_at')
            ->limit(self::MAX_ROWS)
            ->get();

        // Tempo médio entre a data em que o objeto foi encontrado e a entrega ao dono.
        $averageDays = $returns->avg(fn ($return) => $return->lostFoundItem->occurred_at->diffInDays($return->returned_at));

        $categories = Category::orderBy('name')->get(['id', 'name']);
        $locations = Location::orderBy('name')->get(['id', 'name']);

        return [
            'heading' => $this->reportTitle(),
            'returns' => $returns,
            'total' => $total,
            'averageDays' => $averageDays === null ? '—' : number_format($averageDays, 1, ',', '.').' dias',
            'categories' => $categories,
            'locations' => $locations,
            'applied' => array_values(array_filter([
                $this->periodDescription(),
                $this->category ? 'Categoria: '.$categories->firstWhere('id', $this->category)?->name : null,
                $this->location ? 'Local: '.$locations->firstWhere('id', $this->location)?->name : null,
            ])),
            'printUrl' => route('admin.reports.returned', array_filter([
                'de' => $this->dateFrom, 'ate' => $this->dateTo, 'categoria' => $this->category,
                'local' => $this->location, 'imprimir' => 1,
            ])),
        ];
    }
}; ?>

<x-admin.layout :heading="$heading" subheading="Entregas confirmadas pela administração. O período considera a data da entrega." :bare="$printing">
    <x-admin.report :printing="$printing" :print-url="$printUrl" :applied="$applied" :shown="$returns->count()" :total="$total">
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
        </x-slot:filters>

        <x-slot:summary>
            <x-admin.stat label="Objetos devolvidos" :value="$total" />
            <x-admin.stat label="Tempo médio até a entrega" :value="$averageDays" />
        </x-slot:summary>

        <table class="report-table">
            <thead>
                <tr>
                    <th>Data da entrega</th>
                    <th>Objeto</th>
                    <th>Categoria</th>
                    <th>Local</th>
                    <th>Entregue a</th>
                    <th>Administrador</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($returns as $return)
                    <tr wire:key="row-{{ $return->id }}">
                        <td class="whitespace-nowrap">{{ $return->returned_at->format('d/m/Y H:i') }}</td>
                        <td class="font-medium">{{ $return->lostFoundItem->title }}</td>
                        <td>{{ $return->lostFoundItem->category->name }}</td>
                        <td>{{ $return->lostFoundItem->location->name }}</td>
                        <td>{{ $return->returnRequest->user->name }}</td>
                        <td>{{ $return->administrator->name }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </x-admin.report>
</x-admin.layout>
