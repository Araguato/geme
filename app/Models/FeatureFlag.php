<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FeatureFlag extends Model
{
    use HasFactory;

    protected $fillable = [
        'key',
        'value',
        'description',
        'is_enabled',
    ];

    protected $casts = [
        'is_enabled' => 'boolean',
    ];

    public static function isOn(string $key, bool $default = false): bool
    {
        $flag = static::where('key', $key)->first();

        return $flag?->is_enabled ?? $default;
    }

    public static function value(string $key, mixed $default = null): mixed
    {
        $flag = static::where('key', $key)->first();

        return $flag?->value ?? $default;
    }
}
