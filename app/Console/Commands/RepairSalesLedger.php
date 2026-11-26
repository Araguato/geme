<?php

namespace App\Console\Commands;

use App\Models\FiscalLedger;
use App\Models\Order;
use App\Services\FiscalIntegrityService;
use App\Services\FiscalLedgerService;
use Illuminate\Console\Command;

/**
 * Recalcula base imponible, exento, IVA y total de los asientos de venta guardados antes de
 * corregir FiscalLedgerService::saleAmounts (la base imponible quedaba en 0). No toca tasa,
 * moneda, números ni fechas. Por defecto solo muestra lo que cambiaría; con --apply lo guarda
 * y recalcula la cadena de integridad.
 */
class RepairSalesLedger extends Command
{
    protected $signature = 'fiscal:repair-sales-ledger {--apply : Guardar los cambios (sin esto solo se muestran)}';

    protected $description = 'Corrige los importes (base, exento, IVA, total) de los asientos del libro de ventas';

    public function handle(): int
    {
        $apply = (bool) $this->option('apply');
        $changed = 0;
        $firstId = null;

        FiscalLedger::where('entry_type', 'salida')
            ->where('related_type', Order::class)
            ->where('document_type', '!=', 'NOTA_CREDITO')
            ->orderBy('id')
            ->each(function (FiscalLedger $entry) use ($apply, &$changed, &$firstId) {
                $order = Order::with('items')->find($entry->related_id);
                if (!$order || $order->items->isEmpty()) {
                    return;
                }

                $new = FiscalLedgerService::saleAmounts($order);
                $diff = [];
                foreach ($new as $field => $value) {
                    if (abs((float) $entry->{$field} - $value) > 0.004) {
                        $diff[$field] = [(float) $entry->{$field}, $value];
                    }
                }
                if (!$diff) {
                    return;
                }

                $changed++;
                $this->line(sprintf('#%d %s: %s', $entry->id, $entry->document_number,
                    collect($diff)->map(fn ($d, $f) => "{$f} {$d[0]} → {$d[1]}")->implode(', ')));

                if ($apply) {
                    $entry->forceFill($new)->save();
                    $firstId ??= $entry->id;
                }
            });

        if ($apply && $firstId) {
            FiscalIntegrityService::recalculateLedgerChainFrom($firstId);
        }

        $this->info($apply
            ? "Asientos corregidos: {$changed}"
            : "Asientos que cambiarían: {$changed}. Ejecuta con --apply para guardarlos.");

        return self::SUCCESS;
    }
}
