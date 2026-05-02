<?php
namespace Database\Factories;

use App\Helpers\UserHelper;
use App\Models\User;
use App\Models\UserRole;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class LanguageFactory extends Factory
{
    public function definition(): array
    {
        $adminUserRole = UserRole::where("name", UserHelper::USER_ROLE_ADMIN)->inRandomOrder()->first();
        $user          = User::query()->inRandomOrder()->where("user_role_id", $adminUserRole->id)->first() ?? null;

        return [
            'name'          => $this->faker->name(),
            'code'          => Str::snake(Str::lower($this->faker->unique()->lexify('??'))),
            'brief'         => $this->faker->sentence(),
            "created_by_id" => $user?->id ?? "1",
        ];
    }
}
