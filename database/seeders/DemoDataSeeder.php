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
        $categories = Category::all();
        $locations = Location::all();

        // Usa categorias e locais reais (dos seeders) em vez de criar novos.
        $place = fn () => [
            'category_id' => $categories->random()->id,
            'location_id' => $locations->random()->id,
        ];
        $byOthers = fn () => ['user_id' => $others->random()->id];

        // Publicações já aprovadas (aparecem na busca pública).
        LostFoundItem::factory(8)->found()->approved()->state($place)->state($byOthers)->create();
        LostFoundItem::factory(4)->lost()->approved()->state($place)->state($byOthers)->create();

        // Publicações aguardando moderação.
        LostFoundItem::factory(3)->state($place)->state($byOthers)->create();

        // Publicações do aluno de demonstração.
        LostFoundItem::factory()->lost()->approved()->state($place)->for($student)->create();
        LostFoundItem::factory()->found()->state($place)->for($student)->create();
    }
}
