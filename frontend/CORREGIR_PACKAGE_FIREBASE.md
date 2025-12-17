# Cómo Corregir el Package Name en Firebase

## ⚠️ Problema Detectado

Tu Firebase tiene registrado: `com_beautyspa.app` (con guión bajo)
Tu proyecto Android usa: `com.beautyspa.app` (con puntos)

**Esto causará que las notificaciones push NO funcionen.**

## ✅ Solución: Actualizar Firebase

### Opción 1: Editar la App Android (si Firebase lo permite)

1. Ve a Firebase Console
2. Configuración del proyecto (ícono de engranaje)
3. Pestaña "Tus apps"
4. Busca tu app Android
5. Haz clic en el ícono de editar (lápiz) junto a "Nombre del paquete"
6. Cambia de `com_beautyspa.app` a `com.beautyspa.app` (con puntos)
7. Guarda los cambios
8. Descarga el nuevo `google-services.json`
9. Reemplaza el archivo en `frontend/android/app/google-services.json`

### Opción 2: Eliminar y Crear de Nuevo (Recomendado)

Si Firebase no te permite editar el package name:

1. **Eliminar la app Android actual:**
   - Ve a Firebase Console
   - Configuración del proyecto > Tus apps
   - Busca tu app Android
   - Haz clic en los tres puntos (⋮) > Eliminar app
   - Confirma la eliminación

2. **Crear nueva app Android:**
   - Haz clic en "Agregar app" > Android
   - **Nombre del paquete Android**: `com.beautyspa.app` ← **CON PUNTOS**
   - **Apodo de la app**: `BeautySpa`
   - Haz clic en "Registrar app"

3. **Descargar nuevo google-services.json:**
   - Descarga el archivo `google-services.json`
   - Reemplázalo en `frontend/android/app/google-services.json`

## 🔍 Verificación

Después de actualizar, verifica que:

1. **Firebase Console** muestra: `com.beautyspa.app` (con puntos)
2. **google-services.json** tiene: `"package_name": "com.beautyspa.app"` (con puntos)
3. **build.gradle** tiene: `applicationId "com.beautyspa.app"` (con puntos)

Todos deben coincidir exactamente.

## 📝 Nota Importante

- El package name en Firebase **NO se puede cambiar** después de crear la app (por eso a veces hay que eliminarla y crearla de nuevo)
- Una vez que lo configures correctamente, no lo cambies más
- Si ya tienes usuarios, eliminar la app en Firebase no afecta a los usuarios instalados, solo necesitas el nuevo `google-services.json`

## 🚀 Después de Corregir

1. Sincroniza el proyecto:
   ```bash
   cd frontend
   npm run android:sync
   ```

2. Limpia y reconstruye:
   ```bash
   cd frontend/android
   ./gradlew clean
   cd ../..
   npm run build:android
   ```

3. Prueba las notificaciones push




