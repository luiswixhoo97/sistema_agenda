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

    <!-- Modal Nueva/Editar Cita -->
    <Teleport to="body">
      <div v-if="showModal" class="modal-overlay" @click.self="closeModal">
        <div class="modal-content">
          <div class="modal-header">
            <h3>{{ editingCita ? 'Editar Cita' : 'Nueva Cita' }}</h3>
            <button class="modal-close" @click="closeModal">
              <i class="fa fa-times"></i>
            </button>
          </div>
          <div class="modal-body">
            <p class="coming-soon">Formulario en desarrollo...</p>
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
  console.log('Ver', cita); 
}

function editarCita(cita: any) { 
  editingCita.value = cita;
  showModal.value = true;
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
  gap: 12px;
}

.cita-card {
  background: white;
  border-radius: 16px;
  overflow: hidden;
  box-shadow: 0 2px 12px rgba(0, 0, 0, 0.08);
  transition: transform 0.2s, box-shadow 0.2s;
}

.cita-card:active {
  transform: scale(0.98);
}

.cita-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 12px 16px;
  background: #f8f9fa;
  border-bottom: 1px solid #f0f0f0;
}

.cita-id {
  font-size: 13px;
  font-weight: 600;
  color: #666;
}

.status-badge {
  padding: 4px 10px;
  border-radius: 8px;
  font-size: 11px;
  font-weight: 600;
  text-transform: uppercase;
}

.status-badge.pendiente { background: #fff3e0; color: #e65100; }
.status-badge.confirmada { background: #e8f5e9; color: #2e7d32; }
.status-badge.en_proceso { background: #e3f2fd; color: #1565c0; }
.status-badge.completada { background: #f3e5f5; color: #7b1fa2; }
.status-badge.cancelada { background: #ffebee; color: #c62828; }
.status-badge.no_show { background: #fce4ec; color: #c2185b; }

.cita-body {
  padding: 16px;
  display: flex;
  flex-direction: column;
  gap: 10px;
}

.cita-datetime,
.cita-cliente,
.cita-empleado,
.cita-servicios {
  display: flex;
  align-items: center;
  gap: 10px;
  font-size: 14px;
  color: #333;
}

.cita-datetime i,
.cita-cliente i,
.cita-empleado i,
.cita-servicios i {
  width: 20px;
  color: #667eea;
  text-align: center;
}

.cita-datetime {
  font-weight: 600;
  color: #667eea;
}

.cita-servicios span {
  color: #666;
}

.cita-footer {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 12px 16px;
  background: #f8f9fa;
  border-top: 1px solid #f0f0f0;
}

.cita-total {
  font-size: 18px;
  font-weight: 700;
  color: #11998e;
}

.cita-actions {
  display: flex;
  gap: 8px;
}

.btn-icon-sm {
  width: 36px;
  height: 36px;
  border: none;
  border-radius: 10px;
  background: #f0f0f0;
  color: #666;
  font-size: 14px;
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
  background: rgba(0, 0, 0, 0.5);
  display: flex;
  align-items: flex-end;
  justify-content: center;
  z-index: 1000;
  animation: fadeIn 0.2s ease;
}

@keyframes fadeIn {
  from { opacity: 0; }
  to { opacity: 1; }
}

.modal-content {
  background: white;
  width: 100%;
  max-height: 90vh;
  border-radius: 24px 24px 0 0;
  overflow: hidden;
  animation: slideUp 0.3s ease;
}

@keyframes slideUp {
  from { transform: translateY(100%); }
  to { transform: translateY(0); }
}

.modal-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 20px;
  border-bottom: 1px solid #f0f0f0;
}

.modal-header h3 {
  margin: 0;
  font-size: 18px;
  font-weight: 600;
  color: #1a1a2e;
}

.modal-close {
  width: 36px;
  height: 36px;
  border: none;
  border-radius: 50%;
  background: #f0f0f0;
  color: #666;
  font-size: 16px;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
}

.modal-body {
  padding: 20px;
  overflow-y: auto;
  max-height: calc(90vh - 80px);
}

.coming-soon {
  text-align: center;
  color: #999;
  padding: 40px;
}
</style>
