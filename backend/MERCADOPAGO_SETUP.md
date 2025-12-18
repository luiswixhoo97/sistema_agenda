# Configuración de Mercado Pago

Esta guía explica cómo configurar e integrar Mercado Pago para el pago de anticipos en el sistema de agendamiento.

## 📋 Requisitos Previos

1. Cuenta de Mercado Pago (crear en https://www.mercadopago.com.mx/)
2. Access Token de Mercado Pago (obtener desde el panel de desarrolladores)
3. SDK de Mercado Pago instalado (ya incluido: `mercadopago/dx-php:3.8.0`)

## 🔧 Configuración

### 1. Variables de Entorno

Agregar en el archivo `.env` del backend:

```env
MERCADOPAGO_ACCESS_TOKEN=TU_ACCESS_TOKEN_AQUI
```

**Nota:** 
- Para pruebas: usar el Access Token de **test** (sandbox)
- Para producción: usar el Access Token de **producción**

### 2. Método de Pago en Base de Datos

Asegúrate de que existe un método de pago con código `mercado_pago` o `mercadopago` en la tabla `metodos_pago`:

```sql
INSERT INTO metodos_pago (nombre, codigo, es_efectivo, activo, orden) 
VALUES ('Mercado Pago', 'mercado_pago', false, true, 3);
```

### 3. Ejecutar Migración

Ejecutar la migración para crear la tabla de pagos de Mercado Pago:

```bash
cd backend
php artisan migrate
```

## 🔄 Flujo de Pago

### Flujo Completo:

1. **Usuario selecciona servicios** que requieren anticipo
2. **Sistema calcula el anticipo** requerido
3. **Usuario selecciona "Mercado Pago"** como método de pago
4. **Frontend crea preferencia** llamando a `/api/publico/mercadopago/crear-preferencia`
5. **Usuario es redirigido** al checkout de Mercado Pago
6. **Usuario completa el pago** en Mercado Pago
7. **Mercado Pago redirige** de vuelta a la aplicación
8. **Sistema verifica el pago** y actualiza el estado
9. **Usuario completa el OTP** y confirma la cita
10. **Cita se agenda** con el anticipo pagado

## 📡 Webhooks

### Configurar Webhook en Mercado Pago

1. Ir al panel de desarrolladores de Mercado Pago
2. Configurar la URL del webhook: `https://tu-dominio.com/api/publico/mercadopago/webhook`
3. Seleccionar eventos: `payment`

El webhook recibirá notificaciones cuando cambie el estado de un pago.

## 🧪 Pruebas

### Modo Sandbox (Pruebas)

1. Usar Access Token de test
2. Usar tarjetas de prueba de Mercado Pago:
   - Aprobada: `5031 7557 3453 0604` (CVV: 123)
   - Rechazada: `5031 4332 1540 6351` (CVV: 123)

### Verificar Pago Manualmente

```bash
# Verificar estado de un pago
GET /api/publico/mercadopago/verificar/{payment_id}
```

## 📝 Endpoints Disponibles

### Crear Preferencia de Pago
```
POST /api/publico/mercadopago/crear-preferencia
```

**Body:**
```json
{
  "monto": 500.00,
  "descripcion": "Anticipo para cita",
  "payer_email": "cliente@example.com",
  "payer_name": "Juan",
  "payer_surname": "Pérez",
  "external_reference": "anticipo_1234567890",
  "back_url_success": "https://tu-dominio.com/pago/exito",
  "back_url_failure": "https://tu-dominio.com/pago/fallo",
  "back_url_pending": "https://tu-dominio.com/pago/pendiente"
}
```

### Webhook (Mercado Pago)
```
POST /api/publico/mercadopago/webhook
```

### Verificar Pago
```
GET /api/publico/mercadopago/verificar/{payment_id}
```

## 🔍 Troubleshooting

### Error: "Mercado Pago access token no configurado"
- Verificar que `MERCADOPAGO_ACCESS_TOKEN` esté en el `.env`
- Ejecutar `php artisan config:clear`

### Error: "Método de pago Mercado Pago no encontrado"
- Verificar que existe un registro en `metodos_pago` con código `mercado_pago` o `mercadopago`

### El pago no se registra en venta_pagos
- Verificar que el webhook esté configurado correctamente
- Revisar los logs en `storage/logs/laravel.log`
- Verificar que el `external_reference` coincida con el formato esperado

## 📚 Documentación Adicional

- [SDK PHP de Mercado Pago](https://github.com/mercadopago/sdk-php)
- [Documentación de Checkout Pro](https://www.mercadopago.com.mx/developers/es/docs/checkout-pro/landing)
- [Webhooks de Mercado Pago](https://www.mercadopago.com.mx/developers/es/docs/your-integrations/notifications/webhooks)
