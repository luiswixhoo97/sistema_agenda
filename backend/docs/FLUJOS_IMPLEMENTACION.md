# Flujos de Implementación - Sistema de Agenda

Este documento describe los flujos de implementación para:
- Productos
- Inventario
- Configuración de Anticipos
- Modificaciones a la Agenda para Registrar Anticipos
- Generación de Ventas
- Finalización de Ventas

---

## 1. Flujo de Implementación de Productos

### 1.1. Gestión de Categorías de Productos

**Endpoints:**
- `GET /api/v1/admin/categorias-productos` - Listar categorías
- `POST /api/v1/admin/categorias-productos` - Crear categoría
- `GET /api/v1/admin/categorias-productos/{id}` - Ver categoría
- `PUT /api/v1/admin/categorias-productos/{id}` - Actualizar categoría
- `DELETE /api/v1/admin/categorias-productos/{id}` - Eliminar categoría (soft delete)

**Flujo de Creación:**
1. Validar datos de entrada (nombre requerido)
2. Verificar que no exista una categoría con el mismo nombre (activa)
3. Crear registro en `categorias_productos`
4. Retornar categoría creada

**Flujo de Actualización:**
1. Validar que la categoría exista y esté activa
2. Validar datos de entrada
3. Verificar que el nuevo nombre no esté en uso por otra categoría
4. Actualizar registro
5. Retornar categoría actualizada

**Flujo de Eliminación:**
1. Validar que la categoría exista
2. Verificar que no tenga productos asociados activos
3. Realizar soft delete
4. Retornar confirmación

### 1.2. Gestión de Productos

**Endpoints:**
- `GET /api/v1/admin/productos` - Listar productos (con filtros: categoría, activos, búsqueda)
- `POST /api/v1/admin/productos` - Crear producto
- `GET /api/v1/admin/productos/buscar?q={termino}` - Buscar productos
- `GET /api/v1/admin/productos/{id}` - Ver producto
- `PUT /api/v1/admin/productos/{id}` - Actualizar producto
- `DELETE /api/v1/admin/productos/{id}` - Eliminar producto (soft delete)
- `POST /api/v1/admin/productos/{id}/qr` - Generar código QR

**Flujo de Creación:**
1. Validar datos requeridos:
   - `codigo`: único, máximo 100 caracteres
   - `nombre`: requerido, máximo 255 caracteres
   - `precio`: requerido, decimal positivo
   - `categoria_id`: opcional, debe existir si se proporciona
2. Verificar que el código no esté en uso
3. Crear registro en `productos`:
   - `inventario_actual`: default 0
   - `inventario_minimo`: default 0
   - `costo`: default 0
   - `active`: default true
4. Retornar producto creado

**Flujo de Búsqueda:**
1. Recibir término de búsqueda
2. Buscar en `codigo` y `nombre` (LIKE)
3. Filtrar solo productos activos
4. Retornar resultados paginados

**Flujo de Actualización:**
1. Validar que el producto exista y esté activo
2. Validar datos de entrada
3. Si se cambia el código, verificar que no esté en uso
4. Actualizar registro
5. Retornar producto actualizado

**Flujo de Eliminación:**
1. Validar que el producto exista
2. Verificar que no tenga movimientos de inventario o ventas asociadas
3. Realizar soft delete
4. Retornar confirmación

**Flujo de Generación de QR:**
1. Validar que el producto exista
2. Generar código QR con información del producto (ID, código, nombre)
3. Retornar imagen QR o datos para generar en frontend

---

## 2. Flujo de Implementación de Inventario

### 2.1. Movimientos de Inventario

**Endpoints:**
- `POST /api/v1/admin/inventario/entrada` - Registrar entrada manual
- `POST /api/v1/admin/inventario/salida` - Registrar salida manual
- `POST /api/v1/admin/inventario/ajuste` - Ajustar inventario
- `GET /api/v1/admin/inventario/kardex/{producto_id}` - Ver kardex de producto
- `GET /api/v1/admin/inventario/movimientos` - Listar movimientos (con filtros)

**Flujo de Entrada Manual:**
1. Validar datos:
   - `producto_id`: requerido, debe existir
   - `cantidad`: requerido, entero positivo
   - `motivo`: opcional
   - `notas`: opcional
2. Obtener producto y verificar que esté activo
3. Crear registro en `movimientos_inventario`:
   - `tipo`: 'entrada_manual'
   - `cantidad`: valor positivo
   - `user_id`: usuario autenticado
4. Actualizar `productos.inventario_actual` (sumar cantidad)
5. Retornar movimiento creado y nuevo stock

**Flujo de Salida Manual:**
1. Validar datos:
   - `producto_id`: requerido, debe existir
   - `cantidad`: requerido, entero positivo
   - `motivo`: opcional
   - `notas`: opcional
2. Obtener producto y verificar que esté activo
3. Verificar stock disponible (`inventario_actual >= cantidad`)
4. Crear registro en `movimientos_inventario`:
   - `tipo`: 'salida_manual'
   - `cantidad`: valor negativo (o positivo según lógica)
   - `user_id`: usuario autenticado
5. Actualizar `productos.inventario_actual` (restar cantidad)
6. Verificar si el stock queda por debajo del mínimo y generar alerta si aplica
7. Retornar movimiento creado y nuevo stock

**Flujo de Ajuste:**
1. Validar datos:
   - `producto_id`: requerido
   - `nuevo_stock`: requerido, entero >= 0
   - `motivo`: requerido (explicación del ajuste)
   - `notas`: opcional
2. Obtener producto y verificar que esté activo
3. Calcular diferencia: `nuevo_stock - inventario_actual`
4. Si diferencia > 0: crear entrada manual
5. Si diferencia < 0: crear salida manual
6. Si diferencia = 0: no hacer nada
7. Retornar movimiento(s) creado(s) y nuevo stock

**Flujo de Kardex:**
1. Validar que el producto exista
2. Obtener todos los movimientos del producto ordenados por fecha (más reciente primero)
3. Calcular stock acumulado por movimiento
4. Retornar lista de movimientos con:
   - Fecha y hora
   - Tipo de movimiento
   - Cantidad (positiva o negativa)
   - Stock anterior
   - Stock posterior
   - Usuario responsable
   - Motivo y notas

**Flujo de Listado de Movimientos:**
1. Aplicar filtros opcionales:
   - `producto_id`: filtrar por producto
   - `tipo`: filtrar por tipo de movimiento
   - `fecha_inicio` y `fecha_fin`: rango de fechas
   - `user_id`: filtrar por usuario
2. Ordenar por fecha (más reciente primero)
3. Paginar resultados
4. Retornar lista con información completa

### 2.2. Automatización de Inventario en Ventas

**Flujo Automático al Vender Productos:**
1. Cuando se crea una venta con productos, NO se descuenta inventario automáticamente
2. Cuando se registra un pago y la venta se completa:
   - Para cada `venta_detalle` de tipo 'producto':
     - Verificar stock disponible
     - Crear movimiento de tipo 'venta':
       - `cantidad`: negativa (o positiva según lógica)
       - `referencia_id`: ID de la venta
       - `referencia_tipo`: 'venta'
     - Actualizar `productos.inventario_actual`
3. Si no hay stock suficiente, marcar venta como pendiente de stock

---

## 3. Flujo de Configuración de Anticipos

### 3.1. Gestión de Reglas de Anticipo

**Endpoints:**
- `GET /api/v1/admin/anticipos/reglas` - Listar reglas
- `POST /api/v1/admin/anticipos/reglas` - Crear regla
- `GET /api/v1/admin/anticipos/reglas/{id}` - Ver regla
- `PUT /api/v1/admin/anticipos/reglas/{id}` - Actualizar regla
- `DELETE /api/v1/admin/anticipos/reglas/{id}` - Eliminar regla
- `POST /api/v1/admin/anticipos/calcular` - Calcular anticipo para una venta
- `POST /api/v1/admin/anticipos/evaluar` - Evaluar si una venta requiere anticipo

**Estructura de Reglas:**

Cada regla tiene:
- `tipo_regla`: 'fecha', 'monto', o 'servicio'
- `tipo_calculo`: 'porcentaje' o 'monto_fijo'
- `valor_calculo`: porcentaje (0-100) o monto fijo
- `prioridad`: número entero (mayor = mayor prioridad)
- `activo`: boolean

**Flujo de Creación de Regla por Fecha:**
1. Validar datos base:
   - `nombre`: requerido
   - `tipo_regla`: 'fecha'
   - `tipo_calculo`: 'porcentaje' o 'monto_fijo'
   - `valor_calculo`: requerido, positivo
   - `prioridad`: opcional, default 0
2. Validar datos específicos de fecha:
   - `fecha_inicio`: requerido, formato fecha
   - `fecha_fin`: requerido, formato fecha, >= fecha_inicio
   - `aplica_todos_dias`: boolean, default true
   - `dias_semana`: array de números [1-7] si `aplica_todos_dias` = false
3. Crear registro en `reglas_anticipo`
4. Crear registro en `reglas_anticipo_fecha`
5. Retornar regla creada con detalles

**Flujo de Creación de Regla por Monto:**
1. Validar datos base (igual que regla por fecha)
2. Validar datos específicos de monto:
   - `monto_minimo`: requerido, decimal positivo
   - `monto_maximo`: opcional, decimal positivo, >= monto_minimo
3. Crear registro en `reglas_anticipo`
4. Crear registro en `reglas_anticipo_monto`
5. Retornar regla creada con detalles

**Flujo de Creación de Regla por Servicio:**
1. Validar datos base (igual que regla por fecha)
2. Validar datos específicos de servicio:
   - `servicios`: array de IDs de servicios, requerido, no vacío
   - Cada servicio debe existir
3. Crear registro en `reglas_anticipo`
4. Crear registros en `reglas_anticipo_servicio` (uno por cada servicio)
5. Retornar regla creada con detalles

**Flujo de Cálculo de Anticipo:**
1. Recibir datos de la venta:
   - `fecha_venta`: fecha/hora de la venta
   - `total`: monto total de la venta
   - `servicios`: array de IDs de servicios incluidos
2. Obtener todas las reglas activas ordenadas por prioridad (descendente)
3. Para cada regla, evaluar si aplica:
   - **Regla por Fecha:**
     - Verificar si `fecha_venta` está entre `fecha_inicio` y `fecha_fin`
     - Si `aplica_todos_dias` = false, verificar que el día de la semana esté en `dias_semana`
   - **Regla por Monto:**
     - Verificar si `total >= monto_minimo`
     - Si existe `monto_maximo`, verificar si `total <= monto_maximo`
   - **Regla por Servicio:**
     - Verificar si algún servicio de la venta está en la lista de servicios de la regla
4. Si la regla aplica, calcular anticipo:
   - Si `tipo_calculo` = 'porcentaje': `anticipo = total * (valor_calculo / 100)`
   - Si `tipo_calculo` = 'monto_fijo': `anticipo = valor_calculo`
5. Seleccionar la regla con mayor prioridad que requiera el mayor anticipo
6. Retornar:
   - `requiere_anticipo`: boolean
   - `monto_anticipo_requerido`: decimal (0 si no requiere)
   - `regla_aplicada`: información de la regla seleccionada

**Flujo de Evaluación:**
1. Similar al cálculo, pero solo retorna si requiere anticipo (boolean)
2. Útil para validaciones rápidas sin necesidad de calcular el monto

**Flujo de Actualización:**
1. Validar que la regla exista
2. Validar datos según el tipo de regla
3. Actualizar `reglas_anticipo`
4. Actualizar tabla relacionada (fecha, monto, o servicios)
5. Retornar regla actualizada

**Flujo de Eliminación:**
1. Validar que la regla exista
2. Eliminar registros relacionados (cascade delete)
3. Eliminar regla principal
4. Retornar confirmación

---

## 4. Modificaciones a la Agenda para Registrar Anticipos

### 4.1. Modificaciones en el Flujo de Creación de Citas

**Cambios necesarios:**

1. **Al agendar una cita con servicios:**
   - Calcular el total de la venta (suma de precios de servicios)
   - Evaluar si requiere anticipo usando el sistema de reglas
   - Si requiere anticipo:
     - Mostrar al usuario el monto de anticipo requerido
     - Permitir registrar el anticipo antes de confirmar la cita
     - Guardar el anticipo en la venta asociada

2. **Estructura de datos:**
   - La cita se crea normalmente
   - Se crea una venta asociada (o se usa una existente si hay múltiples servicios)
   - La venta tiene:
     - `requiere_anticipo`: true/false
     - `monto_anticipo_requerido`: monto calculado
     - `monto_anticipo_pagado`: 0 inicialmente

### 4.2. Flujo de Registro de Anticipo en Cita

**Endpoint propuesto:**
- `POST /api/v1/admin/citas/{cita_id}/anticipo` - Registrar anticipo de una cita

**Flujo:**
1. Validar que la cita exista y esté en estado 'pendiente' o 'confirmada'
2. Obtener la venta asociada a la cita
3. Verificar que la venta requiera anticipo
4. Validar datos del pago:
   - `metodo_pago_id`: requerido
   - `monto`: requerido, debe ser >= `monto_anticipo_requerido`
   - `monto_recibido`: si es efectivo, requerido
   - `cambio`: calcular si es efectivo
5. Crear registro en `venta_pagos`:
   - `monto`: monto del anticipo
   - `estado_pago`: 'aprobado' (si es efectivo) o 'pendiente' (si es online)
6. Actualizar `ventas`:
   - `monto_anticipo_pagado`: sumar monto pagado
   - `total_pagado`: sumar monto pagado
   - `saldo_pendiente`: recalcular
   - `estado`: actualizar según saldo
7. Si el anticipo es suficiente, confirmar la cita automáticamente
8. Retornar información del pago y estado actualizado

### 4.3. Modificaciones en el Endpoint de Creación de Citas

**Cambios en `POST /api/v1/admin/citas`:**

1. Después de crear la cita y calcular el precio:
   - Crear o actualizar venta asociada
   - Evaluar anticipo usando `POST /api/v1/admin/anticipos/calcular`
   - Si requiere anticipo, incluir en la respuesta:
     ```json
     {
       "cita": {...},
       "venta": {...},
       "anticipo": {
         "requiere_anticipo": true,
         "monto_anticipo_requerido": 500.00,
         "monto_anticipo_pagado": 0.00,
         "puede_confirmar": false
       }
     }
     ```

2. Validación:
   - Si requiere anticipo y no se ha pagado, la cita se crea pero no se confirma automáticamente
   - El usuario debe registrar el anticipo antes de confirmar

### 4.4. Modificaciones en el Endpoint de Confirmación de Citas

**Cambios en `PUT /api/v1/admin/citas/{id}/confirmar`:**

1. Antes de confirmar:
   - Verificar si la venta asociada requiere anticipo
   - Si requiere anticipo, verificar que `monto_anticipo_pagado >= monto_anticipo_requerido`
   - Si no se ha pagado el anticipo, retornar error con información del anticipo requerido

2. Si el anticipo está pagado:
   - Confirmar la cita normalmente
   - Retornar confirmación

---

## 5. Flujo de Generación de Ventas

### 5.1. Ventas desde Citas

**Endpoints:**
- `POST /api/v1/admin/ventas-citas/crear-desde-cita/{cita_id}` - Crear venta desde cita
- `POST /api/v1/admin/ventas-citas/{venta_id}/productos` - Agregar productos a venta de cita
- `POST /api/v1/admin/ventas-citas/{venta_id}/finalizar` - Finalizar venta

**Flujo de Creación de Venta desde Cita:**
1. Validar que la cita exista y esté en estado válido ('confirmada', 'en_proceso', 'completada')
2. Verificar si ya existe una venta asociada a la cita
3. Si no existe, crear venta:
   - `cliente_id`: de la cita
   - `fecha_venta`: fecha/hora actual
   - `subtotal`: precio del servicio
   - `descuento_general`: 0 (o calcular si hay promoción)
   - `impuesto_total`: 0 (o calcular si aplica)
   - `total`: subtotal - descuento + impuesto
   - `estado`: 'pendiente_pago'
   - Evaluar anticipo y establecer campos correspondientes
4. Crear `venta_detalle`:
   - `tipo`: 'servicio'
   - `servicio_id`: de la cita
   - `cita_id`: ID de la cita
   - `cantidad`: 1
   - `precio_unitario`: precio del servicio
   - `subtotal_linea`: precio del servicio
5. Retornar venta creada con detalles

**Flujo de Agregar Productos a Venta de Cita:**
1. Validar que la venta exista y esté en estado 'pendiente_pago' o 'parcial'
2. Validar datos del producto:
   - `producto_id`: requerido, debe existir y estar activo
   - `cantidad`: requerido, entero positivo
   - Verificar stock disponible
3. Obtener precio del producto
4. Calcular subtotal de línea: `cantidad * precio_unitario`
5. Crear `venta_detalle`:
   - `tipo`: 'producto'
   - `producto_id`: ID del producto
   - `cantidad`: cantidad solicitada
   - `precio_unitario`: precio del producto
   - `subtotal_linea`: subtotal calculado
6. Actualizar `ventas`:
   - Recalcular `subtotal` (suma de todos los detalles)
   - Recalcular `total` (subtotal - descuento + impuesto)
   - Recalcular anticipo si aplica
7. Retornar venta actualizada con nuevos detalles

**Flujo de Finalización de Venta:**
1. Validar que la venta exista y esté en estado válido
2. Verificar que todos los productos tengan stock disponible
3. Si la venta requiere anticipo, verificar que esté pagado
4. Procesar pagos pendientes (si hay pagos online)
5. Descontar inventario de productos:
   - Para cada `venta_detalle` de tipo 'producto':
     - Verificar stock
     - Crear movimiento de inventario tipo 'venta'
     - Actualizar `productos.inventario_actual`
6. Actualizar estado de la venta:
   - Si `total_pagado >= total`: `estado = 'completada'`
   - Si `total_pagado > 0`: `estado = 'parcial'`
   - Si `total_pagado = 0`: `estado = 'pendiente_pago'`
7. Si la venta está completada, actualizar estado de citas asociadas a 'completada'
8. Retornar venta finalizada

### 5.2. Ventas Directas (Punto de Venta)

**Endpoints:**
- `GET /api/v1/admin/ventas` - Listar ventas
- `POST /api/v1/admin/ventas` - Crear venta directa
- `POST /api/v1/admin/ventas/calcular-totales` - Calcular totales de una venta
- `GET /api/v1/admin/ventas/productos/buscar` - Buscar productos para venta
- `GET /api/v1/admin/ventas/{id}` - Ver venta
- `PUT /api/v1/admin/ventas/{id}` - Actualizar venta
- `DELETE /api/v1/admin/ventas/{id}` - Eliminar venta (soft delete)

**Flujo de Creación de Venta Directa:**
1. Validar datos:
   - `cliente_id`: opcional (venta a cliente o walk-in)
   - `detalles`: array de productos/servicios, requerido, no vacío
2. Para cada detalle:
   - Validar que el producto/servicio exista
   - Validar cantidad y precio
   - Verificar stock si es producto
3. Calcular totales:
   - `subtotal`: suma de subtotales de líneas
   - `descuento_general`: aplicar si hay promoción general
   - `impuesto_total`: calcular impuestos
   - `total`: subtotal - descuento + impuesto
4. Evaluar anticipo
5. Crear venta
6. Crear detalles de venta
7. Retornar venta creada

**Flujo de Cálculo de Totales:**
1. Recibir array de detalles (productos/servicios con cantidades)
2. Para cada detalle:
   - Obtener precio del producto/servicio
   - Calcular subtotal de línea
   - Aplicar descuentos si hay promociones
3. Calcular totales:
   - Subtotal general
   - Descuentos
   - Impuestos
   - Total final
4. Evaluar anticipo
5. Retornar totales calculados (sin crear venta)

---

## 6. Flujo de Finalización de Ventas

### 6.1. Registro de Pagos

**Endpoints:**
- `POST /api/v1/admin/pagos/registrar` - Registrar pago (efectivo/tarjeta/transferencia)
- `POST /api/v1/admin/pagos/online` - Procesar pago online (Mercado Pago/Stripe)
- `GET /api/v1/admin/pagos/venta/{venta_id}` - Listar pagos de una venta
- `POST /api/v1/admin/pagos/{id}/reembolsar` - Reembolsar pago

**Flujo de Registro de Pago (Efectivo/Tarjeta/Transferencia):**
1. Validar datos:
   - `venta_id`: requerido, debe existir
   - `metodo_pago_id`: requerido, debe existir y estar activo
   - `monto`: requerido, decimal positivo
   - `monto_recibido`: si es efectivo, requerido, >= monto
   - `cambio`: calcular si es efectivo (monto_recibido - monto)
2. Validar que la venta no esté cancelada o completada
3. Validar que el monto no exceda el saldo pendiente
4. Crear registro en `venta_pagos`:
   - `monto`: monto del pago
   - `monto_recibido`: si es efectivo
   - `cambio`: si es efectivo
   - `es_efectivo`: según método de pago
   - `estado_pago`: 'aprobado' (para métodos offline)
   - `user_id`: usuario autenticado
5. Actualizar `ventas`:
   - `total_pagado`: sumar monto
   - `saldo_pendiente`: recalcular
   - `estado`: actualizar según saldo
   - Si es anticipo, actualizar `monto_anticipo_pagado`
6. Si la venta se completa, descontar inventario de productos
7. Retornar pago registrado y venta actualizada

**Flujo de Pago Online:**
1. Validar datos:
   - `venta_id`: requerido
   - `metodo_pago_id`: requerido (Mercado Pago o Stripe)
   - `monto`: requerido
   - Datos adicionales según proveedor (token, email, etc.)
2. Validar que la venta no esté cancelada
3. Crear registro en `venta_pagos` con `estado_pago = 'pendiente'`
4. Procesar pago con proveedor:
   - Mercado Pago: crear preferencia o pago
   - Stripe: crear payment intent
5. Guardar `transaccion_id` y `metadata_pago` en `venta_pagos`
6. Retornar información del pago (URL de pago, estado, etc.)
7. El webhook actualizará el estado cuando el pago se complete

**Flujo de Webhook (Mercado Pago/Stripe):**
1. Recibir webhook del proveedor
2. Validar autenticidad del webhook (firma, token, etc.)
3. Crear registro en `webhooks_pagos`
4. Procesar evento según tipo:
   - `payment.approved`: actualizar `venta_pagos.estado_pago = 'aprobado'`
   - `payment.rejected`: actualizar `venta_pagos.estado_pago = 'rechazado'`
   - `payment.refunded`: actualizar `venta_pagos.estado_pago = 'reembolsado'`
5. Actualizar `ventas`:
   - Recalcular `total_pagado`
   - Recalcular `saldo_pendiente`
   - Actualizar `estado`
6. Si el pago se aprueba y la venta se completa, descontar inventario
7. Marcar webhook como procesado
8. Retornar respuesta al proveedor

**Flujo de Listado de Pagos:**
1. Validar que la venta exista
2. Obtener todos los pagos de la venta ordenados por fecha
3. Retornar lista con información completa de cada pago

**Flujo de Reembolso:**
1. Validar que el pago exista y esté aprobado
2. Validar que el pago sea online (tiene `transaccion_id`)
3. Procesar reembolso con proveedor:
   - Mercado Pago: crear reembolso
   - Stripe: crear refund
4. Actualizar `venta_pagos.estado_pago = 'reembolsado'`
5. Actualizar `ventas`:
   - Restar monto de `total_pagado`
   - Recalcular `saldo_pendiente`
   - Actualizar `estado`
6. Si se reembolsó inventario, revertir movimientos
7. Retornar confirmación

### 6.2. Finalización Automática

**Condiciones para finalización automática:**
1. Cuando `total_pagado >= total`:
   - `estado` = 'completada'
   - Descontar inventario de productos
   - Actualizar estado de citas asociadas
2. Cuando `total_pagado > 0` pero `< total`:
   - `estado` = 'parcial'
3. Cuando `total_pagado = 0`:
   - `estado` = 'pendiente_pago'

**Flujo de Actualización de Estado:**
1. Recalcular `total_pagado` (suma de pagos aprobados)
2. Recalcular `saldo_pendiente` (total - total_pagado)
3. Determinar estado según condiciones
4. Guardar cambios
5. Si se completa, ejecutar acciones de finalización

---

## 7. Consideraciones de Implementación

### 7.1. Transacciones de Base de Datos

- Todas las operaciones que involucren múltiples tablas deben usar transacciones
- Ejemplos:
  - Crear venta + detalles
  - Registrar pago + actualizar venta + descontar inventario
  - Crear movimiento de inventario + actualizar stock

### 7.2. Validaciones Críticas

- **Stock**: Verificar siempre antes de vender productos
- **Anticipos**: Validar que se haya pagado antes de confirmar citas
- **Pagos**: Validar que no se exceda el saldo pendiente
- **Estados**: Validar transiciones de estado permitidas

### 7.3. Notificaciones

- Notificar cuando el stock esté bajo
- Notificar cuando se requiera anticipo
- Notificar cuando un pago online se apruebe/rechace
- Notificar cuando una venta se complete

### 7.4. Auditoría

- Registrar todas las operaciones críticas en `auditoria`
- Ejemplos:
  - Creación/modificación de ventas
  - Registro de pagos
  - Movimientos de inventario
  - Cambios en reglas de anticipo

### 7.5. Performance

- Usar índices en consultas frecuentes
- Paginar resultados de listados
- Cachear reglas de anticipo activas
- Optimizar consultas de inventario

---

## 8. Diagrama de Flujo Simplificado

```
[Cliente solicita cita]
    ↓
[Calcular total de servicios]
    ↓
[Evaluar reglas de anticipo]
    ↓
¿Requiere anticipo?
    ├─ NO → [Crear cita y venta] → [Confirmar cita]
    └─ SÍ → [Mostrar monto de anticipo]
            ↓
        [Registrar anticipo]
            ↓
        ¿Anticipo suficiente?
            ├─ NO → [Cita pendiente de anticipo]
            └─ SÍ → [Confirmar cita] → [Crear venta]
                    ↓
                [Agregar productos opcionales]
                    ↓
                [Registrar pagos restantes]
                    ↓
                [Finalizar venta]
                    ↓
                [Descontar inventario]
                    ↓
                [Completar cita]
```

---

## 9. Casos de Uso Especiales

### 9.1. Venta con Múltiples Servicios

- Una venta puede tener múltiples servicios (múltiples citas)
- Cada servicio en `venta_detalle` tiene su `cita_id`
- El anticipo se calcula sobre el total de todos los servicios

### 9.2. Venta con Productos y Servicios

- Una venta puede incluir servicios (de citas) y productos
- Los productos se agregan después de crear la venta desde la cita
- El inventario solo se descuenta cuando la venta se completa

### 9.3. Pagos Parciales

- Se pueden registrar múltiples pagos para una venta
- Cada pago se registra en `venta_pagos`
- La venta se completa cuando `total_pagado >= total`

### 9.4. Cancelación de Ventas

- Si una venta se cancela antes de completarse:
  - Revertir movimientos de inventario (si se descontó)
  - Revertir pagos (reembolsar si es necesario)
  - Cancelar citas asociadas
  - Marcar venta como cancelada

---

## 10. Próximos Pasos de Implementación

1. **Backend:**
   - Implementar controladores para productos e inventario
   - Implementar controlador de anticipos
   - Modificar controlador de citas para incluir anticipos
   - Implementar controladores de ventas y pagos
   - Implementar webhooks de pagos online

2. **Frontend:**
   - UI para gestión de productos y categorías
   - UI para gestión de inventario
   - UI para configuración de reglas de anticipo
   - Modificar UI de citas para mostrar/registrar anticipos
   - UI para ventas y pagos

3. **Testing:**
   - Tests unitarios para cada flujo
   - Tests de integración para flujos completos
   - Tests de reglas de anticipo
   - Tests de webhooks

4. **Documentación:**
   - Documentar endpoints en detalle
   - Documentar casos de error
   - Documentar ejemplos de uso
