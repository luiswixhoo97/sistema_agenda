<template>
  <div class="admin-view">
    <!-- Header -->
    <div class="view-header">
      <div class="header-info">
        <div class="header-icon">
          <i class="fa fa-cut"></i>
        </div>
        <div class="header-text">
          <h1>Servicios</h1>
          <p class="header-subtitle">{{ servicios.length }} disponibles</p>
        </div>
      </div>
      <button class="btn-action" @click="nuevoServicio">
        <i class="fa fa-plus"></i>
      </button>
    </div>

    <!-- Categories Tabs -->
    <div class="categories-scroll">
      <div class="categories-tabs">
        <button 
          :class="['tab', { active: !categoriaActiva }]"
          @click="categoriaActiva = null"
        >
          <i class="fa fa-th-large"></i>
          Todos
        </button>
        <button 
          v-for="cat in categorias" 
          :key="cat.id" 
          :class="['tab', { active: categoriaActiva === cat.id }]"
          @click="categoriaActiva = cat.id"
        >
          {{ cat.nombre }}
        </button>
      </div>
    </div>

    <!-- Servicios Grid -->
    <div class="servicios-container">
      <div v-if="loading" class="loading-state">
        <div class="spinner"></div>
        <p>Cargando servicios...</p>
      </div>
      
      <div v-else-if="serviciosFiltrados.length === 0" class="empty-state">
        <i class="fa fa-spa"></i>
        <p>No hay servicios en esta categoría</p>
      </div>
      
      <div v-else class="servicios-grid">
        <div 
          v-for="servicio in serviciosFiltrados" 
          :key="servicio.id" 
          class="servicio-card"
          :class="{ inactivo: !isActivo(servicio) }"
        >
          <div class="servicio-icon-container">
            <span class="servicio-icon">💇‍♀️</span>
            <span :class="['status-dot', isActivo(servicio) ? 'activo' : 'inactivo']"></span>
          </div>
          
          <div class="servicio-content">
            <h3 class="servicio-nombre">{{ servicio.nombre }}</h3>
            <p class="servicio-descripcion">{{ servicio.descripcion || 'Sin descripción' }}</p>
            
            <div class="servicio-meta">
              <div class="meta-item precio">
                <i class="fa fa-tag"></i>
                <span>${{ formatPrecio(servicio.precio) }}</span>
              </div>
              <div class="meta-item duracion">
                <i class="fa fa-clock"></i>
                <span>{{ servicio.duracion_minutos || servicio.duracion }} min</span>
              </div>
            </div>
          </div>
          
          <div class="servicio-actions">
            <button class="btn-icon-sm" @click="editarServicio(servicio)">
              <i class="fa fa-edit"></i>
            </button>
            <button 
              class="btn-icon-sm"
              :class="isActivo(servicio) ? 'danger' : 'success'"
              @click="toggleServicio(servicio)"
            >
              <i :class="isActivo(servicio) ? 'fa fa-eye-slash' : 'fa fa-eye'"></i>
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
            <h3>{{ selectedServicio ? 'Editar Servicio' : 'Nuevo Servicio' }}</h3>
            <button class="modal-close" @click="closeModal">
              <i class="fa fa-times"></i>
            </button>
          </div>
          <div class="modal-body">
            <div class="form-container">
              <div class="form-group">
                <label>Nombre del servicio</label>
                <input v-model="formData.nombre" type="text" placeholder="Ej: Corte de cabello" />
              </div>
              
              <div class="form-group">
                <label>Descripción</label>
                <textarea v-model="formData.descripcion" rows="2" placeholder="Breve descripción..."></textarea>
              </div>
              
              <div class="form-row">
                <div class="form-group">
                  <label>Precio</label>
                  <div class="input-prefix">
                    <span>$</span>
                    <input v-model.number="formData.precio" type="number" placeholder="0" />
                  </div>
                </div>
                <div class="form-group">
                  <label>Duración (min)</label>
                  <input v-model.number="formData.duracion_minutos" type="number" placeholder="30" />
                </div>
              </div>
              
              <div class="form-group">
                <label>Categoría</label>
                <select v-model="formData.categoria_id">
                  <option :value="null">Sin categoría</option>
                  <option v-for="cat in categorias" :key="cat.id" :value="cat.id">
                    {{ cat.nombre }}
                  </option>
                </select>
              </div>
              
              <button class="btn-submit" @click="guardarServicio">
                <i class="fa fa-save"></i>
                Guardar Servicio
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
import { getServicios, getCategorias, createServicio, updateServicio } from '@/services/adminService';

const categorias = ref<any[]>([]);
const servicios = ref<any[]>([]);
const categoriaActiva = ref<number | null>(null);
const loading = ref(true);
const showModal = ref(false);
const selectedServicio = ref<any>(null);

const formData = reactive({
  nombre: '',
  descripcion: '',
  precio: 0,
  duracion_minutos: 30,
  categoria_id: null as number | null,
});

const serviciosFiltrados = computed(() => {
  if (!categoriaActiva.value) return servicios.value;
  return servicios.value.filter(s => s.categoria_id === categoriaActiva.value);
});

function isActivo(servicio: any): boolean {
  if (servicio.activo === true || servicio.activo === 1 || servicio.activo === '1' || servicio.activo === 'true') {
    return true;
  }
  return false;
}

async function cargarDatos() {
  loading.value = true;
  try {
    const [serviciosRes, categoriasRes] = await Promise.all([
      getServicios(),
      getCategorias()
    ]);
    
    if (serviciosRes.success) {
      servicios.value = serviciosRes.data || [];
    }
    if (categoriasRes.success) {
      categorias.value = categoriasRes.data || [];
    }
  } catch (error) {
    console.error('Error cargando datos:', error);
  } finally {
    loading.value = false;
  }
}

function formatPrecio(precio: any): string {
  return (Number(precio) || 0).toFixed(2);
}

function nuevoServicio() { 
  selectedServicio.value = null;
  formData.nombre = '';
  formData.descripcion = '';
  formData.precio = 0;
  formData.duracion_minutos = 30;
  formData.categoria_id = null;
  showModal.value = true;
}

function editarServicio(s: any) { 
  selectedServicio.value = s;
  formData.nombre = s.nombre;
  formData.descripcion = s.descripcion || '';
  formData.precio = s.precio;
  formData.duracion_minutos = s.duracion_minutos || s.duracion || 30;
  formData.categoria_id = s.categoria_id;
  showModal.value = true;
}

async function toggleServicio(s: any) { 
  try {
    const nuevoEstado = !isActivo(s);
    await updateServicio(s.id, { activo: nuevoEstado });
    s.activo = nuevoEstado;
  } catch (error) {
    console.error('Error actualizando servicio:', error);
    alert('Error al actualizar servicio');
  }
}

async function guardarServicio() {
  try {
    if (selectedServicio.value) {
      await updateServicio(selectedServicio.value.id, formData);
    } else {
      await createServicio(formData);
    }
    closeModal();
    cargarDatos();
  } catch (error) {
    console.error('Error guardando servicio:', error);
    alert('Error al guardar servicio');
  }
}

function closeModal() {
  showModal.value = false;
  selectedServicio.value = null;
}

onMounted(() => {
  cargarDatos();
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
  background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
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

/* Categories */
.categories-scroll {
  overflow-x: auto;
  margin: 0 -16px 16px;
  padding: 0 16px;
  -webkit-overflow-scrolling: touch;
}

.categories-tabs {
  display: flex;
  gap: 8px;
  padding-bottom: 4px;
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
  background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
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
  border-top-color: #4facfe;
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

/* Servicios Grid */
.servicios-grid {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.servicio-card {
  display: flex;
  align-items: flex-start;
  gap: 12px;
  background: white;
  border-radius: 16px;
  padding: 16px;
  box-shadow: 0 2px 12px rgba(0, 0, 0, 0.08);
  transition: opacity 0.2s;
  opacity: 1;
}

.servicio-card.inactivo {
  opacity: 0.6;
}

.servicio-icon-container {
  position: relative;
}

.servicio-icon {
  width: 56px;
  height: 56px;
  background: linear-gradient(135deg, #e3f2fd 0%, #bbdefb 100%);
  border-radius: 14px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 28px;
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

.servicio-content {
  flex: 1;
  min-width: 0;
}

.servicio-nombre {
  margin: 0 0 4px;
  font-size: 16px;
  font-weight: 600;
  color: #333;
}

.servicio-descripcion {
  margin: 0 0 10px;
  font-size: 12px;
  color: #666;
  line-height: 1.4;
}

.servicio-meta {
  display: flex;
  gap: 16px;
}

.meta-item {
  display: flex;
  align-items: center;
  gap: 6px;
  font-size: 13px;
}

.meta-item.precio {
  color: #4facfe;
  font-weight: 700;
}

.meta-item.precio span {
  font-size: 16px;
}

.meta-item.duracion {
  color: #666;
}

.meta-item i {
  font-size: 12px;
}

.servicio-actions {
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

.btn-icon-sm.danger {
  background: #ffebee;
  color: #c62828;
}

.btn-icon-sm.success {
  background: #e8f5e9;
  color: #2e7d32;
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

/* Form */
.form-container {
  display: flex;
  flex-direction: column;
  gap: 16px;
}

.form-group {
  display: flex;
  flex-direction: column;
  gap: 6px;
}

.form-group label {
  font-size: 13px;
  font-weight: 600;
  color: #333;
}

.form-group input,
.form-group textarea,
.form-group select {
  padding: 12px;
  border: 1px solid #e0e0e0;
  border-radius: 10px;
  font-size: 14px;
  transition: border-color 0.2s;
}

.form-group input:focus,
.form-group textarea:focus,
.form-group select:focus {
  outline: none;
  border-color: #4facfe;
}

.form-row {
  display: flex;
  gap: 12px;
}

.form-row .form-group {
  flex: 1;
}

.input-prefix {
  display: flex;
  align-items: center;
  border: 1px solid #e0e0e0;
  border-radius: 10px;
  overflow: hidden;
}

.input-prefix span {
  padding: 12px;
  background: #f8f9fa;
  color: #666;
  font-weight: 600;
}

.input-prefix input {
  border: none;
  border-radius: 0;
  flex: 1;
}

.btn-submit {
  width: 100%;
  padding: 14px;
  background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
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
}
</style>
