<?php

namespace Database\Factories;

use App\Models\PayrollPeriod;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Carbon\Carbon;

class PayrollPeriodFactory extends Factory
{
    protected $model = PayrollPeriod::class;

    public function definition(): array
    {
        $start = Carbon::instance($this->faker->dateTimeBetween('-1 year', 'now'))->startOfMonth();
        $end = (clone $start)->endOfMonth();

        return [
            'name' => 'Periodo ' . Str::title($start->locale('es')->monthName) . ' ' . $start->year,
            'period_type' => 'mensual',
            'start_date' => $start->toDateString(),
            'end_date' => $end->toDateString(),
            'pay_date' => (clone $end)->addDays(5)->toDateString(),
            'status' => 'draft',
        ];
    }
}
