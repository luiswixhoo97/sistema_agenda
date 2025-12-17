# ✅ Checklist: Implementación de Notificaciones Push Android

## 📱 Frontend (Vue + Capacitor)

### ✅ Completado
- [x] Plugin `@capacitor/push-notifications` instalado
- [x] Composable `usePushNotifications.ts` implementado
- [x] Integración en `App.vue` para registro automático
- [x] `google-services.json` configurado correctamente
- [x] Permisos en `AndroidManifest.xml` configurados
- [x] Package name corregido: `com.beautyspa.app`
- [x] APK compilada y lista

### ⚠️ Pendiente de Verificar
- [ ] **Dispositivo registrado**: Verificar que al abrir la app e iniciar sesión, el token FCM se registre en la BD
  - 📖 **Ver guía**: `COMO_VERIFICAR_REGISTRO_DISPOSITIVO.md`
  - Consulta SQL: `SELECT * FROM dispositivos WHERE activo = 1;`
  - Logs: Buscar "Token registrado en el servidor"
- [ ] **Permisos aceptados**: Verificar que el usuario acepte los permisos de notificaciones
  - 📖 **Ver guía**: `COMO_VERIFICAR_REGISTRO_DISPOSITIVO.md`
  - Configuración Android > Apps > BeautySpa > Permisos > Notificaciones
  - Logs: Buscar "Push registration success, token: ..."

---

## 🖥️ Backend (Laravel)

### ✅ Completado
- [x] Servicio `PushNotificationService` implementado
- [x] Job `EnviarPushNotificationJob` implementado
- [x] Configuración en `config/services.php`
- [x] Endpoint `/api/dispositivos/registrar` funcionando
- [x] Bug corregido: `token_push` en lugar de `token`
- [x] Integración con `NotificacionService` para enviar push al crear citas

### ⚠️ Pendiente de Configurar

#### 1. Variable de Entorno Firebase
**Archivo**: `backend/.env`

```env
FIREBASE_SERVER_KEY=tu_clave_del_servidor_aquí
```

**Cómo obtenerla**:
1. Ve a Firebase Console
2. Configuración del proyecto (ícono de engranaje)
3. Pestaña "Cloud Messaging"
4. En "API de Cloud Messaging (heredada)", copia la "Clave del servidor"

**Estado actual**: Hay un valor por defecto en `services.php`, pero es mejor configurarlo en `.env`

#### 2. Configuración de Colas (Queue)
**Archivo**: `backend/.env`

```env
QUEUE_CONNECTION=database
```

**Verificar**: Ya está configurado por defecto en `config/queue.php`

#### 3. Ejecutar Worker de Colas
**IMPORTANTE**: Las notificaciones se envían mediante colas, necesitas ejecutar:

```bash
cd backend
php artisan queue:work
```

**Para producción**, usa:
```bash
php artisan queue:work --tries=3 --timeout=90 --sleep=3
```

**O con supervisor** (recomendado para producción):
```ini
[program:laravel-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /ruta/a/backend/artisan queue:work --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/ruta/a/backend/storage/logs/worker.log
stopwaitsecs=3600
```

---

## 🗄️ Base de Datos

### ✅ Estructura
- [x] Tabla `dispositivos` creada
- [x] Tabla `notificaciones` creada
- [x] Relaciones configuradas correctamente

### ⚠️ Verificar
- [ ] **Registro de dispositivo**: Al instalar la app y iniciar sesión, verificar que se cree un registro en `dispositivos`:
  ```sql
  SELECT * FROM dispositivos WHERE cliente_id = TU_CLIENTE_ID AND activo = 1;
  ```
- [ ] **Token FCM guardado**: Verificar que el campo `token_push` tenga un valor válido (string largo)

---

## 🔥 Firebase

### ✅ Completado
- [x] Proyecto Firebase creado
- [x] App Android agregada
- [x] `google-services.json` descargado y colocado
- [x] Package name corregido: `com.beautyspa.app`

### ⚠️ Pendiente
- [ ] **Clave del servidor**: Obtener y configurar en `.env` del backend
- [ ] **Cloud Messaging habilitado**: Verificar que esté habilitado en Firebase Console

---

## 🧪 Pruebas

### Pasos para Probar

1. **Instalar APK en dispositivo**
   ```bash
   adb install app-debug.apk
   ```

2. **Abrir la app e iniciar sesión**
   - Aceptar permisos de notificaciones cuando se soliciten

3. **Verificar registro del dispositivo**
   - Revisar logs de la app: `"Push registration success, token: ..."`
   - Revisar logs del backend: `"Token registrado en el servidor"`
   - Verificar en BD: `SELECT * FROM dispositivos WHERE activo = 1;`

4. **Verificar que el worker de colas esté corriendo**
   ```bash
   cd backend
   php artisan queue:work
   ```

5. **Agendar una cita**
   - Crear una cita desde la app
   - Verificar que se envíe la notificación push

6. **Probar desde Firebase Console** (opcional)
   - Firebase Console > Cloud Messaging
   - "Enviar tu primer mensaje"
   - Ingresar el token FCM del dispositivo
   - Verificar que llegue la notificación

---

## 🔍 Verificación de Funcionamiento

### Logs a Revisar

#### Frontend (Logcat de Android Studio)
```
Push registration success, token: [token_fcm]
Token registrado en el servidor
```

#### Backend (Laravel Log)
```
Token registrado en el servidor
Push notification enviada exitosamente
```

#### Base de Datos
```sql
-- Verificar dispositivos registrados
SELECT id, cliente_id, plataforma, activo, created_at 
FROM dispositivos 
WHERE activo = 1;

-- Verificar notificaciones enviadas
SELECT id, cita_id, cliente_id, tipo, medio, estado, enviado_at 
FROM notificaciones 
ORDER BY created_at DESC 
LIMIT 10;
```

---

## ❌ Problemas Comunes

### 1. "No se reciben notificaciones"
**Causas posibles**:
- ❌ Worker de colas no está corriendo (`php artisan queue:work`)
- ❌ `FIREBASE_SERVER_KEY` no configurado o incorrecto
- ❌ Dispositivo no registrado en la BD
- ❌ Token FCM inválido o expirado
- ❌ App en modo "No molestar"

**Solución**:
1. Verificar que el worker esté corriendo
2. Verificar `FIREBASE_SERVER_KEY` en `.env`
3. Verificar que el dispositivo esté registrado en la BD
4. Revisar logs del backend para errores

### 2. "Token no se registra"
**Causas posibles**:
- ❌ Usuario no autenticado
- ❌ API del backend no accesible
- ❌ Error en el endpoint `/api/dispositivos/registrar`

**Solución**:
1. Verificar que el usuario esté autenticado
2. Verificar que la API sea accesible desde el dispositivo
3. Revisar logs del backend para errores

### 3. "Error: SERVICE_NOT_AVAILABLE"
**Causa**: `google-services.json` no configurado correctamente

**Solución**:
1. Verificar que `google-services.json` esté en `frontend/android/app/`
2. Verificar que el `package_name` coincida con `applicationId`
3. Reconstruir la APK

---

## 📋 Resumen de Acciones Pendientes

### Crítico (Necesario para que funcione)
1. ⚠️ **Configurar `FIREBASE_SERVER_KEY` en `.env` del backend**
2. ⚠️ **Ejecutar `php artisan queue:work` en el backend**
3. ⚠️ **Instalar la APK y verificar que el dispositivo se registre**

### Importante (Recomendado)
4. ⚠️ **Verificar que Cloud Messaging esté habilitado en Firebase**
5. ⚠️ **Probar agendando una cita y verificando que llegue la notificación**

### Opcional (Mejoras)
6. ⚠️ **Configurar supervisor para el worker en producción**
7. ⚠️ **Agregar monitoreo de notificaciones fallidas**

---

## ✅ Estado Actual

- **Frontend**: ✅ 100% implementado
- **Backend**: ✅ 95% implementado (falta configurar variables de entorno)
- **Firebase**: ✅ 90% configurado (falta obtener clave del servidor)
- **Base de Datos**: ✅ 100% lista
- **Pruebas**: ⚠️ Pendiente de ejecutar

---

**Última actualización**: 2024-12-15

