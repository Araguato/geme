<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StockMovement extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'unit_id',
        'movement_date',
        'type',
        'reason',
        'quantity',
        'unit',
        'unit_cost',
        'total_cost',
        'running_quantity',
        'running_average_cost',
        'reference_type',
        'reference_id',
        'performed_by',
    ];

    protected $casts = [
        'movement_date' => 'datetime',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function unit()
    {
        return $this->belongsTo(Unit::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'performed_by');
    }
}
