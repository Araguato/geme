@extends('layout')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1>Proveedores</h1>
        <a href="{{ route('suppliers.create') }}" class="btn btn-primary">Nuevo proveedor</a>
    </div>

    @if(session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif

    <table class="table table-striped align-middle">
        <thead>
        <tr>
            <th>Nombre</th>
            <th>Documento</th>
            <th>Teléfono</th>
            <th>Correo</th>
            <th>Condición de pago</th>
            <th>Activo</th>
            <th class="text-end">Acciones</th>
        </tr>
        </thead>
        <tbody>
        @forelse($suppliers as $supplier)
            @php($party = $supplier->party)
            <tr>
                <td>{{ $party?->name }}</td>
                <td>
                    @if($party?->document_type)
                        {{ $party->document_type }} {{ $party->document_number }}
                    @else
                        <span class="text-muted">Sin RIF/CI</span>
                    @endif
                </td>
                <td>{{ $party?->phone }}</td>
                <td>{{ $party?->email }}</td>
                <td>{{ $supplier->payment_terms ?: '—' }}</td>
                <td>
                    @if($party?->is_active)
                        <span class="badge bg-success">Activo</span>
                    @else
                        <span class="badge bg-secondary">Inactivo</span>
                    @endif
                </td>
                <td class="text-end">
                    <a href="{{ route('suppliers.edit', $supplier) }}" class="btn btn-sm btn-primary">Editar</a>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="7" class="text-muted">Aún no hay proveedores registrados.</td>
            </tr>
        @endforelse
        </tbody>
    </table>

    {{ $suppliers->links() }}
@endsection
