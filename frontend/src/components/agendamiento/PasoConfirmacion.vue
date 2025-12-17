<script setup lang="ts">
import { ref, onMounted, computed, watch } from 'vue'
import { useCitasStore } from '@/stores/citas'
import mercadopagoService from '@/services/mercadopagoService'
import './PasoConfirmacion.css'
const store = useCitasStore()

const confirmando = ref(false)
const exito = ref(false)
const otpDigits = ref(['', '', '', '', '', ''])
const otpRefs = ref<(HTMLInputElement | null)[]>([])
const procesandoPago = ref(false)
const mostrarModalPayPal = ref(false)
const mostrarModalStripe = ref(false)

// Verificar si está en modo múltiples empleados
const esModoMultiples = computed(() => store.modoMultiplesEmpleados)

// Obtener profesionales asignados en modo múltiples
const profesionalesTexto = computed(() => {
  if (!esModoMultiples.value) return store.empleadoSeleccionado?.nombre || ''
  return store.empleadosPorServicio.map(ep => ep.empleadoNombre).join(', ')
})

// Computed para verificar si puede confirmar
const puedeConfirmar = computed(() => {
  // Si requiere anticipo, debe tener método de pago seleccionado Y OTP completo
  if (store.anticipoInfo.requiere_anticipo) {
    return store.metodoPagoAnticipo !== null && store.otpCodigo.length === 6
  }
  // Si NO requiere anticipo, solo necesita OTP completo
  return store.otpCodigo.length === 6
})

// Computed para verificar si debe mostrar OTP
const debeMostrarOtp = computed(() => {
  // Si requiere anticipo, mostrar OTP solo después de seleccionar método
  if (store.anticipoInfo.requiere_anticipo) {
    return store.metodoPagoAnticipo !== null
  }
  // Si NO requiere anticipo, mostrar OTP siempre
  return true
})

onMounted(async () => {
  // Verificar si hay parámetros de retorno de Mercado Pago
  const params = mercadopagoService.obtenerParametrosRetorno()
  if (params.payment_id && params.status) {
    await manejarRetornoPago(params)
    return
  }

  // Solo enviar OTP si no requiere anticipo o si ya se seleccionó método de pago
  if (!store.otpEnviado) {
    if (!store.anticipoInfo.requiere_anticipo) {
      // Sin anticipo: enviar OTP inmediatamente
      await store.enviarOtpConfirmacion()
    } else if (store.metodoPagoAnticipo !== null) {
      // Con anticipo pero ya se seleccionó método: enviar OTP
      await store.enviarOtpConfirmacion()
    }
    // Si requiere anticipo y no hay método seleccionado, NO enviar OTP aún
  }
})

// Watch para enviar OTP cuando se selecciona método de pago (si requiere anticipo)
watch(() => store.metodoPagoAnticipo, async (nuevoMetodo) => {
  if (store.anticipoInfo.requiere_anticipo && nuevoMetodo !== null && !store.otpEnviado) {
    console.log('📱 Método de pago seleccionado, enviando OTP...', nuevoMetodo)
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

  // Validar que si requiere anticipo, se haya seleccionado método
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

async function manejarRetornoPago(params: { payment_id: string | null; status: string | null; external_reference: string | null }) {
  if (!params.payment_id || !params.status) return

  procesandoPago.value = true
  store.clearError()

  try {
    // Verificar estado del pago
    const verificacion = await mercadopagoService.verificarPago(params.payment_id)

    if (verificacion.success && verificacion.data) {
      if (verificacion.data.status === 'approved') {
        // Pago aprobado
        store.anticipoPagado = true
        store.metodoPagoAnticipo = 'pasarela'
        store.error = null
        
        // Limpiar parámetros de la URL
        window.history.replaceState({}, document.title, window.location.pathname)
        
        // Si ya tenemos el OTP, proceder a agendar
        if (store.otpCodigo.length === 6) {
          await confirmar()
        } else {
          // Enviar OTP si no se ha enviado
          if (!store.otpEnviado) {
            await store.enviarOtpConfirmacion()
          }
        }
      } else if (verificacion.data.status === 'pending') {
        store.error = 'Tu pago está pendiente. Te notificaremos cuando se apruebe.'
      } else {
        store.error = 'El pago no pudo ser procesado. Intenta con otro método de pago.'
      }
    } else {
      store.error = 'No se pudo verificar el estado del pago. Intenta de nuevo.'
    }
  } catch (error: any) {
    console.error('Error verificando pago:', error)
    store.error = 'Error al verificar el pago. Intenta de nuevo.'
  } finally {
    procesandoPago.value = false
  }
}

async function pagarConMercadoPago() {
  if (!store.anticipoInfo.requiere_anticipo) return

  procesandoPago.value = true
  store.clearError()

  try {
    // Generar external_reference único
    const timestamp = Date.now()
    const externalReference = `anticipo_${timestamp}`

    // Obtener URLs de retorno
    const baseUrl = window.location.origin + window.location.pathname
    const backUrls = {
      success: baseUrl,
      failure: baseUrl,
      pending: baseUrl,
    }

    // Crear preferencia de pago
    const response = await mercadopagoService.crearPreferencia({
      monto: store.anticipoInfo.monto_anticipo,
      descripcion: `Anticipo para cita - ${store.serviciosSeleccionados.map(s => s.nombre).join(', ')}`,
      payer_email: store.datosCliente.email || undefined, // Enviar undefined si no hay email
      payer_name: store.datosCliente.nombre || undefined,
      payer_surname: store.datosCliente.apellido || undefined,
      external_reference: externalReference,
      back_url_success: backUrls.success,
      back_url_failure: backUrls.failure,
      back_url_pending: backUrls.pending,
    })

    if (response.success && response.data) {
      // Guardar external_reference en el store para verificar después
      store.metodoPagoAnticipo = 'pasarela'
      
      // Redirigir al checkout de Mercado Pago
      mercadopagoService.redirigirAlCheckout(response.data.init_point)
    } else {
      store.error = response.message || 'Error al crear la preferencia de pago'
    }
  } catch (error: any) {
    console.error('Error creando preferencia de Mercado Pago:', error)
    store.error = 'Error al procesar el pago. Intenta de nuevo.'
  } finally {
    procesandoPago.value = false
  }
}

function abrirModalPayPal() {
  mostrarModalPayPal.value = true
}

function abrirModalStripe() {
  mostrarModalStripe.value = true
}

function cerrarModalPayPal() {
  mostrarModalPayPal.value = false
}

function cerrarModalStripe() {
  mostrarModalStripe.value = false
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

      <!-- Sección de Anticipo (PRIMERO si requiere) -->
      <div v-if="store.anticipoInfo.requiere_anticipo" class="card anticipo-card">
        <div class="anticipo-header">
          <i class="fa fa-exclamation-circle"></i>
          <h4>Anticipo Requerido</h4>
        </div>
        <p class="anticipo-mensaje">
          Los servicios solicitados requieren un anticipo de 
          <strong>${{ Number(store.anticipoInfo.monto_anticipo).toFixed(2) }}</strong>
        </p>
        
        <div class="anticipo-opciones">
          <div class="anticipo-opcion">
            <button 
              class="btn-anticipo btn-transferencia"
              :class="{ 'activo': store.metodoPagoAnticipo === 'transferencia' }"
              @click="store.seleccionarMetodoPagoAnticipo('transferencia')"
            >
              <i class="fa fa-university"></i>
              <span>Transferencia Bancaria</span>
              <p>Recibirás los datos de cuenta por WhatsApp</p>
            </button>
          </div>
          
          <div class="anticipo-opcion">
            <p class="pasarelas-titulo">Pago con Pasarela</p>
            <div class="pasarelas-cards">
              <!-- Card PayPal -->
              <div 
                class="pasarela-card pasarela-paypal"
                @click="abrirModalPayPal"
              >
                <div class="pasarela-logo">
                  <img src="/logos/PayPal.svg.png" alt="PayPal" />
                </div>
                <div class="pasarela-nombre">PayPal</div>
                <div class="pasarela-badge">Disponible</div>
              </div>

              <!-- Card Stripe -->
              <div 
                class="pasarela-card pasarela-stripe"
                @click="abrirModalStripe"
              >
                <div class="pasarela-logo">
                  <img src="/logos/Stripe_Logo,_revised_2016.svg.png" alt="Stripe" />
                </div>
                <div class="pasarela-nombre">Stripe</div>
                <div class="pasarela-badge">Disponible</div>
              </div>

              <!-- Card Mercado Pago -->
              <div 
                class="pasarela-card pasarela-mercadopago"
                :class="{ 'activo': store.metodoPagoAnticipo === 'pasarela' }"
                @click="pagarConMercadoPago"
              >
                <div class="pasarela-logo">
                  <img src="/logos/mercado-pago-logo.png" alt="Mercado Pago" />
                </div>
                <div class="pasarela-nombre">Mercado Pago</div>
                <div class="pasarela-badge activo" v-if="store.metodoPagoAnticipo === 'pasarela'">Seleccionado</div>
                <div class="pasarela-badge" v-else>Disponible</div>
                <div class="pasarela-loading" v-if="procesandoPago">
                  <i class="fa fa-spinner fa-spin"></i>
                  Procesando...
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Advertencia de 24 horas para transferencia -->
        <div v-if="store.metodoPagoAnticipo === 'transferencia'" class="anticipo-advertencia">
          <div class="advertencia-icon">
            <i class="fa fa-exclamation-triangle"></i>
          </div>
          <div class="advertencia-texto">
            <strong>Importante:</strong> Tienes 24 horas para realizar la transferencia. Si no se recibe el pago en ese tiempo, tu cita será cancelada automáticamente.
          </div>
        </div>

        <!-- Mensaje informativo -->
        <div v-if="store.metodoPagoAnticipo === 'transferencia'" class="anticipo-confirmacion">
          <p class="anticipo-info">
            <i class="fa fa-info-circle"></i>
            Al confirmar, recibirás los datos de cuenta bancaria por WhatsApp para realizar el depósito del anticipo.
          </p>
        </div>
      </div>

      <!-- OTP (solo si no requiere anticipo O si ya se seleccionó método) -->
      <div v-if="debeMostrarOtp" class="card otp-card" :class="{ 'otp-visible': debeMostrarOtp }">
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
          :disabled="confirmando || !puedeConfirmar"
          @click="confirmar"
        >
          <span v-if="confirmando">
            <i class="fa fa-spinner fa-spin"></i>
            Confirmando...
          </span>
          <span v-else-if="store.anticipoInfo.requiere_anticipo && !store.metodoPagoAnticipo">
            <i class="fa fa-lock"></i>
            Selecciona método de pago
          </span>
          <span v-else-if="store.otpCodigo.length !== 6">
            <i class="fa fa-shield-alt"></i>
            Completa el código OTP
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

      <!-- Mensaje si requiere anticipo pero no se ha seleccionado método -->
      <div v-if="store.anticipoInfo.requiere_anticipo && store.metodoPagoAnticipo === null" class="card anticipo-pendiente">
        <div class="pendiente-mensaje">
          <i class="fa fa-lock"></i>
          <p>Por favor selecciona un método de pago para el anticipo para continuar</p>
        </div>
      </div>

      <!-- Volver -->
      <button class="btn-secondary" @click="store.pasoAnterior()">
        <i class="fa fa-arrow-left"></i>
        Volver
      </button>
    </template>

    <!-- Modal PayPal -->
    <div v-if="mostrarModalPayPal" class="modal-overlay" @click.self="cerrarModalPayPal">
      <div class="modal-content">
        <div class="modal-header">
          <div class="modal-logo">
            <img src="/logos/PayPal.svg.png" alt="PayPal" />
          </div>
          <button class="modal-close" @click="cerrarModalPayPal">
            <i class="fa fa-times"></i>
          </button>
        </div>
        <div class="modal-body">
          <div class="modal-icon">
            <i class="fa fa-exclamation-triangle"></i>
          </div>
          <h3>Integración no configurada</h3>
          <p>La llave de integración de PayPal no está configurada en el sistema. Por favor, contacta al administrador para habilitar este método de pago.</p>
        </div>
        <div class="modal-footer">
          <button class="btn-primary" @click="cerrarModalPayPal">Entendido</button>
        </div>
      </div>
    </div>

    <!-- Modal Stripe -->
    <div v-if="mostrarModalStripe" class="modal-overlay" @click.self="cerrarModalStripe">
      <div class="modal-content">
        <div class="modal-header">
          <div class="modal-logo">
            <img src="/logos/Stripe_Logo,_revised_2016.svg.png" alt="Stripe" />
          </div>
          <button class="modal-close" @click="cerrarModalStripe">
            <i class="fa fa-times"></i>
          </button>
        </div>
        <div class="modal-body">
          <div class="modal-icon">
            <i class="fa fa-exclamation-triangle"></i>
          </div>
          <h3>Integración no configurada</h3>
          <p>La llave de integración de Stripe no está configurada en el sistema. Por favor, contacta al administrador para habilitar este método de pago.</p>
        </div>
        <div class="modal-footer">
          <button class="btn-primary" @click="cerrarModalStripe">Entendido</button>
        </div>
      </div>
    </div>
  </div>
</template>
