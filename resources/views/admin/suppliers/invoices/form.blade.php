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
        $unitOptionsHtml = collect($unitOptions)->map(fn ($u) => '<option value="' . e($u['value']) . '">' . e($u['label']) . '</option>')->implode('');
        $taxOptionsHtml = collect($taxOptions)->map(fn ($label, $rate) => '<option value="' . e($rate) . '"' . ((string) $rate === (string) \App\Http\Controllers\SupplierInvoiceController::DEFAULT_TAX_RATE ? ' selected' : '') . '>' . e($label) . '</option>')->implode('');

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
                <input type="date" name="date" id="invoice-date" class="form-control" required
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
                <select name="currency" id="invoice-currency" class="form-select">
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
                <div class="form-text">BCV actual: {{ number_format($bcvRate, 6, ",", ".") }} Bs / USD</div>
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
                <label class="form-label">Tasa BCV de la fecha de emisión</label>
                <input type="number" name="bcv_rate_at_issue" id="invoice-rate" step="0.000001" min="0" class="form-control"
                       value="{{ old('bcv_rate_at_issue', $invoice->bcv_rate_at_issue ?: $bcvRate) }}">
                <div class="form-text" id="rate-hint">Solo vale para esta factura: no cambia la tasa del TPV ni los precios de venta.</div>
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

        <div class="row mb-3">
            <div class="col-md-5">
                <label class="form-label">Depósito que recibe la mercancía</label>
                <select name="warehouse_id" class="form-select">
                    <option value="">Almacén principal</option>
                    @foreach($warehouses as $warehouse)
                        @continue($warehouse->code === 'MAIN')
                        <option value="{{ $warehouse->id }}" @selected(old('warehouse_id', $selectedWarehouseId ?? null) == $warehouse->id)>{{ $warehouse->name }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-bordered align-middle" id="invoice-items-table">
                <thead class="table-light">
                <tr>
                    <th style="min-width: 200px;">Producto</th>
                    <th>Descripción</th>
                    <th style="width: 110px;">Unidad</th>
                    <th style="width: 130px;">Cantidad</th>
                    <th style="width: 150px;">Costo unitario <span class="cur-label"></span></th>
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
                            @php($selected = $prefetchedProducts->get((int) ($row['product_id'] ?? 0)))
                            <input type="hidden" name="items[{{ $index }}][product_id]" class="product-id" value="{{ $row['product_id'] ?? '' }}">
                            <div class="input-group input-group-sm">
                                <input type="text" class="form-control product-search" autocomplete="off"
                                       placeholder="Buscar por nombre, SKU o código…"
                                       value="{{ $selected ? $selected->name . ($selected->sku ? ' (' . $selected->sku . ')' : '') : '' }}"
                                       data-selected-label="{{ $selected ? $selected->name . ($selected->sku ? ' (' . $selected->sku . ')' : '') : '' }}">
                                <button type="button" class="btn btn-outline-secondary product-clear" tabindex="-1" title="Quitar producto">&times;</button>
                            </div>
                        </td>
                        <td>
                            <input type="text" name="items[{{ $index }}][description]" class="form-control form-control-sm"
                                   value="{{ $row['description'] ?? '' }}">
                        </td>
                        <td>
                            @php($rowUnit = $row['unit'] ?? 'unidad')
                            <select name="items[{{ $index }}][unit]" class="form-select form-select-sm">
                                @php($unitKnown = collect($unitOptions)->contains('value', $rowUnit))
                                @unless($unitKnown)
                                    <option value="{{ $rowUnit }}" selected>{{ $rowUnit }}</option>
                                @endunless
                                @foreach($unitOptions as $u)
                                    <option value="{{ $u['value'] }}" @selected($rowUnit === $u['value'])>{{ $u['label'] }}</option>
                                @endforeach
                            </select>
                        </td>
                        <td>
                            <input type="number" name="items[{{ $index }}][quantity]" step="0.0001" min="0.0001" required
                                   class="form-control form-control-sm"
                                   value="{{ $row['quantity'] ?? '' }}">
                        </td>
                        <td>
                            <input type="number" name="items[{{ $index }}][unit_cost]" step="0.000001" min="0.000001" required
                                   class="form-control form-control-sm"
                                   value="{{ $row['unit_cost'] ?? '' }}">
                        </td>
                        <td>
                            @php($rowRate = (isset($row['tax_rate']) && $row['tax_rate'] !== '') ? (float) $row['tax_rate'] : (float) \App\Http\Controllers\SupplierInvoiceController::DEFAULT_TAX_RATE)
                            <select name="items[{{ $index }}][tax_rate]" class="form-select form-select-sm">
                                @unless(array_key_exists((string) (int) $rowRate, $taxOptions) && (float) (int) $rowRate === $rowRate)
                                    <option value="{{ $rowRate }}" selected>{{ rtrim(rtrim(number_format($rowRate, 2, '.', ''), '0'), '.') }} %</option>
                                @endunless
                                @foreach($taxOptions as $rate => $label)
                                    <option value="{{ $rate }}" @selected((float) $rate === $rowRate)>{{ $label }}</option>
                                @endforeach
                            </select>
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

        <div class="d-flex justify-content-between align-items-start mb-4">
            <div>
                <button type="button" class="btn btn-outline-secondary" id="add-item-row">Agregar renglón</button>
                <div class="small text-muted mt-2">Subtotal, impuesto y total del renglón se calculan al indicar cantidad, costo e IVA %; puedes corregirlos a mano.</div>
            </div>
            <table class="table table-sm table-borderless w-auto mb-0">
                <tr><td class="text-end text-muted">Subtotal</td><td class="text-end fw-semibold" id="sum-subtotal">0.00</td></tr>
                <tr><td class="text-end text-muted">IVA</td><td class="text-end fw-semibold" id="sum-tax">0.00</td></tr>
                <tr><td class="text-end">Total <span class="cur-label"></span></td><td class="text-end fw-bold fs-5" id="sum-total">0.00</td></tr>
                <tr id="sum-usd-row" class="d-none"><td class="text-end text-muted">Equivale a USD</td><td class="text-end fw-semibold" id="sum-usd">0.00</td></tr>
            </table>
        </div>

        <button type="submit" class="btn btn-primary">Guardar</button>
        <a href="{{ route('supplier-invoices.index') }}" class="btn btn-secondary">Cancelar</a>
    </form>

    <div class="modal fade" id="quick-product-modal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <form class="modal-content" id="quick-product-form" novalidate>
                <div class="modal-header">
                    <h5 class="modal-title">Crear producto nuevo</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-danger d-none" id="quick-product-error"></div>
                    <div class="row g-2">
                        <div class="col-12">
                            <label class="form-label">Nombre *</label>
                            <input type="text" name="name" class="form-control" maxlength="255" required>
                        </div>
                        <div class="col-md-7">
                            <label class="form-label">Categoría *</label>
                            <select name="category_id" class="form-select" required>
                                <option value="">— Seleccione —</option>
                                @foreach($categories as $category)
                                    <option value="{{ $category->id }}">{{ $category->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-5">
                            <label class="form-label">SKU</label>
                            <input type="text" name="sku" class="form-control" maxlength="50">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Unidad</label>
                            <select name="default_unit" class="form-select">
                                <option value="">—</option>
                                @foreach($unitOptions as $u)
                                    <option value="{{ $u['value'] }}" @selected($u['value'] === 'unidad')>{{ $u['label'] }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Costo</label>
                            <input type="number" name="cost" class="form-control" step="0.000001" min="0">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Precio de venta</label>
                            <input type="number" name="price" class="form-control" step="0.01" min="0" placeholder="= costo">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">IVA %</label>
                            <select name="tax_rate" class="form-select">
                                @foreach($taxOptions as $rate => $label)
                                    <option value="{{ $rate }}" @selected((string) $rate === '16')>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-8 d-flex align-items-end">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="is_stock_tracked" id="quick-stock" checked>
                                <label class="form-check-label" for="quick-stock">Controlar inventario (la compra suma stock)</label>
                            </div>
                        </div>
                    </div>
                    <div class="form-text mt-2">Si dejas el precio vacío se usa el costo; revísalo después en Productos.</div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary" id="quick-product-save">Crear y usar</button>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
<script>
    (function () {
        const itemsBody = document.getElementById('invoice-items-body');
        const addButton = document.getElementById('add-item-row');
        let rowIndex = {{ count($items) }};

        const unitOptionsHtml = @json($unitOptionsHtml);
        const taxOptionsHtml = @json($taxOptionsHtml);

        function buildRow(index) {
            const tr = document.createElement('tr');
            tr.innerHTML = `
                <td>
                    <input type="hidden" name="items[${index}][product_id]" class="product-id" value="">
                    <div class="input-group input-group-sm">
                        <input type="text" class="form-control product-search" autocomplete="off" placeholder="Buscar por nombre, SKU o código…" data-selected-label="">
                        <button type="button" class="btn btn-outline-secondary product-clear" tabindex="-1" title="Quitar producto">&times;</button>
                    </div>
                </td>
                <td><input type="text" name="items[${index}][description]" class="form-control form-control-sm"></td>
                <td><select name="items[${index}][unit]" class="form-select form-select-sm">${unitOptionsHtml}</select></td>
                <td><input type="number" name="items[${index}][quantity]" step="0.0001" min="0.0001" required class="form-control form-control-sm"></td>
                <td><input type="number" name="items[${index}][unit_cost]" step="0.000001" min="0.000001" required class="form-control form-control-sm"></td>
                <td><select name="items[${index}][tax_rate]" class="form-select form-select-sm">${taxOptionsHtml}</select></td>
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


        // ---------- Cálculo del renglón y resumen ----------
        const num = (el) => {
            if (!el || el.value === '') return null;
            const n = parseFloat(el.value);
            return Number.isFinite(n) ? n : null;
        };
        const round2 = (n) => Math.round((n + Number.EPSILON) * 100) / 100;
        const fmt = (n) => n.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

        function updateSummary() {
            let sub = 0, tax = 0, tot = 0;
            itemsBody.querySelectorAll('tr').forEach((tr) => {
                sub += num(field(tr, 'subtotal')) || 0;
                tax += num(field(tr, 'tax_amount')) || 0;
                tot += num(field(tr, 'total')) || 0;
            });
            document.getElementById('sum-subtotal').textContent = fmt(sub);
            document.getElementById('sum-tax').textContent = fmt(tax);
            document.getElementById('sum-total').textContent = fmt(tot);

            // Factura en Bs: equivalente en USD con la tasa de la fecha de emisión
            const rate = num(document.getElementById('invoice-rate')) || 0;
            const inBs = document.getElementById('invoice-currency').value === 'VES';
            document.getElementById('sum-usd-row').classList.toggle('d-none', !inBs);
            document.getElementById('sum-usd').textContent = rate > 0 ? fmt(tot / rate) : '—';
            document.querySelectorAll('.cur-label').forEach((el) => { el.textContent = inBs ? '(Bs)' : '(USD)'; });
        }

        // La tasa se sugiere según la fecha de emisión (histórico de Divisas); si se escribe a mano no se pisa
        const rateHistory = @json($rateHistory ?? []);
        const dateInput = document.getElementById('invoice-date');
        const rateInput = document.getElementById('invoice-rate');
        let rateEdited = {{ $invoice->exists ? 'true' : 'false' }};
        rateInput.addEventListener('input', () => { rateEdited = true; updateSummary(); });
        document.getElementById('invoice-currency').addEventListener('change', updateSummary);
        dateInput.addEventListener('change', () => {
            const hint = document.getElementById('rate-hint');
            const dates = Object.keys(rateHistory).filter((d) => d <= dateInput.value).sort();
            if (rateEdited || !dates.length) {
                if (!rateEdited) hint.textContent = 'No hay tasa guardada para esa fecha: escríbela tal como figura en la factura. Solo vale para esta factura.';
                return;
            }
            const d = dates[dates.length - 1];
            rateInput.value = rateHistory[d];
            hint.textContent = 'Tasa del ' + d + ' tomada de Divisas. Solo vale para esta factura: no cambia la tasa del TPV.';
            updateSummary();
        });

        function recalcRow(tr) {
            const q = num(field(tr, 'quantity'));
            const c = num(field(tr, 'unit_cost'));
            if (q === null || c === null) { updateSummary(); return; }
            const rate = num(field(tr, 'tax_rate')) || 0;
            const sub = round2(q * c);
            const tax = round2(sub * rate / 100);
            field(tr, 'subtotal').value = sub.toFixed(2);
            field(tr, 'tax_amount').value = tax.toFixed(2);
            field(tr, 'total').value = round2(sub + tax).toFixed(2);
            updateSummary();
        }

        itemsBody.addEventListener('input', (e) => {
            const name = e.target.name || '';
            const tr = e.target.closest('tr');
            if (!tr) return;
            if (/\[(quantity|unit_cost|tax_rate)\]$/.test(name)) {
                recalcRow(tr);
            } else if (/\[(subtotal|tax_amount)\]$/.test(name)) {
                // Corrección manual: el total sigue a subtotal + impuesto
                const sub = num(field(tr, 'subtotal')) || 0;
                const tax = num(field(tr, 'tax_amount')) || 0;
                field(tr, 'total').value = round2(sub + tax).toFixed(2);
                updateSummary();
            } else if (/\[total\]$/.test(name)) {
                updateSummary();
            }
        });

        itemsBody.addEventListener('click', (e) => {
            if (e.target.classList.contains('remove-item-row')) setTimeout(updateSummary, 0);
        });

        // ---------- Buscador de productos y creación rápida ----------
        const form = document.getElementById('supplier-invoice-form');
        const csrf = form.querySelector('input[name="_token"]').value;
        const searchUrl = @json(route('supplier-invoices.products.search'));
        const createUrl = @json(route('supplier-invoices.products.store'));

        const panel = document.createElement('div');
        panel.className = 'list-group shadow d-none';
        panel.style.cssText = 'position:fixed;z-index:2000;max-height:280px;overflow:auto';
        document.body.appendChild(panel);

        let activeInput = null;
        let timer = null;
        let seq = 0;
        let highlighted = -1;

        const rowOf = (el) => el.closest('tr');
        const field = (tr, name) => tr.querySelector(`[name$="[${name}]"]`);

        function closePanel() {
            panel.classList.add('d-none');
            panel.innerHTML = '';
            highlighted = -1;
        }

        function placePanel() {
            if (!activeInput) return;
            const r = activeInput.getBoundingClientRect();
            panel.style.left = r.left + 'px';
            panel.style.top = (r.bottom + 2) + 'px';
            panel.style.width = Math.max(r.width, 320) + 'px';
        }

        function labelOf(p) {
            return p.name + (p.sku ? ' (' + p.sku + ')' : '');
        }

        function chooseProduct(tr, p) {
            const input = tr.querySelector('.product-search');
            field(tr, 'product_id').value = p.id;
            input.value = labelOf(p);
            input.dataset.selectedLabel = input.value;
            input.classList.remove('is-invalid');
            const setIfEmpty = (name, value) => {
                const el = field(tr, name);
                if (el && value !== null && value !== undefined && value !== '' && el.value === '') el.value = value;
            };
            setIfEmpty('description', p.name);
            const costInput = field(tr, 'unit_cost');
            setIfEmpty('unit_cost', p.suggested_cost);
            if (costInput) {
                costInput.title = p.suggested_cost ? 'Sugerido: ' + p.cost_source : '';
                // Sin costo conocido se deja vacío (obligatorio): el precio de venta es solo referencia
                costInput.placeholder = p.suggested_cost ? '' : (p.price ? 'venta ' + Number(p.price).toFixed(2) : 'costo');
            }
            // Unidad e IVA son menús: se ajustan solo si el producto trae un valor que exista en la lista
            const pick = (name, value) => {
                const el = field(tr, name);
                if (!el || value === null || value === undefined || value === '') return;
                const wanted = String(value).toLowerCase();
                const opt = Array.from(el.options).find((o) => o.value.toLowerCase() === wanted
                    || o.textContent.trim().toLowerCase().startsWith(wanted));
                if (opt) el.value = opt.value;
            };
            pick('unit', p.default_unit);
            if (Number(p.tax_rate) > 0) pick('tax_rate', Number(p.tax_rate));
            recalcRow(tr);
            closePanel();
            const next = (costInput && costInput.value === '' && field(tr, 'quantity').value !== '') ? costInput : field(tr, 'quantity');
            (next || input).focus();
        }

        function renderResults(tr, products, term) {
            panel.innerHTML = '';
            products.forEach((p) => {
                const b = document.createElement('button');
                b.type = 'button';
                b.className = 'list-group-item list-group-item-action py-1';
                const cost = p.suggested_cost
                    ? ' · costo ' + Number(p.suggested_cost).toFixed(2) + ' (' + p.cost_source + ')'
                    : ' · sin costo previo' + (p.price ? ', venta ' + Number(p.price).toFixed(2) : '');
                b.innerHTML = '<div class="fw-semibold"></div><div class="small text-muted"></div>';
                b.firstChild.textContent = p.name;
                b.lastChild.textContent = (p.sku ? 'SKU ' + p.sku : 'sin SKU') + (p.default_unit ? ' · ' + p.default_unit : '') + cost;
                b.addEventListener('mousedown', (e) => { e.preventDefault(); chooseProduct(tr, p); });
                panel.appendChild(b);
            });
            const create = document.createElement('button');
            create.type = 'button';
            create.className = 'list-group-item list-group-item-action py-2 text-primary';
            create.textContent = term ? '+ Crear producto nuevo «' + term + '»' : '+ Crear producto nuevo';
            create.addEventListener('mousedown', (e) => { e.preventDefault(); closePanel(); openCreateModal(tr, term); });
            panel.appendChild(create);
            if (!products.length) {
                const none = document.createElement('div');
                none.className = 'list-group-item small text-muted py-1';
                none.textContent = term ? 'Sin resultados para «' + term + '».' : 'Escribe para buscar…';
                panel.insertBefore(none, create);
            }
            panel.classList.remove('d-none');
            placePanel();
            highlighted = -1;
        }

        async function runSearch(input) {
            const tr = rowOf(input);
            const term = input.value.trim();
            const mine = ++seq;
            try {
                const currency = (form.querySelector('[name="currency"]') || {}).value === 'VES' ? 'VES' : 'USD';
                const res = await fetch(searchUrl + '?q=' + encodeURIComponent(term) + '&currency=' + currency, { headers: { 'Accept': 'application/json' } });
                if (!res.ok) throw new Error('HTTP ' + res.status);
                const data = await res.json();
                if (mine !== seq || activeInput !== input) return;
                renderResults(tr, data.products || [], term);
            } catch (e) {
                panel.innerHTML = '<div class="list-group-item small text-danger">No se pudo buscar. Revisa la conexión.</div>';
                panel.classList.remove('d-none');
                placePanel();
            }
        }

        itemsBody.addEventListener('focusin', (e) => {
            if (!e.target.classList.contains('product-search')) return;
            activeInput = e.target;
            runSearch(e.target);
        });

        itemsBody.addEventListener('input', (e) => {
            if (!e.target.classList.contains('product-search')) return;
            const input = e.target;
            activeInput = input;
            // Escribir de nuevo invalida el producto elegido
            if (input.value !== input.dataset.selectedLabel) {
                field(rowOf(input), 'product_id').value = '';
            }
            clearTimeout(timer);
            timer = setTimeout(() => runSearch(input), 200);
        });

        itemsBody.addEventListener('keydown', (e) => {
            if (!e.target.classList.contains('product-search') || panel.classList.contains('d-none')) return;
            const items = Array.from(panel.querySelectorAll('button'));
            if (e.key === 'ArrowDown' || e.key === 'ArrowUp') {
                e.preventDefault();
                highlighted = (highlighted + (e.key === 'ArrowDown' ? 1 : -1) + items.length) % items.length;
                items.forEach((b, i) => b.classList.toggle('active', i === highlighted));
            } else if (e.key === 'Enter') {
                e.preventDefault();
                const target = items[highlighted >= 0 ? highlighted : 0];
                if (target) target.dispatchEvent(new MouseEvent('mousedown', { bubbles: true, cancelable: true }));
            } else if (e.key === 'Escape') {
                closePanel();
            }
        });

        itemsBody.addEventListener('focusout', (e) => {
            if (e.target.classList.contains('product-search')) setTimeout(closePanel, 150);
        });

        itemsBody.addEventListener('click', (e) => {
            if (!e.target.classList.contains('product-clear')) return;
            const tr = rowOf(e.target);
            const input = tr.querySelector('.product-search');
            field(tr, 'product_id').value = '';
            input.value = '';
            input.dataset.selectedLabel = '';
            input.focus();
        });

        window.addEventListener('scroll', placePanel, true);
        window.addEventListener('resize', placePanel);

        // Aviso si un renglón tiene texto en el buscador pero no se eligió ningún producto
        form.addEventListener('submit', (e) => {
            const loose = Array.from(itemsBody.querySelectorAll('tr')).filter((tr) => {
                const input = tr.querySelector('.product-search');
                return input && input.value.trim() !== '' && !field(tr, 'product_id').value;
            });
            if (loose.length && !confirm('Hay ' + loose.length + ' renglón(es) con texto pero sin producto elegido: se guardarán sin entrada de inventario. ¿Guardar de todos modos?')) {
                e.preventDefault();
                loose[0].querySelector('.product-search').classList.add('is-invalid');
                loose[0].querySelector('.product-search').focus();
            }
        });

        // ---------- Modal: crear producto ----------
        const modalEl = document.getElementById('quick-product-modal');
        const modal = new bootstrap.Modal(modalEl);
        const qForm = document.getElementById('quick-product-form');
        const qError = document.getElementById('quick-product-error');
        let modalRow = null;

        function openCreateModal(tr, term) {
            modalRow = tr;
            qForm.reset();
            qError.classList.add('d-none');
            qForm.elements.name.value = term || '';
            const unit = field(tr, 'unit'); const cost = field(tr, 'unit_cost'); const tax = field(tr, 'tax_rate');
            if (unit && unit.value) qForm.elements.default_unit.value = unit.value;
            if (cost && cost.value) qForm.elements.cost.value = cost.value;
            if (tax && tax.value) qForm.elements.tax_rate.value = String(parseFloat(tax.value));
            modal.show();
        }

        modalEl.addEventListener('shown.bs.modal', () => qForm.elements.name.focus());

        qForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            qError.classList.add('d-none');
            const payload = Object.fromEntries(new FormData(qForm).entries());
            payload.is_stock_tracked = qForm.elements.is_stock_tracked.checked ? 1 : 0;
            const btn = document.getElementById('quick-product-save');
            btn.disabled = true;
            try {
                const res = await fetch(createUrl, {
                    method: 'POST',
                    headers: { 'Accept': 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf },
                    body: JSON.stringify(payload),
                });
                const data = await res.json().catch(() => ({}));
                if (!res.ok) {
                    const msgs = data.errors ? Object.values(data.errors).flat() : [data.message || 'No se pudo crear el producto.'];
                    qError.innerHTML = '';
                    msgs.forEach((m) => { const d = document.createElement('div'); d.textContent = m; qError.appendChild(d); });
                    if (data.existing) {
                        const use = document.createElement('button');
                        use.type = 'button';
                        use.className = 'btn btn-sm btn-outline-primary mt-2';
                        use.textContent = 'Usar el existente';
                        use.addEventListener('click', () => { modal.hide(); chooseProduct(modalRow, data.existing); });
                        qError.appendChild(use);
                    }
                    qError.classList.remove('d-none');
                    return;
                }
                modal.hide();
                chooseProduct(modalRow, data.product);
            } catch (err) {
                qError.textContent = 'No se pudo crear el producto. Revisa la conexión.';
                qError.classList.remove('d-none');
            } finally {
                btn.disabled = false;
            }
        });
        updateSummary();
    })();
</script>
@endpush
