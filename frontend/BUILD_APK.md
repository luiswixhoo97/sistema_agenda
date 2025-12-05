# Guía para Generar APK

## Prerrequisitos

1. **Android Studio** instalado (para usar Gradle)
2. **Java JDK 17 o superior** instalado
3. **Node.js** instalado (ya lo tienes)

## Pasos para Generar el APK

### 1. Construir la aplicación web (producción)

```bash
npm run build
```

Esto genera los archivos optimizados en la carpeta `dist/`.

### 2. Sincronizar con Capacitor

```bash
npx cap sync android
```

Esto copia los archivos de `dist/` a la carpeta `android/` y actualiza los plugins.

### 3. Abrir en Android Studio (Opcional pero recomendado)

```bash
npx cap open android
```

O manualmente:
- Abre Android Studio
- File → Open → Selecciona la carpeta `frontend/android`

### 4. Generar el APK

Tienes dos opciones:

#### Opción A: Desde Android Studio (Recomendado)
1. Build → Generate Signed Bundle / APK
2. Selecciona APK
3. Crea un keystore (si es la primera vez) o usa uno existente
4. Selecciona "release" build variant
5. Click "Finish"
6. El APK estará en: `android/app/release/app-release.apk`

#### Opción B: Desde la línea de comandos (Más rápido)

```bash
cd android
./gradlew assembleRelease
```

En Windows:
```bash
cd android
gradlew.bat assembleRelease
```

El APK estará en: `android/app/build/outputs/apk/release/app-release.apk`

### 5. Instalar en tu celular

1. Transfiere el archivo `app-release.apk` a tu celular
2. En tu celular, ve a Configuración → Seguridad → Permite instalación de apps de origen desconocido
3. Abre el archivo APK y sigue las instrucciones

## Scripts Rápidos

Puedes agregar estos scripts a tu `package.json`:

```json
{
  "scripts": {
    "build:android": "npm run build && npx cap sync android",
    "apk:debug": "npm run build:android && cd android && ./gradlew assembleDebug",
    "apk:release": "npm run build:android && cd android && ./gradlew assembleRelease"
  }
}
```

Luego ejecuta:
```bash
npm run apk:release
```

## Notas Importantes

- **Primera vez**: Necesitarás crear un keystore para firmar el APK de release
- **Debug APK**: Para pruebas rápidas, usa `assembleDebug` (no requiere firma)
- **Versión**: Actualiza `versionCode` y `versionName` en `android/app/build.gradle` antes de cada release
- **Permisos**: Asegúrate de que todos los permisos necesarios estén en `AndroidManifest.xml`

## Solución de Problemas

### Error: "SDK location not found"
Crea un archivo `android/local.properties`:
```
sdk.dir=C:\\Users\\TU_USUARIO\\AppData\\Local\\Android\\Sdk
```

### Error: "Gradle sync failed"
- Abre Android Studio
- File → Sync Project with Gradle Files

### APK muy grande
- El APK de debug es más grande que el de release
- Usa `assembleRelease` para producción

