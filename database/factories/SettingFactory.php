<?php
namespace Database\Factories;

use App\Models\Setting;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Setting>
 */
class SettingFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'group'         => "Demo",
            'key'           => "name",
            'label'         => "Name",
            'type'          => "String",
            'value'         => $this->faker->name(),
        ];
    }
}
