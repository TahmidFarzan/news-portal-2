<?php

namespace Database\Factories;

use App\Models\MenuType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MenuType>
 */
class MenuTypeFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->name(),
        ];
    }
}
