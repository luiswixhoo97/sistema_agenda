<script setup lang="ts">
import { ref, onMounted, computed, watch } from 'vue'
import { useCitasStore } from '@/stores/citas'
import AppIcons from './AppIcons.vue'

const store = useCitasStore()

const confirmando = ref(false)
const exito = ref(false)
const otpDigits = ref(['', '', '', '', '', ''])
const otpRefs = ref<(HTMLInputElement | null)[]>([])

const esModoMultiples = computed(() => store.modoMultiplesEmpleados)

const profesionalesTexto = computed(() => {
  if (!esModoMultiples.value) return store.empleadoSeleccionado?.nombre || ''
  return store.empleadosPorServicio.map(ep => ep.empleadoNombre).join(', ')
})

const puedeConfirmar = computed(() => {
  if (store.anticipoInfo.requiere_anticipo) {
    return store.metodoPagoAnticipo !== null && store.otpCodigo.length === 6
  }
  return store.otpCodigo.length === 6
})

const debeMostrarOtp = computed(() => {
  if (store.anticipoInfo.requiere_anticipo) {
    return store.metodoPagoAnticipo !== null
  }
  return true
})

onMounted(async () => {
  if (!store.otpEnviado) {
    if (!store.anticipoInfo.requiere_anticipo) {
      await store.enviarOtpConfirmacion()
    } else if (store.metodoPagoAnticipo !== null) {
      await store.enviarOtpConfirmacion()
    }
  }
})

watch(() => store.metodoPagoAnticipo, async (nuevoMetodo) => {
  if (store.anticipoInfo.requiere_anticipo && nuevoMetodo !== null && !store.otpEnviado) {
    await store.enviarOtpConfirmacion()
  }
})

function handleOtpInput(index: number, event: Event) {
  const input = event.target as HTMLInputElement
  const value = input.value.replace(/\D/g, '')
  
  otpDigits.value[index] = value.charAt(0) || ''
  store.otpCodigo = otpDigits.value.join('')
  
  if (value && index < 5) {
    otpRefs.value[index + 1]?.focus()
  }
}

function handleOtpKeydown(index: number, event: KeyboardEvent) {
  if (event.key === 'Backspace' && !otpDigits.value[index] && index > 0) {
    otpRefs.value[index - 1]?.focus()
  }
}

function handlePaste(event: ClipboardEvent) {
  event.preventDefault()
  const paste = event.clipboardData?.getData('text') || ''
  const digits = paste.replace(/\D/g, '').split('').slice(0, 6)
  
  digits.forEach((digit, i) => {
    if (i < 6) otpDigits.value[i] = digit
  })
  
  store.otpCodigo = otpDigits.value.join('')
  
  const emptyIndex = otpDigits.value.findIndex(d => !d)
  if (emptyIndex >= 0) {
    otpRefs.value[emptyIndex]?.focus()
  } else {
    otpRefs.value[5]?.focus()
  }
}

async function reenviarOtp() {
  otpDigits.value = ['', '', '', '', '', '']
  store.otpCodigo = ''
  await store.enviarOtpConfirmacion()
  otpRefs.value[0]?.focus()
}

async function confirmar() {
  if (store.otpCodigo.length !== 6) {
    store.error = 'Ingresa el código de 6 dígitos'
    return
  }

  if (store.anticipoInfo.requiere_anticipo && !store.metodoPagoAnticipo) {
    store.error = 'Debes seleccionar un método de pago para el anticipo'
    return
  }

  confirmando.value = true
  store.clearError()
  
  const resultado = await store.agendarCita()
  confirmando.value = false
  
  if (resultado) {
    exito.value = true
  }
}

function nuevaCita() {
  store.reiniciarAgendamiento()
}

function formatHora(hora: string): string {
  if (!hora) return ''
  const [hours, minutes] = hora.split(':')
  if (!hours) return ''
  const hour = parseInt(hours)
  const ampm = hour >= 12 ? 'PM' : 'AM'
  const hour12 = hour % 12 || 12
  return `${hour12}:${minutes} ${ampm}`
}

function seleccionarTransferencia() {
  store.seleccionarMetodoPagoAnticipo('transferencia')
}
</script>

<template>
  <div class="paso-confirmacion">
    <!-- SUCCESS STATE -->
    <template v-if="exito">
      <div class="success-header">
        <div class="success-icon-small">
          <AppIcons name="check" :size="30" />
        </div>
        <h1 v-if="!esModoMultiples">¡Cita Agendada!</h1>
        <h1 v-else>¡Citas Agendadas!</h1>
        <p v-if="!esModoMultiples">Tu cita ha sido confirmada exitosamente</p>
        <p v-else>Tus {{ store.citasMultiples.length }} citas han sido confirmadas exitosamente</p>
      </div>

      <div class="card success-card">
        <div class="whatsapp-notice">
          <div class="whatsapp-icon">
            <AppIcons name="whatsapp" :size="20" />
          </div>
          <span>Te enviaremos los detalles por WhatsApp</span>
        </div>

        <div class="divider"></div>

        <!-- Single Appointment Details -->
        <template v-if="!esModoMultiples">
          <div class="success-details-list">
            <div class="success-detail-row">
              <div class="detail-icon-success">
                <AppIcons name="user" :size="18" />
              </div>
              <div class="detail-content-success">
                <span class="detail-label-success">Cliente</span>
                <span class="detail-value-success">{{ store.datosCliente.nombre }}</span>
              </div>
            </div>
            
            <div class="success-detail-row">
              <div class="detail-icon-success">
                <AppIcons name="phone" :size="18" />
              </div>
              <div class="detail-content-success">
                <span class="detail-label-success">Teléfono</span>
                <span class="detail-value-success">+52 {{ store.datosCliente.telefono }}</span>
              </div>
            </div>
            
            <div class="success-detail-row">
              <div class="detail-icon-success">
                <AppIcons name="user-check" :size="18" />
              </div>
              <div class="detail-content-success">
                <span class="detail-label-success">Profesional</span>
                <span class="detail-value-success">{{ store.empleadoSeleccionado?.nombre }}</span>
              </div>
            </div>
            
            <div class="success-detail-row">
              <div class="detail-icon-success">
                <AppIcons name="calendar" :size="18" />
              </div>
              <div class="detail-content-success">
                <span class="detail-label-success">Fecha</span>
                <span class="detail-value-success">{{ store.fechaSeleccionada }}</span>
              </div>
            </div>
            
            <div class="success-detail-row">
              <div class="detail-icon-success">
                <AppIcons name="clock" :size="18" />
              </div>
              <div class="detail-content-success">
                <span class="detail-label-success">Hora</span>
                <span class="detail-value-success">{{ formatHora(store.horaSeleccionada || '') }}</span>
              </div>
            </div>
            
            <div class="divider"></div>
            
            <div class="success-detail-row highlight-row">
              <div class="detail-icon-success highlight-icon">
                <AppIcons name="dollar" :size="18" />
              </div>
              <div class="detail-content-success">
                <span class="detail-label-success">Total</span>
                <span class="detail-value-success total-price">${{ Number(store.totalPrecio || 0).toFixed(0) }}</span>
              </div>
            </div>
          </div>
        </template>

        <!-- Multiple Appointments Details -->
        <template v-else>
          <div class="client-info">
            <div class="info-row">
              <span class="info-label">Cliente</span>
              <span class="info-value">{{ store.datosCliente.nombre }}</span>
            </div>
            <div class="info-row">
              <span class="info-label">Teléfono</span>
              <span class="info-value">+52 {{ store.datosCliente.telefono }}</span>
            </div>
          </div>

          <div class="divider"></div>

          <div class="appointments-header">
            <AppIcons name="calendar-check" :size="20" />
            <h4>{{ store.citasMultiples.length }} citas coordinadas</h4>
          </div>

          <div class="appointments-list">
            <div v-for="(cita, index) in store.citasMultiples" :key="cita.id" class="appointment-item">
              <div class="appointment-number">{{ index + 1 }}</div>
              <div class="appointment-content">
                <div class="appointment-service">{{ cita.servicios[0]?.nombre }}</div>
                <div class="appointment-details">
                  <span><AppIcons name="user" :size="12" /> {{ cita.empleado.nombre }}</span>
                  <span><AppIcons name="clock" :size="12" /> {{ cita.hora_texto }}</span>
                </div>
                <div class="appointment-price">${{ cita.precio_final }}</div>
              </div>
            </div>
          </div>

          <div class="total-row">
            <span>Total</span>
            <span class="total">${{ Number(store.totalPrecio || 0).toFixed(0) }}</span>
          </div>
        </template>
      </div>

      <button class="btn-primary" @click="nuevaCita">
        <AppIcons name="plus" :size="18" />
        Agendar otra cita
      </button>
    </template>

    <!-- CONFIRMATION STATE -->
    <template v-else>
      <!-- Header -->
      <div class="page-header">
        <div class="header-icon">
          <AppIcons name="clipboard-check" :size="20" />
        </div>
        <h1 v-if="!esModoMultiples">Confirmar Cita</h1>
        <h1 v-else>Confirmar Citas</h1>
        <p>Revisa los detalles y confirma</p>
      </div>

      <!-- Client Summary -->
      <div class="card summary-card">
        <div class="card-header">
          <div class="header-icon-small">
            <AppIcons name="user" :size="16" />
          </div>
          <h4>Tus Datos</h4>
        </div>
        <div class="summary-content">
          <p class="client-name">{{ store.datosCliente.nombre }}</p>
          <div class="client-contact">
            <div class="contact-item">
              <AppIcons name="phone" :size="14" />
              <span>+52 {{ store.datosCliente.telefono }}</span>
            </div>
            <div v-if="store.datosCliente.email" class="contact-item">
              <AppIcons name="mail" :size="14" />
              <span>{{ store.datosCliente.email }}</span>
            </div>
          </div>
        </div>
      </div>

      <!-- Services (Single Mode) -->
      <div v-if="!esModoMultiples" class="card services-card">
        <div class="card-header">
          <div class="header-icon-small">
            <AppIcons name="spa" :size="16" />
          </div>
          <h4>Servicios</h4>
        </div>
        <div class="services-list">
          <div v-for="servicio in store.serviciosSeleccionados" :key="servicio.id" class="service-item">
            <div class="service-info">
              <span class="service-name">{{ servicio.nombre }}</span>
            </div>
            <span class="service-price">${{ Number(servicio.precio).toFixed(0) }}</span>
          </div>
        </div>
        <div class="divider"></div>
        <div class="total-row">
          <span class="total-label">Total</span>
          <span class="total-value">${{ Number(store.totalPrecio || 0).toFixed(0) }}</span>
        </div>
      </div>

      <!-- Appointment (Single Mode) -->
      <div v-if="!esModoMultiples" class="card appointment-card">
        <div class="card-header">
          <div class="header-icon-small">
            <AppIcons name="calendar-check" :size="16" />
          </div>
          <h4>Tu Cita</h4>
        </div>
        <div class="appointment-details-list">
          <div class="appointment-detail-row">
            <div class="detail-icon">
              <AppIcons name="user-check" :size="18" />
            </div>
            <div class="detail-content">
              <span class="detail-label">Profesional</span>
              <span class="detail-value">{{ store.empleadoSeleccionado?.nombre }}</span>
            </div>
          </div>
          <div class="appointment-detail-row">
            <div class="detail-icon">
              <AppIcons name="calendar" :size="18" />
            </div>
            <div class="detail-content">
              <span class="detail-label">Fecha</span>
              <span class="detail-value">{{ store.fechaSeleccionada }}</span>
            </div>
          </div>
          <div class="appointment-detail-row">
            <div class="detail-icon">
              <AppIcons name="clock" :size="18" />
            </div>
            <div class="detail-content">
              <span class="detail-label">Hora</span>
              <span class="detail-value">{{ formatHora(store.horaSeleccionada || '') }}</span>
            </div>
          </div>
        </div>
      </div>

      <!-- Multiple Appointments -->
      <div v-if="esModoMultiples && store.slotCoordinadoSeleccionado" class="card">
        <h4><AppIcons name="calendar-check" :size="18" /> {{ store.serviciosSeleccionados.length }} Citas Coordinadas</h4>
        
        <div class="coordinated-date">
          <AppIcons name="calendar" :size="16" />
          {{ store.fechaSeleccionada }}
        </div>

        <div class="confirmation-timeline">
          <div v-for="(servicio, index) in store.slotCoordinadoSeleccionado.servicios" :key="index" class="timeline-item">
            <div class="timeline-time">{{ formatHora(servicio.hora_inicio) }}</div>
            <div class="timeline-dot"></div>
            <div class="timeline-content">
              <div class="timeline-service">{{ servicio.servicio_nombre }}</div>
              <div class="timeline-employee">
                <AppIcons name="user" :size="12" />
                {{ servicio.empleado_nombre }}
              </div>
              <div class="timeline-duration">
                <AppIcons name="clock" :size="11" />
                {{ servicio.duracion }} min
              </div>
            </div>
            <div class="timeline-price">
              ${{ store.serviciosSeleccionados.find(s => s.id === servicio.servicio_id)?.precio || 0 }}
            </div>
          </div>
        </div>

        <div class="total-row">
          <span>Total</span>
          <span class="total">${{ Number(store.totalPrecio || 0).toFixed(0) }}</span>
        </div>
      </div>

      <!-- Advance Payment Section -->
      <div v-if="store.anticipoInfo.requiere_anticipo" class="advance-container">
        <!-- Amount Display -->
        <div class="advance-amount-display">
          <div class="amount-icon">
            <AppIcons name="credit-card" :size="24" />
          </div>
          <div class="amount-content">
            <span class="amount-label">Anticipo Requerido</span>
            <span class="amount-value">${{ Number(store.anticipoInfo.monto_anticipo).toFixed(0) }}</span>
          </div>
        </div>
        
        <!-- Payment Method -->
        <div class="payment-method-container">
          <span class="method-label">Método de pago</span>
          
          <button 
            class="payment-method-option"
            :class="{ 'selected': store.metodoPagoAnticipo === 'transferencia' }"
            @click="seleccionarTransferencia"
          >
            <div class="method-info">
              <div class="method-icon">
                <AppIcons name="building" :size="20" />
              </div>
              <div class="method-details">
                <span class="method-name">Transferencia Bancaria</span>
                <span class="method-description">Datos enviados por WhatsApp</span>
              </div>
            </div>
            <div class="method-indicator">
              <div class="radio-button" :class="{ 'checked': store.metodoPagoAnticipo === 'transferencia' }">
                <div class="radio-dot"></div>
              </div>
            </div>
          </button>
        </div>

        <!-- Notice -->
        <Transition name="fade">
          <div v-if="store.metodoPagoAnticipo === 'transferencia'" class="advance-notice">
            <div class="notice-icon">
              <AppIcons name="clock" :size="18" />
            </div>
            <div class="notice-text">
              Tienes <strong>24 horas</strong> para realizar el depósito
            </div>
          </div>
        </Transition>
      </div>

      <!-- OTP Section -->
      <div v-if="debeMostrarOtp" class="card otp-card">
        <div class="otp-header">
          <div class="shield-icon">
            <AppIcons name="shield" :size="28" />
          </div>
          <h4>Código de Verificación</h4>
          <p>Enviamos un código de 6 dígitos a</p>
          <strong class="phone-number">+52 {{ store.datosCliente.telefono }}</strong>
        </div>

        <!-- Debug Code -->
        <div v-if="store.otpDebug" class="debug-code">
          <AppIcons name="info" :size="14" />
          <span>Código de prueba:</span>
          <strong>{{ store.otpDebug }}</strong>
        </div>

        <!-- OTP Inputs -->
        <div class="otp-inputs-container">
          <div class="otp-inputs">
            <input
              v-for="(digit, index) in otpDigits"
              :key="index"
              :ref="el => otpRefs[index] = el as HTMLInputElement"
              type="text"
              inputmode="numeric"
              maxlength="1"
              class="otp-input"
              :class="{ 'filled': digit !== '' }"
              :value="digit"
              @input="handleOtpInput(index, $event)"
              @keydown="handleOtpKeydown(index, $event)"
              @paste="handlePaste"
            />
          </div>
        </div>

        <button class="btn-resend" @click="reenviarOtp">
          <AppIcons name="refresh" :size="16" />
          <span>Reenviar código</span>
        </button>

        <!-- Error -->
        <Transition name="fade">
          <div v-if="store.error" class="error-box">
            <AppIcons name="alert-circle" :size="16" />
            <span>{{ store.error }}</span>
          </div>
        </Transition>

        <!-- Confirm Button -->
        <button 
          class="btn-confirm"
          :class="{ 'loading': confirmando }"
          :disabled="confirmando || !puedeConfirmar"
          @click="confirmar"
        >
          <span v-if="confirmando" class="btn-content">
            <AppIcons name="loader" :size="18" class="spinner" />
            <span>Confirmando...</span>
          </span>
          <span v-else-if="store.anticipoInfo.requiere_anticipo && !store.metodoPagoAnticipo" class="btn-content">
            <AppIcons name="lock" :size="18" />
            <span>Selecciona método de pago</span>
          </span>
          <span v-else-if="store.otpCodigo.length !== 6" class="btn-content">
            <AppIcons name="shield" :size="18" />
            <span>Completa el código</span>
          </span>
          <span v-else-if="!esModoMultiples" class="btn-content">
            <AppIcons name="check" :size="18" />
            <span>Confirmar Cita</span>
          </span>
          <span v-else class="btn-content">
            <AppIcons name="check" :size="18" />
            <span>Confirmar {{ store.serviciosSeleccionados.length }} Citas</span>
          </span>
        </button>
      </div>

      <!-- Pending Advance Message -->
      <div v-if="store.anticipoInfo.requiere_anticipo && store.metodoPagoAnticipo === null" class="card pending-card">
        <div class="pending-message">
          <AppIcons name="lock" :size="24" />
          <p>Selecciona un método de pago para continuar</p>
        </div>
      </div>

      <!-- Back Button -->
      <button class="btn-secondary" @click="store.pasoAnterior()">
        <AppIcons name="arrow-left" :size="18" />
        Volver
      </button>
    </template>
  </div>
</template>

<style scoped>
.paso-confirmacion {
  padding: 20px 16px;
  padding-bottom: 100px;
  font-family: -apple-system, BlinkMacSystemFont, 'SF Pro Display', system-ui, sans-serif;
  -webkit-font-smoothing: antialiased;
}

/* Header */
.page-header {
  padding: 20px 16px 24px;
  text-align: center;
  background: rgba(255, 255, 255, 0.6);
  backdrop-filter: blur(20px);
  -webkit-backdrop-filter: blur(20px);
  border-radius: 20px;
  margin-bottom: 20px;
  border: 1px solid rgba(0, 0, 0, 0.06);
  box-shadow: 0 2px 12px rgba(0, 0, 0, 0.04);
}

.theme-dark .page-header {
  background: rgba(28, 28, 30, 0.6);
  border-color: rgba(255, 255, 255, 0.08);
  box-shadow: 0 2px 12px rgba(0, 0, 0, 0.2);
}

.header-icon {
  width: 44px;
  height: 44px;
  border-radius: 12px;
  background: rgba(0, 122, 255, 0.08);
  display: flex;
  align-items: center;
  justify-content: center;
  margin: 0 auto 14px;
  color: #007aff;
  border: 1px solid rgba(0, 122, 255, 0.12);
}

.theme-dark .header-icon {
  background: rgba(0, 122, 255, 0.12);
  border-color: rgba(0, 122, 255, 0.2);
}

.page-header h1 {
  font-size: 22px;
  font-weight: 600;
  color: #1d1d1f;
  margin: 0 0 6px;
  letter-spacing: -0.02em;
  line-height: 1.3;
}

.theme-dark .page-header h1 {
  color: #f5f5f7;
}

.page-header p {
  font-size: 15px;
  color: #86868b;
  margin: 0;
  letter-spacing: 0;
  line-height: 1.4;
}

.theme-dark .page-header p {
  color: #98989d;
}

/* Card */
.card {
  background: rgba(255, 255, 255, 0.9);
  backdrop-filter: blur(30px);
  -webkit-backdrop-filter: blur(30px);
  border-radius: 20px;
  padding: 24px;
  margin-bottom: 16px;
  border: 1px solid rgba(0, 0, 0, 0.06);
  box-shadow: 0 2px 16px rgba(0, 0, 0, 0.04);
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.theme-dark .card {
  background: rgba(28, 28, 30, 0.9);
  border-color: rgba(255, 255, 255, 0.08);
  box-shadow: 0 2px 16px rgba(0, 0, 0, 0.3);
}

/* Card Header */
.card-header {
  display: flex;
  align-items: center;
  gap: 12px;
  margin-bottom: 20px;
}

.header-icon-small {
  width: 36px;
  height: 36px;
  border-radius: 10px;
  background: rgba(0, 122, 255, 0.1);
  display: flex;
  align-items: center;
  justify-content: center;
  color: #007aff;
}

.theme-dark .header-icon-small {
  background: rgba(0, 122, 255, 0.15);
}

.card-header h4 {
  font-size: 17px;
  font-weight: 600;
  color: #1d1d1f;
  margin: 0;
  letter-spacing: -0.01em;
}

.theme-dark .card-header h4 {
  color: #f5f5f7;
}

/* Success Header */
.success-header {
  padding: 20px 16px 24px;
  text-align: center;
  background: rgba(255, 255, 255, 0.6);
  backdrop-filter: blur(20px);
  -webkit-backdrop-filter: blur(20px);
  border-radius: 20px;
  margin-bottom: 20px;
  border: 1px solid rgba(0, 0, 0, 0.06);
  box-shadow: 0 2px 12px rgba(0, 0, 0, 0.04);
}

.theme-dark .success-header {
  background: rgba(28, 28, 30, 0.6);
  border-color: rgba(255, 255, 255, 0.08);
  box-shadow: 0 2px 12px rgba(0, 0, 0, 0.2);
}

.success-icon-small {
  width: 44px;
  height: 44px;
  border-radius: 12px;
  background: rgba(52, 199, 89, 0.1);
  border: 1px solid rgba(52, 199, 89, 0.15);
  display: flex;
  align-items: center;
  justify-content: center;
  margin: 0 auto 14px;
  color: #34c759;
}

.theme-dark .success-icon-small {
  background: rgba(52, 199, 89, 0.15);
  border-color: rgba(52, 199, 89, 0.2);
}

.success-header h1 {
  font-size: 22px;
  font-weight: 600;
  color: #1d1d1f;
  margin: 0 0 6px;
  letter-spacing: -0.02em;
  line-height: 1.3;
}

.theme-dark .success-header h1 {
  color: #f5f5f7;
}

.success-header p {
  font-size: 15px;
  color: #86868b;
  margin: 0;
  letter-spacing: 0;
  line-height: 1.4;
}

.theme-dark .success-header p {
  color: #98989d;
}

/* WhatsApp Notice */
.whatsapp-notice {
  display: flex;
  align-items: center;
  gap: 14px;
  padding: 16px;
  background: rgba(37, 211, 102, 0.08);
  border-radius: 14px;
  border: 1px solid rgba(37, 211, 102, 0.15);
  margin-bottom: 20px;
}

.theme-dark .whatsapp-notice {
  background: rgba(37, 211, 102, 0.12);
  border-color: rgba(37, 211, 102, 0.2);
}

.whatsapp-icon {
  width: 36px;
  height: 36px;
  border-radius: 10px;
  background: #25d366;
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
  flex-shrink: 0;
}

.whatsapp-notice span {
  flex: 1;
  font-size: 15px;
  font-weight: 500;
  color: #1d1d1f;
  letter-spacing: -0.01em;
}

.theme-dark .whatsapp-notice span {
  color: #f5f5f7;
}

/* Client Summary */
.summary-content {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.client-name {
  font-size: 20px;
  font-weight: 600;
  color: #1d1d1f;
  margin: 0;
  letter-spacing: -0.02em;
}

.theme-dark .client-name {
  color: #f5f5f7;
}

.client-contact {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.contact-item {
  display: flex;
  align-items: center;
  gap: 10px;
  font-size: 15px;
  color: #86868b;
}

.theme-dark .contact-item {
  color: #98989d;
}

.contact-item svg {
  color: #007aff;
  flex-shrink: 0;
}

/* Services List */
.services-list {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.service-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 14px;
  background: rgba(0, 122, 255, 0.04);
  border-radius: 12px;
  transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
}

.theme-dark .service-item {
  background: rgba(0, 122, 255, 0.08);
}

.service-info {
  flex: 1;
}

.service-name {
  font-size: 15px;
  font-weight: 500;
  color: #1d1d1f;
  letter-spacing: -0.01em;
}

.theme-dark .service-name {
  color: #f5f5f7;
}

.service-price {
  font-size: 17px;
  font-weight: 700;
  color: #007aff;
  letter-spacing: -0.01em;
}

.divider {
  height: 1px;
  background: rgba(0, 0, 0, 0.06);
  margin: 16px 0;
}

.theme-dark .divider {
  background: rgba(255, 255, 255, 0.08);
}

.total-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding-top: 4px;
}

.total-label {
  font-size: 17px;
  font-weight: 600;
  color: #1d1d1f;
  letter-spacing: -0.01em;
}

.theme-dark .total-label {
  color: #f5f5f7;
}

.total-value {
  font-size: 24px;
  font-weight: 700;
  color: #007aff;
  letter-spacing: -0.02em;
}

/* Appointment Details List */
.appointment-details-list {
  display: flex;
  flex-direction: column;
  gap: 16px;
}

.appointment-detail-row {
  display: flex;
  align-items: center;
  gap: 14px;
  padding: 16px;
  background: rgba(0, 122, 255, 0.04);
  border-radius: 14px;
  transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
}

.theme-dark .appointment-detail-row {
  background: rgba(0, 122, 255, 0.08);
}

.detail-icon {
  width: 40px;
  height: 40px;
  border-radius: 10px;
  background: rgba(0, 122, 255, 0.1);
  display: flex;
  align-items: center;
  justify-content: center;
  color: #007aff;
  flex-shrink: 0;
}

.theme-dark .detail-icon {
  background: rgba(0, 122, 255, 0.15);
}

.detail-content {
  flex: 1;
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.detail-label {
  font-size: 13px;
  color: #86868b;
  font-weight: 500;
  text-transform: uppercase;
  letter-spacing: 0.05em;
}

.theme-dark .detail-label {
  color: #98989d;
}

.detail-value {
  font-size: 17px;
  font-weight: 600;
  color: #1d1d1f;
  letter-spacing: -0.01em;
}

.theme-dark .detail-value {
  color: #f5f5f7;
}

/* Info Chips (for other uses) */
.info-chip {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 12px 16px;
  background: rgba(0, 122, 255, 0.06);
  border: 1px solid rgba(0, 122, 255, 0.1);
  border-radius: 12px;
  font-size: 15px;
  font-weight: 500;
  color: #1d1d1f;
  transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
  letter-spacing: -0.01em;
}

.theme-dark .info-chip {
  background: rgba(0, 122, 255, 0.1);
  border-color: rgba(0, 122, 255, 0.15);
  color: #f5f5f7;
}

.info-chip svg {
  color: #007aff;
  flex-shrink: 0;
}

/* Coordinated Date */
.coordinated-date {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 12px 16px;
  background: rgba(0, 122, 255, 0.08);
  border-radius: 10px;
  color: #007aff;
  font-weight: 600;
  font-size: 15px;
  margin-bottom: 20px;
}

/* Confirmation Timeline */
.confirmation-timeline {
  display: flex;
  flex-direction: column;
  gap: 0;
  margin-bottom: 16px;
}

.timeline-item {
  display: flex;
  align-items: flex-start;
  gap: 12px;
  padding: 12px 0;
  position: relative;
}

.timeline-item:not(:last-child)::after {
  content: '';
  position: absolute;
  left: 68px;
  top: 28px;
  bottom: -4px;
  width: 2px;
  background: rgba(0,0,0,0.08);
}

.theme-dark .timeline-item:not(:last-child)::after {
  background: rgba(255,255,255,0.1);
}

.timeline-time {
  width: 56px;
  font-size: 13px;
  font-weight: 600;
  color: #1d1d1f;
  text-align: right;
}

.theme-dark .timeline-time {
  color: #f5f5f7;
}

.timeline-dot {
  width: 10px;
  height: 10px;
  border-radius: 5px;
  background: #007aff;
  margin-top: 4px;
  z-index: 1;
  border: 2px solid white;
}

.theme-dark .timeline-dot {
  border-color: rgba(28, 28, 30, 0.95);
}

.timeline-content {
  flex: 1;
}

.timeline-service {
  font-size: 14px;
  font-weight: 600;
  color: #1d1d1f;
  margin-bottom: 2px;
}

.theme-dark .timeline-service {
  color: #f5f5f7;
}

.timeline-employee {
  font-size: 12px;
  color: #86868b;
  display: flex;
  align-items: center;
  gap: 4px;
}

.timeline-employee svg {
  color: #007aff;
}

.timeline-duration {
  font-size: 11px;
  color: #86868b;
  display: flex;
  align-items: center;
  gap: 4px;
  margin-top: 2px;
}

.timeline-price {
  font-size: 15px;
  font-weight: 600;
  color: #007aff;
}

/* Advance Payment Section */
.advance-container {
  background: rgba(255, 255, 255, 0.8);
  backdrop-filter: blur(20px);
  -webkit-backdrop-filter: blur(20px);
  border: 1px solid rgba(0, 0, 0, 0.06);
  border-radius: 20px;
  padding: 24px;
  margin-bottom: 20px;
}

.theme-dark .advance-container {
  background: rgba(28, 28, 30, 0.8);
  border-color: rgba(255, 255, 255, 0.08);
}

/* Amount Display */
.advance-amount-display {
  display: flex;
  align-items: center;
  gap: 16px;
  padding: 20px;
  background: linear-gradient(135deg, rgba(0, 122, 255, 0.06) 0%, rgba(0, 122, 255, 0.02) 100%);
  border-radius: 16px;
  margin-bottom: 24px;
  border: 1px solid rgba(0, 122, 255, 0.1);
}

.theme-dark .advance-amount-display {
  background: linear-gradient(135deg, rgba(0, 122, 255, 0.12) 0%, rgba(0, 122, 255, 0.04) 100%);
  border-color: rgba(0, 122, 255, 0.2);
}

.amount-icon {
  width: 48px;
  height: 48px;
  border-radius: 14px;
  background: #007aff;
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
  flex-shrink: 0;
  box-shadow: 0 4px 12px rgba(0, 122, 255, 0.3);
}

.amount-content {
  flex: 1;
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.amount-label {
  font-size: 13px;
  color: #86868b;
  font-weight: 500;
  letter-spacing: 0.01em;
}

.theme-dark .amount-label {
  color: #98989d;
}

.amount-value {
  font-size: 32px;
  font-weight: 700;
  color: #007aff;
  line-height: 1;
  letter-spacing: -0.02em;
}

/* Payment Method */
.payment-method-container {
  margin-bottom: 16px;
}

.method-label {
  display: block;
  font-size: 13px;
  color: #86868b;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.05em;
  margin-bottom: 12px;
}

.theme-dark .method-label {
  color: #98989d;
}

.payment-method-option {
  width: 100%;
  padding: 16px;
  border-radius: 16px;
  border: 1.5px solid rgba(0, 0, 0, 0.08);
  background: white;
  display: flex;
  align-items: center;
  justify-content: space-between;
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  cursor: pointer;
}

.theme-dark .payment-method-option {
  background: rgba(44, 44, 46, 0.6);
  border-color: rgba(255, 255, 255, 0.12);
}

.payment-method-option:hover {
  border-color: rgba(0, 122, 255, 0.4);
  transform: translateY(-1px);
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
}

.theme-dark .payment-method-option:hover {
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.4);
}

.payment-method-option.selected {
  border-color: #007aff;
  background: rgba(0, 122, 255, 0.04);
}

.theme-dark .payment-method-option.selected {
  background: rgba(0, 122, 255, 0.1);
}

.method-info {
  display: flex;
  align-items: center;
  gap: 14px;
  flex: 1;
}

.method-icon {
  width: 44px;
  height: 44px;
  border-radius: 12px;
  background: rgba(0, 122, 255, 0.08);
  display: flex;
  align-items: center;
  justify-content: center;
  color: #007aff;
  flex-shrink: 0;
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.theme-dark .method-icon {
  background: rgba(0, 122, 255, 0.15);
}

.payment-method-option.selected .method-icon {
  background: #007aff;
  color: white;
  transform: scale(1.05);
}

.method-details {
  display: flex;
  flex-direction: column;
  gap: 3px;
}

.method-name {
  font-size: 15px;
  font-weight: 600;
  color: #1d1d1f;
  letter-spacing: -0.01em;
}

.theme-dark .method-name {
  color: #f5f5f7;
}

.method-description {
  font-size: 13px;
  color: #86868b;
  letter-spacing: 0;
}

.theme-dark .method-description {
  color: #98989d;
}

/* Radio Button */
.method-indicator {
  flex-shrink: 0;
}

.radio-button {
  width: 24px;
  height: 24px;
  border-radius: 50%;
  border: 2px solid rgba(0, 0, 0, 0.15);
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  background: white;
}

.theme-dark .radio-button {
  border-color: rgba(255, 255, 255, 0.2);
  background: rgba(44, 44, 46, 0.6);
}

.radio-button.checked {
  border-color: #007aff;
  background: #007aff;
}

.radio-dot {
  width: 8px;
  height: 8px;
  border-radius: 50%;
  background: white;
  transform: scale(0);
  transition: transform 0.2s cubic-bezier(0.4, 0, 0.2, 1);
}

.radio-button.checked .radio-dot {
  transform: scale(1);
}

/* Notice */
.advance-notice {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 14px 16px;
  background: rgba(0, 122, 255, 0.06);
  border-radius: 14px;
  border: 1px solid rgba(0, 122, 255, 0.1);
}

.theme-dark .advance-notice {
  background: rgba(0, 122, 255, 0.12);
  border-color: rgba(0, 122, 255, 0.2);
}

.notice-icon {
  width: 32px;
  height: 32px;
  border-radius: 8px;
  background: rgba(0, 122, 255, 0.1);
  display: flex;
  align-items: center;
  justify-content: center;
  color: #007aff;
  flex-shrink: 0;
}

.theme-dark .notice-icon {
  background: rgba(0, 122, 255, 0.2);
}

.notice-text {
  flex: 1;
  font-size: 14px;
  color: #1d1d1f;
  line-height: 1.4;
}

.theme-dark .notice-text {
  color: #f5f5f7;
}

.notice-text strong {
  font-weight: 700;
  color: #007aff;
}

.advance-notice strong {
  color: #007aff;
}

/* OTP Card */
.otp-card {
  text-align: center;
}

.otp-header {
  margin-bottom: 32px;
}

.shield-icon {
  width: 64px;
  height: 64px;
  border-radius: 18px;
  background: linear-gradient(135deg, rgba(0, 122, 255, 0.1) 0%, rgba(88, 86, 214, 0.1) 100%);
  border: 1px solid rgba(0, 122, 255, 0.15);
  display: flex;
  align-items: center;
  justify-content: center;
  margin: 0 auto 20px;
  color: #007aff;
}

.theme-dark .shield-icon {
  background: linear-gradient(135deg, rgba(0, 122, 255, 0.15) 0%, rgba(88, 86, 214, 0.15) 100%);
  border-color: rgba(0, 122, 255, 0.2);
}

.otp-header h4 {
  font-size: 22px;
  font-weight: 700;
  color: #1d1d1f;
  margin: 0 0 12px;
  letter-spacing: -0.02em;
}

.theme-dark .otp-header h4 {
  color: #f5f5f7;
}

.otp-header p {
  font-size: 15px;
  color: #86868b;
  margin: 0 0 6px;
  line-height: 1.4;
  letter-spacing: -0.01em;
}

.theme-dark .otp-header p {
  color: #98989d;
}

.phone-number {
  font-size: 17px;
  font-weight: 600;
  color: #007aff;
  display: block;
  letter-spacing: 0.02em;
}

/* Debug Code */
.debug-code {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  background: rgba(255, 149, 0, 0.1);
  border: 1px solid rgba(255, 149, 0, 0.2);
  padding: 12px 16px;
  border-radius: 12px;
  margin-bottom: 24px;
  font-size: 14px;
  color: #ff9500;
}

.theme-dark .debug-code {
  background: rgba(255, 149, 0, 0.15);
  border-color: rgba(255, 149, 0, 0.25);
}

.debug-code strong {
  font-weight: 700;
  letter-spacing: 0.1em;
}

/* OTP Inputs Container */
.otp-inputs-container {
  margin-bottom: 24px;
}

.otp-inputs {
  display: flex;
  justify-content: center;
  gap: 8px;
  margin-bottom: 0;
}

.otp-input {
  width: 48px;
  height: 56px;
  text-align: center;
  font-size: 24px;
  font-weight: 700;
  border: 2px solid rgba(0, 0, 0, 0.1);
  border-radius: 14px;
  background: white;
  color: #1d1d1f;
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  appearance: none;
  letter-spacing: -0.01em;
  font-family: inherit;
}

.theme-dark .otp-input {
  background: rgba(44, 44, 46, 0.8);
  border-color: rgba(255, 255, 255, 0.15);
  color: #f5f5f7;
}

.otp-input:focus {
  outline: none;
  border-color: #007aff;
  box-shadow: 0 0 0 4px rgba(0, 122, 255, 0.12);
  transform: scale(1.05);
}

.otp-input.filled {
  border-color: #007aff;
  background: rgba(0, 122, 255, 0.04);
}

.theme-dark .otp-input.filled {
  background: rgba(0, 122, 255, 0.12);
  border-color: #007aff;
}

/* Resend Button */
.btn-resend {
  background: none;
  border: none;
  color: #007aff;
  font-size: 15px;
  font-weight: 500;
  cursor: pointer;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  margin-bottom: 24px;
  padding: 10px 16px;
  border-radius: 10px;
  font-family: inherit;
  transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
}

.btn-resend:hover {
  background: rgba(0, 122, 255, 0.06);
}

/* Error Box */
.error-box {
  display: flex;
  align-items: center;
  gap: 12px;
  background: rgba(255, 59, 48, 0.08);
  border: 1px solid rgba(255, 59, 48, 0.15);
  color: #ff3b30;
  padding: 14px 16px;
  border-radius: 14px;
  font-size: 15px;
  margin-bottom: 20px;
  animation: shake 0.4s ease;
}

.theme-dark .error-box {
  background: rgba(255, 59, 48, 0.12);
  border-color: rgba(255, 59, 48, 0.2);
}

.error-box svg {
  flex-shrink: 0;
}

/* Confirm Button */
.btn-confirm {
  width: 100%;
  padding: 18px 24px;
  background: #007AFE;
  color: white;
  border: none;
  border-radius: 14px;
  font-size: 17px;
  font-weight: 600;
  cursor: pointer;
  font-family: inherit;
  transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
  box-shadow: 0 1px 4px rgba(0, 0, 0, 0.1);
}

.theme-dark .btn-confirm {
  background: #007AFE;
}

.btn-confirm:not(:disabled):hover {
  background: #0066d6;
  transform: scale(1.01);
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
}

.theme-dark .btn-confirm:not(:disabled):hover {
  background: #0066d6;
}

.btn-confirm:not(:disabled):active {
  transform: scale(0.99);
  background: #0055b3;
}

.theme-dark .btn-confirm:not(:disabled):active {
  background: #0055b3;
}

.btn-confirm:disabled {
  opacity: 0.4;
  cursor: not-allowed;
  background: rgba(0, 122, 254, 0.3);
  box-shadow: none;
}

.theme-dark .btn-confirm:disabled {
  background: rgba(0, 122, 254, 0.3);
}

.btn-confirm.loading {
  opacity: 0.8;
}

.btn-content {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 10px;
}

/* Primary Button (for success state) */
.btn-primary {
  width: 100%;
  padding: 16px 24px;
  background: #007aff;
  color: white;
  border: none;
  border-radius: 16px;
  font-size: 17px;
  font-weight: 600;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 10px;
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  font-family: inherit;
  box-shadow: 0 4px 12px rgba(0, 122, 255, 0.25);
}

.btn-primary:hover {
  transform: translateY(-2px);
  box-shadow: 0 6px 16px rgba(0, 122, 255, 0.35);
}

.btn-primary:active {
  transform: translateY(0);
}

/* Secondary Button */
.btn-secondary {
  width: 100%;
  padding: 16px 24px;
  background: rgba(0, 0, 0, 0.04);
  color: #1d1d1f;
  border: none;
  border-radius: 16px;
  font-size: 17px;
  font-weight: 600;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 10px;
  margin-top: 12px;
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  font-family: inherit;
}

.theme-dark .btn-secondary {
  background: rgba(255, 255, 255, 0.06);
  color: #f5f5f7;
}

.btn-secondary:hover {
  background: rgba(0, 0, 0, 0.08);
  transform: translateY(-1px);
}

.theme-dark .btn-secondary:hover {
  background: rgba(255, 255, 255, 0.1);
}

.btn-secondary:active {
  transform: translateY(0);
}

.btn-full {
  margin-top: 0;
}

/* Pending Card */
.pending-card {
  border: 2px dashed rgba(0,0,0,0.15);
  background: rgba(0,0,0,0.02);
}

.theme-dark .pending-card {
  border-color: rgba(255,255,255,0.15);
  background: rgba(255,255,255,0.02);
}

.pending-message {
  display: flex;
  align-items: center;
  gap: 12px;
  justify-content: center;
  padding: 12px;
  color: #86868b;
}

.pending-message p {
  margin: 0;
  font-size: 14px;
}

/* Success State */
.success-container {
  text-align: center;
  padding: 40px 20px 30px;
}

.success-icon {
  width: 88px;
  height: 88px;
  border-radius: 22px;
  background: #34c759;
  display: flex;
  align-items: center;
  justify-content: center;
  margin: 0 auto 24px;
  color: white;
  animation: bounceIn 0.5s cubic-bezier(0.34, 1.56, 0.64, 1);
}

@keyframes bounceIn {
  0% { transform: scale(0); }
  50% { transform: scale(1.1); }
  100% { transform: scale(1); }
}

.success-container h1 {
  font-size: 32px;
  font-weight: 600;
  color: #1d1d1f;
  margin: 0 0 8px;
}

.theme-dark .success-container h1 {
  color: #f5f5f7;
}

.success-container p {
  font-size: 17px;
  color: #86868b;
  margin: 0;
}

/* Success Card */
.success-card {
  border: 1px solid rgba(52, 199, 89, 0.2);
}

.whatsapp-notice {
  display: flex;
  align-items: center;
  gap: 10px;
  color: #25d366;
  font-size: 16px;
  font-weight: 500;
  margin-bottom: 12px;
}

.divider {
  height: 1px;
  background: rgba(0,0,0,0.06);
  margin: 16px 0;
}

.theme-dark .divider {
  background: rgba(255,255,255,0.08);
}

/* Details Grid */
.details-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 12px;
}

.detail-item {
  display: flex;
  align-items: flex-start;
  gap: 12px;
  padding: 14px;
  background: rgba(0, 122, 255, 0.04);
  border-radius: 12px;
}

.theme-dark .detail-item {
  background: rgba(0, 122, 255, 0.08);
}

.detail-item svg {
  color: #007aff;
  margin-top: 2px;
}

.detail-item .label {
  display: block;
  font-size: 11px;
  color: #86868b;
  text-transform: uppercase;
  letter-spacing: 0.04em;
}

.detail-item .value {
  display: block;
  font-size: 14px;
  font-weight: 600;
  color: #1d1d1f;
  margin-top: 2px;
}

.theme-dark .detail-item .value {
  color: #f5f5f7;
}

.detail-item.highlight {
  background: rgba(0, 122, 255, 0.08);
  grid-column: 1 / -1;
}

.detail-item .value.price {
  font-size: 22px;
  color: #007aff;
}

/* Success Details List */
.success-details-list {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.success-detail-row {
  display: flex;
  align-items: center;
  gap: 14px;
  padding: 16px;
  background: rgba(0, 122, 255, 0.04);
  border-radius: 14px;
  transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
}

.theme-dark .success-detail-row {
  background: rgba(0, 122, 255, 0.08);
}

.success-detail-row.highlight-row {
  background: rgba(52, 199, 89, 0.08);
}

.theme-dark .success-detail-row.highlight-row {
  background: rgba(52, 199, 89, 0.12);
}

.detail-icon-success {
  width: 40px;
  height: 40px;
  border-radius: 10px;
  background: rgba(0, 122, 255, 0.1);
  display: flex;
  align-items: center;
  justify-content: center;
  color: #007aff;
  flex-shrink: 0;
}

.theme-dark .detail-icon-success {
  background: rgba(0, 122, 255, 0.15);
}

.detail-icon-success.highlight-icon {
  background: rgba(52, 199, 89, 0.15);
  color: #34c759;
}

.theme-dark .detail-icon-success.highlight-icon {
  background: rgba(52, 199, 89, 0.2);
}

.detail-content-success {
  flex: 1;
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.detail-label-success {
  font-size: 12px;
  color: #86868b;
  font-weight: 500;
  text-transform: uppercase;
  letter-spacing: 0.05em;
}

.theme-dark .detail-label-success {
  color: #98989d;
}

.detail-value-success {
  font-size: 17px;
  font-weight: 600;
  color: #1d1d1f;
  letter-spacing: -0.01em;
}

.theme-dark .detail-value-success {
  color: #f5f5f7;
}

.detail-value-success.total-price {
  font-size: 24px;
  font-weight: 700;
  color: #34c759;
  letter-spacing: -0.02em;
}

/* Client Info (Multiple) */
.client-info {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 16px;
  margin-bottom: 16px;
}

.info-row {
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.info-label {
  font-size: 12px;
  color: #86868b;
  text-transform: uppercase;
  letter-spacing: 0.04em;
}

.info-value {
  font-size: 16px;
  font-weight: 600;
  color: #1d1d1f;
}

.theme-dark .info-value {
  color: #f5f5f7;
}

/* Appointments Header */
.appointments-header {
  display: flex;
  align-items: center;
  gap: 10px;
  margin: 16px 0;
  padding: 14px 18px;
  background: rgba(0, 122, 255, 0.06);
  border-radius: 12px;
}

.appointments-header svg {
  color: #007aff;
}

.appointments-header h4 {
  margin: 0;
  font-size: 17px;
}

/* Appointments List */
.appointments-list {
  display: flex;
  flex-direction: column;
  gap: 12px;
  margin-bottom: 20px;
}

.appointment-item {
  display: flex;
  gap: 14px;
  padding: 16px;
  background: rgba(0, 122, 255, 0.04);
  border-radius: 14px;
}

.theme-dark .appointment-item {
  background: rgba(0, 122, 255, 0.08);
}

.appointment-number {
  width: 36px;
  height: 36px;
  border-radius: 10px;
  background: #007aff;
  color: white;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 16px;
  font-weight: 700;
}

.appointment-content {
  flex: 1;
}

.appointment-service {
  font-size: 16px;
  font-weight: 600;
  color: #1d1d1f;
  margin-bottom: 6px;
}

.theme-dark .appointment-service {
  color: #f5f5f7;
}

.appointment-details {
  display: flex;
  flex-wrap: wrap;
  gap: 14px;
  font-size: 13px;
  color: #86868b;
  margin-bottom: 8px;
}

.appointment-details span {
  display: flex;
  align-items: center;
  gap: 4px;
}

.appointment-details svg {
  color: #007aff;
}

.appointment-price {
  font-size: 18px;
  font-weight: 700;
  color: #007aff;
}

/* Spinner */
.spinner {
  animation: spin 1s linear infinite;
}

@keyframes spin {
  from { transform: rotate(0deg); }
  to { transform: rotate(360deg); }
}

@keyframes shake {
  0%, 100% { transform: translateX(0); }
  25% { transform: translateX(-8px); }
  75% { transform: translateX(8px); }
}

/* Fade Transition */
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}

/* Responsive */
@media (max-width: 380px) {
  .paso-confirmacion {
    padding: 16px 12px;
  }

  .page-header {
    padding: 18px 14px 20px;
    border-radius: 18px;
  }
  
  .header-icon {
    width: 40px;
    height: 40px;
    margin-bottom: 12px;
  }
  
  .page-header h1 {
    font-size: 20px;
  }

  .page-header p {
    font-size: 14px;
  }
  
  .otp-input {
    width: 42px;
    height: 54px;
    font-size: 20px;
  }
  
  .details-grid {
    grid-template-columns: 1fr;
  }
  
  .detail-item.highlight {
    grid-column: 1;
  }
}
</style>
