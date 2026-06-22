@extends('layout')

@section('content')
    <h1>{{ $mode === 'edit' ? 'Editar factura de proveedor' : 'Nueva factura de proveedor' }}</h1>

    @if($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @php
        $items = old('items', $invoice->relationLoaded('items')
            ? $invoice->items->map(fn ($item) => [
                'product_id' => $item->product_id,
                'description' => $item->description,
                'unit' => $item->unit,
                'quantity' => $item->quantity,
                'unit_cost' => $item->unit_cost,
                'tax_rate' => $item->tax_rate,
                'tax_amount' => $item->tax_amount,
                'subtotal' => $item->subtotal,
                'total' => $item->total,
            ])->toArray()
            : []);

        if (empty($items)) {
            $items = [[]];
        }
    @endphp

    <form action="{{ $mode === 'edit' ? route('supplier-invoices.update', $invoice) : route('supplier-invoices.store') }}" method="POST" class="mt-3" id="supplier-invoice-form">
        @csrf
        @if($mode === 'edit')
            @method('PUT')
        @endif

        <div class="row g-3 mb-3">
            <div class="col-md-5">
                <label class="form-label">Proveedor *</label>
                <select name="supplier_id" class="form-select" required>
                    <option value="">Seleccione...</option>
                    @foreach($suppliers as $supplier)
                        @php($party = $supplier->party)
                        <option value="{{ $supplier->id }}"
                            @selected(old('supplier_id', $selectedSupplierId) == $supplier->id)>
                            {{ $party?->name ?? 'Proveedor #'.$supplier->id }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Fecha *</label>
                <input type="date" name="date" class="form-control" required
                       value="{{ old('date', optional($invoice->date)->format('Y-m-d') ?? now()->format('Y-m-d')) }}">
            </div>
            <div class="col-md-3">
                <label class="form-label">Vencimiento</label>
                <input type="date" name="due_date" class="form-control"
                       value="{{ old('due_date', optional($invoice->due_date)->format('Y-m-d')) }}">
            </div>
        </div>

        <div class="row g-3 mb-3">
            <div class="col-md-3">
                <label class="form-label">N° factura</label>
                <input type="text" name="invoice_number" class="form-control"
                       value="{{ old('invoice_number', $invoice->invoice_number) }}">
            </div>
            <div class="col-md-3">
                <label class="form-label">N° control</label>
                <input type="text" name="control_number" class="form-control"
                       value="{{ old('control_number', $invoice->control_number) }}">
            </div>
            <div class="col-md-2">
                <label class="form-label">Tipo documento</label>
                @php($docType = old('doc_type', $invoice->doc_type ?? 'FC'))
                <select name="doc_type" class="form-select">
                    <option value="FC" @selected($docType === 'FC')>Factura (FC)</option>
                    <option value="ND" @selected($docType === 'ND')>Nota de débito (ND)</option>
                    <option value="NC" @selected($docType === 'NC')>Nota de crédito (NC)</option>
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label">Documento afectado</label>
                <input type="text" name="affected_document" class="form-control"
                       value="{{ old('affected_document', $invoice->affected_document) }}"
                       placeholder="Aplica para ND/NC">
            </div>
        </div>

        <div class="row g-3 mb-3">
            <div class="col-md-2">
                <label class="form-label">Moneda</label>
                @php($cur = old('currency', $invoice->currency ?? 'USD'))
                <select name="currency" class="form-select">
                    <option value="USD" @selected($cur === 'USD')>USD</option>
                    <option value="VES" @selected($cur === 'VES')>Bs</option>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Monto en USD</label>
                <input type="number" name="amount_usd" step="0.01" min="0" class="form-control"
                       value="{{ old('amount_usd', $invoice->amount_usd) }}">
            </div>
            <div class="col-md-3">
                <label class="form-label">Monto en Bs</label>
                <input type="number" name="amount_bs" step="0.01" min="0" class="form-control"
                       value="{{ old('amount_bs', $invoice->amount_bs) }}">
                <div class="form-text">BCV actual: {{ number_format($bcvRate, 2) }} Bs / USD</div>
            </div>
            <div class="col-md-2">
                <label class="form-label">Retenciones</label>
                <input type="number" name="withholding_amount" step="0.01" min="0" class="form-control"
                       value="{{ old('withholding_amount', $invoice->withholding_amount) }}">
            </div>
            <div class="col-md-2">
                <label class="form-label">Estado</label>
                @php($st = old('status', $invoice->status ?? 'pendiente'))
                <select name="status" class="form-select">
                    <option value="pendiente" @selected($st === 'pendiente')>Pendiente</option>
                    <option value="parcial" @selected($st === 'parcial')>Parcial</option>
                    <option value="pagada" @selected($st === 'pagada')>Pagada</option>
                </select>
            </div>
        </div>

        <div class="row g-3 mb-3">
            <div class="col-md-3">
                <label class="form-label">Tasa BCV usada</label>
                <input type="number" name="bcv_rate_at_issue" step="0.000001" min="0" class="form-control"
                       value="{{ old('bcv_rate_at_issue', $invoice->bcv_rate_at_issue ?: $bcvRate) }}">
            </div>
            <div class="col-md-3">
                <label class="form-label">Fuente de la tasa</label>
                <input type="text" name="currency_rate_source" class="form-control"
                       value="{{ old('currency_rate_source', $invoice->currency_rate_source) }}"
                       placeholder="Ej: BCV, Monitor, Convenida">
            </div>
        </div>

        <div class="mb-3">
            <label class="form-label">Notas</label>
            <textarea name="notes" class="form-control" rows="3">{{ old('notes', $invoice->notes) }}</textarea>
        </div>

        <hr class="my-4">

        <h2 class="h5 mb-3">Renglones de la factura</h2>

        <div class="table-responsive">
            <table class="table table-bordered align-middle" id="invoice-items-table">
                <thead class="table-light">
                <tr>
                    <th style="min-width: 200px;">Producto</th>
                    <th>Descripción</th>
                    <th style="width: 110px;">Unidad</th>
                    <th style="width: 130px;">Cantidad</th>
                    <th style="width: 150px;">Costo unitario</th>
                    <th style="width: 130px;">IVA %</th>
                    <th style="width: 150px;">Impuesto</th>
                    <th style="width: 150px;">Subtotal</th>
                    <th style="width: 150px;">Total</th>
                    <th style="width: 60px;"></th>
                </tr>
                </thead>
                <tbody id="invoice-items-body">
                @foreach($items as $index => $row)
                    <tr>
                        <td>
                            <select name="items[{{ $index }}][product_id]" class="form-select form-select-sm">
                                <option value="">— Seleccione —</option>
                                @foreach($prefetchedProducts as $product)
                                    <option value="{{ $product->id }}" @selected((string)($row['product_id'] ?? '') === (string) $product->id)>
                                        {{ $product->name }} @if($product->sku) ({{ $product->sku }}) @endif
                                    </option>
                                @endforeach
                            </select>
                        </td>
                        <td>
                            <input type="text" name="items[{{ $index }}][description]" class="form-control form-control-sm"
                                   value="{{ $row['description'] ?? '' }}">
                        </td>
                        <td>
                            <input type="text" name="items[{{ $index }}][unit]" class="form-control form-control-sm"
                                   value="{{ $row['unit'] ?? '' }}">
                        </td>
                        <td>
                            <input type="number" name="items[{{ $index }}][quantity]" step="0.0001" min="0"
                                   class="form-control form-control-sm"
                                   value="{{ $row['quantity'] ?? '' }}">
                        </td>
                        <td>
                            <input type="number" name="items[{{ $index }}][unit_cost]" step="0.0001" min="0"
                                   class="form-control form-control-sm"
                                   value="{{ $row['unit_cost'] ?? '' }}">
                        </td>
                        <td>
                            <input type="number" name="items[{{ $index }}][tax_rate]" step="0.0001" min="0"
                                   class="form-control form-control-sm"
                                   value="{{ $row['tax_rate'] ?? '' }}">
                        </td>
                        <td>
                            <input type="number" name="items[{{ $index }}][tax_amount]" step="0.01" min="0"
                                   class="form-control form-control-sm"
                                   value="{{ $row['tax_amount'] ?? '' }}">
                        </td>
                        <td>
                            <input type="number" name="items[{{ $index }}][subtotal]" step="0.01" min="0"
                                   class="form-control form-control-sm"
                                   value="{{ $row['subtotal'] ?? '' }}">
                        </td>
                        <td>
                            <input type="number" name="items[{{ $index }}][total]" step="0.01" min="0"
                                   class="form-control form-control-sm"
                                   value="{{ $row['total'] ?? '' }}">
                        </td>
                        <td class="text-center">
                            <button type="button" class="btn btn-sm btn-outline-danger remove-item-row" title="Eliminar renglón">&times;</button>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>

        <div class="d-flex justify-content-between align-items-center mb-4">
            <button type="button" class="btn btn-outline-secondary" id="add-item-row">Agregar renglón</button>
            <small class="text-muted">Los totales se recalculan automáticamente al guardar.</small>
        </div>

        <button type="submit" class="btn btn-primary">Guardar</button>
        <a href="{{ route('supplier-invoices.index') }}" class="btn btn-secondary">Cancelar</a>
    </form>
@endsection

@push('scripts')
<script>
    (function () {
        const itemsBody = document.getElementById('invoice-items-body');
        const addButton = document.getElementById('add-item-row');
        let rowIndex = {{ count($items) }};

        const productOptionsHtml = `@foreach($prefetchedProducts as $product)<option value="{{ $product->id }}">{{ addslashes($product->name) }}@if($product->sku) ({{ addslashes($product->sku) }})@endif</option>@endforeach`;

        function buildRow(index) {
            const tr = document.createElement('tr');
            tr.innerHTML = `
                <td>
                    <select name="items[${index}][product_id]" class="form-select form-select-sm">
                        <option value="">— Seleccione —</option>
                        ${productOptionsHtml}
                    </select>
                </td>
                <td><input type="text" name="items[${index}][description]" class="form-control form-control-sm"></td>
                <td><input type="text" name="items[${index}][unit]" class="form-control form-control-sm"></td>
                <td><input type="number" name="items[${index}][quantity]" step="0.0001" min="0" class="form-control form-control-sm"></td>
                <td><input type="number" name="items[${index}][unit_cost]" step="0.0001" min="0" class="form-control form-control-sm"></td>
                <td><input type="number" name="items[${index}][tax_rate]" step="0.0001" min="0" class="form-control form-control-sm"></td>
                <td><input type="number" name="items[${index}][tax_amount]" step="0.01" min="0" class="form-control form-control-sm"></td>
                <td><input type="number" name="items[${index}][subtotal]" step="0.01" min="0" class="form-control form-control-sm"></td>
                <td><input type="number" name="items[${index}][total]" step="0.01" min="0" class="form-control form-control-sm"></td>
                <td class="text-center">
                    <button type="button" class="btn btn-sm btn-outline-danger remove-item-row" title="Eliminar renglón">&times;</button>
                </td>
            `;
            return tr;
        }

        addButton?.addEventListener('click', () => {
            const row = buildRow(rowIndex++);
            itemsBody.appendChild(row);
        });

        itemsBody?.addEventListener('click', (event) => {
            if (event.target.classList.contains('remove-item-row')) {
                const rows = itemsBody.querySelectorAll('tr');
                if (rows.length > 1) {
                    event.target.closest('tr').remove();
                } else {
                    const inputs = event.target.closest('tr').querySelectorAll('input, select');
                    inputs.forEach((input) => input.value = '');
                }
            }
        });
    })();
</script>
@endpush
