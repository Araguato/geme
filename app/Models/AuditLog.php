<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AuditLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'action',
        'entity_type',
        'entity_id',
        'description',
        'data_before',
        'data_after',
        'ip_address',
        'user_agent',
    ];

    protected $casts = [
        'data_before' => 'array',
        'data_after'  => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
