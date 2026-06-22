<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PayrollRun extends Model
{
    use HasFactory;

    protected $fillable = [
        'payroll_period_id',
        'code',
        'status',
        'processed_at',
        'approved_by',
        'approved_at',
    ];

    protected $casts = [
        'processed_at' => 'date',
        'approved_at' => 'datetime',
    ];

    public function period()
    {
        return $this->belongsTo(PayrollPeriod::class, 'payroll_period_id');
    }

    public function entries()
    {
        return $this->hasMany(PayrollEntry::class);
    }

    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
