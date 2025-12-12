<template>
  <div class="dashboard">
    <!-- Header Section -->
    <header class="dashboard-header">
      <div class="header-left">
        
        <div class="header-text">
          <h1>Dashboard</h1>
          <p class="date">{{ fechaFormateada }}</p>
        </div>
      </div>
      <div class="header-right">
        <span class="user-name">Hola, {{ userName }}</span>
        <span class="user-role">Administrador</span>
      </div>
    </header>

    <!-- Quick Stats - Bento Grid -->
    <section class="bento-grid">
      <!-- Card Principal - Citas Hoy -->
      <div class="bento-card bento-featured">
        <div class="bento-glow blue"></div>
        <div class="bento-content">
          <div class="bento-header">
            <div class="bento-icon-wrapper blue">
              <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                <line x1="16" y1="2" x2="16" y2="6"></line>
                <line x1="8" y1="2" x2="8" y2="6"></line>
                <line x1="3" y1="10" x2="21" y2="10"></line>
              </svg>
            </div>
            <span class="bento-label">Citas de hoy</span>
          </div>
          <div class="bento-value-wrapper">
            <div class="bento-value-row-main">
              <div class="bento-value-large">{{ stats.citasHoy }}</div>
              <span class="bento-badge">citas</span>
            </div>
            <div class="bento-status">
              <span class="bento-trend up">
                <svg xmlns="http://www.w3.org/2000/svg" width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                  <polyline points="23 6 13.5 15.5 8.5 10.5 1 18"></polyline>
                  <polyline points="17 6 23 6 23 12"></polyline>
                </svg>
                Activo
              </span>
            </div>
          </div>
        </div>
      </div>

      <!-- Card Ingresos -->
      <div class="bento-card bento-featured">
        <div class="bento-glow green"></div>
        <div class="bento-content">
          <div class="bento-header">
            <div class="bento-icon-wrapper green">
              <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <line x1="12" y1="1" x2="12" y2="23"></line>
                <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path>
              </svg>
            </div>
            <span class="bento-label">Ingresos del mes</span>
          </div>
          <div class="bento-value-wrapper">
            <div class="bento-value-large">${{ formatMonto(stats.ingresosMes) }}</div>
            <div class="bento-sub-info">
              <span class="bento-sub-label">Hoy: ${{ formatMonto(stats.ingresosHoy) }}</span>
            </div>
          </div>
        </div>
      </div>

      <!-- Card Clientes -->
      <div class="bento-card bento-small">
        <div class="bento-glow purple"></div>
        <div class="bento-content">
          <div class="bento-small-header">
            <div class="bento-icon-wrapper purple">
              <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                <circle cx="9" cy="7" r="4"></circle>
                <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
              </svg>
            </div>
            <span class="bento-label">Clientes</span>
          </div>
          <div class="bento-small-value">
            <div class="bento-value-medium">{{ stats.totalClientes }}</div>
          </div>
        </div>
      </div>

      <!-- Card Pendientes -->
      <div class="bento-card bento-small">
        <div class="bento-glow orange"></div>
        <div class="bento-content">
          <div class="bento-small-header">
            <div class="bento-icon-wrapper orange">
              <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="12" cy="12" r="10"></circle>
                <polyline points="12 6 12 12 16 14"></polyline>
              </svg>
            </div>
            <span class="bento-label">Pendientes</span>
          </div>
          <div class="bento-small-value">
            <div class="bento-value-medium">{{ stats.citasPendientes }}</div>
          </div>
        </div>
      </div>
    </section>

    <!-- Main Content -->
    <div class="main-content">
      <!-- Today's Schedule -->
      <section class="card schedule-card">
        <div class="card-header">
          <div class="card-header-left">
            <div class="card-header-icon">
              <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                <line x1="16" y1="2" x2="16" y2="6"></line>
                <line x1="8" y1="2" x2="8" y2="6"></line>
                <line x1="3" y1="10" x2="21" y2="10"></line>
              </svg>
            </div>
            <h2>Agenda de hoy</h2>
          </div>
          <router-link to="/admin/citas" class="see-all">
            Ver todas
            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
              <polyline points="9 18 15 12 9 6"></polyline>
            </svg>
          </router-link>
        </div>
        
        <div v-if="loading" class="loading-container">
          <div class="loader"></div>
        </div>
        
        <div v-else-if="citasHoy.length === 0" class="empty-state">
          <div class="empty-icon">
            <svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
              <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
              <line x1="16" y1="2" x2="16" y2="6"></line>
              <line x1="8" y1="2" x2="8" y2="6"></line>
              <line x1="3" y1="10" x2="21" y2="10"></line>
            </svg>
          </div>
          <p>No hay citas programadas para hoy</p>
        </div>
        
        <ul v-else class="schedule-list">
          <li v-for="cita in citasHoy" :key="cita.id" class="schedule-item">
            <div class="schedule-time">
              <span class="time-hour">{{ formatHora(cita.fecha_hora) }}</span>
            </div>
            <div class="schedule-content">
              <div class="schedule-main">
                <span class="schedule-client">{{ cita.cliente?.nombre || 'Sin cliente' }}</span>
                <span class="schedule-service">{{ getServiciosNombres(cita) }}</span>
              </div>
            </div>
            <div :class="['schedule-status', cita.estado]">
              {{ estadoLabel(cita.estado) }}
            </div>
          </li>
        </ul>
      </section>

      <!-- Side Panels -->
      <div class="side-panels">
        <!-- Team Activity -->
        <section class="card team-card">
          <div class="card-header">
            <div class="card-header-left">
              <div class="card-header-icon team-icon">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                  <circle cx="9" cy="7" r="4"></circle>
                  <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                  <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                </svg>
              </div>
              <h2>Equipo</h2>
            </div>
          </div>
          
          <div v-if="loading" class="loading-container small">
            <div class="loader"></div>
          </div>
          
          <div v-else-if="empleadosOcupados.length === 0" class="empty-state small">
            <p>Sin actividad</p>
          </div>
          
          <ul v-else class="team-list">
            <li v-for="empleado in empleadosOcupados" :key="empleado.nombre" class="team-member" @click="abrirEstadisticasEmpleado(empleado)">
              <div class="team-avatar">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                  <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                  <circle cx="12" cy="7" r="4"></circle>
                </svg>
              </div>
              <div class="team-member-info">
                <span class="team-member-name">{{ empleado.nombre }}</span>
                <span class="team-member-stats">{{ empleado.citas }} cita{{ empleado.citas !== 1 ? 's' : '' }} hoy</span>
              </div>
            </li>
          </ul>
        </section>

        <!-- Popular Services -->
        <section class="card services-card">
          <div class="card-header">
            <div class="card-header-left">
              <div class="card-header-icon services-icon">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"></path>
                </svg>
              </div>
              <h2>Servicios populares</h2>
            </div>
            <span class="period-badge">Este mes</span>
          </div>
          
          <div v-if="loading" class="loading-container small">
            <div class="loader"></div>
          </div>
          
          <div v-else-if="serviciosPopulares.length === 0" class="empty-state small">
            <p>Sin datos</p>
          </div>
          
          <ul v-else class="services-list">
            <li v-for="(servicio, index) in serviciosPopulares" :key="index" class="service-item">
              <div class="service-rank-badge" :class="getRankClass(index)">
                {{ index + 1 }}
              </div>
              <div class="service-details">
                <span class="service-title">{{ servicio.nombre }}</span>
                <div class="progress-track">
                  <div class="progress-bar" :style="{ width: getServicioPercent(servicio) + '%' }"></div>
                </div>
              </div>
              <div class="service-count-wrapper">
                <span class="service-count">{{ servicio.cantidad }}</span>
              </div>
            </li>
          </ul>
        </section>
      </div>
    </div>

    <!-- Quick Stats Footer -->
    <section class="metrics-section">
      <div class="metric-item">
        <div class="metric-icon">
          <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
            <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
            <circle cx="12" cy="7" r="4"></circle>
          </svg>
        </div>
        <div class="metric-data">
          <span class="metric-value">{{ stats.totalEmpleados }}</span>
          <span class="metric-label">Empleados</span>
        </div>
      </div>

      <div class="metric-item">
        <div class="metric-icon">
          <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
            <path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"></path>
          </svg>
        </div>
        <div class="metric-data">
          <span class="metric-value">{{ stats.totalServicios }}</span>
          <span class="metric-label">Servicios</span>
        </div>
      </div>

      <div class="metric-item">
        <div class="metric-icon warning">
          <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
            <circle cx="12" cy="12" r="10"></circle>
            <line x1="15" y1="9" x2="9" y2="15"></line>
            <line x1="9" y1="9" x2="15" y2="15"></line>
          </svg>
        </div>
        <div class="metric-data">
          <span class="metric-value">{{ stats.citasCanceladas }}</span>
          <span class="metric-label">Canceladas</span>
        </div>
      </div>

      <div class="metric-item">
        <div class="metric-icon success">
          <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
            <line x1="12" y1="1" x2="12" y2="23"></line>
            <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path>
          </svg>
        </div>
        <div class="metric-data">
          <span class="metric-value">${{ formatMonto(stats.ingresosHoy) }}</span>
          <span class="metric-label">Ingresos hoy</span>
        </div>
      </div>
    </section>
  </div>

  <!-- Modal de Estadísticas del Empleado -->
  <div v-if="mostrarEstadisticas" class="modal-overlay" @click="mostrarEstadisticas = false">
    <div class="modal-content" @click.stop>
      <div class="modal-header">
        <div>
          <h3>{{ empleadoSeleccionado?.nombre || 'Estadísticas' }}</h3>
          <p class="modal-subtitle">Estadísticas rápidas</p>
        </div>
        <button class="modal-close" @click="mostrarEstadisticas = false">
          <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <line x1="18" y1="6" x2="6" y2="18"></line>
            <line x1="6" y1="6" x2="18" y2="18"></line>
          </svg>
        </button>
      </div>
      
      <div v-if="cargandoEstadisticas" class="loading-container">
        <div class="loader"></div>
      </div>
      
      <div v-else class="modal-stats-grid">
        <div class="modal-stat-card">
          <div class="modal-stat-icon green">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <line x1="12" y1="1" x2="12" y2="23"></line>
              <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path>
            </svg>
          </div>
          <div class="modal-stat-info">
            <span class="modal-stat-value">${{ formatMonto(estadisticasEmpleado.ingresosMes) }}</span>
            <span class="modal-stat-label">Ingresos del mes</span>
          </div>
        </div>

        <div class="modal-stat-card">
          <div class="modal-stat-icon blue">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <line x1="12" y1="1" x2="12" y2="23"></line>
              <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path>
            </svg>
          </div>
          <div class="modal-stat-info">
            <span class="modal-stat-value">${{ formatMonto(estadisticasEmpleado.ingresosHoy) }}</span>
            <span class="modal-stat-label">Ingresos de hoy</span>
          </div>
        </div>

        <div class="modal-stat-card">
          <div class="modal-stat-icon purple">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
              <line x1="16" y1="2" x2="16" y2="6"></line>
              <line x1="8" y1="2" x2="8" y2="6"></line>
              <line x1="3" y1="10" x2="21" y2="10"></line>
            </svg>
          </div>
          <div class="modal-stat-info">
            <span class="modal-stat-value">{{ estadisticasEmpleado.citasMes }}</span>
            <span class="modal-stat-label">Citas en el mes</span>
          </div>
        </div>

        <div class="modal-stat-card">
          <div class="modal-stat-icon orange">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
              <line x1="16" y1="2" x2="16" y2="6"></line>
              <line x1="8" y1="2" x2="8" y2="6"></line>
              <line x1="3" y1="10" x2="21" y2="10"></line>
            </svg>
          </div>
          <div class="modal-stat-info">
            <span class="modal-stat-value">{{ estadisticasEmpleado.citasHoy }}</span>
            <span class="modal-stat-label">Citas de hoy</span>
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
const mostrarEstadisticas = ref(false);
const cargandoEstadisticas = ref(false);
const empleadoSeleccionado = ref<any>(null);
const estadisticasEmpleado = ref({
  ingresosMes: 0,
  ingresosHoy: 0,
  citasMes: 0,
  citasHoy: 0,
});

const stats = ref({
  citasHoy: 0,
  citasPendientes: 0,
  ingresosHoy: 0,
  ingresosMes: 0,
  totalClientes: 0,
  totalEmpleados: 0,
  totalServicios: 0,
  tasaNoShow: 0,
  citasCanceladas: 0,
});

const citasHoy = ref<any[]>([]);
const citasMes = ref(0);
const empleadosOcupados = ref<any[]>([]);
const serviciosPopulares = ref<any[]>([]);

const userName = computed(() => {
  const nombre = authStore.user?.nombre || 'Admin';
  return nombre.split(' ')[0]; // Solo primer nombre
});

const fechaFormateada = computed(() => {
  return new Date().toLocaleDateString('es-MX', {
    weekday: 'long',
    day: 'numeric',
    month: 'long',
    year: 'numeric'
  });
});

async function cargarDashboard() {
  loading.value = true;
  try {
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
        citasCanceladas: 0, // Se calculará después desde las citas del mes
      };
      
      empleadosOcupados.value = data.empleados_ocupados || [];
      serviciosPopulares.value = data.servicios_populares || [];
    }

    const hoy = new Date().toISOString().split('T')[0];
    const citasRes = await getCitas({ 
      desde: hoy + ' 00:00:00', 
      hasta: hoy + ' 23:59:59',
      per_page: 1000 
    });
    if (citasRes.success) {
      citasHoy.value = citasRes.data || [];
      
      // Calcular citas de hoy para cada empleado
      if (empleadosOcupados.value.length > 0) {
        empleadosOcupados.value = empleadosOcupados.value.map((empleado: any) => {
          const empleadoId = empleado.id || empleado.empleado_id || empleado.empleado?.id;
          const nombreEmpleado = empleado.nombre || empleado.empleado?.nombre;
          
          const citasDelEmpleado = citasHoy.value.filter((cita: any) => {
            if (empleadoId) {
              return cita.empleado_id === empleadoId || 
                     cita.empleado?.id === empleadoId;
            }
            if (nombreEmpleado) {
              return cita.empleado?.nombre === nombreEmpleado ||
                     cita.empleado_nombre === nombreEmpleado;
            }
            return false;
          });
          
          return {
            ...empleado,
            citas: citasDelEmpleado.length
          };
        });
      }
    }

    // Obtener citas del mes
    const ahora = new Date();
    const primerDiaMes = new Date(ahora.getFullYear(), ahora.getMonth(), 1);
    const ultimoDiaMes = new Date(ahora.getFullYear(), ahora.getMonth() + 1, 0);
    const inicioMes = primerDiaMes.toISOString().split('T')[0];
    const finMes = ultimoDiaMes.toISOString().split('T')[0];
    
    const citasMesRes = await getCitas({ 
      desde: inicioMes + ' 00:00:00', 
      hasta: finMes + ' 23:59:59',
      per_page: 1000 
    });
    if (citasMesRes.success) {
      const todasCitasMes = citasMesRes.data || [];
      citasMes.value = todasCitasMes.length;
      
      // Calcular citas canceladas
      const canceladas = todasCitasMes.filter((cita: any) => cita.estado === 'cancelada');
      stats.value.citasCanceladas = canceladas.length;
    }
  } catch (error) {
    console.error('Error cargando dashboard:', error);
  } finally {
    loading.value = false;
  }
}

async function abrirEstadisticasEmpleado(empleado: any) {
  empleadoSeleccionado.value = empleado;
  mostrarEstadisticas.value = true;
  cargandoEstadisticas.value = true;
  
  try {
    const hoy = new Date().toISOString().split('T')[0];
    const ahora = new Date();
    const primerDiaMes = new Date(ahora.getFullYear(), ahora.getMonth(), 1);
    const ultimoDiaMes = new Date(ahora.getFullYear(), ahora.getMonth() + 1, 0);
    const inicioMes = primerDiaMes.toISOString().split('T')[0];
    const finMes = ultimoDiaMes.toISOString().split('T')[0];
    
    // Obtener el ID del empleado
    const empleadoId = empleado.id || empleado.empleado_id || empleado.empleado?.id;
    const nombreEmpleado = empleado.nombre || empleado.empleado?.nombre;
    
    // Obtener todas las citas del día
    const citasHoyRes = await getCitas({ 
      desde: hoy + ' 00:00:00', 
      hasta: hoy + ' 23:59:59',
      per_page: 1000 
    });
    
    // Obtener todas las citas del mes
    const citasMesRes = await getCitas({ 
      desde: inicioMes + ' 00:00:00', 
      hasta: finMes + ' 23:59:59',
      per_page: 1000 
    });
    
    const todasCitasHoy = citasHoyRes.success ? (citasHoyRes.data || []) : [];
    const todasCitasMes = citasMesRes.success ? (citasMesRes.data || []) : [];
    
    // Filtrar manualmente por empleado
    const citasHoyEmpleado = todasCitasHoy.filter((cita: any) => {
      if (empleadoId) {
        return cita.empleado_id === empleadoId || 
               cita.empleado?.id === empleadoId ||
               cita.empleado_id === empleado.id;
      }
      // Si no hay ID, filtrar por nombre
      if (nombreEmpleado) {
        return cita.empleado?.nombre === nombreEmpleado ||
               cita.empleado_nombre === nombreEmpleado;
      }
      return false;
    });
    
    const citasMesEmpleado = todasCitasMes.filter((cita: any) => {
      if (empleadoId) {
        return cita.empleado_id === empleadoId || 
               cita.empleado?.id === empleadoId ||
               cita.empleado_id === empleado.id;
      }
      // Si no hay ID, filtrar por nombre
      if (nombreEmpleado) {
        return cita.empleado?.nombre === nombreEmpleado ||
               cita.empleado_nombre === nombreEmpleado;
      }
      return false;
    });
    
    // Calcular ingresos
    let ingresosHoy = 0;
    let ingresosMes = 0;
    
    citasHoyEmpleado.forEach((cita: any) => {
      if (cita.estado === 'completada' && (cita.total || cita.precio_final)) {
        ingresosHoy += parseFloat(cita.total || cita.precio_final || 0);
      }
    });
    
    citasMesEmpleado.forEach((cita: any) => {
      if (cita.estado === 'completada' && (cita.total || cita.precio_final)) {
        ingresosMes += parseFloat(cita.total || cita.precio_final || 0);
      }
    });
    
    estadisticasEmpleado.value = {
      ingresosMes,
      ingresosHoy,
      citasMes: citasMesEmpleado.length,
      citasHoy: citasHoyEmpleado.length,
    };
  } catch (error) {
    console.error('Error cargando estadísticas del empleado:', error);
    estadisticasEmpleado.value = {
      ingresosMes: 0,
      ingresosHoy: 0,
      citasMes: 0,
      citasHoy: 0,
    };
  } finally {
    cargandoEstadisticas.value = false;
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

function getRankClass(index: number): string {
  if (index === 0) return 'rank-gold';
  if (index === 1) return 'rank-silver';
  if (index === 2) return 'rank-bronze';
  return 'rank-default';
}

onMounted(() => {
  cargarDashboard();
});
</script>

<style scoped>
/* ===== Apple-inspired Dashboard Design ===== */

.dashboard {
  min-height: 100vh;
  background: #f5f5f7;
  padding: 24px;
  padding-bottom: 120px;
  font-family: -apple-system, BlinkMacSystemFont, 'SF Pro Display', 'SF Pro Text', 'Helvetica Neue', sans-serif;
  -webkit-font-smoothing: antialiased;
  -moz-osx-font-smoothing: grayscale;
}

/* Header */
.dashboard-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 28px;
  padding: 20px;
  background: linear-gradient(135deg, #1d1d1f 0%, #3a3a3c 100%);
  border-radius: 20px;
  color: white;
}

.header-left {
  display: flex;
  align-items: center;
  gap: 14px;
}

.header-icon {
  width: 46px;
  height: 46px;
  background: rgba(255, 255, 255, 0.1);
  border-radius: 14px;
  display: flex;
  align-items: center;
  justify-content: center;
  backdrop-filter: blur(10px);
}

.header-text h1 {
  font-size: 22px;
  font-weight: 600;
  margin: 0;
  letter-spacing: -0.3px;
}

.date {
  font-size: 13px;
  opacity: 0.7;
  margin: 4px 0 0;
  text-transform: capitalize;
  font-weight: 400;
}

.header-right {
  text-align: right;
}

.user-name {
  display: block;
  font-size: 15px;
  font-weight: 500;
}

.user-role {
  display: block;
  font-size: 12px;
  opacity: 0.6;
  margin-top: 2px;
}

/* Bento Grid */
.bento-grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  grid-template-rows: auto auto;
  gap: 10px;
  margin-bottom: 20px;
}

.bento-card {
  background: #ffffff;
  border-radius: 16px;
  position: relative;
  overflow: hidden;
  transition: transform 0.2s ease;
}

.bento-card:active {
  transform: scale(0.98);
}

/* Featured Cards (Larger) */
.bento-featured {
  padding: 16px;
  min-height: 110px;
}

.bento-glow {
  position: absolute;
  top: -40%;
  right: -25%;
  width: 100px;
  height: 100px;
  border-radius: 50%;
  filter: blur(35px);
  opacity: 0.35;
  pointer-events: none;
}

.bento-glow.blue { background: #007aff; }
.bento-glow.green { background: #34c759; }

.bento-content {
  position: relative;
  z-index: 1;
  height: 100%;
  display: flex;
  flex-direction: column;
  justify-content: space-between;
}

.bento-header {
  display: flex;
  align-items: center;
  gap: 10px;
  margin-bottom: 12px;
}

.bento-label {
  font-size: 12px;
  font-weight: 500;
  color: #86868b;
  letter-spacing: -0.1px;
  text-transform: uppercase;
}

.bento-icon-wrapper {
  width: 28px;
  height: 28px;
  border-radius: 8px;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

.bento-icon-wrapper.blue {
  background: linear-gradient(135deg, #007aff 0%, #5856d6 100%);
  color: white;
}

.bento-icon-wrapper.green {
  background: linear-gradient(135deg, #34c759 0%, #30d158 100%);
  color: white;
}

.bento-value-wrapper {
  display: flex;
  flex-direction: column;
  gap: 6px;
  flex: 1;
  justify-content: flex-start;
}

.bento-value-row {
  display: flex;
  align-items: center;
  gap: 10px;
}

.bento-value-row-main {
  display: flex;
  align-items: baseline;
  gap: 6px;
  justify-content: center;
}

.bento-value-large {
  font-size: 28px;
  font-weight: 700;
  color: #1d1d1f;
  letter-spacing: -0.8px;
  line-height: 1.1;
}

.bento-badge {
  font-size: 13px;
  font-weight: 500;
  color: #86868b;
  letter-spacing: -0.1px;
}

.bento-status {
  margin-top: 4px;
  display: flex;
  justify-content: center;
}

.bento-sub-info {
  margin-top: 2px;
}

.bento-sub-label {
  font-size: 12px;
  font-weight: 500;
  color: #86868b;
  letter-spacing: -0.1px;
}

.bento-trend {
  display: inline-flex;
  align-items: center;
  gap: 3px;
  font-size: 11px;
  font-weight: 500;
  padding: 3px 7px;
  border-radius: 5px;
  width: fit-content;
}

.bento-trend.up {
  background: rgba(52, 199, 89, 0.12);
  color: #34c759;
}

.bento-trend.down {
  background: rgba(255, 59, 48, 0.12);
  color: #ff3b30;
}

/* Mini Chart */
.mini-chart {
  display: flex;
  align-items: flex-end;
  gap: 3px;
  height: 24px;
  margin-top: 2px;
}

.mini-bar {
  width: 6px;
  background: #e5e5ea;
  border-radius: 3px;
  transition: background 0.2s;
}

.mini-bar.active {
  background: linear-gradient(180deg, #34c759 0%, #30d158 100%);
}

/* Small Cards */
.bento-small {
  padding: 16px;
  min-height: 110px;
  position: relative;
  overflow: hidden;
}

.bento-small .bento-glow {
  position: absolute;
  top: -30%;
  right: -20%;
  width: 80px;
  height: 80px;
  border-radius: 50%;
  filter: blur(30px);
  opacity: 0.3;
  pointer-events: none;
}

.bento-small .bento-glow.purple {
  background: #af52de;
}

.bento-small .bento-glow.orange {
  background: #ff9500;
}

.bento-small .bento-content {
  width: 100%;
  display: flex;
  flex-direction: column;
  justify-content: space-between;
  gap: 10px;
  position: relative;
  z-index: 1;
}

.bento-small-header {
  display: flex;
  align-items: center;
  gap: 10px;
}

.bento-icon-wrapper.purple {
  background: linear-gradient(135deg, #af52de 0%, #bf5af2 100%);
  color: white;
}

.bento-icon-wrapper.orange {
  background: linear-gradient(135deg, #ff9500 0%, #ff9f0a 100%);
  color: white;
}

.bento-small-value {
  display: flex;
  align-items: flex-end;
  justify-content: center;
}

.bento-value-medium {
  font-size: 26px;
  font-weight: 700;
  color: #1d1d1f;
  letter-spacing: -0.6px;
  line-height: 1;
}

.bento-label-small {
  font-size: 11px;
  font-weight: 500;
  color: #86868b;
  text-transform: uppercase;
  letter-spacing: 0.3px;
}

/* Main Content */
.main-content {
  display: flex;
  flex-direction: column;
  gap: 16px;
  margin-bottom: 24px;
}

/* Card Base */
.card {
  background: #ffffff;
  border-radius: 16px;
  overflow: hidden;
}


.card-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 18px 20px 16px;
}

.card-header-left {
  display: flex;
  align-items: center;
  gap: 10px;
}

.card-header-icon {
  width: 32px;
  height: 32px;
  border-radius: 8px;
  background: rgba(0, 122, 255, 0.1);
  color: #007aff;
  display: flex;
  align-items: center;
  justify-content: center;
}

.card-header h2 {
  font-size: 17px;
  font-weight: 600;
  color: #1d1d1f;
  margin: 0;
  letter-spacing: -0.2px;
}

.see-all {
  display: flex;
  align-items: center;
  gap: 4px;
  color: #007aff;
  text-decoration: none;
  font-size: 14px;
  font-weight: 500;
  transition: opacity 0.2s;
  padding: 4px 0;
}

.see-all:hover {
  opacity: 0.7;
}

.period-badge {
  font-size: 12px;
  color: #86868b;
  background: #f5f5f7;
  padding: 4px 10px;
  border-radius: 20px;
  font-weight: 500;
}

/* Loading State */
.loading-container {
  display: flex;
  justify-content: center;
  align-items: center;
  padding: 48px 20px;
}

.loading-container.small {
  padding: 32px 20px;
}

.loader {
  width: 24px;
  height: 24px;
  border: 2px solid #f5f5f7;
  border-top-color: #007aff;
  border-radius: 50%;
  animation: spin 0.8s linear infinite;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}

/* Empty State */
.empty-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 48px 20px;
  text-align: center;
}

.empty-state.small {
  padding: 32px 20px;
}

.empty-icon {
  color: #d1d1d6;
  margin-bottom: 12px;
}

.empty-state p {
  color: #86868b;
  font-size: 15px;
  margin: 0;
}

/* Schedule List */
.schedule-list {
  list-style: none;
  margin: 0;
  padding: 0 20px 20px;
  display: flex;
  flex-direction: column;
  gap: 6px;
}

.schedule-item {
  display: grid;
  grid-template-columns: auto 1fr auto;
  align-items: center;
  gap: 14px;
  padding: 14px 16px;
  background: #f8f9fa;
  border-radius: 14px;
  border: 1px solid #f0f0f0;
  transition: all 0.2s ease;
}

.schedule-item:active {
  background: #f0f0f0;
  transform: scale(0.99);
}

.schedule-time {
  display: flex;
  align-items: center;
  min-width: 56px;
}

.time-hour {
  font-size: 15px;
  font-weight: 600;
  color: #007aff;
  font-variant-numeric: tabular-nums;
  letter-spacing: -0.2px;
}

.schedule-content {
  flex: 1;
  min-width: 0;
}

.schedule-main {
  display: flex;
  flex-direction: column;
  gap: 3px;
}

.schedule-client {
  font-size: 15px;
  font-weight: 600;
  color: #1d1d1f;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  letter-spacing: -0.1px;
}

.schedule-service {
  font-size: 13px;
  color: #86868b;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

/* Status Pills */
.schedule-status {
  font-size: 11px;
  font-weight: 600;
  padding: 5px 11px;
  border-radius: 12px;
  white-space: nowrap;
  text-transform: capitalize;
  letter-spacing: 0.2px;
}

.schedule-status.pendiente {
  background: rgba(255, 149, 0, 0.12);
  color: #ff9500;
}

.schedule-status.confirmada {
  background: rgba(52, 199, 89, 0.12);
  color: #34c759;
}

.schedule-status.en_proceso {
  background: rgba(0, 122, 255, 0.12);
  color: #007aff;
}

.schedule-status.completada {
  background: rgba(52, 199, 89, 0.12);
  color: #34c759;
}

.schedule-status.cancelada {
  background: rgba(255, 59, 48, 0.12);
  color: #ff3b30;
}

.schedule-status.no_show {
  background: rgba(255, 59, 48, 0.12);
  color: #ff3b30;
}

.status-pill {
  font-size: 11px;
  font-weight: 500;
  padding: 5px 10px;
  border-radius: 20px;
  white-space: nowrap;
  text-transform: capitalize;
}

.status-pill.pendiente {
  background: #fff4e6;
  color: #ff9500;
}

.status-pill.confirmada {
  background: #e3f8e8;
  color: #34c759;
}

.status-pill.en_proceso {
  background: #e8f4fd;
  color: #007aff;
}

.status-pill.completada {
  background: #e3f8e8;
  color: #34c759;
}

.status-pill.cancelada {
  background: #ffe5e5;
  color: #ff3b30;
}

.status-pill.no_show {
  background: #ffe5e5;
  color: #ff3b30;
}

/* Side Panels */
.side-panels {
  display: flex;
  flex-direction: column;
  gap: 16px;
}

/* Team List */
.team-list {
  list-style: none;
  margin: 0;
  padding: 0 20px 20px;
  display: flex;
  flex-direction: column;
  gap: 6px;
}

.team-member {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 12px 14px;
  background: #f8f9fa;
  border-radius: 14px;
  border: 1px solid #f0f0f0;
  transition: all 0.2s ease;
  cursor: pointer;
}

.team-member:active {
  background: #f0f0f0;
  transform: scale(0.99);
}

.team-avatar {
  width: 36px;
  height: 36px;
  border-radius: 10px;
  background: #e8f4fd;
  color: #007aff;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

.team-member-info {
  flex: 1;
  display: flex;
  flex-direction: column;
  gap: 2px;
  min-width: 0;
}

.team-member-name {
  font-size: 15px;
  font-weight: 600;
  color: #1d1d1f;
  letter-spacing: -0.1px;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.team-member-stats {
  font-size: 13px;
  color: #86868b;
  font-weight: 400;
}

.card-header-icon.team-icon {
  background: rgba(175, 82, 222, 0.1);
  color: #af52de;
}

/* Services List */
.services-list {
  list-style: none;
  margin: 0;
  padding: 0 20px 20px;
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.service-item {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 12px 14px;
  background: #f8f9fa;
  border-radius: 14px;
  border: 1px solid #f0f0f0;
  transition: all 0.2s ease;
}

.service-item:hover {
  background: #f0f0f0;
}

.service-rank-badge {
  width: 32px;
  height: 32px;
  border-radius: 10px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 13px;
  font-weight: 700;
  flex-shrink: 0;
  letter-spacing: -0.2px;
}

.service-rank-badge.rank-gold {
  background: linear-gradient(135deg, #ffd700 0%, #ffed4e 100%);
  color: #1d1d1f;
}

.service-rank-badge.rank-silver {
  background: linear-gradient(135deg, #c0c0c0 0%, #e8e8e8 100%);
  color: #1d1d1f;
}

.service-rank-badge.rank-bronze {
  background: linear-gradient(135deg, #cd7f32 0%, #e6a55d 100%);
  color: #ffffff;
}

.service-rank-badge.rank-default {
  background: #f5f5f7;
  color: #86868b;
}

.service-details {
  flex: 1;
  min-width: 0;
}

.service-title {
  display: block;
  font-size: 14px;
  font-weight: 600;
  color: #1d1d1f;
  margin-bottom: 8px;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  letter-spacing: -0.1px;
}

.progress-track {
  height: 5px;
  background: #e5e5ea;
  border-radius: 3px;
  overflow: hidden;
}

.progress-bar {
  height: 100%;
  background: linear-gradient(90deg, #007aff 0%, #5856d6 100%);
  border-radius: 3px;
  transition: width 0.5s cubic-bezier(0.4, 0, 0.2, 1);
}

.service-count-wrapper {
  display: flex;
  align-items: center;
  min-width: 40px;
  justify-content: flex-end;
}

.service-count {
  font-size: 16px;
  font-weight: 700;
  color: #1d1d1f;
  letter-spacing: -0.3px;
}

.card-header-icon.services-icon {
  background: rgba(255, 149, 0, 0.1);
  color: #ff9500;
}

/* Metrics Section */
.metrics-section {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 12px;
  background: #ffffff;
  border-radius: 16px;
  padding: 20px;
}

.metric-item {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 12px;
  background: #f5f5f7;
  border-radius: 12px;
}

.metric-icon {
  width: 36px;
  height: 36px;
  border-radius: 10px;
  background: #e8f4fd;
  color: #007aff;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

.metric-icon.warning {
  background: #fff4e6;
  color: #ff9500;
}

.metric-icon.success {
  background: #e3f8e8;
  color: #34c759;
}

.metric-data {
  display: flex;
  flex-direction: column;
  gap: 1px;
}

.metric-value {
  font-size: 17px;
  font-weight: 600;
  color: #1d1d1f;
  letter-spacing: -0.2px;
}

.metric-label {
  font-size: 12px;
  color: #86868b;
}

/* Subtle Animations */
@keyframes fadeIn {
  from {
    opacity: 0;
    transform: translateY(8px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

.dashboard-header,
.bento-card,
.card,
.metric-item {
  animation: fadeIn 0.4s ease backwards;
}

.dashboard-header { animation-delay: 0s; }

.bento-card:nth-child(1) { animation-delay: 0.05s; }
.bento-card:nth-child(2) { animation-delay: 0.1s; }
.bento-card:nth-child(3) { animation-delay: 0.15s; }
.bento-card:nth-child(4) { animation-delay: 0.2s; }

.schedule-card { animation-delay: 0.25s; }
.team-card { animation-delay: 0.3s; }
.services-card { animation-delay: 0.35s; }

/* Modal de Estadísticas */
.modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.4);
  backdrop-filter: blur(8px);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1000;
  padding: 20px;
  animation: fadeIn 0.2s ease;
}

.modal-content {
  background: #ffffff;
  border-radius: 20px;
  width: 100%;
  max-width: 400px;
  max-height: 90vh;
  overflow: hidden;
  box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
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

.modal-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 20px 20px 16px;
  border-bottom: 1px solid #f0f0f0;
}

.modal-header h3 {
  font-size: 20px;
  font-weight: 600;
  color: #1d1d1f;
  margin: 0;
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
  border-radius: 8px;
  background: #f5f5f7;
  border: none;
  display: flex;
  align-items: center;
  justify-content: center;
  color: #86868b;
  cursor: pointer;
  transition: all 0.2s;
}

.modal-close:active {
  background: #ebebed;
  transform: scale(0.95);
}

.modal-stats-grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 12px;
  padding: 20px;
}

.modal-stat-card {
  background: #f8f9fa;
  border-radius: 14px;
  padding: 16px;
  display: flex;
  flex-direction: column;
  gap: 12px;
  border: 1px solid #f0f0f0;
}

.modal-stat-icon {
  width: 36px;
  height: 36px;
  border-radius: 10px;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

.modal-stat-icon.green {
  background: rgba(52, 199, 89, 0.12);
  color: #34c759;
}

.modal-stat-icon.blue {
  background: rgba(0, 122, 255, 0.12);
  color: #007aff;
}

.modal-stat-icon.purple {
  background: rgba(175, 82, 222, 0.12);
  color: #af52de;
}

.modal-stat-icon.orange {
  background: rgba(255, 149, 0, 0.12);
  color: #ff9500;
}

.modal-stat-info {
  display: flex;
  flex-direction: column;
  gap: 2px;
}

.modal-stat-value {
  font-size: 22px;
  font-weight: 700;
  color: #1d1d1f;
  letter-spacing: -0.5px;
  line-height: 1.1;
}

.modal-stat-label {
  font-size: 12px;
  font-weight: 500;
  color: #86868b;
  text-transform: uppercase;
  letter-spacing: 0.3px;
}
</style>
