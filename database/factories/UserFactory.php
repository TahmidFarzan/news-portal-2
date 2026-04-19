<?php
namespace Database\Factories;

use App\Models\User;
use App\Models\UserRole;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserFactory extends Factory
{
    protected static ?string $password;

    public function definition(): array
    {
        $newsDeskUserRole = UserRole::where("name", "News Desk")->first();
        $adminUserRole    = UserRole::where("name", "Admin")->inRandomOrder()->first();

        $user = User::query()->inRandomOrder()->where("user_role_id", $adminUserRole->id)->first() ?? null;

        return [
            'name'              => $this->faker->name(),
            'email'             => $this->faker->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password'          => static::$password ??= Hash::make('password'),
            'remember_token'    => Str::random(10),
            'birth_date'        => $this->faker->dateTimeBetween('-25 years', '-18 years')->format('Y-m-d'),

            'gender'            => $this->faker->randomElement(['Male', 'Female']),
            'religion'          => $this->faker->randomElement(['Islam', 'Hindu', 'Christian']),

            'marital_status'    => $this->faker->randomElement(['Single', 'Married', 'Divorced', 'Separated']),
            'mobile'            => "+8801" . $this->faker->numberBetween(3, 9) . $this->faker->numberBetween(10000000, 99999999),

            "created_by_id"     => $user?->id ?? "1",
            'user_role_id'      => $newsDeskUserRole?->id,
            'is_default'        => false,
        ];
    }

    public function unverified(): static
    {
        return $this->state(fn(array $attributes) => [
            'email_verified_at' => null,
        ]);
    }
}
