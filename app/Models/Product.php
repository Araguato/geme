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
        'purchase_unit',
        'purchase_unit_factor',
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
        'parent_product_id',
        'variant_attributes',
    ];

    protected $casts = [
        'variant_attributes' => 'array',
        'is_active' => 'boolean',
        'is_stock_tracked' => 'boolean',
        'is_prepared' => 'boolean',
        'is_raw_material' => 'boolean',
        'is_tax_inclusive' => 'boolean',
        'is_service' => 'boolean',
        'is_price_change_allowed' => 'boolean',
    ];

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function images()
    {
        return $this->hasMany(ProductImage::class)->orderBy('sort_order')->orderBy('id');
    }

    public function mainImage()
    {
        return $this->hasOne(ProductImage::class)->where('is_main', true);
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

    public function parent()
    {
        return $this->belongsTo(Product::class, 'parent_product_id');
    }

    public function variants()
    {
        return $this->hasMany(Product::class, 'parent_product_id');
    }

    public function stockItems()
    {
        return $this->hasMany(StockItem::class);
    }

    public function variantAttributes()
    {
        return $this->hasMany(ProductVariantAttribute::class);
    }

    /**
     * Búsqueda por nombre, SKU o código de barras. Cada palabra debe aparecer en alguno
     * de los tres (así «salsa tomate» encuentra «Salsa de tomate»), sin importar el orden.
     */
    public function scopeSearchTerm($query, ?string $term)
    {
        $words = preg_split('/\s+/', trim((string) $term), -1, PREG_SPLIT_NO_EMPTY) ?: [];

        foreach ($words as $word) {
            $like = '%' . addcslashes($word, '%_\\') . '%';
            $query->where(function ($q) use ($like) {
                $q->where('name', 'like', $like)
                    ->orWhere('sku', 'like', $like)
                    ->orWhereHas('barcodes', fn ($qb) => $qb->where('barcode', 'like', $like));
            });
        }

        return $query;
    }

    public function isVariant(): bool
    {
        return !is_null($this->parent_product_id);
    }

    public function isParent(): bool
    {
        return $this->variants()->exists();
    }
}
