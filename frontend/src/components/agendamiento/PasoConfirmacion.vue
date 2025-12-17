<script setup lang="ts">
import { ref, onMounted, computed } from 'vue'
import { useCitasStore } from '@/stores/citas'
import './PasoConfirmacion.css'
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
