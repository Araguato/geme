<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FiscalLedger extends Model
{
    use HasFactory;

    protected $fillable = [
        'entry_type',
        'period',
        'company_tax_id',
        'company_name',
        'document_date',
        'document_type',
        'document_number',
        'control_number',
        'affected_document_number',
        'reference_number',
        'partner_name',
        'partner_tax_id',
        'related_id',
        'related_type',
        'currency',
        'exchange_rate',
        'taxable_amount',
        'exempt_amount',
        'exonerated_amount',
        'non_subject_amount',
        'tax_amount',
        'tax_rate',
        'total_amount',
        'withholding_amount',
        'metadata',
        'fiscal_hash',
        'previous_hash',
        'locked_at',
        'is_exported',
    ];

    protected $casts = [
        'document_date' => 'date',
        'exchange_rate' => 'float',
        'taxable_amount' => 'float',
        'exempt_amount' => 'float',
        'exonerated_amount' => 'float',
        'non_subject_amount' => 'float',
        'tax_amount' => 'float',
        'tax_rate' => 'float',
        'total_amount' => 'float',
        'withholding_amount' => 'float',
        'metadata' => 'array',
        'locked_at' => 'datetime',
        'is_exported' => 'boolean',
    ];

    public function related()
    {
        return $this->morphTo();
    }
}
