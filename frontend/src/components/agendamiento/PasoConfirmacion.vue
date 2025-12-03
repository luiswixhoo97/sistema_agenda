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
            <div class="detalle">
              <span class="label">Cliente</span>
              <span class="value">{{ store.datosCliente.nombre }} {{ store.datosCliente.apellido }}</span>
            </div>
            <div class="detalle">
              <span class="label">Teléfono</span>
              <span class="value">+52 {{ store.datosCliente.telefono }}</span>
            </div>
            <div class="detalle">
              <span class="label">Fecha</span>
              <span class="value">{{ store.fechaSeleccionada }}</span>
            </div>
            <div class="detalle">
              <span class="label">Hora</span>
              <span class="value">{{ store.horaSeleccionada }}</span>
            </div>
            <div class="detalle">
              <span class="label">Profesional</span>
              <span class="value">{{ store.empleadoSeleccionado?.nombre }}</span>
            </div>
            <div class="detalle">
              <span class="label">Total</span>
              <span class="value precio">${{ Number(store.totalPrecio || 0).toFixed(2) }}</span>
            </div>
          </div>
        </template>

        <!-- Detalles para múltiples citas -->
        <template v-else>
          <div class="cliente-info-exito">
            <div class="detalle">
              <span class="label">Cliente</span>
              <span class="value">{{ store.datosCliente.nombre }} {{ store.datosCliente.apellido }}</span>
            </div>
            <div class="detalle">
              <span class="label">Teléfono</span>
              <span class="value">+52 {{ store.datosCliente.telefono }}</span>
            </div>
          </div>

          <div class="divider"></div>

          <h4 class="citas-titulo">
            <i class="fa fa-calendar-check"></i>
            Tus {{ store.citasMultiples.length }} citas coordinadas
          </h4>

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
                  <span><i class="fa fa-user"></i> {{ cita.empleado.nombre }}</span>
                  <span><i class="fa fa-clock"></i> {{ cita.hora_texto }}</span>
                  <span class="cita-precio">${{ cita.precio_final }}</span>
                </div>
              </div>
            </div>
          </div>

          <div class="total-multiples">
            <span>Total</span>
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
  padding: 16px;
  padding-bottom: 100px;
}

/* Header */
.paso-header {
  text-align: center;
  padding: 16px 0 20px;
}

.header-icon {
  width: 70px;
  height: 70px;
  border-radius: 50%;
  background: linear-gradient(135deg, rgba(236,64,122,0.1), rgba(236,64,122,0.2));
  display: flex;
  align-items: center;
  justify-content: center;
  margin: 0 auto 16px;
}

.header-icon i {
  font-size: 32px;
  color: #ec407a;
}

.paso-header h2 {
  font-size: 22px;
  font-weight: 700;
  color: var(--color-text);
  margin: 0 0 8px;
}

.paso-header p {
  font-size: 14px;
  color: var(--color-text-secondary);
  margin: 0;
}

/* Cards */
.card {
  background: var(--color-card);
  border-radius: 16px;
  padding: 16px;
  margin-bottom: 12px;
}

.card h4 {
  font-size: 14px;
  font-weight: 600;
  color: var(--color-text);
  margin: 0 0 12px;
  display: flex;
  align-items: center;
  gap: 8px;
}

.card h4 i {
  color: #ec407a;
  font-size: 14px;
}

/* Cliente info */
.cliente-nombre {
  font-size: 16px;
  font-weight: 600;
  color: var(--color-text);
  margin: 0 0 4px;
}

.cliente-contacto {
  font-size: 12px;
  color: var(--color-text-secondary);
  margin: 0;
  display: flex;
  flex-wrap: wrap;
  gap: 12px;
}

.cliente-contacto i {
  margin-right: 4px;
}

/* Servicios */
.servicios-lista {
  margin-bottom: 12px;
}

.servicio-row {
  display: flex;
  justify-content: space-between;
  padding: 8px 0;
  font-size: 13px;
  color: var(--color-text);
  border-bottom: 1px solid var(--color-border);
}

.servicio-row:last-child {
  border-bottom: none;
}

.servicio-row .precio {
  color: #ec407a;
  font-weight: 600;
}

.total-row {
  display: flex;
  justify-content: space-between;
  padding-top: 12px;
  border-top: 2px solid var(--color-border);
  font-size: 15px;
  font-weight: 600;
}

.total-row .total {
  color: #ec407a;
  font-size: 18px;
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
  gap: 8px;
  padding: 8px 12px;
  background: rgba(0,0,0,0.03);
  border-radius: 10px;
  font-size: 13px;
  color: var(--color-text);
}

.theme-dark .info-item {
  background: rgba(255,255,255,0.05);
}

.info-item i {
  color: #ec407a;
  font-size: 12px;
}

/* ===================================== */
/* Estilos para múltiples citas */
/* ===================================== */

.fecha-coordinada {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 10px 14px;
  background: linear-gradient(135deg, rgba(236,64,122,0.08), rgba(236,64,122,0.12));
  border-radius: 10px;
  color: #ec407a;
  font-weight: 600;
  font-size: 14px;
  margin-bottom: 16px;
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
  width: 12px;
  height: 12px;
  border-radius: 50%;
  background: #ec407a;
  flex-shrink: 0;
  margin-top: 4px;
  z-index: 1;
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
  color: #ec407a;
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
  font-size: 14px;
  font-weight: 600;
  color: #ec407a;
  flex-shrink: 0;
}

/* OTP Card */
.otp-card {
  text-align: center;
}

.otp-header i {
  font-size: 28px;
  color: #ec407a;
  margin-bottom: 8px;
}

.otp-header h4 {
  justify-content: center;
  font-size: 16px;
}

.otp-header p {
  font-size: 13px;
  color: var(--color-text-secondary);
  margin: 0 0 16px;
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
  gap: 8px;
  margin-bottom: 16px;
}

.otp-input {
  width: 45px;
  height: 55px;
  text-align: center;
  font-size: 22px;
  font-weight: 700;
  border: 2px solid var(--color-border);
  border-radius: 12px;
  background: var(--color-card);
  color: var(--color-text);
  transition: all 0.2s;
}

.otp-input:focus {
  outline: none;
  border-color: #ec407a;
  box-shadow: 0 0 0 3px rgba(236, 64, 122, 0.2);
}

/* Link button */
.link-btn {
  background: none;
  border: none;
  color: #ec407a;
  font-size: 13px;
  cursor: pointer;
  display: inline-flex;
  align-items: center;
  gap: 6px;
  margin-bottom: 16px;
}

/* Error box */
.error-box {
  background: rgba(239, 68, 68, 0.1);
  color: #ef4444;
  padding: 12px;
  border-radius: 10px;
  font-size: 13px;
  margin-bottom: 16px;
  display: flex;
  align-items: center;
  gap: 8px;
}

/* Buttons */
.btn-primary {
  width: 100%;
  padding: 14px 24px;
  background: linear-gradient(135deg, #ec407a, #d81b60);
  color: white;
  border: none;
  border-radius: 12px;
  font-size: 15px;
  font-weight: 600;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  transition: all 0.2s;
}

.btn-primary:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.btn-primary:not(:disabled):hover {
  transform: translateY(-2px);
  box-shadow: 0 6px 20px rgba(236, 64, 122, 0.4);
}

.btn-secondary {
  width: 100%;
  padding: 14px 24px;
  background: var(--color-card);
  color: var(--color-text);
  border: 2px solid var(--color-border);
  border-radius: 12px;
  font-size: 15px;
  font-weight: 600;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
}

/* ÉXITO */
.exito-container {
  text-align: center;
  padding: 40px 20px 30px;
}

.exito-icon {
  width: 90px;
  height: 90px;
  border-radius: 50%;
  background: linear-gradient(135deg, #4caf50, #388e3c);
  display: flex;
  align-items: center;
  justify-content: center;
  margin: 0 auto 20px;
  animation: bounceIn 0.5s ease;
}

.exito-icon i {
  font-size: 40px;
  color: white;
}

@keyframes bounceIn {
  0% { transform: scale(0); }
  50% { transform: scale(1.1); }
  100% { transform: scale(1); }
}

.exito-container h2 {
  font-size: 26px;
  font-weight: 700;
  color: var(--color-text);
  margin: 0 0 8px;
}

.exito-container p {
  font-size: 14px;
  color: var(--color-text-secondary);
  margin: 0;
}

.whatsapp-notice {
  display: flex;
  align-items: center;
  gap: 10px;
  color: #25d366;
  font-size: 13px;
  margin-bottom: 12px;
}

.whatsapp-notice i {
  font-size: 20px;
}

.divider {
  height: 1px;
  background: var(--color-border);
  margin: 12px 0;
}

.detalles-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 12px;
}

.detalle {
  display: flex;
  flex-direction: column;
}

.detalle .label {
  font-size: 10px;
  color: var(--color-text-secondary);
  text-transform: uppercase;
  letter-spacing: 0.5px;
  margin-bottom: 2px;
}

.detalle .value {
  font-size: 13px;
  font-weight: 600;
  color: var(--color-text);
}

.detalle .value.precio {
  color: #ec407a;
}

/* Éxito múltiples citas */
.cliente-info-exito {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 12px;
}

.citas-titulo {
  font-size: 14px;
  font-weight: 600;
  color: var(--color-text);
  margin: 12px 0;
  display: flex;
  align-items: center;
  gap: 8px;
}

.citas-titulo i {
  color: #ec407a;
}

.citas-multiples-lista {
  display: flex;
  flex-direction: column;
  gap: 10px;
  margin-bottom: 16px;
}

.cita-multiple-item {
  display: flex;
  gap: 12px;
  padding: 12px;
  background: rgba(0,0,0,0.03);
  border-radius: 12px;
}

.theme-dark .cita-multiple-item {
  background: rgba(255,255,255,0.05);
}

.cita-numero {
  width: 28px;
  height: 28px;
  border-radius: 50%;
  background: linear-gradient(135deg, #ec407a, #d81b60);
  color: white;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 13px;
  font-weight: 700;
  flex-shrink: 0;
}

.cita-contenido {
  flex: 1;
}

.cita-servicio {
  font-size: 14px;
  font-weight: 600;
  color: var(--color-text);
  margin-bottom: 4px;
}

.cita-detalles {
  display: flex;
  flex-wrap: wrap;
  gap: 12px;
  font-size: 12px;
  color: var(--color-text-secondary);
}

.cita-detalles i {
  margin-right: 4px;
  font-size: 10px;
}

.cita-precio {
  color: #ec407a !important;
  font-weight: 600;
}

.total-multiples {
  display: flex;
  justify-content: space-between;
  padding-top: 12px;
  border-top: 2px solid var(--color-border);
  font-size: 15px;
  font-weight: 600;
}

.total-multiples .total {
  color: #ec407a;
  font-size: 18px;
}
</style>
