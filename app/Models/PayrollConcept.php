<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PayrollConcept extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'type',
        'is_taxable',
        'is_social_security_applicable',
        'calculation_method',
        'config',
        'is_active',
    ];

    protected $casts = [
        'is_taxable' => 'boolean',
        'is_social_security_applicable' => 'boolean',
        'config' => 'array',
        'is_active' => 'boolean',
    ];

    public function incidents()
    {
        return $this->hasMany(EmployeeIncident::class);
    }

    public function items()
    {
        return $this->hasMany(PayrollEntryItem::class);
    }
}
