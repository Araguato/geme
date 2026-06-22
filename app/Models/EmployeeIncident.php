<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmployeeIncident extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_id',
        'payroll_period_id',
        'payroll_concept_id',
        'incident_date',
        'incident_type',
        'quantity',
        'hours',
        'amount',
        'status',
        'description',
        'metadata',
    ];

    protected $casts = [
        'incident_date' => 'date',
        'quantity' => 'decimal:4',
        'hours' => 'decimal:2',
        'amount' => 'decimal:2',
        'metadata' => 'array',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function period()
    {
        return $this->belongsTo(PayrollPeriod::class, 'payroll_period_id');
    }

    public function concept()
    {
        return $this->belongsTo(PayrollConcept::class, 'payroll_concept_id');
    }
}
