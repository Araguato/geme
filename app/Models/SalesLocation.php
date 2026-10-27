<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SalesLocation extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'address',
        'phone',
        'warehouse_id',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Depósito del que vende el TPV cuando la caja está abierta en este negocio.
     */
    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function cashShifts()
    {
        return $this->hasMany(CashShift::class);
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }
}
