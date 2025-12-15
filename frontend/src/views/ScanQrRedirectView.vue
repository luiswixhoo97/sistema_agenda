<template>
  <div class="scan-redirect-view">
    <div class="container">
      <!-- Procesando -->
      <div v-if="procesando" class="status-card">
        <div class="spinner"></div>
        <h2>Verificando cita...</h2>
        <p>Por favor espera un momento</p>
      </div>

      <!-- Éxito -->
      <div v-else-if="resultado?.success" class="status-card success">
        <div class="status-icon">
          <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
            <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
            <polyline points="22 4 12 14.01 9 11.01"></polyline>
          </svg>
        </div>
        <h2>¡Cita completada!</h2>
        <div class="cita-info">
          <div class="info-item">
            <span class="info-label">Cliente</span>
            <span class="info-value">{{ resultado.cita?.cliente_nombre }}</span>
          </div>
          <div class="info-item">
            <span class="info-label">Servicio</span>
            <span class="info-value">{{ resultado.cita?.servicios_nombres || resultado.cita?.servicio_nombre }}</span>
          </div>
          <div class="info-item">
            <span class="info-label">Fecha</span>
            <span class="info-value">{{ resultado.cita?.fecha }} - {{ resultado.cita?.hora }}</span>
          </div>
        </div>
        <button class="btn-action" @click="irAlInicio">
          <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path>
            <polyline points="9 22 9 12 15 12 15 22"></polyline>
          </svg>
          Volver al inicio
        </button>
      </div>

      <!-- Error - No autenticado -->
      <div v-else-if="!isAuthenticated" class="status-card warning">
        <div class="status-icon">
          <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
            <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
          </svg>
        </div>
        <h2>Acceso restringido</h2>
        <p>Debes iniciar sesión como empleado o administrador para marcar citas como completadas.</p>
        <button class="btn-action" @click="irALogin">
          <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"></path>
            <polyline points="10 17 15 12 10 7"></polyline>
            <line x1="15" y1="12" x2="3" y2="12"></line>
          </svg>
          Iniciar sesión
        </button>
      </div>

      <!-- Error general -->
      <div v-else-if="resultado && !resultado.success" class="status-card error">
        <div class="status-icon">
          <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <circle cx="12" cy="12" r="10"></circle>
            <line x1="12" y1="8" x2="12" y2="12"></line>
            <line x1="12" y1="16" x2="12.01" y2="16"></line>
          </svg>
        </div>
        <h2>Error</h2>
        <p>{{ resultado.message }}</p>
        <button class="btn-action" @click="irAlInicio">
          <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path>
            <polyline points="9 22 9 12 15 12 15 22"></polyline>
          </svg>
          Volver al inicio
        </button>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

const route = useRoute()
const router = useRouter()
const authStore = useAuthStore()
const API_URL = import.meta.env.VITE_API_URL || 'https://salmon-eland-125157.hostingersite.com/backend/public/api'

const procesando = ref(true)
const resultado = ref<any>(null)

const isAuthenticated = computed(() => authStore.isAuthenticated)
const isEmpleadoOrAdmin = computed(() => 
  authStore.userType === 'empleado' || authStore.userType === 'admin'
)

const procesarQr = async () => {
  const token = route.params.token as string
  
  if (!token) {
    resultado.value = { success: false, message: 'Token no válido' }
    procesando.value = false
    return
  }

  // Verificar autenticación
  if (!isAuthenticated.value) {
    procesando.value = false
    return
  }

  // Verificar que sea empleado o admin
  if (!isEmpleadoOrAdmin.value) {
    resultado.value = { 
      success: false, 
      message: 'Solo empleados o administradores pueden marcar citas como completadas' 
    }
    procesando.value = false
    return
  }

  try {
    const endpoint = authStore.userType === 'admin'
      ? `/admin/citas/scan-qr/${token}`
      : `/empleado/citas/scan-qr/${token}`

    const response = await fetch(`${API_URL}${endpoint}`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'Authorization': `Bearer ${authStore.token}`
      }
    })

    resultado.value = await response.json()
  } catch (error) {
    console.error('Error procesando QR:', error)
    resultado.value = { success: false, message: 'Error de conexión' }
  } finally {
    procesando.value = false
  }
}

const irALogin = () => {
  router.push({ name: 'login', query: { redirect: route.fullPath } })
}

const irAlInicio = () => {
  if (authStore.userType === 'admin') {
    router.push({ name: 'admin-dashboard' })
  } else if (authStore.userType === 'empleado') {
    router.push({ name: 'empleado-citas' })
  } else {
    router.push({ name: 'home' })
  }
}

onMounted(() => {
  procesarQr()
})
</script>

<style scoped>
/* ===== Apple-inspired Scan QR Redirect View Design ===== */

.scan-redirect-view {
  min-height: 100vh;
  background: #f5f5f7;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 24px;
  font-family: -apple-system, BlinkMacSystemFont, 'SF Pro Display', 'SF Pro Text', 'Helvetica Neue', sans-serif;
  -webkit-font-smoothing: antialiased;
  -moz-osx-font-smoothing: grayscale;
}

.container {
  width: 100%;
  max-width: 420px;
}

.status-card {
  background: #ffffff;
  border-radius: 24px;
  padding: 40px 32px;
  text-align: center;
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
  border: 0.5px solid rgba(0, 0, 0, 0.08);
  backdrop-filter: blur(20px);
}

/* Spinner */
.spinner {
  width: 56px;
  height: 56px;
  border: 3px solid rgba(0, 122, 255, 0.1);
  border-top-color: #007aff;
  border-radius: 50%;
  animation: spin 0.8s linear infinite;
  margin: 0 auto 24px;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}

/* Status Icon */
.status-icon {
  width: 88px;
  height: 88px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  margin: 0 auto 24px;
  color: white;
}

.status-card.success .status-icon {
  background: linear-gradient(135deg, #34c759 0%, #30d158 100%);
  box-shadow: 0 4px 16px rgba(52, 199, 89, 0.3);
}

.status-card.error .status-icon {
  background: linear-gradient(135deg, #ff3b30 0%, #ff2d55 100%);
  box-shadow: 0 4px 16px rgba(255, 59, 48, 0.3);
}

.status-card.warning .status-icon {
  background: linear-gradient(135deg, #ff9500 0%, #ff9f0a 100%);
  box-shadow: 0 4px 16px rgba(255, 149, 0, 0.3);
}

/* Typography */
.status-card h2 {
  margin: 0 0 12px;
  font-size: 28px;
  font-weight: 600;
  color: #1d1d1f;
  letter-spacing: -0.4px;
}

.status-card.success h2 {
  color: #34c759;
}

.status-card.error h2 {
  color: #ff3b30;
}

.status-card.warning h2 {
  color: #ff9500;
}

.status-card p {
  color: #86868b;
  margin: 0 0 32px;
  line-height: 1.5;
  font-size: 17px;
}

/* Cita Info */
.cita-info {
  background: #f5f5f7;
  border-radius: 16px;
  padding: 20px;
  margin-bottom: 32px;
  text-align: left;
  border: 0.5px solid rgba(0, 0, 0, 0.06);
}

.info-item {
  display: flex;
  flex-direction: column;
  gap: 6px;
  padding: 12px 0;
  border-bottom: 0.5px solid rgba(0, 0, 0, 0.06);
}

.info-item:last-child {
  border-bottom: none;
  padding-bottom: 0;
}

.info-label {
  font-size: 12px;
  font-weight: 600;
  color: #007aff;
  text-transform: uppercase;
  letter-spacing: 0.3px;
}

.info-value {
  font-size: 16px;
  font-weight: 500;
  color: #1d1d1f;
  line-height: 1.4;
}

/* Button */
.btn-action {
  padding: 16px 32px;
  background: linear-gradient(135deg, #007aff 0%, #0051d5 100%);
  color: white;
  border: none;
  border-radius: 14px;
  font-size: 17px;
  font-weight: 600;
  cursor: pointer;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 10px;
  transition: all 0.2s ease;
  box-shadow: 0 4px 12px rgba(0, 122, 255, 0.3);
  min-width: 200px;
}

.btn-action:active {
  transform: scale(0.98);
  box-shadow: 0 2px 8px rgba(0, 122, 255, 0.3);
}

.btn-action svg {
  flex-shrink: 0;
}

/* Dark Mode Support */
@media (prefers-color-scheme: dark) {
  .scan-redirect-view {
    background: #000000;
  }

  .status-card {
    background: #1c1c1e;
    border-color: rgba(255, 255, 255, 0.1);
  }

  .status-card h2 {
    color: #ffffff;
  }

  .status-card.success h2 {
    color: #34c759;
  }

  .status-card.error h2 {
    color: #ff3b30;
  }

  .status-card.warning h2 {
    color: #ff9500;
  }

  .status-card p {
    color: #a1a1a6;
  }

  .cita-info {
    background: #2c2c2e;
    border-color: rgba(255, 255, 255, 0.1);
  }

  .info-label {
    color: #0a84ff;
  }

  .info-value {
    color: #ffffff;
  }

  .info-item {
    border-color: rgba(255, 255, 255, 0.1);
  }
}
</style>

