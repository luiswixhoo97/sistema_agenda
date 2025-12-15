<template>
  <div class="perfil-view">
    <!-- Header -->
    <div class="perfil-header-section">
      <div class="profile-header">
        <div class="header-top">
          <div class="profile-avatar">
            <img v-if="empleado?.foto" :src="empleado.foto" alt="Foto" />
            <span v-else class="avatar-letter">{{ iniciales }}</span>
          </div>
          <div class="header-info">
            <h1>{{ empleado?.nombre || 'Cargando...' }}</h1>
            <p class="profile-bio" v-if="empleado?.bio">{{ empleado.bio }}</p>
            <div class="profile-meta">
              <span v-if="empleado?.especialidades" class="meta-tag">
                <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <rect x="2" y="7" width="20" height="14" rx="2" ry="2"></rect>
                  <path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"></path>
                </svg>
                {{ empleado.especialidades }}
              </span>
              <span v-if="empleado?.promedio_calificacion" class="meta-tag rating">
                <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon>
                </svg>
                {{ empleado.promedio_calificacion.toFixed(1) }}
              </span>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Loading -->
    <div v-if="loading" class="loading-container">
      <div class="loader"></div>
      <p>Cargando información...</p>
    </div>

    <template v-else>
      <!-- Información del Empleado -->
      <div class="section">
        <div class="info-card">
          <div class="card-title">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
              <circle cx="12" cy="7" r="4"></circle>
            </svg>
            <h3>Mi Información</h3>
          </div>
          <div class="info-row">
            <div class="info-icon">
              <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path>
                <polyline points="22,6 12,13 2,6"></polyline>
              </svg>
            </div>
            <span>{{ empleado?.email || 'No registrado' }}</span>
          </div>
          <div class="info-row">
            <div class="info-icon">
              <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"></path>
              </svg>
            </div>
            <span>{{ empleado?.telefono || 'No registrado' }}</span>
          </div>
          <div class="info-row" v-if="empleado?.bio">
            <div class="info-icon">
              <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M3 21c3 0 7-1 7-8V5c0-1.25-.756-2.017-2-2H4c-1.25 0-2 .75-2 1.972V11c0 1.25.75 2 2 2 1 0 1 0 1 1v1c0 1-1 2-2 2s-1 .008-1 1.031V20c0 1 0 1 1 1z"></path>
                <path d="M15 21v-8a2 2 0 0 1 2-2h2a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2h-2a2 2 0 0 1-2-2z"></path>
              </svg>
            </div>
            <span>{{ empleado.bio }}</span>
          </div>
          <div class="info-row horario-info">
            <div class="info-icon">
              <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="12" cy="12" r="10"></circle>
                <polyline points="12 6 12 12 16 14"></polyline>
              </svg>
            </div>
            <div class="horario-simple">
              <span class="horario-text">{{ horarioTexto }}</span>
            </div>
          </div>
        </div>
      </div>

      <!-- Estadísticas HOY -->
      <div class="section">
        <div class="resumen-card hoy">
          <div class="card-title">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <circle cx="12" cy="12" r="5"></circle>
              <line x1="12" y1="1" x2="12" y2="3"></line>
              <line x1="12" y1="21" x2="12" y2="23"></line>
              <line x1="4.22" y1="4.22" x2="5.64" y2="5.64"></line>
              <line x1="18.36" y1="18.36" x2="19.78" y2="19.78"></line>
              <line x1="1" y1="12" x2="3" y2="12"></line>
              <line x1="21" y1="12" x2="23" y2="12"></line>
              <line x1="4.22" y1="19.78" x2="5.64" y2="18.36"></line>
              <line x1="18.36" y1="5.64" x2="19.78" y2="4.22"></line>
            </svg>
            <h3>Hoy</h3>
          </div>
          <div class="resumen-row">
            <div class="resumen-item">
              <span class="resumen-value">{{ estadisticas.hoy?.total || 0 }}</span>
              <span class="resumen-label">Citas</span>
            </div>
            <div class="resumen-divider"></div>
            <div class="resumen-item">
              <span class="resumen-value success">{{ estadisticas.hoy?.completadas || 0 }}</span>
              <span class="resumen-label">Completadas</span>
            </div>
            <div class="resumen-divider"></div>
            <div class="resumen-item">
              <span class="resumen-value warning">{{ estadisticas.hoy?.pendientes || 0 }}</span>
              <span class="resumen-label">Pendientes</span>
            </div>
          </div>
          <div class="resumen-ingresos">
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <line x1="12" y1="1" x2="12" y2="23"></line>
              <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path>
            </svg>
            <span>${{ estadisticas.hoy?.ingresos?.toFixed(0) || '0' }}</span>
            <small>ganado hoy</small>
          </div>
        </div>
      </div>

      <!-- Estadísticas SEMANA -->
      <div class="section">
        <div class="resumen-card semana">
          <div class="card-title">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
              <line x1="16" y1="2" x2="16" y2="6"></line>
              <line x1="8" y1="2" x2="8" y2="6"></line>
              <line x1="3" y1="10" x2="21" y2="10"></line>
            </svg>
            <h3>Esta Semana</h3>
          </div>
          <div class="resumen-row">
            <div class="resumen-item">
              <span class="resumen-value">{{ estadisticas.semana?.total || 0 }}</span>
              <span class="resumen-label">Citas</span>
            </div>
            <div class="resumen-divider"></div>
            <div class="resumen-item">
              <span class="resumen-value success">{{ estadisticas.semana?.completadas || 0 }}</span>
              <span class="resumen-label">Completadas</span>
            </div>
            <div class="resumen-divider"></div>
            <div class="resumen-item">
              <span class="resumen-value primary">${{ estadisticas.semana?.ingresos?.toFixed(0) || '0' }}</span>
              <span class="resumen-label">Ingresos</span>
            </div>
          </div>
        </div>
      </div>

      <!-- Estadísticas MES -->
      <div class="section">
        <div class="stats-container">
          <div class="card-title">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
              <line x1="16" y1="2" x2="16" y2="6"></line>
              <line x1="8" y1="2" x2="8" y2="6"></line>
              <line x1="3" y1="10" x2="21" y2="10"></line>
            </svg>
            <h3>Este Mes</h3>
          </div>
          <div class="stats-grid">
            <div class="stat-card">
              <div class="stat-icon blue">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                  <line x1="16" y1="2" x2="16" y2="6"></line>
                  <line x1="8" y1="2" x2="8" y2="6"></line>
                  <line x1="3" y1="10" x2="21" y2="10"></line>
                </svg>
              </div>
              <div class="stat-info">
                <span class="stat-number">{{ estadisticas.mes?.total || 0 }}</span>
                <span class="stat-label">Citas Total</span>
              </div>
            </div>
            <div class="stat-card">
              <div class="stat-icon green">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                  <polyline points="22 4 12 14.01 9 11.01"></polyline>
                </svg>
              </div>
              <div class="stat-info">
                <span class="stat-number">{{ estadisticas.mes?.completadas || 0 }}</span>
                <span class="stat-label">Completadas</span>
              </div>
            </div>
            <div class="stat-card">
              <div class="stat-icon purple">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <line x1="12" y1="1" x2="12" y2="23"></line>
                  <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path>
                </svg>
              </div>
              <div class="stat-info">
                <span class="stat-number">${{ estadisticas.mes?.ingresos?.toFixed(0) || '0' }}</span>
                <span class="stat-label">Ingresos</span>
              </div>
            </div>
            <div class="stat-card">
              <div class="stat-icon orange">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                  <circle cx="8.5" cy="7" r="4"></circle>
                  <line x1="18" y1="8" x2="23" y2="13"></line>
                  <line x1="23" y1="8" x2="18" y2="13"></line>
                </svg>
              </div>
              <div class="stat-info">
                <span class="stat-number">{{ estadisticas.mes?.no_shows || 0 }}</span>
                <span class="stat-label">No Shows</span>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Totales históricos -->
      <div class="section">
        <div class="historial-card">
          <div class="card-title">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <path d="M6 9H4.5a2.5 2.5 0 0 1 0-5H6"></path>
              <path d="M18 9h1.5a2.5 2.5 0 0 0 0-5H18"></path>
              <path d="M4 22h16"></path>
              <path d="M10 14.66V17c0 .55-.47.98-.97 1.21C7.85 18.75 7 20.24 7 22"></path>
              <path d="M14 14.66V17c0 .55.47.98.97 1.21C16.15 18.75 17 20.24 17 22"></path>
              <path d="M18 2H6v7a6 6 0 0 0 12 0V2Z"></path>
            </svg>
            <h3>Mi Historial</h3>
          </div>
          <div class="historial-item">
            <div class="historial-icon">
              <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                <circle cx="9" cy="7" r="4"></circle>
                <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
              </svg>
            </div>
            <div class="historial-info">
              <span class="historial-value">{{ estadisticas.totales?.clientes_atendidos || 0 }}</span>
              <span class="historial-label">Clientes atendidos</span>
            </div>
          </div>
          <div class="historial-item">
            <div class="historial-icon">
              <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <polyline points="20 6 9 17 4 12"></polyline>
              </svg>
            </div>
            <div class="historial-info">
              <span class="historial-value">{{ estadisticas.totales?.citas_completadas || 0 }}</span>
              <span class="historial-label">Citas completadas</span>
            </div>
          </div>
        </div>
      </div>

      <!-- Servicios -->
      <div class="section">
        <div class="servicios-list">
          <div class="card-title">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"></path>
            </svg>
            <h3>Servicios que Ofrezco</h3>
          </div>
          <div v-for="servicio in servicios" :key="servicio.id" class="servicio-item">
            <div class="servicio-icon">
              <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"></path>
              </svg>
            </div>
            <div class="servicio-info">
              <span class="servicio-nombre">{{ servicio.nombre }}</span>
              <span class="servicio-duracion">
                <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <circle cx="12" cy="12" r="10"></circle>
                  <polyline points="12 6 12 12 16 14"></polyline>
                </svg>
                {{ servicio.duracion_minutos }} min
              </span>
            </div>
            <div class="servicio-precio">
              ${{ Number(servicio.precio).toFixed(0) }}
            </div>
          </div>
          <div v-if="servicios.length === 0" class="empty-servicios">
            <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
              <circle cx="12" cy="12" r="10"></circle>
              <line x1="12" y1="8" x2="12" y2="12"></line>
              <line x1="12" y1="16" x2="12.01" y2="16"></line>
            </svg>
            <p>No tienes servicios asignados</p>
          </div>
        </div>
      </div>

      <!-- Botón cerrar sesión -->
      <div class="section">
        <button class="btn-logout" @click="cerrarSesion">
          <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
            <polyline points="16 17 21 12 16 7"></polyline>
            <line x1="21" y1="12" x2="9" y2="12"></line>
          </svg>
          Cerrar Sesión
        </button>
      </div>
    </template>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import api from '@/services/api'

const router = useRouter()
const authStore = useAuthStore()

const loading = ref(true)
const empleado = ref<any>(null)
const servicios = ref<any[]>([])
const estadisticas = ref({
  hoy: { total: 0, completadas: 0, pendientes: 0, ingresos: 0 },
  semana: { total: 0, completadas: 0, ingresos: 0 },
  mes: { total: 0, completadas: 0, ingresos: 0, no_shows: 0 },
  totales: { clientes_atendidos: 0, citas_completadas: 0 },
})

const horario = ref([
  { dia: 1, nombre: 'Lunes', activo: true, entrada: '09:00', salida: '18:00' },
  { dia: 2, nombre: 'Martes', activo: true, entrada: '09:00', salida: '18:00' },
  { dia: 3, nombre: 'Miércoles', activo: true, entrada: '09:00', salida: '18:00' },
  { dia: 4, nombre: 'Jueves', activo: true, entrada: '09:00', salida: '18:00' },
  { dia: 5, nombre: 'Viernes', activo: true, entrada: '09:00', salida: '18:00' },
  { dia: 6, nombre: 'Sábado', activo: true, entrada: '10:00', salida: '14:00' },
  { dia: 0, nombre: 'Domingo', activo: false, entrada: '', salida: '' },
])

const iniciales = computed(() => {
  if (!empleado.value?.nombre) return '?'
  const partes = empleado.value.nombre.split(' ')
  return partes.map((p: string) => p.charAt(0)).slice(0, 2).join('').toUpperCase()
})

const horarioTexto = computed(() => {
  const diasActivos = horario.value.filter(d => d.activo)
  if (diasActivos.length === 0) return 'Sin horario definido'
  
  // Agrupar por horario
  const grupos: Record<string, string[]> = {}
  diasActivos.forEach(dia => {
    const key = `${dia.entrada}-${dia.salida}`
    if (!grupos[key]) grupos[key] = []
    grupos[key].push(dia.nombre.substring(0, 3))
  })
  
  const textos: string[] = []
  Object.keys(grupos).forEach(key => {
    const [entrada, salida] = key.split('-')
    const dias = grupos[key]
    if (dias && dias.length > 0) {
      if (dias.length === 1) {
        textos.push(`${dias[0]}: ${entrada} - ${salida}`)
      } else {
        textos.push(`${dias[0]}-${dias[dias.length - 1]}: ${entrada} - ${salida}`)
      }
    }
  })
  
  return textos.join(', ')
})

async function cargarPerfil() {
  loading.value = true
  try {
    // Cargar perfil del empleado
    const perfilRes = await api.get('/empleado/perfil')
    empleado.value = perfilRes.data.data?.empleado || perfilRes.data.data || null
    
    // Cargar servicios del empleado
    if (empleado.value?.servicios) {
      servicios.value = empleado.value.servicios
    }

    // Cargar horarios si vienen
    if (empleado.value?.horarios && empleado.value.horarios.length > 0) {
      empleado.value.horarios.forEach((h: any) => {
        const diaIndex = horario.value.findIndex(d => d.dia === h.dia_semana)
        if (diaIndex !== -1 && horario.value[diaIndex]) {
          horario.value[diaIndex].activo = h.activo
          horario.value[diaIndex].entrada = h.hora_inicio?.substring(0, 5) || '09:00'
          horario.value[diaIndex].salida = h.hora_fin?.substring(0, 5) || '18:00'
        }
      })
    }

    // Cargar estadísticas
    await cargarEstadisticas()
  } catch (error) {
    console.error('Error cargando perfil:', error)
  } finally {
    loading.value = false
  }
}

async function cargarEstadisticas() {
  try {
    const res = await api.get('/empleado/estadisticas')
    if (res.data.data) {
      estadisticas.value = res.data.data
    }
  } catch (error) {
    console.error('Error cargando estadísticas:', error)
  }
}

async function cerrarSesion() {
  await authStore.logout()
  router.push('/login')
}

onMounted(() => {
  cargarPerfil()
})
</script>

<style scoped>
/* ===== Apple-inspired Perfil Empleado View Design ===== */

.perfil-view {
  min-height: 100vh;
  background: #f5f5f7;
  padding-bottom: 100px;
  font-family: -apple-system, BlinkMacSystemFont, 'SF Pro Display', 'SF Pro Text', 'Helvetica Neue', sans-serif;
  -webkit-font-smoothing: antialiased;
  -moz-osx-font-smoothing: grayscale;
}

/* Header Section */
.perfil-header-section {
  background: #f5f5f7;
  padding: 24px 20px 20px;
  margin-bottom: 0;
}

.profile-header {
  background: linear-gradient(135deg, #1d1d1f 0%, #3a3a3c 100%);
  border-radius: 20px;
  padding: 20px;
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
  border: 1px solid rgba(255, 255, 255, 0.1);
  position: relative;
  z-index: 1;
}

.header-top {
  display: flex;
  align-items: flex-start;
  gap: 16px;
}

.profile-avatar {
  width: 80px;
  height: 80px;
  border-radius: 50%;
  background: linear-gradient(135deg, #007aff 0%, #5856d6 100%);
  display: flex;
  align-items: center;
  justify-content: center;
  overflow: hidden;
  border: 4px solid rgba(255, 255, 255, 0.2);
  flex-shrink: 0;
  box-shadow: 0 4px 12px rgba(0, 122, 255, 0.3);
}

.profile-avatar img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.avatar-letter {
  font-size: 32px;
  font-weight: 700;
  color: white;
  letter-spacing: -1px;
}

.header-info {
  flex: 1;
  min-width: 0;
}

.header-info h1 {
  font-size: 22px;
  font-weight: 600;
  color: white;
  margin: 0 0 8px;
  letter-spacing: -0.3px;
}

.profile-bio {
  font-size: 14px;
  color: rgba(255, 255, 255, 0.7);
  margin: 0 0 12px;
  line-height: 1.5;
}

.profile-meta {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
}

.meta-tag {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  font-size: 12px;
  color: rgba(255, 255, 255, 0.8);
  padding: 6px 12px;
  background: rgba(255, 255, 255, 0.1);
  border-radius: 12px;
  border: 1px solid rgba(255, 255, 255, 0.2);
  font-weight: 500;
}

.meta-tag svg {
  flex-shrink: 0;
  color: white;
}

.meta-tag.rating {
  color: #ff9500;
}

.meta-tag.rating svg {
  color: #ff9500;
}

/* Loading */
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

/* Sections */
.section {
  padding: 0 20px;
  margin-bottom: 16px;
}

.section:first-of-type {
  margin-top: 0;
}

/* Card Title */
.card-title {
  display: flex;
  align-items: center;
  gap: 10px;
  margin: 0 0 16px;
  padding-bottom: 12px;
  border-bottom: 1px solid #e5e5ea;
}

.card-title svg {
  color: #007aff;
  flex-shrink: 0;
}

.card-title h3 {
  margin: 0;
  font-size: 17px;
  font-weight: 600;
  color: #1d1d1f;
  letter-spacing: -0.3px;
}

/* Info Card */
.info-card {
  background: #ffffff;
  border-radius: 20px;
  padding: 20px;
  box-shadow: 0 2px 12px rgba(0, 0, 0, 0.08);
  border: 1px solid #e5e5ea;
}

.info-row {
  display: flex;
  align-items: flex-start;
  gap: 12px;
  padding: 14px 0;
  border-bottom: 1px solid #e5e5ea;
}

.info-row:first-of-type {
  padding-top: 0;
}

.info-row:last-child {
  border-bottom: none;
  padding-bottom: 0;
}

.info-icon {
  width: 20px;
  height: 20px;
  color: #007aff;
  flex-shrink: 0;
  margin-top: 2px;
}

.info-row span {
  flex: 1;
  font-size: 15px;
  color: #1d1d1f;
  line-height: 1.5;
}

.info-row.horario-info {
  align-items: flex-start;
}

.horario-simple {
  flex: 1;
}

.horario-text {
  font-size: 14px;
  color: #1d1d1f;
  line-height: 1.5;
}

/* Resumen Cards */
.resumen-card {
  background: #ffffff;
  border-radius: 20px;
  padding: 20px;
  box-shadow: 0 2px 12px rgba(0, 0, 0, 0.08);
  border: 1px solid #e5e5ea;
}

.resumen-card.hoy {
  border-left: 4px solid #ff9500;
}

.resumen-card.semana {
  border-left: 4px solid #007aff;
}

.resumen-row {
  display: flex;
  justify-content: space-around;
  align-items: center;
  margin-bottom: 16px;
}

.resumen-item {
  text-align: center;
  flex: 1;
}

.resumen-divider {
  width: 1px;
  height: 40px;
  background: #e5e5ea;
}

.resumen-value {
  display: block;
  font-size: 28px;
  font-weight: 700;
  color: #1d1d1f;
  line-height: 1;
  letter-spacing: -0.5px;
}

.resumen-value.success { color: #34c759; }
.resumen-value.warning { color: #ff9500; }
.resumen-value.primary { color: #007aff; }

.resumen-label {
  font-size: 11px;
  color: #86868b;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  margin-top: 6px;
}

.resumen-ingresos {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  padding-top: 16px;
  border-top: 1px solid #e5e5ea;
}

.resumen-ingresos svg {
  color: #34c759;
  flex-shrink: 0;
}

.resumen-ingresos span {
  font-size: 24px;
  font-weight: 700;
  color: #34c759;
  letter-spacing: -0.5px;
}

.resumen-ingresos small {
  font-size: 13px;
  color: #86868b;
  margin-left: 4px;
}

/* Stats Container */
.stats-container {
  background: #ffffff;
  border-radius: 20px;
  padding: 20px;
  box-shadow: 0 2px 12px rgba(0, 0, 0, 0.08);
  border: 1px solid #e5e5ea;
}

/* Stats Grid */
.stats-grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 12px;
}

.stat-card {
  background: #f5f5f7;
  border-radius: 14px;
  padding: 16px;
  display: flex;
  align-items: center;
  gap: 12px;
  border: 1px solid #e5e5ea;
  transition: all 0.2s;
}

.stat-card:active {
  transform: scale(0.98);
  background: #e5e5ea;
}

.stat-icon {
  width: 44px;
  height: 44px;
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
  flex-shrink: 0;
  box-shadow: 0 2px 6px rgba(0, 0, 0, 0.15);
}

.stat-icon.blue { background: linear-gradient(135deg, #007aff 0%, #5ac8fa 100%); }
.stat-icon.green { background: linear-gradient(135deg, #34c759 0%, #30d158 100%); }
.stat-icon.purple { background: linear-gradient(135deg, #5856d6 0%, #af52de 100%); }
.stat-icon.orange { background: linear-gradient(135deg, #ff9500 0%, #ffad33 100%); }

.stat-info {
  flex: 1;
  min-width: 0;
}

.stat-number {
  display: block;
  font-size: 20px;
  font-weight: 700;
  color: #1d1d1f;
  line-height: 1.2;
  letter-spacing: -0.3px;
}

.stat-label {
  font-size: 11px;
  color: #86868b;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  margin-top: 2px;
}

/* Historial Card */
.historial-card {
  background: #ffffff;
  border-radius: 20px;
  padding: 20px;
  box-shadow: 0 2px 12px rgba(0, 0, 0, 0.08);
  border: 1px solid #e5e5ea;
}

.historial-item {
  display: flex;
  align-items: center;
  gap: 14px;
  padding: 16px 0;
  border-bottom: 1px solid #e5e5ea;
}

.historial-item:first-of-type {
  padding-top: 0;
}

.historial-item:last-child {
  border-bottom: none;
  padding-bottom: 0;
}

.historial-icon {
  width: 52px;
  height: 52px;
  border-radius: 14px;
  background: linear-gradient(135deg, #007aff 0%, #5856d6 100%);
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
  flex-shrink: 0;
  box-shadow: 0 4px 12px rgba(0, 122, 255, 0.3);
}

.historial-info {
  flex: 1;
  min-width: 0;
}

.historial-value {
  display: block;
  font-size: 24px;
  font-weight: 700;
  color: #1d1d1f;
  letter-spacing: -0.4px;
  line-height: 1.2;
}

.historial-label {
  font-size: 13px;
  color: #86868b;
  margin-top: 2px;
}

/* Servicios List */
.servicios-list {
  background: #ffffff;
  border-radius: 20px;
  padding: 20px;
  box-shadow: 0 2px 12px rgba(0, 0, 0, 0.08);
  border: 1px solid #e5e5ea;
}

.servicio-item {
  display: flex;
  align-items: center;
  gap: 14px;
  padding: 14px 0;
  border-bottom: 1px solid #e5e5ea;
}

.servicio-item:first-of-type {
  padding-top: 0;
}

.servicio-item:last-child {
  border-bottom: none;
  padding-bottom: 0;
}

.servicio-icon {
  width: 44px;
  height: 44px;
  border-radius: 12px;
  background: linear-gradient(135deg, rgba(0, 122, 255, 0.1) 0%, rgba(88, 86, 214, 0.1) 100%);
  display: flex;
  align-items: center;
  justify-content: center;
  color: #007aff;
  flex-shrink: 0;
  border: 1px solid rgba(0, 122, 255, 0.2);
}

.servicio-info {
  flex: 1;
  min-width: 0;
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.servicio-nombre {
  display: block;
  font-size: 15px;
  font-weight: 600;
  color: #1d1d1f;
  letter-spacing: -0.2px;
}

.servicio-duracion {
  display: flex;
  align-items: center;
  gap: 4px;
  font-size: 12px;
  color: #86868b;
}

.servicio-duracion svg {
  flex-shrink: 0;
}

.servicio-precio {
  font-size: 17px;
  font-weight: 700;
  color: #007aff;
  flex-shrink: 0;
  letter-spacing: -0.3px;
}

.empty-servicios {
  padding: 40px 20px;
  text-align: center;
  color: #86868b;
}

.empty-servicios svg {
  margin-bottom: 12px;
  opacity: 0.5;
}

.empty-servicios p {
  margin: 0;
  font-size: 14px;
}

/* Logout Button */
.btn-logout {
  width: 100%;
  padding: 16px 20px;
  background: #ffebee;
  border: 1px solid #ffcdd2;
  border-radius: 14px;
  color: #ff3b30;
  font-size: 15px;
  font-weight: 600;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 10px;
  transition: all 0.2s;
}

.btn-logout:active {
  background: #ff3b30;
  color: white;
  transform: scale(0.98);
}

.btn-logout svg {
  flex-shrink: 0;
}

/* Responsive */
@media (max-width: 640px) {
  .perfil-header-section {
    padding: 20px 16px 28px;
  }
  
  .profile-header {
    padding: 16px;
    border-radius: 16px;
  }
  
  .profile-avatar {
    width: 70px;
    height: 70px;
    border-width: 3px;
  }
  
  .avatar-letter {
    font-size: 28px;
  }
  
  .header-info h1 {
    font-size: 20px;
  }
  
  .section {
    padding: 0 16px;
  }
  
  .info-card,
  .resumen-card,
  .stats-container,
  .historial-card,
  .servicios-list {
    padding: 16px;
    border-radius: 16px;
  }
  
  .card-title h3 {
    font-size: 16px;
  }
  
  .resumen-value {
    font-size: 24px;
  }
  
  .resumen-ingresos span {
    font-size: 20px;
  }
  
  .stats-grid {
    gap: 10px;
  }
  
  .stat-card {
    padding: 14px;
  }
  
  .stat-icon {
    width: 40px;
    height: 40px;
  }
  
  .stat-number {
    font-size: 18px;
  }
  
  .historial-icon {
    width: 48px;
    height: 48px;
  }
  
  .historial-value {
    font-size: 22px;
  }
  
  .servicio-icon {
    width: 40px;
    height: 40px;
  }
}
</style>
