<?php

namespace App\Http\Controllers;

use App\Models\FiscalLedger;
use App\Models\Setting;
use App\Services\SeniatXmlService;
use Illuminate\Http\Request;

class FiscalLedgerController extends Controller
{
    public function index(Request $request)
    {
        $entryType = $request->query('entry_type', 'entrada');
        $period = $request->query('period', now()->format('Y-m'));
        $search = $request->query('search');

        $query = FiscalLedger::where('entry_type', $entryType)
            ->where('period', $period)
            ->orderBy('document_date');

        if ($search) {
            $like = '%' . trim($search) . '%';
            $query->where(function ($q) use ($like) {
                $q->where('partner_name', 'like', $like)
                    ->orWhere('partner_tax_id', 'like', $like)
                    ->orWhere('document_number', 'like', $like)
                    ->orWhere('control_number', 'like', $like);
            });
        }

        $entries = $query->paginate(50)->withQueryString();

        $periods = FiscalLedger::select('period')
            ->distinct()
            ->orderByDesc('period')
            ->pluck('period');

        $companyTaxId = Setting::get('company_tax_id', 'J000000000');
        $companyName = Setting::get('company_name', 'EMPRESA SIN NOMBRE');

        return view('fiscal_ledger.index', compact(
            'entries',
            'entryType',
            'period',
            'search',
            'periods',
            'companyTaxId',
            'companyName'
        ));
    }

    public function taxReport(Request $request)
    {
        $year = $request->query('year', now()->year);

        $summary = FiscalLedger::query()
            ->selectRaw('period, entry_type, COUNT(*) as operations, SUM(taxable_amount) as taxable, SUM(tax_amount) as tax, SUM(exempt_amount) as exempt, SUM(exonerated_amount) as exonerated, SUM(non_subject_amount) as non_subject, SUM(withholding_amount) as withholding, SUM(total_amount) as total')
            ->whereRaw('LEFT(period, 4) = ?', [(string) $year])
            ->groupBy('period', 'entry_type')
            ->orderBy('period')
            ->orderBy('entry_type')
            ->get();

        $periods = $summary->groupBy('period')->map(function ($rows) {
            return [
                'entrada' => $rows->firstWhere('entry_type', 'entrada'),
                'salida' => $rows->firstWhere('entry_type', 'salida'),
            ];
        });

        $years = FiscalLedger::selectRaw('DISTINCT LEFT(period, 4) as year')
            ->orderByDesc('year')
            ->pluck('year');

        $companyTaxId = Setting::get('company_tax_id', 'J000000000');
        $companyName = Setting::get('company_name', 'EMPRESA SIN NOMBRE');

        return view('fiscal_ledger.tax_report', compact(
            'periods',
            'year',
            'years',
            'companyTaxId',
            'companyName'
        ));
    }

    public function exportXml(Request $request)
    {
        $entryType = $request->query('entry_type', 'entrada');
        $period = $request->query('period', now()->format('Y-m'));

        $companyTaxId = Setting::get('company_tax_id', 'J000000000');

        $xml = SeniatXmlService::generate($entryType, $period);
        $fileName = SeniatXmlService::fileName($entryType, $period, $companyTaxId);

        FiscalLedger::where('entry_type', $entryType)
            ->where('period', $period)
            ->where('is_exported', false)
            ->update(['is_exported' => true]);

        return response($xml, 200, [
            'Content-Type' => 'application/xml',
            'Content-Disposition' => 'attachment; filename="' . $fileName . '"',
        ]);
    }
}
