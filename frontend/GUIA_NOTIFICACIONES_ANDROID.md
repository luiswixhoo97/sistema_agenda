# Guía Completa: Notificaciones Push Nativas de Android

Esta guía explica cómo implementar y configurar notificaciones push nativas del sistema operativo Android en tu aplicación BeautySpa usando Capacitor y Firebase Cloud Messaging (FCM).

## 📋 Tabla de Contenidos

1. [Requisitos Previos](#requisitos-previos)
2. [Configuración de Firebase](#configuración-de-firebase)
3. [Configuración del Proyecto Android](#configuración-del-proyecto-android)
4. [Implementación en el Frontend](#implementación-en-el-frontend)
5. [Configuración del Backend](#configuración-del-backend)
6. [Pruebas y Verificación](#pruebas-y-verificación)
7. [Solución de Problemas](#solución-de-problemas)

---

## 🔧 Requisitos Previos

- Proyecto Capacitor configurado
- Node.js y npm instalados
- Android Studio instalado
- Cuenta de Google (para Firebase)
- Dispositivo Android físico o emulador con Android 13+ (API 33+)

---

## 🔥 Configuración de Firebase

### Paso 1: Crear Proyecto en Firebase

1. Ve a [Firebase Console](https://console.firebase.google.com/)
2. Haz clic en **"Agregar proyecto"** o selecciona uno existente
3. **IMPORTANTE**: Ingresa el nombre del proyecto **SIN PUNTOS**:
   - ✅ Correcto: `BeautySpa`, `beautyspa-app`, `beautyspa-app-movil`
   - ❌ Incorrecto: `com.beautyspa.app` (Firebase no permite puntos en el nombre del proyecto)
4. Sigue los pasos del asistente (puedes desactivar Google Analytics si no lo necesitas)

**Nota**: El nombre del proyecto Firebase es diferente del package name de Android. El proyecto puede llamarse "BeautySpa" y la app Android puede tener el package `com.beautyspa.app`.

### Paso 2: Agregar App Android

1. Una vez creado el proyecto, en la consola de Firebase, haz clic en el ícono de **Android** o en **"Agregar app"**
2. Completa el formulario:
   - **Nombre del paquete Android**: `com.beautyspa.app` ← **AQUÍ SÍ PUEDES USAR PUNTOS**
     - Este debe coincidir exactamente con `applicationId` en `build.gradle`
     - Formato: `com.empresa.app` (con puntos)
   - **Apodo de la app**: `BeautySpa` (solo para identificación en Firebase)
   - **Certificado SHA-1** (opcional para desarrollo, necesario para producción)
3. Haz clic en **"Registrar app"**

### Paso 3: Descargar google-services.json

1. Descarga el archivo `google-services.json`
2. **IMPORTANTE**: Colócalo en la siguiente ubicación:
   ```
   frontend/android/app/google-services.json
   ```

### Paso 4: Obtener Clave del Servidor (Server Key)

1. En Firebase Console, ve a **Configuración del proyecto** (ícono de engranaje)
2. Ve a la pestaña **"Cloud Messaging"**
3. En la sección **"API de Cloud Messaging (heredada)"**, copia la **"Clave del servidor"**
4. Guarda esta clave, la necesitarás para el backend

---

## 📱 Configuración del Proyecto Android

### Verificación Automática

El proyecto ya está configurado con:

✅ **Plugin de Google Services** en `android/build.gradle`:
```gradle
classpath 'com.google.gms:google-services:4.4.4'
```

✅ **Aplicación del plugin** en `android/app/build.gradle`:
```gradle
apply plugin: 'com.google.gms.google-services'
```

✅ **Permisos** en `AndroidManifest.xml`:
```xml
<uses-permission android:name="android.permission.POST_NOTIFICATIONS" />
<uses-permission android:name="android.permission.VIBRATE" />
<uses-permission android:name="android.permission.RECEIVE_BOOT_COMPLETED" />
<uses-permission android:name="android.permission.WAKE_LOCK" />
```

### Verificar Configuración

1. **Verifica que `google-services.json` esté en el lugar correcto:**
   ```bash
   ls frontend/android/app/google-services.json
   ```

2. **Verifica que el `applicationId` coincida:**
   - En `android/app/build.gradle`: `applicationId "com.beautyspa.app"`
   - En `google-services.json`: `"package_name": "com.beautyspa.app"`

3. **Sincroniza el proyecto:**
   ```bash
   cd frontend
   npm run android:sync
   ```

---

## 💻 Implementación en el Frontend

### Código Ya Implementado

El proyecto ya incluye:

✅ **Composable `usePushNotifications.ts`** - Maneja todo el ciclo de vida de las notificaciones
✅ **Integración en `App.vue`** - Registra automáticamente el dispositivo al iniciar
✅ **Plugin Capacitor** - `@capacitor/push-notifications` instalado y configurado

### Flujo de Funcionamiento

1. **Al iniciar la app** (si el usuario está autenticado):
   - Se solicitan permisos de notificaciones
   - Se registra el dispositivo con Firebase
   - Se obtiene el token FCM
   - Se envía el token al backend para guardarlo

2. **Cuando llega una notificación**:
   - Si la app está en **primer plano**: Se muestra en el listener `pushNotificationReceived`
   - Si la app está en **segundo plano**: Android la muestra automáticamente
   - Si el usuario **toca la notificación**: Se dispara `pushNotificationActionPerformed`

### Personalización de Notificaciones

Puedes personalizar el comportamiento en `App.vue`:

```typescript
configurarListeners(
  (notificacion) => {
    // Notificación recibida en primer plano
    console.log('Notificación recibida:', notificacion);
    
    // Mostrar notificación local si lo deseas
    // LocalNotifications.schedule({ ... });
  },
  (accion) => {
    // Usuario tocó la notificación
    console.log('Acción en notificación:', accion);
    
    // Navegar a una pantalla específica
    // router.push('/citas/' + accion.notification.data.cita_id);
  }
);
```

---

## 🖥️ Configuración del Backend

### Paso 1: Agregar Variable de Entorno

En el archivo `.env` del backend, agrega:

```env
FIREBASE_SERVER_KEY=tu_clave_del_servidor_aquí
```

**Nota**: La clave del servidor la obtuviste en el Paso 4 de la configuración de Firebase.

### Paso 2: Verificar Configuración

El archivo `config/services.php` ya está configurado con:

```php
'firebase' => [
    'server_key' => env('FIREBASE_SERVER_KEY', ''),
    'fcm_url' => env('FIREBASE_FCM_URL', 'https://fcm.googleapis.com/fcm/send'),
],
```

### Paso 3: Enviar Notificaciones

El servicio `PushNotificationService` ya está implementado. Ejemplo de uso:

```php
use App\Services\PushNotificationService;

$pushService = new PushNotificationService();

// Enviar a un dispositivo específico
$resultado = $pushService->enviar(
    token: $tokenFCM,
    titulo: 'Nueva cita agendada',
    mensaje: 'Tu cita ha sido confirmada para el 15 de marzo',
    data: [
        'cita_id' => '123',
        'tipo' => 'cita_confirmada'
    ]
);

// Enviar a múltiples dispositivos
$resultado = $pushService->enviarMultiple(
    tokens: [$token1, $token2, $token3],
    titulo: 'Promoción especial',
    mensaje: 'Descuento del 20% en todos los servicios'
);
```

---

## 🧪 Pruebas y Verificación

### Paso 1: Construir la APK

```bash
cd frontend
npm run build:android
npm run apk:debug
```

### Paso 2: Instalar en Dispositivo

```bash
# Conecta tu dispositivo Android vía USB
# Habilita "Depuración USB" en opciones de desarrollador
adb install android/app/build/outputs/apk/debug/app-debug.apk
```

### Paso 3: Verificar Registro

1. Abre la app en el dispositivo
2. Inicia sesión
3. Acepta los permisos de notificaciones cuando se soliciten
4. Revisa los logs de Android Studio (Logcat):
   ```
   Push registration success, token: [tu_token_fcm]
   Token registrado en el servidor
   ```

### Paso 4: Probar Notificación

Puedes probar enviando una notificación desde Firebase Console:

1. Ve a Firebase Console > Cloud Messaging
2. Haz clic en **"Enviar tu primer mensaje"**
3. Completa título y mensaje
4. Selecciona **"Aplicación Android"**
5. Haz clic en **"Enviar mensaje de prueba"**
6. Ingresa el token FCM de tu dispositivo
7. La notificación debería llegar al dispositivo

---

## 🔍 Solución de Problemas

### Error: "SERVICE_NOT_AVAILABLE" o "AUTHENTICATION_FAILED"

**Causa**: `google-services.json` no está configurado correctamente.

**Solución**:
1. Verifica que `google-services.json` esté en `frontend/android/app/`
2. Verifica que el `package_name` coincida con `applicationId`
3. Reconstruye la APK: `npm run build:android`

### Error: "Permiso de notificaciones denegado"

**Causa**: El usuario denegó los permisos.

**Solución**:
1. Ve a Configuración > Apps > BeautySpa > Notificaciones
2. Habilita las notificaciones manualmente
3. O reinstala la app y acepta los permisos

### Las notificaciones no llegan

**Verificaciones**:
1. ✅ Token FCM registrado en la base de datos
2. ✅ `FIREBASE_SERVER_KEY` configurado en el backend
3. ✅ Dispositivo conectado a internet
4. ✅ App no está en modo "No molestar"
5. ✅ Revisa los logs del backend para errores

### Notificaciones solo funcionan en primer plano

**Causa**: Configuración incorrecta de FCM.

**Solución**:
- Verifica que `google-services.json` esté correctamente configurado
- Asegúrate de que el plugin de Google Services esté aplicado en `build.gradle`
- Reconstruye la APK completamente

### Token no se registra en el servidor

**Verificaciones**:
1. Usuario autenticado (el endpoint requiere autenticación)
2. API del backend accesible desde el dispositivo
3. Revisa los logs de la app: `Token registrado en el servidor`
4. Verifica la tabla `dispositivos` en la base de datos

---

## 📚 Recursos Adicionales

- [Documentación de Capacitor Push Notifications](https://capacitorjs.com/docs/apis/push-notifications)
- [Documentación de Firebase Cloud Messaging](https://firebase.google.com/docs/cloud-messaging)
- [Guía de Android Notifications](https://developer.android.com/develop/ui/views/notifications)

---

## ✅ Checklist de Implementación

- [x] Plugin `@capacitor/push-notifications` instalado
- [x] `google-services.json` descargado y colocado
- [x] Permisos agregados en `AndroidManifest.xml`
- [x] Plugin de Google Services configurado en `build.gradle`
- [x] Composable `usePushNotifications` implementado
- [x] Integración en `App.vue` completada
- [x] Backend configurado con `FIREBASE_SERVER_KEY`
- [x] Servicio `PushNotificationService` implementado
- [x] Endpoint `/api/dispositivos/registrar` funcionando
- [ ] APK construida y probada en dispositivo
- [ ] Notificación de prueba enviada exitosamente

---

## 🎯 Próximos Pasos

1. **Notificaciones programadas**: Implementar notificaciones locales para recordatorios
2. **Categorías de notificaciones**: Agrupar notificaciones por tipo (citas, promociones, etc.)
3. **Acciones en notificaciones**: Agregar botones de acción (Aceptar, Rechazar, etc.)
4. **Badge de notificaciones**: Mostrar contador de notificaciones no leídas
5. **Notificaciones silenciosas**: Enviar datos sin mostrar notificación visible

---

**Última actualización**: 2024

