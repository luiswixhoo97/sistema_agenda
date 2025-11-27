<template>
  <div class="citas-view">
    <!-- Header Row -->
    <div class="header-row">
      <!-- Info Card (8 cols) -->
      <div class="header-info-card">
        <div class="header-icon">
          <i class="fa fa-calendar-check"></i>
        </div>
        <div class="header-text">
          <h2>Mis Citas</h2>
          <p>{{ empleadoNombre }}</p>
        </div>
      </div>

      <!-- Stats Card (4 cols) -->
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
                  {{ s.nombre }}
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
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useAuthStore } from '@/stores/auth'
import api from '@/services/api'
import type { Cita } from '@/types'

const authStore = useAuthStore()

const loading = ref(true)
const citas = ref<Cita[]>([])
const filtroEstado = ref('')
const filtroFecha = ref('')
const citaDetalle = ref<Cita | null>(null)

const empleadoNombre = computed(() => authStore.user?.nombre || 'Empleado')

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
    const index = citas.value.findIndex(c => c.id === citaDetalle.value?.id)
    if (index !== -1) {
      citas.value[index].estado = nuevoEstado as any
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

onMounted(() => {
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
}

/* ===== HEADER INFO CARD (8 cols) ===== */
.header-info-card {
  flex: 0 0 calc(66.666% - 6px); /* 8/12 = 66.666% */
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

/* ===== STATS CARD (4 cols) ===== */
.stats-card {
  flex: 0 0 calc(33.333% - 6px); /* 4/12 = 33.333% */
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
</style>
