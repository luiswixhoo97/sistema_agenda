<template>
  <div class="qr-validation-view">
    <!-- Loading State -->
    <div v-if="validando" class="validation-loading">
      <div class="spinner"></div>
      <h2>Validando QR...</h2>
      <p>Por favor espera un momento</p>
    </div>

    <!-- Validation State -->
    <div v-else-if="modoValidacion && datosValidacion" class="validation-card">
      <!-- Customer Header -->
      <div class="customer-header">
        <div class="customer-avatar">
          <img :src="datosValidacion.citas[0]?.cliente?.foto || '/default-avatar.png'" />
        </div>
        <div class="customer-info">
          <h2>{{ datosValidacion.citas[0]?.cliente?.nombre }}</h2>
          <p v-if="datosValidacion.citas[0]?.cliente?.telefono">{{ datosValidacion.citas[0].cliente.telefono }}</p>
          <p v-if="datosValidacion.citas[0]?.cliente?.email">{{ datosValidacion.citas[0].cliente.email }}</p>
        </div>
      </div>

      <!-- Appointments List -->
      <div class="appointments-section">
        <h3>Servicios a Realizar</h3>
        <div v-for="cita in datosValidacion.citas" :key="cita.id" class="appointment-item">
          <div class="service-info">
            <span class="service-name">{{ cita.servicio.nombre }}</span>
            <span class="employee-name" v-if="cita.empleado">Con: {{ cita.empleado.nombre }}</span>
          </div>
          <div class="appointment-time">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
              <line x1="16" y1="2" x2="16" y2="6"></line>
              <line x1="8" y1="2" x2="8" y2="6"></line>
              <line x1="3" y1="10" x2="21" y2="10"></line>
              <circle cx="8" cy="14" r="1"></circle>
              <circle cx="16" cy="14" r="1"></circle>
            </svg>
            {{ formatFecha(cita.fecha) }} - {{ cita.hora }}
          </div>
          <div class="service-price">
            ${{ formatPrecio(cita.precio_final) }}
          </div>
        </div>
      </div>

      <!-- Total Amount -->
      <div class="total-section">
        <div class="total-amount">
          Total: ${{ formatPrecio(calcularTotal()) }}
        </div>
        <div v-if="datosValidacion.venta_existente" class="existing-sale-notice">
          <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <circle cx="12" cy="12" r="10"></circle>
            <line x1="12" y1="8" x2="12.01" y2="8"></line>
          </svg>
          Venta parcial existente
        </div>
      </div>

      <!-- Action Buttons -->
      <div class="action-buttons">
        <button class="btn-start" @click="iniciarAtencion">
          <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
            <path d="M9 11l3 3L22 12l-10 1-1 1z"></path>
            <path d="M21 12v9c0 1.66-1.34 3-3 3H6c-1.66 0-3-1.34-3-3v-1h13v1z"></path>
          </svg>
          Iniciar Atención
        </button>
        <button class="btn-payment" @click="irAPago">
          <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
            <rect x="1" y="4" width="22" height="16" rx="2" ry="2"></rect>
            <line x1="1" y1="10" x2="23" y2="10"></line>
            <circle cx="9" cy="14" r="1"></circle>
            <circle cx="20" cy="14" r="1"></circle>
          </svg>
          Ir a Pagar
        </button>
      </div>
    </div>

    <!-- Completion Success State -->
    <div v-else-if="!modoValidacion && resultado?.success" class="success-card">
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
          <span class="info-value">{{ resultado.cita_principal?.cliente_nombre }}</span>
        </div>
        <div class="info-item">
          <span class="info-label">Servicio</span>
          <span class="info-value">{{ resultado.cita_principal?.servicios_nombres || resultado.cita_principal?.servicio_nombre }}</span>
        </div>
        <div class="info-item">
          <span class="info-label">Fecha</span>
          <span class="info-value">{{ resultado.cita_principal?.fecha }} - {{ resultado.cita_principal?.hora }}</span>
        </div>
      </div>
      <button class="btn-action" @click="irAVentas">
        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
          <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path>
          <polyline points="9 22 9 12 15 12"></polyline>
        </svg>
        Ver Ventas
      </button>
    </div>

    <!-- Error - No autenticado -->
    <div v-else-if="!isAuthenticated" class="warning-card">
      <div class="status-icon">
        <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
          <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
          <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
        </svg>
      </div>
      <h2>Acceso restringido</h2>
      <p>Debes iniciar sesión como empleado o administrador para procesar códigos QR.</p>
      <button class="btn-action" @click="irALogin">
        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
          <path d="M15 3h4a2 2 0 0 1 2 2v16a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V7a5 5 0 0 1 10 0v4"></path>
          <polyline points="10 17 15 12 10 7"></polyline>
          <line x1="15" y1="12" x2="3" y2="12"></line>
        </svg>
        Iniciar sesión
      </button>
    </div>

    <!-- Error general -->
    <div v-else-if="resultado && !resultado.success" class="error-card">
      <div class="status-icon">
        <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
          <circle cx="12" cy="12" r="10"></circle>
          <line x1="12" y1="8" x2="12" y2="12"></line>
          <line x1="12" y1="16" x2="12.01" y2="16"></line>
        </svg>
      </div>
      <h2>Error</h2>
      <p>{{ resultado.message }}</p>
      <button class="btn-action" @click="irAlInicio">
        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
          <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path>
          <polyline points="9 22 9 12 15 12"></polyline>
        </svg>
        Volver al inicio
      </button>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { validateQrToken, completeQrToken } from '@/services/inventarioService'
import Swal from 'sweetalert2'

const route = useRoute()
const router = useRouter()
const authStore = useAuthStore()
const API_URL = import.meta.env.VITE_API_URL || 'https://salmon-eland-125157.hostingersite.com/backend/public/api'

const validando = ref(true)
const modoValidacion = ref(true)
const datosValidacion = ref<any>(null)
const resultado = ref<any>(null)

const isAuthenticated = computed(() => authStore.isAuthenticated)
const isEmpleadoOrAdmin = computed(() => 
  authStore.userType === 'empleado' || authStore.userType === 'admin'
)

const validarQR = async () => {
  const token = route.params.token as string
  
  if (!token) {
    resultado.value = { success: false, message: 'Token no válido' }
    validando.value = false
    return
  }

  // Verificar autenticación
  if (!isAuthenticated.value) {
    validando.value = false
    return
  }

  // Verificar que sea empleado o admin
  if (!isEmpleadoOrAdmin.value) {
    resultado.value = { 
      success: false, 
      message: 'Solo empleados o administradores pueden procesar códigos QR.' 
    }
    validando.value = false
    return
  }

  try {
    const response = await validateQrToken(token)
    datosValidacion.value = response.data
    validando.value = false
  } catch (error) {
    console.error('Error validando QR:', error)
    resultado.value = { success: false, message: 'Error de conexión' }
    validando.value = false
  }
}

const iniciarAtencion = async () => {
  const token = route.params.token as string
  
  try {
    const response = await completeQrToken(token)
    resultado.value = response.data
    modoValidacion.value = false
    
    if (response.data.success) {
      Swal.fire({
        icon: 'success',
        title: '¡Atención iniciada!',
        text: 'Las citas han sido marcadas como completadas.',
        timer: 2000,
        showConfirmButton: false
      })
      
      // Redirigir al dashboard según tipo de usuario
      setTimeout(() => {
        irAlDashboard()
      }, 2000)
    }
  } catch (error) {
    console.error('Error iniciando atención:', error)
    Swal.fire({
      icon: 'error',
      title: 'Error',
      text: 'Hubo un error al procesar las citas.'
    })
  }
}

const irAPago = async () => {
  const token = route.params.token as string
  
  try {
    const response = await completeQrToken(token)
    resultado.value = response.data
    modoValidacion.value = false
    
    if (response.data.success && response.data.venta?.id) {
      // Redirigir a ventas con venta_id
      router.push({ 
        name: 'admin-ventas', 
        query: { venta_id: response.data.venta.id.toString() } 
      })
    } else {
      Swal.fire({
        icon: 'warning',
        title: 'Atención',
        text: 'No se encontró una venta para estas citas.'
      })
    }
  } catch (error) {
    console.error('Error al ir a pago:', error)
    Swal.fire({
      icon: 'error',
      title: 'Error',
      text: 'Hubo un error al procesar el pago.'
    })
  }
}

const irALogin = () => {
  router.push({ name: 'login', query: { redirect: route.fullPath } })
}

const irAlDashboard = () => {
  if (authStore.userType === 'admin') {
    router.push({ name: 'admin-dashboard' })
  } else if (authStore.userType === 'empleado') {
    router.push({ name: 'empleado-citas' })
  } else {
    router.push({ name: 'home' })
  }
}

const irAVentas = () => {
  router.push({ name: 'admin-ventas' })
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

function formatPrecio(precio: any): string {
  return (Number(precio) || 0).toFixed(2)
}

function formatFecha(fecha: string): string {
  if (!fecha) return ''
  const d = new Date(fecha)
  return d.toLocaleDateString('es-MX', {
    day: '2-digit',
    month: 'short',
    year: 'numeric'
  })
}

const calcularTotal = () => {
  return datosValidacion.value?.citas?.reduce((sum: number, cita: any) => 
    sum + Number(cita.precio_final || 0), 0) || 0
}

onMounted(() => {
  validarQR()
})
</script>

<style scoped>
/* ===== Apple-inspired QR Validation View Design ===== */

.qr-validation-view {
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

.validation-card {
  background: #ffffff;
  border-radius: 24px;
  padding: 32px 24px;
  text-align: center;
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
  border: 0.5px solid rgba(0, 0, 0, 0.08);
  backdrop-filter: blur(20px);
  width: 100%;
  max-width: 420px;
}

/* Loading State */
.validation-loading {
  background: #ffffff;
  border-radius: 24px;
  padding: 40px 32px;
  text-align: center;
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
  border: 0.5px solid rgba(0, 0, 0, 0.08);
  backdrop-filter: blur(20px);
  width: 100%;
  max-width: 420px;
}

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

.validation-loading h2 {
  margin: 0 0 12px;
  font-size: 28px;
  font-weight: 600;
  color: #1d1d1f;
  letter-spacing: -0.4px;
}

.validation-loading p {
  color: #86868b;
  margin: 0;
  line-height: 1.5;
  font-size: 17px;
}

/* Customer Header */
.customer-header {
  display: flex;
  align-items: center;
  gap: 16px;
  margin-bottom: 24px;
  padding-bottom: 24px;
  border-bottom: 0.5px solid rgba(0, 0, 0, 0.06);
}

.customer-avatar {
  width: 64px;
  height: 64px;
  border-radius: 50%;
  overflow: hidden;
  background: #f5f5f7;
  display: flex;
  align-items: center;
  justify-content: center;
}

.customer-avatar img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.customer-info {
  flex: 1;
  text-align: left;
}

.customer-info h2 {
  margin: 0 0 4px;
  font-size: 22px;
  font-weight: 700;
  color: #1d1d1f;
  letter-spacing: -0.4px;
}

.customer-info p {
  margin: 0;
  font-size: 15px;
  color: #86868b;
  font-weight: 400;
}

/* Appointments Section */
.appointments-section {
  margin-bottom: 24px;
}

.appointments-section h3 {
  margin: 0 0 16px;
  font-size: 18px;
  font-weight: 600;
  color: #1d1d1f;
  text-align: left;
}

.appointment-item {
  background: #f5f5f7;
  border-radius: 16px;
  padding: 16px;
  margin-bottom: 12px;
  text-align: left;
  border: 0.5px solid rgba(0, 0, 0, 0.06);
}

.appointment-item:last-child {
  margin-bottom: 0;
}

.service-info {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  margin-bottom: 8px;
}

.service-name {
  font-size: 16px;
  font-weight: 600;
  color: #1d1d1f;
  flex: 1;
}

.employee-name {
  font-size: 13px;
  color: #86868b;
  margin-left: 8px;
}

.appointment-time {
  display: flex;
  align-items: center;
  gap: 6px;
  font-size: 13px;
  color: #86868b;
}

.appointment-time svg {
  flex-shrink: 0;
  color: #007aff;
}

.service-price {
  font-size: 18px;
  font-weight: 700;
  color: #007aff;
  letter-spacing: -0.02em;
}

/* Total Section */
.total-section {
  background: #f5f5f7;
  border-radius: 16px;
  padding: 20px;
  margin-bottom: 24px;
  border: 0.5px solid rgba(0, 0, 0, 0.06);
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.total-amount {
  font-size: 24px;
  font-weight: 700;
  color: #1d1d1f;
  letter-spacing: -0.02em;
}

.existing-sale-notice {
  display: flex;
  align-items: center;
  gap: 6px;
  font-size: 13px;
  font-weight: 600;
  color: #ff9500;
}

.existing-sale-notice svg {
  flex-shrink: 0;
}

/* Action Buttons */
.action-buttons {
  display: flex;
  gap: 12px;
}

.btn-start, .btn-payment {
  flex: 1;
  padding: 16px 24px;
  border-radius: 14px;
  font-size: 16px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s ease;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  border: none;
}

.btn-start {
  background: linear-gradient(135deg, #34c759 0%, #30d158 100%);
  color: white;
  box-shadow: 0 4px 12px rgba(52, 199, 89, 0.3);
}

.btn-start:active {
  transform: scale(0.98);
  box-shadow: 0 2px 8px rgba(52, 199, 89, 0.3);
}

.btn-payment {
  background: linear-gradient(135deg, #007aff 0%, #0051d5 100%);
  color: white;
  box-shadow: 0 4px 12px rgba(0, 122, 255, 0.3);
}

.btn-payment:active {
  transform: scale(0.98);
  box-shadow: 0 2px 8px rgba(0, 122, 255, 0.3);
}

.btn-start svg, .btn-payment svg {
  flex-shrink: 0;
}

/* Success Card (reused from existing) */
.success-card {
  background: #ffffff;
  border-radius: 24px;
  padding: 40px 32px;
  text-align: center;
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
  border: 0.5px solid rgba(0, 0, 0, 0.08);
  backdrop-filter: blur(20px);
}

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

.success-card .status-icon {
  background: linear-gradient(135deg, #34c759 0%, #30d158 100%);
  box-shadow: 0 4px 16px rgba(52, 199, 89, 0.3);
}

.success-card h2 {
  margin: 0 0 12px;
  font-size: 28px;
  font-weight: 600;
  color: #34c759;
  letter-spacing: -0.4px;
}

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

.btn-action {
  padding: 16px 32px;
  background: linear-gradient(135deg, #007aff 0%, #0051d5 100%);
  color: white;
  border: none;
  border-radius: 14px;
  font-size: 17px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s ease;
  box-shadow: 0 4px 12px rgba(0, 122, 255, 0.3);
  min-width: 200px;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 10px;
}

.btn-action:active {
  transform: scale(0.98);
  box-shadow: 0 2px 8px rgba(0, 122, 255, 0.3);
}

.btn-action svg {
  flex-shrink: 0;
}

/* Warning Card */
.warning-card {
  background: #ffffff;
  border-radius: 24px;
  padding: 40px 32px;
  text-align: center;
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
  border: 0.5px solid rgba(0, 0, 0, 0.08);
  backdrop-filter: blur(20px);
}

.warning-card .status-icon {
  background: linear-gradient(135deg, #ff9500 0%, #ff9f0a 100%);
  box-shadow: 0 4px 16px rgba(255, 149, 0, 0.3);
}

.warning-card h2 {
  margin: 0 0 12px;
  font-size: 28px;
  font-weight: 600;
  color: #ff9500;
  letter-spacing: -0.4px;
}

.warning-card p {
  color: #86868b;
  margin: 0 0 32px;
  line-height: 1.5;
  font-size: 17px;
}

/* Error Card */
.error-card {
  background: #ffffff;
  border-radius: 24px;
  padding: 40px 32px;
  text-align: center;
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
  border: 0.5px solid rgba(0, 0, 0, 0.08);
  backdrop-filter: blur(20px);
}

.error-card .status-icon {
  background: linear-gradient(135deg, #ff3b30 0%, #ff2d55 100%);
  box-shadow: 0 4px 16px rgba(255, 59, 48, 0.3);
}

.error-card h2 {
  margin: 0 0 12px;
  font-size: 28px;
  font-weight: 600;
  color: #ff3b30;
  letter-spacing: -0.4px;
}

.error-card p {
  color: #86868b;
  margin: 0 0 32px;
  line-height: 1.5;
  font-size: 17px;
}

/* Responsive Design */
@media (max-width: 480px) {
  .validation-card, .validation-loading, .success-card, .warning-card, .error-card {
    margin: 16px;
    padding: 24px 20px;
  }
  
  .action-buttons {
    flex-direction: column;
  }
  
  .btn-start, .btn-payment {
    min-width: 100%;
  }
}

/* Dark Mode Support */
@media (prefers-color-scheme: dark) {
  .qr-validation-view {
    background: #000000;
  }

  .validation-card, .success-card, .warning-card, .error-card {
    background: #1c1c1e;
    border-color: rgba(255, 255, 255, 0.1);
  }

  .customer-info h2, .success-card h2 {
    color: #ffffff;
  }

  .customer-info p, .validation-loading p, .warning-card p, .error-card p {
    color: #a1a1a6;
  }

  .appointments-section h3 {
    color: #ffffff;
  }

  .appointment-item {
    background: #2c2c2e;
    border-color: rgba(255, 255, 255, 0.1);
  }

  .service-name {
    color: #ffffff;
  }

  .employee-name {
    color: #a1a1a6;
  }

  .appointment-time {
    color: #a1a1a6;
  }

  .service-price {
    color: #0a84ff;
  }

  .total-section {
    background: #2c2c2e;
    border-color: rgba(255, 255, 255, 0.1);
  }

  .total-amount {
    color: #ffffff;
  }

  .existing-sale-notice {
    color: #ff9500;
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

  .appointment-item {
    border-color: rgba(255, 255, 255, 0.1);
  }
}
</style>