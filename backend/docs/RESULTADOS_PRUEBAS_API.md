# Reporte de Pruebas de API - Sistema de Ventas e Inventario

**Fecha de ejecucion:** 2025-12-16 12:35:25

**Base URL:** http://localhost:8000/api/admin

## Resumen

| Metrica | Cantidad |
|---------|----------|
| Total de pruebas | 31 |
| Exitosas | 26 |
| Fallidas | 5 |
| Porcentaje de exito | 83.87% |

---

## Detalle de Pruebas

### [OK] Test 1: Listar productos

- **Metodo:** GET
- **Endpoint:** /productos
- **Status Code:** 200
- **Estado:** Exitoso
- **Timestamp:** 2025-12-16 12:34:59

**Respuesta:**
`json
{"success":true,"data":[{"id":2,"codigo":"PROD002","nombre":"Crema Hidratante","foto":null,"categoria_id":1,"inventario_minimo":10,"inventario_actual":29,"costo":"200.00","precio":"350.00","precio_texto":"$350.00","active":true,"stock_bajo":false,"categoria":{"id":1,"nombre":"Productos de Belleza"}},{"id":1,"codigo":"PROD001","nombre":"Shampoo Reparador","foto":null,"categoria_id":1,"inventario_minimo":10,"inventario_actual":49,"costo":"150.00","precio":"250.00","precio_texto":"$250.00","active":true,"stock_bajo":false,"categoria":{"id":1,"nombre":"Productos de Belleza"}}],"pagination":{"current_page":1,"last_page":1,"total":2}}
`\n
---

### [OK] Test 2: Ver producto especifico

- **Metodo:** GET
- **Endpoint:** /productos/2
- **Status Code:** 200
- **Estado:** Exitoso
- **Timestamp:** 2025-12-16 12:35:20

**Respuesta:**
`json
{"success":true,"data":{"id":2,"codigo":"PROD002","nombre":"Crema Hidratante","foto":null,"categoria_id":1,"inventario_minimo":10,"inventario_actual":29,"costo":"200.00","precio":"350.00","precio_texto":"$350.00","active":true,"stock_bajo":false,"categoria":{"id":1,"nombre":"Productos de Belleza"}}}
`\n
---

### [OK] Test 2b: Producto inexistente (404)

- **Metodo:** GET
- **Endpoint:** /productos/999
- **Status Code:** 404
- **Estado:** Exitoso
- **Timestamp:** 2025-12-16 12:35:20

**Respuesta:**
`json
{"success":false,"message":"Producto no encontrado"}
`\n
---

### [OK] Test 3: Crear producto

- **Metodo:** POST
- **Endpoint:** /productos
- **Status Code:** 201
- **Estado:** Exitoso
- **Timestamp:** 2025-12-16 12:35:20

**Respuesta:**
`json
{"success":true,"message":"Producto creado correctamente","data":{"id":3,"codigo":"PROD_TEST_20251216123520","nombre":"Acondicionador Hidratante","foto":null,"categoria_id":1,"inventario_minimo":10,"inventario_actual":40,"costo":"170.00","precio":"280.00","precio_texto":"$280.00","active":true,"stock_bajo":false,"categoria":{"id":1,"nombre":"Productos de Belleza"}}}
`\n
---

### [OK] Test 4: Actualizar producto

- **Metodo:** PUT
- **Endpoint:** /productos/3
- **Status Code:** 200
- **Estado:** Exitoso
- **Timestamp:** 2025-12-16 12:35:20

**Respuesta:**
`json
{"success":true,"message":"Producto actualizado correctamente","data":{"id":3,"codigo":"PROD_TEST_20251216123520","nombre":"Acondicionador Hidratante Premium","foto":null,"categoria_id":1,"inventario_minimo":10,"inventario_actual":40,"costo":"170.00","precio":"270.00","precio_texto":"$270.00","active":true,"stock_bajo":false,"categoria":{"id":1,"nombre":"Productos de Belleza"}}}
`\n
---

### [FAIL] Test 5: Buscar productos

- **Metodo:** GET
- **Endpoint:** /productos/buscar?q=PROD
- **Status Code:** 422
- **Estado:** Fallido
- **Timestamp:** 2025-12-16 12:35:21

**Respuesta:**
`json
{"message":"validation.required","errors":{"termino":["validation.required"]}}
`\n
---

### [OK] Test 6: Entrada manual de inventario

- **Metodo:** POST
- **Endpoint:** /inventario/entrada
- **Status Code:** 201
- **Estado:** Exitoso
- **Timestamp:** 2025-12-16 12:35:21

**Respuesta:**
`json
{"success":true,"message":"Entrada de inventario registrada correctamente","data":{"movimiento":{"id":5,"tipo":"entrada_manual","cantidad":10,"motivo":"Compra a proveedor","created_at":"2025-12-16T18:35:21.000000Z"},"producto":{"id":2,"nombre":"Crema Hidratante","inventario_actual":39}}}
`\n
---

### [OK] Test 7: Salida manual de inventario

- **Metodo:** POST
- **Endpoint:** /inventario/salida
- **Status Code:** 201
- **Estado:** Exitoso
- **Timestamp:** 2025-12-16 12:35:21

**Respuesta:**
`json
{"success":true,"message":"Salida de inventario registrada correctamente","data":{"movimiento":{"id":6,"tipo":"salida_manual","cantidad":5,"motivo":"Productos danados","created_at":"2025-12-16T18:35:21.000000Z"},"producto":{"id":2,"nombre":"Crema Hidratante","inventario_actual":34}}}
`\n
---

### [FAIL] Test 8: Ajuste de inventario

- **Metodo:** POST
- **Endpoint:** /inventario/ajuste
- **Status Code:** 422
- **Estado:** Fallido
- **Timestamp:** 2025-12-16 12:35:21

**Respuesta:**
`json
{"message":"validation.required","errors":{"nuevo_inventario":["validation.required"]}}
`\n
---

### [OK] Test 9: Kardex de producto

- **Metodo:** GET
- **Endpoint:** /inventario/kardex/2
- **Status Code:** 200
- **Estado:** Exitoso
- **Timestamp:** 2025-12-16 12:35:21

**Respuesta:**
`json
{"success":true,"data":{"producto":{"id":2,"codigo":"PROD002","nombre":"Crema Hidratante","inventario_actual":34},"periodo":{"fecha_inicio":"2025-09-16T06:00:00.000000Z","fecha_fin":"2025-12-17T05:59:59.999999Z"},"resumen":{"saldo_inicial":0,"entradas":41,"salidas":5,"saldo_final":34},"movimientos":[{"id":5,"tipo":"entrada_manual","cantidad":10,"motivo":"Compra a proveedor","referencia_tipo":null,"referencia_id":null,"usuario":null,"notas":"Compra de reposicion de stock","created_at":"2025-12-16T18:35:21.000000Z"},{"id":6,"tipo":"salida_manual","cantidad":5,"motivo":"Productos danados","referencia_tipo":null,"referencia_id":null,"usuario":null,"notas":"Productos vencidos que se retiran del inventario","created_at":"2025-12-16T18:35:21.000000Z"},{"id":4,"tipo":"venta","cantidad":1,"motivo":"Venta #1","referencia_tipo":"venta","referencia_id":1,"usuario":"Administrador","notas":"Venta de prueba","created_at":"2025-12-15T18:15:54.000000Z"},{"id":2,"tipo":"entrada_manual","cantidad":30,"mo... (truncado)
`\n
---

### [OK] Test 10: Listar movimientos de inventario

- **Metodo:** GET
- **Endpoint:** /inventario/movimientos
- **Status Code:** 200
- **Estado:** Exitoso
- **Timestamp:** 2025-12-16 12:35:21

**Respuesta:**
`json
{"success":true,"data":[{"id":5,"producto":{"id":2,"codigo":"PROD002","nombre":"Crema Hidratante"},"tipo":"entrada_manual","cantidad":10,"motivo":"Compra a proveedor","referencia_tipo":null,"referencia_id":null,"usuario":null,"notas":"Compra de reposicion de stock","created_at":"2025-12-16T18:35:21.000000Z"},{"id":6,"producto":{"id":2,"codigo":"PROD002","nombre":"Crema Hidratante"},"tipo":"salida_manual","cantidad":5,"motivo":"Productos danados","referencia_tipo":null,"referencia_id":null,"usuario":null,"notas":"Productos vencidos que se retiran del inventario","created_at":"2025-12-16T18:35:21.000000Z"},{"id":3,"producto":{"id":1,"codigo":"PROD001","nombre":"Shampoo Reparador"},"tipo":"venta","cantidad":1,"motivo":"Venta #1","referencia_tipo":"venta","referencia_id":1,"usuario":{"id":1,"nombre":"Administrador"},"notas":"Venta de prueba","created_at":"2025-12-15T18:15:54.000000Z"},{"id":4,"producto":{"id":2,"codigo":"PROD002","nombre":"Crema Hidratante"},"tipo":"venta","cantidad":1,"mo... (truncado)
`\n
---

### [OK] Test 11: Listar metodos de pago

- **Metodo:** GET
- **Endpoint:** /metodos-pago
- **Status Code:** 200
- **Estado:** Exitoso
- **Timestamp:** 2025-12-16 12:35:22

**Respuesta:**
`json
{"success":true,"data":[{"id":1,"nombre":"Efectivo","codigo":"efectivo","es_efectivo":true,"activo":true,"orden":1},{"id":2,"nombre":"Tarjeta de Débito","codigo":"tarjeta_debito","es_efectivo":false,"activo":true,"orden":2},{"id":3,"nombre":"Tarjeta de Crédito","codigo":"tarjeta_credito","es_efectivo":false,"activo":true,"orden":3},{"id":4,"nombre":"Transferencia","codigo":"transferencia","es_efectivo":false,"activo":true,"orden":4},{"id":5,"nombre":"Mercado Pago","codigo":"mercado_pago","es_efectivo":false,"activo":true,"orden":5},{"id":6,"nombre":"Stripe","codigo":"stripe","es_efectivo":false,"activo":true,"orden":6}]}
`\n
---

### [OK] Test 12: Crear metodo de pago

- **Metodo:** POST
- **Endpoint:** /metodos-pago
- **Status Code:** 201
- **Estado:** Exitoso
- **Timestamp:** 2025-12-16 12:35:22

**Respuesta:**
`json
{"success":true,"message":"Método de pago creado correctamente","data":{"id":7,"nombre":"Tarjeta de Debito","codigo":"TARJETA_DEBITO_TEST_20251216123522","es_efectivo":false,"activo":true,"orden":2}}
`\n
---

### [OK] Test 13: Ver metodo de pago

- **Metodo:** GET
- **Endpoint:** /metodos-pago/1
- **Status Code:** 200
- **Estado:** Exitoso
- **Timestamp:** 2025-12-16 12:35:22

**Respuesta:**
`json
{"success":true,"data":{"id":1,"nombre":"Efectivo","codigo":"efectivo","es_efectivo":true,"activo":true,"orden":1}}
`\n
---

### [OK] Test 14: Actualizar metodo de pago

- **Metodo:** PUT
- **Endpoint:** /metodos-pago/1
- **Status Code:** 200
- **Estado:** Exitoso
- **Timestamp:** 2025-12-16 12:35:22

**Respuesta:**
`json
{"success":true,"message":"Método de pago actualizado correctamente","data":{"id":1,"nombre":"Efectivo Actualizado","codigo":"efectivo","es_efectivo":true,"activo":true,"orden":1}}
`\n
---

### [OK] Test 15: Eliminar metodo de pago

- **Metodo:** DELETE
- **Endpoint:** /metodos-pago/7
- **Status Code:** 200
- **Estado:** Exitoso
- **Timestamp:** 2025-12-16 12:35:22

**Respuesta:**
`json
{"success":true,"message":"Método de pago eliminado correctamente"}
`\n
---

### [OK] Test 16: Listar reglas de anticipo

- **Metodo:** GET
- **Endpoint:** /anticipos/reglas
- **Status Code:** 200
- **Estado:** Exitoso
- **Timestamp:** 2025-12-16 12:35:23

**Respuesta:**
`json
{"success":true,"data":[{"id":1,"nombre":"Anticipo ventas mayores a $500","descripcion":"Ventas de $500 o más requieren 30% de anticipo","tipo_regla":"monto","tipo_calculo":"porcentaje","valor_calculo":"30.00","prioridad":1,"activo":true}]}
`\n
---

### [FAIL] Test 17: Crear regla de anticipo

- **Metodo:** POST
- **Endpoint:** /anticipos/reglas
- **Status Code:** 422
- **Estado:** Fallido
- **Timestamp:** 2025-12-16 12:35:23

**Respuesta:**
`json
{"message":"validation.required_if","errors":{"monto_minimo":["validation.required_if"]}}
`\n
---

### [OK] Test 18: Ver regla de anticipo

- **Metodo:** GET
- **Endpoint:** /anticipos/reglas/1
- **Status Code:** 200
- **Estado:** Exitoso
- **Timestamp:** 2025-12-16 12:35:23

**Respuesta:**
`json
{"success":true,"data":{"id":1,"nombre":"Anticipo ventas mayores a $500","descripcion":"Ventas de $500 o más requieren 30% de anticipo","tipo_regla":"monto","tipo_calculo":"porcentaje","valor_calculo":"30.00","prioridad":1,"activo":true,"configuracion_monto":{"monto_minimo":"500.00","monto_maximo":null}}}
`\n
---

### [OK] Test 19: Actualizar regla de anticipo

- **Metodo:** PUT
- **Endpoint:** /anticipos/reglas/1
- **Status Code:** 200
- **Estado:** Exitoso
- **Timestamp:** 2025-12-16 12:35:23

**Respuesta:**
`json
{"success":true,"message":"Regla de anticipo actualizada correctamente","data":{"id":1,"nombre":"Anticipo ventas mayores a $500","descripcion":"Ventas de $500 o más requieren 30% de anticipo","tipo_regla":"monto","tipo_calculo":"porcentaje","valor_calculo":"35.00","prioridad":1,"activo":true,"configuracion_monto":{"monto_minimo":"500.00","monto_maximo":null}}}
`\n
---

### [OK] Test 20: Calcular anticipo (total 600)

- **Metodo:** POST
- **Endpoint:** /anticipos/calcular
- **Status Code:** 200
- **Estado:** Exitoso
- **Timestamp:** 2025-12-16 12:35:23

**Respuesta:**
`json
{"success":true,"data":{"requiere_anticipo":true,"monto_anticipo":210,"regla_aplicada":{"id":1,"nombre":"Anticipo ventas mayores a $500"},"reglas_evaluadas":[{"regla_id":1,"nombre":"Anticipo ventas mayores a $500","anticipo":210}]}}
`\n
---

### [OK] Test 20b: Calcular anticipo (total 400)

- **Metodo:** POST
- **Endpoint:** /anticipos/calcular
- **Status Code:** 200
- **Estado:** Exitoso
- **Timestamp:** 2025-12-16 12:35:23

**Respuesta:**
`json
{"success":true,"data":{"requiere_anticipo":false,"monto_anticipo":0,"regla_aplicada":null,"reglas_evaluadas":[]}}
`\n
---

### [OK] Test 21: Evaluar reglas aplicables

- **Metodo:** POST
- **Endpoint:** /anticipos/evaluar
- **Status Code:** 200
- **Estado:** Exitoso
- **Timestamp:** 2025-12-16 12:35:24

**Respuesta:**
`json
{"success":true,"data":[{"regla_id":1,"nombre":"Anticipo ventas mayores a $500","tipo_regla":"monto","aplica":true,"razon":"Monto dentro del rango configurado"}]}
`\n
---

### [OK] Test 22: Listar ventas

- **Metodo:** GET
- **Endpoint:** /ventas
- **Status Code:** 200
- **Estado:** Exitoso
- **Timestamp:** 2025-12-16 12:35:24

**Respuesta:**
`json
{"success":true,"data":[{"id":1,"cliente":{"id":1,"nombre":"María García"},"fecha_venta":"2025-12-15T18:15:54.000000Z","subtotal":"600.00","descuento_general":"0.00","impuesto_total":"0.00","total":"600.00","total_pagado":"180.00","saldo_pendiente":"420.00","estado":"parcial","requiere_anticipo":true,"monto_anticipo_requerido":"180.00","monto_anticipo_pagado":"180.00","notas":"Venta de prueba creada desde seeder","created_at":"2025-12-16T18:15:54.000000Z"}],"pagination":{"current_page":1,"last_page":1,"total":1}}
`\n
---

### [OK] Test 23: Crear venta

- **Metodo:** POST
- **Endpoint:** /ventas
- **Status Code:** 201
- **Estado:** Exitoso
- **Timestamp:** 2025-12-16 12:35:24

**Respuesta:**
`json
{"success":true,"message":"Venta creada correctamente","data":{"id":2,"cliente":{"id":1,"nombre":"María García"},"fecha_venta":"2025-12-16T18:35:24.000000Z","subtotal":"500.00","descuento_general":"0.00","impuesto_total":"0.00","total":"500.00","total_pagado":"0.00","saldo_pendiente":"500.00","estado":"pendiente_pago","requiere_anticipo":null,"monto_anticipo_requerido":null,"monto_anticipo_pagado":null,"notas":"Venta de prueba desde API","created_at":"2025-12-16T18:35:24.000000Z","detalles":[{"id":3,"tipo":"producto","producto":{"id":2,"codigo":"PROD002","nombre":"Crema Hidratante"},"servicio":null,"cantidad":2,"precio_unitario":"250.00","descuento":"0.00","impuesto":"0.00","subtotal_linea":"500.00"}],"pagos":[]}}
`\n
---

### [OK] Test 24: Ver venta especifica

- **Metodo:** GET
- **Endpoint:** /ventas/1
- **Status Code:** 200
- **Estado:** Exitoso
- **Timestamp:** 2025-12-16 12:35:24

**Respuesta:**
`json
{"success":true,"data":{"id":1,"cliente":{"id":1,"nombre":"María García"},"fecha_venta":"2025-12-15T18:15:54.000000Z","subtotal":"600.00","descuento_general":"0.00","impuesto_total":"0.00","total":"600.00","total_pagado":"180.00","saldo_pendiente":"420.00","estado":"parcial","requiere_anticipo":true,"monto_anticipo_requerido":"180.00","monto_anticipo_pagado":"180.00","notas":"Venta de prueba creada desde seeder","created_at":"2025-12-16T18:15:54.000000Z","detalles":[{"id":1,"tipo":"producto","producto":{"id":1,"codigo":"PROD001","nombre":"Shampoo Reparador"},"servicio":null,"cantidad":1,"precio_unitario":"250.00","descuento":"0.00","impuesto":"0.00","subtotal_linea":"250.00"},{"id":2,"tipo":"producto","producto":{"id":2,"codigo":"PROD002","nombre":"Crema Hidratante"},"servicio":null,"cantidad":1,"precio_unitario":"350.00","descuento":"0.00","impuesto":"0.00","subtotal_linea":"350.00"}],"pagos":[{"id":1,"metodo_pago":{"id":1,"nombre":"Efectivo Actualizado"},"monto":"180.00","estado_pago... (truncado)
`\n
---

### [OK] Test 25: Calcular totales de venta

- **Metodo:** POST
- **Endpoint:** /ventas/calcular-totales
- **Status Code:** 200
- **Estado:** Exitoso
- **Timestamp:** 2025-12-16 12:35:24

**Respuesta:**
`json
{"success":true,"data":{"subtotal":840,"descuento_general":20,"impuesto_total":0,"total":820}}
`\n
---

### [FAIL] Test 26: Buscar productos para venta

- **Metodo:** GET
- **Endpoint:** /ventas/productos/buscar?q=Shampoo
- **Status Code:** 422
- **Estado:** Fallido
- **Timestamp:** 2025-12-16 12:35:25

**Respuesta:**
`json
{"message":"validation.required","errors":{"termino":["validation.required"]}}
`\n
---

### [OK] Test 27: Actualizar venta

- **Metodo:** PUT
- **Endpoint:** /ventas/1
- **Status Code:** 200
- **Estado:** Exitoso
- **Timestamp:** 2025-12-16 12:35:25

**Respuesta:**
`json
{"success":true,"message":"Venta actualizada correctamente","data":{"id":1,"cliente":{"id":1,"nombre":"María García"},"fecha_venta":"2025-12-15T18:15:54.000000Z","subtotal":"600.00","descuento_general":"0.00","impuesto_total":"0.00","total":"600.00","total_pagado":"180.00","saldo_pendiente":"420.00","estado":"parcial","requiere_anticipo":true,"monto_anticipo_requerido":"180.00","monto_anticipo_pagado":"180.00","notas":"Notas actualizadas","created_at":"2025-12-16T18:15:54.000000Z","detalles":[{"id":1,"tipo":"producto","producto":{"id":1,"codigo":"PROD001","nombre":"Shampoo Reparador"},"servicio":null,"cantidad":1,"precio_unitario":"250.00","descuento":"0.00","impuesto":"0.00","subtotal_linea":"250.00"},{"id":2,"tipo":"producto","producto":{"id":2,"codigo":"PROD002","nombre":"Crema Hidratante"},"servicio":null,"cantidad":1,"precio_unitario":"350.00","descuento":"0.00","impuesto":"0.00","subtotal_linea":"350.00"}],"pagos":[{"id":1,"metodo_pago":{"id":1,"nombre":"Efectivo Actualizado"},"m... (truncado)
`\n
---

### [FAIL] Test 28: Registrar pago

- **Metodo:** POST
- **Endpoint:** /pagos/registrar
- **Status Code:** 500
- **Estado:** Fallido
- **Timestamp:** 2025-12-16 12:35:25

**Respuesta:**
`json
{"success":false,"message":"Error al registrar pago: SQLSTATE[23000]: Integrity constraint violation: 1048 Column \u0027user_id\u0027 cannot be null (Connection: mysql, SQL: insert into `venta_pagos` (`venta_id`, `metodo_pago_id`, `monto`, `monto_recibido`, `cambio`, `estado_pago`, `notas`, `user_id`, `updated_at`, `created_at`) values (1, 1, 180, 200, 20, aprobado, Pago de anticipo, ?, 2025-12-16 12:35:25, 2025-12-16 12:35:25))"}
`\n
---

### [OK] Test 29: Listar pagos de una venta

- **Metodo:** GET
- **Endpoint:** /pagos/venta/1
- **Status Code:** 200
- **Estado:** Exitoso
- **Timestamp:** 2025-12-16 12:35:25

**Respuesta:**
`json
{"success":true,"data":{"venta":{"id":1,"total":"600.00","total_pagado":"180.00","saldo_pendiente":"420.00"},"pagos":[{"id":1,"metodo_pago":{"id":1,"nombre":"Efectivo Actualizado"},"monto":"180.00","monto_recibido":"180.00","cambio":"0.00","estado_pago":"aprobado","proveedor_pago":null,"transaccion_id":null,"notas":"Pago de anticipo de prueba","created_at":"2025-12-16T18:15:54.000000Z"}]}}
`\n
---

