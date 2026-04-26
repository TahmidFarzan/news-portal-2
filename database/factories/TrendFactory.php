<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\UserRole;
use App\Models\Trend;
use Illuminate\Database\Eloquent\Factories\Factory;

class TrendFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $adminUserRole = UserRole::where("name", "Admin")->inRandomOrder()->first();
        $user          = User::inRandomOrder()->where("user_role_id", $adminUserRole->id)->first() ?? null;
        return [
            'updated_at' => null,
			'tag_id' => rand(1, 12),
            "created_by_id" => $user?->id ?? "1",
        ];
    }
}
