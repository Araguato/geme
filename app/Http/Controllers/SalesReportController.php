<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Category;
use App\Models\SalesLocation;
use App\Models\User;
use App\Services\SalesReportService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Informe de ventas por vendedor, categoría o producto (admin, supervisor y contable).
 */
class SalesReportController extends Controller
{
    protected const GROUP_LABELS = [
        'seller' => 'Vendedor',
        'category' => 'Categoría',
        'product' => 'Producto',
    ];

    protected function filters(Request $request): array
    {
        $data = $request->validate([
            'from' => 'nullable|date',
            'to' => 'nullable|date',
            'group' => 'nullable|in:' . implode(',', SalesReportService::GROUPS),
            'user_id' => 'nullable|integer|exists:users,id',
            'category_id' => 'nullable|integer|exists:categories,id',
            'sales_location_id' => 'nullable|integer|exists:sales_locations,id',
        ]);

        $data['group'] = $data['group'] ?? 'seller';

        return $data;
    }

    protected function report(array $f): SalesReportService
    {
        return SalesReportService::forRange($f['from'] ?? null, $f['to'] ?? null, [
            'user_id' => $f['user_id'] ?? null,
            'category_id' => $f['category_id'] ?? null,
            'sales_location_id' => $f['sales_location_id'] ?? null,
        ]);
    }

    public function index(Request $request)
    {
        $f = $this->filters($request);
        $report = $this->report($f);

        // Vendedores: quien tenga rol de venta o haya registrado alguna venta
        $sellers = User::query()
            ->whereHas('roles', fn ($q) => $q->whereIn('name', ['admin', 'supervisor', 'cajero', 'vendedor']))
            ->orWhereIn('id', fn ($q) => $q->select('user_id')->from('orders')->whereNotNull('user_id'))
            ->orderBy('name')
            ->get(['id', 'name']);

        return view('reports.sales', [
            'filters' => $f,
            'from' => $report->from(),
            'to' => $report->to(),
            'group' => $f['group'],
            'groupLabel' => self::GROUP_LABELS[$f['group']],
            'groupLabels' => self::GROUP_LABELS,
            'totals' => $report->totals(),
            'rows' => $report->rows($f['group']),
            'sellers' => $sellers,
            'categories' => Category::orderBy('name')->get(['id', 'name']),
            'salesLocations' => SalesLocation::orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function export(Request $request): StreamedResponse
    {
        $f = $this->filters($request);
        $report = $this->report($f);
        $rows = $report->rows($f['group']);

        AuditLog::create([
            'user_id' => $request->user()->id,
            'action' => 'report.sales.export',
            'entity_type' => 'report',
            'description' => sprintf('Exportó informe de ventas por %s (%s a %s)',
                strtolower(self::GROUP_LABELS[$f['group']]), $report->from()->toDateString(), $report->to()->toDateString()),
            'data_after' => $f,
            'ip_address' => $request->ip(),
            'user_agent' => substr((string) $request->userAgent(), 0, 1000),
        ]);

        $filename = sprintf('ventas_por_%s_%s_%s.csv', $f['group'], $report->from()->format('Ymd'), $report->to()->format('Ymd'));

        return response()->streamDownload(function () use ($rows, $f) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF"); // BOM para que Excel lea bien los acentos
            fputcsv($out, [self::GROUP_LABELS[$f['group']], 'Tickets', 'Cantidad', 'Venta neta', 'IVA', 'Total', 'Costo', 'Margen', 'Margen %', '% de la venta', '% de la venta con costo'], ';');
            foreach ($rows as $r) {
                fputcsv($out, [
                    $this->safeCsv($r['label']), $r['tickets'], $this->num($r['quantity'], 3), $this->num($r['net']), $this->num($r['tax']),
                    $this->num($r['total']), $this->num($r['cost']), $this->num($r['margin']), $r['margin_pct'] === null ? '' : $this->num($r['margin_pct'], 1), $this->num($r['share'], 1), $this->num($r['cost_coverage'], 1),
                ], ';');
            }
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    protected function num(float $v, int $dec = 2): string
    {
        return number_format($v, $dec, ',', '');
    }

    /** Evita inyección de fórmulas al abrir el CSV en Excel. */
    protected function safeCsv(?string $v): string
    {
        $v = (string) $v;

        return preg_match('/^[=+\-@\t\r]/', $v) ? "'" . $v : $v;
    }
}
