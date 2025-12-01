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
      { icon: 'fa-list-alt', label: 'Mis Citas', ruta: '/empleado/citas' },
      { icon: 'fa-user-circle', label: 'Perfil', ruta: '/empleado/perfil' },
    ]
  } else if (authStore.userType === 'admin') {
    return [
      { icon: 'fa-chart-line', label: 'Dashboard', ruta: '/admin' },
      { icon: 'fa-calendar', label: 'Citas', ruta: '/admin/citas' },
      { icon: 'fa-cut', label: 'Servicios', ruta: '/admin/servicios' },
      { icon: 'fa-users', label: 'Equipo', ruta: '/admin/empleados' },
      { icon: 'fa-ellipsis-h', label: 'Más', ruta: '#mas', isMas: true },
    ]
  }
  // Menú público
  return [
    { icon: 'fa-calendar-plus', label: 'Agendar', ruta: '/agendar' },
    { icon: 'fa-sign-in-alt', label: 'Acceso', ruta: '/login' },
  ]
})

// Items adicionales para el menú "Más" (admin)
const menuMasItems = computed(() => [
  { icon: 'fa-user-friends', label: 'Clientes', ruta: '/admin/clientes' },
  { icon: 'fa-folder', label: 'Categorías', ruta: '/admin/categorias' },
  { icon: 'fa-gift', label: 'Promociones', ruta: '/admin/promociones' },
  { icon: 'fa-cog', label: 'Configuración', ruta: '/admin/configuracion' },
])

const handleItemClick = (item: any) => {
  if (item.isMas) {
    mostrarMenuMas.value = !mostrarMenuMas.value
  } else {
    navegarA(item.ruta)
  }
}

const isActive = (ruta: string) => {
  if (ruta === '#mas') {
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
          'mas-active': item.isMas && mostrarMenuMas 
        }"
        @click.prevent="handleItemClick(item)"
      >
        <span class="nav-icon">
          <i :class="['fa', item.icon]"></i>
        </span>
        <span class="nav-label">{{ item.label }}</span>
        <span v-if="isActive(item.ruta) && !item.isMas" class="active-indicator"></span>
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
  background: var(--nav-bg, #ffffff);
  border-top: 1px solid var(--border-color, rgba(0,0,0,0.08));
  z-index: 100;
  padding-bottom: env(safe-area-inset-bottom, 0);
}

.theme-dark .bottom-nav {
  --nav-bg: #1a1a1a;
  --border-color: rgba(255,255,255,0.1);
}

.nav-container {
  display: flex;
  justify-content: space-around;
  align-items: center;
  height: 60px;
  max-width: 500px;
  margin: 0 auto;
}

.nav-item {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  flex: 1;
  height: 100%;
  text-decoration: none;
  position: relative;
  transition: all 0.2s ease;
}

.nav-icon {
  width: 28px;
  height: 28px;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 10px;
  font-size: 18px;
  color: var(--icon-color, #888);
  transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
}

.theme-dark .nav-icon {
  --icon-color: #666;
}

.nav-label {
  font-size: 10px;
  font-weight: 500;
  color: var(--label-color, #888);
  margin-top: 2px;
  transition: all 0.2s;
}

.theme-dark .nav-label {
  --label-color: #666;
}

/* Active state */
.nav-item.active .nav-icon {
  color: #ec407a;
  transform: scale(1.1);
}

.nav-item.active .nav-label {
  color: #ec407a;
  font-weight: 600;
}

.active-indicator {
  position: absolute;
  top: 4px;
  width: 4px;
  height: 4px;
  background: #ec407a;
  border-radius: 50%;
}

/* Más button active */
.nav-item.mas-active .nav-icon {
  background: linear-gradient(135deg, #ec407a, #c2185b);
  color: white;
  transform: scale(1.1) rotate(90deg);
}

/* Hover/tap effect */
.nav-item:active .nav-icon {
  transform: scale(0.9);
}

/* Safe area */
.safe-area {
  height: env(safe-area-inset-bottom, 0);
}

/* ===== MENU MÁS ===== */
.menu-overlay {
  position: fixed;
  inset: 0;
  background: rgba(0, 0, 0, 0.5);
  backdrop-filter: blur(4px);
  z-index: 150;
}

.menu-mas {
  position: fixed;
  bottom: 70px;
  left: 12px;
  right: 12px;
  background: var(--menu-bg, #ffffff);
  border-radius: 20px;
  box-shadow: 0 -10px 40px rgba(0,0,0,0.15);
  z-index: 200;
  overflow: hidden;
  padding-bottom: env(safe-area-inset-bottom, 8px);
}

.theme-dark .menu-mas {
  --menu-bg: #1f1f1f;
  box-shadow: 0 -10px 40px rgba(0,0,0,0.4);
}

.menu-mas-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 16px 20px;
  border-bottom: 1px solid var(--border-color, rgba(0,0,0,0.08));
}

.menu-mas-title {
  font-size: 16px;
  font-weight: 700;
  color: var(--text-color, #333);
}

.theme-dark .menu-mas-title {
  --text-color: #fff;
}

.close-btn {
  width: 32px;
  height: 32px;
  border-radius: 50%;
  background: rgba(0,0,0,0.05);
  border: none;
  color: var(--text-secondary, #666);
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all 0.2s;
}

.theme-dark .close-btn {
  background: rgba(255,255,255,0.1);
  --text-secondary: #aaa;
}

.close-btn:active {
  transform: scale(0.95);
  background: rgba(0,0,0,0.1);
}

/* Menu items */
.menu-mas-items {
  padding: 8px;
}

.menu-mas-item {
  display: flex;
  align-items: center;
  gap: 14px;
  padding: 14px 16px;
  border-radius: 14px;
  text-decoration: none;
  color: var(--text-color, #333);
  transition: all 0.2s;
}

.theme-dark .menu-mas-item {
  --text-color: #eee;
}

.menu-mas-item:active {
  background: rgba(236, 64, 122, 0.1);
}

.menu-mas-item.active {
  background: linear-gradient(135deg, rgba(236, 64, 122, 0.1), rgba(236, 64, 122, 0.15));
}

.item-icon {
  width: 42px;
  height: 42px;
  border-radius: 12px;
  background: linear-gradient(135deg, rgba(236, 64, 122, 0.1), rgba(236, 64, 122, 0.15));
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 16px;
  color: #ec407a;
}

.menu-mas-item.active .item-icon {
  background: linear-gradient(135deg, #ec407a, #c2185b);
  color: white;
}

.item-label {
  flex: 1;
  font-size: 15px;
  font-weight: 500;
}

.item-arrow {
  font-size: 12px;
  color: #ccc;
  opacity: 0.5;
}

/* Theme Toggle Item */
.theme-toggle-item {
  cursor: default;
}

.theme-toggle-item:active {
  background: transparent;
}

.theme-icon {
  background: linear-gradient(135deg, rgba(99, 102, 241, 0.1), rgba(99, 102, 241, 0.15)) !important;
  color: #6366f1 !important;
}

/* iOS Toggle Switch */
.ios-toggle-switch {
  position: relative;
  display: inline-block;
  width: 50px;
  height: 30px;
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
  background-color: #ccc;
  transition: 0.3s;
  border-radius: 30px;
}

.ios-slider:before {
  position: absolute;
  content: "";
  height: 22px;
  width: 22px;
  left: 4px;
  bottom: 4px;
  background-color: white;
  transition: 0.3s;
  border-radius: 50%;
  box-shadow: 0 2px 4px rgba(0,0,0,0.2);
}

.ios-toggle-switch input:checked + .ios-slider {
  background: linear-gradient(135deg, #6366f1, #4f46e5);
}

.ios-toggle-switch input:checked + .ios-slider:before {
  transform: translateX(20px);
}

.theme-dark .ios-slider {
  background-color: #444;
}

.theme-dark .ios-toggle-switch input:checked + .ios-slider {
  background: linear-gradient(135deg, #818cf8, #6366f1);
}

/* Footer */
.menu-mas-footer {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 12px 16px;
  margin: 0 8px;
  background: linear-gradient(135deg, rgba(236, 64, 122, 0.08), rgba(236, 64, 122, 0.12));
  border-radius: 14px;
}

.user-info {
  display: flex;
  align-items: center;
  gap: 12px;
}

.user-avatar {
  width: 40px;
  height: 40px;
  border-radius: 12px;
  background: linear-gradient(135deg, #ec407a, #c2185b);
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 16px;
  font-weight: 700;
  color: white;
}

.user-details {
  display: flex;
  flex-direction: column;
}

.user-name {
  font-size: 14px;
  font-weight: 600;
  color: var(--text-color, #333);
}

.theme-dark .user-name {
  --text-color: #fff;
}

.user-role {
  font-size: 11px;
  color: #ec407a;
  text-transform: capitalize;
  font-weight: 500;
}

.logout-btn {
  width: 40px;
  height: 40px;
  border-radius: 12px;
  background: rgba(239, 68, 68, 0.1);
  border: none;
  color: #ef4444;
  font-size: 16px;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all 0.2s;
}

.logout-btn:active {
  transform: scale(0.95);
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
