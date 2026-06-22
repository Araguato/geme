<?php

namespace Database\Factories;

use App\Models\Employee;
use App\Models\EmploymentContract;
use Illuminate\Database\Eloquent\Factories\Factory;

class EmploymentContractFactory extends Factory
{
    protected $model = EmploymentContract::class;

    public function definition(): array
    {
        $salaryType = $this->faker->randomElement(['mensual', 'por_hora']);
        $salaryAmount = $salaryType === 'mensual'
            ? $this->faker->numberBetween(400, 2000)
            : $this->faker->randomFloat(2, 2, 10);

        return [
            'employee_id' => Employee::factory(),
            'contract_type' => $this->faker->randomElement(['Tiempo completo', 'Medio tiempo', 'Contratista']),
            'job_title' => $this->faker->jobTitle(),
            'start_date' => $this->faker->dateTimeBetween('-2 years', 'now')->format('Y-m-d'),
            'end_date' => null,
            'salary_type' => $salaryType,
            'salary_amount' => $salaryAmount,
            'pay_frequency' => $this->faker->randomElement(['mensual', 'quincenal', 'semanal']),
            'currency_code' => 'USD',
            'is_active' => true,
            'notes' => $this->faker->optional()->sentence(),
        ];
    }
}
