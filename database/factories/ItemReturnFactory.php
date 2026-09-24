<?php

namespace Database\Factories;

use App\Models\ItemReturn;
use App\Models\ReturnRequest;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ItemReturn>
 */
class ItemReturnFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'return_request_id' => ReturnRequest::factory()->approved(),
            // Usa o mesmo objeto da solicitação para manter os dados coerentes.
            'lost_found_item_id' => fn (array $attributes) => ReturnRequest::find($attributes['return_request_id'])->lost_found_item_id,
            'administrator_id' => User::factory()->admin(),
            'returned_at' => now(),
            'notes' => null,
        ];
    }
}
