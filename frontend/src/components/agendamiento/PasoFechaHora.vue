<script setup lang="ts">
import { ref, onMounted } from 'vue'
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

async function abrirModalHorarios(fecha: string) {
  await seleccionarFecha(fecha)
  mostrarModalHorarios.value = true
}

function cerrarModalHorarios() {
  mostrarModalHorarios.value = false
}

function seleccionarHoraYContinuar(hora: string) {
  store.seleccionarHora(hora)
  cerrarModalHorarios()
  store.siguientePaso()
}

function formatHora(hora: string): string {
  if (!hora) return ''
  const [hours, minutes] = hora.split(':')
  const hour = parseInt(hours)
  const ampm = hour >= 12 ? 'PM' : 'AM'
  const hour12 = hour % 12 || 12
  return `${hour12}:${minutes} ${ampm}`
}

onMounted(() => {
  cargarDisponibilidadMes()
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

    <!-- Empleado badge -->
    <div v-if="store.empleadoSeleccionado" class="empleado-badge">
      <i class="fa fa-user-check"></i>
      <span>Con <strong>{{ store.empleadoSeleccionado.nombre }}</strong></span>
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

    <!-- Resumen -->
    <div v-if="store.fechaSeleccionada && store.horaSeleccionada" class="resumen-card">
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
                <p>Selecciona un horario disponible</p>
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

              <!-- Mensaje -->
              <div v-else-if="store.slotsDisponibles?.mensaje" class="horarios-mensaje">
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
                  :class="{ 'selected': store.horaSeleccionada === slot.hora }"
                  @click="store.seleccionarHora(slot.hora)"
                >
                  {{ formatHora(slot.hora) }}
                </button>
              </div>
            </div>

            <div class="modal-footer-horarios">
              <button class="btn-cancelar" @click="cerrarModalHorarios">
                Cancelar
              </button>
              <button 
                class="btn-continuar" 
                @click="seleccionarHoraYContinuar(store.horaSeleccionada)"
                :disabled="!store.horaSeleccionada"
              >
                Continuar
                <i class="fa fa-chevron-right"></i>
              </button>
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
  max-width: 420px;
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
  gap: 12px;
  padding: 16px 24px;
  border-top: 1px solid var(--color-border);
  background: var(--color-background);
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
