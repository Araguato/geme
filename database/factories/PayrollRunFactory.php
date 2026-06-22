<?php

namespace Database\Factories;

use App\Models\PayrollPeriod;
use App\Models\PayrollRun;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class PayrollRunFactory extends Factory
{
    protected $model = PayrollRun::class;

    public function definition(): array
    {
        return [
            'payroll_period_id' => PayrollPeriod::factory(),
            'code' => 'RUN-' . strtoupper(Str::random(5)),
            'status' => $this->faker->randomElement(['draft', 'processing', 'approved']),
            'processed_at' => null,
            'approved_at' => null,
            'approved_by' => null,
        ];
    }
}
