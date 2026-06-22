# WAWI — Descripción funcional y adaptaciones por cliente

## 1. Qué es WAWI

**WAWI** es una aplicación web (Laravel) para la **operación diaria de restaurantes y comercios** con enfoque en:

- **Catálogo** (productos y categorías).
- **Ventas / pedidos** (incluye un modo POS rápido).
- **Inventario** (movimientos y control de stock).
- **Compras a proveedores** (facturas con ítems).
- **Finanzas** (gastos y consumos privados).
- **Reportes y paneles**.
- **Operación interna** (usuarios, roles y ayudas guiadas).

Está pensada para correr en contenedores Docker y desplegarse en CapRover, con persistencia de archivos (imágenes) vía volúmenes.

---

## 2. Módulos principales y capacidades

### 2.1 Productos

- Alta/edición de productos con:
  - Nombre, SKU, descripción.
  - Precio, costo y margen.
  - Impuestos (tasa / configuración de impuesto incluido).
  - Estado (activo/inactivo).
  - Opciones de stock (seguimiento de stock, cantidades, puntos de reorden).
  - Unidades (unidad base / unidad de stock).
  - Imagen asociada (almacenada en `storage/app/public/...`).
- Soporte para **códigos de barras** (relación a producto).

### 2.2 Categorías

- Organización del catálogo por categorías.
- Imagen de categoría.
- Usadas por la UI y por clientes (menú/presentación) para agrupar productos.

### 2.3 Pedidos (Ventas)

- Gestión de pedidos y sus ítems.
- Cálculo de totales y (cuando aplica) impuestos.
- Vistas de listado y edición.

### 2.4 Fast POS

- Interfaz optimizada para venta rápida.
- Consumo intensivo de catálogo (incluye imágenes) para selección ágil.

### 2.5 Inventario

- Movimientos de stock (entradas/salidas) por operaciones internas.
- Stock asociado a productos (según configuración del producto).

### 2.6 Recetas / Productos preparados

- Definición de recetas y componentes.
- Posibilidad de descontar stock de materias primas según consumo.

### 2.7 Compras / Proveedores

- Registro de facturas de proveedores con:
  - Ítems (productos, cantidades, precios, totales).
  - Generación de movimientos de inventario asociados.

### 2.8 Finanzas

- Registro y seguimiento de gastos y consumo privado.
- Resúmenes y filtros por periodo.

### 2.9 Reportes

- Paneles y vistas de reportes para análisis operativo.

### 2.10 Usuarios y operación interna

- Administración de usuarios.
- Apoyo con **tours/guías** en pantallas clave (onboarding y ayuda contextual).

---

## 3. Gestión de imágenes (punto crítico en CapRover)

WAWI guarda imágenes (productos/categorías) en el disco **`public`** de Laravel, que corresponde a:

- **Archivos reales:** `storage/app/public/...`
- **Acceso web:** `/storage/...` a través del symlink `public/storage`.

Para que las imágenes **persistan tras deploys/reinicios** en CapRover, se requiere:

1. **Volumen persistente** montado en:
   - `/var/www/html/storage/app/public`
2. **Symlink `public/storage`** existente y correcto.

En este repositorio se incluye lógica de arranque para recrear el symlink si falta o es incorrecto (ver `AppServiceProvider`).

> Guía operativa completa: `docs/caprover-almacenamiento-imagenes.md`

---

## 4. Integraciones / clientes

WAWI puede ser consumida por:

- **Web admin** (operación interna).
- **Pantallas de cliente** (menú / catálogo) que suelen construir URLs de imágenes del tipo:
  - `https://<dominio>/storage/<ruta-en-DB>`

Esto hace crítico que el symlink y el volumen estén correctos.

---

## 5. Adaptaciones por cliente (qué se puede personalizar)

A continuación se listan adaptaciones típicas en dos niveles:

- **A. Parametrización sin desarrollo** (config/variables/ajustes operativos).
- **B. Adaptación con desarrollo** (cambios de lógica, UI o integraciones).

### 5.1 A — Parametrización sin desarrollo (rápido)

#### Identidad y despliegue

- Dominio por cliente (ej. `cliente.tu-dominio.com`).
- Variables de entorno:
  - `APP_NAME`, `APP_URL`, `APP_ENV`, `APP_DEBUG`.
- HTTPS forzado detrás de proxy (según configuración).

#### Operación

- Usuarios y perfiles (según lo ya soportado por el sistema).
- Activación/desactivación operativa de productos (sin borrarlos).

#### Ajustes del sistema (dependen de lo implementado en UI)

- Impuestos / tasas predefinidas.
- Unidades y catálogos auxiliares.

> Nota: si un ajuste no existe en UI, pasa a la categoría “con desarrollo”.

### 5.2 B — Adaptación con desarrollo (mediano/alto)

#### UI/UX y branding

- Logo, colores, nombre comercial, textos y copies.
- Páginas de inicio/menú específicas.
- Flujos de POS personalizados por tipo de negocio.

#### Catálogo y datos

- Campos adicionales por producto (ej. alérgenos, etiquetas, preparación, calorías).
- Variantes por producto (tamaños, extras, combos).

#### Impuestos / facturación

- Reglas de impuestos especiales (multi-tasa por categoría/producto).
- Redondeos, descuentos avanzados, promociones.

#### Inventario

- Multi-almacén.
- Lotes, vencimientos, trazabilidad.
- Ajustes automáticos de costo promedio.

#### Compras y proveedores

- Flujos por aprobación.
- Integración con facturación externa.
- Importación por formatos específicos de proveedor.

#### Integraciones

- Integración con impresoras de cocina/boleta.
- Integración con pasarelas de pago.
- Integración con delivery / agregadores.
- Apps móviles o kioscos adicionales.

#### Reportes

- Reportes por cliente (KPIs y dashboards específicos).
- Exportaciones (CSV/Excel) con formatos definidos por cliente.

---

## 6. Cómo levantar un documento de requerimientos por cliente (plantilla)

Para definir una adaptación sin ambigüedades, recomiendo que el cliente responda:

### 6.1 Datos generales

- Nombre del negocio / marca.
- País y moneda.
- Impuestos que aplica.
- Tipo de operación: restaurante, cafetería, retail, dark kitchen.

### 6.2 Flujos

- Cómo se toma el pedido (mesa, mostrador, QR, delivery).
- Cómo se cobra (efectivo, tarjeta, transferencia, mixto).
- Si requiere impresión (cocina/cliente).

### 6.3 Catálogo

- Necesita variantes/extras/combos.
- Requiere alérgenos/etiquetas.
- Cantidad aproximada de productos y categorías.

### 6.4 Inventario

- ¿Se controla stock de todo o solo insumos?
- ¿Necesita multi-almacén?
- ¿Necesita vencimientos/lotes?

### 6.5 Reportes

- KPIs requeridos.
- Periodicidad.
- Exportación requerida.

---

## 7. Alcance actual vs alcance propuesto

Este documento describe el **alcance actual conocido** de WAWI y las **familias de adaptaciones** típicas.

Para cada cliente, se recomienda formalizar:

- Lista de cambios requeridos.
- Prioridad (Must/Should/Could).
- Tiempo estimado.
- Impacto en datos (migraciones, seed, compatibilidad).

---

## 8. Referencias internas

- Persistencia de imágenes en CapRover:
  - `docs/caprover-almacenamiento-imagenes.md`
- Lógica de recreación de symlink:
  - `app/Providers/AppServiceProvider.php`
