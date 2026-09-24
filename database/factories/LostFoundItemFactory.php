<?php

namespace Database\Factories;

use App\Enums\ApprovalStatus;
use App\Enums\ItemStatus;
use App\Enums\ItemType;
use App\Models\Category;
use App\Models\Location;
use App\Models\LostFoundItem;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LostFoundItem>
 */
class LostFoundItemFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'category_id' => Category::factory(),
            'location_id' => Location::factory(),
            'type' => fake()->randomElement(ItemType::cases()),
            'title' => fake()->randomElement([
                'Celular preto', 'Carteira marrom', 'Chaveiro com 3 chaves', 'Garrafa térmica azul',
                'Fone de ouvido sem fio', 'Caderno de capa vermelha', 'Óculos de grau', 'Blusa cinza',
            ]),
            'description' => fake()->paragraph(),
            'occurred_at' => fake()->dateTimeBetween('-30 days', 'now'),
            'status' => ItemStatus::Active,
            'approval_status' => ApprovalStatus::Pending,
        ];
    }

    public function lost(): static
    {
        return $this->state(fn (array $attributes) => ['type' => ItemType::Lost]);
    }

    public function found(): static
    {
        return $this->state(fn (array $attributes) => ['type' => ItemType::Found]);
    }

    public function approved(): static
    {
        return $this->state(fn (array $attributes) => ['approval_status' => ApprovalStatus::Approved]);
    }

    public function rejected(): static
    {
        return $this->state(fn (array $attributes) => ['approval_status' => ApprovalStatus::Rejected]);
    }

    public function returned(): static
    {
        return $this->approved()->state(fn (array $attributes) => ['status' => ItemStatus::Returned]);
    }
}
