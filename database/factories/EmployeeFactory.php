<?php

namespace Database\Factories;

use App\Models\Employee;
use App\Models\Party;
use Illuminate\Database\Eloquent\Factories\Factory;

class EmployeeFactory extends Factory
{
    protected $model = Employee::class;

    public function definition(): array
    {
        $salaryType = $this->faker->randomElement(['mensual', 'por_hora']);
        $monthlySalary = $salaryType === 'mensual' ? $this->faker->numberBetween(400, 1500) : null;
        $hourlyRate = $salaryType === 'por_hora' ? $this->faker->randomFloat(2, 2, 10) : null;

        return [
            'party_id' => Party::factory(),
            'user_id' => null,
            'role' => $this->faker->jobTitle(),
            'hire_date' => $this->faker->date(),
            'salary_type' => $salaryType,
            'monthly_salary' => $monthlySalary,
            'hourly_rate' => $hourlyRate,
            'is_current' => true,
        ];
    }
}
