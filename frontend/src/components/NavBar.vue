<script setup lang="ts">
import { ref, computed } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { useAppkit } from '@/composables/useAppkit'

const router = useRouter()
const route = useRoute()
const authStore = useAuthStore()
const { theme, toggleTheme } = useAppkit()

const mostrarMenuMas = ref(false)

const navegarA = (ruta: string) => {
  router.push(ruta)
  mostrarMenuMas.value = false
}

const cerrarSesion = async () => {
  await authStore.logout()
  mostrarMenuMas.value = false
  router.push('/login-cliente')
}

// Items principales del footer (máximo 5)
const footerItems = computed(() => {
  if (authStore.userType === 'cliente') {
    return [
      { icon: 'fa-calendar-plus', label: 'Agendar', ruta: '/agendar' },
      { icon: 'fa-calendar-check', label: 'Mis Citas', ruta: '/mis-citas' },
      { icon: 'fa-user', label: 'Perfil', ruta: '/perfil' },
    ]
  } else if (authStore.userType === 'empleado') {
    return [
      { icon: 'fa-calendar-alt', label: 'Calendario', ruta: '/empleado/calendario' },
      { icon: 'fa-list-alt', label: 'Citas', ruta: '/empleado/citas' },
      { icon: 'fa-qrcode', label: 'Escanear', ruta: '/empleado/scan-qr', isCenter: true },
      { icon: 'fa-user-circle', label: 'Perfil', ruta: '/empleado/perfil' },
      { icon: 'fa-ellipsis-h', label: 'Más', ruta: '#mas-empleado', isMas: true },
    ]
  } else if (authStore.userType === 'admin') {
    return [
      { icon: 'fa-chart-line', label: 'Dashboard', ruta: '/admin' },
      { icon: 'fa-calendar', label: 'Citas', ruta: '/admin/citas' },
      { icon: 'fa-qrcode', label: 'Escanear', ruta: '/admin/scan-qr', isCenter: true },
      { icon: 'fa-cut', label: 'Servicios', ruta: '/admin/servicios' },
      { icon: 'fa-ellipsis-h', label: 'Más', ruta: '#mas', isMas: true },
    ]
  }
  // Menú público
  return [
    { icon: 'fa-calendar-plus', label: 'Agendar', ruta: '/agendar' },
    { icon: 'fa-sign-in-alt', label: 'Acceso', ruta: '/login' },
  ]
})

// Items adicionales para el menú "Más" (admin y empleado)
const menuMasItems = computed(() => {
  if (authStore.userType === 'empleado') {
    return [
      { icon: 'fa-user-circle', label: 'Mi Perfil', ruta: '/empleado/perfil' },
    ]
  }
  // Admin
  return [
    { icon: 'fa-user-friends', label: 'Clientes', ruta: '/admin/clientes' },
    { icon: 'fa-users', label: 'Empleados', ruta: '/admin/empleados' },
    { icon: 'fa-folder', label: 'Categorías', ruta: '/admin/categorias' },
    { icon: 'fa-gift', label: 'Promociones', ruta: '/admin/promociones' },
    { icon: 'fa-comment-dots', label: 'Mensajes', ruta: '/admin/mensajes' },
    { icon: 'fa-cog', label: 'Configuración', ruta: '/admin/configuracion' },
  ]
})

const handleItemClick = (item: any) => {
  if (item.isMas) {
    mostrarMenuMas.value = !mostrarMenuMas.value
  } else {
    mostrarMenuMas.value = false
    navegarA(item.ruta)
  }
}

const isActive = (ruta: string) => {
  if (ruta === '#mas' || ruta === '#mas-empleado') {
    return menuMasItems.value.some(item => route.path === item.ruta)
  }
  return route.path === ruta
}
</script>

<template>
  <!-- Overlay para menú más -->
  <Transition name="fade">
    <div 
      v-if="mostrarMenuMas"
      class="menu-overlay"
      @click="mostrarMenuMas = false"
    ></div>
  </Transition>

  <!-- Menú "Más" popup -->
  <Transition name="slide-up">
    <div v-if="mostrarMenuMas" class="menu-mas">
      <div class="menu-mas-header">
        <span class="menu-mas-title">Más opciones</span>
        <button class="close-btn" @click="mostrarMenuMas = false">
          <i class="fa fa-times"></i>
        </button>
      </div>
      
      <div class="menu-mas-items">
        <a 
          v-for="item in menuMasItems" 
          :key="item.ruta"
          href="#"
          class="menu-mas-item"
          :class="{ active: route.path === item.ruta }"
          @click.prevent="navegarA(item.ruta)"
        >
          <span class="item-icon">
            <i :class="['fa', item.icon]"></i>
          </span>
          <span class="item-label">{{ item.label }}</span>
          <i class="fa fa-chevron-right item-arrow"></i>
        </a>
        
        <!-- Toggle Modo Oscuro -->
        <div class="menu-mas-item theme-toggle-item">
          <span class="item-icon theme-icon">
            <i :class="['fa', theme === 'light' ? 'fa-moon' : 'fa-sun']"></i>
          </span>
          <span class="item-label">Modo {{ theme === 'light' ? 'Oscuro' : 'Claro' }}</span>
          <label class="ios-toggle-switch">
            <input 
              type="checkbox" 
              :checked="theme === 'dark'"
              @change="toggleTheme"
            />
            <span class="ios-slider"></span>
          </label>
        </div>
      </div>

      <!-- User info y logout -->
      <div class="menu-mas-footer">
        <div class="user-info">
          <div class="user-avatar">
            {{ authStore.userName?.charAt(0)?.toUpperCase() || '?' }}
          </div>
          <div class="user-details">
            <span class="user-name">{{ authStore.userName || 'Usuario' }}</span>
            <span class="user-role">{{ authStore.userType }}</span>
          </div>
        </div>
        <button 
          v-if="authStore.isAuthenticated" 
          class="logout-btn"
          @click="cerrarSesion"
        >
          <i class="fa fa-sign-out-alt"></i>
        </button>
      </div>
    </div>
  </Transition>

  <!-- Bottom Navigation -->
  <nav class="bottom-nav">
    <div class="nav-container">
      <a
        v-for="item in footerItems"
        :key="item.ruta"
        href="#"
        class="nav-item"
        :class="{ 
          active: isActive(item.ruta),
          'mas-active': item.isMas && mostrarMenuMas,
          'center-btn': item.isCenter
        }"
        @click.prevent="handleItemClick(item)"
      >
        <!-- Botón central especial (QR Scanner) -->
        <template v-if="item.isCenter">
          <span class="center-icon">
            <i :class="['fa', item.icon]"></i>
          </span>
          <span class="nav-label center-label">{{ item.label }}</span>
        </template>
        
        <!-- Botones normales -->
        <template v-else>
          <span class="nav-icon">
            <i :class="['fa', item.icon]"></i>
          </span>
          <span class="nav-label">{{ item.label }}</span>
          <span v-if="isActive(item.ruta) && !item.isMas" class="active-indicator"></span>
        </template>
      </a>
    </div>
    
    <!-- Safe area para iPhones con notch -->
    <div class="safe-area"></div>
  </nav>
</template>

<style scoped>
/* ===== BOTTOM NAVIGATION ===== */
.bottom-nav {
  position: fixed;
  bottom: 0;
  left: 0;
  right: 0;
  background: rgba(255, 255, 255, 0.95);
  backdrop-filter: blur(20px);
  -webkit-backdrop-filter: blur(20px);
  border-top: 1px solid rgba(0, 0, 0, 0.06);
  z-index: 100;
  padding-bottom: env(safe-area-inset-bottom, 0);
}

.theme-dark .bottom-nav {
  background: rgba(26, 26, 26, 0.95);
  border-top-color: rgba(255, 255, 255, 0.08);
}

.nav-container {
  display: flex;
  justify-content: space-around;
  align-items: flex-end;
  height: 65px;
  max-width: 500px;
  margin: 0 auto;
  padding: 0 8px 8px;
}

.nav-item {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: flex-end;
  flex: 1;
  height: 100%;
  text-decoration: none;
  position: relative;
  padding-bottom: 4px;
  -webkit-tap-highlight-color: transparent;
}

.nav-icon {
  width: 44px;
  height: 32px;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 16px;
  font-size: 20px;
  color: #9ca3af;
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  position: relative;
}

.theme-dark .nav-icon {
  color: #6b7280;
}

.nav-label {
  font-size: 11px;
  font-weight: 500;
  color: #9ca3af;
  margin-top: 4px;
  transition: all 0.3s;
  letter-spacing: 0.2px;
}

.theme-dark .nav-label {
  color: #6b7280;
}

/* Active state */
.nav-item.active .nav-icon {
  background: linear-gradient(135deg, rgba(236, 64, 122, 0.15) 0%, rgba(194, 24, 91, 0.15) 100%);
  color: #ec407a;
}

.nav-item.active .nav-label {
  color: #ec407a;
  font-weight: 600;
}

.active-indicator {
  display: none;
}

/* Más button */
.nav-item.mas-active .nav-icon {
  background: linear-gradient(135deg, #ec407a, #c2185b);
  color: white;
}

.nav-item.mas-active .nav-icon i {
  transform: rotate(90deg);
  transition: transform 0.3s ease;
}

/* Hover/tap effect */
.nav-item:active .nav-icon {
  transform: scale(0.92);
  background: rgba(236, 64, 122, 0.1);
}

/* ===== BOTÓN CENTRAL (QR SCANNER) ===== */
.nav-item.center-btn {
  position: relative;
}

.center-icon {
  width: 48px;
  height: 48px;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 50%;
  background: linear-gradient(135deg, #ec407a 0%, #c2185b 100%);
  color: white;
  font-size: 20px;
  box-shadow: 0 4px 15px rgba(236, 64, 122, 0.4);
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  position: relative;
}

.theme-dark .center-icon {
  box-shadow: 0 4px 20px rgba(236, 64, 122, 0.5);
}

.nav-item.center-btn:active .center-icon {
  transform: scale(0.9);
}

.nav-item.center-btn.active .center-icon {
  background: linear-gradient(135deg, #d81b60 0%, #880e4f 100%);
}

.center-label {
  color: #ec407a;
  font-weight: 600;
  margin-top: 4px;
  font-size: 10px;
}

.nav-item.center-btn.active .center-label {
  color: #c2185b;
}

/* Efecto glow animado */
.center-icon::before {
  content: '';
  position: absolute;
  inset: -4px;
  border-radius: 50%;
  background: rgba(236, 64, 122, 0.3);
  z-index: -1;
  animation: glow-pulse 2s ease-in-out infinite;
}

@keyframes glow-pulse {
  0%, 100% {
    transform: scale(1);
    opacity: 0.5;
  }
  50% {
    transform: scale(1.15);
    opacity: 0;
  }
}

/* Safe area */
.safe-area {
  height: env(safe-area-inset-bottom, 0);
}

/* ===== MENU MÁS ===== */
.menu-overlay {
  position: fixed;
  inset: 0;
  background: rgba(0, 0, 0, 0.4);
  backdrop-filter: blur(8px);
  -webkit-backdrop-filter: blur(8px);
  z-index: 150;
}

.menu-mas {
  position: fixed;
  bottom: 80px;
  left: 16px;
  right: 16px;
  background: rgba(255, 255, 255, 0.98);
  backdrop-filter: blur(20px);
  -webkit-backdrop-filter: blur(20px);
  border-radius: 24px;
  box-shadow: 
    0 -20px 60px rgba(0, 0, 0, 0.12),
    0 0 0 1px rgba(0, 0, 0, 0.05);
  z-index: 200;
  overflow: hidden;
  padding-bottom: env(safe-area-inset-bottom, 12px);
}

.theme-dark .menu-mas {
  background: rgba(30, 30, 30, 0.98);
  box-shadow: 
    0 -20px 60px rgba(0, 0, 0, 0.4),
    0 0 0 1px rgba(255, 255, 255, 0.08);
}

.menu-mas-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 18px 20px 14px;
  border-bottom: 1px solid rgba(0, 0, 0, 0.06);
}

.theme-dark .menu-mas-header {
  border-bottom-color: rgba(255, 255, 255, 0.08);
}

.menu-mas-title {
  font-size: 18px;
  font-weight: 700;
  color: #1f2937;
  letter-spacing: -0.3px;
}

.theme-dark .menu-mas-title {
  color: #f9fafb;
}

.close-btn {
  width: 34px;
  height: 34px;
  border-radius: 50%;
  background: rgba(0, 0, 0, 0.05);
  border: none;
  color: #6b7280;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all 0.2s;
  font-size: 14px;
}

.theme-dark .close-btn {
  background: rgba(255, 255, 255, 0.08);
  color: #9ca3af;
}

.close-btn:active {
  transform: scale(0.92);
  background: rgba(0, 0, 0, 0.1);
}

/* Menu items */
.menu-mas-items {
  padding: 10px 12px;
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 8px;
}

.menu-mas-item {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 8px;
  padding: 16px 12px;
  border-radius: 16px;
  text-decoration: none;
  color: #374151;
  transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
  background: rgba(0, 0, 0, 0.02);
}

.theme-dark .menu-mas-item {
  color: #e5e7eb;
  background: rgba(255, 255, 255, 0.03);
}

.menu-mas-item:active {
  transform: scale(0.96);
  background: rgba(236, 64, 122, 0.1);
}

.menu-mas-item.active {
  background: linear-gradient(135deg, rgba(236, 64, 122, 0.12), rgba(194, 24, 91, 0.12));
}

.item-icon {
  width: 48px;
  height: 48px;
  border-radius: 14px;
  background: linear-gradient(135deg, rgba(236, 64, 122, 0.1), rgba(194, 24, 91, 0.12));
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 20px;
  color: #ec407a;
  transition: all 0.25s;
}

.menu-mas-item.active .item-icon {
  background: linear-gradient(135deg, #ec407a, #c2185b);
  color: white;
  box-shadow: 0 4px 12px rgba(236, 64, 122, 0.3);
}

.item-label {
  font-size: 13px;
  font-weight: 600;
  text-align: center;
  line-height: 1.2;
}

.item-arrow {
  display: none;
}

/* Theme Toggle Item */
.theme-toggle-item {
  grid-column: span 2;
  flex-direction: row !important;
  justify-content: space-between;
  padding: 14px 16px !important;
  background: linear-gradient(135deg, rgba(99, 102, 241, 0.08), rgba(79, 70, 229, 0.08)) !important;
}

.theme-toggle-item:active {
  transform: none !important;
  background: linear-gradient(135deg, rgba(99, 102, 241, 0.12), rgba(79, 70, 229, 0.12)) !important;
}

.theme-toggle-item .item-icon {
  width: 40px;
  height: 40px;
}

.theme-icon {
  background: linear-gradient(135deg, rgba(99, 102, 241, 0.15), rgba(79, 70, 229, 0.18)) !important;
  color: #6366f1 !important;
}

.theme-toggle-item .item-label {
  flex: 1;
  text-align: left;
  margin-left: 12px;
  font-size: 14px;
}

/* iOS Toggle Switch */
.ios-toggle-switch {
  position: relative;
  display: inline-block;
  width: 52px;
  height: 32px;
  flex-shrink: 0;
}

.ios-toggle-switch input {
  opacity: 0;
  width: 0;
  height: 0;
}

.ios-slider {
  position: absolute;
  cursor: pointer;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background-color: #d1d5db;
  transition: 0.35s cubic-bezier(0.4, 0, 0.2, 1);
  border-radius: 32px;
}

.ios-slider:before {
  position: absolute;
  content: "";
  height: 26px;
  width: 26px;
  left: 3px;
  bottom: 3px;
  background-color: white;
  transition: 0.35s cubic-bezier(0.4, 0, 0.2, 1);
  border-radius: 50%;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
}

.ios-toggle-switch input:checked + .ios-slider {
  background: linear-gradient(135deg, #6366f1, #4f46e5);
}

.ios-toggle-switch input:checked + .ios-slider:before {
  transform: translateX(20px);
}

.theme-dark .ios-slider {
  background-color: #4b5563;
}

.theme-dark .ios-toggle-switch input:checked + .ios-slider {
  background: linear-gradient(135deg, #818cf8, #6366f1);
}

/* Footer */
.menu-mas-footer {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 14px 16px;
  margin: 8px 12px 4px;
  background: linear-gradient(135deg, rgba(236, 64, 122, 0.06), rgba(194, 24, 91, 0.08));
  border-radius: 16px;
  border: 1px solid rgba(236, 64, 122, 0.1);
}

.theme-dark .menu-mas-footer {
  background: linear-gradient(135deg, rgba(236, 64, 122, 0.1), rgba(194, 24, 91, 0.12));
  border-color: rgba(236, 64, 122, 0.15);
}

.user-info {
  display: flex;
  align-items: center;
  gap: 12px;
}

.user-avatar {
  width: 44px;
  height: 44px;
  border-radius: 14px;
  background: linear-gradient(135deg, #ec407a, #c2185b);
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 18px;
  font-weight: 700;
  color: white;
  box-shadow: 0 4px 12px rgba(236, 64, 122, 0.25);
}

.user-details {
  display: flex;
  flex-direction: column;
  gap: 2px;
}

.user-name {
  font-size: 15px;
  font-weight: 600;
  color: #1f2937;
}

.theme-dark .user-name {
  color: #f9fafb;
}

.user-role {
  font-size: 12px;
  color: #ec407a;
  text-transform: capitalize;
  font-weight: 600;
  letter-spacing: 0.3px;
}

.logout-btn {
  width: 44px;
  height: 44px;
  border-radius: 14px;
  background: rgba(239, 68, 68, 0.1);
  border: none;
  color: #ef4444;
  font-size: 18px;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all 0.25s;
}

.theme-dark .logout-btn {
  background: rgba(239, 68, 68, 0.15);
}

.logout-btn:active {
  transform: scale(0.92);
  background: rgba(239, 68, 68, 0.2);
}

/* ===== TRANSITIONS ===== */
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.2s ease;
}

.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}

.slide-up-enter-active,
.slide-up-leave-active {
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.slide-up-enter-from,
.slide-up-leave-to {
  transform: translateY(100%);
  opacity: 0;
}
</style>
