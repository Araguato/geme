<?php

namespace App\Services;

use App\Models\FiscalLedger;
use App\Models\Setting;
use Illuminate\Database\Eloquent\Collection;
use SimpleXMLElement;

class SeniatXmlService
{
    public static function generate(string $entryType, string $period): string
    {
        $entries = FiscalLedger::where('entry_type', $entryType)
            ->where('period', $period)
            ->orderBy('document_date')
            ->get();

        $companyTaxId = Setting::get('company_tax_id', 'J000000000');
        $companyName = Setting::get('company_name', 'EMPRESA SIN NOMBRE');
        $fiscalRegime = Setting::get('fiscal_regime', 'ORDINARIO');

        $total = $entries->sum('total_amount');
        $taxable = $entries->sum('taxable_amount');
        $tax = $entries->sum('tax_amount');
        $exempt = $entries->sum('exempt_amount');
        $exonerated = $entries->sum('exonerated_amount');
        $nonSubject = $entries->sum('non_subject_amount');
        $withheld = $entries->sum('withholding_amount');

        $rootName = $entryType === 'entrada' ? 'LibroCompras' : 'LibroVentas';

        $xml = new SimpleXMLElement("<?xml version=\"1.0\" encoding=\"UTF-8\"?><{$rootName} xmlns:xsi=\"http://www.w3.org/2001/XMLSchema-instance\"></{$rootName}>");

        $xml->addChild('Rif', static::escape($companyTaxId));
        $xml->addChild('Nombre', static::escape($companyName));
        $xml->addChild('Periodo', $period);
        $xml->addChild('Regimen', static::escape($fiscalRegime));
        $xml->addChild('NumeroOperaciones', (string) $entries->count());
        $xml->addChild('TotalMonto', number_format($total, 2, '.', ''));
        $xml->addChild('TotalBaseImponible', number_format($taxable, 2, '.', ''));
        $xml->addChild('TotalIva', number_format($tax, 2, '.', ''));
        $xml->addChild('TotalExento', number_format($exempt, 2, '.', ''));
        $xml->addChild('TotalExonerado', number_format($exonerated, 2, '.', ''));
        $xml->addChild('TotalNoSujeto', number_format($nonSubject, 2, '.', ''));
        $xml->addChild('TotalIvaRetenido', number_format($withheld, 2, '.', ''));

        $detail = $xml->addChild('Detalle');

        foreach ($entries as $index => $entry) {
            $row = $detail->addChild('Operacion');
            $row->addChild('Numero', (string) ($index + 1));
            $row->addChild('FechaDocumento', $entry->document_date?->format('d/m/Y') ?? '');
            $row->addChild('RifAgente', static::escape($entry->partner_tax_id ?? 'V000000000'));
            $row->addChild('NombreAgente', static::escape($entry->partner_name ?? 'CONSUMIDOR FINAL'));
            $row->addChild('TipoDocumento', static::escape($entry->document_type ?? 'FACTURA'));
            $row->addChild('NumeroDocumento', static::escape($entry->document_number ?? ''));
            $row->addChild('NumeroControl', static::escape($entry->control_number ?? ''));
            $row->addChild('DocumentoAfectado', static::escape($entry->affected_document_number ?? ''));
            $row->addChild('MontoTotal', number_format($entry->total_amount, 2, '.', ''));
            $row->addChild('MontoExento', number_format($entry->exempt_amount, 2, '.', ''));
            $row->addChild('MontoExonerado', number_format($entry->exonerated_amount, 2, '.', ''));
            $row->addChild('MontoNoSujeto', number_format($entry->non_subject_amount, 2, '.', ''));
            $row->addChild('BaseImponible', number_format($entry->taxable_amount, 2, '.', ''));
            $row->addChild('Alicuota', number_format($entry->tax_rate, 2, '.', ''));
            $row->addChild('MontoIva', number_format($entry->tax_amount, 2, '.', ''));
            $row->addChild('IvaRetenido', number_format($entry->withholding_amount, 2, '.', ''));
            $row->addChild('FiscalHash', $entry->fiscal_hash ?? '');
        }

        $dom = dom_import_simplexml($xml)->ownerDocument;
        $dom->formatOutput = true;

        return $dom->saveXML();
    }

    public static function fileName(string $entryType, string $period, string $companyTaxId): string
    {
        $bookName = $entryType === 'entrada' ? 'LibroCompras' : 'LibroVentas';
        return $bookName . '_' . $period . '_' . str_replace(['-', ' '], ['', '_'], $companyTaxId) . '.xml';
    }

    private static function escape(?string $value): string
    {
        return htmlspecialchars((string) $value, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }
}
