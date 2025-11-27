<template>
  <div class="admin-view">
    <!-- Header -->
    <div class="view-header">
      <div class="header-info">
        <div class="header-icon">
          <i class="fa fa-chart-line"></i>
        </div>
        <div class="header-text">
          <h1>Dashboard</h1>
          <p class="header-subtitle">{{ fechaHoy }}</p>
        </div>
      </div>
      <div class="header-user">
        <span class="user-greeting">Hola, {{ userName }}</span>
        <span class="user-role">Administrador</span>
      </div>
    </div>

    <!-- Stats Cards -->
    <div class="stats-row">
      <div class="stat-card stat-primary">
        <div class="stat-icon">
          <i class="fa fa-calendar-check"></i>
        </div>
        <div class="stat-content">
          <span class="stat-value">{{ stats.citasHoy }}</span>
          <span class="stat-label">Citas Hoy</span>
        </div>
      </div>

      <div class="stat-card stat-success">
        <div class="stat-icon">
          <i class="fa fa-dollar-sign"></i>
        </div>
        <div class="stat-content">
          <span class="stat-value">${{ formatMonto(stats.ingresosMes) }}</span>
          <span class="stat-label">Ingresos Mes</span>
        </div>
      </div>

      <div class="stat-card stat-warning">
        <div class="stat-icon">
          <i class="fa fa-users"></i>
        </div>
        <div class="stat-content">
          <span class="stat-value">{{ stats.totalClientes }}</span>
          <span class="stat-label">Clientes</span>
        </div>
      </div>

      <div class="stat-card stat-info">
        <div class="stat-icon">
          <i class="fa fa-hourglass-half"></i>
        </div>
        <div class="stat-content">
          <span class="stat-value">{{ stats.citasPendientes }}</span>
          <span class="stat-label">Pendientes</span>
        </div>
      </div>
    </div>

    <!-- Main Content Grid -->
    <div class="content-grid">
      <!-- Citas del día -->
      <div class="panel-card">
        <div class="panel-header">
          <h3><i class="fa fa-clock"></i> Citas de Hoy</h3>
          <router-link to="/admin/citas" class="panel-link">
            Ver todas <i class="fa fa-arrow-right"></i>
          </router-link>
        </div>
        <div class="panel-body">
          <div v-if="loading" class="loading-state">
            <div class="spinner"></div>
          </div>
          <div v-else-if="citasHoy.length === 0" class="empty-state">
            <i class="fa fa-calendar-times"></i>
            <p>No hay citas programadas</p>
          </div>
          <div v-else class="citas-list">
            <div v-for="cita in citasHoy" :key="cita.id" class="cita-item">
              <div class="cita-time">
                {{ formatHora(cita.fecha_hora) }}
              </div>
              <div class="cita-details">
                <span class="cita-cliente">{{ cita.cliente?.nombre || 'Sin cliente' }}</span>
                <span class="cita-servicio">{{ getServiciosNombres(cita) }}</span>
              </div>
              <span :class="['status-badge', cita.estado]">
                {{ estadoLabel(cita.estado) }}
              </span>
            </div>
          </div>
        </div>
      </div>

      <!-- Empleados activos -->
      <div class="panel-card">
        <div class="panel-header">
          <h3><i class="fa fa-users"></i> Empleados</h3>
        </div>
        <div class="panel-body">
          <div v-if="loading" class="loading-state">
            <div class="spinner"></div>
          </div>
          <div v-else class="empleados-list">
            <div v-for="empleado in empleadosOcupados" :key="empleado.nombre" class="empleado-item">
              <div class="empleado-avatar">
                {{ empleado.nombre?.charAt(0) || '?' }}
              </div>
              <div class="empleado-info">
                <span class="empleado-nombre">{{ empleado.nombre }}</span>
                <span class="empleado-citas">{{ empleado.citas }} citas</span>
              </div>
            </div>
            <div v-if="empleadosOcupados.length === 0" class="empty-state small">
              <p>Sin datos</p>
            </div>
          </div>
        </div>
      </div>

      <!-- Servicios populares -->
      <div class="panel-card">
        <div class="panel-header">
          <h3><i class="fa fa-fire"></i> Servicios Populares</h3>
          <span class="panel-badge">Este mes</span>
        </div>
        <div class="panel-body">
          <div v-if="loading" class="loading-state">
            <div class="spinner"></div>
          </div>
          <div v-else class="servicios-chart">
            <div v-for="(servicio, index) in serviciosPopulares" :key="index" class="servicio-row">
              <div class="servicio-rank">#{{ index + 1 }}</div>
              <div class="servicio-info">
                <span class="servicio-nombre">{{ servicio.nombre }}</span>
                <div class="progress-bar">
                  <div class="progress-fill" :style="{ width: getServicioPercent(servicio) + '%' }"></div>
                </div>
              </div>
              <span class="servicio-count">{{ servicio.cantidad }}</span>
            </div>
            <div v-if="serviciosPopulares.length === 0" class="empty-state small">
              <p>Sin datos</p>
            </div>
          </div>
        </div>
      </div>

      <!-- Estadísticas generales -->
      <div class="panel-card">
        <div class="panel-header">
          <h3><i class="fa fa-chart-bar"></i> Estadísticas</h3>
        </div>
        <div class="panel-body">
          <div class="stats-mini-grid">
            <div class="stat-mini-item">
              <div class="stat-mini-icon blue">
                <i class="fa fa-user-tie"></i>
              </div>
              <div class="stat-mini-info">
                <span class="stat-mini-value">{{ stats.totalEmpleados }}</span>
                <span class="stat-mini-label">Empleados</span>
              </div>
            </div>
            <div class="stat-mini-item">
              <div class="stat-mini-icon green">
                <i class="fa fa-cut"></i>
              </div>
              <div class="stat-mini-info">
                <span class="stat-mini-value">{{ stats.totalServicios }}</span>
                <span class="stat-mini-label">Servicios</span>
              </div>
            </div>
            <div class="stat-mini-item">
              <div class="stat-mini-icon orange">
                <i class="fa fa-exclamation-circle"></i>
              </div>
              <div class="stat-mini-info">
                <span class="stat-mini-value">{{ stats.tasaNoShow }}%</span>
                <span class="stat-mini-label">No Show</span>
              </div>
            </div>
            <div class="stat-mini-item">
              <div class="stat-mini-icon purple">
                <i class="fa fa-dollar-sign"></i>
              </div>
              <div class="stat-mini-info">
                <span class="stat-mini-value">${{ formatMonto(stats.ingresosHoy) }}</span>
                <span class="stat-mini-label">Hoy</span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue';
import { useAuthStore } from '@/stores/auth';
import { getDashboard, getCitas } from '@/services/adminService';

const authStore = useAuthStore();

const loading = ref(true);
const stats = ref({
  citasHoy: 0,
  citasPendientes: 0,
  ingresosHoy: 0,
  ingresosMes: 0,
  totalClientes: 0,
  totalEmpleados: 0,
  totalServicios: 0,
  tasaNoShow: 0,
});

const citasHoy = ref<any[]>([]);
const empleadosOcupados = ref<any[]>([]);
const serviciosPopulares = ref<any[]>([]);

const userName = computed(() => authStore.user?.nombre || 'Admin');

const fechaHoy = computed(() => {
  return new Date().toLocaleDateString('es-MX', {
    weekday: 'long',
    day: 'numeric',
    month: 'long',
  });
});

async function cargarDashboard() {
  loading.value = true;
  try {
    // Cargar datos del dashboard
    const dashboardRes = await getDashboard();
    if (dashboardRes.success) {
      const data = dashboardRes.data;
      stats.value = {
        citasHoy: data.citas_hoy || 0,
        citasPendientes: data.citas_pendientes || 0,
        ingresosHoy: data.ingresos_hoy || 0,
        ingresosMes: data.ingresos_mes || 0,
        totalClientes: data.estadisticas?.total_clientes || 0,
        totalEmpleados: data.estadisticas?.total_empleados || 0,
        totalServicios: data.estadisticas?.total_servicios || 0,
        tasaNoShow: data.estadisticas?.tasa_no_show || 0,
      };
      
      empleadosOcupados.value = data.empleados_ocupados || [];
      serviciosPopulares.value = data.servicios_populares || [];
    }

    // Cargar citas del día
    const hoy = new Date().toISOString().split('T')[0];
    const citasRes = await getCitas({ 
      desde: hoy + ' 00:00:00', 
      hasta: hoy + ' 23:59:59',
      per_page: 10 
    });
    if (citasRes.success) {
      citasHoy.value = citasRes.data || [];
    }
  } catch (error) {
    console.error('Error cargando dashboard:', error);
  } finally {
    loading.value = false;
  }
}

function formatHora(fecha: string): string {
  if (!fecha) return '--:--';
  const date = new Date(fecha.replace(' ', 'T'));
  return date.toLocaleTimeString('es-MX', { hour: '2-digit', minute: '2-digit' });
}

function formatMonto(monto: number): string {
  return (monto || 0).toLocaleString('es-MX');
}

function estadoLabel(estado: string): string {
  const labels: Record<string, string> = {
    pendiente: 'Pendiente',
    confirmada: 'Confirmada',
    en_proceso: 'En proceso',
    completada: 'Completada',
    cancelada: 'Cancelada',
    no_show: 'No Show',
  };
  return labels[estado] || estado;
}

function getServiciosNombres(cita: any): string {
  if (cita.servicios && cita.servicios.length > 0) {
    return cita.servicios.map((s: any) => s.nombre || s.servicio?.nombre).filter(Boolean).join(', ');
  }
  if (cita.servicio) {
    return cita.servicio.nombre;
  }
  return 'Sin servicio';
}

function getServicioPercent(servicio: any): number {
  if (serviciosPopulares.value.length === 0) return 0;
  const max = Math.max(...serviciosPopulares.value.map(s => s.cantidad || 0));
  return max > 0 ? ((servicio.cantidad || 0) / max) * 100 : 0;
}

onMounted(() => {
  cargarDashboard();
});
</script>

<style scoped>
.admin-view {
  padding: 16px;
  padding-bottom: 100px;
}

/* Header */
.view-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 20px;
  padding: 16px;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  border-radius: 16px;
  color: white;
}

.header-info {
  display: flex;
  align-items: center;
  gap: 12px;
}

.header-icon {
  width: 44px;
  height: 44px;
  background: rgba(255, 255, 255, 0.2);
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 20px;
}

.header-text h1 {
  margin: 0;
  font-size: 20px;
  font-weight: 700;
}

.header-subtitle {
  margin: 2px 0 0;
  font-size: 12px;
  opacity: 0.9;
  text-transform: capitalize;
}

.header-user {
  text-align: right;
}

.user-greeting {
  display: block;
  font-size: 14px;
  font-weight: 600;
}

.user-role {
  font-size: 11px;
  opacity: 0.8;
}

/* Stats Row */
.stats-row {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 12px;
  margin-bottom: 20px;
}

.stat-card {
  background: white;
  border-radius: 16px;
  padding: 16px;
  display: flex;
  flex-direction: column;
  gap: 8px;
  position: relative;
  overflow: hidden;
  box-shadow: 0 2px 12px rgba(0, 0, 0, 0.08);
}

.stat-card::before {
  content: '';
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  height: 4px;
}

.stat-primary::before { background: linear-gradient(90deg, #667eea, #764ba2); }
.stat-success::before { background: linear-gradient(90deg, #11998e, #38ef7d); }
.stat-warning::before { background: linear-gradient(90deg, #f093fb, #f5576c); }
.stat-info::before { background: linear-gradient(90deg, #4facfe, #00f2fe); }

.stat-icon {
  width: 40px;
  height: 40px;
  border-radius: 10px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 18px;
}

.stat-primary .stat-icon { background: rgba(102, 126, 234, 0.1); color: #667eea; }
.stat-success .stat-icon { background: rgba(17, 153, 142, 0.1); color: #11998e; }
.stat-warning .stat-icon { background: rgba(240, 147, 251, 0.1); color: #f093fb; }
.stat-info .stat-icon { background: rgba(79, 172, 254, 0.1); color: #4facfe; }

.stat-content {
  flex: 1;
}

.stat-value {
  display: block;
  font-size: 22px;
  font-weight: 700;
  color: #1a1a2e;
  line-height: 1.2;
}

.stat-label {
  font-size: 11px;
  color: #666;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

/* Content Grid */
.content-grid {
  display: flex;
  flex-direction: column;
  gap: 16px;
}

/* Panel Card */
.panel-card {
  background: white;
  border-radius: 16px;
  overflow: hidden;
  box-shadow: 0 2px 12px rgba(0, 0, 0, 0.08);
}

.panel-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 16px;
  border-bottom: 1px solid #f0f0f0;
}

.panel-header h3 {
  margin: 0;
  font-size: 15px;
  font-weight: 600;
  color: #1a1a2e;
  display: flex;
  align-items: center;
  gap: 8px;
}

.panel-header h3 i {
  color: #667eea;
}

.panel-link {
  color: #667eea;
  text-decoration: none;
  font-size: 13px;
  font-weight: 500;
  display: flex;
  align-items: center;
  gap: 4px;
}

.panel-badge {
  font-size: 11px;
  padding: 4px 10px;
  background: #f0f0f0;
  border-radius: 12px;
  color: #666;
}

.panel-body {
  padding: 16px;
}

/* Loading & Empty States */
.loading-state,
.empty-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 32px;
  color: #999;
}

.empty-state.small {
  padding: 16px;
}

.spinner {
  width: 32px;
  height: 32px;
  border: 3px solid #f0f0f0;
  border-top-color: #667eea;
  border-radius: 50%;
  animation: spin 0.8s linear infinite;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}

.empty-state i {
  font-size: 32px;
  margin-bottom: 8px;
  opacity: 0.5;
}

.empty-state p {
  margin: 0;
  font-size: 14px;
}

/* Citas List */
.citas-list {
  display: flex;
  flex-direction: column;
  gap: 10px;
}

.cita-item {
  display: grid;
  grid-template-columns: auto 1fr auto;
  gap: 12px;
  align-items: center;
  padding: 12px;
  background: #f8f9fa;
  border-radius: 12px;
}

.cita-time {
  font-size: 14px;
  font-weight: 700;
  color: #667eea;
  min-width: 50px;
}

.cita-details {
  min-width: 0;
}

.cita-cliente {
  display: block;
  font-weight: 600;
  color: #333;
  font-size: 14px;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.cita-servicio {
  font-size: 12px;
  color: #666;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  display: block;
}

.status-badge {
  padding: 4px 10px;
  border-radius: 8px;
  font-size: 10px;
  font-weight: 600;
  text-transform: uppercase;
  white-space: nowrap;
}

.status-badge.pendiente { background: #fff3e0; color: #e65100; }
.status-badge.confirmada { background: #e8f5e9; color: #2e7d32; }
.status-badge.en_proceso { background: #e3f2fd; color: #1565c0; }
.status-badge.completada { background: #f3e5f5; color: #7b1fa2; }
.status-badge.cancelada { background: #ffebee; color: #c62828; }
.status-badge.no_show { background: #fce4ec; color: #c2185b; }

/* Empleados List */
.empleados-list {
  display: flex;
  flex-direction: column;
  gap: 10px;
}

.empleado-item {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 12px;
  background: #f8f9fa;
  border-radius: 12px;
}

.empleado-avatar {
  width: 40px;
  height: 40px;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
  font-weight: 700;
  font-size: 16px;
}

.empleado-info {
  flex: 1;
}

.empleado-nombre {
  display: block;
  font-weight: 600;
  color: #333;
  font-size: 14px;
}

.empleado-citas {
  font-size: 12px;
  color: #666;
}

/* Servicios Chart */
.servicios-chart {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.servicio-row {
  display: flex;
  align-items: center;
  gap: 12px;
}

.servicio-rank {
  font-size: 12px;
  font-weight: 700;
  color: #999;
  min-width: 24px;
}

.servicio-info {
  flex: 1;
  min-width: 0;
}

.servicio-nombre {
  display: block;
  font-size: 13px;
  color: #333;
  margin-bottom: 4px;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.progress-bar {
  height: 6px;
  background: #f0f0f0;
  border-radius: 3px;
  overflow: hidden;
}

.progress-fill {
  height: 100%;
  background: linear-gradient(90deg, #667eea, #764ba2);
  border-radius: 3px;
  transition: width 0.3s ease;
}

.servicio-count {
  font-size: 14px;
  font-weight: 700;
  color: #667eea;
  min-width: 32px;
  text-align: right;
}

/* Stats Mini Grid */
.stats-mini-grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 12px;
}

.stat-mini-item {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 12px;
  background: #f8f9fa;
  border-radius: 12px;
}

.stat-mini-icon {
  width: 36px;
  height: 36px;
  border-radius: 10px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 14px;
}

.stat-mini-icon.blue { background: rgba(79, 172, 254, 0.15); color: #4facfe; }
.stat-mini-icon.green { background: rgba(56, 239, 125, 0.15); color: #11998e; }
.stat-mini-icon.orange { background: rgba(250, 112, 154, 0.15); color: #fa709a; }
.stat-mini-icon.purple { background: rgba(118, 75, 162, 0.15); color: #764ba2; }

.stat-mini-info {
  display: flex;
  flex-direction: column;
}

.stat-mini-value {
  font-size: 16px;
  font-weight: 700;
  color: #1a1a2e;
}

.stat-mini-label {
  font-size: 10px;
  color: #666;
  text-transform: uppercase;
}
</style>
