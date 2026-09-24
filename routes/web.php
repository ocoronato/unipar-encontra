<?php

use Illuminate\Support\Facades\Route;
use Livewire\Volt\Volt;

// Página inicial: pública (mostra apenas objetos aprovados).
Volt::route('/', 'home')->name('home');

Route::middleware(['auth'])->group(function () {
    // O mesmo formulário atende às duas telas; o tipo vem da rota.
    Volt::route('objetos/perdido/novo', 'items.form')->defaults('type', 'lost')->name('items.create.lost');
    Volt::route('objetos/encontrado/novo', 'items.form')->defaults('type', 'found')->name('items.create.found');

    // Telas provisórias: serão substituídas pelos componentes das próximas etapas.
    Route::view('buscar', 'coming-soon', ['title' => 'Buscar Objetos'])->name('items.search');
    Route::view('objetos/{item}', 'coming-soon', ['title' => 'Detalhes do Objeto'])->whereNumber('item')->name('items.show');
    Route::view('meus-objetos', 'coming-soon', ['title' => 'Meus Objetos'])->name('items.mine');
    Route::view('minhas-solicitacoes', 'coming-soon', ['title' => 'Minhas Solicitações'])->name('return-requests.mine');

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
    });

require __DIR__.'/auth.php';
