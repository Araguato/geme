<?php

namespace Database\Factories;

use App\Models\Party;
use Illuminate\Database\Eloquent\Factories\Factory;

class PartyFactory extends Factory
{
    protected $model = Party::class;

    public function definition(): array
    {
        return [
            'type' => 'employee',
            'name' => $this->faker->name(),
            'document_type' => $this->faker->randomElement(['CI', 'RIF']),
            'document_number' => $this->faker->unique()->numerify('########'),
            'phone' => $this->faker->phoneNumber(),
            'email' => $this->faker->unique()->safeEmail(),
            'address' => $this->faker->address(),
            'notes' => $this->faker->sentence(),
            'is_active' => true,
        ];
    }
}
