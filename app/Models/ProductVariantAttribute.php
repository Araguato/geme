<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductVariantAttribute extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'attribute_name',
        'values',
    ];

    protected $casts = [
        'values' => 'array',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
