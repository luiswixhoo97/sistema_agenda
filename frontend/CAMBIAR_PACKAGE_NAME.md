# Guía: Cambiar el Package Name de Android

## ⚠️ Importante

El **package name** (applicationId) de Android es el identificador único de tu app. Si lo cambias:

- ✅ La app se considerará una **nueva aplicación** diferente
- ✅ Los usuarios tendrán que **desinstalar la versión anterior** antes de instalar la nueva
- ✅ Necesitarás **actualizar Firebase** con el nuevo package name
- ✅ Necesitarás **mover/renombrar** el directorio de código Java

## 📝 Formato del Package Name

El package name de Android debe seguir estas reglas:
- Usar **puntos** (`.`) como separadores: `com.tuempresa.tuapp`
- No puede empezar ni terminar con punto
- Cada segmento debe empezar con letra
- Solo letras minúsculas, números y guiones bajos (pero mejor evitar guiones bajos)

**Ejemplo válido**: `com.beautyspa.app`, `com.miempresa.salon`, `com.salon.app`

## 🔄 Pasos para Cambiar el Package Name

### Paso 1: Elegir el Nuevo Package Name

Ejemplo: Si quieres cambiar de `com.beautyspa.app` a `com.misalon.app`

### Paso 2: Actualizar build.gradle

**Archivo**: `frontend/android/app/build.gradle`

```gradle
android {
    namespace "com.misalon.app"  // ← Cambiar aquí
    // ...
    defaultConfig {
        applicationId "com.misalon.app"  // ← Cambiar aquí
        // ...
    }
}
```

### Paso 3: Actualizar capacitor.config.ts

**Archivo**: `frontend/capacitor.config.ts`

```typescript
const config: CapacitorConfig = {
  appId: 'com_misalon.app',  // ← Cambiar aquí (puede usar guión bajo)
  // ...
};
```

### Paso 4: Mover y Renombrar MainActivity.java

**Archivo actual**: `frontend/android/app/src/main/java/com/beautyspa/app/MainActivity.java`

1. **Crear nueva estructura de directorios**:
   - Si el nuevo package es `com.misalon.app`
   - Crear: `frontend/android/app/src/main/java/com/misalon/app/`

2. **Mover el archivo**:
   ```bash
   # Desde frontend/android/app/src/main/java/
   mkdir -p com/misalon/app
   mv com/beautyspa/app/MainActivity.java com/misalon/app/
   ```

3. **Actualizar el package en MainActivity.java**:
   ```java
   package com.misalon.app;  // ← Cambiar aquí
   
   import com.getcapacitor.BridgeActivity;
   
   public class MainActivity extends BridgeActivity {}
   ```

4. **Eliminar directorios vacíos**:
   ```bash
   rm -rf com/beautyspa
   ```

### Paso 5: Actualizar strings.xml

**Archivo**: `frontend/android/app/src/main/res/values/strings.xml`

```xml
<resources>
    <string name="app_name">BeautySpa</string>
    <string name="title_activity_main">BeautySpa</string>
    <string name="package_name">com_misalon.app</string>  <!-- ← Cambiar aquí -->
    <string name="custom_url_scheme">com_misalon.app</string>  <!-- ← Cambiar aquí -->
</resources>
```

### Paso 6: Actualizar Firebase

1. **Opción A: Agregar nueva app Android en Firebase**
   - Ve a Firebase Console
   - Selecciona tu proyecto
   - Haz clic en "Agregar app" > Android
   - Ingresa el **nuevo package name**: `com.misalon.app`
   - Descarga el nuevo `google-services.json`
   - Reemplaza el archivo en `frontend/android/app/google-services.json`

2. **Opción B: Actualizar app existente** (si Firebase lo permite)
   - Ve a Configuración del proyecto > Tus apps
   - Edita la app Android
   - Actualiza el package name
   - Descarga el nuevo `google-services.json`

### Paso 7: Sincronizar Capacitor

```bash
cd frontend
npm run android:sync
```

### Paso 8: Limpiar y Reconstruir

```bash
cd frontend/android
./gradlew clean
cd ../..
npm run build:android
```

## ✅ Verificación

Después de cambiar el package name, verifica:

1. **build.gradle**: `applicationId` y `namespace` coinciden
2. **google-services.json**: `package_name` coincide con `applicationId`
3. **MainActivity.java**: El `package` coincide con la estructura de directorios
4. **capacitor.config.ts**: `appId` está actualizado (puede usar guión bajo)

## 🚨 Problemas Comunes

### Error: "Package name mismatch"

**Causa**: El `package_name` en `google-services.json` no coincide con `applicationId`.

**Solución**: Asegúrate de que ambos usen el mismo formato (con puntos).

### Error: "Cannot find MainActivity"

**Causa**: El directorio de MainActivity no coincide con el package.

**Solución**: Verifica que la estructura de directorios coincida exactamente con el package name.

### Error: "App already installed"

**Causa**: Ya tienes la app instalada con el package name anterior.

**Solución**: Desinstala la app anterior:
```bash
adb uninstall com.beautyspa.app
```

## 💡 Recomendación

**Si tu app ya está en producción o tiene usuarios**, considera:

1. **Mantener el package name actual** si no hay una razón fuerte para cambiarlo
2. **Si debes cambiarlo**, comunica a los usuarios que necesitan desinstalar y reinstalar
3. **Usa un package name definitivo** desde el inicio para evitar cambios futuros

## 📚 Ejemplos de Package Names

- ✅ `com.beautyspa.app`
- ✅ `com.misalon.app`
- ✅ `com.salon.belleza`
- ✅ `com.empresa.servicios`
- ❌ `com.BeautySpa.App` (no usar mayúsculas)
- ❌ `com.beautyspa-app` (no usar guiones)
- ❌ `com.beautyspa.app.` (no terminar con punto)

---

**Nota**: El package name es permanente una vez que publiques la app en Google Play Store. Cambiarlo después requerirá crear una nueva app en la tienda.

