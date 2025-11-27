# Integraciones con APIs Externas

## 📋 Descripción General

El sistema necesita comunicarse con servicios externos para:
1. **WhatsApp**: Notificaciones y OTP
2. **Email**: Confirmaciones y recordatorios
3. **Push Notifications**: Alertas en tiempo real

---

## 📱 WhatsApp Business API

### Opciones de Proveedores

| Proveedor | Costo | Complejidad | Recomendación |
|-----------|-------|-------------|---------------|
| **Meta Cloud API** | Bajo | Media | ✅ Recomendado |
| **Twilio** | Alto | Baja | Para escala grande |
| **360dialog** | Medio | Media | Alternativa |
| **Evolution API** | Gratis* | Alta | Self-hosted |

*Evolution API es open source pero requiere WhatsApp Web conectado.

### Recomendación: Meta Cloud API (WhatsApp Business Platform)

**Ventajas**:
- Oficial de Meta
- Costo competitivo
- API bien documentada
- Sin intermediarios

**Requisitos**:
- Cuenta de Meta Business
- Número de teléfono verificado
- Aprobación de plantillas de mensajes

---

### Configuración Meta Cloud API

#### 1. Crear App en Meta Developers

```
1. Ir a developers.facebook.com
2. Crear App tipo "Business"
3. Agregar producto "WhatsApp"
4. Configurar número de teléfono
5. Obtener Access Token permanente
```

#### 2. Variables de Entorno

```env
WHATSAPP_API_URL=https://graph.facebook.com/v18.0
WHATSAPP_PHONE_ID=123456789
WHATSAPP_ACCESS_TOKEN=your_permanent_token
WHATSAPP_VERIFY_TOKEN=your_webhook_verify_token
```

#### 3. Plantillas de Mensajes

WhatsApp requiere **plantillas aprobadas** para mensajes proactivos.

##### Plantilla: Confirmación de Cita
```
Nombre: cita_confirmacion
Idioma: es_MX
Categoría: UTILITY

Contenido:
¡Hola {{1}}! 👋

Tu cita ha sido agendada:
📅 Fecha: {{2}}
⏰ Hora: {{3}}
💇 Servicio: {{4}}
👤 Con: {{5}}

📍 Dirección: [Dirección del negocio]

Para cancelar o modificar, responde a este mensaje.
```

##### Plantilla: Recordatorio 24h
```
Nombre: cita_recordatorio
Idioma: es_MX
Categoría: UTILITY

Contenido:
¡Hola {{1}}! 👋

Te recordamos tu cita para mañana:
📅 Fecha: {{2}}
⏰ Hora: {{3}}
💇 Servicio: {{4}}

¡Te esperamos! 😊

Responde CANCELAR si no podrás asistir.
```

##### Plantilla: Recordatorio 2h
```
Nombre: cita_recordatorio_dia
Idioma: es_MX
Categoría: UTILITY

Contenido:
¡Hola {{1}}! 👋

Tu cita es en 2 horas:
⏰ {{2}}
💇 {{3}}

¡Te esperamos pronto! 🎉
```

##### Plantilla: Cancelación
```
Nombre: cita_cancelacion
Idioma: es_MX
Categoría: UTILITY

Contenido:
Hola {{1}},

Tu cita del {{2}} a las {{3}} ha sido cancelada.

Si deseas reagendar, puedes hacerlo desde nuestra app.

Disculpa los inconvenientes.
```

##### Plantilla: OTP
```
Nombre: codigo_verificacion
Idioma: es_MX
Categoría: AUTHENTICATION

Contenido:
Tu código de verificación es: {{1}}

Este código expira en 5 minutos.
No compartas este código con nadie.
```

---

### Envío de Mensajes WhatsApp

#### Estructura de Request

```json
POST https://graph.facebook.com/v18.0/{PHONE_ID}/messages
Headers:
  Authorization: Bearer {ACCESS_TOKEN}
  Content-Type: application/json

Body (Mensaje con plantilla):
{
  "messaging_product": "whatsapp",
  "to": "521234567890",
  "type": "template",
  "template": {
    "name": "cita_confirmacion",
    "language": {
      "code": "es_MX"
    },
    "components": [
      {
        "type": "body",
        "parameters": [
          { "type": "text", "text": "Juan" },
          { "type": "text", "text": "27 de noviembre" },
          { "type": "text", "text": "10:00 AM" },
          { "type": "text", "text": "Corte + Tinte" },
          { "type": "text", "text": "María" }
        ]
      }
    ]
  }
}

Response:
{
  "messaging_product": "whatsapp",
  "contacts": [{ "input": "521234567890", "wa_id": "521234567890" }],
  "messages": [{ "id": "wamid.xxx..." }]
}
```

---

### Recepción de Respuestas (Webhook)

#### Configurar Webhook

```
URL: https://tu-dominio.com/api/webhooks/whatsapp
Verificar Token: {WHATSAPP_VERIFY_TOKEN}
Suscribirse a: messages
```

#### Endpoint de Verificación

```
GET /api/webhooks/whatsapp
Query params:
  hub.mode=subscribe
  hub.verify_token={tu_token}
  hub.challenge={challenge_string}

Response: hub.challenge (como texto plano)
```

#### Endpoint de Mensajes

```
POST /api/webhooks/whatsapp
Body:
{
  "object": "whatsapp_business_account",
  "entry": [{
    "changes": [{
      "value": {
        "messages": [{
          "from": "521234567890",
          "type": "text",
          "text": { "body": "CANCELAR" }
        }]
      }
    }]
  }]
}
```

#### Procesar Respuestas

```
Respuestas a manejar:
- "CANCELAR" → Cancelar cita pendiente más próxima
- "CONFIRMAR" → Confirmar cita pendiente
- Cualquier otro texto → Ignorar o responder con menú
```

---

### Servicio WhatsApp (Laravel)

```
Clase: WhatsAppService

Métodos:
  - enviarConfirmacionCita(cita)
  - enviarRecordatorio(cita)
  - enviarRecordatorioDia(cita)
  - enviarCancelacion(cita)
  - enviarOTP(telefono, codigo)
  - enviarMensajeLibre(telefono, mensaje) // Solo para respuestas
  
Dependencias:
  - HTTP Client (Guzzle/Laravel HTTP)
  - Config (credenciales)
  - Logger
```

---

## 📧 Email (SMTP)

### Configuración Laravel Mail

#### Variables de Entorno

```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=notificaciones@tunegocio.com
MAIL_PASSWORD=app_password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=notificaciones@tunegocio.com
MAIL_FROM_NAME="Estética XYZ"
```

### Opciones de Proveedores

| Proveedor | Costo | Límites | Recomendación |
|-----------|-------|---------|---------------|
| **Gmail SMTP** | Gratis | 500/día | Desarrollo |
| **Mailgun** | $0.80/1000 | Alto | ✅ Producción |
| **SendGrid** | Freemium | 100/día gratis | Alternativa |
| **Amazon SES** | $0.10/1000 | Alto | Económico |
| **Resend** | Freemium | 100/día gratis | Moderno |

### Recomendación: Mailgun o Amazon SES

Para producción, usar un servicio dedicado por:
- Mejor deliverability
- SPF/DKIM/DMARC configurado
- Métricas y logs
- Sin límites restrictivos

---

### Plantillas de Email (Blade)

#### Estructura de Plantillas

```
resources/views/emails/
├── citas/
│   ├── confirmacion.blade.php
│   ├── recordatorio.blade.php
│   ├── recordatorio-dia.blade.php
│   ├── cancelacion.blade.php
│   └── modificacion.blade.php
├── auth/
│   └── otp.blade.php
├── promociones/
│   └── nueva.blade.php
└── layouts/
    └── base.blade.php
```

#### Layout Base

```html
<!-- resources/views/emails/layouts/base.blade.php -->
<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>@yield('title')</title>
  <style>
    /* Estilos inline para compatibilidad */
    body { font-family: Arial, sans-serif; background: #f5f5f5; }
    .container { max-width: 600px; margin: 0 auto; background: #fff; }
    .header { background: #primary-color; padding: 20px; text-align: center; }
    .content { padding: 30px; }
    .footer { background: #f5f5f5; padding: 20px; text-align: center; font-size: 12px; }
    .button { display: inline-block; padding: 12px 30px; background: #primary; color: #fff; text-decoration: none; border-radius: 5px; }
  </style>
</head>
<body>
  <div class="container">
    <div class="header">
      <img src="{{ asset('logo.png') }}" alt="Logo" height="50">
    </div>
    <div class="content">
      @yield('content')
    </div>
    <div class="footer">
      <p>© {{ date('Y') }} Estética XYZ</p>
      <p>Dirección del negocio</p>
      <p>
        <a href="#">Cancelar suscripción</a>
      </p>
    </div>
  </div>
</body>
</html>
```

#### Plantilla Confirmación de Cita

```html
<!-- resources/views/emails/citas/confirmacion.blade.php -->
@extends('emails.layouts.base')

@section('title', 'Confirmación de Cita')

@section('content')
<h1>¡Cita Confirmada! ✅</h1>

<p>Hola {{ $cliente->nombre }},</p>

<p>Tu cita ha sido agendada exitosamente:</p>

<div style="background: #f9f9f9; padding: 20px; border-radius: 10px; margin: 20px 0;">
  <p><strong>📅 Fecha:</strong> {{ $cita->fecha_hora->format('d/m/Y') }}</p>
  <p><strong>⏰ Hora:</strong> {{ $cita->fecha_hora->format('h:i A') }}</p>
  <p><strong>💇 Servicio:</strong> {{ $cita->servicios_nombres }}</p>
  <p><strong>👤 Atendido por:</strong> {{ $cita->empleado->user->nombre }}</p>
  <p><strong>💰 Total:</strong> ${{ number_format($cita->precio_final, 2) }}</p>
</div>

<p style="text-align: center;">
  <a href="{{ url('/mis-citas/' . $cita->id) }}" class="button">
    Ver mi cita
  </a>
</p>

<p>📍 <strong>Dirección:</strong> [Dirección del negocio]</p>

<p>Si necesitas cancelar o modificar tu cita, puedes hacerlo desde la app o respondiendo a este correo.</p>

<p>¡Te esperamos!</p>
@endsection
```

---

### Mailables (Laravel)

```
Clases Mailable:

app/Mail/
├── Citas/
│   ├── ConfirmacionCitaMail.php
│   ├── RecordatorioCitaMail.php
│   ├── RecordatorioDiaCitaMail.php
│   ├── CancelacionCitaMail.php
│   └── ModificacionCitaMail.php
├── Auth/
│   └── OTPMail.php
└── Promociones/
    └── NuevaPromocionMail.php
```

---

## 🔔 Push Notifications

### Opciones de Servicios

| Servicio | Plataformas | Costo | Recomendación |
|----------|-------------|-------|---------------|
| **Firebase Cloud Messaging** | iOS, Android, Web | Gratis | ✅ Recomendado |
| **OneSignal** | Todas | Freemium | Alternativa fácil |
| **Pusher Beams** | Todas | Freemium | Con Pusher |

### Recomendación: Firebase Cloud Messaging (FCM)

**Ventajas**:
- Gratis
- Integración nativa con Capacitor
- Confiable y escalable
- Buena documentación

---

### Configuración FCM

#### 1. Crear Proyecto en Firebase

```
1. Ir a console.firebase.google.com
2. Crear proyecto nuevo
3. Agregar app Android (com.tunegocio.app)
4. Agregar app iOS (com.tunegocio.app)
5. Descargar google-services.json (Android)
6. Descargar GoogleService-Info.plist (iOS)
7. Obtener Server Key (para backend)
```

#### 2. Variables de Entorno (Backend)

```env
FCM_SERVER_KEY=your_server_key
FCM_SENDER_ID=123456789
```

#### 3. Configuración Capacitor (Frontend)

```json
// capacitor.config.json
{
  "plugins": {
    "PushNotifications": {
      "presentationOptions": ["badge", "sound", "alert"]
    }
  }
}
```

---

### Almacenamiento de Tokens Push

#### Tabla de Dispositivos

```sql
CREATE TABLE dispositivos (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    cliente_id BIGINT UNSIGNED NULL,
    user_id BIGINT UNSIGNED NULL COMMENT 'para empleados',
    token_push VARCHAR(255) NOT NULL,
    plataforma ENUM('android', 'ios', 'web') NOT NULL,
    modelo VARCHAR(100),
    activo TINYINT(1) DEFAULT 1,
    last_used_at TIMESTAMP NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    FOREIGN KEY (cliente_id) REFERENCES clientes(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_token (token_push),
    INDEX idx_dispositivos_cliente (cliente_id),
    INDEX idx_dispositivos_user (user_id)
);
```

---

### Envío de Push Notifications

#### Estructura de Request a FCM

```json
POST https://fcm.googleapis.com/fcm/send
Headers:
  Authorization: key={SERVER_KEY}
  Content-Type: application/json

Body:
{
  "to": "{device_token}",
  "notification": {
    "title": "Recordatorio de Cita",
    "body": "Tu cita es en 2 horas",
    "icon": "notification_icon",
    "click_action": "OPEN_CITA"
  },
  "data": {
    "cita_id": "123",
    "tipo": "recordatorio",
    "fecha": "2025-11-27T10:00:00"
  },
  "android": {
    "priority": "high",
    "notification": {
      "sound": "default",
      "channel_id": "citas"
    }
  },
  "apns": {
    "payload": {
      "aps": {
        "sound": "default",
        "badge": 1
      }
    }
  }
}
```

#### Envío a Múltiples Dispositivos

```json
{
  "registration_ids": [
    "token1...",
    "token2...",
    "token3..."
  ],
  "notification": { ... },
  "data": { ... }
}
```

---

### Manejo de Tokens Inválidos

```
Al enviar push:
1. Si FCM retorna error "InvalidRegistration" o "NotRegistered"
2. Marcar token como inactivo en BD
3. No reintentar envío a ese token
4. El cliente registrará nuevo token al abrir la app
```

---

### Servicio Push Notifications (Laravel)

```
Clase: PushNotificationService

Métodos:
  - enviarACliente(clienteId, titulo, mensaje, data)
  - enviarAEmpleado(userId, titulo, mensaje, data)
  - enviarADispositivo(token, titulo, mensaje, data)
  - enviarMasivo(tokens[], titulo, mensaje, data)
  - registrarDispositivo(token, plataforma, clienteId/userId)
  - desactivarDispositivo(token)
  
Dependencias:
  - HTTP Client
  - Config (FCM credentials)
  - DispositivoRepository
  - Logger
```

---

### Frontend: Capacitor Push Notifications

#### Registro de Token

```typescript
import { PushNotifications } from '@capacitor/push-notifications';

// Solicitar permisos
const requestPermissions = async () => {
  const result = await PushNotifications.requestPermissions();
  if (result.receive === 'granted') {
    await PushNotifications.register();
  }
};

// Obtener token
PushNotifications.addListener('registration', async (token) => {
  // Enviar token al backend
  await api.post('/dispositivos/registrar', {
    token: token.value,
    plataforma: Capacitor.getPlatform()
  });
});

// Manejar notificación recibida (app en foreground)
PushNotifications.addListener('pushNotificationReceived', (notification) => {
  // Mostrar notificación local o actualizar UI
  console.log('Push received:', notification);
});

// Manejar tap en notificación
PushNotifications.addListener('pushNotificationActionPerformed', (action) => {
  // Navegar a la pantalla correspondiente
  const data = action.notification.data;
  if (data.cita_id) {
    router.push(`/citas/${data.cita_id}`);
  }
});
```

---

## 🔄 Servicio Unificado de Notificaciones

### NotificacionService

```
Clase: NotificacionService

Responsabilidad:
  - Decidir qué canales usar según preferencias del cliente
  - Crear registro en tabla 'notificaciones'
  - Encolar jobs para cada canal
  
Métodos:
  - notificarNuevaCita(cita)
  - notificarRecordatorio(cita)
  - notificarRecordatorioDia(cita)
  - notificarCancelacion(cita)
  - notificarModificacion(cita)
  - notificarPromocion(promocion, clientes[])
  
Flujo:
  1. Verificar preferencias del cliente
  2. Crear registro en 'notificaciones' para cada canal activo
  3. Encolar job correspondiente
```

### Flujo de Notificación

```
Evento (ej: nueva cita)
    │
    ▼
NotificacionService
    │
    ├─► Verificar cliente.notificaciones_email
    │       │
    │       ▼ (si activo)
    │   Crear registro notificaciones (tipo: email)
    │   Encolar EnviarEmailJob
    │
    ├─► Verificar cliente.notificaciones_whatsapp
    │       │
    │       ▼ (si activo)
    │   Crear registro notificaciones (tipo: whatsapp)
    │   Encolar EnviarWhatsAppJob
    │
    └─► Verificar cliente.notificaciones_push
            │
            ▼ (si activo)
        Crear registro notificaciones (tipo: push)
        Encolar EnviarPushJob
```

---

## 📊 Tabla de Costos Estimados

### Por Notificación de Cita

| Canal | Costo Unitario | Mensual (500 citas) |
|-------|----------------|---------------------|
| Email (SES) | $0.0001 | $0.05 |
| WhatsApp | $0.05-0.08 | $25-40 |
| Push | $0 | $0 |
| **Total** | | **$25-40/mes** |

### Desglose WhatsApp
- Conversación de utilidad: ~$0.05 USD
- Incluye mensajes en 24 horas
- OTP: ~$0.03-0.05 USD

---

## 🔒 Seguridad de Integraciones

### Credenciales
- Almacenar en variables de entorno
- No commitear credenciales a Git
- Rotar tokens periódicamente
- Usar secrets manager en producción

### Webhooks
- Validar firma de requests (WhatsApp)
- Verificar IP de origen
- Rate limiting en endpoints de webhook
- Logs de requests recibidos

### Tokens Push
- Validar antes de guardar
- Limpiar tokens inactivos
- No exponer tokens en logs

---

## ✅ Checklist de Implementación

### WhatsApp
- [ ] Crear cuenta Meta Business
- [ ] Configurar WhatsApp Business API
- [ ] Crear plantillas de mensajes
- [ ] Esperar aprobación de plantillas
- [ ] Implementar WhatsAppService
- [ ] Configurar webhook
- [ ] Implementar manejo de respuestas
- [ ] Tests

### Email
- [ ] Configurar proveedor SMTP
- [ ] Crear plantillas Blade
- [ ] Implementar Mailables
- [ ] Configurar SPF/DKIM (producción)
- [ ] Tests de envío

### Push Notifications
- [ ] Crear proyecto Firebase
- [ ] Configurar apps (Android/iOS)
- [ ] Implementar PushNotificationService
- [ ] Configurar Capacitor
- [ ] Implementar registro de tokens en frontend
- [ ] Implementar manejo de notificaciones en frontend
- [ ] Tests

### Servicio Unificado
- [ ] Implementar NotificacionService
- [ ] Crear Jobs para cada canal
- [ ] Integrar con sistema de colas
- [ ] Tests de integración

---

## 🎯 Resumen

Con estos tres documentos críticos cubiertos:

1. ✅ **Sistema de Disponibilidad** - Algoritmo completo
2. ✅ **Autenticación** - Flujos para clientes y empleados
3. ✅ **Integraciones Externas** - WhatsApp, Email, Push

El contexto está completo para comenzar la implementación.

¿Hay algún punto que quieras profundizar más antes de empezar a codificar?

