<?php

use Livewire\Volt\Component;

new class extends Component {
    //
}; ?>

<div class="flex flex-col items-start">
    @include('partials.settings-heading')

    <x-settings.layout heading="Aparência" subheading="Escolha o tema de cores da interface">
        <flux:radio.group x-data variant="segmented" x-model="$flux.appearance">
            <flux:radio value="light" icon="sun">Claro</flux:radio>
            <flux:radio value="dark" icon="moon">Escuro</flux:radio>
            <flux:radio value="system" icon="computer-desktop">Sistema</flux:radio>
        </flux:radio.group>
    </x-settings.layout>
</div>
