<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Setting;
use App\Models\Supplier;
use App\Models\SupplierInvoice;
use App\Services\PurchaseService;
use Illuminate\Http\Request;

class SupplierInvoiceController extends Controller
{
    public function index(Request $request)
    {
        $suppliers = Supplier::with('party')->orderByDesc('id')->get();

        $query = SupplierInvoice::with('supplier.party')->orderByDesc('date')->orderByDesc('id');

        if ($request->filled('supplier_id')) {
            $query->where('supplier_id', $request->integer('supplier_id'));
        }

        $invoices = $query->paginate(25)->withQueryString();

        $bcvRate = (float) Setting::get('bcv_rate', 0);

        return view('admin.suppliers.invoices.index', compact('invoices', 'suppliers', 'bcvRate'));
    }

    public function create(Request $request)
    {
        $invoice = new SupplierInvoice();
        $suppliers = Supplier::with('party')->orderByDesc('id')->get();
        $selectedSupplierId = $request->integer('supplier_id');
        $bcvRate = (float) Setting::get('bcv_rate', 0);
        $prefetchedProducts = $this->prefetchProductsForForm();

        return view('admin.suppliers.invoices.form', [
            'invoice' => $invoice,
            'suppliers' => $suppliers,
            'selectedSupplierId' => $selectedSupplierId,
            'bcvRate' => $bcvRate,
            'prefetchedProducts' => $prefetchedProducts,
            'mode' => 'create',
        ]);
    }

    public function store(Request $request)
    {
        [$invoiceData, $itemsData] = $this->validateData($request);

        $invoice = PurchaseService::saveInvoiceWithItems(
            invoiceData: $invoiceData,
            itemsData: $itemsData,
            userId: $request->user()?->id,
        );

        return redirect()->route('supplier-invoices.show', $invoice)
            ->with('status', 'Factura registrada correctamente.');
    }

    public function edit(SupplierInvoice $supplierInvoice)
    {
        $suppliers = Supplier::with('party')->orderByDesc('id')->get();
        $bcvRate = (float) Setting::get('bcv_rate', 0);

        $supplierInvoice->loadMissing(['items.product']);
        $prefetchedProducts = $this->prefetchProductsForForm($supplierInvoice);

        return view('admin.suppliers.invoices.form', [
            'invoice' => $supplierInvoice,
            'suppliers' => $suppliers,
            'selectedSupplierId' => $supplierInvoice->supplier_id,
            'bcvRate' => $bcvRate,
            'prefetchedProducts' => $prefetchedProducts,
            'mode' => 'edit',
        ]);
    }

    public function update(Request $request, SupplierInvoice $supplierInvoice)
    {
        [$invoiceData, $itemsData] = $this->validateData($request);

        $invoice = PurchaseService::saveInvoiceWithItems(
            invoiceData: $invoiceData,
            itemsData: $itemsData,
            userId: $request->user()?->id,
            existingInvoice: $supplierInvoice,
        );

        return redirect()->route('supplier-invoices.show', $invoice)
            ->with('status', 'Factura actualizada correctamente.');
    }

    public function show(SupplierInvoice $supplierInvoice)
    {
        $supplierInvoice->load(['supplier.party', 'payments', 'items.product']);
        $bcvRate = (float) Setting::get('bcv_rate', 0);

        return view('admin.suppliers.invoices.show', [
            'invoice' => $supplierInvoice,
            'bcvRate' => $bcvRate,
        ]);
    }

    protected function validateData(Request $request): array
    {
        $bcvRate = (float) Setting::get('bcv_rate', 0);

        $data = $request->validate([
            'supplier_id' => 'required|exists:suppliers,id',
            'invoice_number' => 'nullable|string|max:100',
            'control_number' => 'nullable|string|max:100',
            'doc_type' => 'nullable|in:FC,ND,NC',
            'affected_document' => 'nullable|string|max:100',
            'date' => 'required|date',
            'due_date' => 'nullable|date',
            'currency' => 'required|string|max:10',
            'amount_usd' => 'nullable|numeric|min:0',
            'amount_bs' => 'nullable|numeric|min:0',
            'bcv_rate_at_issue' => 'nullable|numeric|min:0',
            'currency_rate_source' => 'nullable|string|max:100',
            'status' => 'nullable|string|max:20',
            'notes' => 'nullable|string',
            'withholding_amount' => 'nullable|numeric|min:0',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'nullable|exists:products,id',
            'items.*.description' => 'nullable|string|max:255',
            'items.*.unit' => 'nullable|string|max:20',
            'items.*.quantity' => 'required|numeric|min:0.0001',
            'items.*.unit_cost' => 'required|numeric|min:0',
            'items.*.tax_rate' => 'nullable|numeric|min:0',
            'items.*.tax_amount' => 'nullable|numeric|min:0',
            'items.*.subtotal' => 'nullable|numeric|min:0',
            'items.*.total' => 'nullable|numeric|min:0',
        ]);

        // Normalizar montos según moneda y tasa
        $data['bcv_rate_at_issue'] = $data['bcv_rate_at_issue'] ?? $bcvRate;

        $amountUsd = (float) ($data['amount_usd'] ?? 0);
        $amountBs = (float) ($data['amount_bs'] ?? 0);

        if ($data['currency'] === 'USD') {
            $data['amount_usd'] = $amountUsd;
            $data['amount_bs'] = $amountBs > 0 ? $amountBs : null;
        } else {
            // Moneda base Bs: si no viene amount_usd, lo calculamos
            if ($amountBs > 0 && $data['bcv_rate_at_issue'] > 0 && $amountUsd == 0) {
                $amountUsd = $amountBs / $data['bcv_rate_at_issue'];
            }
            $data['amount_usd'] = $amountUsd;
            $data['amount_bs'] = $amountBs > 0 ? $amountBs : null;
        }

        $data['status'] = $data['status'] ?: 'pendiente';
        $data['doc_type'] = $data['doc_type'] ?: 'FC';
        $data['withholding_amount'] = $data['withholding_amount'] ?? 0;

        $items = $data['items'];
        unset($data['items']);

        return [$data, $items];
    }

    protected function prefetchProductsForForm(?SupplierInvoice $invoice = null)
    {
        $productIds = collect(old('items', []))
            ->pluck('product_id')
            ->filter()
            ->map(fn ($id) => (int) $id);

        if ($invoice?->exists) {
            $items = $invoice->relationLoaded('items') ? $invoice->items : $invoice->items()->get();
            $productIds = $productIds->merge(
                $items->pluck('product_id')->filter()->map(fn ($id) => (int) $id)
            );
        }

        $ids = $productIds->unique()->values();

        $columns = [
            'id',
            'name',
            'sku',
            'default_unit',
            'is_stock_tracked',
            'is_prepared',
            'is_raw_material',
        ];

        if ($ids->isEmpty()) {
            return Product::orderBy('name')
                ->limit(25)
                ->get($columns)
                ->keyBy('id');
        }

        return Product::whereIn('id', $ids)
            ->orderBy('name')
            ->get($columns)
            ->keyBy('id');
    }
}
