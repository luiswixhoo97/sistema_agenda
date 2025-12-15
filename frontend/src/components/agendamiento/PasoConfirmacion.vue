<script setup lang="ts">
import { ref, onMounted, computed } from 'vue'
import { useCitasStore } from '@/stores/citas'

const store = useCitasStore()

const confirmando = ref(false)
const exito = ref(false)
const otpDigits = ref(['', '', '', '', '', ''])
const otpRefs = ref<(HTMLInputElement | null)[]>([])

// Verificar si está en modo múltiples empleados
const esModoMultiples = computed(() => store.modoMultiplesEmpleados)

// Obtener profesionales asignados en modo múltiples
const profesionalesTexto = computed(() => {
  if (!esModoMultiples.value) return store.empleadoSeleccionado?.nombre || ''
  return store.empleadosPorServicio.map(ep => ep.empleadoNombre).join(', ')
})

onMounted(async () => {
  if (!store.otpEnviado) {
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
</script>

<template>
  <div class="paso-confirmacion">
    <!-- ÉXITO -->
    <template v-if="exito">
      <div class="exito-container">
        <div class="exito-icon">
          <i class="fa fa-check"></i>
        </div>
        <h2 v-if="!esModoMultiples">¡Cita Agendada!</h2>
        <h2 v-else>¡Citas Agendadas!</h2>
        <p v-if="!esModoMultiples">Tu cita ha sido confirmada exitosamente</p>
        <p v-else>Tus {{ store.citasMultiples.length }} citas han sido confirmadas exitosamente</p>
      </div>

      <div class="card">
        <div class="whatsapp-notice">
          <i class="fab fa-whatsapp"></i>
          <span>Te enviaremos los detalles por WhatsApp</span>
        </div>

        <div class="divider"></div>

        <!-- Detalles para cita única -->
        <template v-if="!esModoMultiples">
          <div class="detalles-grid">
            <div class="detalle-card">
              <i class="fa fa-user"></i>
              <div class="detalle-content">
                <span class="label">Cliente</span>
                <span class="value">{{ store.datosCliente.nombre }} {{ store.datosCliente.apellido }}</span>
              </div>
            </div>
            <div class="detalle-card">
              <i class="fa fa-phone"></i>
              <div class="detalle-content">
                <span class="label">Teléfono</span>
                <span class="value">+52 {{ store.datosCliente.telefono }}</span>
              </div>
            </div>
            <div class="detalle-card">
              <i class="fa fa-calendar"></i>
              <div class="detalle-content">
                <span class="label">Fecha</span>
                <span class="value">{{ store.fechaSeleccionada }}</span>
              </div>
            </div>
            <div class="detalle-card">
              <i class="fa fa-clock"></i>
              <div class="detalle-content">
                <span class="label">Hora</span>
                <span class="value">{{ formatHora(store.horaSeleccionada || '') }}</span>
              </div>
            </div>
            <div class="detalle-card">
              <i class="fa fa-user-tie"></i>
              <div class="detalle-content">
                <span class="label">Profesional</span>
                <span class="value">{{ store.empleadoSeleccionado?.nombre }}</span>
              </div>
            </div>
            <div class="detalle-card detalle-precio">
              <i class="fa fa-dollar-sign"></i>
              <div class="detalle-content">
                <span class="label">Total</span>
                <span class="value precio">${{ Number(store.totalPrecio || 0).toFixed(2) }}</span>
              </div>
            </div>
          </div>
        </template>

        <!-- Detalles para múltiples citas -->
        <template v-else>
          <div class="cliente-info-exito">
            <div class="detalle-exito">
              <span class="label-exito">Cliente</span>
              <span class="value-exito">{{ store.datosCliente.nombre }} {{ store.datosCliente.apellido }}</span>
            </div>
            <div class="detalle-exito">
              <span class="label-exito">Teléfono</span>
              <span class="value-exito">+52 {{ store.datosCliente.telefono }}</span>
            </div>
          </div>

          <div class="divider"></div>

          <div class="citas-header-exito">
            <i class="fa fa-calendar-check"></i>
            <h4 class="citas-titulo">
              Tus {{ store.citasMultiples.length }} citas coordinadas
            </h4>
          </div>

          <div class="citas-multiples-lista">
            <div 
              v-for="(cita, index) in store.citasMultiples" 
              :key="cita.id"
              class="cita-multiple-item"
            >
              <div class="cita-numero">{{ index + 1 }}</div>
              <div class="cita-contenido">
                <div class="cita-servicio">{{ cita.servicios[0]?.nombre }}</div>
                <div class="cita-detalles">
                  <span class="cita-detalle-item">
                    <i class="fa fa-user"></i>
                    {{ cita.empleado.nombre }}
                  </span>
                  <span class="cita-detalle-item">
                    <i class="fa fa-clock"></i>
                    {{ cita.hora_texto }}
                  </span>
                </div>
                <div class="cita-precio-wrapper">
                  <span class="cita-precio">${{ cita.precio_final }}</span>
                </div>
              </div>
            </div>
          </div>

          <div class="total-multiples">
            <span class="total-label">Total</span>
            <span class="total">${{ Number(store.totalPrecio || 0).toFixed(2) }}</span>
          </div>
        </template>
      </div>

      <button class="btn-primary" @click="nuevaCita">
        <i class="fa fa-plus"></i>
        Agendar otra cita
      </button>
    </template>

    <!-- CONFIRMACIÓN -->
    <template v-else>
      <!-- Header -->
      <div class="paso-header">
        <div class="header-icon">
          <i class="fa fa-clipboard-check"></i>
        </div>
        <h2 v-if="!esModoMultiples">Confirmar Cita</h2>
        <h2 v-else>Confirmar Citas</h2>
        <p>Revisa los detalles y confirma</p>
      </div>

      <!-- Resumen Cliente -->
      <div class="card">
        <h4><i class="fa fa-user"></i> Tus Datos</h4>
        <p class="cliente-nombre">{{ store.datosCliente.nombre }} {{ store.datosCliente.apellido }}</p>
        <p class="cliente-contacto">
          <span><i class="fa fa-phone"></i> +52 {{ store.datosCliente.telefono }}</span>
          <span v-if="store.datosCliente.email"><i class="fa fa-envelope"></i> {{ store.datosCliente.email }}</span>
        </p>
      </div>

      <!-- Resumen Servicios (modo normal) -->
      <div v-if="!esModoMultiples" class="card">
        <h4><i class="fa fa-spa"></i> Servicios</h4>
        <div class="servicios-lista">
          <div 
            v-for="servicio in store.serviciosSeleccionados" 
            :key="servicio.id"
            class="servicio-row"
          >
            <span>{{ servicio.nombre }}</span>
            <span class="precio">${{ Number(servicio.precio).toFixed(2) }}</span>
          </div>
        </div>
        <div class="total-row">
          <span>Total</span>
          <span class="total">${{ Number(store.totalPrecio || 0).toFixed(2) }}</span>
        </div>
      </div>

      <!-- Resumen Cita (modo normal) -->
      <div v-if="!esModoMultiples" class="card">
        <h4><i class="fa fa-calendar-check"></i> Tu Cita</h4>
        <div class="cita-info">
          <div class="info-item">
            <i class="fa fa-user-tie"></i>
            <span>{{ store.empleadoSeleccionado?.nombre }}</span>
          </div>
          <div class="info-item">
            <i class="fa fa-calendar"></i>
            <span>{{ store.fechaSeleccionada }}</span>
          </div>
          <div class="info-item">
            <i class="fa fa-clock"></i>
            <span>{{ store.horaSeleccionada }}</span>
          </div>
        </div>
      </div>

      <!-- Resumen Citas Múltiples -->
      <div v-if="esModoMultiples && store.slotCoordinadoSeleccionado" class="card">
        <h4><i class="fa fa-calendar-check"></i> Tus {{ store.serviciosSeleccionados.length }} Citas Coordinadas</h4>
        
        <div class="fecha-coordinada">
          <i class="fa fa-calendar"></i>
          {{ store.fechaSeleccionada }}
        </div>

        <div class="timeline-confirmacion">
          <div 
            v-for="(servicio, index) in store.slotCoordinadoSeleccionado.servicios" 
            :key="index"
            class="timeline-item-conf"
          >
            <div class="timeline-hora">
              {{ formatHora(servicio.hora_inicio) }}
            </div>
            <div class="timeline-dot"></div>
            <div class="timeline-content-conf">
              <div class="timeline-servicio-nombre">{{ servicio.servicio_nombre }}</div>
              <div class="timeline-empleado-nombre">
                <i class="fa fa-user"></i>
                {{ servicio.empleado_nombre }}
              </div>
              <div class="timeline-duracion">
                <i class="fa fa-clock"></i>
                {{ servicio.duracion }} min
              </div>
            </div>
            <div class="timeline-precio">
              ${{ store.serviciosSeleccionados.find(s => s.id === servicio.servicio_id)?.precio || 0 }}
            </div>
          </div>
        </div>

        <div class="total-row">
          <span>Total</span>
          <span class="total">${{ Number(store.totalPrecio || 0).toFixed(2) }}</span>
        </div>
      </div>

      <!-- OTP -->
      <div class="card otp-card">
        <div class="otp-header">
          <i class="fa fa-shield-alt"></i>
          <h4>Código de Verificación</h4>
          <p>Enviamos un código de 6 dígitos a<br><strong>+52 {{ store.datosCliente.telefono }}</strong></p>
        </div>

        <!-- Debug code -->
        <div v-if="store.otpDebug" class="debug-code">
          <span>Código de prueba:</span>
          <strong>{{ store.otpDebug }}</strong>
        </div>

        <!-- OTP Input -->
        <div class="otp-inputs">
          <input
            v-for="(digit, index) in otpDigits"
            :key="index"
            :ref="el => otpRefs[index] = el as HTMLInputElement"
            type="text"
            inputmode="numeric"
            maxlength="1"
            class="otp-input"
            :value="digit"
            @input="handleOtpInput(index, $event)"
            @keydown="handleOtpKeydown(index, $event)"
            @paste="handlePaste"
          />
        </div>

        <button class="link-btn" @click="reenviarOtp">
          <i class="fa fa-redo"></i>
          Reenviar código
        </button>

        <!-- Error -->
        <div v-if="store.error" class="error-box">
          <i class="fa fa-exclamation-circle"></i>
          {{ store.error }}
        </div>

        <!-- Confirmar -->
        <button 
          class="btn-primary btn-full"
          :disabled="confirmando || store.otpCodigo.length !== 6"
          @click="confirmar"
        >
          <span v-if="confirmando">
            <i class="fa fa-spinner fa-spin"></i>
            Confirmando...
          </span>
          <span v-else-if="!esModoMultiples">
            <i class="fa fa-check"></i>
            Confirmar Cita
          </span>
          <span v-else>
            <i class="fa fa-check"></i>
            Confirmar {{ store.serviciosSeleccionados.length }} Citas
          </span>
        </button>
      </div>

      <!-- Volver -->
      <button class="btn-secondary" @click="store.pasoAnterior()">
        <i class="fa fa-arrow-left"></i>
        Volver
      </button>
    </template>
  </div>
</template>

<style scoped>
.paso-confirmacion {
  padding: 20px 16px;
  padding-bottom: 100px;
  font-family: -apple-system, BlinkMacSystemFont, 'SF Pro Display', 'SF Pro Text', 'Helvetica Neue', sans-serif;
  -webkit-font-smoothing: antialiased;
  -moz-osx-font-smoothing: grayscale;
}

/* Header */
.paso-header {
  text-align: center;
  padding: 24px 0 32px;
}

.header-icon {
  width: 72px;
  height: 72px;
  border-radius: 18px;
  background: #007aff;
  display: flex;
  align-items: center;
  justify-content: center;
  margin: 0 auto 24px;
  box-shadow: 0 2px 8px rgba(0, 122, 255, 0.2);
}

.header-icon i {
  font-size: 32px;
  color: white;
}

.paso-header h2 {
  font-size: 32px;
  font-weight: 600;
  color: #1d1d1f;
  margin: 0 0 10px;
  letter-spacing: -0.5px;
}

.theme-dark .paso-header h2 {
  color: #f5f5f7;
}

.paso-header p {
  font-size: 18px;
  color: #86868b;
  margin: 0;
  font-weight: 400;
}

.theme-dark .paso-header p {
  color: #a1a1a6;
}

/* Cards */
.card {
  background: rgba(255, 255, 255, 0.8);
  backdrop-filter: saturate(180%) blur(20px);
  -webkit-backdrop-filter: saturate(180%) blur(20px);
  border-radius: 20px;
  padding: 24px;
  margin-bottom: 20px;
  box-shadow: 
    0 4px 16px rgba(0, 0, 0, 0.04),
    0 0 0 0.5px rgba(0, 0, 0, 0.06);
  border: 0.5px solid rgba(0, 0, 0, 0.08);
}

.theme-dark .card {
  background: rgba(28, 28, 30, 0.8);
  border-color: rgba(255, 255, 255, 0.1);
  box-shadow: 
    0 4px 16px rgba(0, 0, 0, 0.3),
    0 0 0 0.5px rgba(255, 255, 255, 0.05);
}

.card h4 {
  font-size: 17px;
  font-weight: 600;
  color: #1d1d1f;
  margin: 0 0 18px;
  display: flex;
  align-items: center;
  gap: 10px;
  letter-spacing: -0.2px;
}

.theme-dark .card h4 {
  color: #f5f5f7;
}

.card h4 i {
  color: #007aff;
  font-size: 18px;
}

/* Cliente info */
.cliente-nombre {
  font-size: 19px;
  font-weight: 600;
  color: #1d1d1f;
  margin: 0 0 10px;
  letter-spacing: -0.2px;
}

.theme-dark .cliente-nombre {
  color: #f5f5f7;
}

.cliente-contacto {
  font-size: 16px;
  color: #86868b;
  margin: 0;
  display: flex;
  flex-wrap: wrap;
  gap: 18px;
  font-weight: 400;
}

.theme-dark .cliente-contacto {
  color: #a1a1a6;
}

.cliente-contacto i {
  margin-right: 6px;
  color: #007aff;
}

/* Servicios */
.servicios-lista {
  margin-bottom: 16px;
}

.servicio-row {
  display: flex;
  justify-content: space-between;
  padding: 14px 0;
  font-size: 16px;
  color: #1d1d1f;
  border-bottom: 0.5px solid rgba(0, 0, 0, 0.1);
  font-weight: 400;
}

.theme-dark .servicio-row {
  color: #f5f5f7;
  border-bottom-color: rgba(255, 255, 255, 0.1);
}

.servicio-row:last-child {
  border-bottom: none;
}

.servicio-row .precio {
  color: #007aff;
  font-weight: 600;
}

.total-row {
  display: flex;
  justify-content: space-between;
  padding-top: 18px;
  border-top: 0.5px solid rgba(0, 0, 0, 0.1);
  font-size: 19px;
  font-weight: 600;
}

.theme-dark .total-row {
  border-top-color: rgba(255, 255, 255, 0.1);
}

.total-row .total {
  color: #007aff;
  font-size: 22px;
  letter-spacing: -0.3px;
}

/* Cita info */
.cita-info {
  display: flex;
  flex-wrap: wrap;
  gap: 12px;
}

.info-item {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 12px 16px;
  background: rgba(0, 122, 255, 0.08);
  border-radius: 12px;
  font-size: 16px;
  color: #1d1d1f;
  font-weight: 400;
}

.theme-dark .info-item {
  background: rgba(0, 122, 255, 0.12);
  color: #f5f5f7;
}

.info-item i {
  color: #007aff;
  font-size: 16px;
}

/* ===================================== */
/* Estilos para múltiples citas */
/* ===================================== */

.fecha-coordinada {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 14px 18px;
  background: rgba(0, 122, 255, 0.1);
  border-radius: 12px;
  color: #007aff;
  font-weight: 600;
  font-size: 17px;
  margin-bottom: 24px;
}

.timeline-confirmacion {
  display: flex;
  flex-direction: column;
  gap: 0;
  margin-bottom: 16px;
}

.timeline-item-conf {
  display: flex;
  align-items: flex-start;
  gap: 12px;
  padding: 12px 0;
  position: relative;
}

.timeline-item-conf:not(:last-child)::after {
  content: '';
  position: absolute;
  left: 74px;
  top: 28px;
  bottom: -4px;
  width: 2px;
  background: var(--color-border);
}

.timeline-hora {
  width: 60px;
  font-size: 13px;
  font-weight: 600;
  color: var(--color-text);
  text-align: right;
  flex-shrink: 0;
}

.timeline-dot {
  width: 10px;
  height: 10px;
  border-radius: 50%;
  background: #007aff;
  flex-shrink: 0;
  margin-top: 4px;
  z-index: 1;
  border: 2px solid rgba(255, 255, 255, 0.8);
}

.theme-dark .timeline-dot {
  border-color: rgba(28, 28, 30, 0.8);
}

.timeline-content-conf {
  flex: 1;
}

.timeline-servicio-nombre {
  font-size: 14px;
  font-weight: 600;
  color: var(--color-text);
  margin-bottom: 4px;
}

.timeline-empleado-nombre {
  font-size: 12px;
  color: var(--color-text-secondary);
  display: flex;
  align-items: center;
  gap: 4px;
}

.timeline-empleado-nombre i {
  font-size: 10px;
  color: #007aff;
}

.timeline-duracion {
  font-size: 11px;
  color: var(--color-text-secondary);
  display: flex;
  align-items: center;
  gap: 4px;
  margin-top: 2px;
}

.timeline-duracion i {
  font-size: 10px;
}

.timeline-precio {
  font-size: 15px;
  font-weight: 600;
  color: #007aff;
  flex-shrink: 0;
}

/* OTP Card */
.otp-card {
  text-align: center;
}

.otp-header i {
  font-size: 40px;
  color: #007aff;
  margin-bottom: 16px;
}

.otp-header h4 {
  justify-content: center;
  font-size: 19px;
}

.otp-header p {
  font-size: 17px;
  color: #86868b;
  margin: 0 0 28px;
  font-weight: 400;
  line-height: 1.5;
}

.theme-dark .otp-header p {
  color: #a1a1a6;
}

/* Debug code */
.debug-code {
  background: #fff3cd;
  padding: 10px 16px;
  border-radius: 10px;
  margin-bottom: 16px;
  font-size: 13px;
}

.debug-code strong {
  display: block;
  font-size: 20px;
  letter-spacing: 4px;
  margin-top: 4px;
  color: #333;
}

/* OTP Inputs */
.otp-inputs {
  display: flex;
  justify-content: center;
  gap: 10px;
  margin-bottom: 20px;
}

.otp-input {
  width: 56px;
  height: 68px;
  text-align: center;
  font-size: 28px;
  font-weight: 600;
  border: 1px solid #d1d1d6;
  border-radius: 14px;
  background: rgba(255, 255, 255, 1);
  color: #1d1d1f;
  transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
  font-family: -apple-system, BlinkMacSystemFont, 'SF Pro Text', sans-serif;
}

.theme-dark .otp-input {
  background: rgba(44, 44, 46, 1);
  border-color: rgba(255, 255, 255, 0.15);
  color: #f5f5f7;
}

.otp-input:focus {
  outline: none;
  border-color: #007aff;
  box-shadow: 0 0 0 4px rgba(0, 122, 255, 0.1);
  background: rgba(255, 255, 255, 1);
}

.theme-dark .otp-input:focus {
  background: rgba(58, 58, 60, 1);
  box-shadow: 0 0 0 4px rgba(0, 122, 255, 0.15);
}

/* Link button */
.link-btn {
  background: none;
  border: none;
  color: #007aff;
  font-size: 15px;
  cursor: pointer;
  display: inline-flex;
  align-items: center;
  gap: 6px;
  margin-bottom: 20px;
  font-weight: 500;
  transition: opacity 0.2s;
}

.link-btn:hover {
  opacity: 0.7;
}

/* Error box */
.error-box {
  background: rgba(255, 59, 48, 0.1);
  color: #ff3b30;
  padding: 12px 16px;
  border-radius: 12px;
  font-size: 15px;
  margin-bottom: 20px;
  display: flex;
  align-items: center;
  gap: 10px;
  border: 0.5px solid rgba(255, 59, 48, 0.2);
}

.theme-dark .error-box {
  background: rgba(255, 59, 48, 0.15);
  border-color: rgba(255, 59, 48, 0.25);
}

/* Buttons */
.btn-primary {
  width: 100%;
  padding: 16px 24px;
  background: #007aff;
  color: white;
  border: none;
  border-radius: 14px;
  font-size: 18px;
  font-weight: 600;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 10px;
  transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
  font-family: -apple-system, BlinkMacSystemFont, 'SF Pro Text', sans-serif;
  letter-spacing: -0.2px;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.btn-primary:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.btn-primary:not(:disabled):hover {
  background: #0051d5;
  box-shadow: 0 2px 8px rgba(0, 122, 255, 0.3);
}

.btn-primary:not(:disabled):active {
  transform: scale(0.98);
  background: #0040b3;
}

.btn-secondary {
  width: 100%;
  padding: 16px 24px;
  background: rgba(255, 255, 255, 0.8);
  backdrop-filter: saturate(180%) blur(20px);
  -webkit-backdrop-filter: saturate(180%) blur(20px);
  color: #1d1d1f;
  border: 0.5px solid rgba(0, 0, 0, 0.1);
  border-radius: 14px;
  font-size: 18px;
  font-weight: 600;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 10px;
  margin-top: 16px;
  transition: all 0.2s;
  font-family: -apple-system, BlinkMacSystemFont, 'SF Pro Text', sans-serif;
  letter-spacing: -0.2px;
}

.theme-dark .btn-secondary {
  background: rgba(44, 44, 46, 0.8);
  border-color: rgba(255, 255, 255, 0.1);
  color: #f5f5f7;
}

.btn-secondary:active {
  transform: scale(0.98);
  background: rgba(0, 0, 0, 0.05);
}

.theme-dark .btn-secondary:active {
  background: rgba(255, 255, 255, 0.05);
}

/* ÉXITO */
.exito-container {
  text-align: center;
  padding: 40px 20px 30px;
}

.exito-icon {
  width: 96px;
  height: 96px;
  border-radius: 24px;
  background: #34c759;
  display: flex;
  align-items: center;
  justify-content: center;
  margin: 0 auto 28px;
  animation: bounceIn 0.5s ease;
  box-shadow: 0 4px 16px rgba(52, 199, 89, 0.3);
}

.exito-icon i {
  font-size: 44px;
  color: white;
}

@keyframes bounceIn {
  0% { transform: scale(0); }
  50% { transform: scale(1.1); }
  100% { transform: scale(1); }
}

.exito-container h2 {
  font-size: 36px;
  font-weight: 600;
  color: #1d1d1f;
  margin: 0 0 10px;
  letter-spacing: -0.5px;
}

.theme-dark .exito-container h2 {
  color: #f5f5f7;
}

.exito-container p {
  font-size: 19px;
  color: #86868b;
  margin: 0;
  font-weight: 400;
}

.theme-dark .exito-container p {
  color: #a1a1a6;
}

.whatsapp-notice {
  display: flex;
  align-items: center;
  gap: 10px;
  color: #25d366;
  font-size: 20px;
  margin-bottom: 12px;
}

.whatsapp-notice i {
  font-size: 30px;
}

.divider {
  height: 0.5px;
  background: rgba(0, 0, 0, 0.1);
  margin: 16px 0;
}

.theme-dark .divider {
  background: rgba(255, 255, 255, 0.1);
}

.detalles-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 14px;
}

.detalle-card {
  display: flex;
  align-items: flex-start;
  gap: 12px;
  padding: 16px;
  background: rgba(0, 122, 255, 0.06);
  border-radius: 14px;
  border: 0.5px solid rgba(0, 122, 255, 0.12);
  transition: all 0.2s;
}

.theme-dark .detalle-card {
  background: rgba(0, 122, 255, 0.1);
  border-color: rgba(0, 122, 255, 0.15);
}

.detalle-card:hover {
  background: rgba(0, 122, 255, 0.1);
  border-color: rgba(0, 122, 255, 0.2);
  transform: translateY(-1px);
}

.theme-dark .detalle-card:hover {
  background: rgba(0, 122, 255, 0.15);
}

.detalle-card i {
  font-size: 20px;
  color: #007aff;
  margin-top: 2px;
  flex-shrink: 0;
}

.detalle-content {
  display: flex;
  flex-direction: column;
  gap: 6px;
  flex: 1;
  min-width: 0;
}

.detalle .label {
  font-size: 11px;
  color: #86868b;
  text-transform: uppercase;
  letter-spacing: 0.8px;
  font-weight: 600;
  margin: 0;
}

.theme-dark .detalle .label {
  color: #a1a1a6;
}

.detalle .value {
  font-size: 16px;
  font-weight: 600;
  color: #1d1d1f;
  letter-spacing: -0.2px;
  line-height: 1.3;
  word-break: break-word;
}

.theme-dark .detalle .value {
  color: #f5f5f7;
}

.detalle-precio {
  background: rgba(0, 122, 255, 0.12) !important;
  border-color: rgba(0, 122, 255, 0.2) !important;
  grid-column: 1 / -1;
}

.theme-dark .detalle-precio {
  background: rgba(0, 122, 255, 0.18) !important;
  border-color: rgba(0, 122, 255, 0.25) !important;
}

.detalle-precio:hover {
  background: rgba(0, 122, 255, 0.15) !important;
  border-color: rgba(0, 122, 255, 0.3) !important;
}

.theme-dark .detalle-precio:hover {
  background: rgba(0, 122, 255, 0.22) !important;
}

.detalle .value.precio {
  font-size: 24px;
  font-weight: 700;
  color: #007aff;
  letter-spacing: -0.4px;
}

/* Éxito múltiples citas */
.cliente-info-exito {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 16px;
  margin-bottom: 20px;
}

.detalle-exito {
  display: flex;
  flex-direction: column;
  gap: 6px;
}

.label-exito {
  font-size: 12px;
  color: #86868b;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  font-weight: 500;
}

.theme-dark .label-exito {
  color: #a1a1a6;
}

.value-exito {
  font-size: 17px;
  font-weight: 600;
  color: #1d1d1f;
  letter-spacing: -0.2px;
}

.theme-dark .value-exito {
  color: #f5f5f7;
}

.citas-header-exito {
  display: flex;
  align-items: center;
  gap: 12px;
  margin: 20px 0 24px;
  padding: 16px 20px;
  background: rgba(0, 122, 255, 0.1);
  border-radius: 16px;
  border: 0.5px solid rgba(0, 122, 255, 0.2);
}

.theme-dark .citas-header-exito {
  background: rgba(0, 122, 255, 0.15);
  border-color: rgba(0, 122, 255, 0.25);
}

.citas-header-exito i {
  font-size: 24px;
  color: #007aff;
  flex-shrink: 0;
}

.citas-titulo {
  font-size: 19px;
  font-weight: 600;
  color: #1d1d1f;
  margin: 0;
  letter-spacing: -0.3px;
}

.theme-dark .citas-titulo {
  color: #f5f5f7;
}

.citas-multiples-lista {
  display: flex;
  flex-direction: column;
  gap: 14px;
  margin-bottom: 24px;
}

.cita-multiple-item {
  display: flex;
  gap: 16px;
  padding: 20px;
  background: rgba(0, 122, 255, 0.08);
  border-radius: 18px;
  border: 1px solid rgba(0, 122, 255, 0.15);
  box-shadow: 0 2px 8px rgba(0, 122, 255, 0.08);
  transition: all 0.2s;
}

.theme-dark .cita-multiple-item {
  background: rgba(0, 122, 255, 0.12);
  border-color: rgba(0, 122, 255, 0.2);
  box-shadow: 0 2px 8px rgba(0, 122, 255, 0.15);
}

.cita-multiple-item:hover {
  transform: translateY(-1px);
  box-shadow: 0 4px 12px rgba(0, 122, 255, 0.12);
}

.cita-numero {
  width: 40px;
  height: 40px;
  border-radius: 12px;
  background: #007aff;
  color: white;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 18px;
  font-weight: 700;
  flex-shrink: 0;
  box-shadow: 0 2px 8px rgba(0, 122, 255, 0.3);
}

.cita-contenido {
  flex: 1;
  display: flex;
  flex-direction: column;
  gap: 10px;
}

.cita-servicio {
  font-size: 18px;
  font-weight: 600;
  color: #1d1d1f;
  letter-spacing: -0.2px;
  line-height: 1.3;
}

.theme-dark .cita-servicio {
  color: #f5f5f7;
}

.cita-detalles {
  display: flex;
  flex-wrap: wrap;
  gap: 16px;
  align-items: center;
}

.cita-detalle-item {
  display: flex;
  align-items: center;
  gap: 6px;
  font-size: 15px;
  color: #86868b;
  font-weight: 400;
}

.theme-dark .cita-detalle-item {
  color: #a1a1a6;
}

.cita-detalle-item i {
  font-size: 14px;
  color: #007aff;
}

.cita-precio-wrapper {
  margin-top: 4px;
  padding-top: 12px;
  border-top: 0.5px solid rgba(0, 122, 255, 0.15);
}

.theme-dark .cita-precio-wrapper {
  border-top-color: rgba(0, 122, 255, 0.2);
}

.cita-precio {
  font-size: 20px;
  font-weight: 700;
  color: #007aff;
  letter-spacing: -0.3px;
}

.total-multiples {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 20px 24px;
  background: rgba(0, 122, 255, 0.1);
  border-radius: 16px;
  border: 1px solid rgba(0, 122, 255, 0.2);
  margin-top: 8px;
}

.theme-dark .total-multiples {
  background: rgba(0, 122, 255, 0.15);
  border-color: rgba(0, 122, 255, 0.25);
}

.total-label {
  font-size: 19px;
  font-weight: 600;
  color: #1d1d1f;
  letter-spacing: -0.2px;
}

.theme-dark .total-label {
  color: #f5f5f7;
}

.total-multiples .total {
  color: #007aff;
  font-size: 26px;
  font-weight: 700;
  letter-spacing: -0.4px;
}
</style>
