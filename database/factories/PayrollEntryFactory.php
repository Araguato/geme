<?php

namespace Database\Factories;

use App\Models\Employee;
use App\Models\EmploymentContract;
use App\Models\PayrollEntry;
use App\Models\PayrollRun;
use Illuminate\Database\Eloquent\Factories\Factory;

class PayrollEntryFactory extends Factory
{
    protected $model = PayrollEntry::class;

    public function definition(): array
    {
        return [
            'payroll_run_id' => PayrollRun::factory(),
            'employee_id' => Employee::factory(),
            'employment_contract_id' => EmploymentContract::factory(),
            'status' => 'draft',
            'base_salary_amount' => $this->faker->randomFloat(2, 200, 2000),
            'earnings_total' => $this->faker->randomFloat(2, 200, 2000),
            'deductions_total' => $this->faker->randomFloat(2, 0, 500),
            'contributions_total' => $this->faker->randomFloat(2, 0, 300),
            'net_pay' => $this->faker->randomFloat(2, 200, 2000),
            'hours_worked' => $this->faker->optional()->randomFloat(2, 10, 200),
            'notes' => $this->faker->optional()->sentence(),
        ];
    }
}
