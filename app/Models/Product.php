<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'category_id',
        'name',
        'sku',
        'description',
        'description_zh',
        'price',
        'is_active',
        'warehouse_id',
        'location_id',
        'aisle',
        'shelf',
        'rack',
        'bin',
        'section',
        'image_path',
        'is_stock_tracked',
        'is_prepared',
        'is_raw_material',
        'stock_unit_id',
        'base_unit_id',
        'default_unit',
        'cost',
        'markup_percent',
        'tax_rate',
        'is_tax_inclusive',
        'stock_quantity',
        'reorder_point',
        'preferred_quantity',
        'warning_quantity',
        'measurement_unit',
        'supplier_name',
        'is_service',
        'is_price_change_allowed',
    ];

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function location()
    {
        return $this->belongsTo(Location::class);
    }

    public function barcodes()
    {
        return $this->hasMany(ProductBarcode::class);
    }

    public function stockUnit()
    {
        return $this->belongsTo(Unit::class, 'stock_unit_id');
    }

    public function baseUnit()
    {
        return $this->belongsTo(Unit::class, 'base_unit_id');
    }
}
