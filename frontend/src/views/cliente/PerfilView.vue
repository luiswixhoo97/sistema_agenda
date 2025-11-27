<template>
  <div class="perfil-view">
    <!-- Header -->
    <header class="page-header">
      <h1>Mi Perfil</h1>
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
        <h3>Información Personal</h3>
        
        <form @submit.prevent="guardarPerfil">
          <div class="form-group">
            <label>Nombre completo</label>
            <input
              v-model="formData.nombre"
              type="text"
              :disabled="!editando"
              placeholder="Tu nombre"
            />
          </div>

          <div class="form-group">
            <label>Teléfono</label>
            <div class="phone-display">
              <span class="country-code">+52</span>
              <input
                v-model="formData.telefono"
                type="tel"
                disabled
                placeholder="10 dígitos"
              />
              <span class="verified-badge">✓ Verificado</span>
            </div>
          </div>

          <div class="form-group">
            <label>Email (opcional)</label>
            <input
              v-model="formData.email"
              type="email"
              :disabled="!editando"
              placeholder="tu@email.com"
            />
          </div>

          <div class="form-actions" v-if="editando">
            <button type="button" class="btn-cancel" @click="cancelarEdicion">
              Cancelar
            </button>
            <button type="submit" class="btn-save" :disabled="guardando">
              {{ guardando ? 'Guardando...' : 'Guardar' }}
            </button>
          </div>
        </form>

        <button v-if="!editando" class="btn-edit" @click="editando = true">
          ✏️ Editar información
        </button>
      </section>

      <!-- Preferencias de notificaciones -->
      <section class="perfil-section">
        <h3>Notificaciones</h3>
        
        <div class="notification-options">
          <label class="toggle-option">
            <span class="option-info">
              <span class="option-title">📱 WhatsApp</span>
              <span class="option-desc">Recibe confirmaciones y recordatorios</span>
            </span>
            <input type="checkbox" v-model="notificaciones.whatsapp" @change="actualizarNotificaciones" />
            <span class="toggle"></span>
          </label>

          <label class="toggle-option">
            <span class="option-info">
              <span class="option-title">📧 Email</span>
              <span class="option-desc">Recibe resúmenes de tus citas</span>
            </span>
            <input type="checkbox" v-model="notificaciones.email" @change="actualizarNotificaciones" />
            <span class="toggle"></span>
          </label>

          <label class="toggle-option">
            <span class="option-info">
              <span class="option-title">🔔 Push</span>
              <span class="option-desc">Notificaciones en tiempo real</span>
            </span>
            <input type="checkbox" v-model="notificaciones.push" @change="actualizarNotificaciones" />
            <span class="toggle"></span>
          </label>
        </div>
      </section>

      <!-- Estadísticas -->
      <section class="perfil-section stats-section">
        <h3>Mi Actividad</h3>
        
        <div class="stats-grid">
          <div class="stat-item">
            <span class="stat-value">{{ estadisticas.citasTotales }}</span>
            <span class="stat-label">Citas totales</span>
          </div>
          <div class="stat-item">
            <span class="stat-value">${{ estadisticas.totalGastado }}</span>
            <span class="stat-label">Total gastado</span>
          </div>
          <div class="stat-item">
            <span class="stat-value">{{ estadisticas.servicioFavorito }}</span>
            <span class="stat-label">Servicio favorito</span>
          </div>
        </div>
      </section>

      <!-- Acciones -->
      <section class="perfil-section actions-section">
        <button class="btn-action" @click="verHistorial">
          📋 Ver historial de citas
        </button>
        <button class="btn-action btn-logout" @click="cerrarSesion">
          🚪 Cerrar sesión
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
  router.push('/login-cliente');
}

onMounted(() => {
  cargarPerfil();
});
</script>

<style scoped>
.perfil-view {
  min-height: 100vh;
  background: #f8f9fa;
  padding-bottom: 80px;
}

.page-header {
  background: linear-gradient(135deg, #ec407a 0%, #d81b60 100%);
  color: white;
  padding: 24px 20px;
}

.page-header h1 {
  margin: 0;
  font-size: 24px;
}

.perfil-content {
  padding: 20px;
  max-width: 600px;
  margin: 0 auto;
}

.perfil-avatar-section {
  text-align: center;
  margin-bottom: 24px;
  margin-top: -50px;
}

.avatar {
  width: 100px;
  height: 100px;
  background: linear-gradient(135deg, #ec407a 0%, #d81b60 100%);
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
  font-size: 36px;
  font-weight: 700;
  margin: 0 auto 12px;
  border: 4px solid white;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
}

.perfil-avatar-section h2 {
  margin: 0 0 4px;
  font-size: 24px;
  color: #333;
}

.perfil-avatar-section p {
  margin: 0;
  color: #666;
  font-size: 14px;
}

.perfil-section {
  background: white;
  border-radius: 16px;
  padding: 20px;
  margin-bottom: 16px;
}

.perfil-section h3 {
  margin: 0 0 16px;
  font-size: 16px;
  color: #333;
}

.form-group {
  margin-bottom: 16px;
}

.form-group label {
  display: block;
  font-size: 14px;
  color: #666;
  margin-bottom: 6px;
}

.form-group input {
  width: 100%;
  padding: 12px 16px;
  border: 2px solid #e0e0e0;
  border-radius: 10px;
  font-size: 16px;
  box-sizing: border-box;
}

.form-group input:focus {
  outline: none;
  border-color: #ec407a;
}

.form-group input:disabled {
  background: #f5f5f5;
  color: #666;
}

.phone-display {
  display: flex;
  align-items: center;
  gap: 8px;
}

.country-code {
  padding: 12px;
  background: #f5f5f5;
  border-radius: 10px;
  color: #666;
}

.phone-display input {
  flex: 1;
}

.verified-badge {
  padding: 6px 12px;
  background: #e8f5e9;
  color: #2e7d32;
  border-radius: 12px;
  font-size: 12px;
  font-weight: 600;
}

.form-actions {
  display: flex;
  gap: 12px;
  margin-top: 20px;
}

.btn-cancel,
.btn-save {
  flex: 1;
  padding: 12px;
  border-radius: 10px;
  font-size: 16px;
  font-weight: 600;
  cursor: pointer;
}

.btn-cancel {
  background: #f5f5f5;
  border: none;
  color: #666;
}

.btn-save {
  background: linear-gradient(135deg, #ec407a 0%, #d81b60 100%);
  border: none;
  color: white;
}

.btn-edit {
  width: 100%;
  padding: 12px;
  background: #fce4ec;
  border: none;
  border-radius: 10px;
  color: #d81b60;
  font-size: 14px;
  font-weight: 600;
  cursor: pointer;
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
  padding: 12px;
  background: #f8f9fa;
  border-radius: 12px;
  cursor: pointer;
}

.option-info {
  display: flex;
  flex-direction: column;
}

.option-title {
  font-weight: 500;
  color: #333;
}

.option-desc {
  font-size: 12px;
  color: #666;
}

.toggle-option input {
  display: none;
}

.toggle {
  width: 48px;
  height: 28px;
  background: #e0e0e0;
  border-radius: 14px;
  position: relative;
  transition: background 0.2s;
}

.toggle::after {
  content: '';
  position: absolute;
  width: 24px;
  height: 24px;
  background: white;
  border-radius: 50%;
  top: 2px;
  left: 2px;
  transition: transform 0.2s;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
}

.toggle-option input:checked + .toggle {
  background: #ec407a;
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
  padding: 12px;
  background: #fce4ec;
  border-radius: 12px;
}

.stat-value {
  display: block;
  font-size: 20px;
  font-weight: 700;
  color: #d81b60;
}

.stat-label {
  font-size: 11px;
  color: #666;
}

/* Acciones */
.actions-section {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.btn-action {
  width: 100%;
  padding: 14px;
  background: #f5f5f5;
  border: none;
  border-radius: 12px;
  font-size: 16px;
  color: #333;
  cursor: pointer;
  text-align: left;
}

.btn-logout {
  color: #c62828;
}
</style>

