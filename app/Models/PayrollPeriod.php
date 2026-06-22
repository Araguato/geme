<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PayrollPeriod extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'period_type',
        'start_date',
        'end_date',
        'pay_date',
        'status',
        'locked_at',
        'closed_at',
        'closed_by',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'pay_date' => 'date',
        'locked_at' => 'datetime',
        'closed_at' => 'datetime',
    ];

    public function runs()
    {
        return $this->hasMany(PayrollRun::class);
    }

    public function incidents()
    {
        return $this->hasMany(EmployeeIncident::class);
    }

    public function closedBy()
    {
        return $this->belongsTo(User::class, 'closed_by');
    }
}
