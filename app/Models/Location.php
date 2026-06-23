<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Location extends Model
{
    use HasFactory;

    protected $fillable = [
        'warehouse_id',
        'code',
        'name',
        'aisle',
        'shelf',
        'rack',
        'bin',
        'section',
        'description',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    public function stockItems(): HasMany
    {
        return $this->hasMany(StockItem::class);
    }
}
