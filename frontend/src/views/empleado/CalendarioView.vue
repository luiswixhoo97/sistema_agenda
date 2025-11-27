<template>
  <div class="calendario-view">
    <!-- Header Row -->
    <div class="header-row">
      <!-- Info Card (7 cols) -->
      <div class="header-info-card">
        <div class="header-icon">
          <i class="fa fa-calendar-alt"></i>
        </div>
        <div class="header-text">
          <h2>Mi Calendario</h2>
          <p>{{ empleadoNombre }}</p>
        </div>
      </div>

      <!-- Toggle Card (5 cols) -->
      <div class="toggle-card">
        <div class="view-toggle">
          <button 
            :class="['toggle-btn', { active: vistaActual === 'dia' }]"
            @click="vistaActual = 'dia'"
          >
            <i class="fa fa-calendar-day"></i>
            Día
          </button>
          <button 
            :class="['toggle-btn', { active: vistaActual === 'semana' }]"
            @click="vistaActual = 'semana'"
          >
            <i class="fa fa-calendar-week"></i>
            Semana
          </button>
        </div>
      </div>
    </div>

    <!-- Navegación de fecha -->
    <div class="date-nav">
      <button class="nav-arrow" @click="navegarFecha(-1)">
        <i class="fa fa-chevron-left"></i>
      </button>
      
      <div class="date-display" @click="irAHoy">
        <span class="date-text">{{ fechaTexto }}</span>
        <span v-if="!esHoy" class="today-btn">
          <i class="fa fa-dot-circle"></i> Ir a hoy
        </span>
      </div>
      
      <button class="nav-arrow" @click="navegarFecha(1)">
        <i class="fa fa-chevron-right"></i>
      </button>
    </div>

    <!-- Loading -->
    <div v-if="loading" class="loading-state">
      <div class="spinner"></div>
      <p>Cargando citas...</p>
    </div>

    <!-- VISTA DÍA -->
    <div v-else-if="vistaActual === 'dia'" class="vista-dia">
      <!-- Sin citas -->
      <div v-if="citasDelDia.length === 0" class="empty-state">
        <div class="empty-icon">🗓️</div>
        <h3>Sin citas</h3>
        <p>No tienes citas programadas para este día</p>
      </div>

      <!-- Timeline -->
      <div v-else class="timeline">
        <div 
          v-for="hora in horasDelDia" 
          :key="hora"
          class="timeline-row"
        >
          <div class="time-label">
            <span>{{ hora.toString().padStart(2, '0') }}:00</span>
          </div>
          <div class="time-slots">
            <div 
              v-for="cita in citasEnHora(hora)" 
              :key="cita.id"
              :class="['cita-block', cita.estado]"
              :style="calcularEstiloCita(cita)"
              @click="seleccionarCita(cita)"
            >
              <div class="cita-linea1">
                <span class="cita-time">{{ formatHora(cita.fecha_hora) }}</span>
                <span class="cita-cliente">{{ cita.cliente?.nombre || 'Sin nombre' }}</span>
              </div>
              <div class="cita-servicio">
                {{ cita.servicios?.map((s: any) => s.nombre).join(', ') || 'Sin servicio' }}
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- VISTA SEMANA -->
    <div v-else class="vista-semana">
      <!-- Header días -->
      <div class="week-header">
        <div class="week-time-col"></div>
        <div 
          v-for="dia in diasSemana" 
          :key="dia.fecha"
          :class="['week-day-col', { today: dia.fecha ? esHoyFecha(dia.fecha) : false }]"
        >
          <span class="day-name">{{ dia.nombre }}</span>
          <span class="day-number">{{ dia.numero }}</span>
        </div>
      </div>

      <!-- Grid -->
      <div class="week-grid">
        <div v-for="hora in horasDelDia" :key="hora" class="week-row">
          <div class="time-label">
            <span>{{ hora.toString().padStart(2, '0') }}</span>
          </div>
          <div 
            v-for="dia in diasSemana" 
            :key="`${hora}-${dia.fecha}`"
            :class="['week-cell', { today: dia.fecha ? esHoyFecha(dia.fecha) : false }]"
          >
            <div 
              v-for="cita in citasEnDiaHora(dia.fecha || '', hora)" 
              :key="cita.id"
              :class="['mini-cita', cita.estado]"
              @click="seleccionarCita(cita)"
            >
              <span class="mini-time">{{ formatHora(cita.fecha_hora) }}</span>
              <span class="mini-name">{{ cita.cliente?.nombre?.split(' ')[0] || 'Sin nombre' }}</span>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Modal de cita -->
    <Teleport to="body">
      <Transition name="modal">
        <div v-if="citaSeleccionada" class="modal-backdrop" @click="citaSeleccionada = null">
          <div class="modal-card" @click.stop>
            <!-- Header -->
            <div class="modal-header">
              <div class="modal-badge" :class="citaSeleccionada.estado">
                {{ estadoTexto(citaSeleccionada.estado) }}
              </div>
              <button class="modal-close" @click="citaSeleccionada = null">
                <i class="fa fa-times"></i>
              </button>
            </div>

            <!-- Fecha/hora -->
            <div class="modal-datetime">
              <i class="fa fa-clock"></i>
              {{ formatHoraCompleta(citaSeleccionada.fecha_hora) }}
            </div>

            <!-- Info -->
            <div class="modal-info">
              <div class="info-item">
                <div class="info-icon"><i class="fa fa-user"></i></div>
                <div class="info-content">
                  <span class="info-label">Cliente</span>
                  <span class="info-value">{{ citaSeleccionada.cliente?.nombre }}</span>
                </div>
              </div>

              <div class="info-item clickable">
                <div class="info-icon"><i class="fa fa-phone"></i></div>
                <div class="info-content">
                  <span class="info-label">Teléfono</span>
                  <a :href="`tel:${citaSeleccionada.cliente?.telefono}`" class="info-value link">
                    {{ citaSeleccionada.cliente?.telefono }}
                  </a>
                </div>
              </div>

              <div class="info-item">
                <div class="info-icon"><i class="fa fa-spa"></i></div>
                <div class="info-content">
                  <span class="info-label">Servicios</span>
                  <span class="info-value">
                    {{ citaSeleccionada.servicios?.map((s: any) => s.nombre).join(', ') || 'Sin servicios' }}
                  </span>
                </div>
              </div>

              <div class="info-item">
                <div class="info-icon"><i class="fa fa-hourglass-half"></i></div>
                <div class="info-content">
                  <span class="info-label">Duración</span>
                  <span class="info-value">{{ citaSeleccionada.duracion_total }} minutos</span>
                </div>
              </div>

              <div v-if="citaSeleccionada.notas" class="info-item">
                <div class="info-icon"><i class="fa fa-sticky-note"></i></div>
                <div class="info-content">
                  <span class="info-label">Notas</span>
                  <span class="info-value">{{ citaSeleccionada.notas }}</span>
                </div>
              </div>
            </div>

            <!-- Acciones -->
            <div class="modal-actions">
              <template v-if="citaSeleccionada.estado === 'confirmada'">
                <button class="action-btn success" @click="cambiarEstado('en_proceso')">
                  <i class="fa fa-play"></i> Iniciar
                </button>
              </template>

              <template v-else-if="citaSeleccionada.estado === 'en_proceso'">
                <button class="action-btn primary" @click="cambiarEstado('completada')">
                  <i class="fa fa-check"></i> Completar
                </button>
              </template>

              <template v-if="!['completada', 'cancelada', 'no_show'].includes(citaSeleccionada.estado)">
                <button class="action-btn danger" @click="cambiarEstado('no_show')">
                  <i class="fa fa-user-slash"></i> No asistió
                </button>
              </template>

              <button class="action-btn secondary" @click="abrirFotos">
                <i class="fa fa-camera"></i> Fotos
              </button>
            </div>
          </div>
        </div>
      </Transition>
    </Teleport>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, watch } from 'vue'
import { useAuthStore } from '@/stores/auth'
import api from '@/services/api'
import type { Cita } from '@/types'

const authStore = useAuthStore()

const fechaSeleccionada = ref(new Date())
const vistaActual = ref<'dia' | 'semana'>('dia')
const loading = ref(true)
const citas = ref<Cita[]>([])
const citaSeleccionada = ref<Cita | null>(null)

const empleadoNombre = computed(() => authStore.user?.nombre || 'Empleado')

const esHoy = computed(() => {
  const hoy = new Date()
  return fechaSeleccionada.value.toDateString() === hoy.toDateString()
})

const fechaTexto = computed(() => {
  if (vistaActual.value === 'dia') {
    return fechaSeleccionada.value.toLocaleDateString('es-MX', {
      weekday: 'long',
      day: 'numeric',
      month: 'long',
    })
  }
  const inicio = obtenerInicioSemana(fechaSeleccionada.value)
  const fin = new Date(inicio)
  fin.setDate(fin.getDate() + 6)
  return `${inicio.getDate()} - ${fin.getDate()} de ${fin.toLocaleDateString('es-MX', { month: 'long' })}`
})

const horasDelDia = computed(() => Array.from({ length: 12 }, (_, i) => i + 8))

const diasSemana = computed(() => {
  const inicio = obtenerInicioSemana(fechaSeleccionada.value)
  const dias = []
  for (let i = 0; i < 7; i++) {
    const fecha = new Date(inicio)
    fecha.setDate(fecha.getDate() + i)
    dias.push({
      fecha: fecha.toISOString().split('T')[0],
      nombre: fecha.toLocaleDateString('es-MX', { weekday: 'short' }),
      numero: fecha.getDate(),
    })
  }
  return dias
})

const citasDelDia = computed(() => {
  const fechaStr = fechaSeleccionada.value.toISOString().split('T')[0]
  return citas.value.filter(c => {
    const citaFecha = c.fecha_hora.split(/[T\s]/)[0]
    return citaFecha === fechaStr
  })
})

async function cargarCitas() {
  loading.value = true
  try {
    let endpoint = ''
    let params: any = {}
    
    if (vistaActual.value === 'dia') {
      endpoint = '/empleado/calendario/dia'
      params.fecha = fechaSeleccionada.value.toISOString().split('T')[0]
    } else {
      endpoint = '/empleado/calendario/semana'
      params.fecha = fechaSeleccionada.value.toISOString().split('T')[0]
    }

    const response = await api.get(endpoint, { params })
    
    if (vistaActual.value === 'dia') {
      citas.value = response.data.data?.citas || []
    } else {
      const citasPorDia = response.data.data?.citas_por_dia || {}
      const todasLasCitas: Cita[] = []
      Object.keys(citasPorDia).forEach(fecha => {
        const citasDelDia = citasPorDia[fecha]
        if (Array.isArray(citasDelDia)) {
          todasLasCitas.push(...citasDelDia)
        }
      })
      citas.value = todasLasCitas
    }
  } catch (error) {
    console.error('Error cargando citas:', error)
    citas.value = []
  } finally {
    loading.value = false
  }
}

function navegarFecha(direccion: number) {
  const nueva = new Date(fechaSeleccionada.value)
  if (vistaActual.value === 'dia') {
    nueva.setDate(nueva.getDate() + direccion)
  } else {
    nueva.setDate(nueva.getDate() + (direccion * 7))
  }
  fechaSeleccionada.value = nueva
}

function irAHoy() {
  fechaSeleccionada.value = new Date()
}

function obtenerInicioSemana(fecha: Date): Date {
  const d = new Date(fecha)
  const day = d.getDay()
  const diff = d.getDate() - day + (day === 0 ? -6 : 1)
  return new Date(d.setDate(diff))
}

function esHoyFecha(fechaStr: string): boolean {
  return fechaStr === new Date().toISOString().split('T')[0]
}

function citasEnHora(hora: number): Cita[] {
  return citasDelDia.value.filter(c => {
    const citaHora = parseInt(c.fecha_hora.split(/[T\s]/)[1]?.split(':')[0] || '0')
    return citaHora === hora
  })
}

function citasEnDiaHora(fechaStr: string, hora: number): Cita[] {
  return citas.value.filter(c => {
    const citaFecha = c.fecha_hora.split(/[T\s]/)[0]
    const citaHora = parseInt(c.fecha_hora.split(/[T\s]/)[1]?.split(':')[0] || '0')
    return citaFecha === fechaStr && citaHora === hora
  })
}

function calcularEstiloCita(cita: Cita): Record<string, string> {
  const minutos = new Date(cita.fecha_hora.replace(' ', 'T')).getMinutes()
  return {
    top: `${(minutos / 60) * 100}%`,
  }
}

function seleccionarCita(cita: Cita) {
  citaSeleccionada.value = cita
}

async function cambiarEstado(nuevoEstado: string) {
  if (!citaSeleccionada.value) return
  try {
    const index = citas.value.findIndex(c => c.id === citaSeleccionada.value?.id)
    if (index !== -1 && citas.value[index]) {
      citas.value[index].estado = nuevoEstado as any
    }
    citaSeleccionada.value.estado = nuevoEstado as any
  } catch (error) {
    console.error('Error cambiando estado:', error)
  }
}

function abrirFotos() {
  console.log('Abrir fotos de cita:', citaSeleccionada.value?.id)
}

function formatHora(fecha: string): string {
  const fechaISO = fecha.replace(' ', 'T')
  return new Date(fechaISO).toLocaleTimeString('es-MX', { hour: '2-digit', minute: '2-digit' })
}

function formatHoraCompleta(fecha: string): string {
  const fechaISO = fecha.replace(' ', 'T')
  return new Date(fechaISO).toLocaleString('es-MX', {
    weekday: 'long',
    day: 'numeric',
    month: 'long',
    hour: '2-digit',
    minute: '2-digit',
  })
}

function estadoTexto(estado: string): string {
  const estados: Record<string, string> = {
    pendiente: 'Pendiente',
    confirmada: 'Confirmada',
    en_proceso: 'En proceso',
    completada: 'Completada',
    cancelada: 'Cancelada',
    no_show: 'No asistió',
  }
  return estados[estado] || estado
}

watch([fechaSeleccionada, vistaActual], () => {
  cargarCitas()
})

onMounted(() => {
  cargarCitas()
})
</script>

<style scoped>
.calendario-view {
  min-height: 100%;
  background: var(--color-background);
  padding-bottom: 100px;
}

/* ===== HEADER ROW ===== */
.header-row {
  display: flex;
  gap: 10px;
  padding: 12px 16px;
}

/* ===== HEADER INFO CARD (7 cols) ===== */
.header-info-card {
  flex: 0 0 calc(58.333% - 5px); /* 7/12 = 58.333% */
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 12px;
  background: var(--color-card);
  border-radius: 12px;
  box-shadow: 0 2px 8px rgba(0,0,0,0.05);
}

.header-icon {
  width: 40px;
  height: 40px;
  border-radius: 10px;
  background: linear-gradient(135deg, rgba(236,64,122,0.1), rgba(236,64,122,0.2));
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

.header-icon i {
  font-size: 18px;
  color: #ec407a;
}

.header-text {
  flex: 1;
  min-width: 0;
}

.header-text h2 {
  font-size: 16px;
  font-weight: 700;
  color: var(--color-text);
  margin: 0;
  line-height: 1.2;
}

.header-text p {
  font-size: 11px;
  color: var(--color-text-secondary);
  margin: 2px 0 0;
}

/* ===== TOGGLE CARD (5 cols) ===== */
.toggle-card {
  flex: 0 0 calc(41.667% - 5px); /* 5/12 = 41.667% */
  display: flex;
  align-items: center;
}

.view-toggle {
  width: 100%;
  display: flex;
  background: var(--color-card);
  border-radius: 10px;
  padding: 3px;
  gap: 3px;
  box-shadow: 0 2px 8px rgba(0,0,0,0.05);
}

.toggle-btn {
  flex: 1;
  padding: 10px 6px;
  border: none;
  border-radius: 8px;
  background: transparent;
  color: var(--color-text-secondary);
  font-size: 11px;
  font-weight: 600;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 4px;
  transition: all 0.2s;
}

.toggle-btn i {
  font-size: 12px;
}

.toggle-btn.active {
  background: linear-gradient(135deg, #ec407a, #c2185b);
  color: white;
  box-shadow: 0 2px 6px rgba(236, 64, 122, 0.3);
}

/* ===== DATE NAV ===== */
.date-nav {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 12px 16px;
  background: var(--color-card);
  margin: 0 16px;
  border-radius: 16px;
}

.nav-arrow {
  width: 40px;
  height: 40px;
  border: none;
  border-radius: 12px;
  background: rgba(0,0,0,0.05);
  color: var(--color-text);
  font-size: 14px;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all 0.2s;
}

.theme-dark .nav-arrow {
  background: rgba(255,255,255,0.1);
}

.nav-arrow:hover {
  background: #ec407a;
  color: white;
}

.date-display {
  text-align: center;
  cursor: pointer;
}

.date-text {
  display: block;
  font-size: 16px;
  font-weight: 600;
  color: var(--color-text);
  text-transform: capitalize;
}

.today-btn {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 4px;
  font-size: 12px;
  color: #ec407a;
  margin-top: 4px;
}

/* ===== LOADING & EMPTY ===== */
.loading-state {
  text-align: center;
  padding: 60px 20px;
}

.spinner {
  width: 44px;
  height: 44px;
  border: 3px solid rgba(236,64,122,0.2);
  border-top-color: #ec407a;
  border-radius: 50%;
  animation: spin 0.8s linear infinite;
  margin: 0 auto 16px;
}

@keyframes spin { to { transform: rotate(360deg); } }

.loading-state p {
  color: var(--color-text-secondary);
  font-size: 14px;
}

.empty-state {
  text-align: center;
  padding: 60px 20px;
}

.empty-icon {
  font-size: 60px;
  margin-bottom: 16px;
}

.empty-state h3 {
  font-size: 18px;
  color: var(--color-text);
  margin: 0 0 8px;
}

.empty-state p {
  font-size: 14px;
  color: var(--color-text-secondary);
  margin: 0;
}

/* ===== VISTA DÍA ===== */
.vista-dia {
  padding: 16px;
}

.timeline {
  background: var(--color-card);
  border-radius: 16px;
  overflow: hidden;
}

.timeline-row {
  display: flex;
  min-height: 60px;
  border-bottom: 1px solid var(--color-border);
  position: relative;
}

.timeline-row:last-child {
  border-bottom: none;
}

.time-label {
  width: 50px;
  padding: 8px 6px;
  font-size: 12px;
  font-weight: 600;
  color: var(--color-text-secondary);
  flex-shrink: 0;
  text-align: right;
}

.time-slots {
  flex: 1;
  position: relative;
  min-height: 60px;
  border-left: 1px solid var(--color-border);
}

.cita-block {
  position: absolute;
  left: 6px;
  right: 6px;
  border-radius: 8px;
  padding: 8px 10px;
  cursor: pointer;
  transition: transform 0.2s, box-shadow 0.2s;
}

.cita-block:hover {
  transform: translateX(3px);
  box-shadow: 0 3px 12px rgba(0,0,0,0.15);
}

.cita-block.pendiente { 
  background: #fff8e1; 
  border-left: 4px solid #ff9800; 
}
.cita-block.confirmada { 
  background: #e8f5e9; 
  border-left: 4px solid #4caf50; 
}
.cita-block.en_proceso { 
  background: #e3f2fd; 
  border-left: 4px solid #2196f3;
  animation: pulseBlue 2s infinite;
}
.cita-block.completada { 
  background: #f3e5f5; 
  border-left: 4px solid #9c27b0; 
  opacity: 0.7;
}
.cita-block.cancelada { 
  background: #fafafa; 
  border-left: 4px solid #9e9e9e; 
  opacity: 0.5;
}

@keyframes pulseBlue {
  0%, 100% { box-shadow: 0 0 0 0 rgba(33, 150, 243, 0.3); }
  50% { box-shadow: 0 0 0 4px rgba(33, 150, 243, 0); }
}

.cita-linea1 {
  display: flex;
  align-items: center;
  gap: 8px;
  margin-bottom: 2px;
}

.cita-time {
  font-size: 12px;
  font-weight: 800;
  color: #333;
}

.cita-cliente {
  font-size: 13px;
  font-weight: 600;
  color: #222;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.cita-servicio {
  font-size: 11px;
  color: #666;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

/* ===== VISTA SEMANA ===== */
.vista-semana {
  padding: 16px;
  overflow-x: auto;
}

.week-header {
  display: flex;
  background: var(--color-card);
  border-radius: 16px 16px 0 0;
  position: sticky;
  top: 0;
  z-index: 10;
}

.week-time-col {
  width: 40px;
  flex-shrink: 0;
}

.week-day-col {
  flex: 1;
  min-width: 45px;
  text-align: center;
  padding: 12px 4px;
  border-left: 1px solid var(--color-border);
}

.week-day-col.today {
  background: rgba(236,64,122,0.1);
}

.day-name {
  display: block;
  font-size: 10px;
  font-weight: 600;
  color: var(--color-text-secondary);
  text-transform: uppercase;
}

.day-number {
  display: block;
  font-size: 18px;
  font-weight: 700;
  color: var(--color-text);
}

.week-day-col.today .day-number {
  color: #ec407a;
}

.week-grid {
  background: var(--color-card);
  border-radius: 0 0 16px 16px;
}

.week-row {
  display: flex;
  min-height: 50px;
  border-bottom: 1px solid var(--color-border);
}

.week-row:last-child {
  border-bottom: none;
}

.week-cell {
  flex: 1;
  min-width: 45px;
  padding: 2px;
  border-left: 1px solid var(--color-border);
}

.week-cell.today {
  background: rgba(236,64,122,0.05);
}

.mini-cita {
  padding: 4px 6px;
  border-radius: 6px;
  font-size: 10px;
  margin-bottom: 3px;
  cursor: pointer;
  overflow: hidden;
  border-left: 3px solid transparent;
  transition: all 0.2s;
}

.mini-cita:hover {
  transform: scale(1.05);
  box-shadow: 0 2px 8px rgba(0,0,0,0.15);
}

.mini-cita.pendiente { 
  background: #fff8e1; 
  border-left-color: #ff9800;
}
.mini-cita.confirmada { 
  background: #e8f5e9; 
  border-left-color: #4caf50;
}
.mini-cita.en_proceso { 
  background: #e3f2fd; 
  border-left-color: #2196f3;
  animation: miniPulse 2s infinite;
}
.mini-cita.completada { 
  background: #f3e5f5; 
  border-left-color: #9c27b0;
  opacity: 0.6;
}

@keyframes miniPulse {
  0%, 100% { opacity: 1; }
  50% { opacity: 0.7; }
}

.mini-time {
  font-weight: 800;
  color: #222;
  display: block;
}

.mini-name {
  color: #555;
  display: block;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

/* ===== MODAL ===== */
.modal-backdrop {
  position: fixed;
  inset: 0;
  background: rgba(0,0,0,0.6);
  backdrop-filter: blur(4px);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1000;
  padding: 20px;
}

.modal-card {
  background: var(--color-card);
  border-radius: 24px;
  width: 100%;
  max-width: 400px;
  max-height: 90vh;
  overflow-y: auto;
}

.modal-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 20px 20px 0;
}

.modal-badge {
  padding: 6px 14px;
  border-radius: 20px;
  font-size: 12px;
  font-weight: 700;
  text-transform: uppercase;
}

.modal-badge.pendiente { background: #fff3e0; color: #e65100; }
.modal-badge.confirmada { background: #e8f5e9; color: #2e7d32; }
.modal-badge.en_proceso { background: #e3f2fd; color: #1565c0; }
.modal-badge.completada { background: #f3e5f5; color: #7b1fa2; }
.modal-badge.cancelada { background: #ffebee; color: #c62828; }
.modal-badge.no_show { background: #fafafa; color: #616161; }

.modal-close {
  width: 36px;
  height: 36px;
  border-radius: 50%;
  background: rgba(0,0,0,0.05);
  border: none;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  color: var(--color-text-secondary);
  transition: all 0.2s;
}

.modal-close:hover {
  background: #ef4444;
  color: white;
}

.modal-datetime {
  padding: 16px 20px;
  font-size: 14px;
  color: var(--color-text-secondary);
  text-transform: capitalize;
  display: flex;
  align-items: center;
  gap: 8px;
}

.modal-datetime i {
  color: #ec407a;
}

.modal-info {
  padding: 0 20px 20px;
}

.info-item {
  display: flex;
  align-items: flex-start;
  gap: 14px;
  padding: 14px 0;
  border-bottom: 1px solid var(--color-border);
}

.info-item:last-child {
  border-bottom: none;
}

.info-icon {
  width: 40px;
  height: 40px;
  border-radius: 12px;
  background: linear-gradient(135deg, rgba(236,64,122,0.1), rgba(236,64,122,0.2));
  display: flex;
  align-items: center;
  justify-content: center;
  color: #ec407a;
  font-size: 14px;
}

.info-content {
  flex: 1;
}

.info-label {
  display: block;
  font-size: 11px;
  color: var(--color-text-secondary);
  text-transform: uppercase;
  letter-spacing: 0.5px;
  margin-bottom: 2px;
}

.info-value {
  font-size: 14px;
  font-weight: 500;
  color: var(--color-text);
}

.info-value.link {
  color: #ec407a;
  text-decoration: none;
}

.modal-actions {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 10px;
  padding: 0 20px 20px;
}

.action-btn {
  padding: 14px;
  border: none;
  border-radius: 12px;
  font-size: 13px;
  font-weight: 600;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  transition: all 0.2s;
}

.action-btn:hover {
  transform: translateY(-2px);
}

.action-btn.success { background: #e8f5e9; color: #2e7d32; }
.action-btn.primary { background: linear-gradient(135deg, #ec407a, #c2185b); color: white; }
.action-btn.danger { background: #ffebee; color: #c62828; }
.action-btn.secondary { background: rgba(0,0,0,0.05); color: var(--color-text); }

/* Transitions */
.modal-enter-active, .modal-leave-active {
  transition: opacity 0.3s ease;
}
.modal-enter-active .modal-card, .modal-leave-active .modal-card {
  transition: transform 0.3s ease;
}
.modal-enter-from, .modal-leave-to {
  opacity: 0;
}
.modal-enter-from .modal-card, .modal-leave-to .modal-card {
  transform: scale(0.9) translateY(20px);
}
</style>
