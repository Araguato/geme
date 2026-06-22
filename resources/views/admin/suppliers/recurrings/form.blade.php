@extends('layout')

@section('content')
    <h1>{{ $mode === 'edit' ? 'Editar factura recurrente' : 'Nueva factura recurrente' }}</h1>

    @if($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ $mode === 'edit' ? route('recurring-supplier-invoices.update', $recurring) : route('recurring-supplier-invoices.store') }}" class="mt-3">
        @csrf
        @if($mode === 'edit')
            @method('PUT')
        @endif

        <div class="row g-3 mb-3">
            <div class="col-md-5">
                <div class="d-flex justify-content-between align-items-center">
                    <label class="form-label mb-0">Proveedor *</label>
                    <a href="{{ route('suppliers.create') }}" class="small text-decoration-none">+ Nuevo proveedor</a>
                </div>
                <select name="supplier_id" class="form-select" required>
                    <option value="">Seleccione...</option>
                    @foreach($suppliers as $supplier)
                        @php($party = $supplier->party)
                        <option value="{{ $supplier->id }}" @selected(old('supplier_id', $recurring->supplier_id) == $supplier->id)>
                            {{ $party?->name ?? 'Proveedor #'.$supplier->id }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-5">
                <label class="form-label">Descripción *</label>
                <input type="text" name="description" class="form-control" required
                       value="{{ old('description', $recurring->description) }}">
            </div>
            <div class="col-md-2 d-flex align-items-center">
                <div class="form-check mt-4">
                    <input class="form-check-input" type="checkbox" name="is_active" id="is_active"
                           value="1" @checked(old('is_active', $recurring->is_active))>
                    <label class="form-check-label" for="is_active">Activa</label>
                </div>
            </div>
        </div>

        <div class="row g-3 mb-3">
            <div class="col-md-2">
                <label class="form-label">Moneda</label>
                @php($cur = old('currency', $recurring->currency ?? 'USD'))
                <select name="currency" class="form-select">
                    <option value="USD" @selected($cur === 'USD')>USD</option>
                    <option value="VES" @selected($cur === 'VES')>Bs</option>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Importe base en USD</label>
                <input type="number" step="0.01" min="0" name="base_amount_usd" class="form-control"
                       value="{{ old('base_amount_usd', $recurring->base_amount_usd) }}">
            </div>
            <div class="col-md-3">
                <label class="form-label">Importe base en Bs</label>
                <input type="number" step="0.01" min="0" name="base_amount_bs" class="form-control"
                       value="{{ old('base_amount_bs', $recurring->base_amount_bs) }}">
            </div>
            <div class="col-md-4">
                <label class="form-label">Intervalo *</label>
                @php($interval = old('interval', $recurring->interval ?? 'monthly'))
                <select name="interval" class="form-select">
                    <option value="monthly" @selected($interval === 'monthly')>Mensual</option>
                    <option value="weekly" @selected($interval === 'weekly')>Semanal</option>
                    <option value="yearly" @selected($interval === 'yearly')>Anual</option>
                </select>
            </div>
        </div>

        <div class="row g-3 mb-3">
            <div class="col-md-4">
                <label class="form-label">Próximo vencimiento *</label>
                <input type="date" name="next_due_date" class="form-control" required
                       value="{{ old('next_due_date', optional($recurring->next_due_date)->format('Y-m-d') ?? now()->format('Y-m-d')) }}">
            </div>
            <div class="col-md-4">
                <label class="form-label">Día del mes (opcional)</label>
                <input type="number" min="1" max="31" name="day_of_month" class="form-control"
                       value="{{ old('day_of_month', $recurring->day_of_month) }}">
                <div class="form-text">Usado principalmente para intervalos mensuales/anuales.</div>
            </div>
            <div class="col-md-4">
                <label class="form-label">Día de la semana (1=Lunes ... 7=Domingo, opcional)</label>
                <input type="number" min="1" max="7" name="day_of_week" class="form-control"
                       value="{{ old('day_of_week', $recurring->day_of_week) }}">
            </div>
        </div>

        <button type="submit" class="btn btn-primary">Guardar</button>
        <a href="{{ route('recurring-supplier-invoices.index') }}" class="btn btn-secondary">Cancelar</a>
    </form>
@endsection
