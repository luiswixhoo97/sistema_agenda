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
              <span><i class="fa fa-phone"></i> {{ empleado.telefono }}</span>
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
            </div>
          </div>
          
          <div class="empleado-footer">
            <span :class="['status-badge', empleado.active ? 'activo' : 'inactivo']">
              {{ empleado.active ? 'Activo' : 'Inactivo' }}
            </span>
            <div class="empleado-actions">
              <button class="btn-icon-sm" @click="editarEmpleado(empleado)">
                <i class="fa fa-edit"></i>
              </button>
              <button class="btn-icon-sm" @click="verHorarios(empleado)">
                <i class="fa fa-calendar-alt"></i>
              </button>
              <button 
                class="btn-icon-sm" 
                :class="empleado.active ? 'danger' : 'success'"
                @click="toggleEmpleado(empleado)"
              >
                <i :class="empleado.active ? 'fa fa-ban' : 'fa fa-check'"></i>
              </button>
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
            <h3>{{ modalTitle }}</h3>
            <button class="modal-close" @click="closeModal">
              <i class="fa fa-times"></i>
            </button>
          </div>
          <div class="modal-body">
            <!-- Formulario -->
            <div v-if="modalType === 'form'" class="form-container">
              <div class="form-group">
                <label>Nombre completo</label>
                <input v-model="formData.nombre" type="text" placeholder="Ej: Ana López" />
              </div>
              <div class="form-group">
                <label>Email</label>
                <input v-model="formData.email" type="email" placeholder="ana@beautyspa.com" />
              </div>
              <div class="form-group">
                <label>Teléfono</label>
                <input v-model="formData.telefono" type="tel" placeholder="5511111111" />
              </div>
              <button class="btn-submit" @click="guardarEmpleado">
                <i class="fa fa-save"></i>
                Guardar
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
              <button class="btn-submit" @click="guardarHorarios">
                <i class="fa fa-save"></i>
                Guardar Horarios
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
import { getEmpleados, updateEmpleado, getEmpleadoHorarios, updateEmpleadoHorarios } from '@/services/adminService';

const empleados = ref<any[]>([]);
const loading = ref(true);
const showModal = ref(false);
const modalType = ref<'form' | 'horarios'>('form');
const selectedEmpleado = ref<any>(null);

const formData = reactive({
  nombre: '',
  email: '',
  telefono: '',
});

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

const modalTitle = computed(() => {
  if (modalType.value === 'horarios') return 'Horarios';
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

function getInitial(nombre: string): string {
  return nombre?.charAt(0)?.toUpperCase() || '?';
}

function nuevoEmpleado() { 
  selectedEmpleado.value = null;
  formData.nombre = '';
  formData.email = '';
  formData.telefono = '';
  modalType.value = 'form';
  showModal.value = true;
}

function editarEmpleado(e: any) { 
  selectedEmpleado.value = e;
  formData.nombre = e.nombre;
  formData.email = e.email;
  formData.telefono = e.telefono;
  modalType.value = 'form';
  showModal.value = true;
}

async function verHorarios(e: any) { 
  selectedEmpleado.value = e;
  modalType.value = 'horarios';
  showModal.value = true;
  
  try {
    const response = await getEmpleadoHorarios(e.id);
    if (response.success && response.data) {
      // Mapear horarios del backend a la estructura local
      diasSemana.value.forEach(dia => {
        // El backend devuelve domingo como 7, pero el frontend usa 0
        const diaBackend = dia.value === 0 ? 7 : dia.value;
        const horario = response.data.find((h: any) => h.dia_semana === diaBackend);
        if (horario) {
          dia.activo = horario.active;
          dia.inicio = horario.hora_inicio;
          dia.fin = horario.hora_fin;
        } else {
          // Si no hay horario, el día está inactivo
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
    // Recargar la lista para asegurar sincronización
    await cargarEmpleados();
  } catch (error) {
    console.error('Error actualizando empleado:', error);
    alert('Error al actualizar empleado');
  }
}

async function guardarEmpleado() {
  try {
    if (selectedEmpleado.value) {
      await updateEmpleado(selectedEmpleado.value.id, formData);
    }
    closeModal();
    cargarEmpleados();
  } catch (error) {
    console.error('Error guardando empleado:', error);
    alert('Error al guardar empleado');
  }
}

async function guardarHorarios() {
  if (!selectedEmpleado.value) return;
  
  try {
    // Convertir domingo de 0 a 7 para que pase la validación del backend (min:1)
    const horarios = diasSemana.value.map(dia => {
      // Si el día está activo, usar las horas ingresadas; si no, usar valores por defecto
      let horaInicio = dia.activo && dia.inicio ? dia.inicio.trim() : '09:00';
      let horaFin = dia.activo && dia.fin ? dia.fin.trim() : '18:00';
      
      // Validar formato de hora (HH:mm)
      if (!/^([0-1][0-9]|2[0-3]):[0-5][0-9]$/.test(horaInicio)) {
        horaInicio = '09:00';
      }
      if (!/^([0-1][0-9]|2[0-3]):[0-5][0-9]$/.test(horaFin)) {
        horaFin = '18:00';
      }
      
      return {
        dia_semana: dia.value === 0 ? 7 : dia.value,
        active: !!dia.activo, // Asegurar que sea boolean
        hora_inicio: horaInicio,
        hora_fin: horaFin,
      };
    });
    
    await updateEmpleadoHorarios(selectedEmpleado.value.id, { horarios });
    closeModal();
    cargarEmpleados(); // Recargar para ver cambios
  } catch (error: any) {
    console.error('Error guardando horarios:', error);
    console.error('Response data:', error.response?.data);
    const mensaje = error.response?.data?.message || 
                   (error.response?.data?.errors ? JSON.stringify(error.response.data.errors) : 'Error al guardar horarios');
    alert(mensaje);
  }
}

function closeModal() {
  showModal.value = false;
  selectedEmpleado.value = null;
}

onMounted(() => {
  cargarEmpleados();
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

.form-group input {
  padding: 12px;
  border: 1px solid #e0e0e0;
  border-radius: 10px;
  font-size: 14px;
  transition: border-color 0.2s;
}

.form-group input:focus {
  outline: none;
  border-color: #f093fb;
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
