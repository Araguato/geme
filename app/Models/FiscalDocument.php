<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FiscalDocument extends Model
{
    public const STATUSES = ['pending', 'issued', 'error', 'contingency', 'cancelled'];

    protected $fillable = [
        'order_id', 'document_type', 'status', 'provider', 'environment', 'attempts',
        'next_attempt_at', 'last_error', 'payload', 'invoice_number', 'control_number',
        'provider_reference', 'exchange_rate', 'issued_at', 'resolved_by', 'reported_at', 'reported_by',
    ];

    protected $casts = [
        'payload' => 'array',
        'next_attempt_at' => 'datetime',
        'issued_at' => 'datetime',
        'reported_at' => 'datetime',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function resolver()
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }

    public function isDone(): bool
    {
        return in_array($this->status, ['issued', 'contingency', 'cancelled'], true);
    }

    /** Factura de talonario aún sin remitir al SENIAT o a la imprenta. */
    public function needsReport(): bool
    {
        return $this->status === 'contingency' && $this->reported_at === null;
    }

    /** Se remite al día siguiente: vence al terminar ese día. */
    public function reportDueAt(): ?\Illuminate\Support\Carbon
    {
        return $this->issued_at?->copy()->addDay()->endOfDay();
    }

    public function isReportOverdue(): bool
    {
        return $this->needsReport() && $this->reportDueAt() !== null && now()->gt($this->reportDueAt());
    }

    public static function statusLabel(string $status): string
    {
        return [
            'pending' => 'Pendiente',
            'issued' => 'Emitida',
            'error' => 'Con error',
            'contingency' => 'Contingencia (talonario)',
            'cancelled' => 'Anulada',
        ][$status] ?? $status;
    }
}
