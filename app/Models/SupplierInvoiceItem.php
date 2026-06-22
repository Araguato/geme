<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SupplierInvoiceItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'supplier_invoice_id',
        'product_id',
        'description',
        'unit',
        'quantity',
        'unit_cost',
        'tax_rate',
        'tax_amount',
        'subtotal',
        'total',
        'metadata',
    ];

    protected $casts = [
        'quantity' => 'float',
        'unit_cost' => 'float',
        'tax_rate' => 'float',
        'tax_amount' => 'float',
        'subtotal' => 'float',
        'total' => 'float',
        'metadata' => 'array',
    ];

    public function invoice()
    {
        return $this->belongsTo(SupplierInvoice::class, 'supplier_invoice_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
