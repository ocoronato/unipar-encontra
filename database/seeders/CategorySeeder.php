<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    /**
     * Categorias iniciais do sistema.
     */
    public function run(): void
    {
        $categories = [
            'Eletrônicos' => 'Celulares, fones, carregadores, notebooks e similares.',
            'Documentos' => 'RG, CNH, carteirinha estudantil, cartões e papéis.',
            'Chaves' => 'Chaves avulsas, chaveiros e controles.',
            'Material Escolar' => 'Cadernos, livros, estojos, calculadoras.',
            'Roupas' => 'Blusas, jaquetas, bonés e outras peças.',
            'Acessórios' => 'Óculos, relógios, joias, guarda-chuvas, garrafas.',
            'Mochilas' => 'Mochilas, bolsas e sacolas.',
            'Carteiras' => 'Carteiras e porta-cartões.',
            'Outros' => 'Objetos que não se encaixam nas demais categorias.',
        ];

        foreach ($categories as $name => $description) {
            // updateOrCreate permite rodar o seeder mais de uma vez sem duplicar.
            Category::updateOrCreate(['name' => $name], ['description' => $description, 'active' => true]);
        }
    }
}
