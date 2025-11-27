<template>
  <div class="perfil-view">
    <!-- Header -->
    <div class="profile-header">
      <div class="profile-avatar">
        <img v-if="empleado?.foto" :src="empleado.foto" alt="Foto" />
        <span v-else class="avatar-letter">{{ iniciales }}</span>
      </div>
      <div class="profile-info">
        <h1>{{ empleado?.nombre || 'Cargando...' }}</h1>
        <p class="profile-role">
          <i class="fa fa-briefcase"></i>
          {{ empleado?.especialidades || 'Profesional' }}
        </p>
        <div class="profile-rating" v-if="empleado?.promedio_calificacion">
          <div class="stars">
            <i v-for="i in 5" :key="i" class="fa fa-star" :class="{ active: i <= Math.round(empleado.promedio_calificacion) }"></i>
          </div>
          <span>{{ empleado.promedio_calificacion.toFixed(1) }}</span>
        </div>
      </div>
    </div>

    <!-- Loading -->
    <div v-if="loading" class="loading-state">
      <div class="spinner"></div>
      <p>Cargando información...</p>
    </div>

    <template v-else>
      <!-- Información del Empleado -->
      <div class="section">
        <div class="info-card">
          <h3 class="card-title">
            <i class="fa fa-user"></i>
            Mi Información
          </h3>
          <div class="info-row">
            <i class="fa fa-envelope"></i>
            <span>{{ empleado?.email || 'No registrado' }}</span>
          </div>
          <div class="info-row">
            <i class="fa fa-phone"></i>
            <span>{{ empleado?.telefono || 'No registrado' }}</span>
          </div>
          <div class="info-row" v-if="empleado?.bio">
            <i class="fa fa-quote-left"></i>
            <span>{{ empleado.bio }}</span>
          </div>
          <div class="info-row horario-info">
            <i class="fa fa-clock"></i>
            <div class="horario-simple">
              <span class="horario-text">{{ horarioTexto }}</span>
            </div>
          </div>
        </div>
      </div>

      <!-- Estadísticas HOY -->
      <div class="section">
        <div class="resumen-card hoy">
          <h3 class="card-title">
            <i class="fa fa-sun"></i>
            Hoy
          </h3>
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
            <i class="fa fa-dollar-sign"></i>
            <span>${{ estadisticas.hoy?.ingresos?.toFixed(0) || '0' }}</span>
            <small>ganado hoy</small>
          </div>
        </div>
      </div>

      <!-- Estadísticas SEMANA -->
      <div class="section">
        <div class="resumen-card semana">
          <h3 class="card-title">
            <i class="fa fa-calendar-week"></i>
            Esta Semana
          </h3>
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
          <h3 class="card-title">
            <i class="fa fa-calendar-alt"></i>
            Este Mes
          </h3>
          <div class="stats-grid">
          <div class="stat-card">
            <div class="stat-icon blue">
              <i class="fa fa-calendar"></i>
            </div>
            <div class="stat-info">
              <span class="stat-number">{{ estadisticas.mes?.total || 0 }}</span>
              <span class="stat-label">Citas Total</span>
            </div>
          </div>
          <div class="stat-card">
            <div class="stat-icon green">
              <i class="fa fa-check-circle"></i>
            </div>
            <div class="stat-info">
              <span class="stat-number">{{ estadisticas.mes?.completadas || 0 }}</span>
              <span class="stat-label">Completadas</span>
            </div>
          </div>
          <div class="stat-card">
            <div class="stat-icon purple">
              <i class="fa fa-dollar-sign"></i>
            </div>
            <div class="stat-info">
              <span class="stat-number">${{ estadisticas.mes?.ingresos?.toFixed(0) || '0' }}</span>
              <span class="stat-label">Ingresos</span>
            </div>
          </div>
          <div class="stat-card">
            <div class="stat-icon orange">
              <i class="fa fa-user-slash"></i>
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
          <h3 class="card-title">
            <i class="fa fa-trophy"></i>
            Mi Historial
          </h3>
          <div class="historial-item">
            <div class="historial-icon">
              <i class="fa fa-users"></i>
            </div>
            <div class="historial-info">
              <span class="historial-value">{{ estadisticas.totales?.clientes_atendidos || 0 }}</span>
              <span class="historial-label">Clientes atendidos</span>
            </div>
          </div>
          <div class="historial-item">
            <div class="historial-icon">
              <i class="fa fa-check-double"></i>
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
          <h3 class="card-title">
            <i class="fa fa-spa"></i>
            Servicios que Ofrezco
          </h3>
          <div v-for="servicio in servicios" :key="servicio.id" class="servicio-item">
            <div class="servicio-icon">
              <i class="fa fa-cut"></i>
            </div>
            <div class="servicio-info">
              <span class="servicio-nombre">{{ servicio.nombre }}</span>
              <span class="servicio-duracion">
                <i class="fa fa-clock"></i> {{ servicio.duracion_minutos }} min
              </span>
            </div>
            <div class="servicio-precio">
              ${{ Number(servicio.precio).toFixed(0) }}
            </div>
          </div>
          <div v-if="servicios.length === 0" class="empty-servicios">
            <i class="fa fa-info-circle"></i>
            <p>No tienes servicios asignados</p>
          </div>
        </div>
      </div>

      <!-- Botón cerrar sesión -->
      <div class="section">
        <button class="btn-logout" @click="cerrarSesion">
          <i class="fa fa-sign-out-alt"></i>
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
.perfil-view {
  min-height: 100%;
  background: var(--color-background);
  padding-bottom: 100px;
}

/* ===== PROFILE HEADER ===== */
.profile-header {
  background: linear-gradient(135deg, #ec407a, #c2185b);
  padding: 30px 20px;
  display: flex;
  align-items: center;
  gap: 16px;
}

.profile-avatar {
  width: 80px;
  height: 80px;
  border-radius: 50%;
  background: rgba(255,255,255,0.2);
  display: flex;
  align-items: center;
  justify-content: center;
  overflow: hidden;
  border: 3px solid rgba(255,255,255,0.3);
}

.profile-avatar img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.avatar-letter {
  font-size: 28px;
  font-weight: 700;
  color: white;
}

.profile-info h1 {
  color: white;
  font-size: 22px;
  font-weight: 700;
  margin: 0 0 4px;
}

.profile-role {
  color: rgba(255,255,255,0.9);
  font-size: 13px;
  margin: 0 0 8px;
  display: flex;
  align-items: center;
  gap: 6px;
}

.profile-rating {
  display: flex;
  align-items: center;
  gap: 8px;
}

.stars {
  display: flex;
  gap: 2px;
}

.stars i {
  font-size: 12px;
  color: rgba(255,255,255,0.4);
}

.stars i.active {
  color: #ffc107;
}

.profile-rating span {
  color: white;
  font-size: 14px;
  font-weight: 600;
}

/* ===== LOADING ===== */
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

/* ===== SECTIONS ===== */
.section {
  padding: 0 16px;
  margin-bottom: 16px;
}

.card-title {
  display: flex;
  align-items: center;
  gap: 10px;
  font-size: 15px;
  font-weight: 700;
  color: var(--color-text);
  margin: 0 0 14px;
  padding-bottom: 12px;
  border-bottom: 1px solid var(--color-border);
}

.card-title i {
  color: #ec407a;
  font-size: 15px;
}

/* ===== RESUMEN CARDS ===== */
.resumen-card {
  background: var(--color-card);
  border-radius: 16px;
  padding: 16px;
  box-shadow: 0 2px 8px rgba(0,0,0,0.05);
}

.resumen-card .card-title {
  margin-bottom: 12px;
}

.resumen-card.hoy {
  border-left: 4px solid #ff9800;
}

.resumen-card.semana {
  border-left: 4px solid #2196f3;
}

.resumen-row {
  display: flex;
  justify-content: space-around;
  align-items: center;
}

.resumen-item {
  text-align: center;
  flex: 1;
}

.resumen-divider {
  width: 1px;
  height: 40px;
  background: var(--color-border);
}

.resumen-value {
  display: block;
  font-size: 28px;
  font-weight: 800;
  color: var(--color-text);
  line-height: 1;
}

.resumen-value.success { color: #4caf50; }
.resumen-value.warning { color: #ff9800; }
.resumen-value.primary { color: #ec407a; }

.resumen-label {
  font-size: 11px;
  color: var(--color-text-secondary);
  text-transform: uppercase;
  letter-spacing: 0.3px;
  margin-top: 4px;
}

.resumen-ingresos {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  margin-top: 16px;
  padding-top: 16px;
  border-top: 1px solid var(--color-border);
}

.resumen-ingresos i {
  color: #4caf50;
  font-size: 16px;
}

.resumen-ingresos span {
  font-size: 22px;
  font-weight: 800;
  color: #4caf50;
}

.resumen-ingresos small {
  font-size: 12px;
  color: var(--color-text-secondary);
}

/* ===== STATS CONTAINER ===== */
.stats-container {
  background: var(--color-card);
  border-radius: 16px;
  padding: 16px;
  box-shadow: 0 2px 8px rgba(0,0,0,0.05);
}

.stats-container .card-title {
  margin-bottom: 12px;
}

/* ===== STATS GRID ===== */
.stats-grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 10px;
}

.stat-card {
  background: var(--color-card);
  border-radius: 12px;
  padding: 14px;
  display: flex;
  align-items: center;
  gap: 10px;
  box-shadow: 0 2px 8px rgba(0,0,0,0.05);
}

.stat-icon {
  width: 40px;
  height: 40px;
  border-radius: 10px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 16px;
}

.stat-icon.blue { background: rgba(33, 150, 243, 0.15); color: #2196f3; }
.stat-icon.green { background: rgba(76, 175, 80, 0.15); color: #4caf50; }
.stat-icon.purple { background: rgba(156, 39, 176, 0.15); color: #9c27b0; }
.stat-icon.orange { background: rgba(255, 152, 0, 0.15); color: #ff9800; }

.stat-info {
  flex: 1;
}

.stat-number {
  display: block;
  font-size: 20px;
  font-weight: 800;
  color: var(--color-text);
  line-height: 1;
}

.stat-label {
  font-size: 10px;
  color: var(--color-text-secondary);
  text-transform: uppercase;
  letter-spacing: 0.3px;
}

/* ===== HISTORIAL ===== */
.historial-card {
  background: var(--color-card);
  border-radius: 14px;
  padding: 16px;
  box-shadow: 0 2px 8px rgba(0,0,0,0.05);
}

.historial-card .card-title {
  margin-bottom: 12px;
}

.historial-item {
  display: flex;
  align-items: center;
  gap: 14px;
  padding: 12px 0;
  border-bottom: 1px solid var(--color-border);
}

.historial-item:first-of-type {
  padding-top: 0;
}

.historial-item:last-child {
  border-bottom: none;
}

.historial-icon {
  width: 48px;
  height: 48px;
  border-radius: 12px;
  background: linear-gradient(135deg, #ec407a, #c2185b);
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
  font-size: 18px;
}

.historial-info {
  flex: 1;
}

.historial-value {
  display: block;
  font-size: 24px;
  font-weight: 800;
  color: var(--color-text);
}

.historial-label {
  font-size: 12px;
  color: var(--color-text-secondary);
}

/* ===== HORARIO SIMPLE ===== */
.info-row.horario-info {
  align-items: flex-start;
}

.horario-simple {
  flex: 1;
}

.horario-text {
  font-size: 14px;
  color: var(--color-text);
  line-height: 1.4;
}

/* ===== SERVICIOS ===== */
.servicios-list {
  background: var(--color-card);
  border-radius: 14px;
  padding: 16px;
  box-shadow: 0 2px 8px rgba(0,0,0,0.05);
}

.servicios-list .card-title {
  margin-bottom: 12px;
}

.servicio-item {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 12px 0;
  border-bottom: 1px solid var(--color-border);
}

.servicio-item:first-of-type {
  padding-top: 0;
}

.servicio-item:last-child {
  border-bottom: none;
}

.servicio-icon {
  width: 40px;
  height: 40px;
  border-radius: 10px;
  background: linear-gradient(135deg, rgba(236,64,122,0.1), rgba(236,64,122,0.2));
  display: flex;
  align-items: center;
  justify-content: center;
  color: #ec407a;
  font-size: 16px;
}

.servicio-info {
  flex: 1;
}

.servicio-nombre {
  display: block;
  font-size: 14px;
  font-weight: 600;
  color: var(--color-text);
}

.servicio-duracion {
  font-size: 11px;
  color: var(--color-text-secondary);
}

.servicio-duracion i {
  margin-right: 4px;
}

.servicio-precio {
  font-size: 16px;
  font-weight: 700;
  color: #ec407a;
}

.empty-servicios {
  padding: 30px;
  text-align: center;
  color: var(--color-text-secondary);
}

.empty-servicios i {
  font-size: 30px;
  margin-bottom: 10px;
  opacity: 0.5;
}

.empty-servicios p {
  margin: 0;
  font-size: 13px;
}

/* ===== INFO CARD ===== */
.info-card {
  background: var(--color-card);
  border-radius: 14px;
  padding: 16px;
  box-shadow: 0 2px 8px rgba(0,0,0,0.05);
}

.info-card .card-title {
  margin-bottom: 12px;
}

.info-row {
  display: flex;
  align-items: flex-start;
  gap: 14px;
  padding: 12px 0;
  border-bottom: 1px solid var(--color-border);
}

.info-row:first-of-type {
  padding-top: 0;
}

.info-row:last-child {
  border-bottom: none;
}

.info-row i {
  width: 20px;
  color: #ec407a;
  font-size: 14px;
  margin-top: 2px;
}

.info-row span {
  flex: 1;
  font-size: 14px;
  color: var(--color-text);
}

/* ===== LOGOUT BTN ===== */
.btn-logout {
  width: 100%;
  padding: 16px;
  background: rgba(239, 68, 68, 0.1);
  border: none;
  border-radius: 14px;
  color: #ef4444;
  font-size: 15px;
  font-weight: 600;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 10px;
  transition: all 0.2s;
}

.btn-logout:hover {
  background: #ef4444;
  color: white;
}
</style>

