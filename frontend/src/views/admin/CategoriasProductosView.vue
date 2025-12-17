<template>
  <div class="categorias-productos-view">
    <!-- Header -->
    <div class="page-header">
      <div class="header-left">
        <div class="header-icon">
          <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"></path>
            <line x1="3" y1="6" x2="21" y2="6"></line>
            <path d="M16 10a4 4 0 0 1-8 0"></path>
          </svg>
        </div>
        <div class="header-text">
          <h1>Categorías de Productos</h1>
          <p class="header-subtitle">{{ categorias.length }} registradas</p>
        </div>
      </div>
      <button class="btn-primary" @click="nuevaCategoria" title="Nueva Categoría">
        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <line x1="12" y1="5" x2="12" y2="19"></line>
          <line x1="5" y1="12" x2="19" y2="12"></line>
        </svg>
        <span class="btn-text">Nueva</span>
      </button>
    </div>

    <!-- Filter Tabs -->
    <div class="filter-section">
      <div class="tabs-container">
        <button 
          :class="['tab-btn', { active: filtroActivo === null }]"
          @click="filtroActivo = null"
        >
          <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <rect x="3" y="3" width="7" height="7"></rect>
            <rect x="14" y="3" width="7" height="7"></rect>
            <rect x="14" y="14" width="7" height="7"></rect>
            <rect x="3" y="14" width="7" height="7"></rect>
          </svg>
          Todas
        </button>
        <button 
          :class="['tab-btn', { active: filtroActivo === true }]"
          @click="filtroActivo = true"
        >
          <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
            <polyline points="22 4 12 14.01 9 11.01"></polyline>
          </svg>
          Activas
        </button>
        <button 
          :class="['tab-btn', { active: filtroActivo === false }]"
          @click="filtroActivo = false"
        >
          <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <circle cx="12" cy="12" r="10"></circle>
            <line x1="15" y1="9" x2="9" y2="15"></line>
            <line x1="9" y1="9" x2="15" y2="15"></line>
          </svg>
          Inactivas
        </button>
      </div>
    </div>

    <!-- Lista de Categorías -->
    <div class="content-container">
      <div v-if="loading" class="loading-state">
        <div class="loader"></div>
        <p>Cargando categorías...</p>
      </div>
      
      <div v-else-if="categoriasFiltradas.length === 0" class="empty-state">
        <div class="empty-icon">
          <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
            <path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"></path>
            <line x1="3" y1="6" x2="21" y2="6"></line>
            <path d="M16 10a4 4 0 0 1-8 0"></path>
          </svg>
        </div>
        <p>No hay categorías de productos</p>
        <button class="btn-empty" @click="nuevaCategoria">
          <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <line x1="12" y1="5" x2="12" y2="19"></line>
            <line x1="5" y1="12" x2="19" y2="12"></line>
          </svg>
          Crear primera categoría
        </button>
      </div>
      
      <div v-else class="categorias-grid">
        <div 
          v-for="categoria in categoriasFiltradas" 
          :key="categoria.id" 
          class="categoria-card"
          :class="{ inactivo: !isActiva(categoria) }"
        >
          <div class="card-header">
            <div class="card-icon" :style="{ background: getColorCategoria(categoria.id) }">
              <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"></path>
              </svg>
            </div>
            <span :class="['status-badge', isActiva(categoria) ? 'activo' : 'inactivo']">
              {{ isActiva(categoria) ? 'Activa' : 'Inactiva' }}
            </span>
          </div>
          
          <div class="card-body">
            <h3 class="categoria-nombre">{{ categoria.nombre }}</h3>
            <p class="categoria-descripcion">{{ categoria.descripcion || 'Sin descripción' }}</p>
            
            <div class="categoria-stats">
              <div class="stat-item">
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"></path>
                  <line x1="3" y1="6" x2="21" y2="6"></line>
                  <path d="M16 10a4 4 0 0 1-8 0"></path>
                </svg>
                <span>{{ categoria.productos_count || 0 }} productos</span>
              </div>
            </div>
          </div>
          
          <div class="card-actions">
            <button class="action-btn edit" @click="editarCategoria(categoria)" title="Editar">
              <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
              </svg>
            </button>
            <button 
              class="action-btn"
              :class="isActiva(categoria) ? 'warning' : 'success'"
              @click="toggleCategoria(categoria)"
              :title="isActiva(categoria) ? 'Desactivar' : 'Activar'"
            >
              <svg v-if="isActiva(categoria)" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path>
                <line x1="1" y1="1" x2="23" y2="23"></line>
              </svg>
              <svg v-else xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                <circle cx="12" cy="12" r="3"></circle>
              </svg>
            </button>
            <button 
              class="action-btn danger"
              @click="eliminarCategoria(categoria)"
              title="Eliminar"
            >
              <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <polyline points="3 6 5 6 21 6"></polyline>
                <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                <line x1="10" y1="11" x2="10" y2="17"></line>
                <line x1="14" y1="11" x2="14" y2="17"></line>
              </svg>
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Modal -->
    <Teleport to="body">
      <div v-if="showModal" class="modal-overlay" @click.self="closeModal">
        <div class="modal-content">
          <div class="modal-header">
            <h3>{{ selectedCategoria ? 'Editar Categoría' : 'Nueva Categoría' }}</h3>
            <button class="modal-close" @click="closeModal">
              <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <line x1="18" y1="6" x2="6" y2="18"></line>
                <line x1="6" y1="6" x2="18" y2="18"></line>
              </svg>
            </button>
          </div>
          <div class="modal-body">
            <form class="form-container" @submit.prevent="guardarCategoria">
              <div class="form-group">
                <label class="form-label">
                  <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z"></path>
                    <line x1="7" y1="7" x2="7.01" y2="7"></line>
                  </svg>
                  Nombre <span class="required">*</span>
                </label>
                <input 
                  v-model="formData.nombre" 
                  type="text" 
                  placeholder="Ej: Medicamentos, Belleza..." 
                  maxlength="100"
                  class="form-input"
                  required
                />
              </div>
              
              <div class="form-group">
                <label class="form-label">
                  <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                    <polyline points="14 2 14 8 20 8"></polyline>
                    <line x1="16" y1="13" x2="8" y2="13"></line>
                    <line x1="16" y1="17" x2="8" y2="17"></line>
                  </svg>
                  Descripción
                </label>
                <textarea 
                  v-model="formData.descripcion" 
                  rows="3" 
                  placeholder="Breve descripción de la categoría..."
                  class="form-textarea"
                ></textarea>
              </div>
              
              <div class="form-group" v-if="selectedCategoria">
                <label class="form-label">
                  <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="12" r="10"></circle>
                    <polyline points="12 6 12 12 16 14"></polyline>
                  </svg>
                  Estado
                </label>
                <div class="toggle-row">
                  <label class="toggle-switch">
                    <input type="checkbox" v-model="formData.active" />
                    <span class="toggle-slider"></span>
                  </label>
                  <span class="toggle-label">{{ formData.active ? 'Activa' : 'Inactiva' }}</span>
                </div>
              </div>
              
              <div class="form-actions">
                <button type="button" class="btn-cancel" @click="closeModal">
                  Cancelar
                </button>
                <button type="submit" class="btn-submit" :disabled="!formData.nombre.trim()">
                  <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path>
                    <polyline points="17 21 17 13 7 13 7 21"></polyline>
                    <polyline points="7 3 7 8 15 8"></polyline>
                  </svg>
                  {{ selectedCategoria ? 'Actualizar' : 'Crear' }}
                </button>
              </div>
            </form>
          </div>
        </div>
      </div>
    </Teleport>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, reactive, onMounted } from 'vue';
import { 
  getCategoriasProductos, 
  createCategoriaProducto, 
  updateCategoriaProducto, 
  deleteCategoriaProducto 
} from '@/services/inventarioService';
import Swal from 'sweetalert2';

const categorias = ref<any[]>([]);
const filtroActivo = ref<boolean | null>(null);
const loading = ref(true);
const showModal = ref(false);
const selectedCategoria = ref<any>(null);

const formData = reactive({
  nombre: '',
  descripcion: '',
  active: true,
});

const coloresCategoria = [
  'linear-gradient(135deg, #667eea 0%, #764ba2 100%)',
  'linear-gradient(135deg, #f093fb 0%, #f5576c 100%)',
  'linear-gradient(135deg, #4facfe 0%, #00f2fe 100%)',
  'linear-gradient(135deg, #43e97b 0%, #38f9d7 100%)',
  'linear-gradient(135deg, #fa709a 0%, #fee140 100%)',
  'linear-gradient(135deg, #a8edea 0%, #fed6e3 100%)',
  'linear-gradient(135deg, #ff9a9e 0%, #fecfef 100%)',
  'linear-gradient(135deg, #fbc2eb 0%, #a6c1ee 100%)',
];

const categoriasFiltradas = computed(() => {
  if (filtroActivo.value === null) return categorias.value;
  return categorias.value.filter(c => isActiva(c) === filtroActivo.value);
});

function getColorCategoria(id: number): string {
  return coloresCategoria[id % coloresCategoria.length];
}

function isActiva(categoria: any): boolean {
  const activa = categoria.active !== undefined ? categoria.active : categoria.activo;
  return activa === true || activa === 1 || activa === '1' || activa === 'true';
}

async function cargarCategorias() {
  loading.value = true;
  try {
    const response = await getCategoriasProductos();
    if (response.success) {
      categorias.value = response.data || [];
    }
  } catch (error) {
    console.error('Error cargando categorías:', error);
    Swal.fire({
      icon: 'error',
      title: 'Error',
      text: 'No se pudieron cargar las categorías',
      confirmButtonColor: '#667eea'
    });
  } finally {
    loading.value = false;
  }
}

function nuevaCategoria() { 
  selectedCategoria.value = null;
  formData.nombre = '';
  formData.descripcion = '';
  formData.active = true;
  showModal.value = true;
}

function editarCategoria(c: any) { 
  selectedCategoria.value = c;
  formData.nombre = c.nombre;
  formData.descripcion = c.descripcion || '';
  formData.active = isActiva(c);
  showModal.value = true;
}

async function toggleCategoria(c: any) { 
  try {
    const nuevoEstado = !isActiva(c);
    await updateCategoriaProducto(c.id, { active: nuevoEstado });
    c.active = nuevoEstado;
    
    Swal.fire({
      icon: 'success',
      title: nuevoEstado ? 'Categoría activada' : 'Categoría desactivada',
      toast: true,
      position: 'top-end',
      showConfirmButton: false,
      timer: 2000
    });
  } catch (error: any) {
    console.error('Error actualizando categoría:', error);
    Swal.fire({
      icon: 'error',
      title: 'Error',
      text: error.response?.data?.message || 'Error al actualizar categoría',
      confirmButtonColor: '#667eea'
    });
    await cargarCategorias();
  }
}

async function eliminarCategoria(c: any) {
  const result = await Swal.fire({
    icon: 'warning',
    title: '¿Eliminar categoría?',
    text: `Se eliminará "${c.nombre}". Esta acción no se puede deshacer.`,
    showCancelButton: true,
    confirmButtonColor: '#ff3b30',
    cancelButtonColor: '#86868b',
    confirmButtonText: 'Eliminar',
    cancelButtonText: 'Cancelar'
  });
  
  if (!result.isConfirmed) return;
  
  try {
    await deleteCategoriaProducto(c.id);
    Swal.fire({
      icon: 'success',
      title: 'Categoría eliminada',
      toast: true,
      position: 'top-end',
      showConfirmButton: false,
      timer: 2000
    });
    await cargarCategorias();
  } catch (error: any) {
    console.error('Error eliminando categoría:', error);
    Swal.fire({
      icon: 'error',
      title: 'Error',
      text: error.response?.data?.message || 'Error al eliminar categoría',
      confirmButtonColor: '#667eea'
    });
  }
}

async function guardarCategoria() {
  if (!formData.nombre.trim()) {
    Swal.fire({
      icon: 'warning',
      title: 'Campo requerido',
      text: 'El nombre de la categoría es requerido',
      confirmButtonColor: '#667eea'
    });
    return;
  }

  try {
    if (selectedCategoria.value) {
      await updateCategoriaProducto(selectedCategoria.value.id, formData);
    } else {
      await createCategoriaProducto({
        nombre: formData.nombre.trim(),
        descripcion: formData.descripcion?.trim() || null,
      });
    }
    
    Swal.fire({
      icon: 'success',
      title: selectedCategoria.value ? 'Categoría actualizada' : 'Categoría creada',
      toast: true,
      position: 'top-end',
      showConfirmButton: false,
      timer: 2000
    });
    
    closeModal();
    await cargarCategorias();
  } catch (error: any) {
    console.error('Error guardando categoría:', error);
    Swal.fire({
      icon: 'error',
      title: 'Error',
      text: error.response?.data?.message || 'Error al guardar categoría',
      confirmButtonColor: '#667eea'
    });
  }
}

function closeModal() {
  showModal.value = false;
  selectedCategoria.value = null;
}

onMounted(() => {
  cargarCategorias();
});
</script>

<style scoped>
/* ===== Modern Gradient Design - Categorías Productos ===== */

.categorias-productos-view {
  min-height: 100vh;
  background: linear-gradient(135deg, #f5f7fa 0%, #e4e8ec 100%);
  padding: 20px;
  padding-bottom: 100px;
  font-family: -apple-system, BlinkMacSystemFont, 'SF Pro Display', 'Segoe UI', Roboto, sans-serif;
  -webkit-font-smoothing: antialiased;
}

/* Header */
.page-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 20px;
  padding: 20px;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  border-radius: 20px;
  color: white;
  box-shadow: 0 10px 40px rgba(102, 126, 234, 0.3);
}

.header-left {
  display: flex;
  align-items: center;
  gap: 14px;
  flex: 1;
  min-width: 0;
}

.header-icon {
  width: 48px;
  height: 48px;
  background: rgba(255, 255, 255, 0.2);
  border-radius: 14px;
  display: flex;
  align-items: center;
  justify-content: center;
  backdrop-filter: blur(10px);
  flex-shrink: 0;
}

.header-text {
  flex: 1;
  min-width: 0;
}

.header-text h1 {
  font-size: 20px;
  font-weight: 700;
  margin: 0;
  letter-spacing: -0.5px;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.header-subtitle {
  font-size: 13px;
  opacity: 0.85;
  margin: 4px 0 0;
  font-weight: 500;
}

.btn-primary {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 12px 20px;
  background: rgba(255, 255, 255, 0.2);
  border: 1px solid rgba(255, 255, 255, 0.3);
  border-radius: 12px;
  color: white;
  font-size: 14px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.3s ease;
  backdrop-filter: blur(10px);
}

.btn-primary:active {
  transform: scale(0.96);
  background: rgba(255, 255, 255, 0.3);
}

.btn-text {
  display: none;
}

@media (min-width: 480px) {
  .btn-text {
    display: inline;
  }
  
  .header-text h1 {
    font-size: 24px;
  }
}

/* Filter Section */
.filter-section {
  margin-bottom: 20px;
}

.tabs-container {
  display: flex;
  gap: 10px;
  overflow-x: auto;
  padding-bottom: 4px;
  -webkit-overflow-scrolling: touch;
  scrollbar-width: none;
}

.tabs-container::-webkit-scrollbar {
  display: none;
}

.tab-btn {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 10px 16px;
  background: white;
  border: 1px solid #e5e5ea;
  border-radius: 12px;
  font-size: 14px;
  font-weight: 500;
  color: #1d1d1f;
  cursor: pointer;
  white-space: nowrap;
  transition: all 0.2s ease;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
}

.tab-btn:active {
  transform: scale(0.98);
}

.tab-btn.active {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  border-color: transparent;
  color: white;
  box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
}

/* Content */
.content-container {
  min-height: 200px;
}

.loading-state,
.empty-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 60px 20px;
  text-align: center;
}

.loader {
  width: 36px;
  height: 36px;
  border: 3px solid #e5e5ea;
  border-top-color: #667eea;
  border-radius: 50%;
  animation: spin 0.8s linear infinite;
  margin-bottom: 16px;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}

.loading-state p,
.empty-state p {
  color: #86868b;
  font-size: 15px;
  margin: 0 0 20px;
}

.empty-icon {
  color: #c7c7cc;
  margin-bottom: 16px;
}

.btn-empty {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 12px 24px;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  border: none;
  border-radius: 12px;
  color: white;
  font-size: 14px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s ease;
}

.btn-empty:active {
  transform: scale(0.98);
}

/* Grid */
.categorias-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
  gap: 16px;
}

.categoria-card {
  background: white;
  border-radius: 20px;
  overflow: hidden;
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
  transition: all 0.3s ease;
  border: 1px solid rgba(0, 0, 0, 0.04);
}

.categoria-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 8px 30px rgba(0, 0, 0, 0.12);
}

.categoria-card.inactivo {
  opacity: 0.7;
}

.card-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  padding: 16px 16px 0;
}

.card-icon {
  width: 48px;
  height: 48px;
  border-radius: 14px;
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
  box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
}

.status-badge {
  padding: 5px 10px;
  border-radius: 8px;
  font-size: 11px;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.3px;
}

.status-badge.activo {
  background: rgba(52, 199, 89, 0.12);
  color: #34c759;
}

.status-badge.inactivo {
  background: rgba(255, 59, 48, 0.12);
  color: #ff3b30;
}

.card-body {
  padding: 16px;
}

.categoria-nombre {
  margin: 0 0 8px;
  font-size: 18px;
  font-weight: 700;
  color: #1d1d1f;
  letter-spacing: -0.3px;
}

.categoria-descripcion {
  margin: 0 0 12px;
  font-size: 13px;
  color: #86868b;
  line-height: 1.5;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

.categoria-stats {
  display: flex;
  gap: 16px;
}

.stat-item {
  display: flex;
  align-items: center;
  gap: 6px;
  font-size: 13px;
  color: #667eea;
  font-weight: 500;
}

.card-actions {
  display: flex;
  gap: 8px;
  padding: 12px 16px;
  background: #f8f9fa;
  border-top: 1px solid #f0f0f0;
}

.action-btn {
  flex: 1;
  height: 38px;
  border: none;
  border-radius: 10px;
  background: white;
  color: #86868b;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all 0.2s ease;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.08);
}

.action-btn:active {
  transform: scale(0.95);
}

.action-btn.edit {
  color: #667eea;
}

.action-btn.warning {
  background: #fff3cd;
  color: #ff9500;
}

.action-btn.success {
  background: #d4edda;
  color: #34c759;
}

.action-btn.danger {
  background: #f8d7da;
  color: #ff3b30;
}

/* Modal */
.modal-overlay {
  position: fixed;
  inset: 0;
  background: rgba(0, 0, 0, 0.5);
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
  background: white;
  width: 100%;
  max-height: 90vh;
  border-radius: 24px 24px 0 0;
  overflow: hidden;
  animation: slideUp 0.3s cubic-bezier(0.16, 1, 0.3, 1);
  box-shadow: 0 -10px 50px rgba(0, 0, 0, 0.2);
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
  border-bottom: 1px solid #f0f0f0;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
}

.modal-header h3 {
  margin: 0;
  font-size: 20px;
  font-weight: 700;
  letter-spacing: -0.3px;
}

.modal-close {
  width: 36px;
  height: 36px;
  border: none;
  border-radius: 10px;
  background: rgba(255, 255, 255, 0.2);
  color: white;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all 0.2s;
}

.modal-close:active {
  transform: scale(0.95);
  background: rgba(255, 255, 255, 0.3);
}

.modal-body {
  padding: 24px;
  overflow-y: auto;
  max-height: calc(90vh - 80px);
}

/* Form */
.form-container {
  display: flex;
  flex-direction: column;
  gap: 20px;
}

.form-group {
  display: flex;
  flex-direction: column;
  gap: 10px;
}

.form-label {
  display: flex;
  align-items: center;
  gap: 8px;
  font-size: 13px;
  font-weight: 600;
  color: #667eea;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.required {
  color: #ff3b30;
}

.form-input,
.form-textarea {
  width: 100%;
  padding: 14px 16px;
  border: 2px solid #e5e5ea;
  border-radius: 14px;
  font-size: 15px;
  color: #1d1d1f;
  background: #f8f9fa;
  transition: all 0.2s ease;
  box-sizing: border-box;
  font-family: inherit;
}

.form-input:focus,
.form-textarea:focus {
  outline: none;
  border-color: #667eea;
  background: white;
  box-shadow: 0 0 0 4px rgba(102, 126, 234, 0.1);
}

.form-input::placeholder,
.form-textarea::placeholder {
  color: #a0a0a5;
}

.form-textarea {
  resize: vertical;
  min-height: 100px;
}

/* Toggle */
.toggle-row {
  display: flex;
  align-items: center;
  gap: 14px;
  padding: 8px 0;
}

.toggle-switch {
  position: relative;
  display: inline-block;
  width: 52px;
  height: 32px;
  cursor: pointer;
}

.toggle-switch input {
  opacity: 0;
  width: 0;
  height: 0;
}

.toggle-slider {
  position: absolute;
  cursor: pointer;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background-color: #e5e5ea;
  transition: 0.3s;
  border-radius: 16px;
}

.toggle-slider::before {
  position: absolute;
  content: "";
  height: 28px;
  width: 28px;
  left: 2px;
  bottom: 2px;
  background-color: white;
  transition: 0.3s;
  border-radius: 50%;
  box-shadow: 0 2px 6px rgba(0, 0, 0, 0.2);
}

.toggle-switch input:checked + .toggle-slider {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
}

.toggle-switch input:checked + .toggle-slider::before {
  transform: translateX(20px);
}

.toggle-label {
  font-size: 14px;
  font-weight: 600;
  color: #1d1d1f;
}

/* Form Actions */
.form-actions {
  display: flex;
  gap: 12px;
  margin-top: 12px;
  padding-top: 20px;
  border-top: 1px solid #f0f0f0;
}

.btn-cancel,
.btn-submit {
  flex: 1;
  padding: 14px 20px;
  border: none;
  border-radius: 14px;
  font-size: 15px;
  font-weight: 600;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  transition: all 0.2s ease;
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
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
}

.btn-submit:active:not(:disabled) {
  transform: scale(0.98);
}

.btn-submit:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}
</style>


