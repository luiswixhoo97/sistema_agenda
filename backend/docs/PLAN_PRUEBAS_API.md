# Plan de Pruebas Funcionales - API de Ventas e Inventario

Este documento contiene las pruebas funcionales para todos los endpoints relacionados con productos, inventario, ventas, anticipos y pagos.

**Base URL:** `http://localhost:8000/api/admin`

**Nota:** El middleware de autenticación está comentado para facilitar las pruebas. En producción debe estar activo.

---

## A. PRODUCTOS (`/api/admin/productos`)

### Test 1: Listar productos

**Endpoint:** `GET /api/admin/productos`

**Headers:**
```
Content-Type: application/json
Accept: application/json
```

**Parámetros opcionales:**
- `categoria_id`: Filtrar por categoría
- `active`: true/false
- `buscar`: Buscar por código o nombre
- `per_page`: Items por página (default: 20)

**Respuesta esperada:**
- Status: `200 OK`
- Estructura:
  ```json
  {
    "success": true,
    "data": [
      {
        "id": 1,
        "codigo": "PROD001",
        "nombre": "Shampoo Reparador",
        "precio": 250.00,
        "inventario_actual": 49,
        "categoria": {...}
      }
    ],
    "pagination": {
      "current_page": 1,
      "last_page": 1,
      "total": 2
    }
  }
  ```

**Validaciones:**
- Debe retornar los 2 productos del seeder (PROD001, PROD002)
- Estructura de paginación correcta
- Campos completos de cada producto

---

### Test 2: Ver producto específico

**Endpoint:** `GET /api/admin/productos/{id}`

**Ejemplo:** `GET /api/admin/productos/1`

**Respuesta esperada:**
- Status: `200 OK`
- Debe incluir todos los campos del producto, incluyendo categoría

**Prueba de error:**
- `GET /api/admin/productos/999` debe retornar `404 Not Found`

---

### Test 3: Crear producto

**Endpoint:** `POST /api/admin/productos`

**Body:**
```json
{
  "codigo": "PROD003",
  "nombre": "Acondicionador Hidratante",
  "categoria_id": 1,
  "precio": 280.00,
  "costo": 170.00,
  "inventario_actual": 40,
  "inventario_minimo": 10
}
```

**Respuesta esperada:**
- Status: `201 Created`
- Mensaje: "Producto creado correctamente"
- Debe retornar el producto creado con ID asignado

**Validaciones:**
- Validar que el código sea único (si se intenta crear con código existente debe fallar)
- Validar campos requeridos (nombre, precio)

---

### Test 4: Actualizar producto

**Endpoint:** `PUT /api/admin/productos/{id}`

**Body:**
```json
{
  "precio": 270.00,
  "nombre": "Acondicionador Hidratante Premium"
}
```

**Respuesta esperada:**
- Status: `200 OK`
- Mensaje: "Producto actualizado correctamente"
- Debe reflejar los cambios realizados

---

### Test 5: Buscar productos

**Endpoint:** `GET /api/admin/productos/buscar?q={termino}`

**Ejemplos:**
- `GET /api/admin/productos/buscar?q=PROD001` - Buscar por código
- `GET /api/admin/productos/buscar?q=Shampoo` - Buscar por nombre

**Respuesta esperada:**
- Status: `200 OK`
- Debe retornar productos que coincidan con el término de búsqueda

---

## B. INVENTARIO (`/api/admin/inventario`)

### Test 6: Entrada manual de inventario

**Endpoint:** `POST /api/admin/inventario/entrada`

**Body:**
```json
{
  "producto_id": 1,
  "cantidad": 10,
  "motivo": "Compra a proveedor",
  "notas": "Compra de reposición de stock"
}
```

**Respuesta esperada:**
- Status: `201 Created`
- Mensaje: "Entrada de inventario registrada correctamente"
- Debe incluir el movimiento creado y el inventario_actual actualizado del producto

**Validaciones:**
- El `inventario_actual` del producto debe incrementarse
- Debe crearse un registro en `movimientos_inventario` con tipo `entrada_manual`

---

### Test 7: Salida manual de inventario

**Endpoint:** `POST /api/admin/inventario/salida`

**Body:**
```json
{
  "producto_id": 1,
  "cantidad": 5,
  "motivo": "Productos dañados",
  "notas": "Productos vencidos que se retiran del inventario"
}
```

**Respuesta esperada:**
- Status: `201 Created`
- Mensaje: "Salida de inventario registrada correctamente"
- El `inventario_actual` debe decrementarse

**Validaciones:**
- Validar que haya stock suficiente (si no hay, debe retornar error 422)
- Debe crearse un registro en `movimientos_inventario` con tipo `salida_manual`

---

### Test 8: Ajuste de inventario

**Endpoint:** `POST /api/admin/inventario/ajuste`

**Body:**
```json
{
  "producto_id": 1,
  "cantidad_final": 60,
  "motivo": "Ajuste por conteo físico",
  "notas": "Ajuste después de inventario físico"
}
```

**Respuesta esperada:**
- Status: `200 OK`
- El `inventario_actual` debe ajustarse al valor especificado
- Debe crearse un movimiento de tipo `ajuste`

**Nota:** Verificar si este endpoint existe en el controlador. Si no existe, omitir esta prueba.

---

### Test 9: Kardex de producto

**Endpoint:** `GET /api/admin/inventario/kardex/{producto_id}`

**Ejemplo:** `GET /api/admin/inventario/kardex/1`

**Respuesta esperada:**
- Status: `200 OK`
- Debe retornar todos los movimientos del producto ordenados cronológicamente
- Debe incluir entradas iniciales y salidas por venta del seeder

**Validaciones:**
- Verificar que aparezcan los movimientos creados por el seeder
- Orden cronológico correcto (más antiguo primero o más reciente primero)

---

### Test 10: Listar movimientos de inventario

**Endpoint:** `GET /api/admin/inventario/movimientos`

**Parámetros opcionales:**
- `producto_id`: Filtrar por producto
- `tipo`: Filtrar por tipo (entrada_manual, salida_manual, venta)
- `fecha_inicio`, `fecha_fin`: Filtrar por rango de fechas
- `per_page`: Items por página

**Respuesta esperada:**
- Status: `200 OK`
- Lista paginada de todos los movimientos
- Debe incluir información del producto relacionado

---

## C. MÉTODOS DE PAGO (`/api/admin/metodos-pago`)

### Test 11: Listar métodos de pago

**Endpoint:** `GET /api/admin/metodos-pago`

**Respuesta esperada:**
- Status: `200 OK`
- Debe incluir el método "Efectivo" creado por el seeder

---

### Test 12: Crear método de pago

**Endpoint:** `POST /api/admin/metodos-pago`

**Body:**
```json
{
  "nombre": "Tarjeta de Débito",
  "codigo": "TARJETA_DEBITO",
  "es_efectivo": false,
  "activo": true,
  "orden": 2
}
```

**Respuesta esperada:**
- Status: `201 Created`
- Método de pago creado exitosamente

---

### Test 13: Ver método de pago

**Endpoint:** `GET /api/admin/metodos-pago/{id}`

**Respuesta esperada:**
- Status: `200 OK`
- Información completa del método de pago

---

### Test 14: Actualizar método de pago

**Endpoint:** `PUT /api/admin/metodos-pago/{id}`

**Body:**
```json
{
  "nombre": "Efectivo Actualizado",
  "orden": 1
}
```

**Respuesta esperada:**
- Status: `200 OK`
- Cambios reflejados correctamente

---

### Test 15: Eliminar método de pago

**Endpoint:** `DELETE /api/admin/metodos-pago/{id}`

**Respuesta esperada:**
- Status: `200 OK` o `204 No Content`
- Método eliminado (o marcado como inactivo según la implementación)

---

## D. ANTICIPOS (`/api/admin/anticipos`)

### Test 16: Listar reglas de anticipo

**Endpoint:** `GET /api/admin/anticipos/reglas`

**Respuesta esperada:**
- Status: `200 OK`
- Debe incluir la regla creada por el seeder ("Anticipo ventas mayores a $500")

---

### Test 17: Crear regla de anticipo (tipo monto)

**Endpoint:** `POST /api/admin/anticipos/reglas`

**Body:**
```json
{
  "nombre": "Anticipo ventas altas",
  "descripcion": "Ventas mayores a $1000 requieren 50% de anticipo",
  "tipo_regla": "monto",
  "tipo_calculo": "porcentaje",
  "valor_calculo": 50.00,
  "prioridad": 2,
  "activo": true,
  "regla_monto": {
    "monto_minimo": 1000.00,
    "monto_maximo": null
  }
}
```

**Respuesta esperada:**
- Status: `201 Created`
- Regla creada con su relación en `reglas_anticipo_monto`

---

### Test 18: Ver regla de anticipo

**Endpoint:** `GET /api/admin/anticipos/reglas/{id}`

**Respuesta esperada:**
- Status: `200 OK`
- Información completa de la regla incluyendo relaciones (regla_monto, regla_fecha, etc.)

---

### Test 19: Actualizar regla de anticipo

**Endpoint:** `PUT /api/admin/anticipos/reglas/{id}`

**Body:**
```json
{
  "valor_calculo": 35.00,
  "activo": true
}
```

**Respuesta esperada:**
- Status: `200 OK`
- Cambios aplicados correctamente

---

### Test 20: Calcular anticipo

**Endpoint:** `POST /api/admin/anticipos/calcular`

**Body:**
```json
{
  "total": 600.00,
  "fecha_venta": "2025-01-15"
}
```

**Respuesta esperada:**
- Status: `200 OK`
- Estructura:
  ```json
  {
    "success": true,
    "data": {
      "requiere_anticipo": true,
      "monto_anticipo": 180.00,
      "regla_aplicada": {
        "id": 1,
        "nombre": "Anticipo ventas mayores a $500"
      },
      "reglas_evaluadas": [...]
    }
  }
  ```

**Validaciones:**
- Para total de 600.00, debe calcular 30% = 180.00
- Debe identificar la regla correcta aplicada

**Pruebas adicionales:**
- Total de 400.00 (menor a 500) → `requiere_anticipo: false`
- Total de 1500.00 → Calcular 30% = 450.00

---

### Test 21: Evaluar reglas aplicables

**Endpoint:** `POST /api/admin/anticipos/evaluar`

**Body:**
```json
{
  "total": 600.00,
  "fecha_venta": "2025-01-15",
  "servicios": []
}
```

**Respuesta esperada:**
- Status: `200 OK`
- Lista de todas las reglas con indicación de si aplican o no y la razón

---

## E. VENTAS (`/api/admin/ventas`)

### Test 22: Listar ventas

**Endpoint:** `GET /api/admin/ventas`

**Parámetros opcionales:**
- `cliente_id`: Filtrar por cliente
- `estado`: pendiente_pago, parcial, completada, cancelada
- `fecha_inicio`, `fecha_fin`: Filtrar por rango de fechas
- `per_page`: Items por página

**Respuesta esperada:**
- Status: `200 OK`
- Debe incluir la venta creada por el seeder
- Incluir información del cliente y detalles

---

### Test 23: Crear venta

**Endpoint:** `POST /api/admin/ventas`

**Body:**
```json
{
  "cliente_id": 1,
  "detalles": [
    {
      "tipo": "producto",
      "producto_id": 1,
      "cantidad": 2,
      "precio_unitario": 250.00,
      "descuento": 0,
      "impuesto": 0
    }
  ],
  "descuento_general": 0,
  "impuesto_total": 0,
  "notas": "Venta de prueba desde API"
}
```

**Respuesta esperada:**
- Status: `201 Created`
- Mensaje: "Venta creada correctamente"
- Debe incluir:
  - ID de la venta creada
  - Totales calculados
  - Si requiere anticipo y monto calculado
  - Detalles de la venta

**Validaciones:**
- Si el total >= 500, debe establecer `requiere_anticipo: true` y calcular `monto_anticipo_requerido`
- Debe reducir el `inventario_actual` de los productos
- Debe crear movimientos de inventario tipo `venta`
- Estado inicial: `pendiente_pago`

---

### Test 24: Ver venta específica

**Endpoint:** `GET /api/admin/ventas/{id}`

**Ejemplo:** `GET /api/admin/ventas/1` (ID de la venta del seeder)

**Respuesta esperada:**
- Status: `200 OK`
- Información completa incluyendo:
  - Cliente
  - Detalles con productos
  - Pagos realizados
  - Estado y saldos

---

### Test 25: Calcular totales de venta

**Endpoint:** `POST /api/admin/ventas/calcular-totales`

**Body:**
```json
{
  "detalles": [
    {
      "precio_unitario": 250.00,
      "cantidad": 2,
      "descuento": 10.00,
      "impuesto": 0
    },
    {
      "precio_unitario": 350.00,
      "cantidad": 1,
      "descuento": 0,
      "impuesto": 0
    }
  ],
  "descuento_general": 20.00
}
```

**Respuesta esperada:**
- Status: `200 OK`
- Estructura:
  ```json
  {
    "success": true,
    "data": {
      "subtotal": 890.00,
      "descuento_general": 20.00,
      "impuesto_total": 0,
      "total": 870.00
    }
  }
  ```

**Cálculo esperado:**
- Subtotal línea 1: (250 * 2) - 10 = 490
- Subtotal línea 2: (350 * 1) = 350
- Subtotal: 490 + 350 = 840
- Total: 840 - 20 = 820

---

### Test 26: Buscar productos para venta

**Endpoint:** `GET /api/admin/ventas/productos/buscar?q={termino}`

**Ejemplo:** `GET /api/admin/ventas/productos/buscar?q=Shampoo`

**Respuesta esperada:**
- Status: `200 OK`
- Lista de productos activos que coincidan con el término
- Debe incluir información relevante para venta (precio, stock disponible)

---

### Test 27: Actualizar venta

**Endpoint:** `PUT /api/admin/ventas/{id}`

**Body:**
```json
{
  "notas": "Notas actualizadas",
  "descuento_general": 50.00
}
```

**Respuesta esperada:**
- Status: `200 OK`
- Solo debe permitir actualizaciones si el estado es `pendiente_pago`
- Si la venta ya tiene pagos, debe retornar error

---

## F. PAGOS (`/api/admin/pagos`)

### Test 28: Registrar pago

**Endpoint:** `POST /api/admin/pagos/registrar`

**Body:**
```json
{
  "venta_id": 1,
  "metodo_pago_id": 1,
  "monto": 180.00,
  "monto_recibido": 200.00,
  "notas": "Pago de anticipo"
}
```

**Respuesta esperada:**
- Status: `201 Created`
- Mensaje: "Pago registrado correctamente"
- Estructura:
  ```json
  {
    "success": true,
    "data": {
      "pago": {
        "id": 1,
        "monto": 180.00,
        "cambio": 20.00,
        "estado_pago": "aprobado"
      },
      "venta": {
        "id": 1,
        "total_pagado": 180.00,
        "saldo_pendiente": 420.00,
        "estado": "parcial"
      }
    }
  }
  ```

**Validaciones:**
- No debe permitir pagos mayores al saldo pendiente
- Debe calcular cambio si es efectivo y se proporciona `monto_recibido`
- Debe actualizar el estado de la venta (`parcial` si hay saldo pendiente)
- Debe actualizar `total_pagado` y `saldo_pendiente`

**Prueba de pago completo:**
- Pagar el saldo pendiente restante (420.00)
- Estado debe cambiar a `completada`
- `saldo_pendiente` debe ser 0

---

### Test 29: Listar pagos de una venta

**Endpoint:** `GET /api/admin/pagos/venta/{venta_id}`

**Ejemplo:** `GET /api/admin/pagos/venta/1`

**Respuesta esperada:**
- Status: `200 OK`
- Lista de todos los pagos de la venta
- Debe incluir el pago creado por el seeder
- Información del método de pago usado

---

### Test 30: Reembolsar pago

**Endpoint:** `POST /api/admin/pagos/{id}/reembolsar`

**Body:**
```json
{
  "motivo": "Reembolso solicitado por cliente",
  "notas": "Cliente cambió de opinión"
}
```

**Respuesta esperada:**
- Status: `200 OK`
- El estado del pago debe cambiar a `reembolsado`
- La venta debe actualizar sus saldos (reducir `total_pagado`, aumentar `saldo_pendiente`)

---

## Orden sugerido de ejecución

1. **Productos** (Tests 1-5): Verificar datos del seeder y operaciones CRUD
2. **Inventario** (Tests 6-10): Verificar movimientos y kardex
3. **Métodos de pago** (Tests 11-15): Configuración básica
4. **Anticipos** (Tests 16-21): Verificar cálculo de anticipos
5. **Ventas** (Tests 22-27): Crear y gestionar ventas
6. **Pagos** (Tests 28-30): Procesar pagos y actualizar estados

## Notas importantes

- Todas las pruebas asumen que el seeder se ejecutó correctamente
- Los IDs pueden variar, ajustar según los datos reales en la base de datos
- Para pruebas de creación, usar códigos únicos para evitar conflictos
- Verificar que los cálculos matemáticos sean exactos (decimales)
- Al probar eliminaciones, verificar si son físicas o lógicas (soft delete)

## Herramientas recomendadas

- **Postman**: Para pruebas manuales de endpoints
- **Insomnia**: Alternativa a Postman
- **cURL**: Para pruebas desde terminal
- **PHPUnit**: Para automatizar las pruebas (futuro)
