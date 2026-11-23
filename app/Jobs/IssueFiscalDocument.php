<?php

namespace App\Jobs;

use App\Models\FiscalDocument;
use App\Services\Fiscal\FiscalEmissionService;
use Illuminate\Foundation\Bus\Dispatchable;

/** Se ejecuta tras enviar la respuesta al TPV (no hace falta un worker de colas). */
class IssueFiscalDocument
{
    use Dispatchable;

    public function __construct(public int $documentId)
    {
    }

    public function handle(): void
    {
        if ($doc = FiscalDocument::find($this->documentId)) {
            FiscalEmissionService::process($doc);
        }
    }
}
