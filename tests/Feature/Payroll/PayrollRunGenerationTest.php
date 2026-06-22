<?php

namespace Tests\Feature\Payroll;

use App\Models\Employee;
use App\Models\EmployeeIncident;
use App\Models\EmploymentContract;
use App\Models\PayrollConcept;
use App\Models\PayrollEntry;
use App\Models\PayrollPeriod;
use App\Models\PayrollRun;
use App\Models\User;
use App\Models\Party;
use App\Services\Payroll\PayrollGenerator;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PayrollRunGenerationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->markTestSkipped('Pruebas de generación de nómina deshabilitadas temporalmente.');

        PayrollConcept::factory()->create([
            'code' => 'SAL_BASE',
            'name' => 'Salario base',
            'type' => 'earning',
            'calculation_method' => 'base_salary',
            'is_taxable' => true,
            'is_social_security_applicable' => true,
            'config' => [],
        ]);
    }

    public function test_generate_run_creates_entries_and_items()
    {
        $admin = User::factory()->create();

        $period = PayrollPeriod::factory()->create([
            'name' => 'Abril 2026',
            'period_type' => 'mensual',
            'start_date' => Carbon::parse('2026-04-01'),
            'end_date' => Carbon::parse('2026-04-30'),
            'status' => 'open',
        ]);

        $party = Party::factory()->create([
            'type' => 'employee',
            'name' => 'Carlos Test',
            'is_active' => true,
        ]);

        $employee = Employee::factory()->create([
            'party_id' => $party->id,
            'hire_date' => '2025-01-01',
            'salary_type' => 'mensual',
            'monthly_salary' => 600,
            'is_current' => true,
        ]);

        $contract = EmploymentContract::factory()->create([
            'employee_id' => $employee->id,
            'start_date' => '2025-01-01',
            'salary_type' => 'mensual',
            'salary_amount' => 600,
            'pay_frequency' => 'mensual',
            'is_active' => true,
        ]);

        $run = PayrollRun::factory()->create([
            'payroll_period_id' => $period->id,
            'status' => 'processing',
        ]);

        $generator = $this->app->make(PayrollGenerator::class);
        $generator->generateForRun($run, true);

        $entry = PayrollEntry::where('payroll_run_id', $run->id)
            ->where('employee_id', $employee->id)
            ->first();

        $this->assertNotNull($entry);
        $this->assertEquals(600.00, (float) $entry->earnings_total);
        $this->assertEquals(600.00, (float) $entry->net_pay);
        $this->assertCount(1, $entry->items);
    }

    public function test_generate_run_includes_incident_percentage()
    {
        $concept = PayrollConcept::factory()->create([
            'code' => 'BON_TRAN',
            'name' => 'Bono transporte',
            'type' => 'earning',
            'calculation_method' => 'percentage',
            'config' => ['rate' => 0.10],
            'is_taxable' => false,
            'is_social_security_applicable' => false,
        ]);

        $period = PayrollPeriod::factory()->create([
            'start_date' => '2026-04-01',
            'end_date' => '2026-04-30',
            'status' => 'open',
        ]);

        $party = Party::factory()->create(['type' => 'employee', 'is_active' => true]);
        $employee = Employee::factory()->create([
            'party_id' => $party->id,
            'salary_type' => 'mensual',
            'monthly_salary' => 1000,
            'is_current' => true,
        ]);

        $contract = EmploymentContract::factory()->create([
            'employee_id' => $employee->id,
            'salary_type' => 'mensual',
            'salary_amount' => 1000,
            'pay_frequency' => 'mensual',
            'start_date' => '2025-01-01',
            'is_active' => true,
        ]);

        $run = PayrollRun::factory()->create([
            'payroll_period_id' => $period->id,
            'status' => 'processing',
        ]);

        EmployeeIncident::factory()->create([
            'employee_id' => $employee->id,
            'payroll_period_id' => $period->id,
            'payroll_concept_id' => $concept->id,
            'incident_date' => '2026-04-15',
            'incident_type' => 'bonus',
            'status' => 'approved',
        ]);

        $generator = $this->app->make(PayrollGenerator::class);
        $generator->generateForRun($run, true);

        $entry = PayrollEntry::where('payroll_run_id', $run->id)->where('employee_id', $employee->id)->first();

        $this->assertNotNull($entry);
        $this->assertEquals(1100.00, (float) $entry->earnings_total);
        $this->assertEquals(1100.00, (float) $entry->net_pay);
        $this->assertCount(2, $entry->items);

        $bonusItem = $entry->items()->where('payroll_concept_id', $concept->id)->first();
        $this->assertEquals(100.00, (float) $bonusItem->amount);
    }
}
