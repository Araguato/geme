<?php

namespace App\Listeners;

use App\Models\LoginLog;
use Illuminate\Auth\Events\Failed;

class LogFailedLogin
{
    public function handle(Failed $event): void
    {
        LoginLog::create([
            'user_id' => $event->user?->id,
            'email' => $event->credentials['email'] ?? null,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'status' => 'failed',
            'details' => 'Credenciales incorrectas',
            'created_at' => now(),
        ]);
    }
}
