<?php

return [
    'title' => 'Centro de ayuda',
    'subtitle' => 'Encuentra guías prácticas, pasos a paso y sugerencias para operar WAWI con tu equipo.',
    'search_placeholder' => 'Buscar por palabra clave (ej.: "nómina", "TPV", "inventario")',
    'search_hint' => 'Escribe al menos dos letras para encontrar artículos relacionados.',
    'search_action' => 'Buscar',
    'search_results_title' => 'Resultados de búsqueda',
    'search_results_none' => 'No encontramos artículos que coincidan con tu búsqueda.',
    'quick_links_title' => 'Accesos rápidos',
    'quick_links' => [
        [
            'label' => 'Checklist inicial',
            'description' => 'Configura datos básicos, impuestos y usuarios en pocos minutos.',
            'category' => 'general',
            'article' => 'primeros-pasos',
        ],
        [
            'label' => 'Cargar productos y categorías',
            'description' => 'Aprende a organizar tu catálogo y controlar el inventario.',
            'category' => 'inventario',
            'article' => 'catalogo-productos',
        ],
        [
            'label' => 'Cobrar con TPV rápido',
            'description' => 'Guía para vender, aplicar métodos de pago y cerrar la venta.',
            'category' => 'tpv',
            'article' => 'flujo-caja',
        ],
        [
            'label' => 'Procesar nómina',
            'description' => 'Genera periodos, corridas y ajusta entradas manuales.',
            'category' => 'nomina',
            'article' => 'ciclo-nomina',
        ],
    ],
    'topics' => [
        'general' => [
            'title' => 'Primeros pasos',
            'summary' => 'Configura tu negocio, usuarios y parámetros clave antes de operar.',
            'articles' => [
                'primeros-pasos' => [
                    'title' => 'Checklist inicial del sistema',
                    'summary' => 'Tareas recomendadas para poner WAWI en marcha desde cero.',
                    'estimated_time' => '5 minutos',
                    'content' => <<<'HTML'
<p>Completa este checklist para garantizar que la plataforma esté lista antes de vender:</p>
<ol>
    <li><strong>Datos del negocio:</strong> ve a <em>Configuración &gt; Apariencia</em> y carga logotipo, razón social y moneda por defecto.</li>
    <li><strong>Impuestos:</strong> en <em>Configuración &gt; Tasa BCV</em> define el IVA o tasa fiscal utilizada para tus productos.</li>
    <li><strong>Usuarios y roles:</strong> crea cuentas para administradores, cajeros y mesoneros desde <em>Administración &gt; Usuarios</em>. Asigna roles para controlar permisos.</li>
    <li><strong>Cajas y turnos:</strong> si usarás el TPV rápido, asegúrate de abrir caja en <em>Caja / Turno</em> antes de registrar ventas.</li>
    <li><strong>Catálogo inicial:</strong> crea al menos una categoría y algunos productos para probar el flujo de venta.</li>
</ol>
<p>Una vez finalizado, realiza una venta de prueba en el TPV para validar impuestos, impresión y cierre de caja.</p>
HTML,
                    'tags' => ['configuración', 'usuarios', 'licencia'],
                ],
                'roles-permisos' => [
                    'title' => 'Roles y permisos recomendados',
                    'summary' => 'Define perfiles de acceso para proteger operaciones sensibles.',
                    'estimated_time' => '4 minutos',
                    'content' => <<<'HTML'
<p>WAWI incluye roles predefinidos que puedes asignar a cada usuario:</p>
<ul>
    <li><strong>Administrador:</strong> acceso completo para configuración, reportes y finanzas.</li>
    <li><strong>Cajero:</strong> acceso al TPV rápido, caja, órdenes y reportes diarios.</li>
    <li><strong>Mesonero / Despachador:</strong> puede ver mesas, órdenes y cocina/bar según el módulo activo.</li>
    <li><strong>Cliente:</strong> pensado para el portal de pedidos online, con accesos limitados.</li>
</ul>
<p>Evita compartir usuarios y activa el login por PIN para cajeros desde <em>Operador &gt; PIN</em> para registrar quién atiende cada venta.</p>
HTML,
                    'tags' => ['usuarios', 'seguridad'],
                ],
            ],
        ],
        'inventario' => [
            'title' => 'Inventario y catálogo',
            'summary' => 'Organiza categorías, productos y recetas para controlar existencias.',
            'articles' => [
                'catalogo-productos' => [
                    'title' => 'Cómo crear categorías y productos',
                    'summary' => 'Estructura tu carta con imágenes, precios e impuestos.',
                    'estimated_time' => '6 minutos',
                    'content' => <<<'HTML'
<p>Sigue estos pasos para dejar tu catálogo listo:</p>
<ol>
    <li>Crea categorías en <em>Inventario &gt; Categorías</em>. Usa la opción "Activa" solo para aquellas visibles en TPV y menú online.</li>
    <li>Configura productos desde <em>Inventario &gt; Productos</em> indicando precio, impuesto y si controla stock.</li>
    <li>Agrega códigos de barra (opcional) y recetas si el producto descuenta ingredientes del inventario.</li>
    <li>Verifica el menú público visitando <code>{{ url('/menu') }}</code> para confirmar imágenes y textos.</li>
</ol>
<p>Recuerda desactivar categorías o productos que no deban aparecer en el TPV; el sistema ocultará automáticamente los deshabilitados.</p>
HTML,
                    'tags' => ['inventario', 'productos'],
                ],
                'ajustes-inventario' => [
                    'title' => 'Control de existencias y ajustes',
                    'summary' => 'Registra entradas, salidas y movimientos Kardex.',
                    'estimated_time' => '5 minutos',
                    'content' => <<<'HTML'
<p>Para mantener inventario preciso:</p>
<ul>
    <li>Consulta el stock actual en <em>Inventario &gt; Stock</em> y filtra por categoría o producto.</li>
    <li>Registra ajustes manuales desde <em>Inventario &gt; Ajustes</em> indicando motivo y usuario responsable.</li>
    <li>Revisa el historial de movimientos (Kardex) para cada producto y valida consumos automáticos por ventas.</li>
</ul>
<p>Los ajustes quedan auditados y puedes exportarlos para conciliaciones contables.</p>
HTML,
                    'tags' => ['inventario', 'kardex'],
                ],
            ],
        ],
        'tpv' => [
            'title' => 'TPV rápido y caja',
            'summary' => 'Aprende a usar el punto de venta rápido, métodos de pago y cierres X/Z.',
            'articles' => [
                'flujo-caja' => [
                    'title' => 'Flujo completo de venta en TPV rápido',
                    'summary' => 'Identifica operador, agrega productos, cobra y cierra turno.',
                    'estimated_time' => '7 minutos',
                    'content' => <<<'HTML'
<p>Flujo sugerido para el cajero:</p>
<ol>
    <li><strong>Abrir caja:</strong> desde <em>Caja / Turno</em> registra monto inicial y operador responsable.</li>
    <li><strong>Identificar operador:</strong> en el TPV usa el botón <em>Identificar</em> e ingresa el PIN asignado.</li>
    <li><strong>Agregar productos:</strong> busca por nombre, categoría o escanea el código de barras.</li>
    <li><strong>Cobrar:</strong> selecciona el método de pago (efectivo, tarjeta, PagoMóvil, PIX) e ingresa referencia si aplica.</li>
    <li><strong>Imprimir o enviar comprobante:</strong> genera el ticket y entrega al cliente.</li>
    <li><strong>Cerrar turno:</strong> al final del día emite corte X/Z para comparar montos esperados vs. reales.</li>
</ol>
<p>Si el operador olvida cerrar caja, un administrador puede hacerlo desde el módulo de turnos.</p>
HTML,
                    'tags' => ['tpv', 'caja'],
                ],
                'escaneo-codigos' => [
                    'title' => 'Activar escaneo por cámara y códigos de barra',
                    'summary' => 'Requisitos y consejos para usar lectores integrados en el navegador.',
                    'estimated_time' => '3 minutos',
                    'content' => <<<'HTML'
<p>Para aprovechar el escaneo con cámara:</p>
<ul>
    <li>Usa navegadores compatibles (Chrome, Edge o Safari recientes) y concede permisos de cámara.</li>
    <li>Activa el botón <em>Escanear</em> en el TPV; si el dispositivo lo soporta, aparecerá en pantalla.</li>
    <li>Mantén buena iluminación y enfoca el código a 10-15 cm de la cámara para mejores resultados.</li>
</ul>
<p>También puedes registrar códigos de barra alternos por producto desde <em>Inventario &gt; Productos &gt; Códigos</em>.</p>
HTML,
                    'tags' => ['tpv', 'barcodes'],
                ],
            ],
        ],
        'nomina' => [
            'title' => 'Nómina',
            'summary' => 'Gestiona periodos, corridas y entradas con ajustes manuales.',
            'articles' => [
                'ciclo-nomina' => [
                    'title' => 'Ciclo completo de nómina',
                    'summary' => 'Crea periodos, corre nómina y genera recibos.',
                    'estimated_time' => '8 minutos',
                    'content' => <<<'HTML'
<p>Para cada periodo de nómina:</p>
<ol>
    <li><strong>Crear periodo:</strong> define rango de fechas, tipo (semanal, quincenal) y estado inicial <em>Abierto</em>.</li>
    <li><strong>Crear corrida:</strong> dentro del periodo, genera la corrida y presiona <em>Generar entradas</em> para calcular conceptos automáticos.</li>
    <li><strong>Revisar y ajustar:</strong> entra a cada empleado para agregar o editar conceptos manuales (bonos, deducciones, horas extras).</li>
    <li><strong>Aprobar:</strong> cuando todas las entradas estén correctas, aprueba la corrida para bloquear cambios.</li>
    <li><strong>Emitir recibos:</strong> exporta o imprime recibos desde la vista de la corrida para entregar al personal.</li>
</ol>
<p>Si necesitas corregir montos después de aprobar, duplica la corrida o abre un ajuste extraordinario para mantener la trazabilidad.</p>
HTML,
                    'tags' => ['nómina', 'rrhh'],
                ],
                'conceptos-manuales' => [
                    'title' => 'Agregar conceptos manuales a una entrada',
                    'summary' => 'Incluye bonos, deducciones y horas extras específicas.',
                    'estimated_time' => '4 minutos',
                    'content' => <<<'HTML'
<p>Desde la vista de edición de entrada:</p>
<ul>
    <li>Ubica la sección "Ajustes manuales" y presiona <em>Agregar fila</em>.</li>
    <li>Selecciona el concepto o ingresa uno libre con descripción, cantidad, tasa y monto.</li>
    <li>Marca la casilla para restar (deducción) o deja desmarcada para sumar al neto.</li>
    <li>Guarda los cambios para recalcular totales y reflejar el ajuste en el recibo.</li>
</ul>
<p>Recuerda que una corrida aprobada ya no permite modificaciones; realiza los ajustes antes de finalizar.</p>
HTML,
                    'tags' => ['nómina', 'ajustes'],
                ],
            ],
        ],
    ],
];
