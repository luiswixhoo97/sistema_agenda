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
          <i class="fa fa-check-circle"></i>
        </div>
        <h2>¡Cita completada!</h2>
        <div class="cita-info">
          <p><strong>Cliente:</strong> {{ resultado.cita?.cliente_nombre }}</p>
          <p><strong>Servicio:</strong> {{ resultado.cita?.servicios_nombres || resultado.cita?.servicio_nombre }}</p>
          <p><strong>Fecha:</strong> {{ resultado.cita?.fecha }} - {{ resultado.cita?.hora }}</p>
        </div>
        <button class="btn-action" @click="irAlInicio">
          <i class="fa fa-home"></i>
          Volver al inicio
        </button>
      </div>

      <!-- Error - No autenticado -->
      <div v-else-if="!isAuthenticated" class="status-card warning">
        <div class="status-icon">
          <i class="fa fa-lock"></i>
        </div>
        <h2>Acceso restringido</h2>
        <p>Debes iniciar sesión como empleado o administrador para marcar citas como completadas.</p>
        <button class="btn-action" @click="irALogin">
          <i class="fa fa-sign-in-alt"></i>
          Iniciar sesión
        </button>
      </div>

      <!-- Error general -->
      <div v-else-if="resultado && !resultado.success" class="status-card error">
        <div class="status-icon">
          <i class="fa fa-times-circle"></i>
        </div>
        <h2>Error</h2>
        <p>{{ resultado.message }}</p>
        <button class="btn-action" @click="irAlInicio">
          <i class="fa fa-home"></i>
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
const API_URL = import.meta.env.VITE_API_URL || 'http://localhost:8000/api'

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
.scan-redirect-view {
  min-height: 100vh;
  background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%);
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 1.5rem;
}

.container {
  width: 100%;
  max-width: 400px;
}

.status-card {
  background: white;
  border-radius: 24px;
  padding: 2.5rem 2rem;
  text-align: center;
  box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3);
}

.spinner {
  width: 60px;
  height: 60px;
  border: 4px solid #f0f0f0;
  border-top-color: #667eea;
  border-radius: 50%;
  animation: spin 0.8s linear infinite;
  margin: 0 auto 1.5rem;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}

.status-icon {
  width: 80px;
  height: 80px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  margin: 0 auto 1.5rem;
  font-size: 2.5rem;
}

.status-card.success .status-icon {
  background: linear-gradient(135deg, #10b981 0%, #059669 100%);
  color: white;
}

.status-card.error .status-icon {
  background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
  color: white;
}

.status-card.warning .status-icon {
  background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
  color: white;
}

.status-card h2 {
  margin: 0 0 0.75rem;
  font-size: 1.5rem;
  color: #1a1a2e;
}

.status-card.success h2 {
  color: #059669;
}

.status-card.error h2 {
  color: #dc2626;
}

.status-card p {
  color: #666;
  margin: 0 0 1.5rem;
  line-height: 1.5;
}

.cita-info {
  background: #f8f9fa;
  border-radius: 12px;
  padding: 1rem;
  margin-bottom: 1.5rem;
  text-align: left;
}

.cita-info p {
  margin: 0.25rem 0;
  font-size: 0.9rem;
}

.btn-action {
  padding: 1rem 2rem;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  border: none;
  border-radius: 14px;
  font-size: 1rem;
  font-weight: 600;
  cursor: pointer;
  display: inline-flex;
  align-items: center;
  gap: 0.75rem;
  transition: transform 0.2s;
}

.btn-action:hover {
  transform: translateY(-2px);
}
</style>

