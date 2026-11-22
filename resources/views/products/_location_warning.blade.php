{{--
    Aviso al guardar un producto sin ubicación. No bloquea: el usuario puede guardar igual
    (servicios y productos preparados no lo necesitan y se excluyen del aviso).
--}}
<div class="modal fade" id="noLocationModal" tabindex="-1" aria-labelledby="noLocationModalTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content text-dark">
            <div class="modal-header">
                <h5 class="modal-title" id="noLocationModalTitle"><i class="bi bi-geo-alt text-warning"></i> Producto sin ubicación</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">
                <p class="mb-2">Este producto no ha sido asignado a ninguna ubicación.</p>
                <p class="small text-secondary mb-0">
                    Su stock irá al almacén principal, pero no aparecerá en qué pasillo o estante está
                    (búsqueda, etiquetas, preparación de pedidos). Podrás encontrarlo luego con el filtro
                    <em>“Sin ubicación”</em> del listado de productos.
                </p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" id="noLocationChoose">Elegir ubicación</button>
                <button type="button" class="btn btn-warning" id="noLocationSave">Guardar de todos modos</button>
            </div>
        </div>
    </div>
</div>
<script>
    (function () {
        const form = document.getElementById('productForm');
        const modalEl = document.getElementById('noLocationModal');
        if (!form || !modalEl) return;
        let confirmed = false;

        const checked = id => { const el = document.getElementById(id); return el && el.checked; };

        form.addEventListener('submit', function (e) {
            if (confirmed) return;
            const location = document.getElementById('location_id');
            const needsLocation = !checked('is_service') && !checked('is_prepared');
            if (!location || location.value || !needsLocation) return;

            e.preventDefault();
            if (typeof bootstrap === 'undefined') {
                // Sin Bootstrap JS: aviso nativo
                if (confirm('Este producto no ha sido asignado a ninguna ubicación. ¿Guardarlo de todos modos?')) {
                    confirmed = true;
                    form.requestSubmit ? form.requestSubmit() : form.submit();
                }
                return;
            }
            const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
            modal.show();
        });

        document.getElementById('noLocationSave').addEventListener('click', function () {
            confirmed = true;
            bootstrap.Modal.getOrCreateInstance(modalEl).hide();
            form.requestSubmit ? form.requestSubmit() : form.submit();
        });

        document.getElementById('noLocationChoose').addEventListener('click', function () {
            bootstrap.Modal.getOrCreateInstance(modalEl).hide();
            const section = document.getElementById('prod-location');
            const select = document.getElementById('location_id');
            if (section) section.scrollIntoView({ behavior: 'smooth', block: 'center' });
            if (select) setTimeout(() => select.focus(), 300);
        });
    })();
</script>
