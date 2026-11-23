<?php

namespace App\Services\Fiscal;

/** Resultado de pedir una emisión a un medio autorizado. */
class FiscalResult
{
    public function __construct(
        public bool $ok,
        public ?string $invoiceNumber = null,
        public ?string $controlNumber = null,
        public ?string $reference = null,
        public ?string $error = null,
        /** Si falló, ¿merece la pena reintentar (red, timeout) o es un rechazo definitivo (datos)? */
        public bool $retryable = true,
    ) {
    }

    public static function success(string $invoice, string $control, ?string $reference = null): self
    {
        return new self(true, $invoice, $control, $reference);
    }

    public static function failure(string $error, bool $retryable = true): self
    {
        return new self(false, error: $error, retryable: $retryable);
    }
}
