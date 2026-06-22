<?php

namespace Database\Factories;

use App\Models\PayrollConcept;
use App\Models\PayrollEntry;
use App\Models\PayrollEntryItem;
use Illuminate\Database\Eloquent\Factories\Factory;

class PayrollEntryItemFactory extends Factory
{
    protected $model = PayrollEntryItem::class;

    public function definition(): array
    {
        $type = $this->faker->randomElement(['earning', 'deduction', 'contribution']);
        $amount = $this->faker->randomFloat(2, 5, 500);

        return [
            'payroll_entry_id' => PayrollEntry::factory(),
            'payroll_concept_id' => PayrollConcept::factory(['type' => $type]),
            'type' => $type,
            'quantity' => $this->faker->optional()->randomFloat(2, 1, 40),
            'rate' => $this->faker->optional()->randomFloat(4, 1, 50),
            'amount' => $amount,
            'is_taxable' => $type === 'earning',
            'is_social_security_applicable' => $type !== 'deduction',
            'metadata' => [],
        ];
    }
}
