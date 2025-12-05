# Guía Completa para Generar e Instalar APK

## ✅ Pasos Completados

1. ✅ Build de producción ejecutado
2. ✅ Sincronización con Capacitor completada

## ⚠️ Requisito Pendiente: Java JDK 11+

El error indica que necesitas **Java JDK 11 o superior**. Actualmente tienes Java 8.

### Opción 1: Instalar Java JDK 17 (Recomendado)

1. **Descargar Java JDK 17:**
   - Ve a: https://adoptium.net/temurin/releases/
   - Selecciona: Windows x64 → JDK 17 → .msi installer
   - O usa: https://www.oracle.com/java/technologies/javase/jdk17-archive-downloads.html

2. **Instalar:**
   - Ejecuta el instalador
   - Asegúrate de marcar "Add to PATH" durante la instalación

3. **Verificar instalación:**
   ```bash
   java -version
   ```
   Debe mostrar: `openjdk version "17.x.x"` o similar

4. **Configurar JAVA_HOME (si es necesario):**
   - Abre Variables de Entorno en Windows
   - Crea nueva variable: `JAVA_HOME`
   - Valor: `C:\Program Files\Java\jdk-17` (ajusta según tu instalación)
   - Agrega `%JAVA_HOME%\bin` al PATH

### Opción 2: Usar Android Studio (Más fácil)

Android Studio incluye su propio JDK. Si tienes Android Studio:

1. Abre Android Studio
2. File → Open → Selecciona la carpeta `frontend/android`
3. Espera a que sincronice Gradle
4. Build → Build Bundle(s) / APK(s) → Build APK(s)
5. El APK estará en: `android/app/build/outputs/apk/debug/app-debug.apk`

## 📱 Generar APK (Después de instalar Java)

### Método 1: Desde la línea de comandos

```bash
# Desde la carpeta frontend
cd android
./gradlew assembleDebug    # Para APK de debug (pruebas)
./gradlew assembleRelease  # Para APK de release (producción)
```

El APK estará en:
- Debug: `android/app/build/outputs/apk/debug/app-debug.apk`
- Release: `android/app/build/outputs/apk/release/app-release.apk`

### Método 2: Usar los scripts de npm (Más fácil)

Ya agregué scripts al `package.json`:

```bash
# Desde la carpeta frontend
npm run apk:debug     # Genera APK de debug
npm run apk:release   # Genera APK de release (requiere firma)
```

## 🔐 APK de Release (Producción)

Para generar un APK de release firmado:

1. **Crear un keystore (solo primera vez):**
   ```bash
   keytool -genkey -v -keystore beautyspa-release-key.jks -keyalg RSA -keysize 2048 -validity 10000 -alias beautyspa
   ```
   - Guarda el archivo `beautyspa-release-key.jks` en un lugar seguro
   - Anota la contraseña que uses

2. **Configurar signing en `android/app/build.gradle`:**
   ```gradle
   android {
       ...
       signingConfigs {
           release {
               storeFile file('path/to/beautyspa-release-key.jks')
               storePassword 'TU_PASSWORD'
               keyAlias 'beautyspa'
               keyPassword 'TU_PASSWORD'
           }
       }
       buildTypes {
           release {
               signingConfig signingConfigs.release
               ...
           }
       }
   }
   ```

3. **Generar APK firmado:**
   ```bash
   npm run apk:release
   ```

## 📲 Instalar APK en tu celular

### Opción A: Transferencia directa

1. Conecta tu celular por USB
2. Copia el archivo `.apk` a tu celular
3. En tu celular: Configuración → Seguridad → Activar "Origen desconocido"
4. Abre el archivo APK desde el explorador de archivos
5. Sigue las instrucciones de instalación

### Opción B: ADB (Android Debug Bridge)

1. Activa "Opciones de desarrollador" en tu celular:
   - Configuración → Acerca del teléfono → Toca 7 veces "Número de compilación"
2. Activa "Depuración USB"
3. Conecta por USB
4. Ejecuta:
   ```bash
   adb install android/app/build/outputs/apk/debug/app-debug.apk
   ```

### Opción C: Compartir por WhatsApp/Email

1. Envía el archivo APK por WhatsApp o email a ti mismo
2. Abre el archivo desde tu celular
3. Instala

## 🚀 Resumen Rápido

```bash
# 1. Instalar Java 17 (si no lo tienes)
# 2. Build y sincronizar
npm run build:android

# 3. Generar APK de debug (pruebas rápidas)
npm run apk:debug

# 4. El APK estará en: android/app/build/outputs/apk/debug/app-debug.apk
```

## ❓ Solución de Problemas

### Error: "JAVA_HOME not set"
```bash
# En Windows (PowerShell como Admin)
[System.Environment]::SetEnvironmentVariable("JAVA_HOME", "C:\Program Files\Java\jdk-17", "Machine")
```

### Error: "SDK location not found"
Crea `android/local.properties`:
```
sdk.dir=C:\\Users\\TU_USUARIO\\AppData\\Local\\Android\\Sdk
```

### APK no se instala en el celular
- Verifica que tengas "Origen desconocido" activado
- Asegúrate de que el APK no esté corrupto
- Prueba con el APK de debug primero

