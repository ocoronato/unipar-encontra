<?php

use App\Enums\UserRole;
use App\Models\User;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Volt\Component;
use Livewire\WithPagination;

new #[Title('Usuários')] class extends Component {
    use WithPagination;

    #[Url(as: 'busca')]
    public string $search = '';

    #[Url(as: 'perfil')]
    public string $role = '';

    public function updated(string $property): void
    {
        if (in_array($property, ['search', 'role'])) {
            $this->resetPage();
        }
    }

    /**
     * Promove a administrador ou rebaixa a usuário comum.
     */
    public function toggleRole(User $user): void
    {
        // A rota já é protegida, mas a ação é autorizada novamente aqui.
        $this->authorize('changeRole', $user);

        $user->role = $user->isAdmin() ? UserRole::User : UserRole::Admin;
        $user->save();

        session()->flash('status', "Perfil de {$user->name} alterado para {$user->role->label()}.");
    }

    public function with(): array
    {
        $users = User::query()
            ->when($this->search, fn ($query) => $query->where(function ($query) {
                $query->where('name', 'like', "%{$this->search}%")
                    ->orWhere('email', 'like', "%{$this->search}%")
                    ->orWhere('registration_number', 'like', "%{$this->search}%");
            }))
            ->when(UserRole::tryFrom($this->role), fn ($query, $role) => $query->where('role', $role))
            ->withCount('lostFoundItems')
            ->orderBy('name')
            ->paginate(15);

        return ['users' => $users, 'roles' => UserRole::cases()];
    }
}; ?>

<x-admin.layout heading="Usuários" subheading="Gerencie os usuários e os administradores do sistema">
    @if (session('status'))
        <flux:callout variant="success" icon="check-circle" class="mb-4" :heading="session('status')" />
    @endif

    <div class="mb-4 grid gap-3 sm:grid-cols-3">
        <div class="sm:col-span-2">
            <flux:input wire:model.live.debounce.400ms="search" icon="magnifying-glass" placeholder="Buscar por nome, e-mail ou RA" />
        </div>
        <flux:select wire:model.live="role">
            <flux:select.option value="">Todos os perfis</flux:select.option>
            @foreach ($roles as $option)
                <flux:select.option :value="$option->value">{{ $option->label() }}</flux:select.option>
            @endforeach
        </flux:select>
    </div>

    <flux:table :paginate="$users">
        <flux:table.columns>
            <flux:table.column>Nome</flux:table.column>
            <flux:table.column>E-mail</flux:table.column>
            <flux:table.column>RA</flux:table.column>
            <flux:table.column>Curso</flux:table.column>
            <flux:table.column>Publicações</flux:table.column>
            <flux:table.column>Perfil</flux:table.column>
            <flux:table.column></flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @forelse ($users as $user)
                <flux:table.row :key="$user->id">
                    <flux:table.cell variant="strong">{{ $user->name }}</flux:table.cell>
                    <flux:table.cell>{{ $user->email }}</flux:table.cell>
                    <flux:table.cell>{{ $user->registration_number ?? '—' }}</flux:table.cell>
                    <flux:table.cell>{{ $user->course ?? '—' }}</flux:table.cell>
                    <flux:table.cell>{{ $user->lost_found_items_count }}</flux:table.cell>
                    <flux:table.cell>
                        <flux:badge size="sm" :color="$user->isAdmin() ? 'indigo' : 'zinc'">{{ $user->role->label() }}</flux:badge>
                    </flux:table.cell>
                    <flux:table.cell align="end">
                        @can('changeRole', $user)
                            <flux:button
                                size="sm"
                                variant="ghost"
                                wire:click="toggleRole({{ $user->id }})"
                                wire:confirm="{{ $user->isAdmin() ? 'Remover o acesso de administrador deste usuário?' : 'Tornar este usuário administrador?' }}"
                            >
                                {{ $user->isAdmin() ? 'Tornar usuário' : 'Tornar admin' }}
                            </flux:button>
                        @endcan
                    </flux:table.cell>
                </flux:table.row>
            @empty
                <flux:table.row>
                    <flux:table.cell colspan="7" class="text-center">Nenhum usuário encontrado.</flux:table.cell>
                </flux:table.row>
            @endforelse
        </flux:table.rows>
    </flux:table>
</x-admin.layout>
