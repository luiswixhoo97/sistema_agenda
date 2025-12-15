<template>
  <div class="perfil-view">
    <!-- Header -->
    <header class="perfil-header">
      <div class="header-content">
        <h1>Mi Perfil</h1>
      </div>
    </header>

    <!-- Contenido -->
    <div class="perfil-content">
      <!-- Avatar y nombre -->
      <div class="perfil-avatar-section">
        <div class="avatar">
          {{ iniciales }}
        </div>
        <h2>{{ cliente?.nombre }}</h2>
        <p>Cliente desde {{ fechaRegistro }}</p>
      </div>

      <!-- Información personal -->
      <section class="perfil-section">
        <div class="section-header">
          <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
            <circle cx="12" cy="7" r="4"></circle>
          </svg>
          <h3>Información Personal</h3>
        </div>
        
        <form @submit.prevent="guardarPerfil">
          <div class="form-group">
            <label class="form-label">
              <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                <circle cx="12" cy="7" r="4"></circle>
              </svg>
              Nombre completo
            </label>
            <input
              v-model="formData.nombre"
              type="text"
              :disabled="!editando"
              placeholder="Tu nombre"
              class="form-input"
            />
          </div>

          <div class="form-group">
            <label class="form-label">
              <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"></path>
              </svg>
              Teléfono
            </label>
            <div class="phone-display">
              <span class="country-code">+52</span>
              <input
                v-model="formData.telefono"
                type="tel"
                disabled
                placeholder="10 dígitos"
                class="form-input"
              />
              <span class="verified-badge">
                <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                  <polyline points="22 4 12 14.01 9 11.01"></polyline>
                </svg>
                Verificado
              </span>
            </div>
          </div>

          <div class="form-group">
            <label class="form-label">
              <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path>
                <polyline points="22,6 12,13 2,6"></polyline>
              </svg>
              Email (opcional)
            </label>
            <input
              v-model="formData.email"
              type="email"
              :disabled="!editando"
              placeholder="tu@email.com"
              class="form-input"
            />
          </div>

          <div class="form-actions" v-if="editando">
            <button type="button" class="btn-cancel" @click="cancelarEdicion">
              Cancelar
            </button>
            <button type="submit" class="btn-save" :disabled="guardando">
              <div v-if="guardando" class="spinner-small"></div>
              <svg v-else xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path>
                <polyline points="17 21 17 13 7 13 7 21"></polyline>
                <polyline points="7 3 7 8 15 8"></polyline>
              </svg>
              {{ guardando ? 'Guardando...' : 'Guardar' }}
            </button>
          </div>
        </form>

        <button v-if="!editando" class="btn-edit" @click="editando = true">
          <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
            <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
          </svg>
          Editar información
        </button>
      </section>

      <!-- Preferencias de notificaciones -->
      <section class="perfil-section">
        <div class="section-header">
          <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path>
            <path d="M13.73 21a2 2 0 0 1-3.46 0"></path>
          </svg>
          <h3>Notificaciones</h3>
        </div>
        
        <div class="notification-options">
          <label class="toggle-option">
            <div class="option-info">
              <div class="option-icon whatsapp">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"></path>
                </svg>
              </div>
              <div class="option-text">
                <span class="option-title">WhatsApp</span>
                <span class="option-desc">Recibe confirmaciones y recordatorios</span>
              </div>
            </div>
            <input type="checkbox" v-model="notificaciones.whatsapp" @change="actualizarNotificaciones" />
            <span class="toggle"></span>
          </label>

          <label class="toggle-option">
            <div class="option-info">
              <div class="option-icon email">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path>
                  <polyline points="22,6 12,13 2,6"></polyline>
                </svg>
              </div>
              <div class="option-text">
                <span class="option-title">Email</span>
                <span class="option-desc">Recibe resúmenes de tus citas</span>
              </div>
            </div>
            <input type="checkbox" v-model="notificaciones.email" @change="actualizarNotificaciones" />
            <span class="toggle"></span>
          </label>

          <label class="toggle-option">
            <div class="option-info">
              <div class="option-icon push">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path>
                  <path d="M13.73 21a2 2 0 0 1-3.46 0"></path>
                </svg>
              </div>
              <div class="option-text">
                <span class="option-title">Push</span>
                <span class="option-desc">Notificaciones en tiempo real</span>
              </div>
            </div>
            <input type="checkbox" v-model="notificaciones.push" @change="actualizarNotificaciones" />
            <span class="toggle"></span>
          </label>
        </div>
      </section>

      <!-- Estadísticas -->
      <section class="perfil-section stats-section">
        <div class="section-header">
          <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <line x1="18" y1="20" x2="18" y2="10"></line>
            <line x1="12" y1="20" x2="12" y2="4"></line>
            <line x1="6" y1="20" x2="6" y2="14"></line>
          </svg>
          <h3>Mi Actividad</h3>
        </div>
        
        <div class="stats-grid">
          <div class="stat-item">
            <div class="stat-icon">
              <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                <line x1="16" y1="2" x2="16" y2="6"></line>
                <line x1="8" y1="2" x2="8" y2="6"></line>
                <line x1="3" y1="10" x2="21" y2="10"></line>
              </svg>
            </div>
            <span class="stat-value">{{ estadisticas.citasTotales }}</span>
            <span class="stat-label">Citas totales</span>
          </div>
          <div class="stat-item">
            <div class="stat-icon">
              <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <line x1="12" y1="1" x2="12" y2="23"></line>
                <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path>
              </svg>
            </div>
            <span class="stat-value">${{ estadisticas.totalGastado }}</span>
            <span class="stat-label">Total gastado</span>
          </div>
          <div class="stat-item">
            <div class="stat-icon">
              <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"></path>
              </svg>
            </div>
            <span class="stat-value">{{ estadisticas.servicioFavorito }}</span>
            <span class="stat-label">Servicio favorito</span>
          </div>
        </div>
      </section>

      <!-- Acciones -->
      <section class="perfil-section actions-section">
        <button class="btn-action" @click="verHistorial">
          <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
            <polyline points="14 2 14 8 20 8"></polyline>
            <line x1="16" y1="13" x2="8" y2="13"></line>
            <line x1="16" y1="17" x2="8" y2="17"></line>
            <polyline points="10 9 9 9 8 9"></polyline>
          </svg>
          Ver historial de citas
        </button>
        <button class="btn-action btn-logout" @click="cerrarSesion">
          <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
            <polyline points="16 17 21 12 16 7"></polyline>
            <line x1="21" y1="12" x2="9" y2="12"></line>
          </svg>
          Cerrar sesión
        </button>
      </section>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, reactive } from 'vue';
import { useRouter } from 'vue-router';
import { useAuthStore } from '@/stores/auth';
import api from '@/services/api';

const router = useRouter();
const authStore = useAuthStore();

// Estado
const cliente = ref<any>(null);
const editando = ref(false);
const guardando = ref(false);

const formData = reactive({
  nombre: '',
  telefono: '',
  email: '',
});

const notificaciones = reactive({
  whatsapp: true,
  email: true,
  push: true,
});

const estadisticas = ref({
  citasTotales: 0,
  totalGastado: '0',
  servicioFavorito: '-',
});

// Computed
const iniciales = computed(() => {
  const nombre = cliente.value?.nombre || '';
  return nombre.split(' ').map((n: string) => n[0]).join('').slice(0, 2).toUpperCase();
});

const fechaRegistro = computed(() => {
  if (!cliente.value?.created_at) return '';
  return new Date(cliente.value.created_at).toLocaleDateString('es-MX', {
    month: 'long',
    year: 'numeric',
  });
});

// Métodos
async function cargarPerfil() {
  try {
    // const response = await api.get('/cliente/perfil');
    // cliente.value = response.data.cliente;
    
    // Datos de ejemplo
    cliente.value = {
      id: 1,
      nombre: authStore.user?.nombre || 'Usuario',
      telefono: '5512345678',
      email: 'usuario@email.com',
      created_at: '2024-06-01',
    };

    formData.nombre = cliente.value.nombre;
    formData.telefono = cliente.value.telefono;
    formData.email = cliente.value.email || '';

    estadisticas.value = {
      citasTotales: 15,
      totalGastado: '4,350',
      servicioFavorito: 'Corte',
    };
  } catch (error) {
    console.error('Error cargando perfil:', error);
  }
}

async function guardarPerfil() {
  guardando.value = true;
  try {
    // await api.put('/cliente/perfil', formData);
    cliente.value.nombre = formData.nombre;
    cliente.value.email = formData.email;
    editando.value = false;
  } catch (error) {
    console.error('Error guardando perfil:', error);
  } finally {
    guardando.value = false;
  }
}

function cancelarEdicion() {
  formData.nombre = cliente.value?.nombre || '';
  formData.email = cliente.value?.email || '';
  editando.value = false;
}

async function actualizarNotificaciones() {
  try {
    // await api.put('/cliente/perfil/notificaciones', notificaciones);
    console.log('Notificaciones actualizadas:', notificaciones);
  } catch (error) {
    console.error('Error actualizando notificaciones:', error);
  }
}

function verHistorial() {
  router.push('/mis-citas');
}

function cerrarSesion() {
  authStore.logout();
  router.push('/');
}

onMounted(() => {
  cargarPerfil();
});
</script>

<style scoped>
/* ===== Apple-inspired Perfil View Design ===== */

.perfil-view {
  min-height: 100vh;
  background: #f5f5f7;
  padding-bottom: 100px;
  font-family: -apple-system, BlinkMacSystemFont, 'SF Pro Display', 'SF Pro Text', 'Helvetica Neue', sans-serif;
  -webkit-font-smoothing: antialiased;
  -moz-osx-font-smoothing: grayscale;
}

/* Header */
.perfil-header {
  background: linear-gradient(135deg, #1d1d1f 0%, #3a3a3c 100%);
  color: white;
  padding: 24px 20px 32px;
  position: relative;
}

.header-content h1 {
  margin: 0;
  font-size: 28px;
  font-weight: 600;
  letter-spacing: -0.4px;
}

/* Content */
.perfil-content {
  padding: 0 20px;
  max-width: 600px;
  margin: 0 auto;
}

/* Avatar Section */
.perfil-avatar-section {
  text-align: center;
  margin-bottom: 24px;
  margin-top: -60px;
  position: relative;
  z-index: 1;
}

.avatar {
  width: 120px;
  height: 120px;
  background: linear-gradient(135deg, #007aff 0%, #5856d6 100%);
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
  font-size: 42px;
  font-weight: 700;
  margin: 0 auto 16px;
  border: 5px solid #ffffff;
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
  letter-spacing: -1px;
}

.perfil-avatar-section h2 {
  margin: 0 0 6px;
  font-size: 24px;
  font-weight: 600;
  color: #1d1d1f;
  letter-spacing: -0.3px;
}

.perfil-avatar-section p {
  margin: 0;
  color: #86868b;
  font-size: 14px;
}

/* Sections */
.perfil-section {
  background: #ffffff;
  border-radius: 20px;
  padding: 20px;
  margin-bottom: 16px;
  box-shadow: 0 2px 12px rgba(0, 0, 0, 0.08);
  border: 1px solid #e5e5ea;
}

.section-header {
  display: flex;
  align-items: center;
  gap: 10px;
  margin-bottom: 20px;
}

.section-header svg {
  color: #007aff;
  flex-shrink: 0;
}

.section-header h3 {
  margin: 0;
  font-size: 18px;
  font-weight: 600;
  color: #1d1d1f;
  letter-spacing: -0.3px;
}

/* Form */
.form-group {
  margin-bottom: 20px;
}

.form-group:last-child {
  margin-bottom: 0;
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

.form-input:disabled {
  background: #f5f5f7;
  color: #86868b;
  cursor: not-allowed;
}

.form-input::placeholder {
  color: #c7c7cc;
}

/* Phone Display */
.phone-display {
  display: flex;
  align-items: center;
  gap: 10px;
}

.country-code {
  padding: 14px 16px;
  background: #f5f5f7;
  border-radius: 12px;
  color: #86868b;
  font-size: 15px;
  font-weight: 500;
  flex-shrink: 0;
  border: 1px solid #e5e5ea;
}

.phone-display .form-input {
  flex: 1;
}

.verified-badge {
  display: flex;
  align-items: center;
  gap: 4px;
  padding: 6px 12px;
  background: #e8f5e9;
  color: #34c759;
  border-radius: 12px;
  font-size: 12px;
  font-weight: 600;
  flex-shrink: 0;
}

.verified-badge svg {
  flex-shrink: 0;
}

/* Form Actions */
.form-actions {
  display: flex;
  gap: 12px;
  margin-top: 24px;
}

.btn-cancel,
.btn-save {
  flex: 1;
  padding: 14px 20px;
  border-radius: 12px;
  font-size: 15px;
  font-weight: 600;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  transition: all 0.2s;
  border: none;
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

.btn-save {
  background: linear-gradient(135deg, #007aff 0%, #5856d6 100%);
  color: white;
  box-shadow: 0 4px 12px rgba(0, 122, 255, 0.3);
}

.btn-save:active:not(:disabled) {
  transform: scale(0.98);
  box-shadow: 0 2px 8px rgba(0, 122, 255, 0.4);
}

.btn-save:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.spinner-small {
  width: 14px;
  height: 14px;
  border: 2px solid rgba(255, 255, 255, 0.3);
  border-top-color: white;
  border-radius: 50%;
  animation: spin 0.6s linear infinite;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}

.btn-edit {
  width: 100%;
  padding: 14px 20px;
  background: #e8f4fd;
  border: 1px solid #b3d9ff;
  border-radius: 12px;
  color: #007aff;
  font-size: 15px;
  font-weight: 600;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  transition: all 0.2s;
  margin-top: 20px;
}

.btn-edit:active {
  background: #d0e8fd;
  transform: scale(0.98);
}

.btn-edit svg {
  flex-shrink: 0;
}

/* Notificaciones */
.notification-options {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.toggle-option {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 14px;
  background: #f5f5f7;
  border-radius: 14px;
  cursor: pointer;
  transition: background 0.2s;
  border: 1px solid transparent;
}

.toggle-option:active {
  background: #e5e5ea;
}

.option-info {
  display: flex;
  align-items: center;
  gap: 12px;
  flex: 1;
  min-width: 0;
}

.option-icon {
  width: 40px;
  height: 40px;
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
  box-shadow: 0 2px 6px rgba(0, 0, 0, 0.1);
}

.option-icon.whatsapp {
  background: linear-gradient(135deg, #34c759 0%, #30d158 100%);
  color: white;
}

.option-icon.email {
  background: linear-gradient(135deg, #007aff 0%, #5ac8fa 100%);
  color: white;
}

.option-icon.push {
  background: linear-gradient(135deg, #ff9500 0%, #ffad33 100%);
  color: white;
}

.option-text {
  display: flex;
  flex-direction: column;
  gap: 2px;
  flex: 1;
  min-width: 0;
}

.option-title {
  font-weight: 600;
  color: #1d1d1f;
  font-size: 15px;
  letter-spacing: -0.2px;
}

.option-desc {
  font-size: 13px;
  color: #86868b;
  line-height: 1.3;
}

.toggle-option input {
  display: none;
}

.toggle {
  width: 50px;
  height: 30px;
  background: #c7c7cc;
  border-radius: 15px;
  position: relative;
  transition: background 0.2s;
  flex-shrink: 0;
}

.toggle::after {
  content: '';
  position: absolute;
  width: 26px;
  height: 26px;
  background: white;
  border-radius: 50%;
  top: 2px;
  left: 2px;
  transition: transform 0.2s cubic-bezier(0.4, 0, 0.2, 1);
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
}

.toggle-option input:checked + .toggle {
  background: #34c759;
}

.toggle-option input:checked + .toggle::after {
  transform: translateX(20px);
}

/* Estadísticas */
.stats-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 12px;
}

.stat-item {
  text-align: center;
  padding: 16px 12px;
  background: #f5f5f7;
  border-radius: 14px;
  border: 1px solid #e5e5ea;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 8px;
}

.stat-icon {
  width: 36px;
  height: 36px;
  border-radius: 10px;
  background: linear-gradient(135deg, #007aff 0%, #5856d6 100%);
  color: white;
  display: flex;
  align-items: center;
  justify-content: center;
  box-shadow: 0 2px 6px rgba(0, 122, 255, 0.2);
}

.stat-value {
  display: block;
  font-size: 20px;
  font-weight: 700;
  color: #1d1d1f;
  letter-spacing: -0.3px;
  line-height: 1.2;
}

.stat-label {
  font-size: 11px;
  color: #86868b;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  line-height: 1.2;
}

/* Acciones */
.actions-section {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.btn-action {
  width: 100%;
  padding: 16px 20px;
  background: #ffffff;
  border: 1px solid #e5e5ea;
  border-radius: 14px;
  font-size: 15px;
  font-weight: 500;
  color: #1d1d1f;
  cursor: pointer;
  display: flex;
  align-items: center;
  gap: 12px;
  transition: all 0.2s;
  text-align: left;
}

.btn-action:active {
  background: #f5f5f7;
  transform: scale(0.98);
}

.btn-action svg {
  color: #007aff;
  flex-shrink: 0;
}

.btn-logout {
  color: #ff3b30;
  border-color: #ffebee;
}

.btn-logout svg {
  color: #ff3b30;
}

.btn-logout:active {
  background: #ffebee;
}

/* Responsive */
@media (max-width: 640px) {
  .perfil-header {
    padding: 20px 16px 28px;
  }
  
  .header-content h1 {
    font-size: 24px;
  }
  
  .perfil-content {
    padding: 0 16px;
  }
  
  .perfil-avatar-section {
    margin-top: -50px;
  }
  
  .avatar {
    width: 100px;
    height: 100px;
    font-size: 36px;
    border-width: 4px;
  }
  
  .perfil-avatar-section h2 {
    font-size: 22px;
  }
  
  .perfil-section {
    padding: 16px;
    border-radius: 16px;
  }
  
  .section-header h3 {
    font-size: 16px;
  }
  
  .stats-grid {
    gap: 8px;
  }
  
  .stat-item {
    padding: 12px 8px;
  }
  
  .stat-icon {
    width: 32px;
    height: 32px;
  }
  
  .stat-value {
    font-size: 18px;
  }
  
  .stat-label {
    font-size: 10px;
  }
}
</style>
