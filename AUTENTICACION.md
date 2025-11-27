# Sistema de Autenticación y Autorización

## 📋 Descripción General

El sistema tiene **dos tipos de usuarios** con flujos de autenticación diferentes:

1. **Clientes**: Autenticación por teléfono + OTP (sin contraseña)
2. **Empleados/Admin**: Autenticación por email + contraseña

---

## 👥 Tipos de Usuarios

### Clientes (App Móvil)
- **No tienen usuario en tabla `users`**
- Se identifican por **teléfono**
- Login con **OTP** (código de un solo uso)
- Registro simple (solo datos básicos)

### Empleados
- Tienen usuario en tabla `users`
- Login con **email + contraseña**
- Rol: `empleado`
- Acceso a sus citas y funcionalidades limitadas

### Administradores
- Tienen usuario en tabla `users`
- Login con **email + contraseña**
- Rol: `admin`
- Acceso completo al sistema

---

## 🔐 Flujo de Autenticación - Clientes

### Registro de Cliente Nuevo

```
1. Cliente ingresa teléfono
2. Sistema verifica si existe en tabla 'clientes'
3. Si NO existe:
   a. Mostrar formulario de registro
   b. Cliente ingresa: nombre, email (opcional), fecha_nacimiento (opcional)
   c. Sistema envía OTP al teléfono
   d. Cliente ingresa OTP
   e. Sistema crea registro en 'clientes'
   f. Sistema genera token de sesión
   g. Cliente autenticado
```

### Login de Cliente Existente

```
1. Cliente ingresa teléfono
2. Sistema verifica si existe en tabla 'clientes'
3. Si existe:
   a. Sistema envía OTP al teléfono
   b. Cliente ingresa OTP
   c. Sistema valida OTP
   d. Sistema genera token de sesión
   e. Cliente autenticado
```

### Diagrama de Flujo Cliente

```
┌─────────────────┐
│ Ingresar        │
│ Teléfono        │
└────────┬────────┘
         │
         ▼
┌─────────────────┐
│ ¿Cliente        │
│ existe?         │
└────────┬────────┘
         │
    ┌────┴────┐
    │         │
   NO        SÍ
    │         │
    ▼         ▼
┌───────┐  ┌───────┐
│Mostrar│  │Enviar │
│Form   │  │OTP    │
│Registro│ │       │
└───┬───┘  └───┬───┘
    │          │
    ▼          │
┌───────┐      │
│Guardar│      │
│Datos  │      │
└───┬───┘      │
    │          │
    ▼          │
┌───────┐      │
│Enviar │◄─────┘
│OTP    │
└───┬───┘
    │
    ▼
┌─────────────────┐
│ Ingresar OTP    │
└────────┬────────┘
         │
         ▼
┌─────────────────┐
│ ¿OTP válido?    │
└────────┬────────┘
         │
    ┌────┴────┐
    │         │
   NO        SÍ
    │         │
    ▼         ▼
┌───────┐  ┌───────┐
│Error  │  │Generar│
│Reinten│  │Token  │
│tar    │  │       │
└───────┘  └───┬───┘
               │
               ▼
         ┌───────────┐
         │ AUTENTICADO│
         └───────────┘
```

---

## 🔐 Flujo de Autenticación - Empleados/Admin

### Login Empleado/Admin

```
1. Usuario ingresa email y contraseña
2. Sistema valida credenciales en tabla 'users'
3. Si válido:
   a. Sistema genera token de sesión (Sanctum)
   b. Usuario autenticado
4. Si inválido:
   a. Mostrar error
   b. Incrementar contador de intentos fallidos
   c. Bloquear después de X intentos
```

### Diagrama de Flujo Empleado/Admin

```
┌─────────────────┐
│ Ingresar        │
│ Email + Pass    │
└────────┬────────┘
         │
         ▼
┌─────────────────┐
│ ¿Credenciales   │
│ válidas?        │
└────────┬────────┘
         │
    ┌────┴────┐
    │         │
   NO        SÍ
    │         │
    ▼         ▼
┌───────┐  ┌───────┐
│Error  │  │Generar│
│+1     │  │Token  │
│intento│  │       │
└───┬───┘  └───┬───┘
    │          │
    ▼          ▼
┌───────┐  ┌───────────┐
│¿Bloq? │  │AUTENTICADO│
└───┬───┘  └───────────┘
    │
   SÍ
    │
    ▼
┌───────┐
│Cuenta │
│Bloq.  │
└───────┘
```

---

## 📱 Sistema OTP para Clientes

### ¿Qué es OTP?
**One-Time Password** - Código de un solo uso enviado al teléfono.

### Características del OTP
- **Longitud**: 6 dígitos
- **Validez**: 5 minutos
- **Intentos máximos**: 3
- **Reenvío**: Después de 60 segundos

### Canales de Envío
1. **WhatsApp** (preferido, más confiable)
2. **SMS** (fallback)

### Tabla para OTP

```sql
CREATE TABLE otp_codes (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    telefono VARCHAR(20) NOT NULL,
    codigo VARCHAR(10) NOT NULL,
    intentos TINYINT DEFAULT 0,
    verificado TINYINT(1) DEFAULT 0,
    expira_at TIMESTAMP NOT NULL,
    created_at TIMESTAMP NULL,
    INDEX idx_otp_telefono (telefono),
    INDEX idx_otp_expira (expira_at)
);
```

### Flujo de Generación OTP

```
1. Generar código aleatorio de 6 dígitos
2. Guardar en tabla 'otp_codes':
   - telefono
   - codigo (hasheado)
   - expira_at = now() + 5 minutos
3. Enviar código por WhatsApp/SMS
4. Retornar éxito
```

### Flujo de Validación OTP

```
1. Recibir telefono + codigo
2. Buscar OTP no expirado para ese teléfono
3. Verificar:
   a. OTP existe
   b. No ha expirado
   c. Intentos < 3
   d. Código coincide
4. Si válido:
   a. Marcar como verificado
   b. Generar token de sesión
5. Si inválido:
   a. Incrementar intentos
   b. Retornar error
```

### Seguridad OTP

```
- Rate limiting: máximo 5 OTP por hora por teléfono
- Códigos hasheados en BD (opcional)
- Limpiar OTPs expirados periódicamente
- No revelar si teléfono existe o no
- Delay artificial para evitar timing attacks
```

---

## 🎫 Tokens de Sesión (Laravel Sanctum)

### Para Clientes
```
Token tipo: "cliente"
Abilities: ['cliente:*']
Expira: 30 días (renovable)
```

### Para Empleados
```
Token tipo: "empleado"
Abilities: ['empleado:*']
Expira: 8 horas (jornada laboral)
```

### Para Admins
```
Token tipo: "admin"
Abilities: ['admin:*', 'empleado:*']
Expira: 8 horas
```

### Estructura del Token

```json
{
  "token": "1|abc123...",
  "tipo": "cliente",
  "expira_at": "2025-12-27T10:00:00Z",
  "user": {
    "id": 1,
    "nombre": "Juan",
    "telefono": "+521234567890"
  }
}
```

---

## 🛡️ Autorización (Permisos)

### Roles

| Rol | Descripción |
|-----|-------------|
| admin | Acceso completo |
| empleado | Acceso limitado a sus funcionalidades |

### Permisos por Recurso

#### Citas
| Acción | Cliente | Empleado | Admin |
|--------|---------|----------|-------|
| Ver propias | ✅ | ✅ | ✅ |
| Ver todas | ❌ | ❌ | ✅ |
| Ver del día (empleado) | ❌ | ✅ | ✅ |
| Crear | ✅ | ✅ | ✅ |
| Modificar propia | ✅* | ❌ | ✅ |
| Modificar cualquiera | ❌ | ❌ | ✅ |
| Cancelar propia | ✅* | ❌ | ✅ |
| Cambiar estado | ❌ | ✅** | ✅ |

*Con restricciones de tiempo
**Solo citas asignadas

#### Servicios
| Acción | Cliente | Empleado | Admin |
|--------|---------|----------|-------|
| Ver | ✅ | ✅ | ✅ |
| Crear | ❌ | ❌ | ✅ |
| Modificar | ❌ | ❌ | ✅ |
| Eliminar | ❌ | ❌ | ✅ |

#### Empleados
| Acción | Cliente | Empleado | Admin |
|--------|---------|----------|-------|
| Ver públicos | ✅ | ✅ | ✅ |
| Ver todos | ❌ | ❌ | ✅ |
| Crear | ❌ | ❌ | ✅ |
| Modificar propio | ❌ | ✅* | ✅ |
| Modificar cualquiera | ❌ | ❌ | ✅ |

*Solo ciertos campos (bio, foto)

#### Clientes
| Acción | Cliente | Empleado | Admin |
|--------|---------|----------|-------|
| Ver propio | ✅ | ❌ | ✅ |
| Ver todos | ❌ | ✅* | ✅ |
| Crear | ✅ | ✅ | ✅ |
| Modificar propio | ✅ | ❌ | ✅ |
| Modificar cualquiera | ❌ | ❌ | ✅ |

*Solo información básica

#### Configuración
| Acción | Cliente | Empleado | Admin |
|--------|---------|----------|-------|
| Ver | ❌ | ❌ | ✅ |
| Modificar | ❌ | ❌ | ✅ |

#### Promociones
| Acción | Cliente | Empleado | Admin |
|--------|---------|----------|-------|
| Ver activas | ✅ | ✅ | ✅ |
| Ver todas | ❌ | ❌ | ✅ |
| Crear | ❌ | ❌ | ✅ |
| Modificar | ❌ | ❌ | ✅ |

#### Horarios (Empleados)
| Acción | Cliente | Empleado | Admin |
|--------|---------|----------|-------|
| Ver propios | ❌ | ✅ | ✅ |
| Ver todos | ❌ | ❌ | ✅ |
| Modificar propios | ❌ | ✅* | ✅ |
| Modificar cualquiera | ❌ | ❌ | ✅ |

*Requiere aprobación de admin (opcional)

#### Bloqueos
| Acción | Cliente | Empleado | Admin |
|--------|---------|----------|-------|
| Ver propios | ❌ | ✅ | ✅ |
| Ver todos | ❌ | ❌ | ✅ |
| Crear propios | ❌ | ✅ | ✅ |
| Crear para otros | ❌ | ❌ | ✅ |

#### Fotos de Citas
| Acción | Cliente | Empleado | Admin |
|--------|---------|----------|-------|
| Ver propias | ✅ | ✅* | ✅ |
| Subir | ❌ | ✅* | ✅ |
| Eliminar | ❌ | ❌ | ✅ |

*Solo de sus citas

#### Reportes
| Acción | Cliente | Empleado | Admin |
|--------|---------|----------|-------|
| Ver propios | ✅* | ❌ | ✅ |
| Ver de empleado | ❌ | ✅* | ✅ |
| Ver generales | ❌ | ❌ | ✅ |

*Historial de citas

---

## 🔒 Middleware de Autorización

### Middleware para Tipo de Usuario

```
// Rutas solo para clientes
Route::middleware(['auth:sanctum', 'tipo:cliente'])->group(...)

// Rutas solo para empleados
Route::middleware(['auth:sanctum', 'tipo:empleado'])->group(...)

// Rutas solo para admin
Route::middleware(['auth:sanctum', 'tipo:admin'])->group(...)

// Rutas para empleados Y admin
Route::middleware(['auth:sanctum', 'tipo:empleado,admin'])->group(...)
```

### Middleware de Rate Limiting

```
// Rutas de login
Route::middleware(['throttle:login'])->group(...)
  → máximo 5 intentos por minuto

// Rutas de OTP
Route::middleware(['throttle:otp'])->group(...)
  → máximo 5 por hora

// Rutas generales autenticadas
Route::middleware(['throttle:api'])->group(...)
  → máximo 60 por minuto
```

---

## 📱 API Endpoints de Autenticación

### Cliente: Solicitar OTP

```
POST /api/auth/cliente/otp/solicitar
Body:
{
  "telefono": "+521234567890"
}

Response (éxito):
{
  "success": true,
  "mensaje": "Código enviado",
  "expira_en": 300,
  "reenviar_en": 60
}

Response (nuevo cliente):
{
  "success": true,
  "es_nuevo": true,
  "mensaje": "Complete su registro"
}
```

### Cliente: Verificar OTP

```
POST /api/auth/cliente/otp/verificar
Body:
{
  "telefono": "+521234567890",
  "codigo": "123456"
}

Response (éxito):
{
  "success": true,
  "token": "1|abc123...",
  "cliente": {
    "id": 1,
    "nombre": "Juan Pérez",
    "telefono": "+521234567890",
    "email": "juan@email.com"
  }
}

Response (error):
{
  "success": false,
  "error": "Código inválido",
  "intentos_restantes": 2
}
```

### Cliente: Registro

```
POST /api/auth/cliente/registrar
Body:
{
  "telefono": "+521234567890",
  "codigo": "123456",
  "nombre": "Juan Pérez",
  "email": "juan@email.com",
  "fecha_nacimiento": "1990-01-15"
}

Response:
{
  "success": true,
  "token": "1|abc123...",
  "cliente": { ... }
}
```

### Empleado/Admin: Login

```
POST /api/auth/login
Body:
{
  "email": "empleado@negocio.com",
  "password": "contraseña123"
}

Response (éxito):
{
  "success": true,
  "token": "2|xyz789...",
  "user": {
    "id": 1,
    "nombre": "María García",
    "email": "empleado@negocio.com",
    "role": "empleado"
  },
  "empleado": {
    "id": 1,
    "foto": "/storage/empleados/maria.jpg",
    "bio": "Especialista en colorimetría"
  }
}
```

### Logout

```
POST /api/auth/logout
Headers:
  Authorization: Bearer {token}

Response:
{
  "success": true,
  "mensaje": "Sesión cerrada"
}
```

### Verificar Token (Whoami)

```
GET /api/auth/me
Headers:
  Authorization: Bearer {token}

Response:
{
  "autenticado": true,
  "tipo": "cliente",
  "usuario": { ... }
}
```

### Refresh Token

```
POST /api/auth/refresh
Headers:
  Authorization: Bearer {token}

Response:
{
  "success": true,
  "token": "3|nuevo_token...",
  "expira_at": "2025-12-27T10:00:00Z"
}
```

---

## 🔐 Seguridad Adicional

### Protección contra Ataques

#### Fuerza Bruta
- Rate limiting estricto
- Bloqueo temporal después de X intentos
- CAPTCHA después de Y intentos (opcional)
- Delay artificial en respuestas de error

#### Session Hijacking
- Tokens con fingerprint del dispositivo
- Invalidar tokens en logout
- Tokens de corta duración
- Refresh tokens separados

#### XSS/CSRF
- Tokens almacenados de forma segura (HttpOnly cookies o SecureStorage)
- CSRF token en formularios web
- Content-Security-Policy headers

### Logs de Seguridad

```
Registrar:
- Intentos de login (exitosos y fallidos)
- Cambios de contraseña
- Solicitudes de OTP
- Tokens generados/revocados
- Accesos desde nuevos dispositivos
```

---

## 📊 Tablas Adicionales Sugeridas

### Tabla de Sesiones de Cliente

```sql
CREATE TABLE cliente_sessions (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    cliente_id BIGINT UNSIGNED NOT NULL,
    token_id BIGINT UNSIGNED NOT NULL,
    device_info JSON NULL,
    ip_address VARCHAR(45),
    last_activity TIMESTAMP,
    created_at TIMESTAMP NULL,
    FOREIGN KEY (cliente_id) REFERENCES clientes(id),
    INDEX idx_sessions_cliente (cliente_id)
);
```

### Tabla de Intentos de Login

```sql
CREATE TABLE login_attempts (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    identificador VARCHAR(255) NOT NULL COMMENT 'email o teléfono',
    tipo ENUM('empleado', 'cliente') NOT NULL,
    ip_address VARCHAR(45),
    exitoso TINYINT(1) DEFAULT 0,
    user_agent TEXT,
    created_at TIMESTAMP NULL,
    INDEX idx_attempts_identificador (identificador),
    INDEX idx_attempts_ip (ip_address)
);
```

---

## ✅ Checklist de Implementación

### Autenticación Cliente
- [ ] Endpoint solicitar OTP
- [ ] Servicio de envío OTP (WhatsApp/SMS)
- [ ] Endpoint verificar OTP
- [ ] Endpoint registro cliente
- [ ] Generación de tokens Sanctum
- [ ] Rate limiting OTP

### Autenticación Empleado/Admin
- [ ] Endpoint login
- [ ] Validación de credenciales
- [ ] Generación de tokens
- [ ] Rate limiting login
- [ ] Bloqueo por intentos fallidos

### General
- [ ] Endpoint logout
- [ ] Endpoint whoami
- [ ] Endpoint refresh token
- [ ] Middleware de tipo de usuario
- [ ] Middleware de permisos
- [ ] Logs de seguridad

---

## 🎯 Siguiente Paso

Documentar la integración con APIs externas (WhatsApp, Email, Push).

