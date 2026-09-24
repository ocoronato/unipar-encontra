<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Location;
use App\Models\LostFoundItem;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Dados fictícios para visualizar o sistema durante o desenvolvimento.
 * Executado somente nos ambientes "local" e "testing".
 */
class DemoDataSeeder extends Seeder
{
    public const DEV_USER_EMAIL = 'aluno@unipar-encontra.test';

    /**
     * Exemplos coerentes: [título, categoria, descrição].
     */
    private const EXAMPLES = [
        ['Celular Samsung preto', 'Eletrônicos', 'Celular com capinha transparente e película trincada no canto superior.'],
        ['Carteira marrom de couro', 'Carteiras', 'Carteira marrom com zíper e um chaveiro pequeno preso na alça.'],
        ['Chaveiro com 3 chaves', 'Chaves', 'Chaveiro de metal com três chaves e um pingente azul.'],
        ['Garrafa térmica azul', 'Acessórios', 'Garrafa térmica azul de 500 ml, com adesivos na lateral.'],
        ['Fone de ouvido sem fio', 'Eletrônicos', 'Fone bluetooth branco dentro do estojo de carregamento.'],
        ['Caderno de capa vermelha', 'Material Escolar', 'Caderno universitário de 10 matérias com capa vermelha.'],
        ['Óculos de grau', 'Acessórios', 'Óculos com armação preta dentro de um estojo cinza.'],
        ['Blusa de moletom cinza', 'Roupas', 'Blusa de moletom cinza tamanho M, com capuz.'],
        ['Mochila preta', 'Mochilas', 'Mochila preta com dois compartimentos e um chaveiro de ursinho.'],
        ['Carteirinha estudantil', 'Documentos', 'Carteirinha de estudante da UNIPAR em um porta-cartão transparente.'],
        ['Calculadora científica', 'Material Escolar', 'Calculadora científica cinza com capa protetora.'],
        ['Guarda-chuva preto', 'Acessórios', 'Guarda-chuva preto automático, de tamanho médio.'],
    ];

    public function run(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            return;
        }

        $student = User::firstOrCreate(
            ['email' => self::DEV_USER_EMAIL],
            [
                'name' => 'Aluno Demonstração',
                'password' => 'password',
                'registration_number' => '00000001',
                'course' => 'Sistemas de Informação',
            ],
        );
        $student->forceFill(['email_verified_at' => now()])->save();

        $others = User::factory(3)->create();
        $categories = Category::pluck('id', 'name');
        $locations = Location::pluck('id');

        foreach (self::EXAMPLES as $index => [$title, $category, $description]) {
            LostFoundItem::factory()->create([
                'user_id' => $others->random()->id,
                'category_id' => $categories[$category],
                'location_id' => $locations->random(),
                'title' => $title,
                'description' => $description,
                // Dois terços encontrados, um terço perdidos.
                'type' => $index % 3 === 2 ? 'lost' : 'found',
                // Os três últimos ficam aguardando moderação.
                'approval_status' => $index >= count(self::EXAMPLES) - 3 ? 'pending' : 'approved',
            ]);
        }

        // Publicações do aluno de demonstração.
        LostFoundItem::factory()->lost()->approved()->for($student)->create([
            'category_id' => $categories['Eletrônicos'],
            'location_id' => $locations->random(),
            'title' => 'Carregador de notebook',
            'description' => 'Carregador preto da marca Dell, com o cabo enrolado em um elástico.',
        ]);
        LostFoundItem::factory()->found()->for($student)->create([
            'category_id' => $categories['Outros'],
            'location_id' => $locations->random(),
            'title' => 'Pen drive vermelho',
            'description' => 'Pen drive vermelho de 32 GB encontrado em cima de uma mesa.',
        ]);
    }
}
