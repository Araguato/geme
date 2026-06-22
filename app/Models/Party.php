<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Supplier;
use App\Models\Employee;

class Party extends Model
{
    use HasFactory;

    protected $fillable = [
        'type',
        'name',
        'document_type',
        'document_number',
        'phone',
        'email',
        'address',
        'notes',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function supplier()
    {
        return $this->hasOne(Supplier::class);
    }

    public function employee()
    {
        return $this->hasOne(Employee::class);
    }
}
