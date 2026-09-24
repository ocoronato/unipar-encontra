<?php

namespace Database\Factories;

use App\Enums\ReturnRequestStatus;
use App\Models\LostFoundItem;
use App\Models\ReturnRequest;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ReturnRequest>
 */
class ReturnRequestFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'lost_found_item_id' => LostFoundItem::factory()->found()->approved(),
            'user_id' => User::factory(),
            'message' => fake()->paragraph(),
            'status' => ReturnRequestStatus::Pending,
            'admin_notes' => null,
        ];
    }

    public function approved(): static
    {
        return $this->state(fn (array $attributes) => ['status' => ReturnRequestStatus::Approved]);
    }
}
