<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_number',
        'user_id',
        'customer_name',
        'customer_email',
        'customer_phone',
        'customer_party_id',
        'document_type',
        'external_invoice_number',
        'affected_document_number',
        'type',
        'status',
        'payment_status',
        'subtotal',
        'tax',
        'discount',
        'total',
        'exchange_rate',
        'notes',
        'delivery_info_id',
        'cash_shift_id',
        'sales_location_id',
        'voided_at',
        'voided_by',
        'void_reason',
    ];

    protected $casts = [
        'opened_at' => 'datetime',
        'closed_at' => 'datetime',
        'voided_at' => 'datetime',
    ];

    public function fiscalDocument()
    {
        return $this->hasOne(FiscalDocument::class)->where('document_type', 'FACTURA');
    }

    public function voider()
    {
        return $this->belongsTo(User::class, 'voided_by');
    }

    public function isVoided(): bool
    {
        return $this->status === 'cancelled' && $this->voided_at !== null;
    }

    public function customerParty()
    {
        return $this->belongsTo(Party::class, 'customer_party_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    public function deliveryInfo()
    {
        return $this->belongsTo(DeliveryInfo::class);
    }

    public function cashShift()
    {
        return $this->belongsTo(CashShift::class);
    }

    public function salesLocation()
    {
        return $this->belongsTo(SalesLocation::class);
    }

    public function creditNotes()
    {
        return $this->hasMany(CreditNote::class);
    }

    public function debitNotes()
    {
        return $this->hasMany(DebitNote::class);
    }
}
