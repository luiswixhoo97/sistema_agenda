# Cómo Verificar el Registro del Dispositivo y Permisos

## 📋 Verificación 1: Permisos de Notificaciones

### Método 1: Desde la App (Visual)

1. **Instala la APK en tu dispositivo Android**
   ```bash
   adb install app-debug.apk
   ```

2. **Abre la app**
   - Al iniciar, deberías ver un diálogo del sistema Android pidiendo permisos de notificaciones

3. **Acepta los permisos**
   - Toca "Permitir" cuando aparezca el diálogo
   - Si lo rechazas, puedes habilitarlo después desde Configuración del sistema

### Método 2: Verificar desde Configuración del Sistema

1. Ve a **Configuración** del dispositivo Android
2. **Apps** > **BeautySpa**
3. **Permisos** > **Notificaciones**
4. Verifica que esté **habilitado**

### Método 3: Verificar desde Logs (Técnico)

1. **Conecta el dispositivo vía USB**
2. **Habilita "Depuración USB"** en Opciones de desarrollador
3. **Abre Android Studio** o usa `adb logcat`
4. **Filtra los logs**:
   ```bash
   adb logcat | grep -i "push\|notification\|permission"
   ```

5. **Busca estos mensajes**:
   ```
   Push registration success, token: [token_fcm]
   Token registrado en el servidor
   ```

   Si ves estos mensajes, los permisos fueron aceptados y el registro funcionó.

---

## 📋 Verificación 2: Token FCM Registrado en Base de Datos

### Método 1: Consulta SQL Directa

1. **Conecta a tu base de datos** (MySQL, PostgreSQL, SQLite, etc.)

2. **Ejecuta esta consulta**:
   ```sql
   SELECT 
       id,
       cliente_id,
       token_push,
       plataforma,
       modelo,
       activo,
       last_used_at,
       created_at
   FROM dispositivos
   WHERE activo = 1
   ORDER BY created_at DESC;
   ```

3. **Verifica que**:
   - ✅ Existe al menos un registro con `activo = 1`
   - ✅ El campo `token_push` tiene un valor (string largo, ~150 caracteres)
   - ✅ El `cliente_id` corresponde a tu usuario
   - ✅ La `plataforma` es `'android'`
   - ✅ El `created_at` es reciente (después de instalar la app)

### Método 2: Desde Laravel Tinker

1. **Abre la terminal en el backend**:
   ```bash
   cd backend
   php artisan tinker
   ```

2. **Ejecuta**:
   ```php
   // Ver todos los dispositivos activos
   \App\Models\Dispositivo::where('activo', true)->get();
   
   // Ver dispositivos de un cliente específico
   \App\Models\Dispositivo::where('cliente_id', 1)
       ->where('activo', true)
       ->get();
   
   // Contar dispositivos registrados
   \App\Models\Dispositivo::where('activo', true)->count();
   ```

### Método 3: Desde Logs del Backend

1. **Revisa los logs de Laravel**:
   ```bash
   cd backend
   tail -f storage/logs/laravel.log
   ```

2. **Busca estos mensajes** cuando inicies sesión en la app**:
   ```
   Token registrado en el servidor
   ```

   Si ves este mensaje, el token se guardó correctamente en la BD.

### Método 4: Verificar desde la API (Postman/Insomnia)

1. **Inicia sesión en la app** para obtener el token de autenticación

2. **Haz una petición GET** a tu API (si tienes un endpoint para listar dispositivos):
   ```
   GET /api/dispositivos
   Authorization: Bearer [tu_token]
   ```

   O verifica directamente en la BD con el método 1 o 2.

---

## 🔍 Flujo Completo de Verificación

### Paso 1: Preparar el Entorno

```bash
# Terminal 1: Logs del backend
cd backend
tail -f storage/logs/laravel.log

# Terminal 2: Logcat de Android
adb logcat | grep -i "push\|notification"
```

### Paso 2: Instalar y Abrir la App

1. Instala la APK:
   ```bash
   adb install app-debug.apk
   ```

2. Abre la app en el dispositivo

3. **Inicia sesión** como cliente

### Paso 3: Verificar en Tiempo Real

**En los logs de Android (Terminal 2)**, deberías ver:
```
Push registration success, token: [un_token_muy_largo]
Token registrado en el servidor
```

**En los logs del backend (Terminal 1)**, deberías ver:
```
Token registrado en el servidor
```

### Paso 4: Verificar en Base de Datos

```sql
-- Ver el último dispositivo registrado
SELECT * FROM dispositivos 
WHERE activo = 1 
ORDER BY created_at DESC 
LIMIT 1;
```

**Resultado esperado**:
```
id: 1
cliente_id: [tu_id]
token_push: [token_fcm_largo]
plataforma: 'android'
modelo: [modelo_del_dispositivo]
activo: 1
last_used_at: [fecha_actual]
created_at: [fecha_actual]
```

---

## ❌ Problemas Comunes y Soluciones

### Problema 1: No aparece el diálogo de permisos

**Causa**: Ya fueron denegados anteriormente o la app no los solicita correctamente.

**Solución**:
1. Ve a Configuración > Apps > BeautySpa > Permisos
2. Habilita "Notificaciones" manualmente
3. O desinstala y reinstala la app

### Problema 2: No se registra el token en la BD

**Verificaciones**:
1. ✅ Usuario autenticado (debe estar logueado)
2. ✅ API del backend accesible desde el dispositivo
3. ✅ Endpoint `/api/dispositivos/registrar` funcionando
4. ✅ Revisar logs del backend para errores

**Solución**:
```bash
# Verificar que el endpoint funciona
cd backend
php artisan route:list | grep dispositivos

# Probar manualmente (reemplaza con tu token de auth)
curl -X POST http://tu-api.com/api/dispositivos/registrar \
  -H "Authorization: Bearer [token]" \
  -H "Content-Type: application/json" \
  -d '{
    "token": "test_token_123",
    "plataforma": "android"
  }'
```

### Problema 3: Token se registra pero no llegan notificaciones

**Verificaciones**:
1. ✅ `FIREBASE_SERVER_KEY` configurado en `.env`
2. ✅ Worker de colas corriendo (`php artisan queue:work`)
3. ✅ Token FCM válido (no expirado)
4. ✅ Dispositivo conectado a internet

---

## ✅ Checklist de Verificación Rápida

Marca cada paso cuando lo completes:

- [ ] APK instalada en dispositivo
- [ ] App abierta e iniciada sesión
- [ ] Diálogo de permisos apareció y fue aceptado
- [ ] Logs muestran: "Push registration success, token: ..."
- [ ] Logs muestran: "Token registrado en el servidor"
- [ ] Consulta SQL muestra registro en tabla `dispositivos`
- [ ] El registro tiene `activo = 1`
- [ ] El registro tiene `token_push` con valor válido
- [ ] El registro tiene `cliente_id` correcto

---

## 🎯 Resultado Esperado

Cuando todo funcione correctamente:

1. **Al abrir la app e iniciar sesión**:
   - Aparece diálogo de permisos → Aceptas
   - Logs muestran: "Push registration success"
   - Logs muestran: "Token registrado en el servidor"
   - Se crea registro en tabla `dispositivos`

2. **Al agendar una cita**:
   - Se envía notificación push al dispositivo
   - Aparece notificación en el dispositivo Android
   - Se crea registro en tabla `notificaciones`

---

**Última actualización**: 2024-12-15

