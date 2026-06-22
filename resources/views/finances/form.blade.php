@extends('layout')

@section('content')
    <h1>
        @if($mode === 'edit')
            Editar gasto / consumo
        @else
            Nuevo gasto / consumo
        @endif
    </h1>

    @if($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ $mode === 'edit' ? route('finances.update', $expense) : route('finances.store') }}" class="mt-3" style="max-width: 600px;">
        @csrf
        @if($mode === 'edit')
            @method('PUT')
        @endif

        <div class="mb-3">
            <label class="form-label">Fecha</label>
            <input type="date" name="date" value="{{ old('date', optional($expense->date)->toDateString()) }}" class="form-control" required>
        </div>

        <div class="mb-3">
            <label class="form-label">Fecha de vencimiento (opcional)</label>
            <input type="date" name="due_date" value="{{ old('due_date', optional($expense->due_date)->toDateString()) }}" class="form-control">
            <small class="form-text text-muted">Dejar vacío si no es una cuenta por pagar.</small>
        </div>

        <div class="mb-3">
            <label class="form-label">Monto</label>
            <input type="number" step="0.01" min="0" name="amount" value="{{ old('amount', $expense->amount) }}" class="form-control" required>
        </div>

        <div class="mb-3">
            <label class="form-label">Tipo</label>
            @php($currentType = old('type', $expense->type ?? 'business'))
            <div class="form-check">
                <input class="form-check-input" type="radio" name="type" id="type_business" value="business" {{ $currentType === 'business' ? 'checked' : '' }}>
                <label class="form-check-label" for="type_business">Gasto de negocio</label>
            </div>
            <div class="form-check">
                <input class="form-check-input" type="radio" name="type" id="type_personal" value="personal" {{ $currentType === 'personal' ? 'checked' : '' }}>
                <label class="form-check-label" for="type_personal">Consumo privado</label>
            </div>
        </div>

        <div class="mb-3">
            <label class="form-label">Categoría</label>
            <select name="expense_category_id" class="form-select" required>
                <option value="">Seleccione...</option>
                @php($currentCat = old('expense_category_id', $expense->expense_category_id))
                @foreach($categories as $cat)
                    <option value="{{ $cat->id }}" {{ (string) $currentCat === (string) $cat->id ? 'selected' : '' }}>
                        {{ $cat->name }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="mb-3">
            <label class="form-label">Forma de pago (opcional)</label>
            <input type="text" name="payment_method" value="{{ old('payment_method', $expense->payment_method) }}" class="form-control" placeholder="Efectivo, tarjeta, transferencia, ...">
        </div>

        <div class="mb-3">
            <label class="form-label">Pagado el (opcional)</label>
            <input type="date" name="paid_at" value="{{ old('paid_at', optional($expense->paid_at)->toDateString()) }}" class="form-control">
        </div>

        <div class="mb-3">
            <label class="form-label">Nota (opcional)</label>
            <textarea name="note" class="form-control" rows="3">{{ old('note', $expense->note) }}</textarea>
        </div>

        <button type="submit" class="btn btn-primary">Guardar</button>
        <a href="{{ route('finances.index') }}" class="btn btn-secondary">Cancelar</a>
    </form>
@endsection
