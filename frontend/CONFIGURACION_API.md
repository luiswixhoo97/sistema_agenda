# Configuración Detallada para Consumir APIs - Frontend

## 📋 Tabla de Contenidos
1. [Estructura de Carpetas](#estructura-de-carpetas)
2. [Configuración de la URL Base de la API](#configuración-de-la-url-base-de-la-api)
3. [Configuración de Axios](#configuración-de-axios)
4. [Estructura de Servicios](#estructura-de-servicios)
5. [Endpoints Disponibles](#endpoints-disponibles)
6. [Autenticación y Tokens](#autenticación-y-tokens)
7. [Variables de Entorno](#variables-de-entorno)
8. [Ejemplos de Uso](#ejemplos-de-uso)

---

## 📁 Estructura de Carpetas

```
frontend/
├── src/
│   ├── services/          # Servicios para consumir APIs
│   │   ├── api.ts        # Configuración base de Axios
│   │   ├── authService.ts
│   │   ├── catalogoService.ts
│   │   ├── citaService.ts
│   │   ├── disponibilidadService.ts
│   │   └── adminService.ts
│   ├── stores/           # Pinia stores (gestión de estado)
│   │   ├── auth.ts
│   │   └── citas.ts
│   ├── types/           # Tipos TypeScript
│   │   └── index.ts
│   ├── composables/     # Composables de Vue
│   ├── components/      # Componentes Vue
│   ├── views/           # Vistas/Páginas
│   ├── router/          # Configuración de rutas
│   │   └── index.ts
│   └── main.ts          # Punto de entrada
├── package.json
├── vite.config.ts
└── capacitor.config.ts
```

---

## 🔧 Configuración de la URL Base de la API

### Ubicación del Archivo
`src/services/api.ts`

### Configuración Actual

El archivo `api.ts` detecta automáticamente la URL del API según el entorno:

```typescript
const getApiUrl = () => {
  // Si hay variable de entorno, usarla
  if (import.meta.env.VITE_API_URL) {
    return import.meta.env.VITE_API_URL
  }
  
  // URL del servidor de producción (por defecto)
  return 'https://salmon-eland-125157.hostingersite.com/backend/public/api'
  
  /* Para desarrollo local, descomenta esto:
  if (window.location.hostname === 'localhost' || window.location.hostname === '127.0.0.1') {
    return 'http://localhost:8000/api'
  }
  return `http://${window.location.hostname}:8000/api`
  */
}
```

### Opciones de Configuración

#### Opción 1: Usar Variable de Entorno (Recomendado)

1. Crear archivo `.env` en la raíz de `frontend/`:
```env
VITE_API_URL=http://localhost:8000/api
```

2. Para producción:
```env
VITE_API_URL=https://salmon-eland-125157.hostingersite.com/backend/public/api
```

3. Para desarrollo en red local (desde móvil):
```env
VITE_API_URL=http://192.168.1.100:8000/api
```
*(Reemplaza `192.168.1.100` con la IP de tu computadora en la red local)*

#### Opción 2: Modificar Directamente en `api.ts`

Para desarrollo local:
```typescript
const getApiUrl = () => {
  if (window.location.hostname === 'localhost' || window.location.hostname === '127.0.0.1') {
    return 'http://localhost:8000/api'
  }
  // Para acceso desde dispositivo móvil en la misma red
  return `http://192.168.1.100:8000/api`  // Cambia por tu IP local
}
```

Para producción:
```typescript
const getApiUrl = () => {
  return 'https://salmon-eland-125157.hostingersite.com/backend/public/api'
}
```

### Cómo Obtener tu IP Local (Windows)

1. Abre PowerShell o CMD
2. Ejecuta: `ipconfig`
3. Busca "Dirección IPv4" en "Adaptador de Ethernet" o "Adaptador de LAN inalámbrica"
4. Ejemplo: `192.168.1.100`

---

## ⚙️ Configuración de Axios

### Archivo: `src/services/api.ts`

```typescript
import axios from 'axios'
import type { AxiosInstance, AxiosError, InternalAxiosRequestConfig } from 'axios'

const API_URL = getApiUrl()

// Crear instancia de axios
const api: AxiosInstance = axios.create({
  baseURL: API_URL,
  headers: {
    'Content-Type': 'application/json',
    'Accept': 'application/json',
  },
  timeout: 30000,
  withCredentials: false,  // No enviar cookies (importante para apps móviles)
})
```

### Interceptores

#### 1. Interceptor de Request (Agregar Token)
```typescript
api.interceptors.request.use(
  (config: InternalAxiosRequestConfig) => {
    const token = localStorage.getItem('auth_token')
    if (token) {
      config.headers.Authorization = `Bearer ${token}`
    }
    return config
  },
  (error) => Promise.reject(error)
)
```

#### 2. Interceptor de Response (Manejar Errores 401)
```typescript
api.interceptors.response.use(
  (response) => response,
  (error: AxiosError) => {
    // Si es 401, limpiar sesión (token inválido/expirado)
    if (error.response?.status === 401) {
      const url = error.config?.url || ''
      
      // No limpiar si es una ruta de login/auth
      const isAuthRoute = url.includes('/auth/') || url.includes('/publico/')
      
      if (!isAuthRoute) {
        localStorage.removeItem('auth_token')
        localStorage.removeItem('user')
        localStorage.removeItem('user_type')
        
        // Redirigir solo si no estamos en login
        if (!window.location.pathname.includes('login')) {
          window.location.href = '/'
        }
      }
    }

    return Promise.reject(error)
  }
)
```

---

## 📦 Estructura de Servicios

### 1. `api.ts` - Configuración Base
- Instancia de Axios configurada
- Interceptores para tokens y errores
- Tipos comunes de respuesta

### 2. `authService.ts` - Autenticación
**Endpoints:**
- `POST /auth/login` - Login empleados/admin
- `POST /auth/cliente/otp/solicitar` - Solicitar OTP cliente
- `POST /auth/cliente/otp/verificar` - Verificar OTP cliente
- `POST /auth/cliente/registrar` - Registrar cliente
- `POST /auth/logout` - Cerrar sesión
- `GET /auth/me` - Obtener usuario actual
- `POST /auth/refresh` - Refrescar token

**Funciones principales:**
```typescript
authService.login(email, password)
authService.solicitarOtp(telefono)
authService.verificarOtp(telefono, codigo)
authService.registrarCliente(data)
authService.logout()
authService.me()
authService.getToken()
authService.getUser()
authService.getUserType()
authService.isAuthenticated()
```

### 3. `catalogoService.ts` - Catálogo Público
**Endpoints:**
- `GET /publico/categorias` - Obtener categorías
- `GET /publico/servicios` - Obtener servicios
- `GET /publico/empleados` - Obtener empleados
- `GET /publico/promociones` - Obtener promociones

**Funciones principales:**
```typescript
catalogoService.obtenerCategorias()
catalogoService.obtenerServicios(categoriaId?)
catalogoService.obtenerEmpleados(servicioId?)
catalogoService.obtenerPromociones()
```

### 4. `citaService.ts` - Gestión de Citas
**Endpoints públicos:**
- `POST /publico/agendar/otp` - Enviar OTP para agendar
- `POST /publico/agendar` - Agendar cita pública
- `POST /publico/agendar/multiples` - Agendar múltiples citas

**Endpoints cliente (autenticado):**
- `GET /cliente/citas` - Mis citas
- `GET /cliente/citas/{id}` - Ver mi cita
- `POST /cliente/citas` - Agendar cita
- `PUT /cliente/citas/{id}` - Modificar cita
- `POST /cliente/citas/{id}/cancelar` - Cancelar cita
- `POST /cliente/citas/{id}/calificar` - Calificar cita

**Endpoints empleado (autenticado):**
- `GET /empleado/calendario/dia` - Citas del día
- `GET /empleado/calendario/semana` - Citas de la semana
- `GET /empleado/citas` - Mis citas
- `PUT /empleado/citas/{id}/estado` - Cambiar estado
- `POST /empleado/citas/{id}/fotos` - Subir foto

**Endpoints admin (autenticado):**
- `GET /admin/citas` - Listar todas las citas
- `GET /admin/citas/{id}` - Ver cita
- `POST /admin/citas` - Crear cita
- `PUT /admin/citas/{id}` - Actualizar cita
- `DELETE /admin/citas/{id}` - Eliminar cita
- `GET /admin/citas-calendario` - Calendario de citas

### 5. `disponibilidadService.ts` - Disponibilidad
**Endpoints públicos:**
- `GET /publico/disponibilidad/dias` - Días disponibles
- `GET /publico/disponibilidad/slots` - Slots disponibles
- `POST /publico/disponibilidad/slots-coordinados` - Slots coordinados
- `POST /publico/disponibilidad/calcular` - Calcular servicio
- `POST /publico/disponibilidad/reservar-temporal` - Reservar temporal
- `POST /publico/disponibilidad/liberar-temporal` - Liberar temporal

**Endpoints cliente (autenticado):**
- `GET /cliente/disponibilidad/dias` - Días disponibles
- `GET /cliente/disponibilidad/slots` - Slots disponibles
- `POST /cliente/disponibilidad/verificar` - Verificar disponibilidad
- `GET /cliente/disponibilidad/empleados` - Empleados disponibles

**Endpoints empleado/admin:**
- `GET /empleado/disponibilidad/slots` - Slots (sin restricción anticipación)
- `GET /admin/disponibilidad/slots` - Slots (sin restricción anticipación)

### 6. `adminService.ts` - Administración
**Endpoints:**
- `GET /admin/dashboard` - Dashboard
- `GET /admin/servicios` - Listar servicios
- `POST /admin/servicios` - Crear servicio
- `PUT /admin/servicios/{id}` - Actualizar servicio
- `DELETE /admin/servicios/{id}` - Eliminar servicio
- `GET /admin/categorias` - Listar categorías
- `GET /admin/empleados` - Listar empleados
- `GET /admin/clientes` - Listar clientes
- `GET /admin/promociones` - Listar promociones
- `GET /admin/configuracion` - Obtener configuración
- `PUT /admin/configuracion` - Actualizar configuración
- `GET /admin/reportes/citas` - Reporte de citas
- `GET /admin/reportes/ingresos` - Reporte de ingresos

---

## 🔐 Autenticación y Tokens

### Flujo de Autenticación

#### Para Empleados/Admin:
```typescript
// 1. Login
const response = await authService.login(email, password)

// El token se guarda automáticamente en localStorage
// localStorage.setItem('auth_token', token)
// localStorage.setItem('user', JSON.stringify(user))
// localStorage.setItem('user_type', 'empleado' | 'admin')
```

#### Para Clientes (OTP):
```typescript
// 1. Solicitar OTP
await authService.solicitarOtp(telefono)

// 2. Verificar OTP
const response = await authService.verificarOtp(telefono, codigo)

// El token se guarda automáticamente si es exitoso
```

### Almacenamiento de Tokens

Los tokens se almacenan en `localStorage`:
- `auth_token` - Token JWT
- `user` - Datos del usuario (JSON stringificado)
- `user_type` - Tipo de usuario: 'cliente' | 'empleado' | 'admin'
- `empleado` - Datos adicionales del empleado (si aplica)

### Uso del Token

El token se envía automáticamente en todas las peticiones autenticadas mediante el interceptor de Axios:

```typescript
// Se agrega automáticamente:
headers: {
  'Authorization': 'Bearer {token}'
}
```

### Verificar Autenticación

```typescript
import authService from '@/services/authService'

// Verificar si está autenticado
if (authService.isAuthenticated()) {
  // Usuario autenticado
}

// Obtener tipo de usuario
const userType = authService.getUserType() // 'cliente' | 'empleado' | 'admin'

// Verificar tipo específico
if (authService.isCliente()) { }
if (authService.isEmpleado()) { }
if (authService.isAdmin()) { }

// Obtener usuario actual
const user = authService.getUser()
```

### Cerrar Sesión

```typescript
await authService.logout()
// Limpia automáticamente localStorage y hace POST a /auth/logout
```

---

## 🌍 Variables de Entorno

### Crear archivo `.env` en `frontend/`

```env
# URL del API
VITE_API_URL=http://localhost:8000/api

# Para producción:
# VITE_API_URL=https://salmon-eland-125157.hostingersite.com/backend/public/api

# Para desarrollo móvil (red local):
# VITE_API_URL=http://192.168.1.100:8000/api
```

### Acceder a Variables de Entorno

```typescript
// En cualquier archivo .ts o .vue
const apiUrl = import.meta.env.VITE_API_URL
```

**Importante:** Las variables deben comenzar con `VITE_` para ser accesibles en el frontend.

---

## 💡 Ejemplos de Uso

### Ejemplo 1: Obtener Catálogo de Servicios

```typescript
import catalogoService from '@/services/catalogoService'

// Obtener todas las categorías con servicios
const categorias = await catalogoService.obtenerCategorias()

// Obtener servicios de una categoría específica
const servicios = await catalogoService.obtenerServicios(categoriaId)

// Obtener empleados que ofrecen un servicio
const empleados = await catalogoService.obtenerEmpleados(servicioId)
```

### Ejemplo 2: Agendar una Cita (Público)

```typescript
import citaService from '@/services/citaService'
import disponibilidadService from '@/services/disponibilidadService'

// 1. Obtener días disponibles
const dias = await disponibilidadService.obtenerDiasDisponibles(
  empleadoId,
  mes,
  anio,
  [servicioId1, servicioId2]
)

// 2. Obtener slots disponibles para una fecha
const slots = await disponibilidadService.obtenerSlots(
  empleadoId,
  '2024-01-15',
  [servicioId1, servicioId2]
)

// 3. Enviar OTP
await citaService.enviarOtpAgendamiento(telefono)

// 4. Agendar cita
const resultado = await citaService.agendarPublico({
  cliente_nombre: 'Juan Pérez',
  cliente_telefono: '1234567890',
  cliente_email: 'juan@example.com',
  codigo_otp: '123456',
  empleado_id: empleadoId,
  servicios: [servicioId1, servicioId2],
  fecha_hora: '2024-01-15 10:00:00',
  notas: 'Notas opcionales'
})
```

### Ejemplo 3: Obtener Mis Citas (Cliente Autenticado)

```typescript
import citaService from '@/services/citaService'
import authService from '@/services/authService'

// Verificar autenticación
if (!authService.isCliente()) {
  // Redirigir a login
  return
}

// Obtener citas
const { citas, pagination } = await citaService.misCitas({
  estado: 'confirmada',
  desde: '2024-01-01',
  hasta: '2024-12-31',
  per_page: 10,
  page: 1
})

// Ver detalle de una cita
const cita = await citaService.verMiCita(citaId)

// Cancelar cita
await citaService.cancelar(citaId, 'Motivo de cancelación')
```

### Ejemplo 4: Gestión de Citas (Empleado)

```typescript
import citaService from '@/services/citaService'

// Obtener citas del día
const { fecha, citas } = await citaService.citasDelDia('2024-01-15')

// Obtener citas de la semana
const semana = await citaService.citasDeLaSemana('2024-01-15')

// Cambiar estado de una cita
await citaService.cambiarEstado(citaId, 'en_proceso')

// Subir foto a una cita
const file = // ... obtener archivo
await citaService.subirFoto(citaId, file, 'antes', 'Descripción opcional')
```

### Ejemplo 5: Administración (Admin)

```typescript
import adminService from '@/services/adminService'

// Obtener dashboard
const dashboard = await adminService.getDashboard()

// Listar servicios
const servicios = await adminService.getServicios()

// Crear servicio
await adminService.createServicio({
  nombre: 'Corte de Cabello',
  descripcion: 'Corte moderno',
  precio: 500,
  duracion: 30,
  categoria_id: 1
})

// Listar citas con filtros
const { data: citas, pagination } = await adminService.getCitas({
  estado: 'confirmada',
  empleado_id: 1,
  desde: '2024-01-01',
  hasta: '2024-12-31',
  per_page: 20,
  page: 1
})
```

### Ejemplo 6: Manejo de Errores

```typescript
import api from '@/services/api'

try {
  const response = await api.get('/cliente/citas')
  // Procesar respuesta
} catch (error) {
  if (error.response) {
    // Error de respuesta del servidor
    console.error('Status:', error.response.status)
    console.error('Data:', error.response.data)
    
    if (error.response.status === 401) {
      // Token inválido - el interceptor ya maneja esto
      // Redirigir a login
    } else if (error.response.status === 404) {
      // Recurso no encontrado
    } else if (error.response.status === 422) {
      // Error de validación
      const errors = error.response.data.errors
    }
  } else if (error.request) {
    // Error de red (sin respuesta)
    console.error('Error de red:', error.request)
  } else {
    // Error al configurar la petición
    console.error('Error:', error.message)
  }
}
```

---

## 📝 Notas Importantes

### 1. CORS
El backend debe tener CORS configurado para permitir peticiones desde el frontend. Verificar `backend/config/cors.php`.

### 2. Timeout
El timeout está configurado a 30 segundos. Para operaciones más largas, ajustar en `api.ts`.

### 3. FormData
Para subir archivos (fotos), usar `FormData`:
```typescript
const formData = new FormData()
formData.append('foto', file)
formData.append('tipo', 'antes')

await api.post('/empleado/citas/1/fotos', formData, {
  headers: { 'Content-Type': 'multipart/form-data' }
})
```

### 4. Parámetros de Query
Para endpoints con múltiples parámetros del mismo nombre (arrays):
```typescript
const params = new URLSearchParams()
params.append('servicios[]', '1')
params.append('servicios[]', '2')
await api.get(`/endpoint?${params}`)
```

### 5. Capacitor y URLs
En aplicaciones móviles con Capacitor, asegurarse de que:
- La URL del API sea accesible desde el dispositivo
- Si es desarrollo local, usar la IP de la red local, no `localhost`
- En producción, usar HTTPS

---

## 🔄 Resumen de Configuración Rápida

1. **Configurar URL del API:**
   - Crear `.env` con `VITE_API_URL`
   - O modificar `src/services/api.ts`

2. **Instalar dependencias:**
   ```bash
   npm install axios
   ```

3. **Usar servicios:**
   ```typescript
   import authService from '@/services/authService'
   import catalogoService from '@/services/catalogoService'
   import citaService from '@/services/citaService'
   ```

4. **Manejar autenticación:**
   ```typescript
   // Login
   await authService.login(email, password)
   
   // Verificar autenticación
   if (authService.isAuthenticated()) { }
   
   // Obtener usuario
   const user = authService.getUser()
   ```

5. **Hacer peticiones:**
   - Usar los servicios ya configurados
   - O usar directamente `api` de `@/services/api` para casos especiales

---

## 📚 Referencias

- **Backend API Routes:** `backend/routes/api.php`
- **Configuración Axios:** `frontend/src/services/api.ts`
- **Servicios:** `frontend/src/services/*.ts`
- **Tipos TypeScript:** `frontend/src/types/index.ts`

