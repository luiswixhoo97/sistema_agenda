<script setup lang="ts">
import { ref, computed } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { useAppkit } from '@/composables/useAppkit'

const router = useRouter()
const route = useRoute()
const authStore = useAuthStore()
const { theme, toggleTheme } = useAppkit()

const menuAbierto = ref(false)

const toggleMenu = () => {
  menuAbierto.value = !menuAbierto.value
}

const cerrarMenu = () => {
  menuAbierto.value = false
}

const navegarA = (ruta: string) => {
  router.push(ruta)
  cerrarMenu()
}

const cerrarSesion = async () => {
  await authStore.logout()
  cerrarMenu()
  router.push('/login-cliente')
}

// Menú según tipo de usuario
const menuItems = computed(() => {
  if (authStore.userType === 'cliente') {
    return [
      { icon: 'fa-calendar-plus', label: 'Agendar Cita', ruta: '/agendar' },
      { icon: 'fa-calendar-check', label: 'Mis Citas', ruta: '/mis-citas' },
      { icon: 'fa-user', label: 'Mi Perfil', ruta: '/perfil' },
    ]
  } else if (authStore.userType === 'empleado') {
    return [
      { icon: 'fa-calendar-alt', label: 'Calendario', ruta: '/empleado/calendario' },
      { icon: 'fa-list-alt', label: 'Mis Citas', ruta: '/empleado/citas' },
      { icon: 'fa-user-circle', label: 'Mi Perfil', ruta: '/empleado/perfil' },
    ]
  } else if (authStore.userType === 'admin') {
    return [
      { icon: 'fa-chart-line', label: 'Dashboard', ruta: '/admin' },
      { icon: 'fa-calendar', label: 'Citas', ruta: '/admin/citas' },
      { icon: 'fa-cut', label: 'Servicios', ruta: '/admin/servicios' },
      { icon: 'fa-users', label: 'Empleados', ruta: '/admin/empleados' },
      { icon: 'fa-user-friends', label: 'Clientes', ruta: '/admin/clientes' },
      { icon: 'fa-gift', label: 'Promociones', ruta: '/admin/promociones' },
      { icon: 'fa-cog', label: 'Configuración', ruta: '/admin/configuracion' },
    ]
  }
  // Menú público
  return [
    { icon: 'fa-calendar-plus', label: 'Agendar Cita', ruta: '/agendar' },
    { icon: 'fa-sign-in-alt', label: 'Acceso Personal', ruta: '/login' },
  ]
})

const tituloActual = computed(() => {
  const titulos: Record<string, string> = {
    '/agendar': 'Agendar Cita',
    '/mis-citas': 'Mis Citas',
    '/perfil': 'Mi Perfil',
    '/empleado/calendario': 'Calendario',
    '/empleado/citas': 'Mis Citas',
    '/admin': 'Dashboard',
    '/admin/citas': 'Citas',
    '/admin/servicios': 'Servicios',
    '/admin/empleados': 'Empleados',
    '/admin/clientes': 'Clientes',
    '/admin/promociones': 'Promociones',
    '/admin/configuracion': 'Configuración',
  }
  return titulos[route.path] || 'BeautySpa'
})
</script>

<template>
  <!-- Header -->
  <header class="app-header">
    <div class="header-brand">
      <span class="brand-icon">✨</span>
      <span class="brand-text">{{ tituloActual }}</span>
    </div>
    <button class="menu-btn" @click="toggleMenu" aria-label="Menú">
      <span class="hamburger">
        <span></span>
        <span></span>
        <span></span>
      </span>
    </button>
  </header>

  <!-- Overlay con blur -->
  <Transition name="fade">
    <div 
      v-if="menuAbierto"
      class="menu-overlay"
      @click="cerrarMenu"
    ></div>
  </Transition>

  <!-- Sidebar Menu -->
  <Transition name="slide">
    <aside v-if="menuAbierto" class="sidebar">
      <!-- Header del sidebar -->
      <div class="sidebar-header">
        <div class="sidebar-brand">
          <span class="brand-logo">💅</span>
          <div class="brand-info">
            <h2>BeautySpa</h2>
            <span>Tu belleza, nuestra pasión</span>
          </div>
        </div>
        <button class="close-btn" @click="cerrarMenu">
          <i class="fa fa-times"></i>
        </button>
      </div>

      <!-- User Card -->
      <div class="user-card">
        <div class="user-avatar">
          <span>{{ authStore.userName?.charAt(0)?.toUpperCase() || '👤' }}</span>
        </div>
        <div class="user-details">
          <h4>{{ authStore.userName || 'Invitado' }}</h4>
          <span class="user-role">{{ authStore.userType || 'Visitante' }}</span>
        </div>
        <div class="user-status">
          <span class="status-dot"></span>
        </div>
      </div>

      <!-- Navigation -->
      <nav class="sidebar-nav">
        <div class="nav-section">
          <span class="nav-section-title">Menú Principal</span>
          <a 
            v-for="item in menuItems" 
            :key="item.ruta"
            href="#"
            class="nav-link"
            :class="{ 'active': route.path === item.ruta }"
            @click.prevent="navegarA(item.ruta)"
          >
            <span class="nav-icon">
              <i :class="['fa', item.icon]"></i>
            </span>
            <span class="nav-text">{{ item.label }}</span>
            <span class="nav-arrow">
              <i class="fa fa-chevron-right"></i>
            </span>
          </a>
        </div>
      </nav>

      <!-- Footer -->
      <div class="sidebar-footer">
        <!-- Theme Toggle -->
        <button class="footer-btn theme-btn" @click="toggleTheme">
          <span class="btn-icon">
            <i :class="['fa', theme === 'light' ? 'fa-moon' : 'fa-sun']"></i>
          </span>
          <span class="btn-text">{{ theme === 'light' ? 'Modo Oscuro' : 'Modo Claro' }}</span>
        </button>

        <!-- Logout -->
        <button 
          v-if="authStore.isAuthenticated" 
          class="footer-btn logout-btn"
          @click="cerrarSesion"
        >
          <span class="btn-icon">
            <i class="fa fa-sign-out-alt"></i>
          </span>
          <span class="btn-text">Cerrar Sesión</span>
        </button>

        <!-- Version -->
        <div class="app-version">
          <span>BeautySpa v1.0</span>
        </div>
      </div>
    </aside>
  </Transition>
</template>

<style scoped>
/* ===== HEADER ===== */
.app-header {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  height: 60px;
  background: linear-gradient(135deg, #ec407a 0%, #c2185b 100%);
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 0 16px;
  z-index: 100;
}

.header-brand {
  display: flex;
  align-items: center;
  gap: 10px;
}

.brand-icon {
  font-size: 22px;
}

.brand-text {
  color: white;
  font-size: 18px;
  font-weight: 700;
  letter-spacing: -0.3px;
}

.menu-btn {
  width: 44px;
  height: 44px;
  border-radius: 12px;
  background: rgba(255,255,255,0.15);
  backdrop-filter: blur(10px);
  border: 1px solid rgba(255,255,255,0.2);
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all 0.2s;
}

.menu-btn:hover {
  background: rgba(255,255,255,0.25);
  transform: scale(1.05);
}

.hamburger {
  display: flex;
  flex-direction: column;
  gap: 5px;
}

.hamburger span {
  display: block;
  width: 20px;
  height: 2px;
  background: white;
  border-radius: 2px;
  transition: all 0.3s;
}

.hamburger span:nth-child(2) {
  width: 14px;
}

/* ===== OVERLAY ===== */
.menu-overlay {
  position: fixed;
  inset: 0;
  background: rgba(0, 0, 0, 0.6);
  backdrop-filter: blur(4px);
  z-index: 200;
}

/* ===== SIDEBAR ===== */
.sidebar {
  position: fixed;
  top: 0;
  left: 0;
  width: 300px;
  max-width: 85vw;
  height: 100vh;
  background: var(--color-card, #fff);
  z-index: 300;
  display: flex;
  flex-direction: column;
  box-shadow: 10px 0 40px rgba(0,0,0,0.2);
}

/* Sidebar Header */
.sidebar-header {
  padding: 20px;
  background: linear-gradient(135deg, #ec407a 0%, #c2185b 100%);
  position: relative;
}

.sidebar-brand {
  display: flex;
  align-items: center;
  gap: 12px;
}

.brand-logo {
  font-size: 36px;
}

.brand-info h2 {
  color: white;
  font-size: 20px;
  font-weight: 700;
  margin: 0;
}

.brand-info span {
  color: rgba(255,255,255,0.8);
  font-size: 11px;
}

.close-btn {
  position: absolute;
  top: 16px;
  right: 16px;
  width: 36px;
  height: 36px;
  border-radius: 50%;
  background: rgba(255,255,255,0.2);
  border: none;
  color: white;
  font-size: 16px;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all 0.2s;
}

.close-btn:hover {
  background: rgba(255,255,255,0.3);
  transform: rotate(90deg);
}

/* User Card */
.user-card {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 16px 20px;
  margin: 16px;
  background: linear-gradient(135deg, rgba(236,64,122,0.08), rgba(236,64,122,0.15));
  border-radius: 16px;
  border: 1px solid rgba(236,64,122,0.2);
}

.user-avatar {
  width: 48px;
  height: 48px;
  border-radius: 14px;
  background: linear-gradient(135deg, #ec407a, #c2185b);
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 20px;
  color: white;
  font-weight: 700;
}

.user-details {
  flex: 1;
}

.user-details h4 {
  margin: 0;
  font-size: 15px;
  font-weight: 600;
  color: var(--color-text);
}

.user-role {
  font-size: 12px;
  color: #ec407a;
  text-transform: capitalize;
  font-weight: 500;
}

.user-status {
  display: flex;
  align-items: center;
}

.status-dot {
  width: 10px;
  height: 10px;
  border-radius: 50%;
  background: #4caf50;
  box-shadow: 0 0 0 3px rgba(76,175,80,0.2);
}

/* Navigation */
.sidebar-nav {
  flex: 1;
  overflow-y: auto;
  padding: 0 12px;
}

.nav-section {
  margin-bottom: 16px;
}

.nav-section-title {
  display: block;
  padding: 12px 12px 8px;
  font-size: 11px;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 1px;
  color: var(--color-text-secondary);
}

.nav-link {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 14px 12px;
  margin-bottom: 4px;
  border-radius: 12px;
  color: var(--color-text);
  text-decoration: none;
  transition: all 0.2s;
}

.nav-link:hover {
  background: rgba(236,64,122,0.08);
}

.nav-link.active {
  background: linear-gradient(135deg, rgba(236,64,122,0.12), rgba(236,64,122,0.18));
}

.nav-icon {
  width: 40px;
  height: 40px;
  border-radius: 12px;
  background: rgba(0,0,0,0.04);
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 16px;
  color: var(--color-text-secondary);
  transition: all 0.2s;
}

.theme-dark .nav-icon {
  background: rgba(255,255,255,0.08);
}

.nav-link:hover .nav-icon,
.nav-link.active .nav-icon {
  background: linear-gradient(135deg, #ec407a, #c2185b);
  color: white;
}

.nav-text {
  flex: 1;
  font-size: 14px;
  font-weight: 500;
}

.nav-link.active .nav-text {
  color: #ec407a;
  font-weight: 600;
}

.nav-arrow {
  font-size: 12px;
  color: #ccc;
  opacity: 0;
  transform: translateX(-5px);
  transition: all 0.2s;
}

.nav-link:hover .nav-arrow,
.nav-link.active .nav-arrow {
  opacity: 1;
  transform: translateX(0);
  color: #ec407a;
}

/* Sidebar Footer */
.sidebar-footer {
  padding: 16px;
  border-top: 1px solid var(--color-border);
}

.footer-btn {
  display: flex;
  align-items: center;
  gap: 12px;
  width: 100%;
  padding: 12px;
  margin-bottom: 8px;
  border: none;
  border-radius: 12px;
  background: rgba(0,0,0,0.04);
  cursor: pointer;
  transition: all 0.2s;
}

.theme-dark .footer-btn {
  background: rgba(255,255,255,0.08);
}

.footer-btn:hover {
  background: rgba(0,0,0,0.08);
}

.theme-dark .footer-btn:hover {
  background: rgba(255,255,255,0.12);
}

.btn-icon {
  width: 36px;
  height: 36px;
  border-radius: 10px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 14px;
}

.theme-btn .btn-icon {
  background: linear-gradient(135deg, #6366f1, #4f46e5);
  color: white;
}

.logout-btn .btn-icon {
  background: linear-gradient(135deg, #ef4444, #dc2626);
  color: white;
}

.btn-text {
  font-size: 14px;
  font-weight: 500;
  color: var(--color-text);
}

.logout-btn .btn-text {
  color: #ef4444;
}

.app-version {
  text-align: center;
  padding: 12px 0 0;
  font-size: 11px;
  color: var(--color-text-secondary);
  opacity: 0.6;
}

/* ===== TRANSITIONS ===== */
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.3s ease;
}

.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}

.slide-enter-active,
.slide-leave-active {
  transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.slide-enter-from,
.slide-leave-to {
  transform: translateX(-100%);
}
</style>
