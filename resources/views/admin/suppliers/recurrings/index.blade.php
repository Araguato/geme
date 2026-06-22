@extends('layout')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h1 class="mb-0">Facturas recurrentes</h1>
            <div class="small text-muted mt-1">
                Proveedores se gestionan en <a href="{{ route('suppliers.index') }}" class="text-decoration-none">Administración &rarr; Proveedores</a>.
            </div>
        </div>
        <div class="d-flex gap-2">
            <form action="{{ route('recurring-supplier-invoices.generate') }}" method="POST" onsubmit="return confirm('¿Generar facturas para todas las recurrentes vencidas hasta hoy?');">
                @csrf
                <button type="submit" class="btn btn-success">Generar ahora</button>
            </form>
            <a href="{{ route('recurring-supplier-invoices.create') }}" class="btn btn-primary">Nueva regla</a>
        </div>
    </div>

    @if(session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif

    <table class="table table-striped align-middle">
        <thead>
        <tr>
            <th>Proveedor</th>
            <th>Descripción</th>
            <th>Importe base</th>
            <th>Moneda</th>
            <th>Intervalo</th>
            <th>Próximo vencimiento</th>
            <th>Activa</th>
            <th class="text-end">Acciones</th>
        </tr>
        </thead>
        <tbody>
        @forelse($recurrings as $rec)
            @php($party = $rec->supplier->party ?? null)
            <tr>
                <td>{{ $party?->name ?? 'Proveedor #'.$rec->supplier_id }}</td>
                <td>{{ $rec->description }}</td>
                <td>
                    @if($rec->currency === 'USD')
                        {{ number_format($rec->base_amount_usd, 2) }}
                    @else
                        {{ number_format($rec->base_amount_bs, 2) }}
                    @endif
                </td>
                <td>{{ $rec->currency }}</td>
                <td>
                    @if($rec->interval === 'monthly')
                        Mensual
                    @elseif($rec->interval === 'weekly')
                        Semanal
                    @else
                        Anual
                    @endif
                </td>
                <td>{{ optional($rec->next_due_date)->format('d/m/Y') }}</td>
                <td>
                    @if($rec->is_active)
                        <span class="badge bg-success">Sí</span>
                    @else
                        <span class="badge bg-secondary">No</span>
                    @endif
                </td>
                <td class="text-end">
                    <a href="{{ route('recurring-supplier-invoices.edit', $rec) }}" class="btn btn-sm btn-primary">Editar</a>
                    <form action="{{ route('recurring-supplier-invoices.destroy', $rec) }}" method="POST" class="d-inline"
                          onsubmit="return confirm('¿Eliminar esta regla recurrente?');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-sm btn-danger">X</button>
                    </form>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="8" class="text-muted">Aún no hay reglas recurrentes configuradas.</td>
            </tr>
        @endforelse
        </tbody>
    </table>

    {{ $recurrings->links() }}
@endsection
