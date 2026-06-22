@extends('layout')

@section('content')
<h1 class="h3 mb-3" id="usersTitle">Usuarios</h1>

<div class="mb-3" id="usersActions">
    <a href="{{ route('users.create') }}" class="btn btn-primary btn-sm" id="usersCreateBtn">Nuevo usuario</a>
</div>

<table class="table table-striped table-sm align-middle" id="usersTable">
    <thead>
    <tr>
        <th>Nombre</th>
        <th>Email</th>
        <th>Roles</th>
        <th class="text-end">Acciones</th>
    </tr>
    </thead>
    <tbody>
    @foreach($users as $user)
        <tr>
            <td>{{ $user->name }}</td>
            <td>{{ $user->email }}</td>
            <td>
                @if($user->roles->isEmpty())
                    <span class="text-muted">Sin rol</span>
                @else
                    {{ $user->roles->pluck('name')->join(', ') }}
                @endif
            </td>
            <td class="text-end">
                <a href="{{ route('users.edit', $user) }}" class="btn btn-sm btn-outline-secondary">Editar</a>
            </td>
        </tr>
    @endforeach
    </tbody>
</table>

<script>
    window.WAWI_TOUR_STEPS = [
        {
            intro: 'Gestión de usuarios del sistema. Aquí asignas accesos y roles.'
        },
        {
            element: '#usersCreateBtn',
            intro: 'Crea un usuario nuevo y asígnale roles (admin, cajero, despachador, etc.).'
        },
        {
            element: '#usersTable',
            intro: 'Listado de usuarios con sus roles. En “Editar” puedes cambiar roles, datos y PIN.'
        }
    ];
</script>
@endsection
