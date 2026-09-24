<?php

use App\Enums\ReturnRequestStatus;
use App\Livewire\Concerns\WithReportFilters;
use App\Models\ReturnRequest;
use Livewire\Attributes\Url;
use Livewire\Volt\Component;

/*
| Relatório de Solicitações de devolução. O período considera a data da solicitação.
*/
new class extends Component {
    use WithReportFilters;

    #[Url]
    public string $status = '';

    public function clearFilters(): void
    {
        $this->reset('dateFrom', 'dateTo', 'status');
    }

    protected function reportTitle(): string
    {
        return 'Relatório de Solicitações';
    }

    public function with(): array
    {
        $query = ReturnRequest::query()
            ->when(ReturnRequestStatus::tryFrom($this->status), fn ($query, $status) => $query->where('status', $status));
        $this->applyPeriod($query, 'created_at');

        $byStatus = (clone $query)->toBase()->selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status');
        $requests = $query->with(['user', 'lostFoundItem', 'itemReturn'])->orderBy('created_at')->limit(self::MAX_ROWS)->get();

        return [
            'heading' => $this->reportTitle(),
            'requests' => $requests,
            'total' => $byStatus->sum(),
            'byStatus' => $byStatus,
            'statuses' => ReturnRequestStatus::cases(),
            'applied' => array_values(array_filter([
                $this->periodDescription(),
                ReturnRequestStatus::tryFrom($this->status) ? 'Status: '.ReturnRequestStatus::from($this->status)->label() : null,
            ])),
            'printUrl' => route('admin.reports.requests', array_filter([
                'de' => $this->dateFrom, 'ate' => $this->dateTo, 'status' => $this->status, 'imprimir' => 1,
            ])),
        ];
    }
}; ?>

<x-admin.layout :heading="$heading" subheading="Pedidos de devolução. O período considera a data da solicitação." :bare="$printing">
    <x-admin.report :printing="$printing" :print-url="$printUrl" :applied="$applied" :shown="$requests->count()" :total="$total">
        <x-slot:filters>
            <flux:input type="date" wire:model.live="dateFrom" label="De" />
            <flux:input type="date" wire:model.live="dateTo" label="Até" />

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
                <x-admin.stat :label="$option->label().'s'" :value="$byStatus[$option->value] ?? 0" />
            @endforeach
        </x-slot:summary>

        <table class="report-table">
            <thead>
                <tr>
                    <th>Data</th>
                    <th>Solicitante</th>
                    <th>Objeto</th>
                    <th>Status</th>
                    <th>Entregue?</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($requests as $request)
                    <tr wire:key="row-{{ $request->id }}">
                        <td class="whitespace-nowrap">{{ $request->created_at->format('d/m/Y H:i') }}</td>
                        <td>{{ $request->user->name }}</td>
                        <td class="font-medium">{{ $request->lostFoundItem->title }}</td>
                        <td><x-status-badge :status="$request->status" /></td>
                        <td>{{ $request->itemReturn ? 'Sim, em '.$request->itemReturn->returned_at->format('d/m/Y') : '—' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </x-admin.report>
</x-admin.layout>
