<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'cash_shift_id',
        'method',
        'amount',
        'reference',
        'cashea_initial_percentage',
        'cashea_financed_amount',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function cashShift()
    {
        return $this->belongsTo(CashShift::class);
    }
}
