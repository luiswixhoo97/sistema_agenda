# Configuración de Push Notifications con Firebase

## Paso 1: Crear proyecto en Firebase

1. Ve a [Firebase Console](https://console.firebase.google.com/)
2. Crea un nuevo proyecto o usa uno existente
3. Nombre del proyecto: `BeautySpa` (o el nombre que prefieras)

## Paso 2: Agregar app Android

1. En la consola de Firebase, haz clic en **"Agregar app"**
2. Selecciona **Android**
3. Configura:
   - **Nombre del paquete Android**: `com.beautyspa.app`
   - **Apodo de la app**: BeautySpa
   - **Certificado SHA-1** (opcional para push básico)

4. **Descarga el archivo `google-services.json`**

## Paso 3: Colocar google-services.json

Copia el archivo `google-services.json` descargado a:

```
frontend/android/app/google-services.json
```

## Paso 4: Reconstruir la APK

```bash
cd frontend
npm run build:android
npm run apk:debug
```

## Paso 5: Configurar el backend (Laravel)

En tu backend, necesitas configurar las credenciales de Firebase Cloud Messaging.

### 5.1 Obtener clave del servidor

1. En Firebase Console, ve a **Configuración del proyecto** (ícono de engrane)
2. Pestaña **Cloud Messaging**
3. En "API de Cloud Messaging (heredada)" o "Credenciales de API", obtén la **Clave del servidor**

### 5.2 Agregar al archivo .env del backend

```env
FIREBASE_SERVER_KEY=tu_clave_del_servidor_aquí
```

### 5.3 Actualizar config/services.php

```php
'firebase' => [
    'server_key' => env('FIREBASE_SERVER_KEY'),
],
```

## Verificación

Para verificar que todo funciona:

1. Instala la APK en un dispositivo
2. Abre la app y acepta permisos de notificaciones
3. Revisa los logs de la app (Android Studio > Logcat)
4. Deberías ver: `"Push registration success, token: ..."`

## Troubleshooting

### Error: "No se pudo registrar para notificaciones"
- Verifica que `google-services.json` esté en la ubicación correcta
- Asegúrate de que el `applicationId` coincida

### Las notificaciones no llegan
- Verifica que la clave del servidor esté correcta en el backend
- Revisa que el token FCM se esté guardando en la base de datos

### Error en Firebase Console
- Asegúrate de que Cloud Messaging esté habilitado en tu proyecto

