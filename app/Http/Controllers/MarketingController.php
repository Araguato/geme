<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Category;
use App\Models\Order;
use App\Models\Party;
use App\Services\CustomerInsightsService;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Marketing / CRM (admin y supervisor): segmentos de clientes, ficha de cliente y campañas.
 * Las campañas y exportaciones solo incluyen clientes con consentimiento de marketing.
 */
class MarketingController extends Controller
{
    protected function filters(Request $request): array
    {
        return $request->validate([
            'segmento' => 'nullable|in:' . implode(',', array_keys(CustomerInsightsService::SEGMENTS)),
            'category_id' => 'nullable|integer|exists:categories,id',
            'q' => 'nullable|string|max:100',
            'con_consentimiento' => 'nullable|boolean',
            'mensaje' => 'nullable|string|max:1000',
        ]);
    }

    protected function filtered(CustomerInsightsService $insights, array $f, bool $forceOptIn = false): Collection
    {
        $rows = $insights->customers();

        if (!empty($f['segmento'])) {
            $rows = $rows->filter(fn ($r) => in_array($f['segmento'], $r['segments'], true));
        }
        if (!empty($f['category_id'])) {
            $ids = array_flip($insights->customerIdsForCategory((int) $f['category_id']));
            $rows = $rows->filter(fn ($r) => isset($ids[$r['id']]));
        }
        if (!empty($f['q'])) {
            $q = mb_strtolower(trim($f['q']));
            $rows = $rows->filter(fn ($r) => str_contains(mb_strtolower($r['name'] . ' ' . $r['document'] . ' ' . $r['phone'] . ' ' . $r['email']), $q));
        }
        if ($forceOptIn || !empty($f['con_consentimiento'])) {
            $rows = $rows->filter(fn ($r) => $r['opt_in']);
        }

        return $rows->values();
    }

    public function index(Request $request, CustomerInsightsService $insights)
    {
        $f = $this->filters($request);
        $rows = $this->filtered($insights, $f);

        $page = LengthAwarePaginator::resolveCurrentPage();
        $perPage = 50;
        $paginated = new LengthAwarePaginator(
            $rows->forPage($page, $perPage)->values(), $rows->count(), $perPage, $page,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        $all = $insights->customers();

        return view('marketing.index', [
            'filters' => $f,
            'customers' => $paginated,
            'filteredCount' => $rows->count(),
            'filteredOptIn' => $rows->where('opt_in', true)->count(),
            'segments' => CustomerInsightsService::SEGMENTS,
            'segmentCounts' => $insights->segmentCounts(),
            'categories' => Category::orderBy('name')->get(['id', 'name']),
            'totals' => [
                'customers' => $all->count(),
                'buyers' => $all->where('orders', '>', 0)->count(),
                'opt_in' => $all->where('opt_in', true)->count(),
            ],
            'identified' => $insights->identifiedShare(),
            'message' => $f['mensaje'] ?? '',
        ]);
    }

    public function show(Party $party, CustomerInsightsService $insights)
    {
        abort_unless($party->type === 'customer', 404);

        $stats = $insights->customers()->firstWhere('id', $party->id);

        $orders = Order::where('customer_party_id', $party->id)
            ->with('user:id,name')
            ->withCount('items')
            ->orderByDesc('created_at')
            ->limit(30)
            ->get();

        $topProducts = DB::table('orders as o')
            ->join('order_items as oi', 'oi.order_id', '=', 'o.id')
            ->where('o.customer_party_id', $party->id)
            ->where('o.status', 'completed')
            ->groupBy('oi.product_id', 'oi.name')
            ->selectRaw('oi.name, SUM(oi.quantity) as qty, SUM(oi.total - oi.tax_amount) as net, COUNT(DISTINCT o.id) as times, MAX(o.created_at) as last_at')
            ->orderByDesc('net')
            ->limit(10)
            ->get();

        $monthly = DB::table('orders')
            ->where('customer_party_id', $party->id)
            ->where('status', 'completed')
            ->where('created_at', '>=', now()->subMonths(11)->startOfMonth())
            ->get(['created_at', 'subtotal'])
            ->groupBy(fn ($o) => substr((string) $o->created_at, 0, 7))
            ->map(fn ($g) => round($g->sum('subtotal'), 2));

        $months = [];
        for ($i = 11; $i >= 0; $i--) {
            $key = now()->subMonthsNoOverflow($i)->format('Y-m');
            $months[$key] = (float) ($monthly[$key] ?? 0);
        }

        return view('marketing.show', [
            'party' => $party,
            'stats' => $stats,
            'orders' => $orders,
            'topProducts' => $topProducts,
            'months' => $months,
            'segments' => CustomerInsightsService::SEGMENTS,
            'whatsapp' => CustomerInsightsService::whatsappNumber($party->phone),
        ]);
    }

    public function consent(Request $request, Party $party)
    {
        abort_unless($party->type === 'customer', 404);
        $data = $request->validate(['opt_in' => 'required|boolean']);

        $before = ['marketing_opt_in' => $party->marketing_opt_in];
        $party->setMarketingConsent((bool) $data['opt_in'], 'ficha');

        AuditLog::create([
            'user_id' => $request->user()->id,
            'action' => $data['opt_in'] ? 'marketing.consent.grant' : 'marketing.consent.revoke',
            'entity_type' => 'party',
            'entity_id' => $party->id,
            'description' => ($data['opt_in'] ? 'Registró' : 'Retiró') . ' el consentimiento de marketing del cliente',
            'data_before' => $before,
            'data_after' => ['marketing_opt_in' => (bool) $data['opt_in']],
            'ip_address' => $request->ip(),
            'user_agent' => substr((string) $request->userAgent(), 0, 1000),
        ]);

        return back()->with('success', $data['opt_in']
            ? 'Consentimiento registrado: el cliente podrá recibir promociones.'
            : 'Consentimiento retirado: el cliente ya no aparecerá en campañas ni exportaciones.');
    }

    public function export(Request $request, CustomerInsightsService $insights): StreamedResponse
    {
        $f = $this->filters($request);
        // Exportación: siempre solo clientes con consentimiento
        $rows = $this->filtered($insights, $f, true);

        AuditLog::create([
            'user_id' => $request->user()->id,
            'action' => 'marketing.export',
            'entity_type' => 'marketing',
            'description' => sprintf('Exportó %d clientes con consentimiento para campaña', $rows->count()),
            'data_after' => array_diff_key($f, ['mensaje' => true]) + ['count' => $rows->count()],
            'ip_address' => $request->ip(),
            'user_agent' => substr((string) $request->userAgent(), 0, 1000),
        ]);

        $segmentLabels = array_map(fn ($s) => $s['label'], CustomerInsightsService::SEGMENTS);
        $filename = 'clientes_campana_' . ($f['segmento'] ?? 'todos') . '_' . now()->format('Ymd') . '.csv';

        return response()->streamDownload(function () use ($rows, $segmentLabels) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, ['Nombre', 'Teléfono', 'WhatsApp', 'Correo', 'Segmentos', 'Compras', 'Última compra', 'Categoría favorita', 'Consentimiento desde'], ';');
            foreach ($rows as $r) {
                fputcsv($out, array_map([$this, 'safeCsv'], [
                    $r['name'],
                    $r['phone'],
                    CustomerInsightsService::whatsappNumber($r['phone']),
                    $r['email'],
                    implode(', ', array_map(fn ($s) => $segmentLabels[$s] ?? $s, $r['segments'])),
                    (string) $r['orders'],
                    $r['last_at']?->format('d/m/Y'),
                    $r['favorite_category'],
                    $r['opt_in_at']?->format('d/m/Y'),
                ]), ';');
            }
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /** Evita inyección de fórmulas al abrir el CSV en Excel. */
    public function safeCsv($v): string
    {
        $v = (string) $v;

        return preg_match('/^[=+\-@\t\r]/', $v) ? "'" . $v : $v;
    }
}
