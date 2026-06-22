@extends('layout')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <div>
            <h1 class="mb-0">Editar entrada de nómina</h1>
            <p class="text-muted small mb-0">Ajusta conceptos manuales, horas y notas para el empleado.</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('payroll-entries.show', $entry) }}" class="btn btn-secondary">Ver detalle</a>
            <a href="{{ route('payroll-entries.index', ['run_id' => $entry->payroll_run_id]) }}" class="btn btn-outline-secondary">Volver al listado</a>
        </div>
    </div>

    @if(session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif

    @if($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('payroll-entries.update', $entry) }}" class="card border-0 shadow-sm">
        @csrf
        @method('PUT')

        <div class="card-body">
            <div class="row g-4 mb-4">
                <div class="col-md-4">
                    <h6 class="text-muted text-uppercase fw-semibold">Empleado</h6>
                    <p class="mb-1 fw-semibold">{{ $entry->employee?->party?->name ?? '—' }}</p>
                    <p class="text-muted small mb-0">
                        Documento: {{ $entry->employee?->party?->document_number ?? 'Sin documento' }}<br>
                        Usuario: {{ $entry->employee?->user?->name ?? 'No vinculado' }}
                    </p>
                </div>
                <div class="col-md-4">
                    <h6 class="text-muted text-uppercase fw-semibold">Corrida</h6>
                    <p class="mb-1 fw-semibold">{{ $entry->run?->code ?? 'Corrida #' . $entry->payroll_run_id }}</p>
                    <p class="text-muted small mb-0">
                        Periodo: {{ optional($entry->run?->period?->start_date)->format('d/m/Y') }} –
                        {{ optional($entry->run?->period?->end_date)->format('d/m/Y') }}<br>
                        Estado corrida: {{ ucfirst($entry->run?->status ?? '—') }}
                    </p>
                </div>
                <div class="col-md-4">
                    <h6 class="text-muted text-uppercase fw-semibold">Montos actuales</h6>
                    <ul class="list-unstyled mb-0 small">
                        <li>Ingresos: <strong>{{ number_format($entry->earnings_total, 2) }}</strong></li>
                        <li>Deducciones: <strong>{{ number_format($entry->deductions_total, 2) }}</strong></li>
                        <li>Contribuciones: <strong>{{ number_format($entry->contributions_total, 2) }}</strong></li>
                        <li>Pago neto: <strong class="text-success">{{ number_format($entry->net_pay, 2) }}</strong></li>
                    </ul>
                </div>
            </div>

            <div class="row g-4">
                <div class="col-md-3">
                    <label class="form-label">Horas trabajadas</label>
                    <input type="number" step="0.01" min="0" name="hours_worked" class="form-control"
                           value="{{ old('hours_worked', $entry->hours_worked) }}">
                    <div class="form-text">Opcional. Útil para contratos por hora o incidencias manuales.</div>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Estado de la entrada</label>
                    <select name="status" class="form-select">
                        <option value="">Mantener actual ({{ ucfirst($entry->status) }})</option>
                        @foreach($statusOptions as $value => $label)
                            <option value="{{ $value }}" @selected(old('status') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Notas internas</label>
                    <textarea name="notes" class="form-control" rows="3" maxlength="2000"
                              placeholder="Comentarios o referencias para auditoría">{{ old('notes', $entry->notes) }}</textarea>
                </div>
            </div>

            <hr class="my-4">

            <h5 class="mb-3">Conceptos automáticos</h5>
            <p class="text-muted small">Estos conceptos provienen del contrato y las incidencias del periodo. Para modificarlos, recalcula la corrida o ajusta las incidencias.</p>
            <div class="table-responsive mb-4">
                <table class="table table-striped align-middle">
                    <thead>
                    <tr>
                        <th>Concepto</th>
                        <th>Tipo</th>
                        <th class="text-end">Cantidad</th>
                        <th class="text-end">Tarifa</th>
                        <th class="text-end">Monto</th>
                        <th class="text-center">Tributa</th>
                        <th class="text-center">Seguridad social</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse($autoItems as $item)
                        <tr>
                            <td>{{ $item->concept?->name ?? '—' }}</td>
                            <td>{{ ucfirst($item->type) }}</td>
                            <td class="text-end">{{ $item->quantity !== null ? number_format($item->quantity, 2) : '—' }}</td>
                            <td class="text-end">{{ $item->rate !== null ? number_format($item->rate, 4) : '—' }}</td>
                            <td class="text-end fw-semibold">{{ number_format($item->amount, 2) }}</td>
                            <td class="text-center">
                                <span class="badge {{ $item->is_taxable ? 'bg-success' : 'bg-secondary' }}">
                                    {{ $item->is_taxable ? 'Sí' : 'No' }}
                                </span>
                            </td>
                            <td class="text-center">
                                <span class="badge {{ $item->is_social_security_applicable ? 'bg-success' : 'bg-secondary' }}">
                                    {{ $item->is_social_security_applicable ? 'Sí' : 'No' }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-muted text-center py-3">No hay conceptos automáticos registrados.</td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>

            <h5 class="mb-3">Ajustes manuales existentes</h5>
            <p class="text-muted small">Puedes actualizar montos, cantidades, tarifas o eliminar el ajuste. Cualquier campo vacío en cantidad o tarifa se ignorará.</p>
            <div class="table-responsive mb-4">
                <table class="table table-striped align-middle">
                    <thead>
                    <tr>
                        <th>Concepto</th>
                        <th class="text-end">Cantidad</th>
                        <th class="text-end">Tarifa</th>
                        <th class="text-end">Monto</th>
                        <th class="text-center">Eliminar</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse($manualItems as $item)
                        <tr>
                            <td class="w-25">{{ $item->concept?->name ?? '—' }}</td>
                            <td class="text-end" style="width: 15%">
                                <input type="number" step="0.01" name="existing_manual_items[{{ $item->id }}][quantity]"
                                       class="form-control form-control-sm text-end"
                                       value="{{ old("existing_manual_items.{$item->id}.quantity", $item->quantity) }}">
                            </td>
                            <td class="text-end" style="width: 15%">
                                <input type="number" step="0.01" name="existing_manual_items[{{ $item->id }}][rate]"
                                       class="form-control form-control-sm text-end"
                                       value="{{ old("existing_manual_items.{$item->id}.rate", $item->rate) }}">
                            </td>
                            <td class="text-end" style="width: 15%">
                                <input type="number" step="0.01" name="existing_manual_items[{{ $item->id }}][amount]"
                                       class="form-control form-control-sm text-end"
                                       value="{{ old("existing_manual_items.{$item->id}.amount", $item->amount) }}" required>
                            </td>
                            <td class="text-center" style="width: 10%">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" value="{{ $item->id }}"
                                           name="delete_manual_items[]" id="delete-item-{{ $item->id }}">
                                    <label class="form-check-label" for="delete-item-{{ $item->id }}">
                                        Quitar
                                    </label>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-muted text-center py-3">No hay ajustes manuales registrados.</td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>

            <h5 class="mb-3">Nuevo ajuste manual</h5>
            <p class="text-muted small">Selecciona un concepto y define monto (puedes usar cantidad x tarifa para autocalcular). Puedes dejar filas vacías si no deseas agregar más ajustes.</p>

            @for($i = 0; $i < 3; $i++)
                <div class="row g-3 align-items-end mb-0 pb-2 border-bottom border-light-subtle">
                    <div class="col-md-4">
                        <label class="form-label">Concepto</label>
                        <select name="new_manual_items[{{ $i }}][concept_id]" class="form-select">
                            <option value="">— Seleccionar —</option>
                            @foreach($conceptOptions as $concept)
                                <option value="{{ $concept->id }}"
                                    {{ old("new_manual_items.{$i}.concept_id") == $concept->id ? 'selected' : '' }}>
                                    {{ $concept->code }} · {{ $concept->name }} ({{ ucfirst($concept->type) }})
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Cantidad</label>
                        <input type="number" step="0.01" name="new_manual_items[{{ $i }}][quantity]"
                               class="form-control"
                               value="{{ old("new_manual_items.{$i}.quantity") }}">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Tarifa</label>
                        <input type="number" step="0.01" name="new_manual_items[{{ $i }}][rate]"
                               class="form-control"
                               value="{{ old("new_manual_items.{$i}.rate") }}">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Monto</label>
                        <input type="number" step="0.01" name="new_manual_items[{{ $i }}][amount]"
                               class="form-control"
                               value="{{ old("new_manual_items.{$i}.amount") }}">
                    </div>
                </div>
            @endfor
        </div>

        <div class="card-footer d-flex justify-content-end gap-2 bg-transparent">
            <a href="{{ route('payroll-entries.show', $entry) }}" class="btn btn-outline-secondary">Cancelar</a>
            <button type="submit" class="btn btn-primary">Guardar cambios</button>
        </div>
    </form>
@endsection
