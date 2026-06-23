<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'product_id',
        'name',
        'quantity',
        'unit_price',
        'tax_rate',
        'tax_amount',
        'discount',
        'total',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function creditNoteItems()
    {
        return $this->hasMany(CreditNoteItem::class);
    }

    public function debitNoteItems()
    {
        return $this->hasMany(DebitNoteItem::class);
    }
}
