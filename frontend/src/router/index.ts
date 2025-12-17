import { createRouter, createWebHistory } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes: [
    // =====================================================
    // RUTAS PÚBLICAS
    // =====================================================
    {
      path: '/',
      name: 'login',
      component: () => import('@/views/auth/LoginView.vue'),
      meta: { guest: true },
    },
    {
      path: '/home',
      name: 'home',
      component: () => import('@/views/HomeView.vue'),
    },

    // =====================================================
    // RUTAS DE CLIENTE
    // =====================================================
    {
      path: '/agendar',
      name: 'agendar',
      component: () => import('@/views/cliente/AgendarView.vue'),
      // Público para ver servicios, login requerido al confirmar
    },
    {
      path: '/mis-citas',
      name: 'mis-citas',
      component: () => import('@/views/cliente/MisCitasView.vue'),
      // Sin autenticación requerida - acceso público
    },
    {
      path: '/perfil',
      name: 'perfil-cliente',
      component: () => import('@/views/cliente/PerfilView.vue'),
      // Sin autenticación requerida - acceso público
    },

    // =====================================================
    // RUTAS DE EMPLEADO
    // =====================================================
    {
      path: '/empleado',
      name: 'empleado-home',
      redirect: '/empleado/calendario',
    },
    {
      path: '/empleado/calendario',
      name: 'empleado-calendario',
      component: () => import('@/views/empleado/CalendarioView.vue'),
      meta: { requiresAuth: true, tipo: 'empleado' },
    },
    {
      path: '/empleado/citas',
      name: 'empleado-citas',
      component: () => import('@/views/empleado/CitasEmpleadoView.vue'),
      meta: { requiresAuth: true, tipo: 'empleado' },
    },
    {
      path: '/empleado/perfil',
      name: 'empleado-perfil',
      component: () => import('@/views/empleado/PerfilEmpleadoView.vue'),
      meta: { requiresAuth: true, tipo: 'empleado' },
    },
    {
      path: '/empleado/scan-qr',
      name: 'empleado-scan-qr',
      component: () => import('@/views/empleado/ScanQrView.vue'),
      meta: { requiresAuth: true, tipo: 'empleado' },
    },

    // =====================================================
    // RUTAS DE ADMIN
    // =====================================================
    {
      path: '/admin',
      name: 'admin-dashboard',
      component: () => import('@/views/admin/DashboardView.vue'),
      meta: { requiresAuth: true, tipo: 'admin' },
    },
    {
      path: '/admin/citas',
      name: 'admin-citas',
      component: () => import('@/views/admin/CitasAdminView.vue'),
      meta: { requiresAuth: true, tipo: 'admin' },
    },
    {
      path: '/admin/clientes',
      name: 'admin-clientes',
      component: () => import('@/views/admin/ClientesView.vue'),
      meta: { requiresAuth: true, tipo: 'admin' },
    },
    {
      path: '/admin/empleados',
      name: 'admin-empleados',
      component: () => import('@/views/admin/EmpleadosView.vue'),
      meta: { requiresAuth: true, tipo: 'admin' },
    },
    {
      path: '/admin/servicios',
      name: 'admin-servicios',
      component: () => import('@/views/admin/ServiciosView.vue'),
      meta: { requiresAuth: true, tipo: 'admin' },
    },
    {
      path: '/admin/categorias',
      name: 'admin-categorias',
      component: () => import('@/views/admin/CategoriasView.vue'),
      meta: { requiresAuth: true, tipo: 'admin' },
    },
    {
      path: '/admin/promociones',
      name: 'admin-promociones',
      component: () => import('@/views/admin/PromocionesView.vue'),
      meta: { requiresAuth: true, tipo: 'admin' },
    },
    {
      path: '/admin/configuracion',
      name: 'admin-configuracion',
      component: () => import('@/views/admin/ConfiguracionView.vue'),
      meta: { requiresAuth: true, tipo: 'admin' },
    },
    {
      path: '/admin/mensajes',
      name: 'admin-mensajes',
      component: () => import('@/views/admin/MensajesView.vue'),
      meta: { requiresAuth: true, tipo: 'admin' },
    },
    {
      path: '/admin/scan-qr',
      name: 'admin-scan-qr',
      component: () => import('@/views/empleado/ScanQrView.vue'),
      meta: { requiresAuth: true, tipo: 'admin' },
    },
    // =====================================================
    // RUTAS DE ALMACÉN Y VENTAS
    // =====================================================
    {
      path: '/admin/productos',
      name: 'admin-productos',
      component: () => import('@/views/admin/ProductosView.vue'),
      meta: { requiresAuth: true, tipo: 'admin' },
    },
    {
      path: '/admin/categorias-productos',
      name: 'admin-categorias-productos',
      component: () => import('@/views/admin/CategoriasProductosView.vue'),
      meta: { requiresAuth: true, tipo: 'admin' },
    },
    {
      path: '/admin/inventario',
      name: 'admin-inventario',
      component: () => import('@/views/admin/InventarioView.vue'),
      meta: { requiresAuth: true, tipo: 'admin' },
    },
    {
      path: '/admin/ventas',
      name: 'admin-ventas',
      component: () => import('@/views/admin/VentasView.vue'),
      meta: { requiresAuth: true, tipo: 'admin' },
    },

    // =====================================================
    // RUTA PÚBLICA SCAN QR (redirect)
    // =====================================================
    {
      path: '/scan-qr/:token',
      name: 'scan-qr-redirect',
      component: () => import('@/views/ScanQrRedirectView.vue'),
    },

    // =====================================================
    // 404
    // =====================================================
    {
      path: '/:pathMatch(.*)*',
      name: 'not-found',
      component: () => import('@/views/NotFoundView.vue'),
    },
  ],
})

// Guard de navegación
router.beforeEach(async (to, from, next) => {
  const authStore = useAuthStore()

  // Rutas que requieren autenticación
  if (to.meta.requiresAuth) {
    if (!authStore.isAuthenticated) {
      // Redirigir a login (empleados/admin) o agendar (clientes)
      const loginRoute = to.meta.tipo === 'cliente' ? 'agendar' : 'login'
      return next({ name: loginRoute, query: { redirect: to.fullPath } })
    }

    // Verificar tipo de usuario
    const tipoRequerido = to.meta.tipo as string | undefined
    if (tipoRequerido) {
      const tipoUsuario = authStore.userType

      // Admin puede acceder a rutas de empleado
      if (tipoRequerido === 'empleado' && tipoUsuario === 'admin') {
        return next()
      }

      if (tipoUsuario !== tipoRequerido) {
        // Redirigir según tipo de usuario
        if (tipoUsuario === 'cliente') {
          return next({ name: 'agendar' })
        } else if (tipoUsuario === 'empleado') {
          return next({ name: 'empleado-calendario' })
        } else if (tipoUsuario === 'admin') {
          return next({ name: 'admin-dashboard' })
        }
      }
    }
  }

  // Rutas para invitados (login) - no redirigir automáticamente
  // El usuario puede querer cambiar de cuenta o iniciar sesión diferente
  
  next()
})

export default router
