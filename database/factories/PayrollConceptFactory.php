<?php

namespace Database\Factories;

use App\Models\PayrollConcept;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class PayrollConceptFactory extends Factory
{
    protected $model = PayrollConcept::class;

    public function definition(): array
    {
        $type = $this->faker->randomElement(['earning', 'deduction', 'contribution']);

        return [
            'code' => strtoupper(Str::random(6)),
            'name' => ucfirst($type) . ' ' . $this->faker->word(),
            'type' => $type,
            'is_taxable' => $type === 'earning',
            'is_social_security_applicable' => $type !== 'deduction',
            'calculation_method' => $this->faker->randomElement([
                'fixed_amount',
                'percentage',
                'hours_rate',
                'base_salary',
            ]),
            'config' => [],
            'is_active' => true,
        ];
    }
}
