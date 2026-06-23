<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StockMovement extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'warehouse_id',
        'to_warehouse_id',
        'location_id',
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
        'aisle',
        'shelf',
        'rack',
        'bin',
        'section',
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

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function toWarehouse()
    {
        return $this->belongsTo(Warehouse::class, 'to_warehouse_id');
    }

    public function location()
    {
        return $this->belongsTo(Location::class);
    }
}
