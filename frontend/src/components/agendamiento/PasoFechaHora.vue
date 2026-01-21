<script setup lang="ts">
import { ref, onMounted, computed, onUnmounted } from 'vue'
import { useAgendamiento } from '@/composables/useAgendamiento'
import AppIcons from './AppIcons.vue'

const { 
  store,
  mesActual,
  anioActual,
  nombreMesActual,
  diasDelMes,
  mesAnterior,
  mesSiguiente,
  seleccionarFecha,
  cargarDisponibilidadMes
} = useAgendamiento()

const diasSemana = ['L', 'M', 'X', 'J', 'V', 'S', 'D']
const mostrarModalHorarios = ref(false)
const reservando = ref(false)

const esModoMultiples = computed(() => store.modoMultiplesEmpleados)

const tiempoRestanteFormateado = computed(() => {
  const segundos = store.reservaTemporal.tiempoRestante
  const mins = Math.floor(segundos / 60)
  const secs = segundos % 60
  return `${mins}:${secs.toString().padStart(2, '0')}`
})

const tieneReservaActiva = computed(() => {
  return store.reservaTemporal.token !== null || store.reservaTemporal.tokens.length > 0
})

async function abrirModalHorarios(fecha: string) {
  if (esModoMultiples.value) {
    await store.cargarSlotsCoordinados(fecha)
  } else {
    await seleccionarFecha(fecha)
  }
  mostrarModalHorarios.value = true
}

async function cerrarModalHorarios() {
  mostrarModalHorarios.value = false
  if (!tieneReservaActiva.value) {
    store.horaSeleccionada = null
    store.slotCoordinadoSeleccionado = null
  }
}

async function seleccionarHoraYReservar(hora: string) {
  store.seleccionarHora(hora)
  reservando.value = true
  store.clearError()
  
  try {
    const exito = await store.reservarSlotTemporal()
    if (!exito) {
      await seleccionarFecha(store.fechaSeleccionada!)
    }
  } finally {
    reservando.value = false
  }
}

async function seleccionarSlotCoordinadoYReservar(slot: any) {
  store.seleccionarSlotCoordinado(slot)
  reservando.value = true
  store.clearError()
  
  try {
    const exito = await store.reservarSlotsTemporalesMultiples()
    if (!exito) {
      await store.cargarSlotsCoordinados(store.fechaSeleccionada!)
    }
  } finally {
    reservando.value = false
  }
}

function continuarConReserva() {
  if (tieneReservaActiva.value) {
    cerrarModalHorarios()
    store.siguientePaso()
  }
}

async function cancelarYLiberar() {
  await store.liberarReservasTemporal()
  store.horaSeleccionada = null
  store.slotCoordinadoSeleccionado = null
  if (esModoMultiples.value) {
    await store.cargarSlotsCoordinados(store.fechaSeleccionada!)
  } else {
    await seleccionarFecha(store.fechaSeleccionada!)
  }
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

onMounted(async () => {
  if (store.modoMultiplesEmpleados) {
    if (store.empleadosPorServicio.length > 0) {
      await cargarDisponibilidadMes()
    }
  } else {
    if (store.empleadoSeleccionado) {
      await cargarDisponibilidadMes()
    }
  }
})

onUnmounted(async () => {
  if (tieneReservaActiva.value && store.paso !== 5) {
    await store.liberarReservasTemporal()
  }
})
</script>

<template>
  <div class="paso-fecha">
    <!-- Header -->
       <div class="page-header">
      <div class="header-icon">
        <AppIcons name="calendar" :size="20" />
      </div>
      <h1>¿Cuándo te viene bien?</h1>
      <p>Selecciona fecha y hora</p>
    </div>

    <!-- Employee Badge -->
    <div v-if="!esModoMultiples && store.empleadoSeleccionado" class="pro-badge">
      <AppIcons name="user-check" :size="16" />
      <span>Con <strong>{{ store.empleadoSeleccionado.nombre }}</strong></span>
    </div>

    <!-- Multiple Employees Badge -->
    <div v-if="esModoMultiples && store.empleadosPorServicio.length > 0" class="pro-badge multiple">
      <AppIcons name="users" :size="16" />
      <span>{{ store.empleadosPorServicio.length }} profesionales</span>
    </div>

    <!-- Calendar -->
    <div class="calendar-card">
      <!-- Calendar Header -->
      <div class="calendar-header">
        <button class="nav-btn" @click="mesAnterior">
          <AppIcons name="chevron-left" :size="20" />
        </button>
        <h3>{{ nombreMesActual }} {{ anioActual }}</h3>
        <button class="nav-btn" @click="mesSiguiente">
          <AppIcons name="chevron-right" :size="20" />
        </button>
      </div>

      <!-- Week Days -->
      <div class="week-days">
        <span v-for="dia in diasSemana" :key="dia">{{ dia }}</span>
      </div>

      <!-- Days Grid -->
      <div class="days-grid" :class="{ 'loading': store.loading }">
        <div 
          v-for="(dia, index) in diasDelMes" 
          :key="index"
          class="day-cell"
          :class="{ 
            'empty': dia.dia === 0,
            'available': dia.disponible,
            'selected': dia.fecha === store.fechaSeleccionada,
            'today': dia.esHoy,
            'disabled': dia.esPasado || !dia.disponible,
          }"
          @click="dia.disponible && abrirModalHorarios(dia.fecha)"
        >
          <span v-if="dia.dia > 0">{{ dia.dia }}</span>
        </div>
      </div>

      <!-- Legend -->
      <div class="legend">
        <div class="legend-item">
          <span class="dot available"></span>
          Disponible
        </div>
        <div class="legend-item">
          <span class="dot"></span>
          No disponible
        </div>
      </div>
    </div>

    <!-- Selection Summary -->
    <div v-if="!esModoMultiples && store.fechaSeleccionada && store.horaSeleccionada" class="summary-card">
      <div class="summary-item">
        <AppIcons name="calendar" :size="18" />
        <div>
          <span class="label">Fecha</span>
          <span class="value">{{ store.fechaSeleccionada }}</span>
        </div>
      </div>
      <div class="summary-item">
        <AppIcons name="clock" :size="18" />
        <div>
          <span class="label">Hora</span>
          <span class="value">{{ formatHora(store.horaSeleccionada) }}</span>
        </div>
      </div>
      <div class="summary-item">
        <AppIcons name="hourglass" :size="18" />
        <div>
          <span class="label">Duración</span>
          <span class="value">{{ store.slotsDisponibles?.duracion_total || 0 }} min</span>
        </div>
      </div>
    </div>

    <!-- Multiple Summary -->
    <div v-if="esModoMultiples && store.slotCoordinadoSeleccionado" class="coordinated-summary">
      <div class="summary-header">
        <AppIcons name="check-circle" :size="18" />
        <span>Horario seleccionado</span>
      </div>
      <div class="timeline">
        <div v-for="(servicio, index) in store.slotCoordinadoSeleccionado.servicios" :key="index" class="timeline-item">
          <div class="timeline-time">
            <span class="time-start">{{ formatHora(servicio.hora_inicio) }}</span>
            <span class="time-arrow">→</span>
            <span class="time-end">{{ formatHora(servicio.hora_fin) }}</span>
          </div>
          <div class="timeline-content">
            <div class="timeline-service">{{ servicio.servicio_nombre }}</div>
            <div class="timeline-employee">
              <AppIcons name="user" :size="12" />
              {{ servicio.empleado_nombre }}
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Time Slots Modal -->
    <Teleport to="body">
      <Transition name="modal">
        <div v-if="mostrarModalHorarios" class="modal-backdrop" @click="cerrarModalHorarios">
          <div class="modal-content" @click.stop>
            <!-- Modal Header -->
            <div class="modal-header">
              <div class="modal-title">
                <h3>
                  <AppIcons name="calendar" :size="18" />
                  {{ store.fechaSeleccionada }}
                </h3>
                <p v-if="!esModoMultiples">Selecciona un horario</p>
                <p v-else>Selecciona un horario coordinado</p>
              </div>
              <button class="modal-close" @click="cerrarModalHorarios">
                <AppIcons name="x" :size="18" />
              </button>
            </div>

            <!-- Modal Body -->
            <div class="modal-body">
              <!-- Loading -->
              <div v-if="store.loading" class="modal-loading">
                <AppIcons name="loader" :size="24" class="spinner" />
                <span>Cargando horarios...</span>
              </div>

              <!-- Single Mode: Time Slots -->
              <template v-else-if="!esModoMultiples">
                <div v-if="store.slotsDisponibles?.mensaje" class="modal-message">
                  <AppIcons name="info" :size="24" />
                  {{ store.slotsDisponibles.mensaje }}
                </div>

                <div v-else-if="store.slotsDisponibles?.slots.length === 0" class="modal-message">
                  <AppIcons name="calendar" :size="24" />
                  No hay horarios disponibles
                </div>

                <div v-else class="time-slots">
                  <button 
                    v-for="slot in store.slotsDisponibles?.slots" 
                    :key="slot.hora"
                    class="time-slot"
                    :class="{ 
                      'selected': store.horaSeleccionada === slot.hora,
                      'loading': reservando && store.horaSeleccionada === slot.hora
                    }"
                    :disabled="reservando"
                    @click="seleccionarHoraYReservar(slot.hora)"
                  >
                    <AppIcons v-if="reservando && store.horaSeleccionada === slot.hora" name="loader" :size="16" class="spinner" />
                    <span v-else>{{ formatHora(slot.hora) }}</span>
                  </button>
                </div>
              </template>

              <!-- Multiple Mode: Coordinated Slots -->
              <template v-else>
                <div v-if="!store.slotsCoordinados" class="modal-message">
                  <AppIcons name="loader" :size="24" class="spinner" />
                  Cargando horarios coordinados...
                </div>

                <div v-else-if="store.slotsCoordinados.mensaje" class="modal-message">
                  <AppIcons name="info" :size="24" />
                  {{ store.slotsCoordinados.mensaje }}
                </div>

                <div v-else-if="!store.slotsCoordinados.slots_validos?.length" class="modal-message">
                  <AppIcons name="calendar" :size="24" />
                  No hay horarios donde todos encajen
                </div>

                <div v-else class="coordinated-slots">
                  <div 
                    v-for="slot in store.slotsCoordinados?.slots_validos" 
                    :key="slot.hora"
                    class="coordinated-slot"
                    :class="{ 
                      'selected': store.slotCoordinadoSeleccionado?.hora === slot.hora,
                      'loading': reservando && store.slotCoordinadoSeleccionado?.hora === slot.hora
                    }"
                    @click="!reservando && seleccionarSlotCoordinadoYReservar(slot)"
                  >
                    <div class="slot-main-time">
                      <AppIcons name="clock" :size="16" />
                      {{ formatHora(slot.hora) }}
                    </div>
                    <div class="slot-details">
                      <div v-for="(servicio, idx) in slot.servicios" :key="idx" class="slot-service">
                        <span class="service-time">{{ formatHora(servicio.hora_inicio) }} - {{ formatHora(servicio.hora_fin) }}</span>
                        <span class="service-name">{{ servicio.servicio_nombre }}</span>
                        <span class="service-employee">
                          <AppIcons name="user" :size="10" />
                          {{ servicio.empleado_nombre }}
                        </span>
                      </div>
                    </div>
                    <div class="slot-end-time">
                      <AppIcons name="hourglass" :size="14" />
                      Termina {{ formatHora(slot.hora_fin) }}
                    </div>
                  </div>
                </div>
              </template>
            </div>

            <!-- Modal Footer -->
            <div class="modal-footer">
              <!-- Timer -->
              <div v-if="tieneReservaActiva" class="reservation-timer">
                <AppIcons name="clock" :size="16" />
                <span>Reservado por <strong>{{ tiempoRestanteFormateado }}</strong></span>
              </div>
              
              <div class="modal-actions">
                <button 
                  v-if="tieneReservaActiva"
                  class="btn-cancel" 
                  @click="cancelarYLiberar"
                  :disabled="reservando"
                >
                  Cancelar
                </button>
                <button 
                  v-else
                  class="btn-cancel" 
                  @click="cerrarModalHorarios"
                >
                  Cerrar
                </button>
                
                <button 
                  class="btn-confirm" 
                  @click="continuarConReserva"
                  :disabled="!tieneReservaActiva || reservando"
                >
                  <span v-if="reservando">
                    <AppIcons name="loader" :size="16" class="spinner" />
                    Reservando...
                  </span>
                  <span v-else>
                    Continuar
                    <AppIcons name="chevron-right" :size="16" />
                  </span>
                </button>
              </div>
            </div>
          </div>
        </div>
      </Transition>
    </Teleport>
  </div>
</template>

<style scoped>
.paso-fecha {
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

/* Pro Badge */
.pro-badge {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  padding: 10px 12px;
  background: rgba(0, 122, 255, 0.08);
  color: #007aff;
  border: 1px solid rgba(0, 122, 255, 0.15);
  border-radius: 14px;
  font-size: 15px;
  font-weight: 500;
  margin-bottom: 15px;
}

.theme-dark .pro-badge {
  background: rgba(0, 122, 255, 0.12);
  border-color: rgba(0, 122, 255, 0.2);
}

.pro-badge strong {
  font-weight: 600;
  color: #1d1d1f;
}

.theme-dark .pro-badge strong {
  color: #f5f5f7;
}

.pro-badge.multiple {
  background: rgba(0, 122, 255, 0.08);
  border-color: rgba(0, 122, 255, 0.15);
}

.theme-dark .pro-badge.multiple {
  background: rgba(0, 122, 255, 0.12);
  border-color: rgba(0, 122, 255, 0.2);
}

/* Calendar Card */
.calendar-card {
  background: rgba(255, 255, 255, 0.95);
  backdrop-filter: blur(20px);
  border-radius: 20px;
  padding: 24px;
  margin-bottom: 20px;
  border: 1px solid rgba(0,0,0,0.04);
  box-shadow: 0 2px 20px rgba(0,0,0,0.04);
}

.theme-dark .calendar-card {
  background: rgba(28, 28, 30, 0.95);
  border-color: rgba(255,255,255,0.06);
}

.calendar-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 20px;
}

.calendar-header h3 {
  font-size: 17px;
  font-weight: 600;
  color: #1d1d1f;
  text-transform: capitalize;
  margin: 0;
}

.theme-dark .calendar-header h3 {
  color: #f5f5f7;
}

.nav-btn {
  width: 36px;
  height: 36px;
  border-radius: 10px;
  background: rgba(0,0,0,0.04);
  border: none;
  color: #1d1d1f;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all 0.2s;
}

.theme-dark .nav-btn {
  background: rgba(255,255,255,0.06);
  color: #f5f5f7;
}

.nav-btn:hover {
  background: #007aff;
  color: white;
}

/* Week Days */
.week-days {
  display: grid;
  grid-template-columns: repeat(7, 1fr);
  margin-bottom: 8px;
}

.week-days span {
  text-align: center;
  font-size: 11px;
  font-weight: 600;
  color: #86868b;
  padding: 8px 0;
}

/* Days Grid */
.days-grid {
  display: grid;
  grid-template-columns: repeat(7, 1fr);
  gap: 4px;
  transition: opacity 0.3s;
}

.days-grid.loading {
  opacity: 0.5;
}

.day-cell {
  aspect-ratio: 1;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 10px;
  font-size: 14px;
  font-weight: 500;
  color: #1d1d1f;
  cursor: pointer;
  transition: all 0.2s;
}

.theme-dark .day-cell {
  color: #f5f5f7;
}

.day-cell.empty {
  cursor: default;
}

.day-cell.disabled {
  opacity: 0.3;
  cursor: not-allowed;
}

.day-cell.available {
  background: rgba(52, 199, 89, 0.1);
  color: #248a3d;
}

.theme-dark .day-cell.available {
  background: rgba(52, 199, 89, 0.15);
  color: #30d158;
}

.day-cell.available:hover {
  background: rgba(52, 199, 89, 0.2);
  transform: scale(1.05);
}

.day-cell.today {
  border: 2px solid #007aff;
}

.day-cell.selected {
  background: #007aff !important;
  color: white !important;
  transform: scale(1.05);
}

/* Legend */
.legend {
  display: flex;
  justify-content: center;
  gap: 20px;
  margin-top: 20px;
  padding-top: 20px;
  border-top: 1px solid rgba(0,0,0,0.06);
}

.theme-dark .legend {
  border-color: rgba(255,255,255,0.08);
}

.legend-item {
  display: flex;
  align-items: center;
  gap: 6px;
  font-size: 11px;
  color: #86868b;
}

.dot {
  width: 8px;
  height: 8px;
  border-radius: 4px;
  background: #d1d1d6;
}

.dot.available {
  background: #34c759;
}

/* Summary Card */
.summary-card {
  background: rgba(255, 255, 255, 0.95);
  backdrop-filter: blur(20px);
  border-radius: 16px;
  padding: 16px;
  display: flex;
  justify-content: space-around;
  border: 1px solid rgba(52, 199, 89, 0.2);
  margin-bottom: 16px;
}

.theme-dark .summary-card {
  background: rgba(28, 28, 30, 0.95);
}

.summary-item {
  display: flex;
  align-items: center;
  gap: 10px;
  text-align: left;
}

.summary-item svg {
  color: #007aff;
}

.summary-item .label {
  display: block;
  font-size: 10px;
  color: #86868b;
  text-transform: uppercase;
  letter-spacing: 0.04em;
}

.summary-item .value {
  display: block;
  font-size: 13px;
  font-weight: 600;
  color: #1d1d1f;
}

.theme-dark .summary-item .value {
  color: #f5f5f7;
}

/* Coordinated Summary */
.coordinated-summary {
  background: rgba(255, 255, 255, 0.95);
  backdrop-filter: blur(20px);
  border-radius: 16px;
  padding: 16px;
  border: 1px solid rgba(52, 199, 89, 0.2);
  margin-bottom: 16px;
}

.theme-dark .coordinated-summary {
  background: rgba(28, 28, 30, 0.95);
}

.summary-header {
  display: flex;
  align-items: center;
  gap: 8px;
  margin-bottom: 14px;
  color: #34c759;
  font-weight: 600;
  font-size: 14px;
}

.timeline {
  display: flex;
  flex-direction: column;
  gap: 10px;
}

.timeline-item {
  display: flex;
  gap: 12px;
  padding: 10px;
  background: rgba(0,0,0,0.02);
  border-radius: 10px;
}

.theme-dark .timeline-item {
  background: rgba(255,255,255,0.04);
}

.timeline-time {
  display: flex;
  flex-direction: column;
  align-items: center;
  min-width: 70px;
  font-size: 12px;
}

.time-start {
  font-weight: 600;
  color: #1d1d1f;
}

.theme-dark .time-start {
  color: #f5f5f7;
}

.time-arrow {
  color: #86868b;
  font-size: 10px;
}

.time-end {
  color: #86868b;
  font-size: 11px;
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
  color: #007aff;
  display: flex;
  align-items: center;
  gap: 4px;
}

/* Modal */
.modal-backdrop {
  position: fixed;
  inset: 0;
  background: rgba(0,0,0,0.5);
  backdrop-filter: blur(4px);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1000;
  padding: 20px;
}

.modal-content {
  background: rgba(255, 255, 255, 0.98);
  backdrop-filter: blur(20px);
  border-radius: 24px;
  width: 100%;
  max-width: 440px;
  max-height: 90vh;
  display: flex;
  flex-direction: column;
  box-shadow: 0 20px 60px rgba(0,0,0,0.2);
  border: 1px solid rgba(0,0,0,0.06);
}

.theme-dark .modal-content {
  background: rgba(28, 28, 30, 0.98);
  border-color: rgba(255,255,255,0.08);
}

.modal-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  padding: 24px;
  border-bottom: 1px solid rgba(0,0,0,0.06);
}

.theme-dark .modal-header {
  border-color: rgba(255,255,255,0.08);
}

.modal-title h3 {
  margin: 0 0 6px;
  font-size: 18px;
  font-weight: 600;
  color: #1d1d1f;
  display: flex;
  align-items: center;
  gap: 8px;
}

.theme-dark .modal-title h3 {
  color: #f5f5f7;
}

.modal-title h3 svg {
  color: #007aff;
}

.modal-title p {
  margin: 0;
  font-size: 14px;
  color: #86868b;
}

.modal-close {
  width: 32px;
  height: 32px;
  border-radius: 8px;
  background: rgba(0,0,0,0.04);
  border: none;
  color: #86868b;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all 0.2s;
}

.theme-dark .modal-close {
  background: rgba(255,255,255,0.06);
}

.modal-close:hover {
  background: #ff3b30;
  color: white;
}

.modal-body {
  padding: 20px 24px;
  overflow-y: auto;
  flex: 1;
  max-height: calc(90vh - 180px);
}

.modal-loading {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 10px;
  padding: 40px 20px;
  color: #86868b;
  font-size: 14px;
}

.modal-message {
  text-align: center;
  padding: 40px 20px;
  color: #86868b;
  font-size: 14px;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 12px;
}

.modal-message svg {
  opacity: 0.5;
}

/* Time Slots */
.time-slots {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 10px;
}

.time-slot {
  padding: 14px 12px;
  border: 1px solid rgba(0,0,0,0.1);
  border-radius: 12px;
  background: white;
  font-size: 14px;
  font-weight: 600;
  color: #1d1d1f;
  cursor: pointer;
  transition: all 0.2s;
  font-family: inherit;
  display: flex;
  align-items: center;
  justify-content: center;
}

.theme-dark .time-slot {
  background: rgba(44,44,46,1);
  border-color: rgba(255,255,255,0.1);
  color: #f5f5f7;
}

.time-slot:hover {
  border-color: #007aff;
  color: #007aff;
}

.time-slot.selected {
  background: #007aff;
  border-color: #007aff;
  color: white;
}

.time-slot.loading {
  opacity: 0.7;
  pointer-events: none;
}

/* Coordinated Slots */
.coordinated-slots {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.coordinated-slot {
  background: rgba(0, 122, 255, 0.04);
  border-radius: 14px;
  padding: 16px;
  cursor: pointer;
  transition: all 0.2s;
  border: 1px solid rgba(0, 122, 255, 0.1);
}

.theme-dark .coordinated-slot {
  background: rgba(0, 122, 255, 0.08);
}

.coordinated-slot:hover {
  background: rgba(0, 122, 255, 0.08);
  border-color: rgba(0, 122, 255, 0.2);
}

.coordinated-slot.selected {
  background: rgba(0, 122, 255, 0.12);
  border-color: #007aff;
}

.coordinated-slot.loading {
  opacity: 0.7;
  pointer-events: none;
}

.slot-main-time {
  display: flex;
  align-items: center;
  gap: 8px;
  font-size: 16px;
  font-weight: 600;
  color: #007aff;
  margin-bottom: 12px;
}

.slot-details {
  display: flex;
  flex-direction: column;
  gap: 8px;
  padding-left: 8px;
  border-left: 2px solid rgba(0,0,0,0.08);
  margin-bottom: 12px;
}

.theme-dark .slot-details {
  border-color: rgba(255,255,255,0.1);
}

.slot-service {
  padding-left: 10px;
  position: relative;
}

.slot-service::before {
  content: '';
  position: absolute;
  left: -6px;
  top: 6px;
  width: 6px;
  height: 6px;
  border-radius: 3px;
  background: #007aff;
}

.service-time {
  font-size: 11px;
  color: #86868b;
  display: block;
  margin-bottom: 2px;
}

.service-name {
  font-size: 13px;
  font-weight: 600;
  color: #1d1d1f;
  display: block;
}

.theme-dark .service-name {
  color: #f5f5f7;
}

.service-employee {
  font-size: 11px;
  color: #86868b;
  display: flex;
  align-items: center;
  gap: 4px;
}

.slot-end-time {
  display: flex;
  align-items: center;
  gap: 6px;
  font-size: 12px;
  color: #86868b;
  padding-top: 10px;
  border-top: 1px dashed rgba(0,0,0,0.08);
}

.theme-dark .slot-end-time {
  border-color: rgba(255,255,255,0.1);
}

.slot-end-time svg {
  color: #007aff;
}

/* Modal Footer */
.modal-footer {
  display: flex;
  flex-direction: column;
  gap: 12px;
  padding: 20px 24px;
  border-top: 1px solid rgba(0,0,0,0.06);
  background: rgba(248,248,248,0.5);
}

.theme-dark .modal-footer {
  background: rgba(18,18,18,0.5);
  border-color: rgba(255,255,255,0.08);
}

.reservation-timer {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  padding: 10px;
  background: rgba(255, 149, 0, 0.1);
  border: 1px solid rgba(255, 149, 0, 0.2);
  border-radius: 10px;
  color: #cc7700;
  font-size: 13px;
}

.theme-dark .reservation-timer {
  color: #ffb74d;
}

.reservation-timer strong {
  font-family: monospace;
  font-size: 15px;
}

.modal-actions {
  display: flex;
  gap: 12px;
}

.btn-cancel {
  flex: 1;
  padding: 14px;
  border: 1px solid rgba(0,0,0,0.08);
  border-radius: 12px;
  background: white;
  font-size: 15px;
  font-weight: 600;
  color: #1d1d1f;
  cursor: pointer;
  transition: all 0.2s;
  font-family: inherit;
}

.theme-dark .btn-cancel {
  background: rgba(44,44,46,0.8);
  border-color: rgba(255,255,255,0.1);
  color: #f5f5f7;
}

.btn-cancel:hover {
  background: rgba(0,0,0,0.04);
}

.btn-confirm {
  flex: 1;
  padding: 14px;
  border: none;
  border-radius: 12px;
  background: #007aff;
  color: white;
  font-size: 15px;
  font-weight: 600;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 6px;
  transition: all 0.2s;
  font-family: inherit;
}

.btn-confirm:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.btn-confirm:not(:disabled):hover {
  background: #0066d6;
}

/* Spinner */
.spinner {
  animation: spin 1s linear infinite;
}

@keyframes spin {
  from { transform: rotate(0deg); }
  to { transform: rotate(360deg); }
}

/* Modal Transitions */
.modal-enter-active,
.modal-leave-active {
  transition: opacity 0.25s ease;
}

.modal-enter-active .modal-content,
.modal-leave-active .modal-content {
  transition: transform 0.25s ease, opacity 0.25s ease;
}

.modal-enter-from,
.modal-leave-to {
  opacity: 0;
}

.modal-enter-from .modal-content,
.modal-leave-to .modal-content {
  transform: translateY(20px);
  opacity: 0;
}

/* Responsive */
@media (max-width: 480px) {
  .time-slots {
    grid-template-columns: repeat(2, 1fr);
  }
}

@media (max-width: 380px) {
  .paso-fecha {
    padding: 16px 12px;
  }
  
  .calendar-card {
    padding: 20px;
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
  
  .summary-card {
    flex-direction: column;
    gap: 12px;
  }
}
</style>
