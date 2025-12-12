<template>
  <div class="servicios-view">
    <!-- Header -->
    <header class="servicios-header">
      <div class="header-left">
        <div class="header-icon">
          <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"></path>
          </svg>
        </div>
        <div class="header-text">
          <h1>Servicios</h1>
          <p class="header-subtitle">{{ servicios.length }} disponibles</p>
        </div>
      </div>
      <button class="btn-new-servicio" @click="nuevoServicio">
        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
          <line x1="12" y1="5" x2="12" y2="19"></line>
          <line x1="5" y1="12" x2="19" y2="12"></line>
        </svg>
        Nuevo servicio
      </button>
    </header>

    <!-- Categories Tabs -->
    <section class="categories-section">
      <div class="categories-scroll">
        <div class="categories-tabs">
          <button 
            :class="['category-tab', { active: !categoriaActiva }]"
            @click="categoriaActiva = null"
          >
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <rect x="3" y="3" width="7" height="7"></rect>
              <rect x="14" y="3" width="7" height="7"></rect>
              <rect x="14" y="14" width="7" height="7"></rect>
              <rect x="3" y="14" width="7" height="7"></rect>
            </svg>
            Todos
          </button>
          <button 
            v-for="cat in categorias" 
            :key="cat.id" 
            :class="['category-tab', { active: categoriaActiva === cat.id }]"
            @click="categoriaActiva = cat.id"
          >
            {{ cat.nombre }}
          </button>
        </div>
      </div>
    </section>

    <!-- Servicios Grid -->
    <div class="servicios-container">
      <div v-if="loading" class="loading-container">
        <div class="loader"></div>
        <p>Cargando servicios...</p>
      </div>
      
      <div v-else-if="serviciosFiltrados.length === 0" class="empty-state">
        <div class="empty-icon">
          <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
            <path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"></path>
          </svg>
        </div>
        <p>No hay servicios en esta categoría</p>
      </div>
      
      <div v-else class="servicios-grid">
        <div 
          v-for="servicio in serviciosFiltrados" 
          :key="servicio.id" 
          class="servicio-card"
          :class="{ inactivo: !isActivo(servicio) }"
        >
          <div class="servicio-icon-wrapper">
            <div class="servicio-icon">
              <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"></path>
              </svg>
            </div>
            <span :class="['status-badge', isActivo(servicio) ? 'activo' : 'inactivo']">
              {{ isActivo(servicio) ? 'Activo' : 'Inactivo' }}
            </span>
          </div>
          
          <div class="servicio-content">
            <h3 class="servicio-nombre">{{ servicio.nombre }}</h3>
            <p class="servicio-descripcion" v-if="servicio.descripcion">{{ servicio.descripcion }}</p>
            
            <div class="servicio-info-row">
              <div class="info-item">
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <line x1="12" y1="1" x2="12" y2="23"></line>
                  <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path>
                </svg>
                <span class="info-value">${{ formatPrecio(servicio.precio) }}</span>
              </div>
              <div class="info-item">
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <circle cx="12" cy="12" r="10"></circle>
                  <polyline points="12 6 12 12 16 14"></polyline>
                </svg>
                <span class="info-value">{{ servicio.duracion_minutos || servicio.duracion }} min</span>
              </div>
            </div>
          </div>
          
          <div class="servicio-actions">
            <button class="action-btn" @click.stop="editarServicio(servicio)" title="Editar">
              <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
              </svg>
            </button>
            <button 
              class="action-btn"
              :class="isActivo(servicio) ? 'danger' : 'success'"
              @click.stop="toggleServicio(servicio)"
              :title="isActivo(servicio) ? 'Desactivar' : 'Activar'"
            >
              <svg v-if="isActivo(servicio)" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path>
                <line x1="1" y1="1" x2="23" y2="23"></line>
              </svg>
              <svg v-else xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                <circle cx="12" cy="12" r="3"></circle>
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
            <h3>{{ selectedServicio ? 'Editar Servicio' : 'Nuevo Servicio' }}</h3>
            <button class="modal-close" @click="closeModal">
              <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <line x1="18" y1="6" x2="6" y2="18"></line>
                <line x1="6" y1="6" x2="18" y2="18"></line>
              </svg>
            </button>
          </div>
          <div class="modal-body">
            <form class="servicio-form" @submit.prevent="guardarServicio">
              <div class="form-section">
                <label class="form-label">
                  <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"></path>
                  </svg>
                  Nombre del servicio <span class="required">*</span>
                </label>
                <input 
                  v-model="formData.nombre" 
                  type="text" 
                  class="form-input"
                  placeholder="Ej: Corte de cabello"
                  required
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
                  Descripción <span class="optional">(opcional)</span>
                </label>
                <textarea 
                  v-model="formData.descripcion" 
                  class="form-textarea"
                  rows="3" 
                  placeholder="Breve descripción del servicio..."
                ></textarea>
              </div>
              
              <div class="form-row">
                <div class="form-section">
                  <label class="form-label">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                      <line x1="12" y1="1" x2="12" y2="23"></line>
                      <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path>
                    </svg>
                    Precio <span class="required">*</span>
                  </label>
                  <div class="input-prefix">
                    <span class="prefix-symbol">$</span>
                    <input 
                      v-model.number="formData.precio" 
                      type="number" 
                      class="form-input"
                      placeholder="0.00"
                      step="0.01"
                      min="0"
                      required
                    />
                  </div>
                </div>
                <div class="form-section">
                  <label class="form-label">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                      <circle cx="12" cy="12" r="10"></circle>
                      <polyline points="12 6 12 12 16 14"></polyline>
                    </svg>
                    Duración (min) <span class="required">*</span>
                  </label>
                  <input 
                    v-model.number="formData.duracion_minutos" 
                    type="number" 
                    class="form-input"
                    placeholder="30"
                    min="5"
                    max="480"
                    required
                  />
                </div>
              </div>
              
              <div class="form-section">
                <label class="form-label">
                  <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <rect x="3" y="3" width="7" height="7"></rect>
                    <rect x="14" y="3" width="7" height="7"></rect>
                    <rect x="14" y="14" width="7" height="7"></rect>
                    <rect x="3" y="14" width="7" height="7"></rect>
                  </svg>
                  Categoría <span class="required">*</span>
                </label>
                <select v-model="formData.categoria_id" class="form-select" required>
                  <option :value="null" disabled>Selecciona una categoría</option>
                  <option v-for="cat in categorias" :key="cat.id" :value="cat.id">
                    {{ cat.nombre }}
                  </option>
                </select>
              </div>
              
              <div class="form-actions">
                <button type="button" class="btn-cancel" @click="closeModal">
                  Cancelar
                </button>
                <button type="submit" class="btn-submit">
                  <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path>
                    <polyline points="17 21 17 13 7 13 7 21"></polyline>
                    <polyline points="7 3 7 8 15 8"></polyline>
                  </svg>
                  Guardar Servicio
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
import { getServicios, getCategorias, createServicio, updateServicio } from '@/services/adminService';
import Swal from 'sweetalert2';

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
  // El backend devuelve 'active', no 'activo'
  const activo = servicio.active !== undefined ? servicio.active : servicio.activo;
  if (activo === true || activo === 1 || activo === '1' || activo === 'true') {
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
    await updateServicio(s.id, { active: nuevoEstado });
    // Actualizar el estado local
    s.active = nuevoEstado;
    s.activo = nuevoEstado; // Mantener compatibilidad
  } catch (error: any) {
    console.error('Error actualizando servicio:', error);
    Swal.fire({
      icon: 'error',
      title: 'Error',
      text: error.response?.data?.message || 'Error al actualizar servicio',
      confirmButtonColor: '#ff3b30'
    });
    // Recargar en caso de error para sincronizar
    await cargarDatos();
  }
}

async function guardarServicio() {
  // Validación básica
  if (!formData.nombre || formData.nombre.trim() === '') {
    Swal.fire({
      icon: 'warning',
      title: 'Campo requerido',
      text: 'El nombre del servicio es obligatorio',
      confirmButtonColor: '#ff3b30'
    });
    return;
  }
  
  if (formData.precio < 0) {
    Swal.fire({
      icon: 'warning',
      title: 'Precio inválido',
      text: 'El precio debe ser mayor o igual a 0',
      confirmButtonColor: '#ff3b30'
    });
    return;
  }
  
  if (formData.duracion_minutos < 5 || formData.duracion_minutos > 480) {
    Swal.fire({
      icon: 'warning',
      title: 'Duración inválida',
      text: 'La duración debe estar entre 5 y 480 minutos',
      confirmButtonColor: '#ff3b30'
    });
    return;
  }
  
  // Validar que categoria_id esté seleccionada
  if (!formData.categoria_id || formData.categoria_id === null) {
    Swal.fire({
      icon: 'warning',
      title: 'Campo requerido',
      text: 'Debes seleccionar una categoría',
      confirmButtonColor: '#ff3b30'
    });
    return;
  }
  
  try {
    // Preparar los datos para enviar (el backend espera 'duracion', no 'duracion_minutos')
    const dataToSend: any = {
      nombre: formData.nombre.trim(),
      precio: Number(formData.precio) || 0,
      duracion: Number(formData.duracion_minutos) || 30, // Backend espera 'duracion'
      categoria_id: formData.categoria_id, // Requerido por el backend
    };
    
    // Solo incluir descripcion si tiene valor
    if (formData.descripcion && formData.descripcion.trim() !== '') {
      dataToSend.descripcion = formData.descripcion.trim();
    }
    
    if (selectedServicio.value) {
      await updateServicio(selectedServicio.value.id, dataToSend);
    } else {
      await createServicio(dataToSend);
    }
    
    Swal.fire({
      icon: 'success',
      title: '¡Éxito!',
      text: selectedServicio.value ? 'Servicio actualizado correctamente' : 'Servicio creado correctamente',
      confirmButtonColor: '#34c759',
      timer: 1500,
      showConfirmButton: false
    });
    
    closeModal();
    await cargarDatos();
  } catch (error: any) {
    console.error('Error guardando servicio:', error);
    
    // Obtener el mensaje de error del backend
    let errorMessage = 'Error al guardar servicio';
    if (error.response?.data?.message) {
      errorMessage = error.response.data.message;
    } else if (error.response?.data?.errors) {
      // Si hay errores de validación, mostrar todos los errores
      const errors = error.response.data.errors;
      const errorMessages: string[] = [];
      Object.keys(errors).forEach(key => {
        if (Array.isArray(errors[key])) {
          errorMessages.push(...errors[key]);
        } else {
          errorMessages.push(errors[key]);
        }
      });
      if (errorMessages.length > 0) {
        errorMessage = errorMessages.join('\n');
      }
    } else if (error.message) {
      errorMessage = error.message;
    }
    
    Swal.fire({
      icon: 'error',
      title: 'Error al guardar',
      text: errorMessage,
      confirmButtonColor: '#ff3b30'
    });
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
/* ===== Apple-inspired Servicios View Design ===== */

.servicios-view {
  min-height: 100vh;
  background: #f5f5f7;
  padding: 24px;
  padding-bottom: 120px;
  font-family: -apple-system, BlinkMacSystemFont, 'SF Pro Display', 'SF Pro Text', 'Helvetica Neue', sans-serif;
  -webkit-font-smoothing: antialiased;
  -moz-osx-font-smoothing: grayscale;
}

/* Header */
.servicios-header {
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

.btn-new-servicio {
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

.btn-new-servicio:active {
  background: rgba(255, 255, 255, 0.25);
  transform: scale(0.98);
}

/* Categories */
.categories-section {
  margin-bottom: 20px;
}

.categories-scroll {
  overflow-x: auto;
  margin: 0 -24px;
  padding: 0 24px;
  -webkit-overflow-scrolling: touch;
  scrollbar-width: none;
  -ms-overflow-style: none;
}

.categories-scroll::-webkit-scrollbar {
  display: none;
}

.categories-tabs {
  display: flex;
  gap: 10px;
  padding-bottom: 4px;
}

.category-tab {
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

.category-tab:active {
  transform: scale(0.98);
}

.category-tab.active {
  background: #007aff;
  border-color: #007aff;
  color: white;
  box-shadow: 0 2px 8px rgba(0, 122, 255, 0.3);
}

.category-tab svg {
  flex-shrink: 0;
}

/* Loading & Empty */
.loading-container {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 60px 20px;
  color: #86868b;
}

.loading-container p {
  margin: 16px 0 0;
  font-size: 15px;
  color: #86868b;
}

.loader {
  width: 32px;
  height: 32px;
  border: 3px solid #f5f5f7;
  border-top-color: #007aff;
  border-radius: 50%;
  animation: spin 0.8s linear infinite;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}

.empty-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 60px 20px;
  text-align: center;
}

.empty-icon {
  color: #d1d1d6;
  margin-bottom: 16px;
}

.empty-state p {
  color: #86868b;
  font-size: 15px;
  margin: 0;
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
  gap: 16px;
  background: #ffffff;
  border-radius: 16px;
  padding: 18px;
  border: 1px solid #e5e5ea;
  transition: all 0.2s ease;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
  opacity: 1;
}

.servicio-card.inactivo {
  opacity: 0.6;
  background: #f8f9fa;
}

.servicio-icon-wrapper {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 8px;
  flex-shrink: 0;
}

.servicio-icon {
  width: 56px;
  height: 56px;
  background: rgba(0, 122, 255, 0.1);
  border-radius: 14px;
  display: flex;
  align-items: center;
  justify-content: center;
  color: #007aff;
}

.status-badge {
  padding: 3px 8px;
  border-radius: 8px;
  font-size: 10px;
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

.servicio-content {
  flex: 1;
  min-width: 0;
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.servicio-nombre {
  margin: 0;
  font-size: 18px;
  font-weight: 600;
  color: #1d1d1f;
  letter-spacing: -0.2px;
}

.servicio-descripcion {
  margin: 0;
  font-size: 13px;
  color: #86868b;
  line-height: 1.5;
}

.servicio-info-row {
  display: flex;
  gap: 16px;
  margin-top: 4px;
}

.info-item {
  display: flex;
  align-items: center;
  gap: 6px;
  font-size: 14px;
}

.info-item svg {
  color: #86868b;
  flex-shrink: 0;
}

.info-value {
  font-weight: 600;
  color: #1d1d1f;
}

.info-item:first-child .info-value {
  color: #007aff;
  font-size: 16px;
}

.servicio-actions {
  display: flex;
  flex-direction: column;
  gap: 6px;
  flex-shrink: 0;
}

.action-btn {
  width: 36px;
  height: 36px;
  border: none;
  border-radius: 10px;
  background: #f5f5f7;
  color: #86868b;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all 0.2s;
}

.action-btn:active {
  transform: scale(0.95);
  background: #e5e5ea;
}

.action-btn.danger {
  background: rgba(255, 59, 48, 0.12);
  color: #ff3b30;
}

.action-btn.success {
  background: rgba(52, 199, 89, 0.12);
  color: #34c759;
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
  background: white;
  width: 100%;
  max-height: 90vh;
  border-radius: 24px 24px 0 0;
  overflow: hidden;
  animation: slideUp 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  box-shadow: 0 -8px 32px rgba(0, 0, 0, 0.2);
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
  background: #ffffff;
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
  color: #86868b;
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
  background: #f5f5f7;
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

/* Form */
.servicio-form {
  display: flex;
  flex-direction: column;
  gap: 20px;
}

.form-section {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.form-label {
  display: flex;
  align-items: center;
  gap: 8px;
  font-size: 13px;
  font-weight: 600;
  color: #007aff;
  text-transform: uppercase;
  letter-spacing: 0.3px;
}

.form-label svg {
  flex-shrink: 0;
}

.required {
  color: #ff3b30;
}

.optional {
  color: #86868b;
  font-weight: 400;
  text-transform: none;
  font-size: 11px;
}

.form-input,
.form-textarea,
.form-select {
  width: 100%;
  padding: 14px 16px;
  border: 1px solid #e5e5ea;
  border-radius: 14px;
  font-size: 15px;
  background: white;
  color: #1d1d1f;
  transition: all 0.2s;
  font-family: inherit;
}

.form-input:focus,
.form-textarea:focus,
.form-select:focus {
  outline: none;
  border-color: #007aff;
  box-shadow: 0 0 0 4px rgba(0, 122, 255, 0.1);
}

.form-textarea {
  resize: vertical;
  min-height: 80px;
}

.form-row {
  display: flex;
  gap: 12px;
}

.form-row .form-section {
  flex: 1;
}

.input-prefix {
  display: flex;
  align-items: center;
  border: 1px solid #e5e5ea;
  border-radius: 14px;
  overflow: hidden;
  background: white;
  transition: all 0.2s;
}

.input-prefix:focus-within {
  border-color: #007aff;
  box-shadow: 0 0 0 4px rgba(0, 122, 255, 0.1);
}

.prefix-symbol {
  padding: 14px 12px;
  background: #f5f5f7;
  color: #86868b;
  font-weight: 600;
  font-size: 15px;
  border-right: 1px solid #e5e5ea;
}

.input-prefix .form-input {
  border: none;
  border-radius: 0;
  padding-left: 12px;
  box-shadow: none;
}

.input-prefix .form-input:focus {
  box-shadow: none;
}

.form-actions {
  display: flex;
  gap: 12px;
  margin-top: 8px;
  padding-top: 20px;
  border-top: 1px solid #e5e5ea;
}

.btn-cancel,
.btn-submit {
  flex: 1;
  padding: 16px;
  border: none;
  border-radius: 12px;
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
}

.btn-cancel:active {
  background: #e5e5ea;
  transform: scale(0.98);
}

.btn-submit {
  background: #34c759;
  color: white;
}

.btn-submit:active {
  transform: scale(0.98);
  background: #30d158;
}
</style>
