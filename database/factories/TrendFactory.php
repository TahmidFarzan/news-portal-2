<?php
namespace Database\Factories;

use App\Models\Trend;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Trend>
 */
class TrendFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $user = User::where("is_super_admin", true)->inRandomOrder()->first();
        return [
            'updated_at'    => null,
            'tag_id'        => rand(1, 12),
            "created_by_id" => $user?->id ?? "1",
        ];
    }
}
