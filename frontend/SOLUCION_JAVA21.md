# Solución: Java 21 Requerido

El plugin de Capacitor Filesystem requiere **Java 21**. Tienes dos opciones:

## Opción 1: Instalar Java 21 (Recomendado - 5 minutos)

1. **Descargar Java 21:**
   - Ve a: https://adoptium.net/temurin/releases/
   - Selecciona: **Windows x64** → **JDK 21** → **.msi installer**
   - Descarga e instala

2. **Verificar instalación:**
   ```bash
   java -version
   ```
   Debe mostrar: `openjdk version "21.x.x"`

3. **Generar APK:**
   ```bash
   cd frontend
   npm run apk:debug
   ```

## Opción 2: Usar Android Studio (Más fácil)

Android Studio incluye Java 21 y maneja todo automáticamente:

1. Abre **Android Studio**
2. **File** → **Open** → Selecciona la carpeta `frontend/android`
3. Espera a que sincronice Gradle (puede tomar unos minutos la primera vez)
4. **Build** → **Build Bundle(s) / APK(s)** → **Build APK(s)**
5. El APK estará en: `android/app/build/outputs/apk/debug/app-debug.apk`

## Opción 3: Configurar Gradle para descargar Java 21 automáticamente

Si prefieres no instalar Java 21, puedes configurar Gradle para que lo descargue automáticamente:

1. Edita `frontend/android/gradle.properties` y agrega:
   ```properties
   # Enable automatic Java toolchain download
   org.gradle.java.installations.auto-download=true
   ```

2. Edita `frontend/android/build.gradle` y agrega al final:
   ```gradle
   // Configure toolchain to download Java 21 if needed
   allprojects {
       tasks.withType(JavaCompile).configureEach {
           javaCompiler = javaToolchains.compilerFor {
               languageVersion = JavaLanguageVersion.of(21)
           }
       }
   }
   ```

3. Ejecuta:
   ```bash
   cd frontend/android
   ./gradlew assembleDebug
   ```

**Nota:** La primera vez descargará Java 21 (aprox. 200MB), luego será más rápido.

## Recomendación

**Usa Android Studio** - Es la forma más fácil y no requiere instalar Java manualmente. Además, te permite:
- Ver errores de compilación fácilmente
- Depurar la aplicación
- Generar APKs firmados fácilmente
- Ver el progreso del build visualmente

