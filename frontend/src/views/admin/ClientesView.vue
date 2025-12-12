<template>
  <div class="clientes-view">
    <!-- Header -->
    <div class="clientes-header">
      <div class="header-left">
        <div class="header-icon">
          <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
            <circle cx="12" cy="7" r="4"></circle>
          </svg>
        </div>
        <div class="header-text">
          <h1>Clientes</h1>
          <p class="header-subtitle">{{ totalClientes }} registrados</p>
        </div>
      </div>
      <button class="btn-new-cliente" @click="nuevoCliente">
        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <line x1="12" y1="5" x2="12" y2="19"></line>
          <line x1="5" y1="12" x2="19" y2="12"></line>
        </svg>
        Nuevo Cliente
      </button>
    </div>

    <!-- Search -->
    <div class="search-section">
      <div class="search-box">
        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <circle cx="11" cy="11" r="8"></circle>
          <path d="m21 21-4.35-4.35"></path>
        </svg>
        <input 
          v-model="busqueda" 
          type="text" 
          placeholder="Buscar por nombre o teléfono..." 
          @input="debouncedSearch"
        />
        <button v-if="busqueda" class="clear-btn" @click="busqueda = ''; cargarClientes()">
          <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <line x1="18" y1="6" x2="6" y2="18"></line>
            <line x1="6" y1="6" x2="18" y2="18"></line>
          </svg>
        </button>
      </div>
    </div>

    <!-- Clientes List -->
    <div class="clientes-container">
      <div v-if="loading" class="loading-container">
        <div class="loader"></div>
        <p>Cargando clientes...</p>
      </div>
      
      <div v-else-if="clientes.length === 0" class="empty-state">
        <div class="empty-icon">
          <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
            <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
            <circle cx="12" cy="7" r="4"></circle>
          </svg>
        </div>
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
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"></path>
                </svg>
                {{ cliente.telefono }}
              </span>
              <span v-if="cliente.email" class="contacto-item">
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path>
                  <polyline points="22,6 12,13 2,6"></polyline>
                </svg>
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
            <button class="action-btn" @click.stop="editarCliente(cliente)" title="Editar">
              <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
              </svg>
            </button>
            <button class="action-btn" @click.stop="llamarCliente(cliente)" title="Llamar">
              <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"></path>
              </svg>
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
          <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <polyline points="15 18 9 12 15 6"></polyline>
          </svg>
        </button>
        <span class="page-info">{{ pagination.current_page }} / {{ pagination.last_page }}</span>
        <button 
          class="page-btn" 
          :disabled="pagination.current_page === pagination.last_page"
          @click="cambiarPagina(pagination.current_page + 1)"
        >
          <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <polyline points="9 18 15 12 9 6"></polyline>
          </svg>
        </button>
      </div>
    </div>

    <!-- Modal -->
    <Teleport to="body">
      <div v-if="showModal" class="modal-overlay" @click.self="closeModal">
        <div class="modal-content">
          <div class="modal-header">
            <h3>{{ isEditing && selectedCliente ? 'Editar Cliente' : (isEditing ? 'Nuevo Cliente' : 'Detalle Cliente') }}</h3>
            <button class="modal-close" @click="closeModal">
              <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <line x1="18" y1="6" x2="6" y2="18"></line>
                <line x1="6" y1="6" x2="18" y2="18"></line>
              </svg>
            </button>
          </div>
          <div class="modal-body">
            <!-- Modo Edición/Nuevo -->
            <div v-if="isEditing" class="edit-form">
              <div class="form-section">
                <label class="form-label">
                  <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                    <circle cx="12" cy="7" r="4"></circle>
                  </svg>
                  Nombre completo <span class="required">*</span>
                </label>
                <input 
                  v-model="formData.nombre" 
                  type="text" 
                  placeholder="Nombre del cliente"
                  class="form-input"
                  required
                />
              </div>
              <div class="form-section">
                <label class="form-label">
                  <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"></path>
                  </svg>
                  Teléfono <span class="required">*</span>
                </label>
                <input 
                  v-model="formData.telefono" 
                  type="tel" 
                  placeholder="Teléfono de contacto"
                  class="form-input"
                  required
                />
              </div>
              <div class="form-section">
                <label class="form-label">
                  <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path>
                    <polyline points="22,6 12,13 2,6"></polyline>
                  </svg>
                  Correo electrónico <span class="optional">(opcional)</span>
                </label>
                <input 
                  v-model="formData.email" 
                  type="email" 
                  placeholder="correo@ejemplo.com"
                  class="form-input"
                />
              </div>
              <div class="form-actions">
                <button type="button" class="btn-cancel" @click="cancelarEdicion">
                  Cancelar
                </button>
                <button type="button" class="btn-submit" @click="guardarCliente">
                  <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path>
                    <polyline points="17 21 17 13 7 13 7 21"></polyline>
                    <polyline points="7 3 7 8 15 8"></polyline>
                  </svg>
                  Guardar Cambios
                </button>
              </div>
            </div>
            
            <!-- Modo Vista -->
            <div v-else class="detail-view">
              <div class="detail-section">
                <div class="detail-avatar">
                  {{ getInitials(selectedCliente.nombre) }}
                </div>
                <h2>{{ selectedCliente.nombre }}</h2>
              </div>
              
              <div class="detail-info">
                <div class="info-row">
                  <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"></path>
                  </svg>
                  <span>{{ selectedCliente.telefono }}</span>
                </div>
                <div class="info-row" v-if="selectedCliente.email">
                  <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path>
                    <polyline points="22,6 12,13 2,6"></polyline>
                  </svg>
                  <span>{{ selectedCliente.email }}</span>
                </div>
                <div class="info-row">
                  <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                    <line x1="16" y1="2" x2="16" y2="6"></line>
                    <line x1="8" y1="2" x2="8" y2="6"></line>
                    <line x1="3" y1="10" x2="21" y2="10"></line>
                  </svg>
                  <span>{{ selectedCliente.citas_count || 0 }} citas realizadas</span>
                </div>
                <div class="info-row" v-if="selectedCliente.ultima_visita">
                  <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="12" r="10"></circle>
                    <polyline points="12 6 12 12 16 14"></polyline>
                  </svg>
                  <span>Última visita: {{ formatFecha(selectedCliente.ultima_visita) }}</span>
                </div>
              </div>
              
              <div class="detail-actions">
                <button class="btn-primary" @click="agendarCita(selectedCliente)">
                  <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                    <line x1="16" y1="2" x2="16" y2="6"></line>
                    <line x1="8" y1="2" x2="8" y2="6"></line>
                    <line x1="3" y1="10" x2="21" y2="10"></line>
                    <line x1="12" y1="14" x2="12" y2="18"></line>
                    <line x1="8" y1="16" x2="16" y2="16"></line>
                  </svg>
                  Agendar Cita
                </button>
                <button class="btn-secondary" @click="verHistorial(selectedCliente)">
                  <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="1 4 1 10 7 10"></polyline>
                    <path d="M3.51 15a9 9 0 1 0 2.13-9.36L1 10"></path>
                  </svg>
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
import { getClientes, updateCliente, createCliente } from '@/services/adminService';
import Swal from 'sweetalert2';

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
  isEditing.value = true;
  formData.value = {
    nombre: '',
    telefono: '',
    email: '',
  };
  showModal.value = true;
}

function verCliente(cliente: any) { 
  selectedCliente.value = cliente;
  isEditing.value = false;
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
  if (!formData.value.nombre || !formData.value.telefono) {
    Swal.fire({
      icon: 'warning',
      title: 'Campos requeridos',
      text: 'El nombre y teléfono son obligatorios',
      confirmButtonColor: '#ff3b30'
    });
    return;
  }
  
  try {
    let response;
    
    // Si es nuevo cliente (selectedCliente es null)
    if (!selectedCliente.value) {
      response = await createCliente({
        nombre: formData.value.nombre.trim(),
        telefono: formData.value.telefono.trim(),
        email: formData.value.email?.trim() || null,
      });
      
      if (response.success) {
        Swal.fire({
          icon: 'success',
          title: '¡Éxito!',
          text: 'Cliente creado correctamente',
          confirmButtonColor: '#34c759',
          timer: 1500,
          showConfirmButton: false
        });
        await cargarClientes(pagination.value.current_page);
        closeModal();
      } else {
        Swal.fire({
          icon: 'error',
          title: 'Error',
          text: response.message || 'Error al crear el cliente',
          confirmButtonColor: '#ff3b30'
        });
      }
    } else {
      // Si es edición (selectedCliente tiene id)
      response = await updateCliente(selectedCliente.value.id, {
        nombre: formData.value.nombre.trim(),
        telefono: formData.value.telefono.trim(),
        email: formData.value.email?.trim() || null,
      });
      
      if (response.success) {
        Swal.fire({
          icon: 'success',
          title: '¡Éxito!',
          text: 'Cliente actualizado correctamente',
          confirmButtonColor: '#34c759',
          timer: 1500,
          showConfirmButton: false
        });
        // Actualizar el cliente en la lista
        const index = clientes.value.findIndex(c => c.id === selectedCliente.value.id);
        if (index !== -1) {
          clientes.value[index] = { ...clientes.value[index], ...formData.value };
        }
        closeModal();
      } else {
        Swal.fire({
          icon: 'error',
          title: 'Error',
          text: response.message || 'Error al guardar los cambios',
          confirmButtonColor: '#ff3b30'
        });
      }
    }
  } catch (error: any) {
    console.error('Error guardando cliente:', error);
    Swal.fire({
      icon: 'error',
      title: 'Error',
      text: error.response?.data?.message || 'Error al guardar los cambios',
      confirmButtonColor: '#ff3b30'
    });
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
/* ===== Apple-inspired Clientes View Design ===== */

.clientes-view {
  min-height: 100vh;
  background: #f5f5f7;
  padding: 24px;
  padding-bottom: 120px;
  font-family: -apple-system, BlinkMacSystemFont, 'SF Pro Display', 'SF Pro Text', 'Helvetica Neue', sans-serif;
  -webkit-font-smoothing: antialiased;
  -moz-osx-font-smoothing: grayscale;
}

/* Header */
.clientes-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 24px;
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

.header-subtitle {
  font-size: 13px;
  opacity: 0.7;
  margin: 4px 0 0;
}

.btn-new-cliente {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 12px 20px;
  background: rgba(255, 255, 255, 0.15);
  border: 1px solid rgba(255, 255, 255, 0.2);
  border-radius: 12px;
  color: white;
  font-size: 15px;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.2s;
  backdrop-filter: blur(10px);
}

.btn-new-cliente:active {
  background: rgba(255, 255, 255, 0.25);
  transform: scale(0.98);
}

/* Search */
.search-section {
  margin-bottom: 20px;
}

.search-box {
  display: flex;
  align-items: center;
  gap: 12px;
  background: #ffffff;
  border-radius: 16px;
  padding: 14px 18px;
  box-shadow: 0 2px 12px rgba(0, 0, 0, 0.08);
  border: 1px solid #e5e5ea;
}

.search-box svg {
  color: #86868b;
  flex-shrink: 0;
}

.search-box input {
  flex: 1;
  border: none;
  outline: none;
  font-size: 15px;
  background: transparent;
  color: #1d1d1f;
}

.search-box input::placeholder {
  color: #86868b;
}

.clear-btn {
  background: none;
  border: none;
  color: #86868b;
  cursor: pointer;
  padding: 4px;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: color 0.2s;
}

.clear-btn:active {
  color: #1d1d1f;
}

/* Loading & Empty */
.loading-container,
.empty-state {
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

.loading-container p,
.empty-state p {
  color: #86868b;
  font-size: 15px;
  margin: 0;
}

.empty-icon {
  color: #d1d1d6;
  margin-bottom: 16px;
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
  gap: 16px;
  background: #ffffff;
  border-radius: 16px;
  padding: 18px;
  box-shadow: 0 2px 12px rgba(0, 0, 0, 0.08);
  border: 1px solid #e5e5ea;
  transition: all 0.2s;
  cursor: pointer;
}

.cliente-card:active {
  transform: scale(0.98);
  box-shadow: 0 1px 6px rgba(0, 0, 0, 0.12);
}

.cliente-avatar {
  width: 56px;
  height: 56px;
  background: linear-gradient(135deg, #007aff 0%, #5856d6 100%);
  border-radius: 14px;
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
  font-weight: 700;
  font-size: 20px;
  flex-shrink: 0;
  box-shadow: 0 2px 8px rgba(0, 122, 255, 0.3);
}

.cliente-info {
  flex: 1;
  min-width: 0;
}

.cliente-nombre {
  margin: 0 0 6px;
  font-size: 16px;
  font-weight: 600;
  color: #1d1d1f;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.cliente-contacto {
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.contacto-item {
  display: flex;
  align-items: center;
  gap: 8px;
  font-size: 13px;
  color: #86868b;
}

.contacto-item svg {
  color: #007aff;
  flex-shrink: 0;
}

.cliente-stats {
  text-align: center;
  padding: 0 12px;
}

.stat {
  display: flex;
  flex-direction: column;
  align-items: center;
}

.stat-value {
  font-size: 22px;
  font-weight: 700;
  color: #007aff;
  line-height: 1;
}

.stat-label {
  font-size: 11px;
  color: #86868b;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  margin-top: 4px;
}

.cliente-actions {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.action-btn {
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

.action-btn:active {
  background: #e5e5ea;
  transform: scale(0.95);
}

/* Pagination */
.pagination {
  display: flex;
  justify-content: center;
  align-items: center;
  gap: 20px;
  margin-top: 24px;
  padding: 20px;
}

.page-btn {
  width: 44px;
  height: 44px;
  border: none;
  border-radius: 12px;
  background: #ffffff;
  color: #007aff;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
  border: 1px solid #e5e5ea;
  transition: all 0.2s;
}

.page-btn:active:not(:disabled) {
  transform: scale(0.95);
  box-shadow: 0 1px 4px rgba(0, 0, 0, 0.12);
}

.page-btn:disabled {
  opacity: 0.4;
  cursor: not-allowed;
}

.page-info {
  font-size: 15px;
  color: #1d1d1f;
  font-weight: 500;
}

/* Modal */
.modal-overlay {
  position: fixed;
  inset: 0;
  background: rgba(0, 0, 0, 0.4);
  backdrop-filter: blur(8px);
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
  background: #ffffff;
  width: 100%;
  max-height: 90vh;
  border-radius: 24px 24px 0 0;
  overflow: hidden;
  animation: slideUp 0.3s cubic-bezier(0.16, 1, 0.3, 1);
  box-shadow: 0 -4px 24px rgba(0, 0, 0, 0.15);
}

@keyframes slideUp {
  from { transform: translateY(100%); }
  to { transform: translateY(0); }
}

.modal-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 20px 24px;
  border-bottom: 1px solid #e5e5ea;
}

.modal-header h3 {
  margin: 0;
  font-size: 20px;
  font-weight: 600;
  color: #1d1d1f;
  letter-spacing: -0.3px;
}

.modal-close {
  width: 36px;
  height: 36px;
  border: none;
  border-radius: 10px;
  background: #f5f5f7;
  color: #1d1d1f;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all 0.2s;
}

.modal-close:active {
  background: #e5e5ea;
  transform: scale(0.95);
}

.modal-body {
  padding: 24px;
  overflow-y: auto;
  max-height: calc(90vh - 80px);
}

/* Detail View */
.detail-view {
  padding: 8px 0;
}

.detail-section {
  text-align: center;
  margin-bottom: 28px;
}

.detail-avatar {
  width: 88px;
  height: 88px;
  background: linear-gradient(135deg, #007aff 0%, #5856d6 100%);
  border-radius: 20px;
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
  font-weight: 700;
  font-size: 32px;
  margin: 0 auto 16px;
  box-shadow: 0 4px 16px rgba(0, 122, 255, 0.3);
}

.detail-section h2 {
  margin: 0;
  font-size: 22px;
  font-weight: 600;
  color: #1d1d1f;
  letter-spacing: -0.3px;
}

.detail-info {
  background: #f5f5f7;
  border-radius: 16px;
  padding: 20px;
  margin-bottom: 24px;
}

.info-row {
  display: flex;
  align-items: center;
  gap: 14px;
  padding: 14px 0;
  border-bottom: 1px solid #e5e5ea;
}

.info-row:last-child {
  border-bottom: none;
  padding-bottom: 0;
}

.info-row svg {
  color: #007aff;
  flex-shrink: 0;
}

.info-row span {
  color: #1d1d1f;
  font-size: 15px;
}

.detail-actions {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.btn-primary,
.btn-secondary {
  width: 100%;
  padding: 16px;
  border: none;
  border-radius: 14px;
  font-size: 16px;
  font-weight: 600;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 10px;
  transition: all 0.2s;
}

.btn-primary {
  background: linear-gradient(135deg, #007aff 0%, #5856d6 100%);
  color: white;
  box-shadow: 0 4px 12px rgba(0, 122, 255, 0.3);
}

.btn-primary:active {
  transform: scale(0.98);
  box-shadow: 0 2px 8px rgba(0, 122, 255, 0.4);
}

.btn-secondary {
  background: #f5f5f7;
  color: #1d1d1f;
  border: 1px solid #e5e5ea;
}

.btn-secondary:active {
  background: #e5e5ea;
  transform: scale(0.98);
}

/* Edit Form */
.edit-form {
  padding: 8px 0;
}

.form-section {
  margin-bottom: 24px;
}

.form-label {
  display: flex;
  align-items: center;
  gap: 8px;
  font-size: 13px;
  font-weight: 600;
  color: #1d1d1f;
  margin-bottom: 10px;
  letter-spacing: -0.1px;
}

.form-label svg {
  color: #007aff;
  flex-shrink: 0;
}

.required {
  color: #ff3b30;
}

.optional {
  color: #86868b;
  font-weight: 400;
}

.form-input {
  width: 100%;
  padding: 14px 16px;
  border: 1px solid #e5e5ea;
  border-radius: 12px;
  font-size: 15px;
  color: #1d1d1f;
  background: #f5f5f7;
  transition: all 0.2s;
  box-sizing: border-box;
  font-family: inherit;
}

.form-input:focus {
  outline: none;
  border-color: #007aff;
  background: #ffffff;
  box-shadow: 0 0 0 4px rgba(0, 122, 255, 0.1);
}

.form-input::placeholder {
  color: #86868b;
}

.form-actions {
  display: flex;
  gap: 12px;
  margin-top: 32px;
}

.btn-cancel,
.btn-submit {
  flex: 1;
  padding: 14px 20px;
  border: none;
  border-radius: 12px;
  font-size: 15px;
  font-weight: 600;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  transition: all 0.2s;
}

.btn-cancel {
  background: #f5f5f7;
  color: #1d1d1f;
  border: 1px solid #e5e5ea;
}

.btn-cancel:active {
  background: #e5e5ea;
  transform: scale(0.98);
}

.btn-submit {
  background: linear-gradient(135deg, #007aff 0%, #5856d6 100%);
  color: white;
  box-shadow: 0 4px 12px rgba(0, 122, 255, 0.3);
}

.btn-submit:active {
  transform: scale(0.98);
  box-shadow: 0 2px 8px rgba(0, 122, 255, 0.4);
}
</style>
