@extends('layouts.public')

@section('title', 'Confirmar pedido - ' . \App\Models\Setting::get('business_name', config('app.name')))

@section('content')
<section class="hero">
    <div class="container text-center">
        <h1 class="display-5 fw-bold mb-3">Confirmar pedido</h1>
        <p class="lead mb-0">Ingresa tus datos. El pago se realiza al retirar o en la entrega.</p>
    </div>
</section>

<section class="py-5">
    <div class="container">
        <div class="row g-4">
            <div class="col-lg-7">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title mb-3">Tus datos</h5>
                        <form action="{{ route('public.order.store') }}" method="POST">
                            @csrf

                            <div class="mb-3">
                                <label for="customer_name" class="form-label">Nombre completo <span class="text-danger">*</span></label>
                                <input type="text" id="customer_name" name="customer_name" class="form-control @error('customer_name') is-invalid @enderror" value="{{ old('customer_name') }}" required>
                                @error('customer_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>

                            <div class="mb-3">
                                <label for="customer_phone" class="form-label">Teléfono <span class="text-danger">*</span></label>
                                <input type="text" id="customer_phone" name="customer_phone" class="form-control @error('customer_phone') is-invalid @enderror" value="{{ old('customer_phone') }}" required>
                                @error('customer_phone')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>

                            <div class="mb-3">
                                <label for="customer_email" class="form-label">Correo electrónico</label>
                                <input type="email" id="customer_email" name="customer_email" class="form-control @error('customer_email') is-invalid @enderror" value="{{ old('customer_email') }}">
                                @error('customer_email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>

                            <div class="mb-3">
                                <label for="address" class="form-label">Dirección de entrega / retiro <span class="text-danger">*</span></label>
                                <textarea id="address" name="address" rows="3" class="form-control @error('address') is-invalid @enderror" required>{{ old('address') }}</textarea>
                                @error('address')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>

                            <div class="mb-3">
                                <label for="city" class="form-label">Ciudad / zona</label>
                                <input type="text" id="city" name="city" class="form-control @error('city') is-invalid @enderror" value="{{ old('city') }}">
                                @error('city')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>

                            <div class="mb-3">
                                <label for="instructions" class="form-label">Instrucciones adicionales</label>
                                <textarea id="instructions" name="instructions" rows="2" class="form-control @error('instructions') is-invalid @enderror">{{ old('instructions') }}</textarea>
                                @error('instructions')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>

                            <div class="d-flex justify-content-between mt-4">
                                <a href="{{ route('public.cart') }}" class="btn btn-outline-secondary">Volver al pedido</a>
                                <button type="submit" class="btn btn-success btn-lg">Enviar pedido</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-lg-5">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title mb-3">Resumen</h5>
                        <ul class="list-group list-group-flush mb-3">
                            @php $grandTotal = 0; @endphp
                            @foreach($cart as $item)
                                @php $lineTotal = $item['price'] * $item['quantity']; $grandTotal += $lineTotal; @endphp
                                <li class="list-group-item d-flex justify-content-between align-items-start">
                                    <div>
                                        <div class="fw-semibold">{{ $item['name'] }}</div>
                                        <small class="text-muted">{{ number_format($item['quantity'], 3) }} x $ {{ number_format($item['price'], 2) }}</small>
                                    </div>
                                    <span class="fw-bold">$ {{ number_format($lineTotal, 2) }}</span>
                                </li>
                            @endforeach
                        </ul>
                        <div class="d-flex justify-content-between align-items-center border-top pt-3">
                            <span class="fw-bold">Total estimado</span>
                            <span class="fw-bold text-success fs-5">$ {{ number_format($grandTotal, 2) }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection
