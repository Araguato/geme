<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\FiscalDocument;
use App\Models\Setting;
use App\Services\Fiscal\FiscalEmissionService;
use App\Services\Fiscal\FiscalManager;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Facturación por medio autorizado (SNAT/2026/00093): ajustes (solo admin) y bandeja de
 * documentos con reintento y registro de contingencia (admin, supervisor y contable).
 */
class FiscalDocumentController extends Controller
{
    public function index(Request $request)
    {
        $status = $request->query('status');

        $docs = FiscalDocument::with('order:id,order_number,total,created_at')
            ->when(in_array($status, FiscalDocument::STATUSES, true), fn ($q) => $q->where('status', $status))
            ->latest('id')->paginate(25)->withQueryString();

        return view('fiscal.index', [
            'docs' => $docs,
            'status' => $status,
            'counts' => FiscalDocument::selectRaw('status, COUNT(*) as n')->groupBy('status')->pluck('n', 'status'),
            'pendingReports' => FiscalDocument::where('status', 'contingency')->whereNull('reported_at')->get(),
            'enabled' => FiscalManager::enabled(),
            'providerLabel' => FiscalManager::LABELS[FiscalManager::key()],
        ]);
    }

    public function retry(FiscalDocument $document)
    {
        $doc = FiscalEmissionService::retry($document);

        return back()->with($doc->status === 'issued' ? 'success' : 'error',
            $doc->status === 'issued' ? 'Documento emitido: ' . $doc->invoice_number : 'No se pudo emitir: ' . $doc->last_error);
    }

    public function processPending()
    {
        $n = FiscalEmissionService::processDue();

        return back()->with('success', "Documentos procesados: {$n}.");
    }

    public function contingency(Request $request, FiscalDocument $document)
    {
        abort_if($document->isDone(), 422, 'Este documento ya está resuelto.');

        $data = $request->validate([
            'invoice_number' => 'required|string|max:40',
            'control_number' => 'required|string|max:40',
        ], [
            'invoice_number.required' => 'Indica el número de la factura de talonario.',
            'control_number.required' => 'Indica el número de control de la factura de talonario.',
        ]);

        FiscalEmissionService::registerContingency($document, $data['invoice_number'], $data['control_number'], Auth::id());

        return back()->with('success', 'Factura de talonario registrada en contingencia.');
    }

    /** Anota que la factura de talonario ya se remitió (PDF al SENIAT o a la imprenta). */
    public function report(Request $request, FiscalDocument $document)
    {
        abort_unless($document->status === 'contingency', 422, 'Solo las facturas de contingencia se remiten.');
        $this->markReported($document, $request);

        return back()->with('success', 'Documento marcado como remitido.');
    }

    public function reportAll(Request $request)
    {
        $docs = FiscalDocument::where('status', 'contingency')->whereNull('reported_at')->get();
        $docs->each(fn ($d) => $this->markReported($d, $request));

        return back()->with('success', "Documentos marcados como remitidos: {$docs->count()}.");
    }

    private function markReported(FiscalDocument $document, Request $request): void
    {
        if ($document->reported_at) {
            return;
        }
        $document->forceFill(['reported_at' => now(), 'reported_by' => Auth::id()])->save();
        AuditLog::create([
            'user_id' => Auth::id(),
            'action' => 'fiscal_contingency_reported',
            'entity_type' => FiscalDocument::class,
            'entity_id' => $document->id,
            'description' => "Factura de contingencia {$document->invoice_number} remitida (pedido #{$document->order_id})",
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);
    }

    public function edit()
    {
        return view('settings.fiscal', [
            'providers' => FiscalManager::LABELS,
            'current' => FiscalManager::key(),
            'pending' => FiscalDocument::whereIn('status', ['pending', 'error'])->count(),
        ]);
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'provider' => 'required|in:' . implode(',', array_keys(FiscalManager::LABELS)),
        ]);

        $before = FiscalManager::key();
        Setting::set('fiscal_provider', $data['provider']);

        AuditLog::create([
            'user_id' => Auth::id(),
            'action' => 'fiscal_provider_changed',
            'entity_type' => 'Setting',
            'description' => "Medio de emisión fiscal: {$before} → {$data['provider']}",
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return redirect()->route('settings.fiscal.edit')->with('success', 'Medio de emisión actualizado.');
    }
}
