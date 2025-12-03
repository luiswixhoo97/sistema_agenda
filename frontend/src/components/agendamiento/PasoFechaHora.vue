<script setup lang="ts">
import { ref, onMounted, computed, onUnmounted } from 'vue'
import { useAgendamiento } from '@/composables/useAgendamiento'

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

// Verificar si está en modo múltiples empleados
const esModoMultiples = computed(() => store.modoMultiplesEmpleados)

// Tiempo restante formateado (MM:SS)
const tiempoRestanteFormateado = computed(() => {
  const segundos = store.reservaTemporal.tiempoRestante
  const mins = Math.floor(segundos / 60)
  const secs = segundos % 60
  return `${mins}:${secs.toString().padStart(2, '0')}`
})

// Verificar si hay una reserva activa
const tieneReservaActiva = computed(() => {
  return store.reservaTemporal.token !== null || store.reservaTemporal.tokens.length > 0
})

async function abrirModalHorarios(fecha: string) {
  if (esModoMultiples.value) {
    // Cargar slots coordinados
    await store.cargarSlotsCoordinados(fecha)
  } else {
    // Cargar slots normales
    await seleccionarFecha(fecha)
  }
  mostrarModalHorarios.value = true
}

async function cerrarModalHorarios() {
  mostrarModalHorarios.value = false
  // Si hay una hora seleccionada pero no se ha reservado, limpiar
  if (!tieneReservaActiva.value) {
    store.horaSeleccionada = null
    store.slotCoordinadoSeleccionado = null
  }
}

// Seleccionar hora y reservar temporalmente
async function seleccionarHoraYReservar(hora: string) {
  store.seleccionarHora(hora)
  reservando.value = true
  store.clearError()
  
  try {
    const exito = await store.reservarSlotTemporal()
    if (!exito) {
      // La reserva falló, recargar slots
      await seleccionarFecha(store.fechaSeleccionada!)
    }
  } finally {
    reservando.value = false
  }
}

// Seleccionar slot coordinado y reservar temporalmente
async function seleccionarSlotCoordinadoYReservar(slot: any) {
  store.seleccionarSlotCoordinado(slot)
  reservando.value = true
  store.clearError()
  
  try {
    const exito = await store.reservarSlotsTemporalesMultiples()
    if (!exito) {
      // La reserva falló, recargar slots
      await store.cargarSlotsCoordinados(store.fechaSeleccionada!)
    }
  } finally {
    reservando.value = false
  }
}

// Continuar al siguiente paso (ya con reserva activa)
function continuarConReserva() {
  if (tieneReservaActiva.value) {
    cerrarModalHorarios()
    store.siguientePaso()
  }
}

// Cancelar y liberar reserva
async function cancelarYLiberar() {
  await store.liberarReservasTemporal()
  store.horaSeleccionada = null
  store.slotCoordinadoSeleccionado = null
  // Recargar slots
  if (esModoMultiples.value) {
    await store.cargarSlotsCoordinados(store.fechaSeleccionada!)
  } else {
    await seleccionarFecha(store.fechaSeleccionada!)
  }
}

function formatHora(hora: string): string {
  if (!hora) return ''
  const [hours, minutes] = hora.split(':')
  const hour = parseInt(hours)
  const ampm = hour >= 12 ? 'PM' : 'AM'
  const hour12 = hour % 12 || 12
  return `${hour12}:${minutes} ${ampm}`
}

onMounted(async () => {
  // En modo múltiples, verificar que hay empleados asignados
  if (store.modoMultiplesEmpleados) {
    if (store.empleadosPorServicio.length > 0) {
      await cargarDisponibilidadMes()
    } else {
      console.warn('Modo múltiples: No hay empleados asignados aún')
    }
  } else {
    // Modo normal: verificar que hay empleado seleccionado
    if (store.empleadoSeleccionado) {
      await cargarDisponibilidadMes()
    }
  }
})

// Limpiar al desmontar el componente
onUnmounted(async () => {
  // Si el usuario sale de este paso sin completar, liberar reservas
  if (tieneReservaActiva.value && store.paso !== 5) {
    await store.liberarReservasTemporal()
  }
})
</script>

<template>
  <div class="paso-fecha">
    <!-- Header -->
    <div class="paso-header">
      <div class="header-icon">
        <i class="fa fa-calendar-alt"></i>
      </div>
      <h2>¿Cuándo te viene bien?</h2>
      <p>Selecciona fecha y hora</p>
    </div>

    <!-- Empleado badge (modo normal) -->
    <div v-if="!esModoMultiples && store.empleadoSeleccionado" class="empleado-badge">
      <i class="fa fa-user-check"></i>
      <span>Con <strong>{{ store.empleadoSeleccionado.nombre }}</strong></span>
    </div>

    <!-- Empleados badge (modo múltiples) -->
    <div v-if="esModoMultiples && store.empleadosPorServicio.length > 0" class="empleados-multiples-badge">
      <i class="fa fa-users"></i>
      <span>{{ store.empleadosPorServicio.length }} profesionales asignados</span>
    </div>

    <!-- Calendario -->
    <div class="calendario-card">
      <!-- Header calendario -->
      <div class="calendario-header">
        <button class="nav-btn" @click="mesAnterior">
          <i class="fa fa-chevron-left"></i>
        </button>
        <h3>{{ nombreMesActual }} {{ anioActual }}</h3>
        <button class="nav-btn" @click="mesSiguiente">
          <i class="fa fa-chevron-right"></i>
        </button>
      </div>

      <!-- Días semana -->
      <div class="dias-semana">
        <span v-for="dia in diasSemana" :key="dia">{{ dia }}</span>
      </div>

      <!-- Días mes -->
      <div class="dias-grid" :class="{ 'loading': store.loading }">
        <div 
          v-for="(dia, index) in diasDelMes" 
          :key="index"
          class="dia-cell"
          :class="{ 
            'vacio': dia.dia === 0,
            'disponible': dia.disponible,
            'seleccionado': dia.fecha === store.fechaSeleccionada,
            'hoy': dia.esHoy,
            'pasado': dia.esPasado || !dia.disponible,
          }"
          @click="dia.disponible && abrirModalHorarios(dia.fecha)"
        >
          <span v-if="dia.dia > 0">{{ dia.dia }}</span>
        </div>
      </div>

      <!-- Leyenda -->
      <div class="leyenda">
        <div class="leyenda-item">
          <span class="dot disponible"></span>
          Disponible
        </div>
        <div class="leyenda-item">
          <span class="dot"></span>
          No disponible
        </div>
      </div>
    </div>

    <!-- Resumen (modo normal) -->
    <div v-if="!esModoMultiples && store.fechaSeleccionada && store.horaSeleccionada" class="resumen-card">
      <div class="resumen-item">
        <i class="fa fa-calendar"></i>
        <div>
          <span class="label">Fecha</span>
          <span class="value">{{ store.fechaSeleccionada }}</span>
        </div>
      </div>
      <div class="resumen-item">
        <i class="fa fa-clock"></i>
        <div>
          <span class="label">Hora</span>
          <span class="value">{{ formatHora(store.horaSeleccionada) }}</span>
        </div>
      </div>
      <div class="resumen-item">
        <i class="fa fa-hourglass-half"></i>
        <div>
          <span class="label">Duración</span>
          <span class="value">{{ store.slotsDisponibles?.duracion_total || 0 }} min</span>
        </div>
      </div>
    </div>

    <!-- Resumen (modo múltiples) -->
    <div v-if="esModoMultiples && store.slotCoordinadoSeleccionado" class="resumen-coordinado">
      <div class="resumen-header">
        <i class="fa fa-check-circle"></i>
        <span>Horario seleccionado</span>
      </div>
      <div class="timeline-servicios">
        <div 
          v-for="(servicio, index) in store.slotCoordinadoSeleccionado.servicios" 
          :key="index"
          class="timeline-item"
        >
          <div class="timeline-time">
            <span class="hora-inicio">{{ formatHora(servicio.hora_inicio) }}</span>
            <span class="separador">→</span>
            <span class="hora-fin">{{ formatHora(servicio.hora_fin) }}</span>
          </div>
          <div class="timeline-content">
            <div class="timeline-servicio">{{ servicio.servicio_nombre }}</div>
            <div class="timeline-empleado">
              <i class="fa fa-user"></i>
              {{ servicio.empleado_nombre }}
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Modal Horarios -->
    <Teleport to="body">
      <Transition name="modal">
        <div v-if="mostrarModalHorarios" class="modal-backdrop" @click="cerrarModalHorarios">
          <div class="modal-horarios" @click.stop>
            <div class="modal-header-horarios">
              <div>
                <h3>
                  <i class="fa fa-calendar-alt"></i>
                  {{ store.fechaSeleccionada }}
                </h3>
                <p v-if="!esModoMultiples">Selecciona un horario disponible</p>
                <p v-else>Selecciona un horario donde todos encajen</p>
              </div>
              <button class="modal-close" @click="cerrarModalHorarios">
                <i class="fa fa-times"></i>
              </button>
            </div>

            <div class="modal-body-horarios">
              <!-- Loading horarios -->
              <div v-if="store.loading" class="loading-horarios">
                <div class="spinner-sm"></div>
                <span>Cargando horarios...</span>
              </div>

              <!-- ===================================== -->
              <!-- MODO NORMAL: Horarios simples -->
              <!-- ===================================== -->
              <template v-else-if="!esModoMultiples">
                <!-- Mensaje -->
                <div v-if="store.slotsDisponibles?.mensaje" class="horarios-mensaje">
                  <i class="fa fa-info-circle"></i>
                  {{ store.slotsDisponibles.mensaje }}
                </div>

                <!-- Sin horarios -->
                <div v-else-if="store.slotsDisponibles?.slots.length === 0" class="horarios-mensaje">
                  <i class="fa fa-calendar-times"></i>
                  No hay horarios disponibles para esta fecha
                </div>

                <!-- Grid horarios -->
                <div v-else class="horarios-grid-modal">
                  <button 
                    v-for="slot in store.slotsDisponibles?.slots" 
                    :key="slot.hora"
                    class="horario-btn-modal"
                    :class="{ 
                      'selected': store.horaSeleccionada === slot.hora,
                      'reservando': reservando && store.horaSeleccionada === slot.hora
                    }"
                    :disabled="reservando"
                    @click="seleccionarHoraYReservar(slot.hora)"
                  >
                    <span v-if="reservando && store.horaSeleccionada === slot.hora">
                      <i class="fa fa-spinner fa-spin"></i>
                    </span>
                    <span v-else>{{ formatHora(slot.hora) }}</span>
                  </button>
                </div>
              </template>

              <!-- ===================================== -->
              <!-- MODO MÚLTIPLES: Horarios coordinados -->
              <!-- ===================================== -->
              <template v-else>
                <!-- No se han cargado slots aún -->
                <div v-if="!store.slotsCoordinados" class="horarios-mensaje">
                  <i class="fa fa-calendar-alt"></i>
                  Cargando horarios coordinados...
                </div>

                <!-- Mensaje de error o información -->
                <div v-else-if="store.slotsCoordinados.mensaje" class="horarios-mensaje">
                  <i class="fa fa-info-circle"></i>
                  {{ store.slotsCoordinados.mensaje }}
                </div>

                <!-- Sin horarios -->
                <div v-else-if="!store.slotsCoordinados.slots_validos || store.slotsCoordinados.slots_validos.length === 0" class="horarios-mensaje">
                  <i class="fa fa-calendar-times"></i>
                  No hay horarios donde todos los servicios encajen
                </div>

                <!-- Lista de slots coordinados -->
                <div v-else class="slots-coordinados-list">
                  <div 
                    v-for="slot in store.slotsCoordinados?.slots_validos" 
                    :key="slot.hora"
                    class="slot-coordinado-card"
                    :class="{ 
                      'selected': store.slotCoordinadoSeleccionado?.hora === slot.hora,
                      'reservando': reservando && store.slotCoordinadoSeleccionado?.hora === slot.hora
                    }"
                    @click="!reservando && seleccionarSlotCoordinadoYReservar(slot)"
                  >
                    <div class="slot-hora-principal">
                      <i class="fa fa-clock"></i>
                      {{ formatHora(slot.hora) }}
                    </div>
                    <div class="slot-timeline">
                      <div 
                        v-for="(servicio, idx) in slot.servicios" 
                        :key="idx"
                        class="slot-servicio"
                      >
                        <div class="servicio-tiempo">
                          {{ formatHora(servicio.hora_inicio) }} - {{ formatHora(servicio.hora_fin) }}
                        </div>
                        <div class="servicio-nombre">{{ servicio.servicio_nombre }}</div>
                        <div class="servicio-empleado">
                          <i class="fa fa-user"></i>
                          {{ servicio.empleado_nombre }}
                        </div>
                      </div>
                    </div>
                    <div class="slot-duracion-total">
                      <i class="fa fa-hourglass-end"></i>
                      Termina a las {{ formatHora(slot.hora_fin) }}
                    </div>
                  </div>
                </div>
              </template>
            </div>

            <div class="modal-footer-horarios">
              <!-- Temporizador de reserva -->
              <div v-if="tieneReservaActiva" class="temporizador-reserva">
                <i class="fa fa-clock"></i>
                <span>Reservado por <strong>{{ tiempoRestanteFormateado }}</strong></span>
              </div>
              
              <div class="footer-buttons">
                <button 
                  v-if="tieneReservaActiva"
                  class="btn-cancelar" 
                  @click="cancelarYLiberar"
                  :disabled="reservando"
                >
                  Cancelar reserva
                </button>
                <button 
                  v-else
                  class="btn-cancelar" 
                  @click="cerrarModalHorarios"
                >
                  Cerrar
                </button>
                
                <button 
                  class="btn-continuar" 
                  @click="continuarConReserva"
                  :disabled="!tieneReservaActiva || reservando"
                >
                  <span v-if="reservando">
                    <i class="fa fa-spinner fa-spin"></i>
                    Reservando...
                  </span>
                  <span v-else>
                    Continuar
                    <i class="fa fa-chevron-right"></i>
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

/* Empleado badge */
.empleado-badge {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  padding: 10px 16px;
  background: linear-gradient(135deg, #ec407a, #d81b60);
  color: white;
  border-radius: 25px;
  font-size: 13px;
  margin-bottom: 16px;
}

.empleado-badge i {
  font-size: 12px;
}

/* Empleados múltiples badge */
.empleados-multiples-badge {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  padding: 10px 16px;
  background: linear-gradient(135deg, #7b1fa2, #9c27b0);
  color: white;
  border-radius: 25px;
  font-size: 13px;
  margin-bottom: 16px;
}

.empleados-multiples-badge i {
  font-size: 14px;
}

/* Calendario card */
.calendario-card {
  background: var(--color-card);
  border-radius: 20px;
  padding: 20px;
  margin-bottom: 16px;
}

.calendario-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 20px;
}

.calendario-header h3 {
  font-size: 16px;
  font-weight: 700;
  color: var(--color-text);
  text-transform: capitalize;
  margin: 0;
}

.nav-btn {
  width: 36px;
  height: 36px;
  border-radius: 10px;
  background: rgba(0,0,0,0.05);
  border: none;
  color: var(--color-text);
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all 0.2s;
}

.theme-dark .nav-btn {
  background: rgba(255,255,255,0.1);
}

.nav-btn:hover {
  background: #ec407a;
  color: white;
}

/* Días semana */
.dias-semana {
  display: grid;
  grid-template-columns: repeat(7, 1fr);
  margin-bottom: 8px;
}

.dias-semana span {
  text-align: center;
  font-size: 11px;
  font-weight: 600;
  color: var(--color-text-secondary);
  padding: 8px 0;
}

/* Días grid */
.dias-grid {
  display: grid;
  grid-template-columns: repeat(7, 1fr);
  gap: 4px;
  transition: opacity 0.3s;
}

.dias-grid.loading {
  opacity: 0.5;
}

.dia-cell {
  aspect-ratio: 1;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 10px;
  font-size: 13px;
  font-weight: 500;
  color: var(--color-text);
  cursor: pointer;
  transition: all 0.2s;
}

.dia-cell.vacio {
  cursor: default;
}

.dia-cell.pasado {
  opacity: 0.3;
  cursor: not-allowed;
}

.dia-cell.disponible {
  background: rgba(76, 175, 80, 0.12);
  color: #2e7d32;
}

.theme-dark .dia-cell.disponible {
  background: rgba(76, 175, 80, 0.2);
  color: #81c784;
}

.dia-cell.disponible:hover {
  background: rgba(76, 175, 80, 0.25);
  transform: scale(1.1);
}

.dia-cell.hoy {
  border: 2px solid #ec407a;
}

.dia-cell.seleccionado {
  background: linear-gradient(135deg, #ec407a, #d81b60) !important;
  color: white !important;
  box-shadow: 0 4px 12px rgba(236, 64, 122, 0.4);
  transform: scale(1.05);
}

/* Leyenda */
.leyenda {
  display: flex;
  justify-content: center;
  gap: 20px;
  margin-top: 16px;
  padding-top: 16px;
  border-top: 1px solid var(--color-border);
}

.leyenda-item {
  display: flex;
  align-items: center;
  gap: 6px;
  font-size: 11px;
  color: var(--color-text-secondary);
}

.dot {
  width: 10px;
  height: 10px;
  border-radius: 50%;
  background: #ddd;
}

.dot.disponible {
  background: #4caf50;
}

/* Loading horarios */
.loading-horarios {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 10px;
  padding: 20px;
  color: var(--color-text-secondary);
  font-size: 13px;
}

.spinner-sm {
  width: 20px;
  height: 20px;
  border: 2px solid rgba(236,64,122,0.2);
  border-top-color: #ec407a;
  border-radius: 50%;
  animation: spin 0.8s linear infinite;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}

/* Horarios mensaje */
.horarios-mensaje {
  text-align: center;
  padding: 20px;
  color: var(--color-text-secondary);
  font-size: 13px;
}

.horarios-mensaje i {
  display: block;
  font-size: 30px;
  margin-bottom: 10px;
  opacity: 0.5;
}

/* Modal Horarios */
.modal-backdrop {
  position: fixed;
  inset: 0;
  background: rgba(0, 0, 0, 0.6);
  backdrop-filter: blur(4px);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1000;
  padding: 20px;
}

.modal-horarios {
  background: var(--color-card);
  border-radius: 24px;
  width: 100%;
  max-width: 480px;
  max-height: 90vh;
  display: flex;
  flex-direction: column;
  box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
  animation: slideUp 0.3s ease;
}

@keyframes slideUp {
  from {
    opacity: 0;
    transform: translateY(20px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

.modal-header-horarios {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  padding: 20px 24px;
  border-bottom: 1px solid var(--color-border);
}

.modal-header-horarios h3 {
  margin: 0 0 4px;
  font-size: 18px;
  font-weight: 700;
  color: var(--color-text);
  display: flex;
  align-items: center;
  gap: 10px;
}

.modal-header-horarios h3 i {
  color: #ec407a;
}

.modal-header-horarios p {
  margin: 0;
  font-size: 13px;
  color: var(--color-text-secondary);
}

.modal-close {
  width: 36px;
  height: 36px;
  border-radius: 50%;
  background: rgba(0, 0, 0, 0.05);
  border: none;
  color: var(--color-text-secondary);
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all 0.2s;
  flex-shrink: 0;
}

.modal-close:hover {
  background: #ef4444;
  color: white;
}

.modal-body-horarios {
  padding: 20px 24px;
  overflow-y: auto;
  flex: 1;
  max-height: calc(90vh - 160px);
}

.modal-body-horarios::-webkit-scrollbar {
  width: 6px;
}

.modal-body-horarios::-webkit-scrollbar-track {
  background: #f1f1f1;
}

.modal-body-horarios::-webkit-scrollbar-thumb {
  background: #c1c1c1;
  border-radius: 3px;
}

.horarios-grid-modal {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 10px;
}

.horario-btn-modal {
  padding: 14px 12px;
  border: 2px solid var(--color-border);
  border-radius: 12px;
  background: transparent;
  font-size: 14px;
  font-weight: 600;
  color: var(--color-text);
  cursor: pointer;
  transition: all 0.2s;
}

.horario-btn-modal:hover {
  border-color: #ec407a;
  color: #ec407a;
  transform: translateY(-2px);
}

.horario-btn-modal.selected {
  background: linear-gradient(135deg, #ec407a, #d81b60);
  border-color: transparent;
  color: white;
  box-shadow: 0 4px 12px rgba(236, 64, 122, 0.4);
  transform: translateY(-2px);
}

.modal-footer-horarios {
  display: flex;
  flex-direction: column;
  gap: 12px;
  padding: 16px 24px;
  border-top: 1px solid var(--color-border);
  background: var(--color-background);
}

.footer-buttons {
  display: flex;
  gap: 12px;
}

/* Temporizador de reserva */
.temporizador-reserva {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  padding: 10px 16px;
  background: linear-gradient(135deg, rgba(255, 152, 0, 0.1), rgba(255, 152, 0, 0.15));
  border: 1px solid rgba(255, 152, 0, 0.3);
  border-radius: 10px;
  color: #e65100;
  font-size: 13px;
}

.theme-dark .temporizador-reserva {
  color: #ffb74d;
}

.temporizador-reserva i {
  font-size: 14px;
}

.temporizador-reserva strong {
  font-family: monospace;
  font-size: 16px;
}

/* Estado reservando */
.horario-btn-modal.reservando,
.slot-coordinado-card.reservando {
  opacity: 0.7;
  pointer-events: none;
}

.btn-cancelar {
  flex: 1;
  padding: 14px;
  border: 2px solid var(--color-border);
  border-radius: 12px;
  background: transparent;
  font-size: 14px;
  font-weight: 600;
  color: var(--color-text-secondary);
  cursor: pointer;
  transition: all 0.2s;
}

.btn-cancelar:hover {
  background: var(--color-border);
}

.btn-continuar {
  flex: 1;
  padding: 14px;
  border: none;
  border-radius: 12px;
  background: linear-gradient(135deg, #ec407a, #d81b60);
  color: white;
  font-size: 14px;
  font-weight: 600;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  transition: all 0.2s;
}

.btn-continuar:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.btn-continuar:not(:disabled):hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(236, 64, 122, 0.4);
}

/* Transiciones modal */
.modal-enter-active,
.modal-leave-active {
  transition: opacity 0.3s ease;
}

.modal-enter-active .modal-horarios,
.modal-leave-active .modal-horarios {
  transition: transform 0.3s ease, opacity 0.3s ease;
}

.modal-enter-from,
.modal-leave-to {
  opacity: 0;
}

.modal-enter-from .modal-horarios,
.modal-leave-to .modal-horarios {
  transform: translateY(20px);
  opacity: 0;
}

/* Resumen card */
.resumen-card {
  background: var(--color-card);
  border-radius: 20px;
  padding: 16px;
  display: flex;
  justify-content: space-around;
  border: 2px solid rgba(76, 175, 80, 0.3);
}

.resumen-item {
  display: flex;
  align-items: center;
  gap: 10px;
  text-align: left;
}

.resumen-item i {
  width: 36px;
  height: 36px;
  border-radius: 10px;
  background: linear-gradient(135deg, rgba(236,64,122,0.1), rgba(236,64,122,0.2));
  display: flex;
  align-items: center;
  justify-content: center;
  color: #ec407a;
  font-size: 14px;
}

.resumen-item .label {
  display: block;
  font-size: 10px;
  color: var(--color-text-secondary);
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.resumen-item .value {
  display: block;
  font-size: 13px;
  font-weight: 600;
  color: var(--color-text);
}

/* ===================================== */
/* Estilos para slots coordinados */
/* ===================================== */

.slots-coordinados-list {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.slot-coordinado-card {
  background: rgba(0,0,0,0.03);
  border-radius: 16px;
  padding: 16px;
  cursor: pointer;
  transition: all 0.2s;
  border: 2px solid transparent;
}

.theme-dark .slot-coordinado-card {
  background: rgba(255,255,255,0.05);
}

.slot-coordinado-card:hover {
  background: rgba(236,64,122,0.05);
  border-color: rgba(236,64,122,0.3);
}

.slot-coordinado-card.selected {
  background: linear-gradient(135deg, rgba(236,64,122,0.08), rgba(236,64,122,0.12));
  border-color: #ec407a;
  box-shadow: 0 4px 16px rgba(236, 64, 122, 0.2);
}

.slot-hora-principal {
  display: flex;
  align-items: center;
  gap: 8px;
  font-size: 18px;
  font-weight: 700;
  color: #ec407a;
  margin-bottom: 12px;
}

.slot-hora-principal i {
  font-size: 16px;
}

.slot-timeline {
  display: flex;
  flex-direction: column;
  gap: 10px;
  padding-left: 8px;
  border-left: 3px solid var(--color-border);
}

.slot-servicio {
  padding-left: 12px;
  position: relative;
}

.slot-servicio::before {
  content: '';
  position: absolute;
  left: -7px;
  top: 6px;
  width: 10px;
  height: 10px;
  border-radius: 50%;
  background: #ec407a;
}

.servicio-tiempo {
  font-size: 11px;
  color: var(--color-text-secondary);
  margin-bottom: 2px;
}

.servicio-nombre {
  font-size: 14px;
  font-weight: 600;
  color: var(--color-text);
}

.servicio-empleado {
  font-size: 12px;
  color: var(--color-text-secondary);
  display: flex;
  align-items: center;
  gap: 4px;
}

.servicio-empleado i {
  font-size: 10px;
}

.slot-duracion-total {
  margin-top: 12px;
  padding-top: 12px;
  border-top: 1px dashed var(--color-border);
  font-size: 12px;
  color: var(--color-text-secondary);
  display: flex;
  align-items: center;
  gap: 6px;
}

.slot-duracion-total i {
  color: #ec407a;
}

/* Resumen coordinado */
.resumen-coordinado {
  background: var(--color-card);
  border-radius: 20px;
  padding: 16px;
  border: 2px solid rgba(76, 175, 80, 0.3);
}

.resumen-header {
  display: flex;
  align-items: center;
  gap: 8px;
  margin-bottom: 16px;
  color: #4caf50;
  font-weight: 600;
  font-size: 14px;
}

.timeline-servicios {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.timeline-item {
  display: flex;
  gap: 12px;
  padding: 10px;
  background: rgba(0,0,0,0.03);
  border-radius: 12px;
}

.theme-dark .timeline-item {
  background: rgba(255,255,255,0.05);
}

.timeline-time {
  display: flex;
  flex-direction: column;
  align-items: center;
  min-width: 80px;
}

.timeline-time .hora-inicio {
  font-size: 13px;
  font-weight: 600;
  color: var(--color-text);
}

.timeline-time .separador {
  font-size: 10px;
  color: var(--color-text-secondary);
}

.timeline-time .hora-fin {
  font-size: 11px;
  color: var(--color-text-secondary);
}

.timeline-content {
  flex: 1;
}

.timeline-servicio {
  font-size: 14px;
  font-weight: 600;
  color: var(--color-text);
  margin-bottom: 2px;
}

.timeline-empleado {
  font-size: 12px;
  color: #ec407a;
  display: flex;
  align-items: center;
  gap: 4px;
}

.timeline-empleado i {
  font-size: 10px;
}

@media (max-width: 480px) {
  .horarios-grid-modal {
    grid-template-columns: repeat(2, 1fr);
  }
}

@media (max-width: 360px) {
  .horarios-grid-modal {
    grid-template-columns: repeat(2, 1fr);
    gap: 8px;
  }
  
  .horario-btn-modal {
    padding: 12px 8px;
    font-size: 13px;
  }
  
  .resumen-card {
    flex-direction: column;
    gap: 12px;
  }
}
</style>
