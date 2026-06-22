<?php

namespace App\Services\Payroll;

use App\Models\EmploymentContract;
use App\Models\PayrollConcept;
use App\Models\PayrollEntry;
use App\Models\PayrollEntryItem;
use App\Models\PayrollRun;
use Carbon\Carbon;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PayrollGenerator
{
    private const BASE_SALARY_CONCEPT = 'SAL_BASE';

    /**
     * Punto de entrada principal para generar o regenerar los detalles de una corrida.
     */
    public function generateForRun(PayrollRun $run, bool $forceRecalculate = false): PayrollRun
    {
        return DB::transaction(function () use ($run, $forceRecalculate) {
            $run->loadMissing([
                'entries.items',
                'entries.employee.party',
                'entries.contract',
                'period',
                'period.incidents.concept',
            ]);

            if ($run->entries->isEmpty()) {
                $this->bootstrapEntries($run);
                $run->load('entries');
            }

            if ($forceRecalculate || $run->status !== 'draft') {
                $this->recalculateEntries($run);
            }

            return $run->fresh(['entries.items', 'entries.employee.party', 'entries.contract']);
        });
    }

    /**
     * Crea registros base de payroll_entries para cada empleado elegible del periodo.
     */
    public function bootstrapEntries(PayrollRun $run): void
    {
        $period = $run->period;
        if (!$period) {
            Log::warning('Intento de generar nómina sin periodo asociado', ['payroll_run_id' => $run->id]);
            return;
        }

        $contracts = EmploymentContract::query()
            ->where('is_active', true)
            ->whereDate('start_date', '<=', $period->end_date)
            ->where(function ($query) use ($period) {
                $query->whereNull('end_date')
                    ->orWhereDate('end_date', '>=', $period->start_date);
            })
            ->with('employee')
            ->get();

        foreach ($contracts as $contract) {
            PayrollEntry::firstOrCreate(
                [
                    'payroll_run_id' => $run->id,
                    'employee_id' => $contract->employee_id,
                ],
                [
                    'employment_contract_id' => $contract->id,
                    'status' => 'draft',
                    'base_salary_amount' => $contract->salary_amount,
                ]
            );
        }
    }

    /**
     * Recalcula montos de cada entrada según conceptos, incidencias y contrato.
     * Actualmente sólo sincroniza los acumulados base como punto de partida.
     */
    public function recalculateEntries(PayrollRun $run): void
    {
        $period = $run->period;
        if (!$period) {
            Log::warning('Intento de recalcular nómina sin periodo asociado', ['payroll_run_id' => $run->id]);
            return;
        }

        $baseConcept = PayrollConcept::where('code', self::BASE_SALARY_CONCEPT)->first();
        $incidentsByEmployee = $period->incidents
            ? $period->incidents->groupBy('employee_id')
            : collect();

        foreach ($run->entries as $entry) {
            $entry->loadMissing(['items', 'contract', 'employee.party']);

            $employeeIncidents = $incidentsByEmployee->get($entry->employee_id) ?? collect();

            // Eliminar items automáticos antes de regenerar
            $entry->items()
                ->where('metadata->origin', 'auto')
                ->delete();

            $itemsPayload = [];
            $contract = $entry->contract;
            $baseAmount = 0.0;
            $hoursWorked = $entry->hours_worked;

            if ($contract) {
                $baseData = $this->calculateBaseSalary($contract, $run, $entry, $employeeIncidents);
                $baseAmount = $baseData['amount'];
                $hoursWorked = $baseData['hours'] ?? $hoursWorked;

                if ($baseConcept && $baseAmount > 0) {
                    $itemsPayload[] = [
                        'payroll_concept_id' => $baseConcept->id,
                        'type' => $baseConcept->type,
                        'quantity' => $baseData['quantity'],
                        'rate' => $baseData['rate'],
                        'amount' => $baseAmount,
                        'is_taxable' => $baseConcept->is_taxable,
                        'is_social_security_applicable' => $baseConcept->is_social_security_applicable,
                        'metadata' => [
                            'origin' => 'auto',
                            'source' => 'base',
                            'period_days' => $baseData['period_days'] ?? null,
                        ],
                    ];
                }
            }

            $earningsRunning = $baseAmount;

            foreach ($employeeIncidents as $incident) {
                $concept = $incident->concept;
                if (!$concept || !$concept->is_active) {
                    continue;
                }

                $calc = $this->calculateIncidentAmount($incident, $concept, $contract, $earningsRunning, $baseAmount);

                if ($calc['amount'] === 0.0) {
                    continue;
                }

                if ($concept->type === 'earning') {
                    $earningsRunning += $calc['amount'];
                }

                $itemsPayload[] = [
                    'payroll_concept_id' => $concept->id,
                    'type' => $concept->type,
                    'quantity' => $calc['quantity'],
                    'rate' => $calc['rate'],
                    'amount' => $calc['amount'],
                    'is_taxable' => $concept->is_taxable,
                    'is_social_security_applicable' => $concept->is_social_security_applicable,
                    'metadata' => [
                        'origin' => 'auto',
                        'source' => 'incident',
                        'incident_id' => $incident->id,
                        'incident_type' => $incident->incident_type,
                    ],
                ];
            }

            foreach ($itemsPayload as $itemData) {
                $entry->items()->create($itemData);
            }

            $entry->load('items');

            $entry->base_salary_amount = round($baseAmount, 2);
            if ($hoursWorked !== null) {
                $entry->hours_worked = round((float) $hoursWorked, 2);
            }

            $entry->earnings_total = round($entry->items->where('type', 'earning')->sum('amount'), 2);
            $entry->deductions_total = round($entry->items->where('type', 'deduction')->sum('amount'), 2);
            $entry->contributions_total = round($entry->items->where('type', 'contribution')->sum('amount'), 2);
            $entry->net_pay = round($entry->earnings_total - $entry->deductions_total, 2);
            $entry->status = $run->status === 'approved' ? 'approved' : 'draft';
            $entry->save();
        }
    }

    private function calculateBaseSalary(
        EmploymentContract $contract,
        PayrollRun $run,
        PayrollEntry $entry,
        Collection $incidents
    ): array {
        $period = $run->period;
        $amount = 0.0;
        $quantity = null;
        $rate = null;
        $hours = null;

        if ($contract->salary_type === 'por_hora') {
            $hours = $entry->hours_worked;
            if ($hours === null) {
                $hours = $incidents->sum(fn ($incident) => $incident->hours ?? 0);
            }
            $hours = round((float) $hours, 2);
            $rate = round((float) $contract->salary_amount, 4);
            $amount = round($hours * $rate, 2);
            $quantity = $hours;
        } else {
            $salaryAmount = (float) $contract->salary_amount;
            if ($period && $period->start_date && $period->end_date) {
                $start = Carbon::parse($period->start_date);
                $end = Carbon::parse($period->end_date);
                $daysInPeriod = $start->diffInDays($end) + 1;
                $quantity = $daysInPeriod;
                $basisDays = $this->resolveBasisDays($contract->pay_frequency ?? 'mensual', $start);
                $amount = round($salaryAmount * ($daysInPeriod / max($basisDays, 1)), 2);
            } else {
                $amount = round($salaryAmount, 2);
            }
        }

        return [
            'amount' => $amount,
            'quantity' => $quantity,
            'rate' => $rate,
            'hours' => $hours,
            'period_days' => $quantity,
        ];
    }

    private function calculateIncidentAmount(
        $incident,
        PayrollConcept $concept,
        ?EmploymentContract $contract,
        float $currentEarnings,
        float $baseAmount
    ): array {
        $amount = (float) ($incident->amount ?? 0);
        $quantity = $incident->quantity ?? $incident->hours;
        $rate = null;
        $config = $concept->config ?? [];

        if ($incident->amount === null) {
            switch ($concept->calculation_method) {
                case 'fixed_amount':
                    $amount = (float) Arr::get($config, 'amount', 0);
                    break;
                case 'hours_rate':
                    $hours = $incident->hours ?? $quantity ?? 0;
                    $rate = (float) Arr::get($config, 'rate', $contract?->salary_amount ?? 0);
                    $multiplier = (float) Arr::get($config, 'multiplier', 1);
                    $amount = $hours * $rate * $multiplier;
                    $quantity = $hours;
                    break;
                case 'percentage':
                    $percentage = (float) Arr::get($config, 'rate', 0);
                    if ($percentage > 1) {
                        $percentage = $percentage / 100;
                    }
                    $base = $concept->type === 'deduction'
                        ? ($baseAmount ?: $currentEarnings)
                        : $currentEarnings;
                    $amount = $base * $percentage;
                    break;
                case 'base_salary':
                    $amount = $baseAmount ?: $currentEarnings;
                    break;
                default:
                    // manual / otros métodos: dejar monto en 0 si no está definido
                    break;
            }
        }

        return [
            'amount' => round((float) $amount, 2),
            'quantity' => $quantity !== null ? round((float) $quantity, 4) : null,
            'rate' => $rate !== null ? round((float) $rate, 4) : null,
        ];
    }

    private function resolveBasisDays(string $frequency, Carbon $reference): int
    {
        return match ($frequency) {
            'semanal' => 7,
            'quincenal' => 15,
            'mensual', 'monthly' => $reference->daysInMonth,
            default => $reference->daysInMonth,
        };
    }
}
