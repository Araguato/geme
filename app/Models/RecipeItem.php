<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RecipeItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'recipe_id',
        'component_product_id',
        'quantity',
        'unit',
        'wastage_percent',
    ];

    public function recipe()
    {
        return $this->belongsTo(Recipe::class);
    }

    public function component()
    {
        return $this->belongsTo(Product::class, 'component_product_id');
    }
}
