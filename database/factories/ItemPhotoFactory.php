<?php

namespace Database\Factories;

use App\Models\ItemPhoto;
use App\Models\LostFoundItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ItemPhoto>
 */
class ItemPhotoFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'lost_found_item_id' => LostFoundItem::factory(),
            'path' => 'items/'.fake()->uuid().'.jpg',
            'original_name' => 'foto.jpg',
        ];
    }
}
