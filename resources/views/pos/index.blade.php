@extends('layout')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h1>TPV / Punto de venta</h1>
    <div class="d-flex align-items-center gap-2">
        @if($activeShift)
            <span class="badge bg-success">Turno abierto</span>
        @else
            <span class="badge bg-danger">Sin turno abierto</span>
        @endif
        <button type="button" class="btn btn-sm btn-outline-primary" onclick="startPosTour()">
            <i class="bi bi-question-circle"></i> Tour
        </button>
    </div>
</div>

@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif

@if(session('error'))
    <div class="alert alert-danger">{{ session('error') }}</div>
@endif

@if(!$activeShift)
<div class="card mb-4">
    <div class="card-body">
        <h5 class="card-title">Abrir turno de caja</h5>
        @if($salesLocations->isEmpty())
            <div class="alert alert-warning mb-3">
                No hay ubicaciones de venta configuradas.
                <a href="{{ route('sales-locations.create') }}">Crea una ubicación</a> para poder abrir el turno.
            </div>
        @endif
        <form action="{{ route('cash-shifts.open') }}" method="POST" class="row g-3">
            @csrf
            <div class="col-md-3">
                <label class="form-label">Ubicación de venta</label>
                <select name="sales_location_id" class="form-select" required @if($salesLocations->isEmpty()) disabled @endif>
                    <option value="">Seleccione...</option>
                    @foreach($salesLocations as $location)
                        <option value="{{ $location->id }}">{{ $location->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Monto inicial en caja</label>
                <input type="number" step="0.01" min="0" name="opening_amount" class="form-control" required @if($salesLocations->isEmpty()) disabled @endif>
            </div>
            <div class="col-md-2 d-flex align-items-end">
                <button type="submit" class="btn btn-primary w-100" @if($salesLocations->isEmpty()) disabled @endif>Abrir caja</button>
            </div>
        </form>
    </div>
</div>
@else
<div class="card mb-4">
    <div class="card-body d-flex justify-content-between align-items-center">
        <div>
            <h5 class="card-title mb-1">Turno abierto</h5>
            <div class="small text-muted">
                Ubicación: <strong>{{ $activeShift->salesLocation?->name ?? 'Sin ubicación' }}</strong>
            </div>
        </div>
        <form action="{{ route('cash-shifts.close') }}" method="POST" class="row g-3">
            @csrf
            <div class="col-auto">
                <label class="form-label">Monto final en caja</label>
                <input type="number" step="0.01" min="0" name="closing_amount" class="form-control" required>
            </div>
            <div class="col-auto d-flex align-items-end">
                <button type="submit" class="btn btn-warning">Cerrar caja</button>
            </div>
        </form>
    </div>
</div>
@endif

@if($activeShift)
<div class="row">
    <div class="col-md-5">
        <div class="card" id="posProductsCard">
            <div class="card-header">Productos</div>
            <div class="card-body" style="max-height: 60vh; overflow-y: auto;">
                <input type="text" id="product-search" class="form-control mb-3" placeholder="Buscar producto...">
                <div class="list-group" id="product-list">
                    @foreach($products as $product)
                        <div class="list-group-item product-item-wrapper p-2">
                            <div class="d-flex align-items-center gap-3">
                                <button type="button" class="btn btn-link p-0 product-info-btn flex-shrink-0"
                                        data-id="{{ $product->id }}"
                                        data-name="{{ $product->name }}"
                                        data-description="{{ $product->description }}"
                                        data-price="{{ $product->price }}"
                                        data-image="{{ $product->mainImage ? asset('storage/' . $product->mainImage->path) : '' }}"
                                        data-images="{{ $product->images->map(fn($img) => asset('storage/' . $img->path))->toJson() }}"
                                        data-url="{{ route('catalog.show', $product) }}"
                                        title="Ver información y QR">
                                    @if($product->mainImage)
                                        <img src="{{ asset('storage/' . $product->mainImage->path) }}" alt="" class="rounded" style="width: 64px; height: 64px; object-fit: cover;">
                                    @else
                                        <div class="rounded bg-secondary-subtle d-flex align-items-center justify-content-center" style="width: 64px; height: 64px;">
                                            <i class="bi bi-image text-secondary fs-4"></i>
                                        </div>
                                    @endif
                                </button>
                                <button type="button" class="list-group-item list-group-item-action border-0 product-add-btn flex-grow-1"
                                        data-id="{{ $product->id }}"
                                        data-name="{{ $product->name }}"
                                        data-price="{{ $product->price }}">
                                    <div class="d-flex justify-content-between">
                                        <span class="fw-medium">{{ $product->name }}</span>
                                        <span>$ {{ number_format($product->price, 2) }}</span>
                                    </div>
                                    <small class="text-muted">{{ $product->category?->name }}</small>
                                </button>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-7">
        <form action="{{ route('pos.store') }}" method="POST" id="pos-form">
            @csrf
            <div class="card" id="posCartCard">
                <div class="card-header">Venta actual</div>
                <div class="card-body">
                    <div class="mb-3" id="posCustomer">
                        <label class="form-label">Cliente (opcional)</label>
                        <div class="input-group">
                            <select name="customer_party_id" class="form-select" id="customer-select">
                                <option value="">-- Cliente contado --</option>
                                @foreach($customers as $customer)
                                    <option value="{{ $customer->id }}" data-rif="{{ $customer->document_number }}" data-name="{{ $customer->name }}">
                                        {{ $customer->name }} — {{ $customer->document_number }}
                                    </option>
                                @endforeach
                            </select>
                            <a href="{{ route('parties.create') }}?type=customer&redirect=pos" class="btn btn-outline-secondary" target="_blank">+</a>
                        </div>
                        <input type="hidden" name="customer_name" id="customer-name-input">
                        <div class="form-text" id="customer-rif-text">RIF: consumidor final</div>
                    </div>
                    <table class="table table-sm" id="cart-table">
                        <thead>
                            <tr>
                                <th>Producto</th>
                                <th style="width: 100px;">Cantidad</th>
                                <th style="width: 120px;">Precio</th>
                                <th style="width: 120px;">Total</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr id="empty-row">
                                <td colspan="5" class="text-center text-muted">Agrega productos</td>
                            </tr>
                        </tbody>
                    </table>
                    <div class="d-flex justify-content-between fs-5 fw-bold">
                        <span>Total:</span>
                        <span id="cart-total">$ 0.00</span>
                    </div>
                </div>
                <div class="card-footer" id="posPayment">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">Método de pago</label>
                            <select name="payment[method]" class="form-select" required>
                                <option value="cash">Efectivo</option>
                                <option value="card">Tarjeta</option>
                                <option value="transfer">Transferencia</option>
                                <option value="other">Otro</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Monto pagado</label>
                            <input type="number" step="0.01" min="0" name="payment[amount]" id="payment-amount" class="form-control" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Referencia</label>
                            <input type="text" name="payment[reference]" class="form-control" placeholder="Número de operación">
                        </div>
                    </div>
                    <button type="submit" class="btn btn-success mt-3 w-100">Finalizar venta</button>
                </div>
            </div>
        </form>
    </div>
</div>

<div class="modal fade" id="productInfoModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="productInfoModalTitle">Producto</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body text-center">
                <img id="productInfoModalImage" src="" alt="" class="img-fluid rounded mb-3" style="max-height: 220px; object-fit: cover;">
                <div id="productInfoModalGallery" class="d-flex justify-content-center gap-2 flex-wrap mb-3"></div>
                <p id="productInfoModalDescription" class="text-muted"></p>
                <div class="mt-3">
                    <p class="small text-muted mb-2">Escanea para ver la ficha del producto</p>
                    <img id="productInfoModalQr" src="" alt="Código QR" class="img-fluid">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>

<script>
(function() {
    window.GEME_TOUR_STEPS = [
        { element: '#posProductsCard', intro: 'Busca y selecciona productos para agregarlos a la venta.' },
        { element: '#product-search', intro: 'Escribe aquí para filtrar productos rápidamente.' },
        { element: '#posCustomer', intro: 'Opcionalmente selecciona un cliente registrado con RIF para el libro de ventas SENIAT.' },
        { element: '#posCartCard', intro: 'Revisa el carrito, ajusta cantidades o precios antes de finalizar.' },
        { element: '#posPayment', intro: 'Elige el método de pago y el monto recibido.' }
    ];

    const cart = [];
    const tbody = document.querySelector('#cart-table tbody');
    const totalEl = document.getElementById('cart-total');
    const paymentAmount = document.getElementById('payment-amount');
    const emptyRow = document.getElementById('empty-row');

    function render() {
        tbody.innerHTML = '';
        let total = 0;
        if (cart.length === 0) {
            tbody.appendChild(emptyRow);
        } else {
            cart.forEach((item, idx) => {
                const lineTotal = item.qty * item.price;
                total += lineTotal;
                const tr = document.createElement('tr');
                tr.innerHTML = `
                    <td>${item.name}</td>
                    <td><input type="number" step="0.001" min="0.001" name="items[${idx}][quantity]" class="form-control form-control-sm qty-input" value="${item.qty}" data-idx="${idx}"></td>
                    <td><input type="number" step="0.01" min="0" name="items[${idx}][unit_price]" class="form-control form-control-sm price-input" value="${item.price.toFixed(2)}" data-idx="${idx}"></td>
                    <td class="line-total">${lineTotal.toFixed(2)}</td>
                    <td>
                        <button type="button" class="btn btn-sm btn-outline-danger remove-item" data-idx="${idx}">X</button>
                    </td>
                    <input type="hidden" name="items[${idx}][product_id]" value="${item.id}">
                `;
                tbody.appendChild(tr);
            });
        }
        totalEl.textContent = '$ ' + total.toFixed(2);
        paymentAmount.value = total.toFixed(2);
    }

    const productInfoModal = document.getElementById('productInfoModal');
    const productInfoModalTitle = document.getElementById('productInfoModalTitle');
    const productInfoModalImage = document.getElementById('productInfoModalImage');
    const productInfoModalGallery = document.getElementById('productInfoModalGallery');
    const productInfoModalDescription = document.getElementById('productInfoModalDescription');
    const productInfoModalQr = document.getElementById('productInfoModalQr');

    document.querySelectorAll('.product-add-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const id = this.dataset.id;
            const name = this.dataset.name;
            const price = parseFloat(this.dataset.price);
            const existing = cart.find(i => i.id === id);
            if (existing) {
                existing.qty += 1;
            } else {
                cart.push({ id, name, price, qty: 1 });
            }
            render();
        });
    });

    document.querySelectorAll('.product-info-btn').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            const name = this.dataset.name;
            const description = this.dataset.description || 'Sin descripción';
            const image = this.dataset.image;
            const images = JSON.parse(this.dataset.images || '[]');
            const url = this.dataset.url;
            productInfoModalTitle.textContent = name;
            productInfoModalDescription.textContent = description;
            productInfoModalImage.src = image || '';
            productInfoModalImage.style.display = image ? 'block' : 'none';

            productInfoModalGallery.innerHTML = '';
            images.forEach(src => {
                const thumb = document.createElement('img');
                thumb.src = src;
                thumb.className = 'rounded';
                thumb.style.width = '70px';
                thumb.style.height = '70px';
                thumb.style.objectFit = 'cover';
                thumb.style.cursor = 'pointer';
                thumb.addEventListener('click', function() {
                    productInfoModalImage.src = src;
                    productInfoModalImage.style.display = 'block';
                });
                productInfoModalGallery.appendChild(thumb);
            });
            productInfoModalGallery.style.display = images.length > 1 ? 'flex' : 'none';

            productInfoModalQr.src = 'https://chart.googleapis.com/chart?cht=qr&chs=200x200&chl=' + encodeURIComponent(url);
            const modal = bootstrap.Modal.getOrCreateInstance(productInfoModal);
            modal.show();
        });
    });

    tbody.addEventListener('click', function(e) {
        const btn = e.target.closest('.remove-item');
        if (btn) {
            cart.splice(parseInt(btn.dataset.idx), 1);
            render();
        }
    });

    tbody.addEventListener('input', function(e) {
        const input = e.target.closest('.qty-input') || e.target.closest('.price-input');
        if (!input) return;
        const idx = parseInt(input.dataset.idx);
        const field = input.classList.contains('qty-input') ? 'qty' : 'price';
        const val = parseFloat(input.value);
        if (!isNaN(val) && val >= 0) {
            cart[idx][field] = val;
            render();
        }
    });

    document.getElementById('product-search').addEventListener('input', function() {
        const term = this.value.toLowerCase();
        document.querySelectorAll('.product-item-wrapper').forEach(item => {
            const text = item.textContent.toLowerCase();
            item.style.display = text.includes(term) ? '' : 'none';
        });
    });

    document.getElementById('pos-form').addEventListener('submit', function(e) {
        if (cart.length === 0) {
            e.preventDefault();
            alert('Agrega al menos un producto a la venta.');
        }
    });

    const customerSelect = document.getElementById('customer-select');
    const customerNameInput = document.getElementById('customer-name-input');
    const customerRifText = document.getElementById('customer-rif-text');

    function updateCustomerInfo() {
        const option = customerSelect.options[customerSelect.selectedIndex];
        if (!option.value) {
            customerNameInput.value = '';
            customerRifText.textContent = 'RIF: consumidor final';
            return;
        }
        customerNameInput.value = option.dataset.name || '';
        customerRifText.textContent = 'RIF: ' + (option.dataset.rif || 'V000000000');
    }

    customerSelect.addEventListener('change', updateCustomerInfo);
    updateCustomerInfo();

    window.startPosTour = function() {
        if (typeof introJs === 'undefined') return;
        introJs()
            .setOptions({
                steps: [
                    { element: '#posProductsCard', intro: 'Busca y selecciona productos para agregarlos a la venta.' },
                    { element: '#product-search', intro: 'Escribe aquí para filtrar productos rápidamente.' },
                    { element: '#posCustomer', intro: 'Opcionalmente selecciona un cliente registrado con RIF para el libro de ventas SENIAT.' },
                    { element: '#posCartCard', intro: 'Revisa el carrito, ajusta cantidades o precios antes de finalizar.' },
                    { element: '#posPayment', intro: 'Elige el método de pago y el monto recibido.' },
                ],
                nextLabel: 'Siguiente',
                prevLabel: 'Anterior',
                skipLabel: 'Saltar',
                doneLabel: 'Listo',
                showProgress: true,
            })
            .start();
    };
})();
</script>
@endif
@endsection
