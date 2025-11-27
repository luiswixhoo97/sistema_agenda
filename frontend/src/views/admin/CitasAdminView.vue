<template>
  <div class="admin-view">
    <!-- Header -->
    <div class="view-header">
      <div class="header-info">
        <div class="header-icon">
          <i class="fa fa-calendar-alt"></i>
        </div>
        <div class="header-text">
          <h1>Citas</h1>
          <p class="header-subtitle">{{ totalCitas }} registradas</p>
        </div>
      </div>
      <button class="btn-action" @click="nuevaCita">
        <i class="fa fa-plus"></i>
      </button>
    </div>

    <!-- Filters -->
    <div class="filters-section">
      <div class="search-box">
        <i class="fa fa-search"></i>
        <input 
          v-model="busqueda" 
          type="text" 
          placeholder="Buscar cliente..." 
          @input="debouncedSearch"
        />
      </div>
      <div class="filter-row">
        <select v-model="filtroEstado" @change="cargarCitas" class="filter-select">
          <option value="">Todos</option>
          <option value="pendiente">Pendiente</option>
          <option value="confirmada">Confirmada</option>
          <option value="en_proceso">En proceso</option>
          <option value="completada">Completada</option>
          <option value="cancelada">Cancelada</option>
          <option value="no_show">No Show</option>
        </select>
        <input 
          v-model="filtroFecha" 
          type="date" 
          class="filter-date"
          @change="cargarCitas" 
        />
      </div>
    </div>

    <!-- Citas List -->
    <div class="citas-container">
      <div v-if="loading" class="loading-state">
        <div class="spinner"></div>
        <p>Cargando citas...</p>
      </div>
      
      <div v-else-if="citas.length === 0" class="empty-state">
        <i class="fa fa-calendar-times"></i>
        <p>No hay citas que mostrar</p>
      </div>
      
      <div v-else class="citas-list">
        <div 
          v-for="cita in citas" 
          :key="cita.id" 
          class="cita-card"
          @click="verCita(cita)"
        >
          <div class="cita-header">
            <span class="cita-id">#{{ cita.id }}</span>
            <span :class="['status-badge', cita.estado]">
              {{ estadoLabel(cita.estado) }}
            </span>
          </div>
          
          <div class="cita-body">
            <div class="cita-datetime">
              <i class="fa fa-clock"></i>
              <span>{{ formatFecha(cita.fecha_hora) }}</span>
            </div>
            
            <div class="cita-cliente">
              <i class="fa fa-user"></i>
              <span>{{ cita.cliente?.nombre || 'Sin cliente' }}</span>
            </div>
            
            <div class="cita-empleado">
              <i class="fa fa-user-tie"></i>
              <span>{{ cita.empleado?.nombre || 'Sin asignar' }}</span>
            </div>
            
            <div class="cita-servicios">
              <i class="fa fa-cut"></i>
              <span>{{ getServiciosNombres(cita) }}</span>
            </div>
          </div>
          
          <div class="cita-footer">
            <span class="cita-total">${{ formatPrecio(cita.precio_final || cita.precio_total) }}</span>
            <div class="cita-actions">
              <button class="btn-icon-sm" @click.stop="editarCita(cita)" title="Editar">
                <i class="fa fa-edit"></i>
              </button>
              <button 
                v-if="cita.estado !== 'cancelada' && cita.estado !== 'completada'" 
                class="btn-icon-sm danger" 
                @click.stop="cancelarCita(cita)" 
                title="Cancelar"
              >
                <i class="fa fa-times"></i>
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- Pagination -->
      <div v-if="pagination.last_page > 1" class="pagination">
        <button 
          class="page-btn" 
          :disabled="pagination.current_page === 1"
          @click="cambiarPagina(pagination.current_page - 1)"
        >
          <i class="fa fa-chevron-left"></i>
        </button>
        <span class="page-info">{{ pagination.current_page }} / {{ pagination.last_page }}</span>
        <button 
          class="page-btn" 
          :disabled="pagination.current_page === pagination.last_page"
          @click="cambiarPagina(pagination.current_page + 1)"
        >
          <i class="fa fa-chevron-right"></i>
        </button>
      </div>
    </div>

    <!-- Modal Ver/Editar Cita -->
    <Teleport to="body">
      <div v-if="showModal" class="modal-overlay" @click.self="closeModal">
        <div class="modal-content">
          <div class="modal-header">
            <h3>{{ editingCita ? 'Detalle de Cita' : 'Nueva Cita' }}</h3>
            <button class="modal-close" @click="closeModal">
              <i class="fa fa-times"></i>
            </button>
          </div>
          <div class="modal-body" v-if="editingCita">
            <!-- Vista Detalle -->
            <div class="cita-detail">
              <!-- Header con ID y Estado -->
              <div class="detail-header">
                <div class="detail-id">
                  <span class="id-label">Cita #</span>
                  <span class="id-value">{{ editingCita.id }}</span>
                </div>
                <span :class="['status-badge-large', editingCita.estado]">
                  {{ estadoLabel(editingCita.estado) }}
                </span>
              </div>

              <!-- Información Principal -->
              <div class="detail-section">
                <div class="section-title">
                  <i class="fa fa-calendar-alt"></i>
                  <span>Fecha y Hora</span>
                </div>
                <div class="info-block">
                  <div class="info-item">
                    <i class="fa fa-clock"></i>
                    <div class="info-content">
                      <span class="info-label">Fecha</span>
                      <span class="info-value">{{ formatFechaCompleta(editingCita.fecha_hora) }}</span>
                    </div>
                  </div>
                  <div class="info-item">
                    <i class="fa fa-hourglass-half"></i>
                    <div class="info-content">
                      <span class="info-label">Duración estimada</span>
                      <span class="info-value">{{ getDuracionTotal(editingCita) }} minutos</span>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Cliente -->
              <div class="detail-section">
                <div class="section-title">
                  <i class="fa fa-user"></i>
                  <span>Cliente</span>
                </div>
                <div class="info-block">
                  <div class="info-item">
                    <div class="info-content">
                      <span class="info-label">Nombre</span>
                      <span class="info-value">{{ editingCita.cliente?.nombre || 'Sin cliente' }}</span>
                    </div>
                  </div>
                  <div class="info-item" v-if="editingCita.cliente?.telefono">
                    <i class="fa fa-phone"></i>
                    <div class="info-content">
                      <span class="info-label">Teléfono</span>
                      <span class="info-value">
                        <a :href="`tel:${editingCita.cliente.telefono}`" class="phone-link">
                          {{ editingCita.cliente.telefono }}
                        </a>
                      </span>
                    </div>
                  </div>
                  <div class="info-item" v-if="editingCita.cliente?.email">
                    <i class="fa fa-envelope"></i>
                    <div class="info-content">
                      <span class="info-label">Email</span>
                      <span class="info-value">{{ editingCita.cliente.email }}</span>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Empleado -->
              <div class="detail-section">
                <div class="section-title">
                  <i class="fa fa-user-tie"></i>
                  <span>Empleado</span>
                </div>
                <div class="info-block">
                  <div class="info-item">
                    <div class="info-content">
                      <span class="info-label">Asignado a</span>
                      <span class="info-value">{{ editingCita.empleado?.nombre || 'Sin asignar' }}</span>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Servicios -->
              <div class="detail-section">
                <div class="section-title">
                  <i class="fa fa-cut"></i>
                  <span>Servicios</span>
                </div>
                <div class="servicios-list">
                  <div 
                    v-for="(servicio, index) in getServiciosList(editingCita)" 
                    :key="index"
                    class="servicio-item"
                  >
                    <div class="servicio-info">
                      <span class="servicio-nombre">{{ servicio.nombre }}</span>
                      <span class="servicio-duracion">{{ servicio.duracion || servicio.duracion_minutos }} min</span>
                    </div>
                    <span class="servicio-precio">${{ formatPrecio(servicio.precio || servicio.precio_aplicado) }}</span>
                  </div>
                </div>
              </div>

              <!-- Precio Total -->
              <div class="detail-section total-section">
                <div class="total-row">
                  <span class="total-label">Total</span>
                  <span class="total-value">${{ formatPrecio(editingCita.precio_final || editingCita.precio_total) }}</span>
                </div>
              </div>

              <!-- Notas -->
              <div class="detail-section" v-if="editingCita.notas">
                <div class="section-title">
                  <i class="fa fa-sticky-note"></i>
                  <span>Notas</span>
                </div>
                <div class="notas-content">
                  {{ editingCita.notas }}
                </div>
              </div>

              <!-- Acciones -->
              <div class="detail-actions">
                <button 
                  v-if="editingCita.estado !== 'cancelada' && editingCita.estado !== 'completada'"
                  class="btn-action-primary"
                  @click="cambiarEstado(editingCita, 'completada')"
                >
                  <i class="fa fa-check"></i>
                  Marcar como Completada
                </button>
                <button 
                  v-if="editingCita.estado === 'pendiente' || editingCita.estado === 'confirmada'"
                  class="btn-action-secondary"
                  @click="cambiarEstado(editingCita, 'en_proceso')"
                >
                  <i class="fa fa-play"></i>
                  Iniciar Cita
                </button>
                <button 
                  v-if="editingCita.estado !== 'cancelada' && editingCita.estado !== 'completada'"
                  class="btn-action-danger"
                  @click="cancelarCita(editingCita)"
                >
                  <i class="fa fa-times"></i>
                  Cancelar Cita
                </button>
              </div>
            </div>
          </div>
          <div v-else class="modal-body">
            <p class="coming-soon">Formulario de nueva cita en desarrollo...</p>
          </div>
        </div>
      </div>
    </Teleport>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue';
import { getCitas, updateCita } from '@/services/adminService';

const citas = ref<any[]>([]);
const busqueda = ref('');
const filtroEstado = ref('');
const filtroFecha = ref('');
const loading = ref(true);
const showModal = ref(false);
const editingCita = ref<any>(null);
const pagination = ref({
  current_page: 1,
  last_page: 1,
  total: 0,
});

let searchTimeout: ReturnType<typeof setTimeout>;

const totalCitas = computed(() => pagination.value.total);

async function cargarCitas(page = 1) {
  loading.value = true;
  try {
    const params: any = { 
      per_page: 10,
      page,
    };
    
    if (filtroEstado.value) {
      params.estado = filtroEstado.value;
    }
    if (filtroFecha.value) {
      params.desde = filtroFecha.value + ' 00:00:00';
      params.hasta = filtroFecha.value + ' 23:59:59';
    }
    
    const response = await getCitas(params);
    if (response.success) {
      citas.value = response.data || [];
      if (response.pagination) {
        pagination.value = response.pagination;
      }
    }
  } catch (error) {
    console.error('Error cargando citas:', error);
  } finally {
    loading.value = false;
  }
}

function debouncedSearch() {
  clearTimeout(searchTimeout);
  searchTimeout = setTimeout(() => {
    cargarCitas(1);
  }, 500);
}

function cambiarPagina(page: number) {
  cargarCitas(page);
}

function formatFecha(fecha: string) {
  if (!fecha) return '--';
  const date = new Date(fecha.replace(' ', 'T'));
  return date.toLocaleDateString('es-MX', { 
    weekday: 'short',
    day: 'numeric', 
    month: 'short',
    hour: '2-digit',
    minute: '2-digit'
  });
}

function formatPrecio(precio: any): string {
  return (Number(precio) || 0).toFixed(2);
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

function nuevaCita() { 
  editingCita.value = null;
  showModal.value = true;
}

function verCita(cita: any) { 
  editingCita.value = cita;
  showModal.value = true;
}

function editarCita(cita: any) { 
  editingCita.value = cita;
  showModal.value = true;
}

function getInitials(nombre: string): string {
  if (!nombre) return '?';
  return nombre
    .split(' ')
    .map(n => n.charAt(0))
    .slice(0, 2)
    .join('')
    .toUpperCase();
}

function formatFechaCompleta(fecha: string): string {
  if (!fecha) return '--';
  const date = new Date(fecha.replace(' ', 'T'));
  return date.toLocaleDateString('es-MX', { 
    weekday: 'long',
    day: 'numeric', 
    month: 'long',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  });
}

function getServiciosList(cita: any): any[] {
  if (cita.servicios && cita.servicios.length > 0) {
    return cita.servicios.map((s: any) => ({
      nombre: s.nombre || s.servicio?.nombre || 'Sin nombre',
      duracion: s.duracion || s.duracion_minutos || s.servicio?.duracion_minutos || 0,
      precio: s.precio || s.precio_aplicado || s.servicio?.precio || 0,
    }));
  }
  if (cita.servicio) {
    return [{
      nombre: cita.servicio.nombre,
      duracion: cita.servicio.duracion_minutos || cita.servicio.duracion || 0,
      precio: cita.servicio.precio || 0,
    }];
  }
  return [];
}

function getDuracionTotal(cita: any): number {
  const servicios = getServiciosList(cita);
  return servicios.reduce((total, s) => total + (s.duracion || 0), 0);
}

async function cambiarEstado(cita: any, nuevoEstado: string) {
  try {
    await updateCita(cita.id, { estado: nuevoEstado });
    cargarCitas(pagination.value.current_page);
    closeModal();
  } catch (error) {
    console.error('Error cambiando estado:', error);
    alert('Error al cambiar el estado de la cita');
  }
}

async function cancelarCita(cita: any) { 
  if (confirm('¿Estás seguro de cancelar esta cita?')) {
    try {
      await updateCita(cita.id, { estado: 'cancelada' });
      cargarCitas(pagination.value.current_page);
    } catch (error) {
      console.error('Error cancelando cita:', error);
      alert('Error al cancelar la cita');
    }
  }
}

function closeModal() {
  showModal.value = false;
  editingCita.value = null;
}

onMounted(() => {
  cargarCitas();
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
  margin-bottom: 16px;
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
}

.btn-action {
  width: 44px;
  height: 44px;
  background: rgba(255, 255, 255, 0.2);
  border: none;
  border-radius: 12px;
  color: white;
  font-size: 18px;
  cursor: pointer;
  transition: background 0.2s;
}

.btn-action:active {
  background: rgba(255, 255, 255, 0.3);
}

/* Filters */
.filters-section {
  margin-bottom: 16px;
}

.search-box {
  display: flex;
  align-items: center;
  gap: 10px;
  background: white;
  border-radius: 12px;
  padding: 12px 16px;
  margin-bottom: 10px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
}

.search-box i {
  color: #999;
}

.search-box input {
  flex: 1;
  border: none;
  outline: none;
  font-size: 14px;
  background: transparent;
}

.filter-row {
  display: flex;
  gap: 10px;
}

.filter-select,
.filter-date {
  flex: 1;
  padding: 12px;
  border: none;
  border-radius: 12px;
  font-size: 14px;
  background: white;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
  color: #333;
}

/* Loading & Empty */
.loading-state,
.empty-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 60px 20px;
  color: #999;
}

.spinner {
  width: 40px;
  height: 40px;
  border: 3px solid #f0f0f0;
  border-top-color: #667eea;
  border-radius: 50%;
  animation: spin 0.8s linear infinite;
  margin-bottom: 12px;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}

.empty-state i {
  font-size: 48px;
  margin-bottom: 12px;
  opacity: 0.5;
}

/* Citas List */
.citas-list {
  display: flex;
  flex-direction: column;
  gap: 10px;
}

.cita-card {
  background: white;
  border-radius: 18px;
  overflow: hidden;
  box-shadow: 0 4px 16px rgba(0, 0, 0, 0.08);
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  border: 1px solid #f0f0f0;
}

.cita-card:active {
  transform: scale(0.98);
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.12);
}

.cita-card:hover {
  box-shadow: 0 6px 20px rgba(102, 126, 234, 0.15);
  transform: translateY(-2px);
}

.cita-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 14px 18px;
  background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
  border-bottom: 1px solid #e0e0e0;
}

.cita-id {
  font-size: 13px;
  font-weight: 600;
  color: #666;
}

.status-badge {
  padding: 4px 10px;
  border-radius: 10px;
  font-size: 10px;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.3px;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.status-badge.pendiente { 
  background: linear-gradient(135deg, #fff3e0 0%, #ffe0b2 100%); 
  color: #e65100; 
}
.status-badge.confirmada { 
  background: linear-gradient(135deg, #e8f5e9 0%, #c8e6c9 100%); 
  color: #2e7d32; 
}
.status-badge.en_proceso { 
  background: linear-gradient(135deg, #e3f2fd 0%, #bbdefb 100%); 
  color: #1565c0; 
}
.status-badge.completada { 
  background: linear-gradient(135deg, #f3e5f5 0%, #e1bee7 100%); 
  color: #7b1fa2; 
}
.status-badge.cancelada { 
  background: linear-gradient(135deg, #ffebee 0%, #ffcdd2 100%); 
  color: #c62828; 
}
.status-badge.no_show { 
  background: linear-gradient(135deg, #fce4ec 0%, #f8bbd0 100%); 
  color: #c2185b; 
}

.cita-body {
  padding: 12px 14px;
  display: flex;
  flex-direction: column;
  gap: 8px;
  background: white;
}

.cita-datetime,
.cita-cliente,
.cita-empleado,
.cita-servicios {
  display: flex;
  align-items: center;
  gap: 10px;
  font-size: 13px;
  color: #333;
  padding: 4px 0;
}

.cita-datetime i,
.cita-cliente i,
.cita-empleado i,
.cita-servicios i {
  width: 20px;
  height: 20px;
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  border-radius: 6px;
  font-size: 10px;
  flex-shrink: 0;
}

.cita-cliente i {
  background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
}

.cita-empleado i {
  background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
}

.cita-servicios i {
  background: linear-gradient(135deg, #fa709a 0%, #fee140 100%);
}

.cita-datetime {
  font-weight: 700;
  color: #667eea;
  font-size: 15px;
}

.cita-servicios span {
  color: #666;
  line-height: 1.4;
}

.cita-footer {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 10px 14px;
  background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
  border-top: 1px solid #e0e0e0;
}

.cita-total {
  font-size: 16px;
  font-weight: 700;
  background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
}

.cita-actions {
  display: flex;
  gap: 8px;
}

.btn-icon-sm {
  width: 32px;
  height: 32px;
  border: none;
  border-radius: 8px;
  background: #f0f0f0;
  color: #666;
  font-size: 12px;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all 0.2s;
}

.btn-icon-sm:active {
  transform: scale(0.95);
}

.btn-icon-sm.danger {
  background: #ffebee;
  color: #c62828;
}

/* Pagination */
.pagination {
  display: flex;
  justify-content: center;
  align-items: center;
  gap: 16px;
  margin-top: 20px;
  padding: 16px;
}

.page-btn {
  width: 40px;
  height: 40px;
  border: none;
  border-radius: 10px;
  background: white;
  color: #667eea;
  font-size: 14px;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
}

.page-btn:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.page-info {
  font-size: 14px;
  color: #666;
  font-weight: 500;
}

/* Modal */
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

.coming-soon {
  text-align: center;
  color: #999;
  padding: 60px 40px;
  font-size: 16px;
}

/* Cita Detail */
.cita-detail {
  display: flex;
  flex-direction: column;
  gap: 20px;
}

.detail-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 20px;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  border-radius: 18px;
  color: white;
  margin-bottom: 12px;
  box-shadow: 0 4px 16px rgba(102, 126, 234, 0.3);
}

.detail-id {
  display: flex;
  flex-direction: column;
}

.id-label {
  font-size: 11px;
  opacity: 0.95;
  text-transform: uppercase;
  letter-spacing: 1px;
  margin-bottom: 4px;
}

.id-value {
  font-size: 28px;
  font-weight: 800;
  letter-spacing: -0.5px;
}

.status-badge-large {
  padding: 10px 18px;
  border-radius: 24px;
  font-size: 12px;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  background: rgba(255, 255, 255, 0.25);
  backdrop-filter: blur(10px);
  color: white;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
}

.detail-section {
  background: #f8f9fa;
  border-radius: 16px;
  padding: 18px;
  border: 1px solid #e9ecef;
  transition: all 0.2s;
}

.detail-section:hover {
  background: #f0f0f0;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
}

.section-title {
  display: flex;
  align-items: center;
  gap: 10px;
  margin-bottom: 14px;
  font-size: 12px;
  font-weight: 700;
  color: #667eea;
  text-transform: uppercase;
  letter-spacing: 1px;
}

.section-title i {
  font-size: 16px;
  width: 28px;
  height: 28px;
  display: flex;
  align-items: center;
  justify-content: center;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  border-radius: 8px;
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

.avatar-small {
  width: 48px;
  height: 48px;
  border-radius: 50%;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
  font-weight: 800;
  font-size: 16px;
  flex-shrink: 0;
  box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
  border: 3px solid white;
}

.avatar-small.empleado {
  background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
  box-shadow: 0 4px 12px rgba(17, 153, 142, 0.3);
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
  color: #667eea;
  text-decoration: none;
}

.phone-link:active {
  opacity: 0.7;
}

.info-item i {
  width: 20px;
  color: #667eea;
  text-align: center;
  flex-shrink: 0;
}

.servicios-list {
  display: flex;
  flex-direction: column;
  gap: 10px;
}

.servicio-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 14px;
  background: white;
  border-radius: 12px;
  border: 1px solid #e9ecef;
  transition: all 0.2s;
}

.servicio-item:hover {
  background: #f8f9fa;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
  transform: translateX(4px);
}

.servicio-info {
  display: flex;
  flex-direction: column;
  gap: 6px;
  flex: 1;
}

.servicio-nombre {
  font-size: 15px;
  font-weight: 700;
  color: #333;
}

.servicio-duracion {
  font-size: 12px;
  color: #999;
  font-weight: 500;
}

.servicio-precio {
  font-size: 18px;
  font-weight: 800;
  background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
}

.total-section {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  box-shadow: 0 4px 16px rgba(102, 126, 234, 0.3);
  border: none;
}

.total-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.total-label {
  font-size: 14px;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 1px;
  opacity: 0.95;
}

.total-value {
  font-size: 32px;
  font-weight: 800;
  letter-spacing: -1px;
}

.notas-content {
  padding: 16px;
  background: white;
  border-radius: 12px;
  font-size: 14px;
  color: #666;
  line-height: 1.7;
  border: 1px solid #e9ecef;
  font-style: italic;
}

.detail-actions {
  display: flex;
  flex-direction: column;
  gap: 12px;
  margin-top: 12px;
}

.btn-action-primary,
.btn-action-secondary,
.btn-action-danger {
  width: 100%;
  padding: 16px;
  border: none;
  border-radius: 14px;
  font-size: 15px;
  font-weight: 700;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 10px;
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
  letter-spacing: 0.3px;
}

.btn-action-primary {
  background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
  color: white;
}

.btn-action-primary:hover {
  box-shadow: 0 6px 20px rgba(17, 153, 142, 0.4);
  transform: translateY(-2px);
}

.btn-action-secondary {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
}

.btn-action-secondary:hover {
  box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4);
  transform: translateY(-2px);
}

.btn-action-danger {
  background: linear-gradient(135deg, #fa709a 0%, #fee140 100%);
  color: white;
}

.btn-action-danger:hover {
  box-shadow: 0 6px 20px rgba(250, 112, 154, 0.4);
  transform: translateY(-2px);
}

.btn-action-primary:active,
.btn-action-secondary:active,
.btn-action-danger:active {
  transform: translateY(0) scale(0.98);
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
}
</style>
