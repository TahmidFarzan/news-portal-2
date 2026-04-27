<?php

namespace Database\Factories;

use App\Models\NewsUserType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<NewsUserType>
 */
class NewsUserTypeFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => $this->faker->name(),
        ];
    }
}
