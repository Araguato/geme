@extends('layouts.public')

@section('title', 'Tu pedido - ' . \App\Models\Setting::get('business_name', config('app.name')))

@section('content')
<section class="hero">
    <div class="container text-center">
        <h1 class="display-5 fw-bold mb-3">Tu pedido</h1>
        <p class="lead mb-0">Revisa los productos antes de confirmar.</p>
    </div>
</section>

<section class="py-5">
    <div class="container">
        @if(count($cart) > 0)
            <div class="table-responsive">
                <table class="table align-middle">
                    <thead>
                        <tr>
                            <th>Producto</th>
                            <th class="text-end">Precio</th>
                            <th class="text-center">Cantidad</th>
                            <th class="text-end">Total</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @php $grandTotal = 0; @endphp
                        @foreach($cart as $item)
                            @php $lineTotal = $item['price'] * $item['quantity']; $grandTotal += $lineTotal; @endphp
                            <tr>
                                <td>{{ $item['name'] }}</td>
                                <td class="text-end">$ {{ number_format($item['price'], 2) }}</td>
                                <td class="text-center">{{ number_format($item['quantity'], 3) }}</td>
                                <td class="text-end">$ {{ number_format($lineTotal, 2) }}</td>
                                <td class="text-end">
                                    <form action="{{ route('public.cart.remove') }}" method="POST" class="d-inline">
                                        @csrf
                                        <input type="hidden" name="product_id" value="{{ $item['product_id'] }}">
                                        <button type="submit" class="btn btn-sm btn-outline-danger">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr>
                            <th colspan="3" class="text-end">Total estimado</th>
                            <th class="text-end">$ {{ number_format($grandTotal, 2) }}</th>
                            <th></th>
                        </tr>
                    </tfoot>
                </table>
            </div>

            <div class="d-flex justify-content-between mt-4">
                <a href="{{ route('public.order.index') }}" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left"></i> Seguir agregando
                </a>
                <a href="{{ route('public.checkout') }}" class="btn btn-success btn-lg">
                    Confirmar pedido <i class="bi bi-arrow-right"></i>
                </a>
            </div>
        @else
            <div class="text-center py-5">
                <p class="text-muted fs-5">Tu pedido está vacío.</p>
                <a href="{{ route('public.order.index') }}" class="btn btn-success">Ver productos</a>
            </div>
        @endif
    </div>
</section>
@endsection
