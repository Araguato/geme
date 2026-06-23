<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DebitNoteItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'debit_note_id',
        'order_item_id',
        'name',
        'quantity',
        'unit_price',
        'total',
    ];

    public function debitNote()
    {
        return $this->belongsTo(DebitNote::class);
    }

    public function orderItem()
    {
        return $this->belongsTo(OrderItem::class);
    }
}
