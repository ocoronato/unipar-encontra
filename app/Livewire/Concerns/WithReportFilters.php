<?php

namespace App\Livewire\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Livewire\Attributes\Url;

/**
 * Recursos comuns aos relatórios: filtro de período e versão para impressão.
 * Os filtros ficam na URL, então a versão para impressão usa os mesmos filtros.
 */
trait WithReportFilters
{
    /** Quantidade máxima de linhas exibidas (e impressas) por relatório. */
    public const MAX_ROWS = 1000;

    #[Url(as: 'de')]
    public string $dateFrom = '';

    #[Url(as: 'ate')]
    public string $dateTo = '';

    /** true = versão para impressão (layout sem menus). */
    #[Url(as: 'imprimir')]
    public bool $printing = false;

    abstract protected function reportTitle(): string;

    public function rendering($view): void
    {
        $view->title($this->reportTitle());

        if ($this->printing) {
            $view->layout('components.layouts.print');
        }
    }

    /**
     * Filtra a coluna de data pelo período escolhido (datas inválidas são ignoradas).
     */
    protected function applyPeriod(Builder $query, string $column): Builder
    {
        return $query
            ->when($this->isDate($this->dateFrom), fn ($query) => $query->whereDate($column, '>=', $this->dateFrom))
            ->when($this->isDate($this->dateTo), fn ($query) => $query->whereDate($column, '<=', $this->dateTo));
    }

    /**
     * Descrição do período para o cabeçalho do relatório (null = sem filtro).
     */
    protected function periodDescription(): ?string
    {
        $from = $this->isDate($this->dateFrom) ? Carbon::parse($this->dateFrom)->format('d/m/Y') : null;
        $to = $this->isDate($this->dateTo) ? Carbon::parse($this->dateTo)->format('d/m/Y') : null;

        return match (true) {
            $from && $to => "Período: {$from} a {$to}",
            $from !== null => "Período: a partir de {$from}",
            $to !== null => "Período: até {$to}",
            default => null,
        };
    }

    private function isDate(string $value): bool
    {
        return $value !== '' && Carbon::canBeCreatedFromFormat($value, 'Y-m-d');
    }
}
