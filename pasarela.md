## 📝 Changelog - Últimos Cambios

### Últimos 11 Commits (17 de Diciembre, 2025)

#### Commit `ce0f8dc` - agendar (15:35)
- **Archivos modificados:**
  - `.htaccess` - Configuración de Apache para enrutamiento
  - `frontend/src/views/auth/LoginView.vue` - Ajustes en vista de login

#### Commit `1d8c486` - correción en tipado (15:25)
- **Archivos modificados:**
  - `frontend/src/views/admin/CategoriasProductosView.vue` - Corrección de tipos TypeScript

#### Commit `79354c6` - mejoras a la versión web (15:21)
- **Archivos modificados:**
  - `frontend/public/.htaccess` - Configuración completa de Apache para SPA (119 líneas)
  - Mejoras en el enrutamiento y configuración para la versión web

#### Commit `a0a8847` - pagos (15:17)
- **Integración de Mercado Pago:**
  - Nuevo servicio `MercadoPagoService.php` (328 líneas)
  - Controlador `MercadoPagoController.php` (370 líneas)
  - Modelo `MercadoPagoPago.php` (62 líneas)
  - Migración para tabla `mercado_pago_pagos`
  - Documentación `MERCADOPAGO_SETUP.md` (134 líneas)
  - Servicio frontend `mercadopagoService.ts` (82 líneas)
  - Logos de métodos de pago (PayPal, Stripe, Mercado Pago)
  - Actualización de `PasoConfirmacion.vue` con integración de pagos
  - Estilos CSS mejorados para componente de confirmación (430 líneas)
  - Rutas públicas para procesamiento de pagos

**Comandos para aplicar cambios:**
```powershell
# Backend - Instalar dependencias de Mercado Pago
cd backend
composer install

# Ejecutar migración
php artisan migrate

# Configurar variables de entorno en .env
# MERCADOPAGO_ACCESS_TOKEN=tu_token
# MERCADOPAGO_PUBLIC_KEY=tu_public_key
```

#### Commit `a645baa` - guardar telefono en localstorage (14:40)
- **Archivos modificados:**
  - `frontend/src/components/agendamiento/PasoDatosCliente.vue` - Persistencia del teléfono en localStorage para mejor UX

#### Commit `988e001` - Boton de tema (14:33)
- **Archivos modificados:**
  - `frontend/src/views/cliente/AgendarView.vue` - Agregado botón para cambiar tema (modo claro/oscuro)

#### Commit `6adf075` - Datos de anticipo (14:31)
- **Sistema de anticipos:**
  - Nuevo controlador `AnticipoController.php` (77 líneas)
  - Actualización de `AgendamientoPublicoController.php` con lógica de anticipos
  - Mejoras en `CitaService.php` y `NotificacionService.php`
  - Nuevo seeder `DatosBancariosSeeder.php` (60 líneas)
  - Actualización de `PasoConfirmacion.vue` con formulario de anticipo
  - Nuevos estilos CSS para confirmación (389 líneas)
  - Servicio frontend `citaService.ts` actualizado
  - Store de citas (`citas.ts`) con gestión de anticipos (106 líneas)
  - Rutas públicas para anticipos

**Comandos para aplicar cambios:**
```powershell
# Backend - Ejecutar seeder de datos bancarios
cd backend
php artisan db:seed --class=DatosBancariosSeeder
```

#### Commit `70a4e40` - Add CSS styles for PasoConfirmacion component (13:23)
- **Refactorización de estilos:**
  - Separación de estilos CSS del componente Vue
  - Nuevo archivo `PasoConfirmacion.css` (897 líneas)
  - Limpieza de `PasoConfirmacion.vue` (eliminadas 900 líneas de estilos inline)

#### Commit `c0d5ff8` - Buscar cliente (13:16)
- **Búsqueda de clientes:**
  - Nuevo endpoint en `ClienteController.php` para búsqueda (43 líneas)
  - Actualización de `PasoDatosCliente.vue` con funcionalidad de búsqueda (70 líneas)
  - Nuevo método en `catalogoService.ts` para buscar clientes (25 líneas)
  - Actualización de store `citas.ts` con búsqueda de clientes
  - Configuración de `wasenderapi.php` actualizada
  - Variables de entorno agregadas en `.env.example`

#### Commit `b50830b` - ENV (12:54)
- **Configuración de entorno:**
  - Actualización de `frontend/.env.example` con nuevas variables
  - Refactorización de `frontend/src/services/api.ts` para usar variables de entorno

#### Commit `223db01` - Separar estilos (12:39)
- **Refactorización de estilos:**
  - Separación de estilos CSS del componente `PasoDatosCliente.vue`
  - Nuevo archivo `PasoDatosCliente.css` (587 líneas)
  - Limpieza de componente Vue (eliminadas 591 líneas de estilos inline)

### Resumen de Cambios

**Total de archivos modificados:** ~30 archivos
**Líneas agregadas:** ~3,500+ líneas
**Líneas eliminadas:** ~1,500+ líneas

**Principales mejoras:**
1. ✅ Integración completa de Mercado Pago para pagos en línea
2. ✅ Sistema de anticipos para reservas
3. ✅ Búsqueda de clientes en el proceso de agendamiento
4. ✅ Mejoras en la versión web con configuración Apache
5. ✅ Refactorización de estilos CSS (separación de concerns)
6. ✅ Persistencia de datos en localStorage
7. ✅ Botón de cambio de tema
8. ✅ Correcciones de tipado TypeScript

**Para ver los cambios completos:**
```powershell
# Ver detalles de un commit específico
git show <hash>

# Ver diferencias entre commits
git diff <hash1> <hash2>

# Ver historial completo
git log --oneline -11
```
