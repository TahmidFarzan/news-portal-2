<?php

namespace Database\Factories;

use App\Models\NewsType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<NewsType>
 */
class NewsTypeFactory extends Factory
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
