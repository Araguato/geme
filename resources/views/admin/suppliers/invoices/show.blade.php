@extends('layout')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1>Factura de proveedor</h1>
        <div>
            <a href="{{ route('supplier-invoices.edit', $invoice) }}" class="btn btn-primary me-2">Editar factura</a>
            <a href="{{ route('supplier-invoices.index') }}" class="btn btn-secondary">Volver</a>
        </div>
    </div>

    @if(session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif

    @php($party = $invoice->supplier->party ?? null)

    <div class="card mb-3">
        <div class="card-body">
            <div class="row mb-2">
                <div class="col-md-6">
                    <h5 class="card-title mb-1">{{ $party?->name ?? 'Proveedor #'.$invoice->supplier_id }}</h5>
                    @if($party?->document_type)
                        <div class="text-muted">{{ $party->document_type }} {{ $party->document_number }}</div>
                    @endif
                </div>
                <div class="col-md-3">
                    <div><strong>Fecha:</strong> {{ $invoice->date->format('d/m/Y') }}</div>
                    <div><strong>Vence:</strong> {{ $invoice->due_date ? $invoice->due_date->format('d/m/Y') : '—' }}</div>
                </div>
                <div class="col-md-3">
                    <div><strong>N° factura:</strong> {{ $invoice->invoice_number ?: '—' }}</div>
                    <div><strong>N° control:</strong> {{ $invoice->control_number ?: '—' }}</div>
                    <div><strong>Tipo doc:</strong> {{ $invoice->doc_type }}</div>
                </div>
            </div>

            <div class="row mb-2">
                <div class="col-md-4">
                    <div><strong>Moneda:</strong> {{ $invoice->currency }}</div>
                    <div><strong>BCV emisión:</strong> {{ number_format($invoice->bcv_rate_at_issue, 6) }}</div>
                    <div><strong>Fuente tasa:</strong> {{ $invoice->currency_rate_source ?: '—' }}</div>
                </div>
                <div class="col-md-4">
                    <div><strong>Documento afectado:</strong> {{ $invoice->affected_document ?: '—' }}</div>
                    <div><strong>Monto base (USD):</strong> {{ number_format($invoice->amount_usd, 2) }}</div>
                    @if($invoice->amount_bs)
                        <div><strong>Monto original (Bs):</strong> {{ number_format($invoice->amount_bs, 2) }}</div>
                    @endif
                </div>
                <div class="col-md-4">
                    <div><strong>Pagado (USD equiv.):</strong> {{ number_format($invoice->paid_usd, 2) }}</div>
                    <div><strong>Saldo (USD):</strong> {{ number_format($invoice->remaining_usd, 2) }}</div>
                    <div><strong>Saldo estimado en Bs (BCV actual {{ number_format($bcvRate, 2) }}):</strong></div>
                    <div>{{ number_format($invoice->remaining_usd * $bcvRate, 2) }} Bs</div>
                </div>
            </div>

            <div class="mb-2">
                <strong>Estado:</strong>
                @if($invoice->status === 'pagada')
                    <span class="badge bg-success">Pagada</span>
                @elseif($invoice->status === 'parcial')
                    <span class="badge bg-warning text-dark">Parcial</span>
                @else
                    <span class="badge bg-danger">Pendiente</span>
                @endif
            </div>

            @if($invoice->notes)
                <div class="mt-2">
                    <strong>Notas:</strong>
                    <div>{{ $invoice->notes }}</div>
                </div>
            @endif
        </div>
    </div>

    @if($invoice->items->isNotEmpty())
        <div class="card mb-4">
            <div class="card-body">
                <h3 class="h5 mb-3">Detalle de renglones</h3>
                <div class="table-responsive">
                    <table class="table table-striped align-middle mb-0">
                        <thead>
                        <tr>
                            <th>Producto</th>
                            <th>Descripción</th>
                            <th class="text-end">Cantidad</th>
                            <th>Unidad</th>
                            <th class="text-end">Costo unitario</th>
                            <th class="text-end">IVA %</th>
                            <th class="text-end">Impuesto</th>
                            <th class="text-end">Subtotal</th>
                            <th class="text-end">Total</th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach($invoice->items as $item)
                            <tr>
                                <td>{{ $item->product?->name ?? 'N/A' }}</td>
                                <td>{{ $item->description ?: '—' }}</td>
                                <td class="text-end">{{ number_format($item->quantity, 4) }}</td>
                                <td>{{ $item->unit ?: $item->product?->default_unit ?: '—' }}</td>
                                <td class="text-end">{{ number_format($item->unit_cost, 2) }}</td>
                                <td class="text-end">{{ number_format($item->tax_rate * 100, 2) }}</td>
                                <td class="text-end">{{ number_format($item->tax_amount, 2) }}</td>
                                <td class="text-end">{{ number_format($item->subtotal, 2) }}</td>
                                <td class="text-end">{{ number_format($item->total, 2) }}</td>
                            </tr>
                        @endforeach
                        </tbody>
                        <tfoot>
                        <tr class="table-light">
                            <th colspan="6" class="text-end">Totales</th>
                            <th class="text-end">{{ number_format($invoice->total_tax, 2) }}</th>
                            <th class="text-end">{{ number_format($invoice->total_items, 2) }}</th>
                            <th class="text-end">{{ number_format($invoice->total_amount, 2) }}</th>
                        </tr>
                        </tfoot>
                    </table>
                </div>

                <div class="row mt-3">
                    <div class="col-md-4">
                        <div><strong>Base imponible:</strong> {{ number_format($invoice->taxable_amount, 2) }}</div>
                        <div><strong>Exento:</strong> {{ number_format($invoice->exempt_amount, 2) }}</div>
                    </div>
                    <div class="col-md-4">
                        <div><strong>Impuesto total:</strong> {{ number_format($invoice->total_tax, 2) }}</div>
                        <div><strong>Retenciones:</strong> {{ number_format($invoice->withholding_amount, 2) }}</div>
                    </div>
                    <div class="col-md-4">
                        <div><strong>Total factura:</strong> {{ number_format($invoice->total_amount, 2) }}</div>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <h3>Pagos registrados</h3>

    <table class="table table-striped align-middle mb-3">
        <thead>
        <tr>
            <th>Fecha</th>
            <th class="text-end">USD</th>
            <th class="text-end">Bs</th>
            <th>BCV pago</th>
            <th>Método</th>
            <th>Notas</th>
        </tr>
        </thead>
        <tbody>
        @forelse($invoice->payments as $payment)
            <tr>
                <td>{{ $payment->payment_date->format('d/m/Y') }}</td>
                <td class="text-end">{{ $payment->amount_usd ? number_format($payment->amount_usd, 2) : '—' }}</td>
                <td class="text-end">{{ $payment->amount_bs ? number_format($payment->amount_bs, 2) : '—' }}</td>
                <td>{{ number_format($payment->bcv_rate_at_payment, 2) }}</td>
                <td>{{ $payment->method ?: '—' }}</td>
                <td>{{ $payment->notes ?: '—' }}</td>
            </tr>
        @empty
            <tr>
                <td colspan="6" class="text-muted">Aún no hay pagos registrados.</td>
            </tr>
        @endforelse
        </tbody>
    </table>

    <h3>Registrar pago</h3>

    <form action="{{ route('supplier-invoices.payments.store', $invoice) }}" method="POST" class="row g-3 mt-1">
        @csrf
        <div class="col-md-3">
            <label class="form-label">Fecha *</label>
            <input type="date" name="payment_date" class="form-control" required
                   value="{{ now()->format('Y-m-d') }}">
        </div>
        <div class="col-md-3">
            <label class="form-label">Monto en USD</label>
            <input type="number" name="amount_usd" step="0.01" min="0" class="form-control">
        </div>
        <div class="col-md-3">
            <label class="form-label">Monto en Bs</label>
            <input type="number" name="amount_bs" step="0.01" min="0" class="form-control">
            <div class="form-text">BCV actual: {{ number_format($bcvRate, 2) }} Bs / USD</div>
        </div>
        <div class="col-md-3">
            <label class="form-label">BCV usado</label>
            <input type="number" name="bcv_rate_at_payment" step="0.000001" min="0" class="form-control"
                   value="{{ $bcvRate }}">
        </div>
        <div class="col-md-4">
            <label class="form-label">Método</label>
            <select name="method" class="form-select">
                <option value="">Seleccione...</option>
                <option value="Efectivo USD">Efectivo USD</option>
                <option value="Efectivo Bs">Efectivo Bs</option>
                <option value="Transferencia USD">Transferencia USD</option>
                <option value="Transferencia Bs">Transferencia Bs</option>
                <option value="Zelle">Zelle</option>
                <option value="Pago móvil">Pago móvil</option>
                <option value="Otro">Otro</option>
            </select>
        </div>
        <div class="col-md-8">
            <label class="form-label">Notas</label>
            <input type="text" name="notes" class="form-control">
        </div>
        <div class="col-12">
            <button type="submit" class="btn btn-primary">Guardar pago</button>
        </div>
    </form>
@endsection
