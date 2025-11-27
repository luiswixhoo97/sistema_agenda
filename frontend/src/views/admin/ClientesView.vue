<template>
  <div class="admin-view">
    <!-- Header -->
    <div class="view-header">
      <div class="header-info">
        <div class="header-icon">
          <i class="fa fa-users"></i>
        </div>
        <div class="header-text">
          <h1>Clientes</h1>
          <p class="header-subtitle">{{ totalClientes }} registrados</p>
        </div>
      </div>
      <button class="btn-action" @click="nuevoCliente">
        <i class="fa fa-plus"></i>
      </button>
    </div>

    <!-- Search -->
    <div class="search-section">
      <div class="search-box">
        <i class="fa fa-search"></i>
        <input 
          v-model="busqueda" 
          type="text" 
          placeholder="Buscar por nombre o teléfono..." 
          @input="debouncedSearch"
        />
        <button v-if="busqueda" class="clear-btn" @click="busqueda = ''; cargarClientes()">
          <i class="fa fa-times"></i>
        </button>
      </div>
    </div>

    <!-- Clientes List -->
    <div class="clientes-container">
      <div v-if="loading" class="loading-state">
        <div class="spinner"></div>
        <p>Cargando clientes...</p>
      </div>
      
      <div v-else-if="clientes.length === 0" class="empty-state">
        <i class="fa fa-user-slash"></i>
        <p>No se encontraron clientes</p>
      </div>
      
      <div v-else class="clientes-list">
        <div 
          v-for="cliente in clientes" 
          :key="cliente.id" 
          class="cliente-card"
          @click="verCliente(cliente)"
        >
          <div class="cliente-avatar">
            {{ getInitials(cliente.nombre) }}
          </div>
          
          <div class="cliente-info">
            <h3 class="cliente-nombre">{{ cliente.nombre }}</h3>
            <div class="cliente-contacto">
              <span class="contacto-item">
                <i class="fa fa-phone"></i>
                {{ cliente.telefono }}
              </span>
              <span v-if="cliente.email" class="contacto-item">
                <i class="fa fa-envelope"></i>
                {{ cliente.email }}
              </span>
            </div>
          </div>
          
          <div class="cliente-stats">
            <div class="stat">
              <span class="stat-value">{{ cliente.citas_count || 0 }}</span>
              <span class="stat-label">Citas</span>
            </div>
          </div>
          
          <div class="cliente-actions">
            <button class="btn-icon-sm" @click.stop="editarCliente(cliente)">
              <i class="fa fa-edit"></i>
            </button>
            <button class="btn-icon-sm" @click.stop="llamarCliente(cliente)">
              <i class="fa fa-phone"></i>
            </button>
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

    <!-- Modal -->
    <Teleport to="body">
      <div v-if="showModal" class="modal-overlay" @click.self="closeModal">
        <div class="modal-content">
          <div class="modal-header">
            <h3>{{ isEditing ? 'Editar Cliente' : (selectedCliente ? 'Detalle Cliente' : 'Nuevo Cliente') }}</h3>
            <button class="modal-close" @click="closeModal">
              <i class="fa fa-times"></i>
            </button>
          </div>
          <div class="modal-body" v-if="selectedCliente || isEditing">
            <!-- Modo Edición -->
            <div v-if="isEditing" class="edit-form">
              <div class="form-group">
                <label>Nombre completo</label>
                <input 
                  v-model="formData.nombre" 
                  type="text" 
                  placeholder="Nombre del cliente"
                  class="form-input"
                />
              </div>
              <div class="form-group">
                <label>Teléfono</label>
                <input 
                  v-model="formData.telefono" 
                  type="tel" 
                  placeholder="Teléfono de contacto"
                  class="form-input"
                />
              </div>
              <div class="form-group">
                <label>Correo electrónico (opcional)</label>
                <input 
                  v-model="formData.email" 
                  type="email" 
                  placeholder="correo@ejemplo.com"
                  class="form-input"
                />
              </div>
              <div class="form-actions">
                <button class="btn-secondary" @click="cancelarEdicion">
                  Cancelar
                </button>
                <button class="btn-primary" @click="guardarCliente">
                  <i class="fa fa-save"></i>
                  Guardar Cambios
                </button>
              </div>
            </div>
            
            <!-- Modo Vista -->
            <div v-else>
              <div class="detail-section">
                <div class="detail-avatar">
                  {{ getInitials(selectedCliente.nombre) }}
                </div>
                <h2>{{ selectedCliente.nombre }}</h2>
              </div>
              
              <div class="detail-info">
                <div class="info-row">
                  <i class="fa fa-phone"></i>
                  <span>{{ selectedCliente.telefono }}</span>
                </div>
                <div class="info-row" v-if="selectedCliente.email">
                  <i class="fa fa-envelope"></i>
                  <span>{{ selectedCliente.email }}</span>
                </div>
                <div class="info-row">
                  <i class="fa fa-calendar-check"></i>
                  <span>{{ selectedCliente.citas_count || 0 }} citas realizadas</span>
                </div>
                <div class="info-row" v-if="selectedCliente.ultima_visita">
                  <i class="fa fa-clock"></i>
                  <span>Última visita: {{ formatFecha(selectedCliente.ultima_visita) }}</span>
                </div>
              </div>
              
              <div class="detail-actions">
                <button class="btn-primary" @click="agendarCita(selectedCliente)">
                  <i class="fa fa-calendar-plus"></i>
                  Agendar Cita
                </button>
                <button class="btn-secondary" @click="verHistorial(selectedCliente)">
                  <i class="fa fa-history"></i>
                  Ver Historial
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>
    </Teleport>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue';
import { getClientes, updateCliente } from '@/services/adminService';

const clientes = ref<any[]>([]);
const busqueda = ref('');
const loading = ref(true);
const showModal = ref(false);
const selectedCliente = ref<any>(null);
const isEditing = ref(false);
const formData = ref({
  nombre: '',
  telefono: '',
  email: '',
});
const pagination = ref({
  current_page: 1,
  last_page: 1,
  total: 0,
});

let searchTimeout: ReturnType<typeof setTimeout>;

const totalClientes = computed(() => pagination.value.total);

async function cargarClientes(page = 1) {
  loading.value = true;
  try {
    const params: any = { 
      per_page: 15,
      page,
    };
    
    if (busqueda.value) {
      params.buscar = busqueda.value;
    }
    
    const response = await getClientes(params);
    if (response.success) {
      clientes.value = response.data || [];
      if (response.pagination) {
        pagination.value = response.pagination;
      }
    }
  } catch (error) {
    console.error('Error cargando clientes:', error);
  } finally {
    loading.value = false;
  }
}

function debouncedSearch() {
  clearTimeout(searchTimeout);
  searchTimeout = setTimeout(() => {
    cargarClientes(1);
  }, 500);
}

function cambiarPagina(page: number) {
  cargarClientes(page);
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

function formatFecha(fecha: string): string {
  if (!fecha) return '--';
  return new Date(fecha).toLocaleDateString('es-MX', { 
    day: 'numeric', 
    month: 'short',
    year: 'numeric'
  });
}

function nuevoCliente() { 
  selectedCliente.value = null;
  showModal.value = true;
}

function verCliente(cliente: any) { 
  selectedCliente.value = cliente;
  showModal.value = true;
}

function editarCliente(c: any) { 
  selectedCliente.value = c;
  isEditing.value = true;
  formData.value = {
    nombre: c.nombre || '',
    telefono: c.telefono || '',
    email: c.email || '',
  };
  showModal.value = true;
}

function llamarCliente(c: any) { 
  window.location.href = `tel:${c.telefono}`;
}

function agendarCita(c: any) {
  console.log('Agendar cita para', c);
}

function verHistorial(c: any) {
  console.log('Ver historial de', c);
}

async function guardarCliente() {
  if (!selectedCliente.value) return;
  
  if (!formData.value.nombre || !formData.value.telefono) {
    alert('El nombre y teléfono son obligatorios');
    return;
  }
  
  try {
    const response = await updateCliente(selectedCliente.value.id, {
      nombre: formData.value.nombre,
      telefono: formData.value.telefono,
      email: formData.value.email || null,
    });
    
    if (response.success) {
      // Actualizar el cliente en la lista
      const index = clientes.value.findIndex(c => c.id === selectedCliente.value.id);
      if (index !== -1) {
        clientes.value[index] = { ...clientes.value[index], ...formData.value };
      }
      closeModal();
    } else {
      alert('Error al guardar los cambios');
    }
  } catch (error) {
    console.error('Error guardando cliente:', error);
    alert('Error al guardar los cambios');
  }
}

function cancelarEdicion() {
  isEditing.value = false;
  formData.value = {
    nombre: '',
    telefono: '',
    email: '',
  };
  closeModal();
}

function closeModal() {
  showModal.value = false;
  selectedCliente.value = null;
  isEditing.value = false;
  formData.value = {
    nombre: '',
    telefono: '',
    email: '',
  };
}

onMounted(() => {
  cargarClientes();
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
  background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
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
}

/* Search */
.search-section {
  margin-bottom: 16px;
}

.search-box {
  display: flex;
  align-items: center;
  gap: 10px;
  background: white;
  border-radius: 12px;
  padding: 12px 16px;
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

.clear-btn {
  background: none;
  border: none;
  color: #999;
  cursor: pointer;
  padding: 4px;
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
  border-top-color: #11998e;
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

/* Clientes List */
.clientes-list {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.cliente-card {
  display: flex;
  align-items: center;
  gap: 12px;
  background: white;
  border-radius: 16px;
  padding: 16px;
  box-shadow: 0 2px 12px rgba(0, 0, 0, 0.08);
  transition: transform 0.2s;
}

.cliente-card:active {
  transform: scale(0.98);
}

.cliente-avatar {
  width: 50px;
  height: 50px;
  background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
  font-weight: 700;
  font-size: 18px;
  flex-shrink: 0;
}

.cliente-info {
  flex: 1;
  min-width: 0;
}

.cliente-nombre {
  margin: 0 0 4px;
  font-size: 15px;
  font-weight: 600;
  color: #333;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.cliente-contacto {
  display: flex;
  flex-direction: column;
  gap: 2px;
}

.contacto-item {
  display: flex;
  align-items: center;
  gap: 6px;
  font-size: 12px;
  color: #666;
}

.contacto-item i {
  width: 14px;
  color: #11998e;
  font-size: 11px;
}

.cliente-stats {
  text-align: center;
  padding: 0 12px;
}

.stat {
  display: flex;
  flex-direction: column;
}

.stat-value {
  font-size: 20px;
  font-weight: 700;
  color: #11998e;
}

.stat-label {
  font-size: 10px;
  color: #999;
  text-transform: uppercase;
}

.cliente-actions {
  display: flex;
  flex-direction: column;
  gap: 6px;
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
  color: #11998e;
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

.detail-section {
  text-align: center;
  margin-bottom: 24px;
}

.detail-avatar {
  width: 80px;
  height: 80px;
  background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
  font-weight: 700;
  font-size: 28px;
  margin: 0 auto 12px;
}

.detail-section h2 {
  margin: 0;
  font-size: 20px;
  color: #333;
}

.detail-info {
  background: #f8f9fa;
  border-radius: 12px;
  padding: 16px;
  margin-bottom: 20px;
}

.info-row {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 10px 0;
  border-bottom: 1px solid #eee;
}

.info-row:last-child {
  border-bottom: none;
}

.info-row i {
  width: 20px;
  color: #11998e;
  text-align: center;
}

.info-row span {
  color: #333;
  font-size: 14px;
}

.detail-actions {
  display: flex;
  flex-direction: column;
  gap: 10px;
}

.btn-primary,
.btn-secondary {
  width: 100%;
  padding: 14px;
  border: none;
  border-radius: 12px;
  font-size: 15px;
  font-weight: 600;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
}

.btn-primary {
  background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
  color: white;
}

.btn-secondary {
  background: #f0f0f0;
  color: #333;
}

/* Edit Form */
.edit-form {
  padding: 8px 0;
}

.form-group {
  margin-bottom: 20px;
}

.form-group label {
  display: block;
  font-size: 12px;
  font-weight: 600;
  color: #666;
  margin-bottom: 8px;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.form-input {
  width: 100%;
  padding: 12px 16px;
  border: 1px solid #e0e0e0;
  border-radius: 12px;
  font-size: 15px;
  color: #333;
  background: #f8f9fa;
  transition: all 0.2s;
  box-sizing: border-box;
}

.form-input:focus {
  outline: none;
  border-color: #11998e;
  background: white;
  box-shadow: 0 0 0 3px rgba(17, 153, 142, 0.1);
}

.form-actions {
  display: flex;
  gap: 12px;
  margin-top: 24px;
}

.form-actions .btn-primary,
.form-actions .btn-secondary {
  flex: 1;
}
</style>
