<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SupplierInvoice extends Model
{
    use HasFactory;

    protected $fillable = [
        'supplier_id',
        'invoice_number',
        'control_number',
        'date',
        'due_date',
        'bcv_rate_at_issue',
        'currency_rate_source',
        'amount_usd',
        'amount_bs',
        'total_items',
        'total_tax',
        'total_amount',
        'currency',
        'status',
        'notes',
        'doc_type',
        'affected_document',
        'taxable_amount',
        'exempt_amount',
        'withholding_amount',
    ];

    protected $casts = [
        'date' => 'date',
        'due_date' => 'date',
        'bcv_rate_at_issue' => 'float',
        'amount_usd' => 'float',
        'amount_bs' => 'float',
        'total_items' => 'float',
        'total_tax' => 'float',
        'total_amount' => 'float',
        'taxable_amount' => 'float',
        'exempt_amount' => 'float',
        'withholding_amount' => 'float',
    ];

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function items()
    {
        return $this->hasMany(SupplierInvoiceItem::class);
    }

    public function payments()
    {
        return $this->hasMany(SupplierPayment::class);
    }

    public function getPaidUsdAttribute(): float
    {
        return $this->payments->sum(function (SupplierPayment $payment) {
            $usd = (float) ($payment->amount_usd ?? 0);
            $bs = (float) ($payment->amount_bs ?? 0);

            if ($bs > 0 && $payment->bcv_rate_at_payment > 0) {
                $usd += $bs / $payment->bcv_rate_at_payment;
            }

            return $usd;
        });
    }

    public function getRemainingUsdAttribute(): float
    {
        return max(0, (float) $this->amount_usd - $this->paid_usd);
    }

    public function recalculateTotals(): void
    {
        $items = $this->relationLoaded('items') ? $this->items : $this->items()->get();

        $this->total_items = (float) $items->sum('subtotal');
        $this->total_tax = (float) $items->sum('tax_amount');
        $this->total_amount = (float) $items->sum('total');

        $taxable = 0.0;
        $exempt = 0.0;

        foreach ($items as $item) {
            $rate = (float) ($item->tax_rate ?? 0);
            $subtotal = (float) ($item->subtotal ?? 0);

            if ($rate > 0) {
                $taxable += $subtotal;
            } else {
                $exempt += $subtotal;
            }
        }

        $this->taxable_amount = $taxable;
        $this->exempt_amount = $exempt;

        if ((float) $this->amount_usd <= 0 && $this->currency === 'USD') {
            $this->amount_usd = $this->total_amount;
        }

        if ((float) $this->amount_bs <= 0 && $this->currency === 'VES' && $this->bcv_rate_at_issue > 0) {
            $this->amount_bs = $this->total_amount * $this->bcv_rate_at_issue;
        }
    }
}
