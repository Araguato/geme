<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'is_active',
        'prep_area',
        'image_path',
    ];

    public function products()
    {
        return $this->hasMany(Product::class);
    }
}
