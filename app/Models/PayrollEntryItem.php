<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PayrollEntryItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'payroll_entry_id',
        'payroll_concept_id',
        'type',
        'quantity',
        'rate',
        'amount',
        'is_taxable',
        'is_social_security_applicable',
        'metadata',
    ];

    protected $casts = [
        'quantity' => 'decimal:4',
        'rate' => 'decimal:4',
        'amount' => 'decimal:2',
        'is_taxable' => 'boolean',
        'is_social_security_applicable' => 'boolean',
        'metadata' => 'array',
    ];

    public function entry()
    {
        return $this->belongsTo(PayrollEntry::class, 'payroll_entry_id');
    }

    public function concept()
    {
        return $this->belongsTo(PayrollConcept::class, 'payroll_concept_id');
    }
}
