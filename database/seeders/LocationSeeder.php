<?php

namespace Database\Seeders;

use App\Models\Location;
use Illuminate\Database\Seeder;

class LocationSeeder extends Seeder
{
    /**
     * Locais iniciais da universidade.
     */
    public function run(): void
    {
        $locations = [
            'Bloco 1',
            'Bloco 2',
            'Bloco 3',
            'Biblioteca',
            'Cantina',
            'Laboratórios',
            'Estacionamento',
            'Área Externa',
            'Outros',
        ];

        foreach ($locations as $name) {
            Location::updateOrCreate(['name' => $name], ['active' => true]);
        }
    }
}
