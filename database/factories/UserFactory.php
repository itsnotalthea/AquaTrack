<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    protected static ?string $password;

    protected $model = User::class;

    public function definition(): array
    {
        return [
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'mobile' => '09'.fake()->numerify('#########'),
            'role' => User::ROLE_CUSTOMER,
            'house_no' => (string) fake()->numberBetween(1, 250),
            'street' => fake()->streetName(),
            'subdivision' => fake()->citySuffix(),
            'city' => 'Marikina City',
            'remember_token' => Str::random(10),
        ];
    }

    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    public function role(string $role): static
    {
        // internal roles must carry a matching email domain
        $domain = match ($role) {
            User::ROLE_ADMIN => 'admin.com',
            User::ROLE_STAFF => 'staff.com',
            User::ROLE_DRIVER => 'delivery.com',
            default => null,
        };

        return $this->state(fn (array $attributes) => [
            'role' => $role,
            'email' => $domain
                ? fake()->unique()->userName().'@'.$domain
                : fake()->unique()->safeEmail(),
        ]);
    }

    public function customer(): static
    {
        return $this->role(User::ROLE_CUSTOMER);
    }

    public function staff(): static
    {
        return $this->role(User::ROLE_STAFF);
    }

    public function driver(): static
    {
        return $this->role(User::ROLE_DRIVER);
    }

    public function admin(): static
    {
        return $this->role(User::ROLE_ADMIN);
    }
}
