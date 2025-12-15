<template>
  <div class="calendario-view">
    <!-- Header Row -->
    <div class="header-row">
      <!-- Info Card -->
      <div class="header-info-card">
        <div class="header-icon">
          <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
            <line x1="16" y1="2" x2="16" y2="6"></line>
            <line x1="8" y1="2" x2="8" y2="6"></line>
            <line x1="3" y1="10" x2="21" y2="10"></line>
          </svg>
        </div>
        <div class="header-text">
          <h2>Mi Calendario</h2>
          <p>{{ empleadoNombre }}</p>
        </div>
      </div>

      <!-- Toggle Card -->
      <div class="toggle-card">
        <div class="view-toggle">
          <button 
            :class="['toggle-btn', { active: vistaActual === 'dia' }]"
            @click="vistaActual = 'dia'"
          >
            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <rect x="3" y="4" width="16" height="16" rx="2" ry="2"></rect>
              <line x1="16" y1="2" x2="16" y2="6"></line>
              <line x1="8" y1="2" x2="8" y2="6"></line>
              <line x1="3" y1="10" x2="21" y2="10"></line>
            </svg>
            Día
          </button>
          <button 
            :class="['toggle-btn', { active: vistaActual === 'semana' }]"
            @click="vistaActual = 'semana'"
          >
            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
              <line x1="16" y1="2" x2="16" y2="6"></line>
              <line x1="8" y1="2" x2="8" y2="6"></line>
              <line x1="3" y1="10" x2="21" y2="10"></line>
            </svg>
            Semana
          </button>
        </div>
      </div>
    </div>

    <!-- Navegación de fecha -->
    <div class="date-nav">
      <button class="nav-arrow" @click="navegarFecha(-1)">
        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <polyline points="15 18 9 12 15 6"></polyline>
        </svg>
      </button>
      
      <div class="date-display" @click="irAHoy">
        <span class="date-text">{{ fechaTexto }}</span>
        <span v-if="!esHoy" class="today-btn">
          <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <circle cx="12" cy="12" r="10"></circle>
            <circle cx="12" cy="12" r="3"></circle>
          </svg>
          Ir a hoy
        </span>
      </div>
      
      <button class="nav-arrow" @click="navegarFecha(1)">
        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <polyline points="9 18 15 12 9 6"></polyline>
        </svg>
      </button>
    </div>

    <!-- Loading -->
    <div v-if="loading" class="loading-container">
      <div class="loader"></div>
      <p>Cargando citas...</p>
    </div>

    <!-- VISTA DÍA -->
    <div v-else-if="vistaActual === 'dia'" class="vista-dia">
      <!-- Sin citas -->
      <div v-if="citasDelDia.length === 0" class="empty-state">
        <div class="empty-icon">
          <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
            <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
            <line x1="16" y1="2" x2="16" y2="6"></line>
            <line x1="8" y1="2" x2="8" y2="6"></line>
            <line x1="3" y1="10" x2="21" y2="10"></line>
          </svg>
        </div>
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
          <div class="time-label week-time-label">
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
        <div v-if="citaSeleccionada" class="modal-overlay" @click="citaSeleccionada = null">
          <div class="modal-content" @click.stop>
            <!-- Header -->
            <div class="modal-header">
              <div>
                <h3>Detalle de Cita</h3>
                <p class="modal-subtitle">Cita #{{ citaSeleccionada.id }}</p>
              </div>
              <button class="modal-close" @click="citaSeleccionada = null">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <line x1="18" y1="6" x2="6" y2="18"></line>
                  <line x1="6" y1="6" x2="18" y2="18"></line>
                </svg>
              </button>
            </div>

            <div class="modal-body">
              <!-- Estado Badge -->
              <div class="detail-status-badge">
                <span :class="['status-badge-large', citaSeleccionada.estado]">
                  {{ estadoTexto(citaSeleccionada.estado) }}
                </span>
              </div>

              <!-- Fecha/hora -->
              <div class="detail-section">
                <div class="section-title">
                  <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="12" r="10"></circle>
                    <polyline points="12 6 12 12 16 14"></polyline>
                  </svg>
                  <span>Fecha y Hora</span>
                </div>
                <div class="info-block">
                  <div class="info-item">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                      <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                      <line x1="16" y1="2" x2="16" y2="6"></line>
                      <line x1="8" y1="2" x2="8" y2="6"></line>
                      <line x1="3" y1="10" x2="21" y2="10"></line>
                    </svg>
                    <div class="info-content">
                      <span class="info-label">Fecha y Hora</span>
                      <span class="info-value">{{ formatHoraCompleta(citaSeleccionada.fecha_hora) }}</span>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Info -->
              <div class="detail-section">
                <div class="section-title">
                  <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                    <circle cx="12" cy="7" r="4"></circle>
                  </svg>
                  <span>Información</span>
                </div>
                <div class="info-block">
                  <div class="info-item">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                      <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                      <circle cx="12" cy="7" r="4"></circle>
                    </svg>
                    <div class="info-content">
                      <span class="info-label">Cliente</span>
                      <span class="info-value">{{ citaSeleccionada.cliente?.nombre || 'Sin cliente' }}</span>
                    </div>
                  </div>

                  <div class="info-item" v-if="citaSeleccionada.cliente?.telefono">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                      <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"></path>
                    </svg>
                    <div class="info-content">
                      <span class="info-label">Teléfono</span>
                      <span class="info-value">
                        <a :href="`tel:${citaSeleccionada.cliente.telefono}`" class="phone-link">
                          {{ citaSeleccionada.cliente.telefono }}
                        </a>
                      </span>
                    </div>
                  </div>

                  <div class="info-item">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                      <path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"></path>
                    </svg>
                    <div class="info-content">
                      <span class="info-label">Servicios</span>
                      <span class="info-value">
                        {{ citaSeleccionada.servicios?.map((s: any) => s.nombre).join(', ') || 'Sin servicios' }}
                      </span>
                    </div>
                  </div>

                  <div class="info-item">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                      <circle cx="12" cy="12" r="10"></circle>
                      <polyline points="12 6 12 12 16 14"></polyline>
                    </svg>
                    <div class="info-content">
                      <span class="info-label">Duración</span>
                      <span class="info-value">{{ citaSeleccionada.duracion_total }} minutos</span>
                    </div>
                  </div>

                  <div v-if="citaSeleccionada.notas" class="info-item">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                      <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                      <polyline points="14 2 14 8 20 8"></polyline>
                      <line x1="16" y1="13" x2="8" y2="13"></line>
                      <line x1="16" y1="17" x2="8" y2="17"></line>
                      <polyline points="10 9 9 9 8 9"></polyline>
                    </svg>
                    <div class="info-content">
                      <span class="info-label">Notas</span>
                      <span class="info-value">{{ citaSeleccionada.notas }}</span>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Acciones -->
              <div class="detail-actions">
                <template v-if="citaSeleccionada.estado === 'confirmada'">
                  <button class="btn-action-secondary" @click="cambiarEstado('en_proceso')">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                      <polygon points="5 3 19 12 5 21 5 3"></polygon>
                    </svg>
                    Iniciar
                  </button>
                </template>

                <template v-else-if="citaSeleccionada.estado === 'en_proceso'">
                  <button class="btn-action-primary" @click="cambiarEstado('completada')">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                      <polyline points="20 6 9 17 4 12"></polyline>
                    </svg>
                    Completar
                  </button>
                </template>

                <template v-if="!['completada', 'cancelada', 'no_show'].includes(citaSeleccionada.estado)">
                  <button class="btn-action-cancel" @click="cambiarEstado('no_show')">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                      <path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                      <circle cx="8.5" cy="7" r="4"></circle>
                      <line x1="18" y1="8" x2="23" y2="13"></line>
                      <line x1="23" y1="8" x2="18" y2="13"></line>
                    </svg>
                    No asistió
                  </button>
                </template>

                <a 
                  v-if="citaSeleccionada.cliente?.telefono"
                  :href="`https://wa.me/52${citaSeleccionada.cliente.telefono}`"
                  target="_blank"
                  class="btn-action-whatsapp"
                >
                  <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="currentColor">
                    <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413Z"/>
                  </svg>
                  WhatsApp
                </a>
              </div>
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
    fecha.setDate(inicio.getDate() + i)
    fecha.setHours(12, 0, 0, 0) // Usar mediodía para evitar problemas de zona horaria
    const año = fecha.getFullYear()
    const mes = String(fecha.getMonth() + 1).padStart(2, '0')
    const dia = String(fecha.getDate()).padStart(2, '0')
    dias.push({
      fecha: `${año}-${mes}-${dia}`,
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
  d.setHours(0, 0, 0, 0) // Resetear horas para evitar problemas de zona horaria
  const day = d.getDay()
  const diff = d.getDate() - day + (day === 0 ? -6 : 1)
  const inicio = new Date(d)
  inicio.setDate(diff)
  return inicio
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
/* ===== Apple-inspired Calendario View Design ===== */

.calendario-view {
  min-height: 100vh;
  background: #f5f5f7;
  padding-bottom: 100px;
  font-family: -apple-system, BlinkMacSystemFont, 'SF Pro Display', 'SF Pro Text', 'Helvetica Neue', sans-serif;
  -webkit-font-smoothing: antialiased;
  -moz-osx-font-smoothing: grayscale;
}

/* ===== HEADER ROW ===== */
.header-row {
  display: flex;
  gap: 1px;
  padding: 16px 16px 16px 20px;
  align-items: stretch;
}

/* ===== HEADER INFO CARD ===== */
.header-info-card {
  flex: 0 0 calc(64% - 6px);
  display: flex;
  align-items: center;
  gap: 14px;
  padding: 16px;
  background: linear-gradient(135deg, #1d1d1f 0%, #3a3a3c 100%);
  border-radius: 16px;
  box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.header-icon {
  width: 50px;
  height: 50px;
  border-radius: 14px;
  background: rgba(255, 255, 255, 0.1);
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

.header-icon svg {
  color: white;
}

.header-text {
  flex: 1;
  min-width: 0;
}

.header-text h2 {
  font-size: 20px;
  font-weight: 700;
  color: white;
  margin: 0;
}

.header-text p {
  font-size: 13px;
  color: rgba(255, 255, 255, 0.7);
  margin: 4px 0 0;
}

/* ===== TOGGLE CARD ===== */
.toggle-card {
  flex: 0 0 calc(36% - 6px);
  display: flex;
  align-items: center;
}

.view-toggle {
  width: 100%;
  display: flex;
  background: #ffffff;
  border-radius: 14px;
  padding: 4px;
  gap: 4px;
  box-shadow: 0 2px 8px rgba(0,0,0,0.05);
  border: 1px solid #e5e5ea;
}

.toggle-btn {
  flex: 1;
  padding: 12px 8px;
  border: none;
  border-radius: 10px;
  background: transparent;
  color: #86868b;
  font-size: 13px;
  font-weight: 600;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 6px;
  transition: all 0.2s;
}

.toggle-btn svg {
  width: 14px;
  height: 14px;
  flex-shrink: 0;
}

.toggle-btn.active {
  background: #007aff;
  color: white;
  box-shadow: 0 2px 6px rgba(0, 122, 255, 0.3);
}

/* ===== DATE NAV ===== */
.date-nav {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 16px 20px;
  background: #ffffff;
  margin: 0 16px 16px;
  border-radius: 16px;
  box-shadow: 0 2px 8px rgba(0,0,0,0.05);
  border: 1px solid #e5e5ea;
}

.nav-arrow {
  width: 40px;
  height: 40px;
  border: none;
  border-radius: 12px;
  background: #f5f5f7;
  color: #1d1d1f;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all 0.2s;
}

.nav-arrow:active {
  background: #007aff;
  color: white;
  transform: scale(0.95);
}

.nav-arrow svg {
  width: 18px;
  height: 18px;
}

.date-display {
  text-align: center;
  cursor: pointer;
}

.date-text {
  display: block;
  font-size: 17px;
  font-weight: 600;
  color: #1d1d1f;
  text-transform: capitalize;
  letter-spacing: -0.3px;
}

.today-btn {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 4px;
  font-size: 12px;
  color: #007aff;
  margin-top: 6px;
  font-weight: 500;
}

.today-btn svg {
  width: 12px;
  height: 12px;
}

/* ===== LOADING & EMPTY ===== */
.loading-container {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 60px 20px;
  text-align: center;
}

.loader {
  width: 32px;
  height: 32px;
  border: 3px solid #f5f5f7;
  border-top-color: #007aff;
  border-radius: 50%;
  animation: spin 0.8s linear infinite;
  margin-bottom: 16px;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}

.loading-container p {
  color: #86868b;
  font-size: 15px;
  margin: 0;
}

.empty-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 60px 20px;
  text-align: center;
}

.empty-icon {
  color: #d1d1d6;
  margin-bottom: 16px;
}

.empty-state h3 {
  font-size: 18px;
  color: #1d1d1f;
  margin: 0 0 8px;
  font-weight: 600;
}

.empty-state p {
  font-size: 14px;
  color: #86868b;
  margin: 0;
}

/* ===== VISTA DÍA ===== */
.vista-dia {
  padding: 16px;
}

.timeline {
  background: #ffffff;
  border-radius: 16px;
  overflow: hidden;
  box-shadow: 0 2px 8px rgba(0,0,0,0.05);
  border: 1px solid #e5e5ea;
}

.timeline-row {
  display: flex;
  min-height: 60px;
  border-bottom: 1px solid #f0f0f0;
  position: relative;
}

.timeline-row:last-child {
  border-bottom: none;
}

.time-label {
  width: 60px;
  padding: 12px 8px;
  font-size: 12px;
  font-weight: 600;
  color: #86868b;
  flex-shrink: 0;
  text-align: right;
  font-variant-numeric: tabular-nums;
}

.time-slots {
  flex: 1;
  position: relative;
  min-height: 60px;
  border-left: 1px solid #f0f0f0;
  padding-left: 12px;
}

.cita-block {
  position: absolute;
  left: 6px;
  right: 6px;
  border-radius: 12px;
  padding: 10px 12px;
  cursor: pointer;
  transition: all 0.2s;
  box-shadow: 0 2px 6px rgba(0,0,0,0.08);
}

.cita-block:active {
  transform: scale(0.98);
}

.cita-block.pendiente { 
  background: #fff8e1; 
  border-left: 4px solid #ff9500; 
}
.cita-block.confirmada { 
  background: #e8f5e9; 
  border-left: 4px solid #34c759; 
}
.cita-block.en_proceso { 
  background: #e3f2fd; 
  border-left: 4px solid #007aff;
  animation: pulseBlue 2s infinite;
}
.cita-block.completada { 
  background: #f3e5f5; 
  border-left: 4px solid #5856d6; 
  opacity: 0.7;
}
.cita-block.cancelada { 
  background: #fafafa; 
  border-left: 4px solid #86868b; 
  opacity: 0.5;
}

@keyframes pulseBlue {
  0%, 100% { box-shadow: 0 0 0 0 rgba(0, 122, 255, 0.3); }
  50% { box-shadow: 0 0 0 4px rgba(0, 122, 255, 0); }
}

.cita-linea1 {
  display: flex;
  align-items: center;
  gap: 8px;
  margin-bottom: 4px;
}

.cita-time {
  font-size: 12px;
  font-weight: 700;
  color: #1d1d1f;
  font-variant-numeric: tabular-nums;
}

.cita-cliente {
  font-size: 14px;
  font-weight: 600;
  color: #1d1d1f;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  letter-spacing: -0.2px;
}

.cita-servicio {
  font-size: 12px;
  color: #86868b;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

/* ===== VISTA SEMANA ===== */
.vista-semana {
  padding: 0 16px 16px;
  overflow-x: auto;
  width: 100%;
  box-sizing: border-box;
}

.week-header {
  display: flex;
  background: #ffffff;
  border-radius: 16px 16px 0 0;
  position: sticky;
  top: 0;
  z-index: 10;
  box-shadow: 0 2px 8px rgba(0,0,0,0.05);
  border: 1px solid #e5e5ea;
  border-bottom: none;
  width: 100%;
  box-sizing: border-box;
  min-width: 0;
}

.week-time-col {
  width: 40px;
  flex-shrink: 0;
  padding: 14px 8px;
  box-sizing: border-box;
}

.week-time-label {
  width: 40px;
  padding: 12px 8px;
  font-size: 12px;
  font-weight: 600;
  color: #86868b;
  flex-shrink: 0;
  text-align: right;
  font-variant-numeric: tabular-nums;
  box-sizing: border-box;
}

.week-day-col {
  flex: 1 1 0;
  min-width: 0;
  text-align: center;
  padding: 14px 6px;
  border-left: 1px solid #f0f0f0;
  box-sizing: border-box;
}

.week-day-col:first-of-type {
  border-left: none;
}

.week-day-col.today {
  background: rgba(0, 122, 255, 0.08);
}

.day-name {
  display: block;
  font-size: 11px;
  font-weight: 600;
  color: #86868b;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  margin-bottom: 4px;
}

.day-number {
  display: block;
  font-size: 20px;
  font-weight: 700;
  color: #1d1d1f;
  letter-spacing: -0.3px;
}

.week-day-col.today .day-number {
  color: #007aff;
}

.week-grid {
  background: #ffffff;
  border-radius: 0 0 16px 16px;
  box-shadow: 0 2px 8px rgba(0,0,0,0.05);
  border: 1px solid #e5e5ea;
  border-top: none;
  width: 100%;
  box-sizing: border-box;
  min-width: 0;
}

.week-row {
  display: flex;
  min-height: 60px;
  border-bottom: 1px solid #f0f0f0;
  width: 100%;
  box-sizing: border-box;
  min-width: 0;
}

.week-row:last-child {
  border-bottom: none;
}

.week-cell {
  flex: 1 1 0;
  min-width: 0;
  padding: 4px;
  border-left: 1px solid #f0f0f0;
  box-sizing: border-box;
}

.week-row .week-cell:first-of-type {
  border-left: none;
}

.week-cell.today {
  background: rgba(0, 122, 255, 0.03);
}

.mini-cita {
  padding: 6px 8px;
  border-radius: 8px;
  font-size: 10px;
  margin-bottom: 4px;
  cursor: pointer;
  overflow: hidden;
  border-left: 3px solid transparent;
  transition: all 0.2s;
  box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}

.mini-cita:active {
  transform: scale(0.98);
}

.mini-cita.pendiente { 
  background: #fff8e1; 
  border-left-color: #ff9500;
}
.mini-cita.confirmada { 
  background: #e8f5e9; 
  border-left-color: #34c759;
}
.mini-cita.en_proceso { 
  background: #e3f2fd; 
  border-left-color: #007aff;
  animation: miniPulse 2s infinite;
}
.mini-cita.completada { 
  background: #f3e5f5; 
  border-left-color: #5856d6;
  opacity: 0.6;
}

@keyframes miniPulse {
  0%, 100% { opacity: 1; }
  50% { opacity: 0.7; }
}

.mini-time {
  font-weight: 700;
  color: #1d1d1f;
  display: block;
  font-size: 10px;
  font-variant-numeric: tabular-nums;
}

.mini-name {
  color: #86868b;
  display: block;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  font-size: 10px;
  margin-top: 2px;
}

/* ===== MODAL ===== */
.modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.4);
  backdrop-filter: blur(8px);
  display: flex;
  align-items: flex-end;
  justify-content: center;
  z-index: 1000;
  padding: 0;
  animation: fadeIn 0.2s ease;
}

@keyframes fadeIn {
  from { opacity: 0; }
  to { opacity: 1; }
}

.modal-content {
  background: #ffffff;
  width: 100%;
  max-height: 90vh;
  border-radius: 28px 28px 0 0;
  overflow: hidden;
  animation: slideUp 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  box-shadow: 0 -4px 24px rgba(0, 0, 0, 0.2);
}

@keyframes slideUp {
  from {
    transform: translateY(100%);
    opacity: 0;
  }
  to {
    transform: translateY(0);
    opacity: 1;
  }
}

.modal-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 20px 24px;
  border-bottom: 1px solid #f0f0f0;
  background: linear-gradient(135deg, #f8f9fa 0%, #ffffff 100%);
}

.modal-header h3 {
  margin: 0;
  font-size: 20px;
  font-weight: 600;
  color: #1d1d1f;
  letter-spacing: -0.3px;
}

.modal-subtitle {
  font-size: 13px;
  color: #86868b;
  margin: 4px 0 0;
  font-weight: 400;
}

.modal-close {
  width: 32px;
  height: 32px;
  border: none;
  border-radius: 8px;
  background: #f5f5f7;
  color: #86868b;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all 0.2s;
}

.modal-close:active {
  background: #ebebed;
  transform: scale(0.95);
}

.modal-close svg {
  width: 20px;
  height: 20px;
}

.modal-body {
  padding: 24px;
  overflow-y: auto;
  max-height: calc(90vh - 80px);
  background: #fafafa;
}

.modal-body::-webkit-scrollbar {
  width: 6px;
}

.modal-body::-webkit-scrollbar-track {
  background: #f1f1f1;
}

.modal-body::-webkit-scrollbar-thumb {
  background: #c1c1c1;
  border-radius: 3px;
}

.modal-body::-webkit-scrollbar-thumb:hover {
  background: #a8a8a8;
}

.detail-status-badge {
  display: flex;
  justify-content: center;
  margin-bottom: 20px;
}

.status-badge-large {
  padding: 10px 18px;
  border-radius: 20px;
  font-size: 13px;
  font-weight: 600;
  text-transform: capitalize;
  letter-spacing: 0.3px;
}

.status-badge-large.pendiente {
  background: rgba(255, 149, 0, 0.12);
  color: #ff9500;
}

.status-badge-large.confirmada {
  background: rgba(52, 199, 89, 0.12);
  color: #34c759;
}

.status-badge-large.en_proceso {
  background: rgba(0, 122, 255, 0.12);
  color: #007aff;
}

.status-badge-large.completada {
  background: rgba(88, 86, 214, 0.12);
  color: #5856d6;
}

.status-badge-large.cancelada {
  background: rgba(255, 59, 48, 0.12);
  color: #ff3b30;
}

.status-badge-large.no_show {
  background: rgba(134, 134, 139, 0.12);
  color: #86868b;
}

.detail-section {
  background: #ffffff;
  border-radius: 16px;
  padding: 18px;
  border: 1px solid #e5e5ea;
  margin-bottom: 16px;
  transition: all 0.2s;
}

.detail-section:hover {
  background: #f8f9fa;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
}

.section-title {
  display: flex;
  align-items: center;
  gap: 10px;
  margin-bottom: 14px;
  font-size: 12px;
  font-weight: 600;
  color: #007aff;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.section-title svg {
  width: 16px;
  height: 16px;
  color: #007aff;
  flex-shrink: 0;
}

.info-block {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.info-item {
  display: flex;
  align-items: center;
  gap: 12px;
}

.info-item svg {
  width: 16px;
  height: 16px;
  color: #007aff;
  flex-shrink: 0;
}

.info-content {
  display: flex;
  flex-direction: column;
  flex: 1;
  min-width: 0;
  gap: 4px;
}

.info-label {
  font-size: 10px;
  color: #999;
  text-transform: uppercase;
  letter-spacing: 1px;
  font-weight: 600;
}

.info-value {
  font-size: 16px;
  font-weight: 700;
  color: #333;
  line-height: 1.3;
}

.phone-link {
  color: #007aff;
  text-decoration: none;
}

.phone-link:active {
  opacity: 0.7;
}

.detail-actions {
  display: flex;
  flex-direction: column;
  gap: 12px;
  margin-top: 12px;
}

.btn-action-primary,
.btn-action-secondary,
.btn-action-whatsapp,
.btn-action-cancel {
  width: 100%;
  padding: 16px;
  border: none;
  border-radius: 14px;
  font-size: 15px;
  font-weight: 600;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 10px;
  transition: all 0.2s;
  letter-spacing: 0.2px;
  text-decoration: none;
}

.btn-action-primary {
  background: #34c759;
  color: white;
}

.btn-action-primary:active {
  transform: scale(0.98);
  background: #30d158;
}

.btn-action-secondary {
  background: #007aff;
  color: white;
}

.btn-action-secondary:active {
  transform: scale(0.98);
  background: #0051d5;
}

.btn-action-whatsapp {
  background: #25d366;
  color: white;
}

.btn-action-whatsapp:active {
  transform: scale(0.98);
  background: #20ba5a;
}

.btn-action-cancel {
  background: #ff3b30;
  color: white;
}

.btn-action-cancel:active {
  transform: scale(0.98);
  background: #d70015;
}

.btn-action-primary svg,
.btn-action-secondary svg,
.btn-action-whatsapp svg,
.btn-action-cancel svg {
  flex-shrink: 0;
}

/* Transitions */
.modal-enter-active, .modal-leave-active {
  transition: opacity 0.3s ease;
}
.modal-enter-active .modal-content, .modal-leave-active .modal-content {
  transition: transform 0.3s ease;
}
.modal-enter-from, .modal-leave-to {
  opacity: 0;
}
.modal-enter-from .modal-content, .modal-leave-to .modal-content {
  transform: scale(0.9) translateY(20px);
}
</style>
