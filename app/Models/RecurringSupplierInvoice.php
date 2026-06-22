<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RecurringSupplierInvoice extends Model
{
    use HasFactory;

    protected $fillable = [
        'supplier_id',
        'description',
        'base_amount_usd',
        'base_amount_bs',
        'currency',
        'interval',
        'day_of_month',
        'day_of_week',
        'next_due_date',
        'is_active',
    ];

    protected $casts = [
        'next_due_date' => 'date',
        'is_active' => 'bool',
        'base_amount_usd' => 'float',
        'base_amount_bs' => 'float',
    ];

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }
}
