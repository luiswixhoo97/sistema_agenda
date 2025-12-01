<template>
  <div class="citas-view">
    <!-- Header Row -->
    <div class="header-row">
      <!-- Info Card -->
      <div class="header-info-card">
        <div class="header-icon">
          <i class="fa fa-calendar-check"></i>
        </div>
        <div class="header-text">
          <h2>Mis Citas</h2>
          <p>{{ empleadoNombre }}</p>
        </div>
      </div>

      <!-- Stats -->
      <div class="stats-card">
        <div class="stat-item">
          <span class="stat-value">{{ citasPendientes }}</span>
          <span class="stat-label">Pendientes</span>
        </div>
      </div>
    </div>

    <!-- Filtros -->
    <div class="filtros-card">
      <div class="filtro-grupo">
        <label><i class="fa fa-filter"></i> Estado</label>
        <select v-model="filtroEstado">
          <option value="">Todos</option>
          <option value="pendiente">Pendientes</option>
          <option value="confirmada">Confirmadas</option>
          <option value="en_proceso">En proceso</option>
          <option value="completada">Completadas</option>
          <option value="cancelada">Canceladas</option>
        </select>
      </div>
      <div class="filtro-grupo">
        <label><i class="fa fa-calendar"></i> Fecha</label>
        <input type="date" v-model="filtroFecha" />
      </div>
    </div>

    <!-- Loading -->
    <div v-if="loading" class="loading-state">
      <div class="spinner"></div>
      <p>Cargando citas...</p>
    </div>

    <!-- Empty -->
    <div v-else-if="citasFiltradas.length === 0" class="empty-state">
      <div class="empty-icon">📭</div>
      <h3>Sin citas</h3>
      <p>No tienes citas con estos filtros</p>
      <button v-if="filtroEstado || filtroFecha" class="clear-btn" @click="limpiarFiltros">
        <i class="fa fa-times"></i> Limpiar filtros
      </button>
    </div>

    <!-- Lista de citas -->
    <div v-else class="citas-lista">
      <!-- Próxima cita destacada -->
      <div v-if="proximaCita" class="proxima-cita-card">
        <div class="proxima-header">
          <span class="proxima-label">
            <i class="fa fa-star"></i> Próxima cita
          </span>
          <span class="proxima-tiempo">{{ tiempoRestante(proximaCita) }}</span>
        </div>
        <div class="proxima-body" @click="verDetalle(proximaCita)">
          <div class="proxima-fecha">
            <span class="fecha-dia">{{ formatDia(proximaCita.fecha_hora) }}</span>
            <span class="fecha-hora">{{ formatHora(proximaCita.fecha_hora) }}</span>
          </div>
          <div class="proxima-info">
            <h4>{{ proximaCita.cliente?.nombre }}</h4>
            <p>{{ proximaCita.servicios?.map((s: any) => s.nombre).join(', ') }}</p>
          </div>
          <div class="proxima-arrow">
            <i class="fa fa-chevron-right"></i>
          </div>
        </div>
      </div>

      <!-- Resto de citas -->
      <div class="lista-titulo" v-if="otrasCitas.length > 0">
        <span>Todas las citas</span>
        <span class="contador">{{ otrasCitas.length }}</span>
      </div>

      <div 
        v-for="cita in otrasCitas" 
        :key="cita.id"
        class="cita-card"
        @click="verDetalle(cita)"
      >
        <div class="cita-estado-bar" :class="cita.estado"></div>
        
        <div class="cita-fecha">
          <span class="dia">{{ formatDiaCorto(cita.fecha_hora) }}</span>
          <span class="hora">{{ formatHora(cita.fecha_hora) }}</span>
        </div>

        <div class="cita-body">
          <div class="cita-cliente">{{ cita.cliente?.nombre }}</div>
          <div class="cita-servicios">
            {{ cita.servicios?.map((s: any) => s.nombre).join(', ') }}
          </div>
          <div class="cita-meta">
            <span class="meta-item">
              <i class="fa fa-clock"></i>
              {{ cita.duracion_total }} min
            </span>
            <span :class="['badge', cita.estado]">
              {{ estadoTexto(cita.estado) }}
            </span>
          </div>
        </div>

        <div class="cita-arrow">
          <i class="fa fa-chevron-right"></i>
        </div>
      </div>
    </div>

    <!-- Modal detalle -->
    <Teleport to="body">
      <Transition name="modal">
        <div v-if="citaDetalle" class="modal-backdrop" @click="citaDetalle = null">
          <div class="modal-card" @click.stop>
            <div class="modal-header">
              <span class="modal-badge" :class="citaDetalle.estado">
                {{ estadoTexto(citaDetalle.estado) }}
              </span>
              <button class="modal-close" @click="citaDetalle = null">
                <i class="fa fa-times"></i>
              </button>
            </div>

            <div class="modal-datetime">
              <i class="fa fa-calendar-alt"></i>
              {{ formatFechaCompleta(citaDetalle.fecha_hora) }}
            </div>

            <div class="modal-section">
              <h5><i class="fa fa-user"></i> Cliente</h5>
              <p class="section-main">{{ citaDetalle.cliente?.nombre }}</p>
              <a :href="`tel:${citaDetalle.cliente?.telefono}`" class="section-link">
                <i class="fa fa-phone"></i>
                {{ citaDetalle.cliente?.telefono }}
              </a>
            </div>

            <div class="modal-section">
              <h5><i class="fa fa-spa"></i> Servicios</h5>
              <div class="servicios-list">
                <span 
                  v-for="s in citaDetalle.servicios" 
                  :key="s.id"
                  class="servicio-tag"
                >
                  {{ (s as any).nombre || s.servicio?.nombre }}
                </span>
              </div>
              <p class="section-sub">
                <i class="fa fa-hourglass-half"></i>
                Duración total: {{ citaDetalle.duracion_total }} min
              </p>
            </div>

            <div v-if="citaDetalle.notas" class="modal-section">
              <h5><i class="fa fa-sticky-note"></i> Notas</h5>
              <p class="section-notes">{{ citaDetalle.notas }}</p>
            </div>

            <div class="modal-actions">
              <button 
                v-if="citaDetalle.estado === 'confirmada'"
                class="action-btn success" 
                @click="cambiarEstado('en_proceso')"
              >
                <i class="fa fa-play"></i> Iniciar
              </button>
              <button 
                v-if="citaDetalle.estado === 'en_proceso'"
                class="action-btn primary" 
                @click="cambiarEstado('completada')"
              >
                <i class="fa fa-check"></i> Completar
              </button>
              <button 
                v-if="!['completada', 'cancelada', 'no_show'].includes(citaDetalle.estado)"
                class="action-btn danger" 
                @click="cambiarEstado('no_show')"
              >
                <i class="fa fa-user-slash"></i> No asistió
              </button>
              <a 
                :href="`https://wa.me/52${citaDetalle.cliente?.telefono}`"
                target="_blank"
                class="action-btn whatsapp"
              >
                <i class="fab fa-whatsapp"></i> WhatsApp
              </a>
            </div>
          </div>
        </div>
      </Transition>
    </Teleport>

    <!-- Modal Nueva Cita -->
    <Teleport to="body">
      <Transition name="modal">
        <div v-if="mostrarModalNuevaCita" class="modal-overlay" @click="cerrarModalNuevaCita">
          <div class="modal-content" @click.stop>
            <div class="modal-header">
              <h3>Nueva Cita</h3>
              <button class="modal-close" @click="cerrarModalNuevaCita">
                <i class="fa fa-times"></i>
              </button>
            </div>

            <div class="modal-body">
              <form @submit.prevent="guardarCita" class="cita-form">
              <!-- Cliente -->
              <div class="form-section">
                <label class="form-label">
                  <i class="fa fa-user"></i>
                  Cliente <span class="required">*</span>
                </label>
                <div class="form-row">
                  <div class="search-client-wrapper">
                    <div class="search-client-input">
                      <i class="fa fa-search"></i>
                      <input 
                        v-model="busquedaCliente"
                        type="text"
                        class="form-input search-input"
                        placeholder="Buscar por nombre o teléfono..."
                        @input="onBuscarCliente"
                      />
                      <button 
                        v-if="busquedaCliente"
                        type="button"
                        class="btn-clear-search"
                        @click="limpiarBusquedaCliente"
                      >
                        <i class="fa fa-times"></i>
                      </button>
                    </div>
                    <div v-if="busquedaCliente && clientesFiltrados.length > 0" class="clientes-dropdown">
                      <div 
                        v-for="cliente in clientesFiltrados" 
                        :key="cliente.id"
                        class="cliente-option"
                        :class="{ selected: clienteSeleccionado?.id === cliente.id }"
                        @click="seleccionarCliente(cliente)"
                      >
                        <div class="cliente-info">
                          <span class="cliente-nombre">{{ cliente.nombre }}</span>
                          <span class="cliente-telefono" v-if="cliente.telefono">
                            <i class="fa fa-phone"></i> {{ cliente.telefono }}
                          </span>
                        </div>
                        <i v-if="clienteSeleccionado?.id === cliente.id" class="fa fa-check"></i>
                      </div>
                    </div>
                    <div v-if="busquedaCliente && clientesFiltrados.length === 0" class="clientes-dropdown empty">
                      <div class="no-results">
                        <i class="fa fa-user-times"></i>
                        <p>No se encontraron clientes</p>
                        <button 
                          type="button"
                          class="btn-create-client"
                          @click="mostrarCamposNuevoCliente = true"
                        >
                          <i class="fa fa-plus"></i> Crear nuevo cliente
                        </button>
                      </div>
                    </div>
                  </div>
                  <button 
                    type="button" 
                    class="btn-add-client"
                    @click="mostrarCamposNuevoCliente = !mostrarCamposNuevoCliente"
                    :class="{ active: mostrarCamposNuevoCliente }"
                    title="Agregar nuevo cliente"
                  >
                    <i class="fa" :class="mostrarCamposNuevoCliente ? 'fa-times' : 'fa-plus'"></i>
                  </button>
                </div>
                
                <!-- Campos para nuevo cliente (inline) -->
                <Transition name="slide-down">
                  <div v-if="mostrarCamposNuevoCliente && !clienteSeleccionado" class="nuevo-cliente-fields">
                    <div class="nuevo-cliente-header">
                      <h4>
                        <i class="fa fa-user-plus"></i>
                        Nuevo Cliente
                      </h4>
                    </div>
                    <div class="form-row">
                      <div class="form-group">
                        <label class="form-label-small">
                          Nombre <span class="required">*</span>
                        </label>
                        <input 
                          v-model="nuevoClienteData.nombre" 
                          type="text" 
                          class="form-input"
                          placeholder="Nombre completo"
                        />
                      </div>
                      <div class="form-group">
                        <label class="form-label-small">
                          Teléfono <span class="required">*</span>
                        </label>
                        <input 
                          v-model="nuevoClienteData.telefono" 
                          type="tel" 
                          class="form-input"
                          placeholder="5512345678"
                          maxlength="10"
                        />
                      </div>
                    </div>
                    <div class="form-group">
                      <label class="form-label-small">
                        Email
                      </label>
                      <input 
                        v-model="nuevoClienteData.email" 
                        type="email" 
                        class="form-input"
                        placeholder="cliente@email.com"
                      />
                    </div>
                    <div class="nuevo-cliente-actions">
                      <button 
                        type="button"
                        class="btn-cancel-small"
                        @click="cancelarNuevoCliente"
                      >
                        Cancelar
                      </button>
                      <button 
                        type="button"
                        class="btn-submit-small"
                        @click="guardarNuevoCliente"
                        :disabled="!puedeGuardarCliente"
                      >
                        <i class="fa fa-save"></i>
                        Guardar Cliente
                      </button>
                    </div>
                  </div>
                </Transition>
                
                <div v-if="clienteSeleccionado" class="cliente-selected">
                  <div class="selected-info">
                    <i class="fa fa-check-circle"></i>
                    <span>{{ clienteSeleccionado.nombre }}</span>
                    <span v-if="clienteSeleccionado.telefono" class="selected-phone">{{ clienteSeleccionado.telefono }}</span>
                  </div>
                  <button 
                    type="button"
                    class="btn-remove-selection"
                    @click="deseleccionarCliente"
                  >
                    <i class="fa fa-times"></i>
                  </button>
                </div>
              </div>

              <!-- Servicios -->
              <div class="form-section">
                <label class="form-label">
                  <i class="fa fa-cut"></i>
                  Servicios <span class="required">*</span>
                </label>
                <div v-if="cargandoServicios" class="servicios-loading">
                  <div class="loading-message">
                    <i class="fa fa-spinner fa-spin"></i>
                    <p>Cargando servicios...</p>
                  </div>
                </div>
                <div v-else-if="misServicios.length === 0" class="servicios-empty">
                  <div class="empty-message">
                    <i class="fa fa-exclamation-triangle"></i>
                    <p>No tienes servicios asignados</p>
                  </div>
                </div>
                <div v-else class="servicios-selector">
                  <div 
                    v-for="servicio in misServicios" 
                    :key="servicio.id"
                    class="servicio-option"
                    :class="{ selected: serviciosSeleccionados.includes(servicio.id) }"
                    @click="toggleServicio(servicio.id)"
                  >
                    <div class="servicio-checkbox">
                      <i class="fa fa-check" v-if="serviciosSeleccionados.includes(servicio.id)"></i>
                    </div>
                    <div class="servicio-info">
                      <span class="servicio-name">{{ servicio.nombre }}</span>
                      <span class="servicio-details" v-if="servicio.duracion > 0 || servicio.precio > 0">
                        {{ servicio.duracion || 0 }} min • 
                        ${{ formatPrecio(servicio.precio) }}
                      </span>
                      <span class="servicio-details" v-else style="color: #999; font-style: italic;">
                        Sin información de precio/duración
                      </span>
                    </div>
                  </div>
                </div>
                <div v-if="serviciosSeleccionados.length === 0 && misServicios.length > 0" class="form-error">
                  Debes seleccionar al menos un servicio
                </div>
              </div>

              <!-- Fecha y Hora -->
              <div class="form-section">
                <label class="form-label">
                  <i class="fa fa-calendar-alt"></i>
                  Fecha y Hora <span class="required">*</span>
                </label>
                <div class="fecha-hora-container">
                  <input 
                    v-model="nuevaCitaData.fecha" 
                    type="date" 
                    class="form-input"
                    required
                    :min="minDate"
                    :disabled="serviciosSeleccionados.length === 0"
                    @change="onFechaChange"
                  />
                  <div class="hora-select-wrapper">
                    <select 
                      v-model="nuevaCitaData.hora" 
                      class="form-select"
                      required
                      :disabled="!nuevaCitaData.fecha || serviciosSeleccionados.length === 0 || cargandoHorarios"
                    >
                      <option value="">
                        {{ getHoraPlaceholder() }}
                      </option>
                      <option 
                        v-for="slot in horariosDisponibles" 
                        :key="slot.hora"
                        :value="slot.hora"
                      >
                        {{ formatHoraAmPm(slot.hora) }}
                      </option>
                    </select>
                    <div v-if="cargandoHorarios" class="loading-horarios">
                      <i class="fa fa-spinner fa-spin"></i>
                    </div>
                  </div>
                </div>
                <div v-if="serviciosSeleccionados.length === 0" class="form-hint">
                  <i class="fa fa-info-circle"></i>
                  Selecciona al menos un servicio para habilitar la fecha
                </div>
                <div v-if="!nuevaCitaData.fecha && serviciosSeleccionados.length > 0" class="form-hint">
                  <i class="fa fa-info-circle"></i>
                  Selecciona una fecha para ver los horarios disponibles
                </div>
                <div v-if="horariosDisponibles.length === 0 && nuevaCitaData.fecha && serviciosSeleccionados.length > 0 && !cargandoHorarios" class="form-error">
                  <i class="fa fa-exclamation-triangle"></i>
                  No hay horarios disponibles para esta fecha
                </div>
              </div>

              <!-- Precio Total -->
              <div class="form-section total-preview">
                <div class="total-row">
                  <span class="total-label">Total estimado:</span>
                  <span class="total-value">${{ formatPrecio(precioTotal) }}</span>
                </div>
                <div class="total-duration">
                  <i class="fa fa-clock"></i>
                  Duración: {{ duracionTotal }} minutos
                </div>
              </div>

              <!-- Notas -->
              <div class="form-section">
                <label class="form-label">
                  <i class="fa fa-sticky-note"></i>
                  Notas <span class="optional">(opcional)</span>
                </label>
                <textarea 
                  v-model="nuevaCitaData.notas" 
                  class="form-textarea"
                  placeholder="Agregar notas adicionales sobre la cita..."
                  rows="3"
                ></textarea>
              </div>

              <!-- Botones -->
              <div class="form-actions">
                <button type="button" class="btn-cancel" @click="cerrarModalNuevaCita">
                  Cancelar
                </button>
                <button type="submit" class="btn-submit" :disabled="!puedeGuardarCita || guardandoCita">
                  <i class="fa fa-save"></i>
                  {{ guardandoCita ? 'Guardando...' : 'Guardar Cita' }}
                </button>
              </div>
              </form>
            </div>
          </div>
        </div>
      </Transition>
    </Teleport>

    <!-- Botón flotante agregar cita -->
    <button class="btn-fab-add-cita" @click="abrirModalNuevaCita" title="Nueva cita">
      <i class="fa fa-plus"></i>
    </button>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, watch } from 'vue'
import { useAuthStore } from '@/stores/auth'
import api from '@/services/api'
import disponibilidadService from '@/services/disponibilidadService'
import type { Cita } from '@/types'

const authStore = useAuthStore()

const loading = ref(true)
const citas = ref<Cita[]>([])
const filtroEstado = ref('')
const filtroFecha = ref('')
const citaDetalle = ref<Cita | null>(null)

// Nueva cita
const mostrarModalNuevaCita = ref(false)
const clientes = ref<any[]>([])
const misServicios = ref<any[]>([])
const busquedaCliente = ref('')
const clienteSeleccionado = ref<any>(null)
const serviciosSeleccionados = ref<number[]>([])
const horariosDisponibles = ref<any[]>([])
const cargandoClientes = ref(false)
const cargandoServicios = ref(false)
const cargandoHorarios = ref(false)
const guardandoCita = ref(false)
const mostrarCamposNuevoCliente = ref(false)

const nuevaCitaData = ref({
  fecha: '',
  hora: '',
  notas: ''
})

const nuevoClienteData = ref({
  nombre: '',
  apellido: '',
  telefono: '',
  email: ''
})

const empleadoNombre = computed(() => authStore.user?.nombre || 'Empleado')
const empleadoId = computed(() => {
  // Primero intentar obtener del objeto empleado guardado
  if (authStore.empleado?.id) {
    return authStore.empleado.id
  }
  // Fallback al user id
  return authStore.user?.id
})

const citasFiltradas = computed(() => {
  return citas.value.filter(c => {
    const matchEstado = !filtroEstado.value || c.estado === filtroEstado.value
    const matchFecha = !filtroFecha.value || c.fecha_hora.split(/[T\s]/)[0] === filtroFecha.value
    return matchEstado && matchFecha
  })
})

const citasPendientes = computed(() => {
  return citas.value.filter(c => ['pendiente', 'confirmada'].includes(c.estado)).length
})

const proximaCita = computed(() => {
  return citasFiltradas.value.find(c => 
    c.estado === 'pendiente' || c.estado === 'confirmada'
  )
})

const otrasCitas = computed(() => {
  if (!proximaCita.value) return citasFiltradas.value
  return citasFiltradas.value.filter(c => c.id !== proximaCita.value?.id)
})

async function cargarCitas() {
  loading.value = true
  try {
    const response = await api.get('/empleado/citas')
    citas.value = response.data.data?.citas || []
  } catch (error) {
    console.error('Error cargando citas:', error)
    citas.value = []
  } finally {
    loading.value = false
  }
}

function verDetalle(cita: Cita) {
  citaDetalle.value = cita
}

function limpiarFiltros() {
  filtroEstado.value = ''
  filtroFecha.value = ''
}

async function cambiarEstado(nuevoEstado: string) {
  if (!citaDetalle.value) return
  try {
    const cita = citas.value.find(c => c.id === citaDetalle.value?.id)
    if (cita) {
      cita.estado = nuevoEstado as any
    }
    citaDetalle.value.estado = nuevoEstado as any
  } catch (error) {
    console.error('Error:', error)
  }
}

function tiempoRestante(cita: Cita): string {
  const fechaCita = new Date(cita.fecha_hora.replace(' ', 'T'))
  const ahora = new Date()
  const diff = fechaCita.getTime() - ahora.getTime()
  
  if (diff < 0) return 'Ahora'
  
  const minutos = Math.floor(diff / 60000)
  const horas = Math.floor(minutos / 60)
  const dias = Math.floor(horas / 24)
  
  if (dias > 0) return `En ${dias} día${dias > 1 ? 's' : ''}`
  if (horas > 0) return `En ${horas} hora${horas > 1 ? 's' : ''}`
  return `En ${minutos} min`
}

function formatDia(fecha: string): string {
  const fechaISO = fecha.replace(' ', 'T')
  return new Date(fechaISO).toLocaleDateString('es-MX', {
    weekday: 'short',
    day: 'numeric',
    month: 'short',
  })
}

function formatDiaCorto(fecha: string): string {
  const fechaISO = fecha.replace(' ', 'T')
  const d = new Date(fechaISO)
  return `${d.getDate()}/${d.getMonth() + 1}`
}

function formatHora(fecha: string): string {
  const fechaISO = fecha.replace(' ', 'T')
  return new Date(fechaISO).toLocaleTimeString('es-MX', {
    hour: '2-digit',
    minute: '2-digit',
  })
}

function formatFechaCompleta(fecha: string): string {
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

// ===== NUEVA CITA =====
const clientesFiltrados = computed(() => {
  if (!busquedaCliente.value || busquedaCliente.value.length < 2) return []
  const search = busquedaCliente.value.toLowerCase()
  return clientes.value.filter(c => {
    const nombre = (c.nombre || '').toLowerCase()
    const telefono = (c.telefono || '').toString()
    return nombre.includes(search) || telefono.includes(search)
  }).slice(0, 5)
})

const minDate = computed(() => {
  const today = new Date()
  return today.toISOString().split('T')[0]
})

const duracionTotal = computed(() => {
  const total = serviciosSeleccionados.value.reduce((total, id) => {
    const servicio = misServicios.value.find(s => s.id === id)
    const duracion = servicio?.duracion || 0
    return total + (typeof duracion === 'number' ? duracion : 0)
  }, 0)
  return isNaN(total) ? 0 : total
})

const precioTotal = computed(() => {
  const total = serviciosSeleccionados.value.reduce((total, id) => {
    const servicio = misServicios.value.find(s => s.id === id)
    const precio = servicio?.precio || 0
    return total + (typeof precio === 'number' ? precio : 0)
  }, 0)
  return isNaN(total) ? 0 : total
})

const puedeGuardarCliente = computed(() => {
  return nuevoClienteData.value.nombre.trim() && 
         nuevoClienteData.value.telefono.trim().length >= 10
})

const puedeGuardarCita = computed(() => {
  return clienteSeleccionado.value && 
         serviciosSeleccionados.value.length > 0 && 
         nuevaCitaData.value.fecha && 
         nuevaCitaData.value.hora
})

function abrirModalNuevaCita() {
  mostrarModalNuevaCita.value = true
  console.log('Abriendo modal nueva cita')
  console.log('Auth user:', authStore.user)
  console.log('Empleado ID:', empleadoId.value)
  cargarClientes()
  cargarMisServicios()
}

function cerrarModalNuevaCita() {
  mostrarModalNuevaCita.value = false
  resetFormNuevaCita()
}

function resetFormNuevaCita() {
  busquedaCliente.value = ''
  clienteSeleccionado.value = null
  serviciosSeleccionados.value = []
  horariosDisponibles.value = []
  nuevaCitaData.value = { fecha: '', hora: '', notas: '' }
  nuevoClienteData.value = { nombre: '', apellido: '', telefono: '', email: '' }
  mostrarCamposNuevoCliente.value = false
}

async function cargarClientes() {
  cargandoClientes.value = true
  console.log('Cargando clientes...')
  try {
    const response = await api.get('/empleado/clientes')
    console.log('Respuesta clientes:', response.data)
    clientes.value = response.data.data || response.data || []
    console.log('Clientes cargados:', clientes.value.length)
  } catch (error) {
    console.error('Error cargando clientes:', error)
    clientes.value = []
  } finally {
    cargandoClientes.value = false
  }
}

async function cargarMisServicios() {
  cargandoServicios.value = true
  console.log('Cargando mis servicios...')
  console.log('Empleado ID:', empleadoId.value)
  console.log('Auth empleado:', authStore.empleado)
  console.log('Auth user:', authStore.user)
  try {
    const response = await api.get('/empleado/mis-servicios')
    console.log('Respuesta mis-servicios:', response.data)
    const data = response.data.data || response.data || []
    misServicios.value = data.map((s: any) => {
      const precioRaw = s.precio_estandar || s.precio_especial || s.precio || 0
      const precio = typeof precioRaw === 'number' ? precioRaw : (typeof precioRaw === 'string' ? parseFloat(precioRaw) : 0)
      const duracionRaw = s.duracion_minutos || s.duracion || 0
      const duracion = typeof duracionRaw === 'number' ? duracionRaw : (typeof duracionRaw === 'string' ? parseInt(duracionRaw) : 0)
      
      return {
        id: s.id,
        nombre: s.nombre,
        duracion: isNaN(duracion) ? 0 : duracion,
        precio: isNaN(precio) ? 0 : precio
      }
    })
    console.log('Mis servicios mapeados:', misServicios.value)
  } catch (error) {
    console.error('Error cargando servicios:', error)
    misServicios.value = []
  } finally {
    cargandoServicios.value = false
  }
}

async function cargarHorariosDisponibles() {
  console.log('=== Cargar Horarios ===')
  console.log('Fecha:', nuevaCitaData.value.fecha)
  console.log('Servicios seleccionados:', serviciosSeleccionados.value)
  console.log('Empleado ID:', empleadoId.value)
  
  if (!nuevaCitaData.value.fecha || serviciosSeleccionados.value.length === 0) {
    console.log('Falta fecha o servicios, limpiando horarios')
    horariosDisponibles.value = []
    return
  }
  
  if (!empleadoId.value) {
    console.error('No hay empleado ID')
    horariosDisponibles.value = []
    return
  }
  
  cargandoHorarios.value = true
  nuevaCitaData.value.hora = ''
  
  try {
    console.log('Llamando a obtenerSlots con:', {
      empleadoId: empleadoId.value,
      fecha: nuevaCitaData.value.fecha,
      servicios: serviciosSeleccionados.value
    })
    
    // obtenerSlots espera un array de IDs de servicios
    // Pasamos true para indicar que es un empleado (ignora anticipación mínima)
    const response = await disponibilidadService.obtenerSlots(
      empleadoId.value as number,
      nuevaCitaData.value.fecha,
      serviciosSeleccionados.value,
      true // Es empleado, ignorar anticipación mínima
    )
    
    console.log('Respuesta obtenerSlots:', response)
    const responseData = response as any
    console.log('Horario del empleado:', responseData.horario_empleado)
    console.log('Bloqueos:', responseData.bloqueos_count)
    console.log('Citas existentes:', responseData.citas_count)
    console.log('Slots recibidos:', response.slots)
    console.log('Detalle de slots:', response.slots.map((s: any) => `${s.hora} - ${s.hora_fin}`))
    
    // Si los slots no tienen propiedad 'disponible', asumimos que todos están disponibles
    // Si tienen la propiedad, filtramos solo los disponibles
    const slotsDisponibles = (response.slots || []).filter((slot: any) => {
      // Si no tiene propiedad disponible, está disponible
      if (slot.disponible === undefined) {
        return true
      }
      // Si tiene la propiedad, debe ser true
      return slot.disponible === true
    })
    
    console.log('Slots disponibles filtrados:', slotsDisponibles)
    console.log('Horarios disponibles:', slotsDisponibles.map((s: any) => `${s.hora} - ${s.hora_fin}`))
    console.log('Total slots disponibles:', slotsDisponibles.length)
    
    // Si hay horario del empleado pero no hay slots disponibles, mostrar mensaje
    if (responseData.horario_empleado && slotsDisponibles.length === 0) {
      console.warn('No hay slots disponibles aunque el empleado tiene horario:', responseData.horario_empleado)
    }
    
    horariosDisponibles.value = slotsDisponibles
  } catch (error: any) {
    console.error('Error cargando horarios:', error)
    console.error('Error response:', error.response?.data)
    horariosDisponibles.value = []
  } finally {
    cargandoHorarios.value = false
  }
}

function onBuscarCliente() {
  // Trigger reactivity
}

function limpiarBusquedaCliente() {
  busquedaCliente.value = ''
}

function seleccionarCliente(cliente: any) {
  clienteSeleccionado.value = cliente
  busquedaCliente.value = ''
}

function deseleccionarCliente() {
  clienteSeleccionado.value = null
}

function toggleServicio(servicioId: number) {
  const index = serviciosSeleccionados.value.indexOf(servicioId)
  if (index > -1) {
    serviciosSeleccionados.value.splice(index, 1)
  } else {
    serviciosSeleccionados.value.push(servicioId)
  }
  onServiciosChange()
}

function onServiciosChange() {
  console.log('Servicios cambiaron:', serviciosSeleccionados.value)
  // Si ya hay una fecha seleccionada, recargar horarios
  if (nuevaCitaData.value.fecha && serviciosSeleccionados.value.length > 0) {
    nuevaCitaData.value.hora = ''
    cargarHorariosDisponibles()
  } else {
    nuevaCitaData.value.fecha = ''
    nuevaCitaData.value.hora = ''
    horariosDisponibles.value = []
  }
}

function onFechaChange() {
  console.log('Fecha cambió:', nuevaCitaData.value.fecha)
  if (serviciosSeleccionados.value.length > 0) {
    cargarHorariosDisponibles()
  }
}

function getHoraPlaceholder(): string {
  if (!nuevaCitaData.value.fecha) return 'Selecciona fecha primero'
  if (cargandoHorarios.value) return 'Cargando horarios...'
  if (horariosDisponibles.value.length === 0) return 'Sin horarios disponibles'
  return 'Seleccionar hora'
}

function formatHoraAmPm(hora: string): string {
  if (!hora) return ''
  const parts = hora.split(':')
  const h = parts[0] || '0'
  const m = parts[1] || '00'
  const hour = parseInt(h)
  const ampm = hour >= 12 ? 'PM' : 'AM'
  const hour12 = hour % 12 || 12
  return `${hour12}:${m} ${ampm}`
}

function cancelarNuevoCliente() {
  mostrarCamposNuevoCliente.value = false
  nuevoClienteData.value = { nombre: '', apellido: '', telefono: '', email: '' }
}

function formatPrecio(precio: number | string | null | undefined): string {
  const numPrecio = typeof precio === 'number' ? precio : (typeof precio === 'string' ? parseFloat(precio) : 0)
  return (isNaN(numPrecio) ? 0 : numPrecio).toFixed(2)
}

async function guardarNuevoCliente() {
  console.log('Guardando nuevo cliente:', nuevoClienteData.value)
  console.log('Puede guardar cliente:', puedeGuardarCliente.value)
  
  if (!puedeGuardarCliente.value) {
    console.log('No se puede guardar: falta nombre o teléfono')
    return
  }
  
  try {
    const response = await api.post('/empleado/clientes', nuevoClienteData.value)
    console.log('Respuesta guardar cliente:', response.data)
    const nuevoCliente = response.data.data || response.data
    clientes.value.push(nuevoCliente)
    clienteSeleccionado.value = nuevoCliente
    mostrarCamposNuevoCliente.value = false
    nuevoClienteData.value = { nombre: '', apellido: '', telefono: '', email: '' }
  } catch (error: any) {
    console.error('Error creando cliente:', error)
    console.error('Error response:', error.response?.data)
    alert('Error al crear el cliente: ' + (error.response?.data?.message || 'Error desconocido'))
  }
}

async function guardarCita() {
  console.log('Guardando cita...')
  console.log('Puede guardar cita:', puedeGuardarCita.value)
  console.log('Cliente seleccionado:', clienteSeleccionado.value)
  console.log('Servicios seleccionados:', serviciosSeleccionados.value)
  console.log('Fecha:', nuevaCitaData.value.fecha)
  console.log('Hora:', nuevaCitaData.value.hora)
  
  if (!puedeGuardarCita.value || guardandoCita.value) return
  
  guardandoCita.value = true
  
  try {
    const fechaHora = `${nuevaCitaData.value.fecha} ${nuevaCitaData.value.hora}:00`
    
    const payload = {
      cliente_id: clienteSeleccionado.value.id,
      empleado_id: empleadoId.value,
      servicios: serviciosSeleccionados.value.map(id => {
        const servicio = misServicios.value.find(s => s.id === id)
        return { id, duracion: servicio?.duracion || 30 }
      }),
      fecha_hora: fechaHora,
      estado: 'confirmada',
      notas: nuevaCitaData.value.notas || null
    }
    
    console.log('Payload cita:', payload)
    
    const response = await api.post('/empleado/citas', payload)
    console.log('Respuesta guardar cita:', response.data)
    
    await cargarCitas()
    cerrarModalNuevaCita()
    alert('Cita creada exitosamente')
  } catch (error: any) {
    console.error('Error guardando cita:', error)
    console.error('Error response:', error.response?.data)
    const msg = error.response?.data?.message || 'Error al crear la cita'
    alert(msg)
  } finally {
    guardandoCita.value = false
  }
}

onMounted(() => {
  const today = new Date()
  filtroFecha.value = today.toISOString().split('T')[0] || ''
  cargarCitas()
})
</script>

<style scoped>
.citas-view {
  min-height: 100%;
  background: var(--color-background);
  padding-bottom: 100px;
}

/* ===== HEADER ROW ===== */
.header-row {
  display: flex;
  gap: 12px;
  padding: 16px;
  align-items: stretch;
}

/* ===== HEADER INFO CARD ===== */
.header-info-card {
  flex: 0 0 calc(66.666% - 6px);
  display: flex;
  align-items: center;
  gap: 14px;
  padding: 16px;
  background: var(--color-card);
  border-radius: 16px;
  box-shadow: 0 2px 8px rgba(0,0,0,0.05);
}

.header-icon {
  width: 50px;
  height: 50px;
  border-radius: 14px;
  background: linear-gradient(135deg, rgba(236,64,122,0.1), rgba(236,64,122,0.2));
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

.header-icon i {
  font-size: 22px;
  color: #ec407a;
}

.header-text {
  flex: 1;
  min-width: 0;
}

.header-text h2 {
  font-size: 20px;
  font-weight: 700;
  color: var(--color-text);
  margin: 0;
}

.header-text p {
  font-size: 13px;
  color: var(--color-text-secondary);
  margin: 4px 0 0;
}

/* ===== STATS CARD ===== */
.stats-card {
  flex: 0 0 calc(33.333% - 6px);
}

.stat-item {
  height: 100%;
  background: linear-gradient(135deg, #ec407a, #c2185b);
  padding: 16px 12px;
  border-radius: 16px;
  text-align: center;
  box-shadow: 0 4px 12px rgba(236, 64, 122, 0.3);
  display: flex;
  flex-direction: column;
  justify-content: center;
}

.stat-value {
  display: block;
  font-size: 28px;
  font-weight: 800;
  color: white;
  line-height: 1;
  margin-bottom: 4px;
}

.stat-label {
  font-size: 11px;
  color: rgba(255,255,255,0.9);
  text-transform: uppercase;
  letter-spacing: 0.5px;
  font-weight: 600;
}

/* ===== FILTROS ===== */
.filtros-card {
  display: flex;
  gap: 12px;
  padding: 16px;
  margin: 0 16px 16px;
  background: var(--color-card);
  border-radius: 16px;
}

.filtro-grupo {
  flex: 1;
}

.filtro-grupo label {
  display: block;
  font-size: 11px;
  font-weight: 600;
  color: var(--color-text-secondary);
  margin-bottom: 6px;
}

.filtro-grupo label i {
  margin-right: 4px;
  color: #ec407a;
}

.filtro-grupo select,
.filtro-grupo input {
  width: 100%;
  padding: 10px 12px;
  border: 2px solid var(--color-border);
  border-radius: 10px;
  font-size: 13px;
  background: var(--color-background);
  color: var(--color-text);
}

.filtro-grupo select:focus,
.filtro-grupo input:focus {
  outline: none;
  border-color: #ec407a;
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
  margin: 0 0 20px;
}

.clear-btn {
  padding: 12px 20px;
  background: linear-gradient(135deg, #ec407a, #c2185b);
  color: white;
  border: none;
  border-radius: 12px;
  font-size: 14px;
  font-weight: 600;
  cursor: pointer;
}

/* ===== LISTA ===== */
.citas-lista {
  padding: 0 16px;
}

/* Próxima cita */
.proxima-cita-card {
  background: linear-gradient(135deg, #fff8e1, #ffecb3);
  border-radius: 20px;
  margin-bottom: 20px;
  overflow: hidden;
  border: 2px solid #ffc107;
}

.proxima-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 12px 16px;
  background: rgba(255,193,7,0.3);
}

.proxima-label {
  font-size: 12px;
  font-weight: 700;
  color: #f57c00;
  display: flex;
  align-items: center;
  gap: 6px;
}

.proxima-tiempo {
  font-size: 12px;
  font-weight: 600;
  color: #e65100;
  background: white;
  padding: 4px 10px;
  border-radius: 10px;
}

.proxima-body {
  display: flex;
  align-items: center;
  gap: 14px;
  padding: 16px;
  cursor: pointer;
}

.proxima-fecha {
  text-align: center;
  padding-right: 14px;
  border-right: 2px solid rgba(0,0,0,0.1);
}

.fecha-dia {
  display: block;
  font-size: 11px;
  color: #666;
  text-transform: uppercase;
}

.fecha-hora {
  display: block;
  font-size: 20px;
  font-weight: 700;
  color: #333;
}

.proxima-info {
  flex: 1;
}

.proxima-info h4 {
  margin: 0 0 4px;
  font-size: 16px;
  font-weight: 600;
  color: #333;
}

.proxima-info p {
  margin: 0;
  font-size: 13px;
  color: #666;
}

.proxima-arrow {
  color: #f57c00;
  font-size: 16px;
}

/* Lista título */
.lista-titulo {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 12px 0;
  margin-bottom: 8px;
}

.lista-titulo span:first-child {
  font-size: 14px;
  font-weight: 600;
  color: var(--color-text);
}

.contador {
  font-size: 12px;
  background: rgba(0,0,0,0.08);
  padding: 4px 10px;
  border-radius: 10px;
  color: var(--color-text-secondary);
}

/* Cita card */
.cita-card {
  display: flex;
  align-items: center;
  gap: 12px;
  background: var(--color-card);
  border-radius: 16px;
  margin-bottom: 10px;
  overflow: hidden;
  cursor: pointer;
  transition: transform 0.2s, box-shadow 0.2s;
}

.cita-card:hover {
  transform: translateX(4px);
  box-shadow: 0 4px 15px rgba(0,0,0,0.1);
}

.cita-estado-bar {
  width: 4px;
  align-self: stretch;
}

.cita-estado-bar.pendiente { background: #ff9800; }
.cita-estado-bar.confirmada { background: #4caf50; }
.cita-estado-bar.en_proceso { background: #2196f3; }
.cita-estado-bar.completada { background: #9c27b0; }
.cita-estado-bar.cancelada { background: #f44336; }
.cita-estado-bar.no_show { background: #9e9e9e; }

.cita-fecha {
  text-align: center;
  padding: 16px 12px;
  min-width: 60px;
}

.cita-fecha .dia {
  display: block;
  font-size: 11px;
  color: var(--color-text-secondary);
}

.cita-fecha .hora {
  display: block;
  font-size: 16px;
  font-weight: 700;
  color: var(--color-text);
}

.cita-body {
  flex: 1;
  padding: 14px 0;
}

.cita-cliente {
  font-size: 15px;
  font-weight: 600;
  color: var(--color-text);
  margin-bottom: 2px;
}

.cita-servicios {
  font-size: 12px;
  color: var(--color-text-secondary);
  margin-bottom: 6px;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  max-width: 180px;
}

.cita-meta {
  display: flex;
  align-items: center;
  gap: 10px;
}

.meta-item {
  font-size: 11px;
  color: var(--color-text-secondary);
}

.meta-item i {
  margin-right: 4px;
  color: #ec407a;
}

.badge {
  padding: 3px 8px;
  border-radius: 8px;
  font-size: 10px;
  font-weight: 700;
  text-transform: uppercase;
}

.badge.pendiente { background: #fff3e0; color: #e65100; }
.badge.confirmada { background: #e8f5e9; color: #2e7d32; }
.badge.en_proceso { background: #e3f2fd; color: #1565c0; }
.badge.completada { background: #f3e5f5; color: #7b1fa2; }
.badge.cancelada { background: #ffebee; color: #c62828; }
.badge.no_show { background: #fafafa; color: #616161; }

.cita-arrow {
  padding-right: 14px;
  color: #ccc;
  font-size: 12px;
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

.modal-section {
  padding: 16px 20px;
  border-top: 1px solid var(--color-border);
}

.modal-section h5 {
  font-size: 12px;
  font-weight: 600;
  color: var(--color-text-secondary);
  margin: 0 0 8px;
  display: flex;
  align-items: center;
  gap: 6px;
}

.modal-section h5 i {
  color: #ec407a;
}

.section-main {
  font-size: 16px;
  font-weight: 600;
  color: var(--color-text);
  margin: 0 0 6px;
}

.section-link {
  font-size: 14px;
  color: #ec407a;
  text-decoration: none;
  display: inline-flex;
  align-items: center;
  gap: 6px;
}

.servicios-list {
  display: flex;
  flex-wrap: wrap;
  gap: 6px;
  margin-bottom: 10px;
}

.servicio-tag {
  padding: 6px 12px;
  background: linear-gradient(135deg, rgba(236,64,122,0.1), rgba(236,64,122,0.2));
  color: #c2185b;
  border-radius: 10px;
  font-size: 12px;
  font-weight: 500;
}

.section-sub {
  font-size: 12px;
  color: var(--color-text-secondary);
  margin: 0;
}

.section-sub i {
  margin-right: 4px;
}

.section-notes {
  font-size: 14px;
  color: var(--color-text);
  margin: 0;
  background: rgba(0,0,0,0.03);
  padding: 10px;
  border-radius: 8px;
}

.modal-actions {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 10px;
  padding: 20px;
  border-top: 1px solid var(--color-border);
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
  text-decoration: none;
  transition: all 0.2s;
}

.action-btn:hover {
  transform: translateY(-2px);
}

.action-btn.success { background: #e8f5e9; color: #2e7d32; }
.action-btn.primary { background: linear-gradient(135deg, #ec407a, #c2185b); color: white; }
.action-btn.danger { background: #ffebee; color: #c62828; }
.action-btn.whatsapp { background: #25d366; color: white; }

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

/* ===== BOTÓN FLOTANTE AGREGAR CITA ===== */
.btn-fab-add-cita {
  position: fixed;
  bottom: calc(env(safe-area-inset-bottom, 0px) + 80px);
  right: 20px;
  width: 56px;
  height: 56px;
  border-radius: 50%;
  background: linear-gradient(135deg, #ec407a, #c2185b);
  border: none;
  color: white;
  font-size: 24px;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  box-shadow: 0 4px 16px rgba(236, 64, 122, 0.5);
  transition: transform 0.2s, box-shadow 0.2s;
  z-index: 100;
}

.btn-fab-add-cita:hover {
  transform: scale(1.1);
  box-shadow: 0 6px 20px rgba(236, 64, 122, 0.6);
}

.btn-fab-add-cita:active {
  transform: scale(0.95);
}

/* ===== MODAL NUEVA CITA ===== */
.modal-overlay {
  position: fixed;
  inset: 0;
  background: rgba(0, 0, 0, 0.6);
  backdrop-filter: blur(4px);
  display: flex;
  align-items: flex-end;
  justify-content: center;
  z-index: 1000;
  animation: fadeIn 0.3s ease;
}

@keyframes fadeIn {
  from { opacity: 0; }
  to { opacity: 1; }
}

.modal-content {
  background: white;
  width: 100%;
  max-height: 90vh;
  border-radius: 28px 28px 0 0;
  overflow: hidden;
  animation: slideUp 0.4s cubic-bezier(0.4, 0, 0.2, 1);
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
  font-weight: 700;
  color: #1a1a2e;
  letter-spacing: -0.3px;
}

.modal-close {
  width: 40px;
  height: 40px;
  border: none;
  border-radius: 50%;
  background: #f0f0f0;
  color: #666;
  font-size: 16px;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all 0.2s;
}

.modal-close:hover {
  background: #e0e0e0;
  transform: rotate(90deg);
}

.modal-close:active {
  transform: rotate(90deg) scale(0.95);
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

/* Form Styles */
.cita-form {
  display: flex;
  flex-direction: column;
  gap: 20px;
}

.form-section {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.form-label {
  display: flex;
  align-items: center;
  gap: 8px;
  font-size: 13px;
  font-weight: 700;
  color: #667eea;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.form-label i {
  font-size: 14px;
  width: 20px;
  text-align: center;
}

.required {
  color: #fa709a;
}

.optional {
  color: #999;
  font-weight: 400;
  text-transform: none;
  font-size: 11px;
}

.form-row {
  display: flex;
  gap: 10px;
  align-items: center;
}

.form-select,
.form-input,
.form-textarea {
  width: 100%;
  padding: 14px 16px;
  border: 2px solid #e9ecef;
  border-radius: 12px;
  font-size: 15px;
  background: white;
  color: #333;
  transition: all 0.2s;
  font-family: inherit;
}

.form-select:focus,
.form-input:focus,
.form-textarea:focus {
  outline: none;
  border-color: #667eea;
  box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
}

.form-textarea {
  resize: vertical;
  min-height: 80px;
}

.form-hint {
  display: flex;
  align-items: center;
  gap: 8px;
  margin-top: 8px;
  padding: 10px 12px;
  background: rgba(102, 126, 234, 0.1);
  border-radius: 8px;
  font-size: 12px;
  color: #667eea;
  font-weight: 600;
}

.form-hint i {
  font-size: 14px;
  flex-shrink: 0;
}

.form-error {
  font-size: 12px;
  color: #fa709a;
  font-weight: 600;
  margin-top: 4px;
  display: flex;
  align-items: center;
  gap: 6px;
}

/* Search Client */
.search-client-wrapper {
  position: relative;
  flex: 1;
}

.search-client-input {
  position: relative;
  display: flex;
  align-items: center;
  gap: 10px;
}

.search-client-input i.fa-search {
  position: absolute;
  left: 16px;
  color: #999;
  font-size: 14px;
  z-index: 1;
}

.search-input {
  padding-left: 44px !important;
  padding-right: 44px !important;
}

.btn-clear-search {
  position: absolute;
  right: 12px;
  width: 28px;
  height: 28px;
  border: none;
  background: #f0f0f0;
  border-radius: 50%;
  color: #666;
  font-size: 12px;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all 0.2s;
  z-index: 1;
}

.btn-clear-search:hover {
  background: #e0e0e0;
  transform: scale(1.1);
}

.clientes-dropdown {
  position: absolute;
  top: calc(100% + 8px);
  left: 0;
  right: 0;
  background: white;
  border-radius: 12px;
  box-shadow: 0 8px 24px rgba(0, 0, 0, 0.15);
  max-height: 300px;
  overflow-y: auto;
  z-index: 1000;
  border: 2px solid #e9ecef;
  animation: slideDown 0.2s ease;
}

@keyframes slideDown {
  from {
    opacity: 0;
    transform: translateY(-10px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

.cliente-option {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 14px 16px;
  cursor: pointer;
  transition: all 0.2s;
  border-bottom: 1px solid #f0f0f0;
}

.cliente-option:last-child {
  border-bottom: none;
}

.cliente-option:hover {
  background: linear-gradient(135deg, rgba(102, 126, 234, 0.05) 0%, rgba(118, 75, 162, 0.05) 100%);
}

.cliente-option.selected {
  background: linear-gradient(135deg, rgba(102, 126, 234, 0.1) 0%, rgba(118, 75, 162, 0.1) 100%);
}

.cliente-info {
  display: flex;
  flex-direction: column;
  gap: 4px;
  flex: 1;
}

.cliente-nombre {
  font-size: 15px;
  font-weight: 700;
  color: #333;
}

.cliente-telefono {
  font-size: 12px;
  color: #999;
  display: flex;
  align-items: center;
  gap: 6px;
}

.cliente-telefono i {
  font-size: 10px;
}

.cliente-option i.fa-check {
  color: #667eea;
  font-size: 16px;
  flex-shrink: 0;
}

.clientes-dropdown.empty {
  padding: 40px 20px;
}

.no-results {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 12px;
  text-align: center;
}

.no-results i {
  font-size: 48px;
  color: #ccc;
}

.no-results p {
  margin: 0;
  color: #999;
  font-size: 14px;
}

.btn-create-client {
  padding: 12px 20px;
  border: none;
  border-radius: 10px;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  font-size: 14px;
  font-weight: 700;
  cursor: pointer;
  display: flex;
  align-items: center;
  gap: 8px;
  transition: all 0.2s;
  box-shadow: 0 2px 8px rgba(102, 126, 234, 0.3);
}

.btn-create-client:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
}

.btn-create-client:active {
  transform: translateY(0);
}

.cliente-selected {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 12px 16px;
  background: linear-gradient(135deg, rgba(17, 153, 142, 0.1) 0%, rgba(56, 239, 125, 0.1) 100%);
  border-radius: 12px;
  border: 2px solid rgba(17, 153, 142, 0.2);
  margin-top: 10px;
}

.selected-info {
  display: flex;
  align-items: center;
  gap: 10px;
  flex: 1;
}

.selected-info i {
  color: #11998e;
  font-size: 18px;
}

.selected-info span:first-of-type {
  font-size: 15px;
  font-weight: 700;
  color: #333;
}

.selected-phone {
  font-size: 13px;
  color: #999;
  margin-left: 8px;
}

.btn-remove-selection {
  width: 32px;
  height: 32px;
  border: none;
  background: rgba(250, 112, 154, 0.1);
  border-radius: 8px;
  color: #fa709a;
  font-size: 14px;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all 0.2s;
  flex-shrink: 0;
}

.btn-remove-selection:hover {
  background: rgba(250, 112, 154, 0.2);
  transform: scale(1.1);
}

.btn-remove-selection:active {
  transform: scale(0.95);
}

.btn-add-client {
  width: 44px;
  height: 44px;
  border: none;
  border-radius: 12px;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  font-size: 16px;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
  transition: all 0.2s;
  box-shadow: 0 2px 8px rgba(102, 126, 234, 0.3);
}

.btn-add-client:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
}

.btn-add-client:active {
  transform: translateY(0) scale(0.95);
}

.btn-add-client.active {
  background: linear-gradient(135deg, #fa709a 0%, #fee140 100%);
  transform: rotate(45deg);
}

/* Nuevo cliente inline */
.nuevo-cliente-fields {
  margin-top: 16px;
  padding: 20px;
  background: linear-gradient(135deg, rgba(102, 126, 234, 0.05) 0%, rgba(118, 75, 162, 0.05) 100%);
  border-radius: 16px;
  border: 2px solid rgba(102, 126, 234, 0.2);
}

.nuevo-cliente-header {
  margin-bottom: 16px;
  padding-bottom: 12px;
  border-bottom: 2px solid rgba(102, 126, 234, 0.1);
}

.nuevo-cliente-header h4 {
  margin: 0;
  font-size: 16px;
  font-weight: 700;
  color: #667eea;
  display: flex;
  align-items: center;
  gap: 8px;
}

.nuevo-cliente-header i {
  font-size: 18px;
}

.nuevo-cliente-fields .form-row {
  margin-bottom: 12px;
}

.nuevo-cliente-fields .form-group {
  flex: 1;
}

.form-label-small {
  display: block;
  font-size: 12px;
  font-weight: 700;
  color: #667eea;
  margin-bottom: 6px;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.nuevo-cliente-actions {
  display: flex;
  gap: 10px;
  margin-top: 16px;
  padding-top: 16px;
  border-top: 1px solid rgba(102, 126, 234, 0.1);
}

.btn-cancel-small,
.btn-submit-small {
  flex: 1;
  padding: 12px 16px;
  border: none;
  border-radius: 10px;
  font-size: 14px;
  font-weight: 700;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 6px;
  transition: all 0.2s;
}

.btn-cancel-small {
  background: #f0f0f0;
  color: #666;
}

.btn-cancel-small:hover {
  background: #e0e0e0;
}

.btn-submit-small {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  box-shadow: 0 2px 8px rgba(102, 126, 234, 0.3);
}

.btn-submit-small:hover:not(:disabled) {
  box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
  transform: translateY(-2px);
}

.btn-submit-small:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

/* Transición para campos nuevo cliente */
.slide-down-enter-active {
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.slide-down-leave-active {
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.slide-down-enter-from {
  opacity: 0;
  transform: translateY(-20px);
  max-height: 0;
}

.slide-down-leave-to {
  opacity: 0;
  transform: translateY(-20px);
  max-height: 0;
}

.slide-down-enter-to,
.slide-down-leave-from {
  opacity: 1;
  transform: translateY(0);
  max-height: 500px;
}

/* Servicios */
.servicios-selector {
  display: flex;
  flex-direction: column;
  gap: 10px;
  max-height: 300px;
  overflow-y: auto;
  padding: 4px;
}

.servicio-option {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 14px;
  background: white;
  border: 2px solid #e9ecef;
  border-radius: 12px;
  cursor: pointer;
  transition: all 0.2s;
}

.servicio-option:hover {
  border-color: #667eea;
  background: #f8f9ff;
  transform: translateX(4px);
}

.servicio-option.selected {
  border-color: #667eea;
  background: linear-gradient(135deg, rgba(102, 126, 234, 0.1) 0%, rgba(118, 75, 162, 0.1) 100%);
  box-shadow: 0 2px 8px rgba(102, 126, 234, 0.15);
}

.servicio-checkbox {
  width: 24px;
  height: 24px;
  border: 2px solid #e9ecef;
  border-radius: 6px;
  display: flex;
  align-items: center;
  justify-content: center;
  background: white;
  transition: all 0.2s;
  flex-shrink: 0;
}

.servicio-option.selected .servicio-checkbox {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  border-color: #667eea;
  color: white;
}

.servicio-option.selected .servicio-checkbox i {
  font-size: 12px;
}

.servicio-info {
  display: flex;
  flex-direction: column;
  gap: 4px;
  flex: 1;
}

.servicio-name {
  font-size: 15px;
  font-weight: 700;
  color: #333;
}

.servicio-details {
  font-size: 12px;
  color: #999;
  font-weight: 500;
}

.servicios-disabled,
.servicios-loading,
.servicios-empty {
  padding: 40px 20px;
  text-align: center;
  border-radius: 12px;
  background: #f8f9fa;
  border: 2px dashed #e9ecef;
}

.disabled-message,
.loading-message,
.empty-message {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 12px;
}

.loading-message i,
.empty-message i {
  font-size: 48px;
}

.loading-message i {
  color: #667eea;
}

.empty-message i {
  color: #fa709a;
}

.loading-message p,
.empty-message p {
  margin: 0;
  color: #999;
  font-size: 14px;
  font-weight: 600;
}

.servicios-selector::-webkit-scrollbar {
  width: 6px;
}

.servicios-selector::-webkit-scrollbar-track {
  background: #f1f1f1;
  border-radius: 3px;
}

.servicios-selector::-webkit-scrollbar-thumb {
  background: #c1c1c1;
  border-radius: 3px;
}

.servicios-selector::-webkit-scrollbar-thumb:hover {
  background: #a8a8a8;
}

/* Contenedor Fecha y Hora */
.fecha-hora-container {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

/* Selector de Horarios */
.hora-select-wrapper {
  position: relative;
  width: 100%;
}

.hora-select-wrapper .form-select:disabled {
  background: #f5f5f5;
  color: #999;
  cursor: not-allowed;
}

.loading-horarios {
  position: absolute;
  right: 16px;
  top: 50%;
  transform: translateY(-50%);
  color: #667eea;
  pointer-events: none;
}

/* Total preview */
.total-preview {
  background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
  padding: 18px;
  border-radius: 12px;
  border: 2px solid #e0e0e0;
}

.total-preview .total-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 8px;
}

.total-preview .total-label {
  font-size: 14px;
  font-weight: 700;
  color: #666;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.total-preview .total-value {
  font-size: 24px;
  font-weight: 800;
  background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
}

.total-duration {
  display: flex;
  align-items: center;
  gap: 6px;
  font-size: 12px;
  color: #999;
  font-weight: 600;
}

.total-duration i {
  font-size: 11px;
}

.form-actions {
  display: flex;
  gap: 12px;
  margin-top: 8px;
  padding-top: 20px;
  border-top: 1px solid #e9ecef;
}

.btn-cancel,
.btn-submit {
  flex: 1;
  padding: 16px;
  border: none;
  border-radius: 12px;
  font-size: 15px;
  font-weight: 700;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.btn-cancel {
  background: #f0f0f0;
  color: #666;
}

.btn-cancel:hover {
  background: #e0e0e0;
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

.btn-submit {
  background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
  color: white;
  box-shadow: 0 4px 12px rgba(17, 153, 142, 0.3);
}

.btn-submit:hover:not(:disabled) {
  box-shadow: 0 6px 20px rgba(17, 153, 142, 0.4);
  transform: translateY(-2px);
}

.btn-submit:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.btn-cancel:active,
.btn-submit:active:not(:disabled) {
  transform: translateY(0) scale(0.98);
}
</style>
