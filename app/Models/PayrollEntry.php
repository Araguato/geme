<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PayrollEntry extends Model
{
    use HasFactory;

    protected $fillable = [
        'payroll_run_id',
        'employee_id',
        'employment_contract_id',
        'status',
        'base_salary_amount',
        'earnings_total',
        'deductions_total',
        'contributions_total',
        'net_pay',
        'hours_worked',
        'notes',
    ];

    protected $casts = [
        'base_salary_amount' => 'decimal:2',
        'earnings_total' => 'decimal:2',
        'deductions_total' => 'decimal:2',
        'contributions_total' => 'decimal:2',
        'net_pay' => 'decimal:2',
        'hours_worked' => 'decimal:2',
    ];

    public function run()
    {
        return $this->belongsTo(PayrollRun::class, 'payroll_run_id');
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function contract()
    {
        return $this->belongsTo(EmploymentContract::class, 'employment_contract_id');
    }

    public function items()
    {
        return $this->hasMany(PayrollEntryItem::class);
    }
}
