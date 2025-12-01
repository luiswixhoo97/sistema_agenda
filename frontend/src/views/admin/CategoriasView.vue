<template>
  <div class="admin-view">
    <!-- Header -->
    <div class="view-header">
      <div class="header-info">
        <div class="header-icon">
          <i class="fa fa-folder"></i>
        </div>
        <div class="header-text">
          <h1>Categorías</h1>
          <p class="header-subtitle">{{ categorias.length }} registradas</p>
        </div>
      </div>
      <button class="btn-action" @click="nuevaCategoria">
        <i class="fa fa-plus"></i>
      </button>
    </div>

    <!-- Filter Tabs -->
    <div class="filter-tabs">
      <button 
        :class="['tab', { active: filtroActivo === null }]"
        @click="filtroActivo = null"
      >
        <i class="fa fa-th-large"></i>
        Todas
      </button>
      <button 
        :class="['tab', { active: filtroActivo === true }]"
        @click="filtroActivo = true"
      >
        <i class="fa fa-check-circle"></i>
        Activas
      </button>
      <button 
        :class="['tab', { active: filtroActivo === false }]"
        @click="filtroActivo = false"
      >
        <i class="fa fa-eye-slash"></i>
        Inactivas
      </button>
    </div>

    <!-- Categorias List -->
    <div class="categorias-container">
      <div v-if="loading" class="loading-state">
        <div class="spinner"></div>
        <p>Cargando categorías...</p>
      </div>
      
      <div v-else-if="categoriasFiltradas.length === 0" class="empty-state">
        <i class="fa fa-folder-open"></i>
        <p>No hay categorías registradas</p>
      </div>
      
      <div v-else class="categorias-list">
        <div 
          v-for="categoria in categoriasFiltradas" 
          :key="categoria.id" 
          class="categoria-card"
          :class="{ inactivo: !isActiva(categoria) }"
        >
          <div class="categoria-header">
            <div class="categoria-info">
              <div class="categoria-icon-container">
                <div class="categoria-icon">
                  <i class="fa fa-folder"></i>
                </div>
                <span :class="['status-dot', isActiva(categoria) ? 'activo' : 'inactivo']"></span>
              </div>
              <div class="categoria-content">
                <h3 class="categoria-nombre">{{ categoria.nombre }}</h3>
                <p class="categoria-descripcion">{{ categoria.descripcion || 'Sin descripción' }}</p>
              </div>
            </div>
            <div class="categoria-actions">
              <button class="btn-icon-sm" @click="editarCategoria(categoria)" title="Editar">
                <i class="fa fa-edit"></i>
              </button>
              <button 
                class="btn-icon-sm"
                :class="isActiva(categoria) ? 'danger' : 'success'"
                @click="toggleCategoria(categoria)"
                :title="isActiva(categoria) ? 'Desactivar' : 'Activar'"
              >
                <i :class="isActiva(categoria) ? 'fa fa-eye-slash' : 'fa fa-eye'"></i>
              </button>
              <button 
                class="btn-icon-sm delete"
                @click="eliminarCategoria(categoria)"
                title="Eliminar"
              >
                <i class="fa fa-trash"></i>
              </button>
            </div>
          </div>
          
          <div class="categoria-footer">
            <div class="categoria-stats">
              <span class="stat-item">
                <i class="fa fa-cut"></i>
                {{ categoria.servicios_count || 0 }} servicios
              </span>
              <span :class="['status-badge', isActiva(categoria) ? 'activo' : 'inactivo']">
                {{ isActiva(categoria) ? 'Activa' : 'Inactiva' }}
              </span>
            </div>
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
              <i class="fa fa-times"></i>
            </button>
          </div>
          <div class="modal-body">
            <div class="form-container">
              <div class="form-group">
                <label>
                  <i class="fa fa-tag"></i>
                  Nombre de la categoría <span class="required">*</span>
                </label>
                <input 
                  v-model="formData.nombre" 
                  type="text" 
                  placeholder="Ej: Cortes de cabello" 
                  maxlength="100"
                />
              </div>
              
              <div class="form-group">
                <label>
                  <i class="fa fa-align-left"></i>
                  Descripción
                </label>
                <textarea 
                  v-model="formData.descripcion" 
                  rows="3" 
                  placeholder="Breve descripción de la categoría..."
                ></textarea>
              </div>
              
              <div class="form-group" v-if="selectedCategoria">
                <label>
                  <i class="fa fa-toggle-on"></i>
                  Estado
                </label>
                <div class="toggle-container">
                  <label class="toggle-switch">
                    <input 
                      type="checkbox" 
                      v-model="formData.active"
                    />
                    <span class="toggle-slider"></span>
                  </label>
                  <span class="toggle-label">{{ formData.active ? 'Activa' : 'Inactiva' }}</span>
                </div>
              </div>
              
              <button class="btn-submit" @click="guardarCategoria" :disabled="!formData.nombre.trim()">
                <i class="fa fa-save"></i>
                {{ selectedCategoria ? 'Actualizar' : 'Crear' }} Categoría
              </button>
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
  } catch (error) {
    console.error('Error actualizando categoría:', error);
    alert('Error al actualizar categoría');
    await cargarCategorias();
  }
}

async function eliminarCategoria(c: any) {
  if (!confirm(`¿Estás seguro de eliminar la categoría "${c.nombre}"?`)) {
    return;
  }
  
  try {
    await deleteCategoria(c.id);
    await cargarCategorias();
    alert('Categoría eliminada exitosamente');
  } catch (error: any) {
    console.error('Error eliminando categoría:', error);
    const errorMessage = error.response?.data?.message || 'Error al eliminar categoría';
    alert(errorMessage);
  }
}

async function guardarCategoria() {
  if (!formData.nombre.trim()) {
    alert('El nombre de la categoría es requerido');
    return;
  }

  try {
    if (selectedCategoria.value) {
      await updateCategoria(selectedCategoria.value.id, formData);
      alert('Categoría actualizada exitosamente');
    } else {
      await createCategoria({
        nombre: formData.nombre,
        descripcion: formData.descripcion || null,
      });
      alert('Categoría creada exitosamente');
    }
    closeModal();
    await cargarCategorias();
  } catch (error: any) {
    console.error('Error guardando categoría:', error);
    const errorMessage = error.response?.data?.message || 'Error al guardar categoría';
    alert(errorMessage);
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
  background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
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
  transition: all 0.2s;
}

.btn-action:active {
  background: rgba(255, 255, 255, 0.3);
}

/* Filter Tabs */
.filter-tabs {
  display: flex;
  gap: 8px;
  margin-bottom: 16px;
  overflow-x: auto;
  padding-bottom: 4px;
  -webkit-overflow-scrolling: touch;
}

.tab {
  display: flex;
  align-items: center;
  gap: 6px;
  padding: 10px 16px;
  background: white;
  border: none;
  border-radius: 10px;
  font-size: 13px;
  font-weight: 500;
  color: #666;
  cursor: pointer;
  white-space: nowrap;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
  transition: all 0.2s;
}

.tab.active {
  background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
  color: white;
}

.tab i {
  font-size: 14px;
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
  border-top-color: #f093fb;
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

/* Categorias List */
.categorias-list {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.categoria-card {
  background: white;
  border-radius: 16px;
  padding: 16px;
  box-shadow: 0 2px 12px rgba(0, 0, 0, 0.08);
  transition: all 0.2s;
  border: 2px solid transparent;
}

.categoria-card:hover {
  box-shadow: 0 4px 16px rgba(240, 147, 251, 0.15);
  transform: translateY(-2px);
}

.categoria-card.inactivo {
  opacity: 0.6;
}

.categoria-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  margin-bottom: 12px;
}

.categoria-info {
  display: flex;
  align-items: flex-start;
  gap: 12px;
  flex: 1;
}

.categoria-icon-container {
  position: relative;
  flex-shrink: 0;
}

.categoria-icon {
  width: 56px;
  height: 56px;
  background: linear-gradient(135deg, #fce4ec 0%, #f8bbd0 100%);
  border-radius: 14px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 24px;
  color: #f5576c;
}

.status-dot {
  position: absolute;
  top: -2px;
  right: -2px;
  width: 12px;
  height: 12px;
  border-radius: 50%;
  border: 2px solid white;
}

.status-dot.activo {
  background: #38ef7d;
}

.status-dot.inactivo {
  background: #ff6b6b;
}

.categoria-content {
  flex: 1;
  min-width: 0;
}

.categoria-nombre {
  margin: 0 0 4px;
  font-size: 16px;
  font-weight: 600;
  color: #333;
}

.categoria-descripcion {
  margin: 0;
  font-size: 12px;
  color: #666;
  line-height: 1.4;
}

.categoria-actions {
  display: flex;
  gap: 6px;
  flex-shrink: 0;
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

.btn-icon-sm.success {
  background: #e8f5e9;
  color: #2e7d32;
}

.btn-icon-sm.delete {
  background: #ffebee;
  color: #c62828;
}

.categoria-footer {
  padding-top: 12px;
  border-top: 1px solid #f0f0f0;
}

.categoria-stats {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.stat-item {
  display: flex;
  align-items: center;
  gap: 6px;
  font-size: 12px;
  color: #666;
}

.stat-item i {
  font-size: 11px;
  color: #f5576c;
}

.status-badge {
  padding: 4px 10px;
  border-radius: 10px;
  font-size: 10px;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.3px;
}

.status-badge.activo {
  background: linear-gradient(135deg, #e8f5e9 0%, #c8e6c9 100%);
  color: #2e7d32;
}

.status-badge.inactivo {
  background: linear-gradient(135deg, #ffebee 0%, #ffcdd2 100%);
  color: #c62828;
}

/* Modal */
.modal-overlay {
  position: fixed;
  inset: 0;
  background: rgba(0, 0, 0, 0.5);
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
  background: linear-gradient(135deg, #f8f9fa 0%, #ffffff 100%);
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
  padding: 20px;
  overflow-y: auto;
  max-height: calc(90vh - 80px);
}

/* Form */
.form-container {
  display: flex;
  flex-direction: column;
  gap: 16px;
}

.form-group {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.form-group label {
  font-size: 13px;
  font-weight: 600;
  color: #333;
  display: flex;
  align-items: center;
  gap: 6px;
}

.form-group label i {
  color: #f5576c;
  font-size: 14px;
}

.required {
  color: #f5576c;
}

.form-group input,
.form-group textarea {
  padding: 12px;
  border: 2px solid #e0e0e0;
  border-radius: 10px;
  font-size: 14px;
  transition: border-color 0.2s;
  font-family: inherit;
}

.form-group input:focus,
.form-group textarea:focus {
  outline: none;
  border-color: #f093fb;
  box-shadow: 0 0 0 3px rgba(240, 147, 251, 0.1);
}

.toggle-container {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 8px 0;
}

.toggle-switch {
  position: relative;
  display: inline-block;
  width: 50px;
  height: 28px;
  cursor: pointer;
}

.toggle-switch input[type="checkbox"] {
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
  background-color: #e0e0e0;
  transition: 0.3s;
  border-radius: 28px;
}

.toggle-slider:before {
  position: absolute;
  content: "";
  height: 22px;
  width: 22px;
  left: 3px;
  bottom: 3px;
  background-color: white;
  transition: 0.3s;
  border-radius: 50%;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
}

.toggle-switch input:checked + .toggle-slider {
  background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
}

.toggle-switch input:checked + .toggle-slider:before {
  transform: translateX(22px);
}

.toggle-label {
  font-size: 14px;
  font-weight: 600;
  color: #333;
}

.btn-submit {
  width: 100%;
  padding: 14px;
  background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
  color: white;
  border: none;
  border-radius: 12px;
  font-size: 15px;
  font-weight: 600;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  margin-top: 8px;
  transition: all 0.2s;
  box-shadow: 0 4px 12px rgba(240, 147, 251, 0.3);
}

.btn-submit:hover:not(:disabled) {
  box-shadow: 0 6px 20px rgba(240, 147, 251, 0.4);
  transform: translateY(-2px);
}

.btn-submit:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.btn-submit:active:not(:disabled) {
  transform: translateY(0);
}
</style>

