<?php

namespace App\Services;

use App\Models\Party;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Estadísticas y segmentos de clientes para Marketing.
 *
 * Solo cuenta ventas completadas con cliente asignado (orders.customer_party_id).
 * Los segmentos se calculan al vuelo; no se guardan.
 */
class CustomerInsightsService
{
    public const FREQUENT_MIN_ORDERS_90D = 4;
    public const NEW_DAYS = 30;
    public const AT_RISK_DAYS = 45;
    public const INACTIVE_DAYS = 90;
    /** Mejores clientes: el 20 % que más compró en 12 meses, como máximo 20. */
    public const VIP_SHARE = 0.2;
    public const VIP_MAX = 20;

    public const SEGMENTS = [
        'vip' => ['label' => 'Mejores clientes', 'icon' => 'bi-gem', 'help' => 'El 20 % que más compró en los últimos 12 meses (máx. 20).'],
        'frecuentes' => ['label' => 'Frecuentes', 'icon' => 'bi-arrow-repeat', 'help' => '4 o más compras en los últimos 90 días.'],
        'nuevos' => ['label' => 'Nuevos', 'icon' => 'bi-person-plus', 'help' => 'Primera compra en los últimos 30 días.'],
        'en_riesgo' => ['label' => 'En riesgo', 'icon' => 'bi-hourglass-split', 'help' => 'Compraban, pero llevan entre 45 y 90 días sin volver.'],
        'inactivos' => ['label' => 'Inactivos', 'icon' => 'bi-moon', 'help' => 'Más de 90 días sin comprar.'],
        'sin_compras' => ['label' => 'Sin compras', 'icon' => 'bi-person-dash', 'help' => 'Registrados, pero aún no han comprado.'],
    ];

    protected ?Collection $stats = null;

    /**
     * Una fila por cliente (tipo customer) con sus métricas y segmentos.
     *
     * @return Collection<int, array>
     */
    public function customers(): Collection
    {
        if ($this->stats !== null) {
            return $this->stats;
        }

        $now = now();
        $d90 = $now->copy()->subDays(90);
        $d365 = $now->copy()->subDays(365);

        $agg = DB::table('orders')
            ->where('status', 'completed')
            ->whereNotNull('customer_party_id')
            ->groupBy('customer_party_id')
            ->selectRaw('customer_party_id as id, COUNT(*) as orders, SUM(subtotal) as net, SUM(total) as total,
                MIN(created_at) as first_at, MAX(created_at) as last_at,
                SUM(CASE WHEN created_at >= ? THEN 1 ELSE 0 END) as orders_90,
                SUM(CASE WHEN created_at >= ? THEN subtotal ELSE 0 END) as net_365', [$d90, $d365])
            ->get()
            ->keyBy('id');

        $favorite = $this->favoriteCategories();

        $rows = Party::customers()
            ->get(['id', 'name', 'document_type', 'document_number', 'phone', 'email', 'is_active', 'marketing_opt_in', 'marketing_opt_in_at', 'marketing_opt_in_source', 'created_at'])
            ->map(function (Party $p) use ($agg, $favorite, $now) {
                $a = $agg->get($p->id);
                $last = $a ? Carbon::parse($a->last_at) : null;
                $first = $a ? Carbon::parse($a->first_at) : null;

                return [
                    'id' => $p->id,
                    'name' => $p->name,
                    'document' => trim(($p->document_type ?? '') . ' ' . ($p->document_number ?? '')),
                    'phone' => $p->phone,
                    'email' => $p->email,
                    'is_active' => $p->is_active,
                    'opt_in' => (bool) $p->marketing_opt_in,
                    'opt_in_at' => $p->marketing_opt_in_at,
                    'opt_in_source' => $p->marketing_opt_in_source,
                    'orders' => (int) ($a->orders ?? 0),
                    'orders_90' => (int) ($a->orders_90 ?? 0),
                    'net' => round((float) ($a->net ?? 0), 2),
                    'net_365' => round((float) ($a->net_365 ?? 0), 2),
                    'total' => round((float) ($a->total ?? 0), 2),
                    'avg_ticket' => $a && $a->orders ? round($a->total / $a->orders, 2) : 0.0,
                    'first_at' => $first,
                    'last_at' => $last,
                    'days_since' => $last ? (int) $last->copy()->startOfDay()->diffInDays($now->copy()->startOfDay()) : null,
                    'favorite_category' => $favorite[$p->id]['name'] ?? null,
                    'favorite_category_id' => $favorite[$p->id]['id'] ?? null,
                    'segments' => [],
                ];
            });

        $buyers365 = $rows->filter(fn ($r) => $r['net_365'] > 0);
        $vipCount = min(self::VIP_MAX, max(1, (int) ceil($buyers365->count() * self::VIP_SHARE)));
        $vipIds = $buyers365->sortByDesc('net_365')->take($vipCount)->pluck('id')->flip();

        $this->stats = $rows->map(function ($r) use ($vipIds) {
            $s = [];
            if ($r['orders'] === 0) {
                $s[] = 'sin_compras';
            } else {
                if (isset($vipIds[$r['id']])) {
                    $s[] = 'vip';
                }
                if ($r['orders_90'] >= self::FREQUENT_MIN_ORDERS_90D) {
                    $s[] = 'frecuentes';
                }
                if ($r['first_at'] && $r['first_at']->gte(now()->subDays(self::NEW_DAYS))) {
                    $s[] = 'nuevos';
                }
                if ($r['days_since'] > self::INACTIVE_DAYS) {
                    $s[] = 'inactivos';
                } elseif ($r['days_since'] > self::AT_RISK_DAYS) {
                    $s[] = 'en_riesgo';
                }
            }
            $r['segments'] = $s;

            return $r;
        })->sortByDesc('net_365')->values();

        return $this->stats;
    }

    /** Número de clientes por segmento. */
    public function segmentCounts(): array
    {
        $counts = array_fill_keys(array_keys(self::SEGMENTS), 0);
        foreach ($this->customers() as $c) {
            foreach ($c['segments'] as $s) {
                $counts[$s]++;
            }
        }

        return $counts;
    }

    /** Clientes que compraron una categoría en los últimos N días. */
    public function customerIdsForCategory(int $categoryId, int $days = 90): array
    {
        return DB::table('orders as o')
            ->join('order_items as oi', 'oi.order_id', '=', 'o.id')
            ->join('products as p', 'p.id', '=', 'oi.product_id')
            ->where('o.status', 'completed')
            ->whereNotNull('o.customer_party_id')
            ->where('o.created_at', '>=', now()->subDays($days))
            ->where('p.category_id', $categoryId)
            ->distinct()
            ->pluck('o.customer_party_id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }

    /** Qué parte de la venta (90 días) tiene cliente asignado: mide la calidad de los datos. */
    public function identifiedShare(int $days = 90): array
    {
        $row = DB::table('orders')
            ->where('status', 'completed')
            ->where('created_at', '>=', now()->subDays($days))
            ->selectRaw('COUNT(*) as orders, SUM(CASE WHEN customer_party_id IS NOT NULL THEN 1 ELSE 0 END) as identified,
                SUM(subtotal) as net, SUM(CASE WHEN customer_party_id IS NOT NULL THEN subtotal ELSE 0 END) as identified_net')
            ->first();

        $orders = (int) ($row->orders ?? 0);

        return [
            'orders' => $orders,
            'identified' => (int) ($row->identified ?? 0),
            'pct_orders' => $orders ? round($row->identified / $orders * 100, 1) : 0.0,
            'pct_net' => ($row->net ?? 0) > 0 ? round($row->identified_net / $row->net * 100, 1) : 0.0,
        ];
    }

    /** [party_id => ['id' => categoría, 'name' => nombre]] por venta neta en 12 meses. */
    protected function favoriteCategories(): array
    {
        $rows = DB::table('orders as o')
            ->join('order_items as oi', 'oi.order_id', '=', 'o.id')
            ->join('products as p', 'p.id', '=', 'oi.product_id')
            ->join('categories as c', 'c.id', '=', 'p.category_id')
            ->where('o.status', 'completed')
            ->whereNotNull('o.customer_party_id')
            ->where('o.created_at', '>=', now()->subDays(365))
            ->groupBy('o.customer_party_id', 'c.id', 'c.name')
            ->selectRaw('o.customer_party_id as party_id, c.id as category_id, c.name as category, SUM(oi.total - oi.tax_amount) as net')
            ->get();

        $best = [];
        foreach ($rows as $r) {
            if (!isset($best[$r->party_id]) || $r->net > $best[$r->party_id]['net']) {
                $best[$r->party_id] = ['id' => (int) $r->category_id, 'name' => $r->category, 'net' => (float) $r->net];
            }
        }

        return $best;
    }

    /** Teléfono en formato internacional para WhatsApp (Venezuela por defecto: 0414… → 58414…). */
    public static function whatsappNumber(?string $phone, string $countryCode = '58'): ?string
    {
        $digits = preg_replace('/\D+/', '', (string) $phone);
        if ($digits === '' || strlen($digits) < 7) {
            return null;
        }
        if (str_starts_with($digits, '00')) {
            $digits = substr($digits, 2);
        } elseif (str_starts_with($digits, '0')) {
            $digits = $countryCode . substr($digits, 1);
        } elseif (strlen($digits) <= 10 && !str_starts_with($digits, $countryCode)) {
            $digits = $countryCode . $digits;
        }

        return $digits;
    }
}
