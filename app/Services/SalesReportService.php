<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Ventas agrupadas por vendedor, categoría o producto.
 *
 * Solo cuenta órdenes completadas. El costo de cada línea sale del kardex (movimientos de
 * salida que el TPV registra con reference_type = 'order'); si la línea no movió stock
 * (producto sin control de stock o preparado con receta) se usa el costo actual del producto.
 * Las notas de crédito (devoluciones) no se descuentan.
 */
class SalesReportService
{
    public const GROUPS = ['seller', 'category', 'product'];

    public function __construct(
        protected Carbon $from,
        protected Carbon $to,
        protected array $filters = []
    ) {
        $this->from = $from->copy()->startOfDay();
        $this->to = $to->copy()->endOfDay();
    }

    public static function forRange(string|Carbon|null $from, string|Carbon|null $to, array $filters = []): self
    {
        $from = $from ? Carbon::parse($from) : now()->startOfMonth();
        $to = $to ? Carbon::parse($to) : now();
        if ($from->gt($to)) {
            [$from, $to] = [$to, $from];
        }

        return new self($from, $to, $filters);
    }

    public function from(): Carbon
    {
        return $this->from;
    }

    public function to(): Carbon
    {
        return $this->to;
    }

    /** Costo de una línea vendida (expresión SQL sobre oi / p). */
    protected function itemCostSql(): string
    {
        return "COALESCE(
            (SELECT -SUM(sm.total_cost) FROM stock_movements sm
              WHERE sm.reference_type = 'order' AND sm.reference_id = oi.order_id AND sm.product_id = oi.product_id)
            / (SELECT COUNT(*) FROM order_items oi2 WHERE oi2.order_id = oi.order_id AND oi2.product_id = oi.product_id),
            oi.quantity * COALESCE(p.cost, 0)
        )";
    }

    protected function ordersQuery()
    {
        $q = DB::table('orders as o')
            ->where('o.status', 'completed')
            ->whereBetween('o.created_at', [$this->from, $this->to]);

        if (!empty($this->filters['user_id'])) {
            $q->where('o.user_id', $this->filters['user_id']);
        }
        if (!empty($this->filters['sales_location_id'])) {
            $q->where('o.sales_location_id', $this->filters['sales_location_id']);
        }

        return $q;
    }

    protected function itemsQuery()
    {
        $q = $this->ordersQuery()
            ->join('order_items as oi', 'oi.order_id', '=', 'o.id')
            ->leftJoin('products as p', 'p.id', '=', 'oi.product_id');

        if (!empty($this->filters['category_id'])) {
            $q->where('p.category_id', $this->filters['category_id']);
        }

        return $q;
    }

    /** Totales del período (para tarjetas KPI). */
    public function totals(): array
    {
        if (!empty($this->filters['category_id'])) {
            // Con filtro de categoría solo valen las líneas de esa categoría
            $row = $this->itemsQuery()->selectRaw(
                'COUNT(DISTINCT o.id) as tickets, SUM(oi.total - oi.tax_amount) as net, SUM(oi.tax_amount) as tax, SUM(oi.total) as total, '
                . 'SUM(' . $this->itemCostSql() . ') as cost, ' . $this->uncostedSql() . ' as uncosted'
            )->first();
        } else {
            $row = $this->ordersQuery()->selectRaw(
                'COUNT(*) as tickets, SUM(o.subtotal) as net, SUM(o.tax) as tax, SUM(o.total) as total'
            )->first();
            $items = $this->itemsQuery()->selectRaw('SUM(' . $this->itemCostSql() . ') as cost, ' . $this->uncostedSql() . ' as uncosted')->first();
            $row->cost = $items->cost ?? 0;
            $row->uncosted = $items->uncosted ?? 0;
        }

        return $this->withMargin([
            'tickets' => (int) ($row->tickets ?? 0),
            'net' => (float) ($row->net ?? 0),
            'tax' => (float) ($row->tax ?? 0),
            'total' => (float) ($row->total ?? 0),
            'cost' => (float) ($row->cost ?? 0),
            'uncosted' => (float) ($row->uncosted ?? 0),
        ]);
    }

    /** Filas agrupadas, ordenadas por venta neta descendente. */
    public function rows(string $group, ?int $limit = null): Collection
    {
        $cost = $this->itemCostSql();

        $q = $this->itemsQuery();

        switch ($group) {
            case 'category':
                $q->leftJoin('categories as c', 'c.id', '=', 'p.category_id')
                    ->groupBy('p.category_id', 'c.name')
                    ->selectRaw("p.category_id as id, COALESCE(c.name, 'Sin categoría') as label");
                break;
            case 'product':
                $q->groupBy('oi.product_id', 'oi.name')
                    ->selectRaw('oi.product_id as id, oi.name as label');
                break;
            default:
                $group = 'seller';
                $q->leftJoin('users as u', 'u.id', '=', 'o.user_id')
                    ->groupBy('o.user_id', 'u.name')
                    ->selectRaw("o.user_id as id, COALESCE(u.name, 'Sin vendedor') as label");
        }

        $q->selectRaw("COUNT(DISTINCT o.id) as tickets, SUM(oi.quantity) as quantity,
                SUM(oi.total - oi.tax_amount) as net, SUM(oi.tax_amount) as tax, SUM(oi.total) as total,
                SUM($cost) as cost, {$this->uncostedSql()} as uncosted")
            ->orderByDesc('net');

        if ($limit) {
            $q->limit($limit);
        }

        $rows = $q->get()->map(fn ($r) => $this->withMargin([
            'id' => $r->id,
            'label' => $r->label,
            'tickets' => (int) $r->tickets,
            'quantity' => (float) $r->quantity,
            'net' => (float) $r->net,
            'tax' => (float) $r->tax,
            'total' => (float) $r->total,
            'cost' => (float) $r->cost,
            'uncosted' => (float) $r->uncosted,
        ]));

        $netTotal = $rows->sum('net');

        return $rows->map(function ($r) use ($netTotal) {
            $r['share'] = $netTotal > 0 ? round($r['net'] / $netTotal * 100, 1) : 0.0;
            $r['avg_ticket'] = $r['tickets'] > 0 ? round($r['total'] / $r['tickets'], 2) : 0.0;

            return $r;
        });
    }

    /** Venta total por día (para gráficos). */
    public function dailyTotals(): array
    {
        return $this->ordersQuery()
            ->selectRaw('DATE(o.created_at) as d, SUM(o.total) as total')
            ->groupBy('d')
            ->orderBy('d')
            ->pluck('total', 'd')
            ->map(fn ($v) => round((float) $v, 2))
            ->toArray();
    }

    /** Venta neta de líneas sin costo conocido (ni en kardex ni en la ficha del producto). */
    protected function uncostedSql(): string
    {
        return 'SUM(CASE WHEN (' . $this->itemCostSql() . ') <= 0 THEN oi.total - oi.tax_amount ELSE 0 END)';
    }

    /**
     * Margen calculado solo sobre la venta que tiene costo: una línea sin costo no es
     * "100 % de ganancia", es un dato que falta. cost_coverage indica qué parte de la
     * venta tiene costo (100 = completo); margin_pct es null si ninguna lo tiene.
     */
    protected function withMargin(array $r): array
    {
        $uncosted = min($r['uncosted'] ?? 0, $r['net']);
        $costedNet = $r['net'] - $uncosted;
        $r['uncosted'] = round($uncosted, 2);
        $r['cost_coverage'] = $r['net'] > 0 ? round($costedNet / $r['net'] * 100, 1) : 100.0;
        $r['margin'] = round($costedNet - $r['cost'], 2);
        $r['margin_pct'] = $costedNet > 0 ? round($r['margin'] / $costedNet * 100, 1) : null;

        return $r;
    }
}
