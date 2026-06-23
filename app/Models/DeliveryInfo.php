<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DeliveryInfo extends Model
{
    use HasFactory;

    protected $table = 'delivery_infos';

    protected $fillable = [
        'order_id',
        'address',
        'city',
        'phone',
        'instructions',
        'status',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }
}
