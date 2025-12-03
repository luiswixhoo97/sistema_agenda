# BeautySpa - Sistema de Citas para Estética

Sistema híbrido web/móvil para gestión de citas de un negocio de estética, desarrollado con **Vue 3**, **Laravel**, y **Capacitor**.

## 🚀 Tecnologías

### Frontend
- **Vue 3** (Composition API)
- **TypeScript**
- **Pinia** (State Management)
- **Vue Router**
- **Capacitor** (Mobile)
- **Vite** (Build Tool)

### Backend
- **Laravel 11**
- **Laravel Sanctum** (Authentication)
- **MySQL/MariaDB**
- **Laravel Queues** (Notifications)

### Mobile
- **Capacitor** (iOS & Android)
- **@capacitor/camera**
- **@capacitor/push-notifications**
- **@capacitor/network**

## 📁 Estructura del Proyecto

```
movil/
├── backend/                    # Laravel API
│   ├── app/
│   │   ├── Http/Controllers/   # Controllers
│   │   ├── Models/             # Eloquent Models
│   │   ├── Services/           # Business Logic
│   │   └── Jobs/               # Queue Jobs
│   ├── database/migrations/    # Migraciones
│   └── routes/api.php          # API Routes
│
├── frontend/                   # Vue 3 + Capacitor
│   ├── src/
│   │   ├── views/              # Pages
│   │   ├── components/         # Components
│   │   ├── composables/        # Composition API
│   │   ├── services/           # API Services
│   │   ├── stores/             # Pinia Stores
│   │   └── types/              # TypeScript Types
│   └── capacitor.config.ts     # Capacitor Config
│
└── docs/                       # Documentación
    ├── MULTIPLES_CITAS_SERVICIOS.md  # Múltiples citas coordinadas
    └── SISTEMA_DISPONIBILIDAD.md    # Sistema de disponibilidad
```

## 🛠️ Instalación

### Requisitos
- Node.js >= 18
- PHP >= 8.2
- Composer
- MySQL/MariaDB
- Android Studio (para Android)
- Xcode (para iOS, solo macOS)

### Backend (Laravel)

```bash
cd backend

# Instalar dependencias
composer install

# Copiar archivo de entorno
cp .env.example .env

# Generar key
php artisan key:generate

# Configurar base de datos en .env
# DB_DATABASE=beautyspa
# DB_USERNAME=root
# DB_PASSWORD=

# Ejecutar migraciones
php artisan migrate

# Ejecutar seeders (datos de prueba)
php artisan db:seed

# Iniciar servidor
php artisan serve
```

### Frontend (Vue 3)

```bash
cd frontend

# Instalar dependencias
npm install

# Iniciar servidor de desarrollo
npm run dev
```

### Configuración de la API

Edita `frontend/src/services/api.ts` para configurar la URL de la API:

```typescript
const api = axios.create({
  baseURL: 'http://localhost:8000/api', // URL de tu API Laravel
});
```

## 📱 Compilar para Móvil

### Android

```bash
cd frontend

# Compilar el proyecto
npm run build

# Sincronizar con Capacitor
npx cap sync android

# Abrir en Android Studio
npx cap open android
```

### iOS (solo macOS)

```bash
cd frontend

# Compilar el proyecto
npm run build

# Sincronizar con Capacitor
npx cap sync ios

# Abrir en Xcode
npx cap open ios
```

### Live Reload (Desarrollo)

Para desarrollar con live reload en el dispositivo:

1. Obtén tu IP local (ej: `192.168.1.100`)
2. Edita `capacitor.config.ts`:

```typescript
server: {
  url: 'http://192.168.1.100:5173',
  cleartext: true,
}
```

3. Inicia el servidor de desarrollo: `npm run dev -- --host`
4. Sincroniza y ejecuta: `npx cap run android`

## 🔑 Autenticación

### Clientes
- Login con **OTP** (código por WhatsApp/SMS)
- Registro rápido con teléfono

### Empleados/Admin
- Login con **email/password**
- Roles: admin, empleado

## 📋 Funcionalidades

### Cliente
- ✅ Agendar citas
- ✅ **Agendar múltiples servicios con diferentes empleados** (ver [docs/MULTIPLES_CITAS_SERVICIOS.md](docs/MULTIPLES_CITAS_SERVICIOS.md))
- ✅ Ver mis citas
- ✅ Cancelar/modificar citas
- ✅ Reagendar citas
- ✅ Ver catálogo de servicios
- ✅ Recibir notificaciones

### Empleado
- ✅ Ver calendario de citas
- ✅ Cambiar estado de citas
- ✅ Subir fotos de citas
- ✅ Gestionar bloqueos de tiempo

### Admin
- ✅ Dashboard con estadísticas
- ✅ Gestión de servicios
- ✅ Gestión de empleados
- ✅ Gestión de clientes
- ✅ Promociones
- ✅ Configuración del sistema

## 🔔 Notificaciones

El sistema soporta:
- **WhatsApp** (via WhatsApp Business API)
- **Email** (SMTP)
- **Push Notifications** (FCM)

Configurar en `backend/.env`:

```env
# WhatsApp (Meta)
WHATSAPP_API_URL=https://graph.facebook.com/v18.0
WHATSAPP_PHONE_NUMBER_ID=your_phone_number_id
WHATSAPP_TOKEN=your_access_token

# Firebase (Push)
FIREBASE_SERVER_KEY=your_server_key
```

## 🗄️ Base de Datos

Para ejecutar las migraciones:

```bash
cd backend
php artisan migrate
```

Para resetear y sembrar datos de prueba:

```bash
php artisan migrate:fresh --seed
```

## 🚦 Queue Workers

Para procesar notificaciones en segundo plano:

```bash
# Desarrollo
php artisan queue:work

# Producción (con supervisor)
php artisan queue:work --daemon
```

## 📄 API Endpoints

### Públicos
- `POST /api/auth/login` - Login empleado/admin
- `POST /api/auth/cliente/otp/solicitar` - Solicitar OTP
- `POST /api/auth/cliente/otp/verificar` - Verificar OTP
- `GET /api/publico/servicios` - Catálogo de servicios
- `GET /api/publico/categorias` - Categorías
- `GET /api/publico/empleados` - Empleados activos

### Cliente (autenticado)
- `GET /api/cliente/citas` - Mis citas
- `POST /api/cliente/citas` - Agendar cita
- `GET /api/cliente/disponibilidad/slots` - Horarios disponibles

### Empleado (autenticado)
- `GET /api/empleado/calendario/dia` - Citas del día
- `PUT /api/empleado/citas/{id}/estado` - Cambiar estado

### Admin (autenticado)
- `GET /api/admin/dashboard` - Dashboard
- Recursos CRUD: servicios, empleados, clientes, promociones

## 🧪 Testing

```bash
# Backend
cd backend
php artisan test

# Frontend
cd frontend
npm run test
```

## 📝 Licencia

Este proyecto es privado y de uso exclusivo para [Nombre del Negocio].

---

Desarrollado con ❤️ usando Vue 3 + Laravel + Capacitor

