# Configuración de WhatsApp con WasenderAPI

## Variables de Entorno

Agrega las siguientes variables a tu archivo `.env`:

```env
# WasenderAPI Configuration
WHATSAPP_API_URL=https://wasenderapi.com/api
WHATSAPP_API_TOKEN=ae02434e1d46b604bbb3190cc9aed71f1c960b6ca26f4fd898800a4cb929e45b
WHATSAPP_SESSION_ID=
```

## Notas

- `WHATSAPP_API_URL`: URL base de la API de WasenderAPI (ya configurado por defecto)
- `WHATSAPP_API_TOKEN`: Tu API key de WasenderAPI (Bearer Token)
- `WHATSAPP_SESSION_ID`: ID de la sesión de WhatsApp (opcional, si tienes múltiples sesiones)

## Próximos Pasos

1. Asegúrate de tener una sesión de WhatsApp conectada en WasenderAPI
2. Ejecuta la migración para agregar el campo `token_qr`:
   ```bash
   php artisan migrate
   ```

3. Crea el link simbólico de storage para los QR codes:
   ```bash
   php artisan storage:link
   ```

4. Prueba enviando un mensaje de prueba desde tu aplicación

## Funcionalidades Implementadas

✅ Envío de mensajes de texto
✅ Envío de imágenes (QR codes)
✅ Confirmación de citas con QR
✅ Cancelación de citas
✅ Modificación de citas
✅ Códigos OTP
✅ Escaneo de QR para marcar citas como completadas

