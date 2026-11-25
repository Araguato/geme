<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Unit extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'category',
        'is_active',
    ];

    /** Texto que se guarda en renglones y productos (convención heredada: unidad, kg, g, l, ml). */
    public function getLegacyValueAttribute(): string
    {
        return strtoupper((string) $this->code) === 'UND' ? 'unidad' : strtolower((string) $this->code);
    }
}
