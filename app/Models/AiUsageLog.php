<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AiUsageLog extends Model
{
    protected $fillable = [
        'user_id', 'mode', 'provider_host', 'model', 'purpose', 'data_level',
        'input_tokens', 'output_tokens', 'status', 'error', 'duration_ms',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
