# GEME — Análisis competitivo, IA, ISO 27001 y Providencia SENIAT

| | |
|---|---|
| **Fecha** | 22 de noviembre de 2026 |
| **Autor** | Thomas (con apoyo de Claude) |
| **Estado** | Borrador para revisión con el CEO |
| **Alcance** | GEME ERP (repo `Araguato/geme`), instancia `tienda.quorixtech.eu` |

---

## 1. Resumen ejecutivo

1. **Competidor de referencia: Fina (finapartner.com).** Es un software de gestión para pymes venezolanas que usa sus datos para ofrecer crédito ("crédito embebido"). Tiene un asistente de IA (NINA), un módulo de Marketing y conciliación bancaria, que GEME aún no tiene.
2. **La IA no contradice la ISO 27001**, siempre que se trate como un proveedor externo más: datos minimizados, contrato con el proveedor, control de acceso por rol, registro de uso y supervisión humana. El financiamiento basado en datos es el caso de mayor riesgo y queda **aparcado** hasta tener socio financiero y revisión legal.
3. **SENIAT:** la Providencia **SNAT/2026/00093** (G.O. 43.484, 21-10-2026) entra en vigor el **19 de abril de 2027**. Las facturas deberán emitirse por medios digitales autorizados (portal o app del SENIAT, imprentas digitales o proveedores tecnológicos autorizados) o por máquinas fiscales. **GEME debe integrarse con uno de esos medios.** Es la prioridad número uno, porque tiene fecha legal.
4. **Rendimiento:** una IA consumida por API **no carga el servidor de forma relevante**, siempre que las llamadas se hagan en segundo plano (colas) y los datos se preparen agregados y en caché. Ejecutar un modelo de IA propio en el mismo VPS **no es recomendable**.
5. **Coste:** basta un **modelo pequeño** (unos 0,40 $/mes por tienda); un modelo grande costaría 20–40 veces más sin aportar valor. La mayor parte del análisis se resuelve con SQL, sin IA.

**Orden propuesto:** (1) cumplimiento SENIAT → (2) Marketing/CRM → (3) asistente de IA → (4) financiamiento (aparcado).

---

## 2. Análisis competitivo: Fina

### 2.1 Qué es

- Startup venezolana fundada en 2024. Cerró una ronda semilla de US$1M en agosto de 2026 y declara más de 5.000–6.000 comercios activos (autopartes, ferreterías, ropa, bodegones, restaurantes, distribuidores).
- **Software:** ventas multimoneda (Bs. y $ con tasa del día), inventario por sucursal y depósito, caja, cuentas por cobrar y por pagar, conciliación bancaria, gastos, estadísticas y Marketing.
- **NINA (IA):** agente que procesa los datos transaccionales del comercio para generar diagnósticos de gestión, métricas de rendimiento y proyecciones de crecimiento.
- **Fina Presta (crédito embebido):** financiamiento ofrecido dentro de la propia plataforma a partir de los datos que ya tiene del comercio. Según la empresa, más de 2.000 créditos en el piloto, y los comercios con crédito vendieron en promedio un 16,7 % más.

### 2.2 Comparativa con GEME

| Área | Fina | GEME (nov. 2026) |
|---|---|---|
| TPV multimoneda con tasa del día | ✅ | ✅ (tasa BCV diaria, Cashea, Pago Móvil) |
| Inventario por depósito, ubicación y variantes | ✅ | ✅ (kardex, costo promedio, recetas) |
| Cuentas por pagar | ✅ | ✅ (con facturas recurrentes) |
| Cuentas por cobrar | ✅ | ❌ |
| Conciliación bancaria | ✅ | ❌ |
| Gastos | ✅ | ✅ |
| Estadísticas | ✅ | ✅ (panel por rol, informe por vendedor, categoría y producto) |
| Marketing / CRM | ✅ | ❌ |
| Asistente de IA | ✅ NINA | ❌ |
| Crédito embebido | ✅ Fina Presta | ❌ (aparcado) |
| Libros SENIAT y cadena de integridad | No consta en su web | ✅ |
| Nómina | No consta | ✅ |
| Roles (admin, contable, almacén, vendedor) | No consta | ✅ |

**Lectura:** GEME está a la par o por delante en operación (inventario, nómina, fiscal, roles). Fina se diferencia por las **funciones de valor añadido sobre los datos**: CRM, IA y crédito.

### 2.3 Funciones a incorporar en GEME

**a) Marketing / CRM** (esfuerzo bajo-medio; los datos ya existen):
- Ficha de cliente con historial de compras, ticket medio y última visita.
- Segmentos automáticos: frecuentes, nuevos, inactivos (más de 60 días sin comprar), por categoría preferida.
- Exportación de segmentos (CSV) para campañas por WhatsApp o correo, con **consentimiento** registrado (`marketing_opt_in` ya existe en usuarios).

**b) Asistente de IA** (esfuerzo medio; detalle en el apartado 3):
- "¿Cómo va mi negocio?": resumen semanal o mensual en lenguaje natural.
- Tendencias y alertas: productos que caen, categorías con margen negativo, quiebres de stock frecuentes.
- **Efecto cambiario:** evolución de ventas y márgenes en $ y en Bs a tasa BCV y erosión del margen por la variación de la tasa. Se usa **solo la tasa oficial BCV**; publicar o calcular tasas no oficiales tiene riesgo legal en Venezuela y requiere consulta previa.

**c) Cuentas por cobrar y conciliación bancaria** (esfuerzo medio).

**d) Financiamiento** (aparcado; ver el apartado 3.4).

---

## 3. IA y la ISO 27001

### 3.1 Principio

La ISO/IEC 27001:2022 no prohíbe la IA. Exige que **todo tratamiento de información esté evaluado en riesgo y controlado**. Un servicio de IA externo es, a efectos del SGSI, **un proveedor en la nube que recibe información de la organización y de sus clientes**. Lo que contradiría la norma es enviar datos sin clasificar, sin contrato y sin registro.

### 3.2 Diseño propuesto

1. GEME calcula **agregados** (ventas por día, categoría y vendedor, márgenes, stock bajo, tasas BCV) con el `SalesReportService` que ya existe.
2. Solo esos agregados, **sin datos personales** (nombres de clientes, RIF, cédulas, teléfonos), se envían al modelo de IA.
3. La IA devuelve texto o recomendaciones que **se muestran y no se ejecutan**: no cambia precios, stock ni facturas.
4. Cada consulta queda en el registro de auditoría (`AuditLog`): usuario, rol, tipo de consulta y conjunto de datos enviado.

### 3.3 Mapa de controles (Anexo A)

| Riesgo | Control Anexo A | Medida en GEME |
|---|---|---|
| Fuga de datos personales al proveedor de IA | 5.12 clasificación · 5.34 privacidad y datos personales · 8.11 enmascaramiento | Solo agregados; lista blanca de campos que se pueden enviar; sin datos personales. |
| Proveedor de IA sin garantías | 5.19–5.22 relación con proveedores · 5.23 servicios en la nube | Contrato o condiciones con **no entrenamiento con datos del cliente**, retención limitada, región conocida; alta en el registro de proveedores. |
| Una tienda accede a datos de otra | 5.15 control de acceso · 8.3 restricción de acceso | Consultas limitadas a la propia instancia; el rol decide el alcance (el vendedor no ve márgenes ni costos). |
| Decisiones basadas en respuestas erróneas | 5.37 procedimientos documentados · supervisión humana | Respuestas con la fuente de los datos; la IA solo aconseja; aviso visible de "sugerencia automática". |
| *Prompt injection* (textos de usuarios usados como instrucciones) | 8.28 codificación segura | Nombres de productos, notas y clientes se tratan como datos; la IA no tiene herramientas de escritura. |
| Falta de trazabilidad | 8.15 registro · 8.16 monitorización | Registro de cada consulta y su coste; alertas por uso anómalo. |
| Indisponibilidad del proveedor | 5.30 continuidad TIC | La IA es opcional: si falla, GEME funciona igual. |
| Secretos (clave de API) | 5.17 información de autenticación · 8.24 criptografía | Clave en variables de entorno de CapRover, nunca en el repositorio; rotación periódica. |

**Tareas del SGSI:**
- Añadir "Servicio de IA" a la evaluación de riesgos y a la Declaración de Aplicabilidad.
- Registrar al proveedor de IA en el inventario de proveedores.
- Valorar la **ISO/IEC 42001** (sistema de gestión de IA), que se integra con la 27001, si la IA pasa a ser un producto central.

### 3.4 Financiamiento basado en datos (aparcado)

Es el uso de mayor riesgo:
- **Consentimiento** expreso y revocable del comercio para usar sus datos en la evaluación crediticia.
- **Decisión humana:** la IA puede dar un indicador, pero la decisión y su explicación las da una persona.
- **Marco legal:**
  - Venezuela no tiene una ley integral de protección de datos (solo el art. 28 de la Constitución, *habeas data*, y normas dispersas).
  - Si el tratamiento se realiza o se controla desde **Quorisk S.L. (España)**, aplican el **RGPD** y el **Reglamento europeo de IA**, que califica como **alto riesgo** los sistemas de evaluación de solvencia de personas físicas (Anexo III). Muchos comercios pequeños son personas naturales.
- **Requisito previo:** socio financiero y revisión legal antes de diseñar nada.

---

## 4. Rendimiento del servidor

**Respuesta corta:** si la IA se consume por **API externa**, el servidor apenas trabaja. El modelo corre en la infraestructura del proveedor; GEME solo prepara unos pocos kilobytes de datos y espera la respuesta.

### 4.1 Dónde sí puede notarse y cómo evitarlo

| Punto | Riesgo | Solución |
|---|---|---|
| **Espera de la respuesta** (2–20 s) | Con Apache + mod_php (el Dockerfile actual), cada consulta ocupa un proceso de Apache mientras espera. Con muchas consultas a la vez, el TPV podría quedarse sin procesos libres. | Lanzar la consulta como **trabajo en cola** (Laravel Queue) y que la pantalla consulte el resultado o lo reciba en *streaming*. Tiempo máximo (*timeout*) de 30 s. |
| **Cálculo de los agregados** | Las consultas de informes sobre muchos meses pueden pesar. | Ya hay índices (migración `add_report_indexes`). Guardar en **caché** los resúmenes diarios y precalcularlos de noche con el planificador. |
| **Abuso o uso excesivo** | Coste de API y carga. | Límite de consultas por usuario y día; respuestas en caché (la misma pregunta el mismo día no vuelve a llamar). |
| **Modelo de IA propio en el VPS** | Un modelo local necesita mucha RAM y CPU, o GPU; competiría con la base de datos y el TPV. | **No recomendado** en el VPS de CapRover. Si algún día se quiere un modelo propio, en un servidor separado. |

### 4.2 Arquitectura recomendada en CapRover

- **App `tienda`** (web), sin cambios.
- **Worker de colas:** una segunda app en CapRover con la misma imagen y el comando `php artisan queue:work`, o un proceso supervisado dentro del contenedor. Procesa las consultas de IA y las tareas pesadas.
- **Planificador:** `php artisan schedule:run` cada minuto (cron del contenedor o app aparte) para precalcular los resúmenes nocturnos.
- **Monitorización:** tiempo de respuesta del TPV antes y después de activar la IA; alerta si la cola acumula trabajos.

**Conclusión:** con colas y caché, el impacto en la tienda es despreciable. Sin colas, en horas pico podría ralentizar el TPV. Por eso las colas son condición para activar la IA.

---

## 4bis. Coste y tipo de modelo de IA

### 4bis.1 No hace falta un modelo grande

Para GEME la IA solo tiene que **leer cifras ya calculadas y explicarlas en español**. Eso lo hace bien un modelo pequeño. Los modelos grandes (Claude Opus/Sonnet, GPT de gama alta, Gemini Pro) se pagan por razonamiento complejo que aquí no se usa.

**Regla de diseño:** la mayor parte del "análisis" **no necesita IA**. Tendencias, alertas de stock, caída de ventas, márgenes negativos, efecto de la tasa BCV o ranking de clientes son **cálculos SQL deterministas**, ya hechos en parte (`SalesReportService`, panel por rol). Esos cálculos cuestan 0 $ y son exactos y auditables. El modelo de lenguaje solo redacta el resumen y responde preguntas sobre esos números.

### 4bis.2 Precios de modelos pequeños (USD por millón de tokens, nov. 2026)

| Proveedor | Modelo | Entrada | Salida | Observaciones |
|---|---|---|---|---|
| Anthropic | Claude Haiku 5.5 | 0,10 | 0,50 | Batch −50 %; caché a 0,1x |
| OpenAI | GPT-6 Luna | 0,10 | 0,50 | |
| Mistral (UE) | Mistral Small 4 | 0,15 | 0,60 | Proveedor europeo (RGPD) |
| Alibaba | Qwen3.8-Flash | 0,15 | 0,47 | Datos fuera de la UE (China) |
| DeepSeek | V4.1-Flash | 0,15–0,30 | 0,60–1,20 | Datos en China |
| Google | Gemini 3.5 Flash-Lite | 0,30 | 2,50 | |
| *Referencia grande* | Claude Sonnet 5.5 / Opus 5.5 | 2 / 4 | 10 / 20 | 20–40 veces más caro; innecesario |

Fuentes: precios oficiales de Anthropic (platform.claude.com/docs/en/about-claude/pricing) y tabla de nhimg.org, revisada el 15-11-2026 en las páginas de cada proveedor. Los precios cambian: revisar antes de contratar.

### 4bis.3 Estimación de coste para GEME

Supuestos: cada consulta envía unos 3.000 tokens (cifras agregadas e instrucciones) y recibe unos 600. Se suma un resumen nocturno por tienda (unos 8.000 tokens de entrada y 1.000 de salida).

| Escenario | Modelo pequeño (0,10 / 0,50) | Modelo grande (Sonnet 5.5) |
|---|---|---|
| 1 consulta | ≈ 0,0006 $ | ≈ 0,012 $ |
| 1 tienda, 20 consultas/día + resumen nocturno | ≈ **0,40 $/mes** | ≈ 7,6 $/mes |
| 100 tiendas | ≈ **40 $/mes** | ≈ 760 $/mes |

Con caché de las instrucciones fijas y Batch para los resúmenes nocturnos, el coste del modelo pequeño baja otro 30–50 %.

### 4bis.4 Criterios de elección, además del precio

1. **Contrato de datos:** que no entrene con los datos del cliente y que tenga retención limitada (ISO 27001, controles 5.19–5.23).
2. **Ubicación de los datos:** si se opera desde Quorisk S.L. (España), es preferible un proveedor con procesamiento en la UE o con garantías RGPD. DeepSeek y Qwen procesan fuera de la UE.
3. **Disponibilidad para Venezuela:** varios proveedores estadounidenses restringen su uso en ciertos países. Hay que **revisar los términos de uso** de cada uno respecto a servir a usuarios finales en Venezuela antes de elegir.
4. **Calidad en español** y respuestas en formato estructurado (JSON).
5. **Sin dependencia de un proveedor:** GEME llamará a la IA a través de una **interfaz propia** (`AiProvider`), para cambiar de proveedor con una variable de entorno.

### 4bis.5 Modelo propio (autoalojado)

Un modelo abierto pequeño en un servidor propio con GPU tiene un coste **fijo** (decenas o cientos de $/mes) más mantenimiento. Solo compensa con miles de tiendas o si hay requisitos de que los datos no salgan nunca de la infraestructura. **No en el VPS actual de CapRover.**

### 4bis.6 Recomendación

- Cálculos y alertas **sin IA** (SQL).
- Un **modelo pequeño** (clase Haiku, Luna, Mistral Small) solo para redactar y responder.
- **Interfaz neutral** de proveedor.
- **Límite de gasto** por tienda y mes (por ejemplo, 1 $) y caché de respuestas.
- Prueba piloto con 2–3 modelos sobre las mismas preguntas reales de la tienda, y elegir por calidad en español, condiciones de datos y precio.

---

## 5. Cumplimiento SENIAT: Providencia SNAT/2026/00093

### 5.1 Marco normativo vigente

| Norma | Estado | Efecto |
|---|---|---|
| SNAT/2024/000121 (homologación de software) | **Derogada** por SNAT/2026/00084 (G.O. 43.435, 12-08-2026) | Ya no es obligatorio usar software homologado. |
| SNAT/2024/000102 (medios digitales) y Providencia 0071 | **Derogadas** por SNAT/2026/00093 | Sustituidas por el nuevo régimen. |
| **SNAT/2026/00093** | Publicada en G.O. 43.484 (21-10-2026); **vigente desde el 19-04-2027** | Nuevo régimen general de facturación. |

### 5.2 Contenido relevante para GEME

- **Libre elección del medio de emisión:**
  - Medios digitales o electrónicos: portal web o app del SENIAT, o **imprentas digitales y proveedores tecnológicos autorizados**.
  - Máquinas fiscales autorizadas.
  - El papel (talonarios de imprenta autorizada) solo para **contingencia**.
- **Cambio de medio:** se comunica a la Gerencia Regional o al Sector de Tributos Internos del domicilio fiscal.
- **Contingencia:** fallas prolongadas de electricidad o internet, averías o mantenimiento crítico. Superada la contingencia, los documentos se registran y se remiten **al día siguiente en PDF** al SENIAT o a la imprenta digital.
- **Requisitos de la factura** (según el resumen de Deloitte, Boletín 55):
  1. Denominación "Factura".
  2. Numeración consecutiva y única.
  3. **Número de control** consecutivo y único por emisor (en medios digitales).
  4. Nombre o razón social, domicilio fiscal y RIF del emisor.
  5. Fecha **y hora** de emisión.
  6. Datos del adquirente: nombre o razón social y RIF; para persona natural sin factura fiscal, cédula o pasaporte.
  7. Descripción, cantidad y monto; marca **(E)** en operaciones exentas, exoneradas o no gravadas.
  8. Ajustes al precio (descuentos, bonificaciones, recargos, anulaciones).
  9. Base imponible del IVA por alícuota, y montos exentos, exonerados o sujetos a percepción.
  10. IVA discriminado por alícuota.
  11. Total.
  12. **Moneda extranjera:** monto en divisas, **equivalente en Bs y tipo de cambio aplicado**.
- **Autorización de proveedores tecnológicos:** la norma exige que imprentas digitales y proveedores estén autorizados, pero los resúmenes disponibles **no detallan el procedimiento**. Hasta que se publique, la vía segura para GEME es **integrarse con un medio ya autorizado**.

### 5.3 Brechas de GEME

| Requisito | Estado actual | Acción |
|---|---|---|
| Emisión por medio autorizado | ❌ GEME genera la venta pero no es medio autorizado | **Integración por API con una imprenta digital autorizada** (vía principal) y, de forma opcional, controlador para máquina fiscal. |
| Número de control | Campo `control_number` en el libro; no se obtiene de un medio autorizado | Recibirlo de la imprenta digital y guardarlo en la orden y en el libro. |
| Numeración consecutiva | `order_number` interno | Usar el número de factura asignado por el medio autorizado. |
| Hora de emisión | ✅ (`created_at`) | Mostrarla en el comprobante. |
| Datos del adquirente | Parcial (cliente opcional en el TPV) | Pedir RIF o cédula cuando corresponda; validar el formato del RIF. |
| Moneda extranjera (equivalente en Bs y tasa) | Tasa BCV guardada por día (`ExchangeRateService`) | Guardar la tasa usada **en cada venta** e imprimir el equivalente en Bs y la tasa. |
| Marca (E) y desglose por alícuota | ✅ en libros | Verificar en el comprobante. |
| Notas de crédito y débito | ✅ (modelos existentes) | Emitirlas también por el medio autorizado. |
| Modo contingencia | ❌ | Pantalla para registrar facturas de talonario y exportar o enviar el PDF al día siguiente. |
| Libros de compras y ventas | ✅ (XML) | Mantener; revisar formatos tras la entrada en vigor. |
| Integridad e inalterabilidad | ✅ cadena hash (`FiscalIntegrityService`) | Mantener; documentar en el SGSI (control 8.15 y 5.33). |

### 5.4 Plan y calendario (hasta el 19-04-2027)

| Fase | Fecha objetivo | Entregable |
|---|---|---|
| 1. Selección de imprenta digital | dic. 2026 | Proveedor elegido, autorización verificada ante el SENIAT, documentación de API y contrato. |
| 2. Validación tributaria | dic. 2026 | Contador o asesor confirma la lectura del texto completo de la Gaceta 43.484. |
| 3. Desarrollo de la integración | ene.–feb. 2027 | Emisión de factura y notas vía API, número de control, tasa por venta, datos del adquirente. |
| 4. Modo contingencia | feb. 2027 | Registro de talonarios y envío del PDF. |
| 5. Pruebas en entorno de pruebas del proveedor | mar. 2027 | Batería de tests y piloto en `tienda.quorixtech.eu`. |
| 6. Producción | antes del 19-04-2027 | Comunicación de cambio de medio al SENIAT si corresponde. |

> **Aviso:** este análisis se basa en resúmenes de asesoras (Deloitte Boletín 55, Forvis Mazars FMI 0726, Crowe, PwC) y prensa especializada, no en el texto íntegro de la Gaceta Oficial. Antes del desarrollo debe confirmarlo un profesional tributario.

---

## 6. Hoja de ruta consolidada

| Prioridad | Iniciativa | Motivo | Dependencias |
|---|---|---|---|
| 1 | Cumplimiento SNAT/2026/00093 | Obligación legal con fecha (19-04-2027) | Imprenta digital autorizada, asesor tributario |
| 2 | Marketing / CRM | Paridad con la competencia; datos ya disponibles | Consentimiento de marketing |
| 3 | Cuentas por cobrar y conciliación | Paridad con la competencia | — |
| 4 | Asistente de IA | Diferenciación | Colas y worker en CapRover, proveedor de IA con contrato, actualización del SGSI |
| 5 | Financiamiento basado en datos | Modelo de negocio | Socio financiero, revisión legal (RGPD, Reglamento de IA), consentimiento |

---

## 7. Fuentes

- Fina — web de demostración: https://demos.finapartner.com/
- Fina, ronda semilla y NINA: https://ecosistemastartup.com/?p=127483 · https://latamlist.com/venezuelan-fintech-fina-raises-1m-seed-round/amp/ · https://thesaasnews.com/news/fina-raises-1m-seed
- Fina Presta (crédito embebido): https://ecosistemastartup.com/?p=120109
- Deloitte, Boletín Tributario 55 (SNAT/2026/0093): https://www.deloitte.com/content/dam/assets-zone4/latam/es/docs/services/tax/2026/Boletin-Informativo-Tributario-y-Legal-Edicion55.pdf
- Crowe, análisis SNAT/2026/00093: https://www.crowe.com/ve/news/analisis-de-la-nueva-providencia-administrativa-snat202600093
- Finanzas Digital, fin de la obligatoriedad de máquinas fiscales: https://finanzasdigital.com/seniat-levanta-uso-obligatorio-maquinas-fiscales-gaceta-oficial/
- Forvis Mazars FMI 0726, derogatoria de la homologación: https://www.forvismazars.com/ve/es/insights/forvis-mazars-insights/fmi-0726-derogatoria-de-sistemas-homologados
- El Diario, eliminación de la homologación: https://eldiario.com/2026/08/16/eliminacion-homologacion-facturacion/
- Precios API de Anthropic: https://platform.claude.com/docs/en/about-claude/pricing
- Comparativa de precios de modelos (15-11-2026): https://nhimg.org/llm-pricing-tracker
- Protección de datos en Venezuela: https://www.vozdeamerica.com/a/venezuela-vulneracion-derecho-privacidad-proteccion-datos/6419822.html
