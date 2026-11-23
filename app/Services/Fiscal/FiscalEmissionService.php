<?php

namespace App\Services\Fiscal;

use App\Jobs\IssueFiscalDocument;
use App\Models\AuditLog;
use App\Models\FiscalDocument;
use App\Models\Order;
use App\Services\FiscalLedgerService;
use Throwable;

/**
 * Cola de emisión: la venta NUNCA se bloquea por el medio fiscal. Si falla, el documento
 * queda pendiente/con error para reintentar o resolver por contingencia (talonario).
 */
class FiscalEmissionService
{
    public const MAX_ATTEMPTS = 8;

    /** Crea el documento pendiente (si hay medio configurado) y lo emite tras responder. */
    public static function enqueue(Order $order): ?FiscalDocument
    {
        if (!FiscalManager::enabled()) {
            return null;
        }

        try {
            $doc = FiscalDocument::firstOrCreate(
                ['order_id' => $order->id, 'document_type' => 'FACTURA'],
                [
                    'status' => 'pending',
                    'provider' => FiscalManager::key(),
                    'environment' => FiscalManager::environment(),
                    'exchange_rate' => $order->exchange_rate,
                    'payload' => FiscalPayloadBuilder::build($order),
                ]
            );

            if (!$doc->isDone()) {
                IssueFiscalDocument::dispatchAfterResponse($doc->id);
            }

            return $doc;
        } catch (Throwable $e) {
            report($e); // la venta ya está guardada: nunca se propaga

            return null;
        }
    }

    public static function process(FiscalDocument $doc): FiscalDocument
    {
        if ($doc->isDone()) {
            return $doc;
        }

        $provider = FiscalManager::provider($doc->provider);
        if (!$provider) {
            return self::fail($doc, 'El medio fiscal "' . $doc->provider . '" ya no está disponible.', false);
        }

        $doc->increment('attempts');

        try {
            $result = $provider->issue($doc->payload ?? []);
        } catch (Throwable $e) {
            $result = FiscalResult::failure('Error del proveedor: ' . $e->getMessage());
        }

        if (!$result->ok) {
            return self::fail($doc->refresh(), (string) $result->error, $result->retryable);
        }

        $doc->update([
            'status' => 'issued',
            'invoice_number' => $result->invoiceNumber,
            'control_number' => $result->controlNumber,
            'provider_reference' => $result->reference,
            'issued_at' => now(),
            'last_error' => null,
            'next_attempt_at' => null,
        ]);

        self::syncOrderAndLedger($doc);

        return $doc;
    }

    /** Factura de talonario en contingencia: se registra a mano su número. */
    public static function registerContingency(FiscalDocument $doc, string $invoice, string $control, ?int $userId): FiscalDocument
    {
        $doc->update([
            'status' => 'contingency',
            'invoice_number' => $invoice,
            'control_number' => $control,
            'issued_at' => now(),
            'last_error' => null,
            'next_attempt_at' => null,
            'resolved_by' => $userId,
        ]);

        self::syncOrderAndLedger($doc);
        AuditLog::create([
            'user_id' => $userId,
            'action' => 'fiscal_contingency',
            'entity_type' => FiscalDocument::class,
            'entity_id' => $doc->id,
            'description' => "Factura de talonario registrada ($invoice) para el pedido #{$doc->order_id}",
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);

        return $doc;
    }

    public static function retry(FiscalDocument $doc): FiscalDocument
    {
        if ($doc->isDone()) {
            return $doc;
        }
        $doc->update(['status' => 'pending', 'next_attempt_at' => null]);

        return self::process($doc->refresh());
    }

    /** Procesa los pendientes y los errores reintentables (para el planificador). */
    public static function processDue(int $limit = 50): int
    {
        $docs = FiscalDocument::whereIn('status', ['pending', 'error'])
            ->where('attempts', '<', self::MAX_ATTEMPTS)
            ->where(fn ($q) => $q->whereNull('next_attempt_at')->orWhere('next_attempt_at', '<=', now()))
            ->orderBy('id')->limit($limit)->get();

        foreach ($docs as $doc) {
            self::process($doc);
        }

        return $docs->count();
    }

    private static function fail(FiscalDocument $doc, string $error, bool $retryable): FiscalDocument
    {
        $giveUp = !$retryable || $doc->attempts >= self::MAX_ATTEMPTS;

        $doc->update([
            'status' => 'error',
            'last_error' => mb_substr($error, 0, 1000),
            // Espera creciente: 1, 2, 4… minutos (máx. 60). Sin fecha = requiere intervención.
            'next_attempt_at' => $giveUp ? null : now()->addMinutes(min(60, 2 ** max(0, $doc->attempts - 1))),
        ]);

        return $doc;
    }

    private static function syncOrderAndLedger(FiscalDocument $doc): void
    {
        $order = $doc->order;
        $order->update(['external_invoice_number' => $doc->invoice_number]);
        FiscalLedgerService::recordSale($order->refresh());
    }
}
