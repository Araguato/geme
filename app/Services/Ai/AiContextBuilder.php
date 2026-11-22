<?php

namespace App\Services\Ai;

use App\Models\ExchangeRate;
use App\Models\Product;
use App\Services\SalesReportService;

/**
 * Construye el conjunto de datos que se envía a la IA. Es la ÚNICA fuente de datos de
 * negocio para la IA: lo que no está aquí, la IA no lo recibe.
 *
 * Nunca incluye datos personales de clientes (nombre, RIF, cédula, teléfono, correo).
 * Los vendedores van seudonimizados ("Vendedor 1", "Vendedor 2"…).
 *
 *  - aggregate: totales del mes y del anterior, ventas diarias, categorías, tasa BCV, stock.
 *  - detailed:  además, productos más vendidos y ventas por vendedor (seudónimo).
 */
class AiContextBuilder
{
    public static function build(string $level = 'aggregate'): array
    {
        $now = now();
        $month = SalesReportService::forRange($now->copy()->startOfMonth(), $now);
        $prev = SalesReportService::forRange(
            $now->copy()->subMonthNoOverflow()->startOfMonth(),
            $now->copy()->subMonthNoOverflow()->endOfMonth()
        );
        $last30 = SalesReportService::forRange($now->copy()->subDays(29)->startOfDay(), $now);

        $totals = fn (array $t) => [
            'venta_neta' => round($t['net'], 2),
            'iva' => round($t['tax'], 2),
            'total' => round($t['total'], 2),
            'tickets' => $t['tickets'],
            'ticket_medio' => $t['tickets'] ? round($t['total'] / $t['tickets'], 2) : 0,
            'margen_bruto' => round($t['margin'], 2),
            'margen_pct' => $t['margin_pct'],
            'pct_venta_con_costo' => $t['cost_coverage'],
        ];

        $data = [
            'nivel_de_datos' => $level,
            'moneda' => 'USD',
            'fecha' => $now->toDateString(),
            'mes_en_curso' => ['desde' => $now->copy()->startOfMonth()->toDateString(), 'hasta' => $now->toDateString()] + $totals($month->totals()),
            'mes_anterior' => ['mes' => $now->copy()->subMonthNoOverflow()->format('Y-m')] + $totals($prev->totals()),
            'ventas_diarias_30_dias' => $last30->dailyTotals(),
            'categorias_mes' => $month->rows('category', 10)->map(fn ($r) => [
                'categoria' => $r['label'],
                'venta_neta' => round($r['net'], 2),
                'pct_de_la_venta' => $r['share'],
                'margen_pct' => $r['margin_pct'],
            ])->values()->all(),
            'tasa_bcv_bs_por_usd' => ExchangeRate::where('rate_date', '>=', $now->copy()->subDays(29)->toDateString())
                ->orderBy('rate_date')
                ->pluck('bs_per_usd', 'rate_date')
                ->map(fn ($v) => round((float) $v, 4))
                ->all(),
            'inventario' => [
                'productos_activos' => Product::where('is_active', true)->count(),
                'bajo_minimo' => Product::where('is_active', true)->where('reorder_point', '>', 0)
                    ->whereColumn('stock_quantity', '<=', 'reorder_point')->count(),
                'sin_existencia' => Product::where('is_active', true)->where('is_stock_tracked', true)
                    ->where('stock_quantity', '<=', 0)->count(),
            ],
        ];

        if ($level === 'detailed') {
            $data['productos_mas_vendidos_mes'] = $month->rows('product', 15)->map(fn ($r) => [
                'producto' => $r['label'],
                'cantidad' => round($r['quantity'], 2),
                'venta_neta' => round($r['net'], 2),
                'margen_pct' => $r['margin_pct'],
            ])->values()->all();

            $i = 0;
            $data['vendedores_mes'] = $month->rows('seller')->map(function ($r) use (&$i) {
                $i++;

                return [
                    'vendedor' => 'Vendedor ' . $i,
                    'venta_neta' => round($r['net'], 2),
                    'tickets' => $r['tickets'],
                    'pct_de_la_venta' => $r['share'],
                ];
            })->values()->all();
        }

        return $data;
    }
}
