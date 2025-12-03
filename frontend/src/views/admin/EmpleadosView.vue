<template>
  <div class="admin-view">
    <!-- Header -->
    <div class="view-header">
      <div class="header-info">
        <div class="header-icon">
          <i class="fa fa-user-tie"></i>
        </div>
        <div class="header-text">
          <h1>Empleados</h1>
          <p class="header-subtitle">{{ empleados.length }} registrados</p>
        </div>
      </div>
      <button class="btn-action" @click="nuevoEmpleado">
        <i class="fa fa-plus"></i>
      </button>
    </div>

    <!-- Stats -->
    <div class="stats-mini">
      <div class="stat-mini">
        <i class="fa fa-user-check"></i>
        <span>{{ empleadosActivos }} activos</span>
      </div>
      <div class="stat-mini">
        <i class="fa fa-user-clock"></i>
        <span>{{ empleadosInactivos }} inactivos</span>
      </div>
    </div>

    <!-- Empleados List -->
    <div class="empleados-container">
      <div v-if="loading" class="loading-state">
        <div class="spinner"></div>
        <p>Cargando empleados...</p>
      </div>
      
      <div v-else class="empleados-grid">
        <div 
          v-for="empleado in empleados" 
          :key="empleado.id" 
          class="empleado-card"
          :class="{ inactivo: !empleado.active }"
        >
          <div class="empleado-header">
            <div class="empleado-avatar">
              {{ getInitial(empleado.nombre) }}
            </div>
            <span :class="['status-indicator', empleado.active ? 'activo' : 'inactivo']"></span>
          </div>
          
          <div class="empleado-body">
            <h3 class="empleado-nombre">{{ empleado.nombre }}</h3>
            
            <div class="empleado-contacto">
              <span><i class="fa fa-envelope"></i> {{ empleado.email }}</span>
              <span v-if="empleado.telefono"><i class="fa fa-phone"></i> {{ empleado.telefono }}</span>
            </div>
            
            <div class="empleado-servicios">
              <span 
                v-for="servicio in (empleado.servicios || []).slice(0, 3)" 
                :key="servicio.id" 
                class="servicio-tag"
              >
                {{ servicio.nombre }}
              </span>
              <span v-if="(empleado.servicios || []).length > 3" class="servicio-more">
                +{{ empleado.servicios.length - 3 }}
              </span>
              <span v-if="(empleado.servicios || []).length === 0" class="no-servicios">
                Sin servicios asignados
              </span>
            </div>
          </div>
          
          <div class="empleado-footer">
            <span :class="['status-badge', empleado.active ? 'activo' : 'inactivo']">
              {{ empleado.active ? 'Activo' : 'Inactivo' }}
            </span>
            <div class="empleado-actions">
              <button class="btn-icon-sm" @click="editarEmpleado(empleado)" title="Editar">
                <i class="fa fa-edit"></i>
              </button>
              <button class="btn-icon-sm servicios" @click="gestionarServicios(empleado)" title="Servicios">
                <i class="fa fa-cut"></i>
              </button>
              <button class="btn-icon-sm" @click="verHorarios(empleado)" title="Horarios">
                <i class="fa fa-calendar-alt"></i>
              </button>
              <button 
                class="btn-icon-sm" 
                :class="empleado.active ? 'danger' : 'success'"
                @click="toggleEmpleado(empleado)"
                :title="empleado.active ? 'Desactivar' : 'Activar'"
              >
                <i :class="empleado.active ? 'fa fa-ban' : 'fa fa-check'"></i>
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- Empty state -->
      <div v-if="!loading && empleados.length === 0" class="empty-state">
        <i class="fa fa-user-tie"></i>
        <p>No hay empleados registrados</p>
        <button class="btn-create" @click="nuevoEmpleado">
          <i class="fa fa-plus"></i>
          Agregar empleado
        </button>
      </div>
    </div>

    <!-- Modal -->
    <Teleport to="body">
      <div v-if="showModal" class="modal-overlay" @click.self="closeModal">
        <div class="modal-content">
          <div class="modal-header">
            <h3>{{ modalTitle }}</h3>
            <button class="modal-close" @click="closeModal">
              <i class="fa fa-times"></i>
            </button>
          </div>
          <div class="modal-body">
            <!-- Formulario de empleado -->
            <div v-if="modalType === 'form'" class="form-container">
              <div class="form-group">
                <label>Nombre completo <span class="required">*</span></label>
                <input v-model="formData.nombre" type="text" placeholder="Ej: Ana López" />
              </div>
              <div class="form-group">
                <label>Email <span class="required">*</span></label>
                <input v-model="formData.email" type="email" placeholder="ana@beautyspa.com" />
              </div>
              <div class="form-group">
                <label>Teléfono</label>
                <input v-model="formData.telefono" type="tel" placeholder="5511111111" />
              </div>
              
              <!-- Campo de contraseña -->
              <div class="form-group">
                <label>
                  {{ selectedEmpleado ? 'Nueva contraseña' : 'Contraseña' }}
                  <span v-if="!selectedEmpleado" class="required">*</span>
                  <span v-else class="optional">(dejar vacío para no cambiar)</span>
                </label>
                <div class="password-input-wrapper">
                  <input 
                    :type="showPassword ? 'text' : 'password'" 
                    v-model="formData.password" 
                    :placeholder="selectedEmpleado ? '••••••••' : 'Mínimo 6 caracteres'"
                    class="password-field"
                  />
                  <span class="toggle-password-btn" @click.prevent.stop="togglePasswordVisibility">
                    <i :class="showPassword ? 'fa fa-eye-slash' : 'fa fa-eye'"></i>
                  </span>
                </div>
              </div>

              <div class="form-group">
                <label>Biografía / Descripción</label>
                <textarea v-model="formData.bio" placeholder="Experiencia, especialidades..." rows="3"></textarea>
              </div>

              <div class="form-group">
                <label>Especialidades</label>
                <input v-model="formData.especialidades" type="text" placeholder="Cortes, Coloración, Peinados..." />
              </div>

              <!-- Servicios (solo para nuevo empleado) -->
              <div v-if="!selectedEmpleado" class="form-group">
                <label>Servicios que puede realizar</label>
                <div class="servicios-selector">
                  <div 
                    v-for="servicio in todosServicios" 
                    :key="servicio.id"
                    class="servicio-checkbox"
                    :class="{ selected: serviciosSeleccionados.includes(servicio.id) }"
                    @click="toggleServicioSeleccion(servicio.id)"
                  >
                    <i :class="serviciosSeleccionados.includes(servicio.id) ? 'fa fa-check-square' : 'fa fa-square'"></i>
                    <span>{{ servicio.nombre }}</span>
                    <small>${{ servicio.precio }}</small>
                  </div>
                  <p v-if="todosServicios.length === 0" class="no-servicios-msg">
                    No hay servicios disponibles. Crea servicios primero.
                  </p>
                </div>
              </div>

              <div v-if="formError" class="form-error">
                <i class="fa fa-exclamation-circle"></i>
                {{ formError }}
              </div>

              <button class="btn-submit" @click="guardarEmpleado" :disabled="guardando">
                <i v-if="guardando" class="fa fa-spinner fa-spin"></i>
                <i v-else class="fa fa-save"></i>
                {{ guardando ? 'Guardando...' : 'Guardar' }}
              </button>
            </div>
            
            <!-- Gestión de Servicios -->
            <div v-if="modalType === 'servicios'" class="servicios-container">
              <p class="servicios-description">
                Selecciona los servicios que <strong>{{ selectedEmpleado?.nombre }}</strong> puede realizar:
              </p>
              
              <!-- Buscador de servicios -->
              <div class="servicios-search">
                <i class="fa fa-search"></i>
                <input 
                  type="text" 
                  v-model="busquedaServicio" 
                  placeholder="Buscar servicio..."
                />
                <span v-if="busquedaServicio" class="clear-search" @click="busquedaServicio = ''">
                  <i class="fa fa-times"></i>
                </span>
              </div>
              
              <div class="servicios-list">
                <div 
                  v-for="servicio in serviciosFiltrados" 
                  :key="servicio.id"
                  class="servicio-item"
                  :class="{ selected: serviciosDelEmpleado.some(s => s.id === servicio.id) }"
                  @click="toggleServicioEmpleado(servicio)"
                >
                  <div class="servicio-check">
                    <i :class="serviciosDelEmpleado.some(s => s.id === servicio.id) ? 'fa fa-check-circle' : 'fa fa-circle'"></i>
                  </div>
                  <div class="servicio-info">
                    <span class="servicio-nombre">{{ servicio.nombre }}</span>
                    <span class="servicio-categoria">{{ servicio.categoria?.nombre || 'Sin categoría' }}</span>
                  </div>
                  <div class="servicio-precio">
                    <span class="precio-label">Precio</span>
                    <span class="precio-valor">${{ servicio.precio }}</span>
                  </div>
                  <div class="servicio-duracion">
                    <span class="duracion-label">Duración</span>
                    <span class="duracion-valor">{{ servicio.duracion }} min</span>
                  </div>
                </div>
                
                <!-- Sin resultados -->
                <div v-if="serviciosFiltrados.length === 0 && busquedaServicio" class="no-results">
                  <i class="fa fa-search"></i>
                  <p>No se encontraron servicios con "{{ busquedaServicio }}"</p>
                </div>
              </div>

              <div class="servicios-resumen">
                <span>{{ serviciosDelEmpleado.length }} servicio(s) seleccionado(s)</span>
              </div>

              <button class="btn-submit" @click="guardarServicios" :disabled="guardando">
                <i v-if="guardando" class="fa fa-spinner fa-spin"></i>
                <i v-else class="fa fa-save"></i>
                {{ guardando ? 'Guardando...' : 'Guardar Servicios' }}
              </button>
            </div>

            <!-- Horarios -->
            <div v-if="modalType === 'horarios'" class="horarios-container">
              <div class="horario-day" v-for="dia in diasSemana" :key="dia.value">
                <div class="day-header">
                  <span class="day-name">{{ dia.label }}</span>
                  <label class="toggle-day">
                    <input type="checkbox" v-model="dia.activo" />
                    <span class="toggle-switch"></span>
                  </label>
                </div>
                <div v-if="dia.activo" class="day-hours">
                  <div class="time-input-group">
                    <label class="time-label">Inicio</label>
                    <input type="time" v-model="dia.inicio" class="time-input" />
                  </div>
                  <span class="time-separator">a</span>
                  <div class="time-input-group">
                    <label class="time-label">Fin</label>
                    <input type="time" v-model="dia.fin" class="time-input" />
                  </div>
                </div>
              </div>
              <button class="btn-submit" @click="guardarHorarios" :disabled="guardando">
                <i v-if="guardando" class="fa fa-spinner fa-spin"></i>
                <i v-else class="fa fa-save"></i>
                {{ guardando ? 'Guardando...' : 'Guardar Horarios' }}
              </button>
            </div>
          </div>
        </div>
      </div>
    </Teleport>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, reactive } from 'vue';
import { 
  getEmpleados, 
  createEmpleado,
  updateEmpleado, 
  getEmpleadoHorarios, 
  updateEmpleadoHorarios,
  getEmpleadoServicios,
  updateEmpleadoServicios,
  getServicios
} from '@/services/adminService';

interface Servicio {
  id: number;
  nombre: string;
  precio: number;
  duracion: number;
  categoria?: { id: number; nombre: string };
}

const empleados = ref<any[]>([]);
const todosServicios = ref<Servicio[]>([]);
const loading = ref(true);
const guardando = ref(false);
const showModal = ref(false);
const modalType = ref<'form' | 'horarios' | 'servicios'>('form');
const selectedEmpleado = ref<any>(null);
const showPassword = ref(false);
const formError = ref('');
const busquedaServicio = ref('');

const formData = reactive({
  nombre: '',
  email: '',
  telefono: '',
  password: '',
  bio: '',
  especialidades: '',
});

// Servicios seleccionados para nuevo empleado
const serviciosSeleccionados = ref<number[]>([]);

// Servicios del empleado seleccionado (para edición de servicios)
const serviciosDelEmpleado = ref<{ id: number; precio_especial?: number | null }[]>([]);

const diasSemana = ref([
  { value: 1, label: 'Lunes', activo: true, inicio: '09:00', fin: '18:00' },
  { value: 2, label: 'Martes', activo: true, inicio: '09:00', fin: '18:00' },
  { value: 3, label: 'Miércoles', activo: true, inicio: '09:00', fin: '18:00' },
  { value: 4, label: 'Jueves', activo: true, inicio: '09:00', fin: '18:00' },
  { value: 5, label: 'Viernes', activo: true, inicio: '09:00', fin: '18:00' },
  { value: 6, label: 'Sábado', activo: true, inicio: '10:00', fin: '14:00' },
  { value: 0, label: 'Domingo', activo: false, inicio: '', fin: '' },
]);

const empleadosActivos = computed(() => empleados.value.filter(e => e.active).length);
const empleadosInactivos = computed(() => empleados.value.filter(e => !e.active).length);

// Filtrar servicios por búsqueda parcial
const serviciosFiltrados = computed(() => {
  if (!busquedaServicio.value.trim()) {
    return todosServicios.value;
  }
  const busqueda = busquedaServicio.value.toLowerCase().trim();
  return todosServicios.value.filter(servicio => 
    servicio.nombre.toLowerCase().includes(busqueda) ||
    (servicio.categoria?.nombre || '').toLowerCase().includes(busqueda)
  );
});

const modalTitle = computed(() => {
  if (modalType.value === 'horarios') return `Horarios de ${selectedEmpleado.value?.nombre || ''}`;
  if (modalType.value === 'servicios') return `Servicios de ${selectedEmpleado.value?.nombre || ''}`;
  return selectedEmpleado.value ? 'Editar Empleado' : 'Nuevo Empleado';
});

async function cargarEmpleados() {
  loading.value = true;
  try {
    const response = await getEmpleados();
    if (response.success) {
      empleados.value = response.data || [];
    }
  } catch (error) {
    console.error('Error cargando empleados:', error);
  } finally {
    loading.value = false;
  }
}

async function cargarServicios() {
  try {
    const response = await getServicios();
    if (response.success) {
      todosServicios.value = response.data || [];
    }
  } catch (error) {
    console.error('Error cargando servicios:', error);
  }
}

function getInitial(nombre: string): string {
  return nombre?.charAt(0)?.toUpperCase() || '?';
}

function togglePasswordVisibility() {
  showPassword.value = !showPassword.value;
}

function nuevoEmpleado() { 
  selectedEmpleado.value = null;
  formData.nombre = '';
  formData.email = '';
  formData.telefono = '';
  formData.password = '';
  formData.bio = '';
  formData.especialidades = '';
  serviciosSeleccionados.value = [];
  showPassword.value = false;
  formError.value = '';
  modalType.value = 'form';
  showModal.value = true;
}

function editarEmpleado(e: any) { 
  selectedEmpleado.value = e;
  formData.nombre = e.nombre;
  formData.email = e.email;
  formData.telefono = e.telefono || '';
  formData.password = '';
  formData.bio = e.bio || '';
  formData.especialidades = e.especialidades || '';
  showPassword.value = false;
  formError.value = '';
  modalType.value = 'form';
  showModal.value = true;
}

async function gestionarServicios(e: any) {
  selectedEmpleado.value = e;
  modalType.value = 'servicios';
  showModal.value = true;
  
  try {
    const response = await getEmpleadoServicios(e.id);
    if (response.success && response.data) {
      serviciosDelEmpleado.value = response.data.map((s: any) => ({
        id: s.id,
        precio_especial: s.precio_especial,
      }));
    } else {
      serviciosDelEmpleado.value = [];
    }
  } catch (error) {
    console.error('Error cargando servicios del empleado:', error);
    serviciosDelEmpleado.value = [];
  }
}

function toggleServicioEmpleado(servicio: Servicio) {
  const index = serviciosDelEmpleado.value.findIndex(s => s.id === servicio.id);
  if (index >= 0) {
    serviciosDelEmpleado.value.splice(index, 1);
  } else {
    serviciosDelEmpleado.value.push({ id: servicio.id, precio_especial: null });
  }
}

function toggleServicioSeleccion(servicioId: number) {
  const index = serviciosSeleccionados.value.indexOf(servicioId);
  if (index >= 0) {
    serviciosSeleccionados.value.splice(index, 1);
  } else {
    serviciosSeleccionados.value.push(servicioId);
  }
}

async function guardarServicios() {
  if (!selectedEmpleado.value) return;
  
  guardando.value = true;
  try {
    await updateEmpleadoServicios(selectedEmpleado.value.id, serviciosDelEmpleado.value);
    closeModal();
    await cargarEmpleados();
  } catch (error: any) {
    console.error('Error guardando servicios:', error);
    alert(error.response?.data?.message || 'Error al guardar servicios');
  } finally {
    guardando.value = false;
  }
}

async function verHorarios(e: any) { 
  selectedEmpleado.value = e;
  modalType.value = 'horarios';
  showModal.value = true;
  
  try {
    const response = await getEmpleadoHorarios(e.id);
    if (response.success && response.data) {
      diasSemana.value.forEach(dia => {
        const diaBackend = dia.value === 0 ? 7 : dia.value;
        const horario = response.data.find((h: any) => h.dia_semana === diaBackend);
        if (horario) {
          dia.activo = horario.active;
          dia.inicio = horario.hora_inicio;
          dia.fin = horario.hora_fin;
        } else {
          dia.activo = false;
          dia.inicio = '09:00';
          dia.fin = '18:00';
        }
      });
    }
  } catch (error) {
    console.error('Error cargando horarios:', error);
  }
}

async function toggleEmpleado(e: any) {
  try {
    const nuevoEstado = !e.active;
    await updateEmpleado(e.id, { active: nuevoEstado });
    e.active = nuevoEstado;
    await cargarEmpleados();
  } catch (error) {
    console.error('Error actualizando empleado:', error);
    alert('Error al actualizar empleado');
  }
}

async function guardarEmpleado() {
  formError.value = '';
  
  // Validaciones
  if (!formData.nombre.trim()) {
    formError.value = 'El nombre es requerido';
    return;
  }
  if (!formData.email.trim()) {
    formError.value = 'El email es requerido';
    return;
  }
  if (!selectedEmpleado.value && !formData.password) {
    formError.value = 'La contraseña es requerida para nuevos empleados';
    return;
  }
  if (formData.password && formData.password.length < 6) {
    formError.value = 'La contraseña debe tener al menos 6 caracteres';
    return;
  }

  guardando.value = true;
  try {
    if (selectedEmpleado.value) {
      // Actualizar empleado existente
      const datos: any = {
        nombre: formData.nombre,
        email: formData.email,
        telefono: formData.telefono || null,
        bio: formData.bio || null,
        especialidades: formData.especialidades || null,
      };
      if (formData.password) {
        datos.password = formData.password;
      }
      await updateEmpleado(selectedEmpleado.value.id, datos);
    } else {
      // Crear nuevo empleado
      const datos = {
        nombre: formData.nombre,
        email: formData.email,
        telefono: formData.telefono || null,
        password: formData.password,
        bio: formData.bio || null,
        especialidades: formData.especialidades || null,
        servicios: serviciosSeleccionados.value,
      };
      await createEmpleado(datos);
    }
    closeModal();
    await cargarEmpleados();
  } catch (error: any) {
    console.error('Error guardando empleado:', error);
    if (error.response?.data?.errors) {
      const errores = Object.values(error.response.data.errors).flat();
      formError.value = errores.join('. ');
    } else {
      formError.value = error.response?.data?.message || 'Error al guardar empleado';
    }
  } finally {
    guardando.value = false;
  }
}

async function guardarHorarios() {
  if (!selectedEmpleado.value) return;
  
  guardando.value = true;
  try {
    const horarios = diasSemana.value.map(dia => {
      let horaInicio = dia.activo && dia.inicio ? dia.inicio.trim() : '09:00';
      let horaFin = dia.activo && dia.fin ? dia.fin.trim() : '18:00';
      
      if (!/^([0-1][0-9]|2[0-3]):[0-5][0-9]$/.test(horaInicio)) {
        horaInicio = '09:00';
      }
      if (!/^([0-1][0-9]|2[0-3]):[0-5][0-9]$/.test(horaFin)) {
        horaFin = '18:00';
      }
      
      return {
        dia_semana: dia.value === 0 ? 7 : dia.value,
        active: !!dia.activo,
        hora_inicio: horaInicio,
        hora_fin: horaFin,
      };
    });
    
    await updateEmpleadoHorarios(selectedEmpleado.value.id, { horarios });
    closeModal();
    await cargarEmpleados();
  } catch (error: any) {
    console.error('Error guardando horarios:', error);
    const mensaje = error.response?.data?.message || 'Error al guardar horarios';
    alert(mensaje);
  } finally {
    guardando.value = false;
  }
}

function closeModal() {
  showModal.value = false;
  selectedEmpleado.value = null;
  formError.value = '';
  busquedaServicio.value = '';
  showPassword.value = false;
}

onMounted(async () => {
  await Promise.all([cargarEmpleados(), cargarServicios()]);
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
}

/* Stats Mini */
.stats-mini {
  display: flex;
  gap: 12px;
  margin-bottom: 16px;
}

.stat-mini {
  flex: 1;
  display: flex;
  align-items: center;
  gap: 8px;
  background: white;
  padding: 12px 16px;
  border-radius: 12px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
}

.stat-mini i {
  color: #f093fb;
}

.stat-mini span {
  font-size: 13px;
  color: #333;
  font-weight: 500;
}

/* Loading */
.loading-state {
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

/* Empleados Grid */
.empleados-grid {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.empleado-card {
  background: white;
  border-radius: 16px;
  overflow: hidden;
  box-shadow: 0 2px 12px rgba(0, 0, 0, 0.08);
  transition: transform 0.2s, opacity 0.2s;
}

.empleado-card.inactivo {
  opacity: 0.7;
}

.empleado-header {
  position: relative;
  display: flex;
  justify-content: center;
  padding: 20px 16px 10px;
  background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
}

.empleado-avatar {
  width: 60px;
  height: 60px;
  background: white;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  color: #f093fb;
  font-weight: 700;
  font-size: 24px;
  border: 3px solid white;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
}

.status-indicator {
  position: absolute;
  top: 16px;
  right: 16px;
  width: 12px;
  height: 12px;
  border-radius: 50%;
  border: 2px solid white;
}

.status-indicator.activo {
  background: #38ef7d;
}

.status-indicator.inactivo {
  background: #ff6b6b;
}

.empleado-body {
  padding: 16px;
  text-align: center;
}

.empleado-nombre {
  margin: 0 0 8px;
  font-size: 17px;
  font-weight: 600;
  color: #333;
}

.empleado-contacto {
  display: flex;
  flex-direction: column;
  gap: 4px;
  margin-bottom: 12px;
}

.empleado-contacto span {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 6px;
  font-size: 12px;
  color: #666;
}

.empleado-contacto i {
  color: #f093fb;
  width: 14px;
}

.empleado-servicios {
  display: flex;
  flex-wrap: wrap;
  gap: 6px;
  justify-content: center;
}

.servicio-tag {
  background: #fce4ec;
  color: #f5576c;
  padding: 4px 10px;
  border-radius: 10px;
  font-size: 11px;
  font-weight: 500;
}

.servicio-more {
  background: #f0f0f0;
  color: #666;
  padding: 4px 10px;
  border-radius: 10px;
  font-size: 11px;
}

.no-servicios {
  font-size: 11px;
  color: #999;
  font-style: italic;
}

.empleado-footer {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 12px 16px;
  background: #f8f9fa;
  border-top: 1px solid #f0f0f0;
}

.status-badge {
  padding: 4px 12px;
  border-radius: 8px;
  font-size: 11px;
  font-weight: 600;
}

.status-badge.activo {
  background: #e8f5e9;
  color: #2e7d32;
}

.status-badge.inactivo {
  background: #ffebee;
  color: #c62828;
}

.empleado-actions {
  display: flex;
  gap: 6px;
}

.btn-icon-sm {
  width: 34px;
  height: 34px;
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

.btn-icon-sm.servicios {
  background: #fff3e0;
  color: #ef6c00;
}

/* Empty State */
.empty-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 60px 20px;
  text-align: center;
}

.empty-state i {
  font-size: 48px;
  color: #ddd;
  margin-bottom: 16px;
}

.empty-state p {
  color: #999;
  margin-bottom: 20px;
}

.btn-create {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 12px 24px;
  background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
  color: white;
  border: none;
  border-radius: 12px;
  font-size: 14px;
  font-weight: 600;
  cursor: pointer;
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

.form-group label .required {
  color: #f5576c;
}

.form-group label .optional {
  font-weight: 400;
  font-size: 11px;
  color: #999;
}

.form-group input,
.form-group textarea {
  padding: 12px;
  border: 1px solid #e0e0e0;
  border-radius: 10px;
  font-size: 14px;
  transition: border-color 0.2s;
}

.form-group input:focus,
.form-group textarea:focus {
  outline: none;
  border-color: #f093fb;
}

.form-group textarea {
  resize: vertical;
  min-height: 80px;
}

/* Password Input */
.password-input-wrapper {
  position: relative;
  display: flex;
  align-items: center;
}

.password-field {
  flex: 1;
  padding: 12px;
  padding-right: 48px !important;
  border: 1px solid #e0e0e0;
  border-radius: 10px;
  font-size: 14px;
  transition: border-color 0.2s;
  width: 100%;
}

.password-field:focus {
  outline: none;
  border-color: #f093fb;
}

.toggle-password-btn {
  position: absolute;
  right: 4px;
  top: 50%;
  transform: translateY(-50%);
  width: 40px;
  height: 40px;
  display: flex;
  align-items: center;
  justify-content: center;
  color: #999;
  cursor: pointer;
  border-radius: 8px;
  transition: all 0.2s;
  z-index: 10;
  user-select: none;
}

.toggle-password-btn:hover {
  color: #f093fb;
  background: rgba(240, 147, 251, 0.1);
}

.toggle-password-btn:active {
  transform: translateY(-50%) scale(0.95);
}

.toggle-password-btn i {
  font-size: 16px;
  pointer-events: none;
}

/* Servicios Selector (para nuevo empleado) */
.servicios-selector {
  max-height: 200px;
  overflow-y: auto;
  border: 1px solid #e0e0e0;
  border-radius: 10px;
  padding: 8px;
}

.servicio-checkbox {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 10px;
  border-radius: 8px;
  cursor: pointer;
  transition: background 0.2s;
}

.servicio-checkbox:hover {
  background: #f8f9fa;
}

.servicio-checkbox.selected {
  background: #fce4ec;
}

.servicio-checkbox i {
  color: #999;
  font-size: 16px;
}

.servicio-checkbox.selected i {
  color: #f5576c;
}

.servicio-checkbox span {
  flex: 1;
  font-size: 13px;
  color: #333;
}

.servicio-checkbox small {
  font-size: 12px;
  color: #666;
  font-weight: 600;
}

.no-servicios-msg {
  text-align: center;
  color: #999;
  font-size: 13px;
  padding: 20px;
}

/* Form Error */
.form-error {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 12px;
  background: #ffebee;
  color: #c62828;
  border-radius: 10px;
  font-size: 13px;
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
}

.btn-submit:disabled {
  opacity: 0.7;
  cursor: not-allowed;
}

/* Servicios Container */
.servicios-container {
  display: flex;
  flex-direction: column;
  gap: 16px;
}

.servicios-description {
  font-size: 14px;
  color: #666;
  margin: 0;
}

/* Buscador de servicios */
.servicios-search {
  position: relative;
  display: flex;
  align-items: center;
  background: #f8f9fa;
  border: 2px solid #e0e0e0;
  border-radius: 12px;
  padding: 0 12px;
  transition: all 0.2s;
}

.servicios-search:focus-within {
  border-color: #f093fb;
  background: white;
  box-shadow: 0 0 0 3px rgba(240, 147, 251, 0.1);
}

.servicios-search i.fa-search {
  color: #999;
  font-size: 14px;
  margin-right: 10px;
}

.servicios-search input {
  flex: 1;
  border: none;
  background: transparent;
  padding: 12px 0;
  font-size: 14px;
  color: #333;
  outline: none;
}

.servicios-search input::placeholder {
  color: #999;
}

.clear-search {
  width: 28px;
  height: 28px;
  display: flex;
  align-items: center;
  justify-content: center;
  color: #999;
  cursor: pointer;
  border-radius: 50%;
  transition: all 0.2s;
}

.clear-search:hover {
  background: #e0e0e0;
  color: #666;
}

.no-results {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 40px 20px;
  color: #999;
  text-align: center;
}

.no-results i {
  font-size: 32px;
  margin-bottom: 12px;
  opacity: 0.5;
}

.no-results p {
  margin: 0;
  font-size: 14px;
}

.servicios-list {
  display: flex;
  flex-direction: column;
  gap: 8px;
  max-height: 400px;
  overflow-y: auto;
}

.servicio-item {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 12px;
  border: 2px solid #e0e0e0;
  border-radius: 12px;
  cursor: pointer;
  transition: all 0.2s;
}

.servicio-item:hover {
  border-color: #f093fb;
}

.servicio-item.selected {
  border-color: #f5576c;
  background: #fce4ec;
}

.servicio-check {
  font-size: 20px;
  color: #ccc;
}

.servicio-item.selected .servicio-check {
  color: #f5576c;
}

.servicio-info {
  flex: 1;
  min-width: 0;
}

.servicio-nombre {
  display: block;
  font-weight: 600;
  color: #333;
  font-size: 14px;
}

.servicio-categoria {
  display: block;
  font-size: 11px;
  color: #999;
}

.servicio-precio,
.servicio-duracion {
  text-align: center;
}

.precio-label,
.duracion-label {
  display: block;
  font-size: 10px;
  color: #999;
  text-transform: uppercase;
}

.precio-valor {
  display: block;
  font-weight: 700;
  color: #f5576c;
  font-size: 14px;
}

.duracion-valor {
  display: block;
  font-weight: 600;
  color: #666;
  font-size: 13px;
}

.servicios-resumen {
  text-align: center;
  padding: 10px;
  background: #f8f9fa;
  border-radius: 10px;
  font-size: 13px;
  color: #666;
}

/* Horarios */
.horarios-container {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.horario-day {
  background: white;
  border: 2px solid #f0f0f0;
  border-radius: 14px;
  padding: 16px;
  transition: all 0.2s;
}

.horario-day:hover {
  border-color: #f093fb;
  box-shadow: 0 2px 8px rgba(240, 147, 251, 0.1);
}

.day-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 12px;
}

.day-name {
  font-weight: 600;
  font-size: 15px;
  color: #333;
  flex: 1;
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
  width: 48px;
  height: 28px;
  background: #e0e0e0;
  border-radius: 14px;
  position: relative;
  transition: background 0.3s;
}

.toggle-switch::after {
  content: '';
  position: absolute;
  width: 24px;
  height: 24px;
  background: white;
  border-radius: 50%;
  top: 2px;
  left: 2px;
  transition: transform 0.3s;
  box-shadow: 0 2px 6px rgba(0, 0, 0, 0.2);
}

.toggle-day input:checked + .toggle-switch {
  background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
}

.toggle-day input:checked + .toggle-switch::after {
  transform: translateX(20px);
}

.day-hours {
  display: flex;
  align-items: flex-end;
  gap: 12px;
  margin-top: 12px;
  padding-top: 12px;
  border-top: 1px solid #f0f0f0;
}

.time-input-group {
  flex: 1;
  display: flex;
  flex-direction: column;
  gap: 6px;
}

.time-label {
  font-size: 11px;
  font-weight: 600;
  color: #666;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.time-input {
  width: 100%;
  padding: 10px 12px;
  border: 2px solid #e0e0e0;
  border-radius: 10px;
  font-size: 14px;
  font-weight: 600;
  color: #333;
  transition: all 0.2s;
  background: white;
}

.time-input:focus {
  outline: none;
  border-color: #f093fb;
  box-shadow: 0 0 0 3px rgba(240, 147, 251, 0.1);
}

.time-separator {
  color: #999;
  font-weight: 500;
  font-size: 14px;
  padding-bottom: 8px;
  flex-shrink: 0;
}
</style>
