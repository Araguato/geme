@extends('layout')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1>Registro de logins</h1>
    </div>

    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('login-logs.index') }}" class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">Correo</label>
                    <input type="text" name="email" class="form-control" value="{{ request('email') }}" placeholder="Buscar por correo">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Estado</label>
                    <select name="status" class="form-select">
                        <option value="">Todos</option>
                        <option value="success" {{ request('status') === 'success' ? 'selected' : '' }}>Exitoso</option>
                        <option value="failed" {{ request('status') === 'failed' ? 'selected' : '' }}>Fallido</option>
                    </select>
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary w-100">Filtrar</button>
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <a href="{{ route('login-logs.index') }}" class="btn btn-secondary w-100">Limpiar</a>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-body table-responsive">
            <table class="table table-striped align-middle">
                <thead>
                    <tr>
                        <th>Fecha y hora</th>
                        <th>Usuario</th>
                        <th>Correo</th>
                        <th>IP</th>
                        <th>Estado</th>
                        <th>Navegador</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($logs as $log)
                        <tr>
                            <td>{{ $log->created_at?->format('d/m/Y H:i:s') }}</td>
                            <td>{{ $log->user?->name ?? '—' }}</td>
                            <td>{{ $log->email ?? '—' }}</td>
                            <td>{{ $log->ip_address ?? '—' }}</td>
                            <td>
                                @if($log->status === 'success')
                                    <span class="badge bg-success">Exitoso</span>
                                @else
                                    <span class="badge bg-danger">Fallido</span>
                                @endif
                            </td>
                            <td class="text-muted small" style="max-width: 300px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                                {{ $log->user_agent }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-muted">No hay registros de login.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>

            {{ $logs->links() }}
        </div>
    </div>
@endsection
