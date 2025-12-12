<template>
  <div class="categorias-view">
    <!-- Header -->
    <div class="categorias-header">
      <div class="header-left">
        <div class="header-icon">
          <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <rect x="3" y="3" width="7" height="7"></rect>
            <rect x="14" y="3" width="7" height="7"></rect>
            <rect x="14" y="14" width="7" height="7"></rect>
            <rect x="3" y="14" width="7" height="7"></rect>
          </svg>
        </div>
        <div class="header-text">
          <h1>Categorías</h1>
          <p class="header-subtitle">{{ categorias.length }} registradas</p>
        </div>
      </div>
      <button class="btn-new-categoria" @click="nuevaCategoria" title="Nueva Categoría">
        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <line x1="12" y1="5" x2="12" y2="19"></line>
          <line x1="5" y1="12" x2="19" y2="12"></line>
        </svg>
        <span class="btn-text">Nuevo</span>
      </button>
    </div>

    <!-- Filter Tabs -->
    <div class="filter-tabs">
      <div class="tabs-scroll">
        <button 
          :class="['tab', { active: filtroActivo === null }]"
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
          :class="['tab', { active: filtroActivo === true }]"
          @click="filtroActivo = true"
        >
          <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
            <polyline points="22 4 12 14.01 9 11.01"></polyline>
          </svg>
          Activas
        </button>
        <button 
          :class="['tab', { active: filtroActivo === false }]"
          @click="filtroActivo = false"
        >
          <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path>
            <line x1="1" y1="1" x2="23" y2="23"></line>
          </svg>
          Inactivas
        </button>
      </div>
    </div>

    <!-- Categorias List -->
    <div class="categorias-container">
      <div v-if="loading" class="loading-container">
        <div class="loader"></div>
        <p>Cargando categorías...</p>
      </div>
      
      <div v-else-if="categoriasFiltradas.length === 0" class="empty-state">
        <div class="empty-icon">
          <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
            <rect x="3" y="3" width="7" height="7"></rect>
            <rect x="14" y="3" width="7" height="7"></rect>
            <rect x="14" y="14" width="7" height="7"></rect>
            <rect x="3" y="14" width="7" height="7"></rect>
          </svg>
        </div>
        <p>No hay categorías registradas</p>
      </div>
      
      <div v-else class="categorias-list">
        <div 
          v-for="categoria in categoriasFiltradas" 
          :key="categoria.id" 
          class="categoria-card"
          :class="{ inactivo: !isActiva(categoria) }"
        >
          <div class="categoria-main">
            <div class="categoria-icon-container">
              <div class="categoria-icon">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"></path>
                </svg>
              </div>
              <span :class="['status-dot', isActiva(categoria) ? 'activo' : 'inactivo']"></span>
            </div>
            
            <div class="categoria-info">
              <div class="categoria-header-info">
                <h3 class="categoria-nombre">{{ categoria.nombre }}</h3>
                <span :class="['status-badge', isActiva(categoria) ? 'activo' : 'inactivo']">
                  {{ isActiva(categoria) ? 'Activa' : 'Inactiva' }}
                </span>
              </div>
              <p class="categoria-descripcion">{{ categoria.descripcion || 'Sin descripción' }}</p>
              <div class="categoria-stats">
                <span class="stat-item">
                  <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"></path>
                  </svg>
                  {{ categoria.servicios_count || 0 }} servicios
                </span>
              </div>
            </div>
          </div>
          
          <div class="categoria-actions">
            <button class="action-btn" @click="editarCategoria(categoria)" title="Editar">
              <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
              </svg>
            </button>
            <button 
              class="action-btn"
              :class="isActiva(categoria) ? 'danger' : 'success'"
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
              class="action-btn delete"
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
            <div class="form-container">
              <div class="form-section">
                <label class="form-label">
                  <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z"></path>
                    <line x1="7" y1="7" x2="7.01" y2="7"></line>
                  </svg>
                  Nombre de la categoría <span class="required">*</span>
                </label>
                <input 
                  v-model="formData.nombre" 
                  type="text" 
                  placeholder="Ej: Cortes de cabello" 
                  maxlength="100"
                  class="form-input"
                />
              </div>
              
              <div class="form-section">
                <label class="form-label">
                  <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                    <polyline points="14 2 14 8 20 8"></polyline>
                    <line x1="16" y1="13" x2="8" y2="13"></line>
                    <line x1="16" y1="17" x2="8" y2="17"></line>
                    <polyline points="10 9 9 9 8 9"></polyline>
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
              
              <div class="form-section" v-if="selectedCategoria">
                <label class="form-label">
                  <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="12" r="10"></circle>
                    <polyline points="12 6 12 12 16 14"></polyline>
                  </svg>
                  Estado
                </label>
                <div class="toggle-container">
                  <label class="toggle-day">
                    <input type="checkbox" v-model="formData.active" />
                    <span class="toggle-switch"></span>
                  </label>
                  <span class="toggle-label">{{ formData.active ? 'Activa' : 'Inactiva' }}</span>
                </div>
              </div>
              
              <div class="form-actions">
                <button type="button" class="btn-cancel" @click="closeModal">
                  Cancelar
                </button>
                <button type="button" class="btn-submit" @click="guardarCategoria" :disabled="!formData.nombre.trim()">
                  <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path>
                    <polyline points="17 21 17 13 7 13 7 21"></polyline>
                    <polyline points="7 3 7 8 15 8"></polyline>
                  </svg>
                  {{ selectedCategoria ? 'Actualizar' : 'Crear' }} Categoría
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
import { ref, computed, reactive, onMounted } from 'vue';
import { 
  getCategorias, 
  createCategoria, 
  updateCategoria, 
  deleteCategoria 
} from '@/services/adminService';
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

const categoriasFiltradas = computed(() => {
  if (filtroActivo.value === null) return categorias.value;
  return categorias.value.filter(c => c.active === filtroActivo.value);
});

function isActiva(categoria: any): boolean {
  const activa = categoria.active !== undefined ? categoria.active : categoria.activo;
  if (activa === true || activa === 1 || activa === '1' || activa === 'true') {
    return true;
  }
  return false;
}

async function cargarCategorias() {
  loading.value = true;
  try {
    const response = await getCategorias();
    if (response.success) {
      categorias.value = response.data || [];
    }
  } catch (error) {
    console.error('Error cargando categorías:', error);
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
    await updateCategoria(c.id, { active: nuevoEstado });
    // Actualizar el estado local
    c.active = nuevoEstado;
    c.activo = nuevoEstado; // Mantener compatibilidad
  } catch (error: any) {
    console.error('Error actualizando categoría:', error);
    Swal.fire({
      icon: 'error',
      title: 'Error',
      text: error.response?.data?.message || 'Error al actualizar categoría',
      confirmButtonColor: '#ff3b30'
    });
    await cargarCategorias();
  }
}

async function eliminarCategoria(c: any) {
  const result = await Swal.fire({
    icon: 'warning',
    title: '¿Eliminar categoría?',
    text: `¿Estás seguro de eliminar la categoría "${c.nombre}"? Esta acción no se puede deshacer.`,
    showCancelButton: true,
    confirmButtonColor: '#ff3b30',
    cancelButtonColor: '#86868b',
    confirmButtonText: 'Sí, eliminar',
    cancelButtonText: 'Cancelar'
  });
  
  if (!result.isConfirmed) {
    return;
  }
  
  try {
    await deleteCategoria(c.id);
    Swal.fire({
      icon: 'success',
      title: '¡Éxito!',
      text: 'Categoría eliminada exitosamente',
      confirmButtonColor: '#34c759',
      timer: 1500,
      showConfirmButton: false
    });
    await cargarCategorias();
  } catch (error: any) {
    console.error('Error eliminando categoría:', error);
    const errorMessage = error.response?.data?.message || 'Error al eliminar categoría';
    Swal.fire({
      icon: 'error',
      title: 'Error',
      text: errorMessage,
      confirmButtonColor: '#ff3b30'
    });
  }
}

async function guardarCategoria() {
  if (!formData.nombre.trim()) {
    Swal.fire({
      icon: 'warning',
      title: 'Campo requerido',
      text: 'El nombre de la categoría es requerido',
      confirmButtonColor: '#ff3b30'
    });
    return;
  }

  try {
    if (selectedCategoria.value) {
      await updateCategoria(selectedCategoria.value.id, formData);
      Swal.fire({
        icon: 'success',
        title: '¡Éxito!',
        text: 'Categoría actualizada exitosamente',
        confirmButtonColor: '#34c759',
        timer: 1500,
        showConfirmButton: false
      });
    } else {
      await createCategoria({
        nombre: formData.nombre.trim(),
        descripcion: formData.descripcion?.trim() || null,
      });
      Swal.fire({
        icon: 'success',
        title: '¡Éxito!',
        text: 'Categoría creada exitosamente',
        confirmButtonColor: '#34c759',
        timer: 1500,
        showConfirmButton: false
      });
    }
    closeModal();
    await cargarCategorias();
  } catch (error: any) {
    console.error('Error guardando categoría:', error);
    const errorMessage = error.response?.data?.message || 'Error al guardar categoría';
    Swal.fire({
      icon: 'error',
      title: 'Error',
      text: errorMessage,
      confirmButtonColor: '#ff3b30'
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
/* ===== Apple-inspired Categorías View Design ===== */

.categorias-view {
  min-height: 100vh;
  background: #f5f5f7;
  padding: 24px;
  padding-bottom: 120px;
  font-family: -apple-system, BlinkMacSystemFont, 'SF Pro Display', 'SF Pro Text', 'Helvetica Neue', sans-serif;
  -webkit-font-smoothing: antialiased;
  -moz-osx-font-smoothing: grayscale;
}

/* Header */
.categorias-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 20px;
  padding: 16px;
  background: linear-gradient(135deg, #1d1d1f 0%, #3a3a3c 100%);
  border-radius: 16px;
  color: white;
}

.header-left {
  display: flex;
  align-items: center;
  gap: 12px;
  flex: 1;
  min-width: 0;
}

.header-icon {
  width: 40px;
  height: 40px;
  background: rgba(255, 255, 255, 0.1);
  border-radius: 12px;
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
  font-size: 18px;
  font-weight: 600;
  margin: 0;
  letter-spacing: -0.3px;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.header-subtitle {
  font-size: 12px;
  opacity: 0.7;
  margin: 2px 0 0;
}

.btn-new-categoria {
  display: flex;
  align-items: center;
  gap: 6px;
  padding: 10px 14px;
  background: rgba(255, 255, 255, 0.15);
  border: 1px solid rgba(255, 255, 255, 0.2);
  border-radius: 12px;
  color: white;
  font-size: 14px;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.2s;
  backdrop-filter: blur(10px);
}

.btn-new-categoria .btn-text {
  display: none;
}

.btn-new-categoria:active {
  background: rgba(255, 255, 255, 0.25);
  transform: scale(0.98);
}

@media (min-width: 480px) {
  .categorias-header {
    padding: 20px;
    margin-bottom: 24px;
    border-radius: 20px;
  }
  
  .header-icon {
    width: 46px;
    height: 46px;
  }
  
  .header-text h1 {
    font-size: 22px;
  }
  
  .header-subtitle {
    font-size: 13px;
    margin: 4px 0 0;
  }
  
  .btn-new-categoria {
    padding: 12px 20px;
    font-size: 15px;
  }
  
  .btn-new-categoria .btn-text {
    display: inline;
  }
}

/* Filter Tabs */
.filter-tabs {
  margin-bottom: 20px;
}

.tabs-scroll {
  display: flex;
  gap: 10px;
  overflow-x: auto;
  padding-bottom: 4px;
  -webkit-overflow-scrolling: touch;
  scrollbar-width: none;
  -ms-overflow-style: none;
}

.tabs-scroll::-webkit-scrollbar {
  display: none;
}

.tab {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 10px 16px;
  background: #ffffff;
  border: 1px solid #e5e5ea;
  border-radius: 12px;
  font-size: 14px;
  font-weight: 500;
  color: #1d1d1f;
  cursor: pointer;
  white-space: nowrap;
  transition: all 0.2s;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
}

.tab:active {
  transform: scale(0.98);
}

.tab.active {
  background: #007aff;
  border-color: #007aff;
  color: white;
  box-shadow: 0 2px 8px rgba(0, 122, 255, 0.3);
}

.tab svg {
  flex-shrink: 0;
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

/* Categorias List */
.categorias-list {
  display: flex;
  flex-direction: column;
  gap: 10px;
}

.categoria-card {
  background: #ffffff;
  border-radius: 14px;
  padding: 14px;
  box-shadow: 0 2px 12px rgba(0, 0, 0, 0.08);
  border: 1px solid #e5e5ea;
  transition: all 0.2s;
}

.categoria-card.inactivo {
  opacity: 0.7;
}

.categoria-main {
  display: flex;
  align-items: flex-start;
  gap: 14px;
  margin-bottom: 12px;
}

.categoria-icon-container {
  position: relative;
  flex-shrink: 0;
}

.categoria-icon {
  width: 48px;
  height: 48px;
  background: linear-gradient(135deg, #007aff 0%, #5856d6 100%);
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
  box-shadow: 0 2px 8px rgba(0, 122, 255, 0.3);
}

.status-dot {
  position: absolute;
  bottom: -2px;
  right: -2px;
  width: 14px;
  height: 14px;
  border-radius: 50%;
  border: 2px solid white;
  box-shadow: 0 2px 6px rgba(0, 0, 0, 0.2);
}

.status-dot.activo {
  background: #34c759;
}

.status-dot.inactivo {
  background: #ff3b30;
}

.categoria-info {
  flex: 1;
  min-width: 0;
}

.categoria-header-info {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 10px;
  margin-bottom: 6px;
}

.categoria-nombre {
  margin: 0;
  font-size: 16px;
  font-weight: 600;
  color: #1d1d1f;
  letter-spacing: -0.2px;
  flex: 1;
  min-width: 0;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.categoria-descripcion {
  margin: 0 0 8px;
  font-size: 13px;
  color: #86868b;
  line-height: 1.4;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

.categoria-stats {
  margin-top: 8px;
}

.stat-item {
  display: flex;
  align-items: center;
  gap: 6px;
  font-size: 12px;
  color: #86868b;
}

.stat-item svg {
  color: #007aff;
  flex-shrink: 0;
}

.status-badge {
  padding: 4px 10px;
  border-radius: 8px;
  font-size: 11px;
  font-weight: 600;
  flex-shrink: 0;
}

.status-badge.activo {
  background: #e8f5e9;
  color: #34c759;
}

.status-badge.inactivo {
  background: #ffebee;
  color: #ff3b30;
}

.categoria-actions {
  display: flex;
  gap: 6px;
  padding-top: 12px;
  border-top: 1px solid #e5e5ea;
}

.action-btn {
  flex: 1;
  min-width: 0;
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
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.06);
}

.action-btn:active {
  transform: scale(0.95);
  box-shadow: 0 1px 2px rgba(0, 0, 0, 0.12);
}

.action-btn.danger {
  background: #ffebee;
  color: #ff3b30;
}

.action-btn.success {
  background: #e8f5e9;
  color: #34c759;
}

.action-btn.delete {
  background: #ffebee;
  color: #ff3b30;
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

/* Form */
.form-container {
  display: flex;
  flex-direction: column;
  gap: 20px;
}

.form-section {
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
  color: #1d1d1f;
  letter-spacing: -0.1px;
}

.form-label svg {
  color: #007aff;
  flex-shrink: 0;
}

.required {
  color: #ff3b30;
}

.form-input,
.form-textarea {
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

.form-input:focus,
.form-textarea:focus {
  outline: none;
  border-color: #007aff;
  background: #ffffff;
  box-shadow: 0 0 0 4px rgba(0, 122, 255, 0.1);
}

.form-input::placeholder,
.form-textarea::placeholder {
  color: #86868b;
}

.form-textarea {
  resize: vertical;
  min-height: 100px;
}

.toggle-container {
  display: flex;
  align-items: center;
  gap: 14px;
  padding: 8px 0;
}

.toggle-day {
  display: flex;
  align-items: center;
  cursor: pointer;
  flex-shrink: 0;
}

.toggle-day input {
  display: none;
}

.toggle-switch {
  width: 50px;
  height: 30px;
  background: #e5e5ea;
  border-radius: 15px;
  position: relative;
  transition: background 0.3s;
}

.toggle-switch::after {
  content: '';
  position: absolute;
  width: 26px;
  height: 26px;
  background: white;
  border-radius: 50%;
  top: 2px;
  left: 2px;
  transition: transform 0.3s;
  box-shadow: 0 2px 6px rgba(0, 0, 0, 0.2);
}

.toggle-day input:checked + .toggle-switch {
  background: linear-gradient(135deg, #007aff 0%, #5856d6 100%);
}

.toggle-day input:checked + .toggle-switch::after {
  transform: translateX(20px);
}

.toggle-label {
  font-size: 14px;
  font-weight: 600;
  color: #1d1d1f;
}

.form-actions {
  display: flex;
  gap: 12px;
  margin-top: 8px;
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

.btn-submit:active:not(:disabled) {
  transform: scale(0.98);
  box-shadow: 0 2px 8px rgba(0, 122, 255, 0.4);
}

.btn-submit:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}
</style>
