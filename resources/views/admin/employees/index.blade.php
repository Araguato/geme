@extends('layout')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1>Empleados</h1>
        <div>
            <a href="{{ route('employees.create') }}" class="btn btn-primary" id="employeesCreateBtn">Nuevo empleado</a>
        </div>
    </div>

    @if(session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif

    <table class="table table-striped align-middle" id="employeesTable">
        <thead>
        <tr>
            <th>Nombre</th>
            <th>Documento</th>
            <th>Teléfono</th>
            <th>Usuario</th>
            <th>Rol / Cargo</th>
            <th>Fecha ingreso</th>
            <th>Salario</th>
            <th>Activo</th>
            <th class="text-end">Acciones</th>
        </tr>
        </thead>
        <tbody>
        @forelse($employees as $employee)
            @php($party = $employee->party)
            <tr>
                <td>{{ $party?->name }}</td>
                <td>
                    @if($party?->document_type)
                        {{ $party->document_type }} {{ $party->document_number }}
                    @else
                        <span class="text-muted">Sin CI/RIF</span>
                    @endif
                </td>
                <td>{{ $party?->phone }}</td>
                <td>{{ $employee->user?->name ?? '—' }}</td>
                <td>{{ $employee->role ?: '—' }}</td>
                <td>{{ $employee->hire_date ? $employee->hire_date->format('d/m/Y') : '—' }}</td>
                <td>
                    @if($employee->salary_type === 'mensual' && $employee->monthly_salary !== null)
                        Mensual: {{ number_format($employee->monthly_salary, 2) }}
                    @elseif($employee->salary_type === 'por_hora' && $employee->hourly_rate !== null)
                        Por hora: {{ number_format($employee->hourly_rate, 2) }}
                    @else
                        <span class="text-muted">Sin definir</span>
                    @endif
                </td>
                <td>
                    @if($party?->is_active && $employee->is_current)
                        <span class="badge bg-success">Activo</span>
                    @else
                        <span class="badge bg-secondary">Inactivo</span>
                    @endif
                </td>
                <td class="text-end">
                    <a href="{{ route('employees.edit', $employee) }}" class="btn btn-sm btn-primary">Editar</a>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="8" class="text-muted">Aún no hay empleados registrados.</td>
            </tr>
        @endforelse
        </tbody>
    </table>

    {{ $employees->links() }}

    <script>
        window.GEME_TOUR_STEPS = [
            { intro: 'Gestión de empleados. Aquí registras el personal, sus datos laborales, salarios y vínculos con usuarios del sistema.' },
            { element: '#employeesCreateBtn', intro: 'Crea un nuevo empleado. Puedes vincularlo a un usuario existente o dejarlo sin usuario.' },
            { element: '#employeesTable', intro: 'Listado de empleados con documento, teléfono, salario, estado y vínculo de usuario. Usa Editar para actualizar datos.' }
        ];
    </script>
@endsection
