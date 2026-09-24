<?php

use App\Models\Category;
use App\Models\Location;
use Flux\Flux;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Url;
use Livewire\Volt\Component;
use Livewire\WithPagination;

/*
| Cadastro de Categorias e de Locais.
| As duas tabelas têm os mesmos campos (nome, descrição, ativo), por isso usam
| o mesmo componente. A rota define qual delas é editada ($resource).
*/
new class extends Component {
    use WithPagination;

    private const RESOURCES = [
        'categories' => [
            'model' => Category::class,
            'title' => 'Categorias',
            'subtitle' => 'Tipos de objeto usados no cadastro e na busca',
            'new' => 'Nova categoria',
            'saved' => 'Categoria salva com sucesso.',
        ],
        'locations' => [
            'model' => Location::class,
            'title' => 'Locais',
            'subtitle' => 'Lugares da universidade onde objetos são perdidos ou encontrados',
            'new' => 'Novo local',
            'saved' => 'Local salvo com sucesso.',
        ],
    ];

    /** 'categories' ou 'locations' (definido na rota). */
    #[Locked]
    public string $resource = '';

    #[Url(as: 'busca')]
    public string $search = '';

    /** Registro em edição no modal (null = novo). */
    #[Locked]
    public ?int $editingId = null;

    public string $name = '';
    public string $description = '';
    public bool $active = true;

    /**
     * Executado em toda requisição (inclusive nas ações): só administradores.
     */
    public function boot(): void
    {
        $this->authorize('access-admin');
    }

    public function mount(): void
    {
        abort_unless(isset(self::RESOURCES[$this->resource]), 404);
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function create(): void
    {
        $this->reset('editingId', 'name', 'description');
        $this->active = true;
        $this->resetValidation();

        Flux::modal('catalog-form')->show();
    }

    public function edit(int $id): void
    {
        $record = $this->query()->findOrFail($id);

        $this->editingId = $record->id;
        $this->name = $record->name;
        $this->description = $record->description ?? '';
        $this->active = $record->active;
        $this->resetValidation();

        Flux::modal('catalog-form')->show();
    }

    public function save(): void
    {
        $table = $this->query()->getModel()->getTable();

        $data = $this->validate([
            'name' => ['required', 'string', 'max:100', Rule::unique($table, 'name')->ignore($this->editingId)],
            'description' => ['nullable', 'string', 'max:500'],
            'active' => ['boolean'],
        ]);
        $data['description'] = $data['description'] ?: null;

        $this->editingId
            ? $this->query()->findOrFail($this->editingId)->update($data)
            : $this->query()->create($data);

        Flux::modal('catalog-form')->close();
        Flux::toast(variant: 'success', text: $this->config('saved'));
    }

    public function toggleActive(int $id): void
    {
        $record = $this->query()->findOrFail($id);
        $record->update(['active' => ! $record->active]);

        Flux::toast(text: "\"{$record->name}\" agora está ".($record->active ? 'ativo.' : 'inativo e não aparece mais nos formulários.'));
    }

    public function delete(int $id): void
    {
        $record = $this->query()->findOrFail($id);

        // Registros usados por objetos fazem parte do histórico: apenas desative.
        if ($record->lostFoundItems()->exists()) {
            Flux::toast(variant: 'danger', text: 'Não é possível excluir: existem objetos usando este registro. Desative-o.');

            return;
        }

        $record->delete();
        Flux::toast(variant: 'success', text: "Registro \"{$record->name}\" excluído.");
    }

    public function rendering($view): void
    {
        $view->title($this->config('title'));
    }

    public function with(): array
    {
        return [
            'records' => $this->query()
                ->withCount('lostFoundItems')
                ->when($this->search, fn ($query) => $query->where('name', 'like', "%{$this->search}%"))
                ->orderBy('name')
                ->paginate(15),
            'title' => $this->config('title'),
            'subtitle' => $this->config('subtitle'),
            'newLabel' => $this->config('new'),
        ];
    }

    private function config(string $key): string
    {
        return self::RESOURCES[$this->resource][$key];
    }

    /**
     * Consulta do model do cadastro atual (Category ou Location).
     */
    private function query()
    {
        return $this->config('model')::query();
    }
}; ?>

<x-admin.layout :heading="$title" :subheading="$subtitle">
    <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
        <div class="w-full max-w-sm">
            <flux:input wire:model.live.debounce.400ms="search" icon="magnifying-glass" placeholder="Buscar pelo nome" clearable aria-label="Buscar" />
        </div>
        <flux:button variant="primary" icon="plus" wire:click="create">{{ $newLabel }}</flux:button>
    </div>

    <flux:table :paginate="$records">
        <flux:table.columns>
            <flux:table.column>Nome</flux:table.column>
            <flux:table.column>Descrição</flux:table.column>
            <flux:table.column>Objetos</flux:table.column>
            <flux:table.column>Situação</flux:table.column>
            <flux:table.column align="end">Ações</flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @forelse ($records as $record)
                <flux:table.row :key="$record->id">
                    <flux:table.cell variant="strong">{{ $record->name }}</flux:table.cell>
                    <flux:table.cell>
                        <p class="max-w-xs truncate">{{ $record->description ?? '—' }}</p>
                    </flux:table.cell>
                    <flux:table.cell>{{ $record->lost_found_items_count }}</flux:table.cell>
                    <flux:table.cell>
                        <flux:badge size="sm" :color="$record->active ? 'green' : 'zinc'">{{ $record->active ? 'Ativo' : 'Inativo' }}</flux:badge>
                    </flux:table.cell>
                    <flux:table.cell align="end">
                        <div class="flex justify-end gap-1">
                            <flux:button size="sm" variant="ghost" icon="pencil-square" wire:click="edit({{ $record->id }})">Editar</flux:button>
                            <flux:button size="sm" variant="ghost" :icon="$record->active ? 'eye-slash' : 'eye'" wire:click="toggleActive({{ $record->id }})">
                                {{ $record->active ? 'Desativar' : 'Ativar' }}
                            </flux:button>
                            @if ($record->lost_found_items_count === 0)
                                <flux:button size="sm" variant="ghost" icon="trash" wire:click="delete({{ $record->id }})" wire:confirm="Excluir &quot;{{ $record->name }}&quot;?">Excluir</flux:button>
                            @endif
                        </div>
                    </flux:table.cell>
                </flux:table.row>
            @empty
                <flux:table.row>
                    <flux:table.cell colspan="5" class="text-center">Nenhum registro encontrado.</flux:table.cell>
                </flux:table.row>
            @endforelse
        </flux:table.rows>
    </flux:table>

    <flux:text class="mt-3 text-xs">Registros usados por objetos não podem ser excluídos (fazem parte do histórico). Desative-os para que não apareçam mais nos formulários.</flux:text>

    <flux:modal name="catalog-form" class="w-full max-w-md">
        <form wire:submit="save" class="space-y-6">
            <flux:heading size="lg">{{ $editingId ? 'Editar registro' : $newLabel }}</flux:heading>

            <flux:input wire:model="name" label="Nome" maxlength="100" required />
            <flux:textarea wire:model="description" label="Descrição (opcional)" rows="2" maxlength="500" />
            <flux:checkbox wire:model="active" label="Ativo (aparece nos formulários e filtros)" />

            <div class="flex justify-end gap-2">
                <flux:modal.close>
                    <flux:button variant="ghost">Cancelar</flux:button>
                </flux:modal.close>
                <flux:button type="submit" variant="primary">Salvar</flux:button>
            </div>
        </form>
    </flux:modal>
</x-admin.layout>
