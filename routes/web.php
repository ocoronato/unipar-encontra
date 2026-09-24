<?php

use Illuminate\Support\Facades\Route;
use Livewire\Volt\Volt;

// Página inicial: pública (mostra apenas objetos aprovados).
Volt::route('/', 'home')->name('home');

Route::middleware(['auth'])->group(function () {
    // O mesmo formulário atende às duas telas; o tipo vem da rota.
    Volt::route('objetos/perdido/novo', 'items.form')->defaults('type', 'lost')->name('items.create.lost');
    Volt::route('objetos/encontrado/novo', 'items.form')->defaults('type', 'found')->name('items.create.found');

    Volt::route('objetos/{item}/editar', 'items.form')->whereNumber('item')->name('items.edit');

    Volt::route('buscar', 'items.search')->name('items.search');
    Volt::route('objetos/{item}', 'items.show')->whereNumber('item')->name('items.show');

    Volt::route('meus-objetos', 'items.mine')->name('items.mine');
    Volt::route('minhas-solicitacoes', 'return-requests.mine')->name('return-requests.mine');
    Volt::route('objetos/{item}/solicitar-devolucao', 'return-requests.create')->whereNumber('item')->name('return-requests.create');

    Route::redirect('settings', 'settings/profile');

    Volt::route('settings/profile', 'settings.profile')->name('settings.profile');
    Volt::route('settings/password', 'settings.password')->name('settings.password');
    Volt::route('settings/appearance', 'settings.appearance')->name('settings.appearance');
});

/*
| Área administrativa: protegida no backend pelo Gate "access-admin".
| O middleware "can" também é aplicado às requisições Livewire dessas páginas.
*/
Route::middleware(['auth', 'can:access-admin'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        Volt::route('/', 'admin.dashboard')->name('dashboard');
        Volt::route('usuarios', 'admin.users.index')->name('users.index');
        // Categorias e Locais usam o mesmo componente de cadastro.
        Volt::route('categorias', 'admin.catalog')->defaults('resource', 'categories')->name('categories.index');
        Volt::route('locais', 'admin.catalog')->defaults('resource', 'locations')->name('locations.index');

        Volt::route('objetos', 'admin.items.index')->name('items.index');
        Volt::route('solicitacoes', 'admin.return-requests.index')->name('return-requests.index');
        Volt::route('devolucoes', 'admin.returns.index')->name('returns.index');

        // Relatórios (Perdidos e Encontrados usam o mesmo componente).
        Volt::route('relatorios/perdidos', 'admin.reports.items')->defaults('type', 'lost')->name('reports.lost');
        Volt::route('relatorios/encontrados', 'admin.reports.items')->defaults('type', 'found')->name('reports.found');
        Volt::route('relatorios/devolvidos', 'admin.reports.returned')->name('reports.returned');
        Volt::route('relatorios/solicitacoes', 'admin.reports.requests')->name('reports.requests');
    });

require __DIR__.'/auth.php';
