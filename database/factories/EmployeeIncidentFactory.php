<?php

namespace Database\Factories;

use App\Models\Employee;
use App\Models\EmployeeIncident;
use App\Models\PayrollConcept;
use App\Models\PayrollPeriod;
use Illuminate\Database\Eloquent\Factories\Factory;
use Carbon\Carbon;

class EmployeeIncidentFactory extends Factory
{
    protected $model = EmployeeIncident::class;

    public function definition(): array
    {
        $date = Carbon::instance($this->faker->dateTimeBetween('-1 month', 'now'));

        return [
            'employee_id' => Employee::factory(),
            'payroll_period_id' => PayrollPeriod::factory(),
            'payroll_concept_id' => PayrollConcept::factory(),
            'incident_date' => $date->toDateString(),
            'incident_type' => $this->faker->randomElement(['bonus', 'deduction', 'adjustment']),
            'quantity' => $this->faker->optional()->randomFloat(2, 1, 40),
            'hours' => $this->faker->optional()->randomFloat(2, 1, 40),
            'amount' => $this->faker->optional()->randomFloat(2, 10, 200),
            'status' => $this->faker->randomElement(['pending', 'approved', 'rejected']),
            'description' => $this->faker->sentence(),
            'metadata' => [],
        ];
    }
}
