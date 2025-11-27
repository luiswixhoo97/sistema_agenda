<template>
  <div class="admin-view">
    <!-- Header -->
    <div class="view-header">
      <div class="header-info">
        <div class="header-icon">
          <i class="fa fa-cog"></i>
        </div>
        <div class="header-text">
          <h1>Configuración</h1>
          <p class="header-subtitle">Ajustes del sistema</p>
        </div>
      </div>
      <button 
        class="btn-save" 
        @click="guardarConfiguracion" 
        :disabled="guardando"
      >
        <i :class="guardando ? 'fa fa-spinner fa-spin' : 'fa fa-save'"></i>
      </button>
    </div>

    <!-- Loading -->
    <div v-if="loading" class="loading-state">
      <div class="spinner"></div>
      <p>Cargando configuración...</p>
    </div>

    <!-- Config Sections -->
    <div v-else class="config-container">
      <!-- Negocio -->
      <div class="config-card">
        <div class="card-header">
          <div class="card-icon blue">
            <i class="fa fa-store"></i>
          </div>
          <h3>Información del Negocio</h3>
        </div>
        <div class="card-body">
          <!-- Nombre del negocio -->
          <div class="info-field">
            <div class="field-icon name">
              <i class="fa fa-building"></i>
            </div>
            <div class="field-content">
              <label>Nombre del negocio</label>
              <input 
                v-model="config.nombre_negocio" 
                type="text" 
                placeholder="Ej: BeautySpa" 
                class="field-input"
              />
            </div>
          </div>

          <!-- Teléfono -->
          <div class="info-field">
            <div class="field-icon phone">
              <i class="fa fa-phone"></i>
            </div>
            <div class="field-content">
              <label>Teléfono de contacto</label>
              <input 
                v-model="config.telefono" 
                type="tel" 
                placeholder="Ej: (555) 123-4567" 
                class="field-input"
              />
            </div>
          </div>

          <!-- Email -->
          <div class="info-field">
            <div class="field-icon email">
              <i class="fa fa-envelope"></i>
            </div>
            <div class="field-content">
              <label>Correo electrónico</label>
              <input 
                v-model="(config as any).email" 
                type="email" 
                placeholder="Ej: contacto@beautyspa.com" 
                class="field-input"
              />
            </div>
          </div>

          <!-- Dirección -->
          <div class="info-field full-width">
            <div class="field-icon address">
              <i class="fa fa-map-marker-alt"></i>
            </div>
            <div class="field-content">
              <label>Dirección completa</label>
              <textarea 
                v-model="config.direccion" 
                rows="2" 
                placeholder="Calle, Número, Colonia, Ciudad, Estado, C.P."
                class="field-textarea"
              ></textarea>
            </div>
          </div>
        </div>
      </div>

      <!-- Horario -->
      <div class="config-card">
        <div class="card-header">
          <div class="card-icon green">
            <i class="fa fa-clock"></i>
          </div>
          <h3>Horario de Atención</h3>
        </div>
        <div class="card-body">
          <!-- Horario de apertura y cierre -->
          <div class="horario-times">
            <div class="time-block">
              <div class="time-icon apertura">
                <i class="fa fa-sun"></i>
              </div>
              <div class="time-content">
                <span class="time-label">Apertura</span>
                <input 
                  v-model="config.hora_apertura" 
                  type="time" 
                  class="time-input"
                />
              </div>
            </div>
            <div class="time-divider">
              <div class="divider-line"></div>
              <span class="divider-text">hasta</span>
              <div class="divider-line"></div>
            </div>
            <div class="time-block">
              <div class="time-icon cierre">
                <i class="fa fa-moon"></i>
              </div>
              <div class="time-content">
                <span class="time-label">Cierre</span>
                <input 
                  v-model="config.hora_cierre" 
                  type="time" 
                  class="time-input"
                />
              </div>
            </div>
          </div>

          <!-- Días laborales -->
          <div class="dias-section">
            <div class="dias-title">
              <span>Días de atención</span>
              <span class="dias-badge">{{ config.dias_laborales.length }}/7</span>
            </div>
            <div class="dias-grid-new">
              <button
                v-for="dia in diasSemana"
                :key="dia.value"
                type="button"
                class="dia-btn"
                :class="{ active: config.dias_laborales.includes(dia.value) }"
                @click="toggleDiaDirecto(dia.value)"
              >
                <span class="dia-abbr">{{ dia.abbr }}</span>
                <span class="dia-full">{{ dia.label }}</span>
                <i class="fa fa-check dia-check"></i>
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- Citas -->
      <div class="config-card">
        <div class="card-header">
          <div class="card-icon purple">
            <i class="fa fa-calendar-alt"></i>
          </div>
          <h3>Configuración de Citas</h3>
        </div>
        <div class="card-body">
          <!-- Anticipación -->
          <div class="citas-section">
            <div class="section-title">
              <i class="fa fa-clock"></i>
              <span>Anticipación</span>
            </div>
            <div class="citas-grid">
              <div class="cita-setting">
                <div class="setting-icon min">
                  <i class="fa fa-hourglass-start"></i>
                </div>
                <div class="setting-content">
                  <label>Mínima</label>
                  <div class="setting-input-wrapper">
                    <input 
                      v-model.number="config.anticipacion_minima" 
                      type="number" 
                      min="0" 
                      class="setting-input"
                    />
                    <span class="setting-unit">horas</span>
                  </div>
                </div>
              </div>
              <div class="cita-setting">
                <div class="setting-icon max">
                  <i class="fa fa-hourglass-end"></i>
                </div>
                <div class="setting-content">
                  <label>Máxima</label>
                  <div class="setting-input-wrapper">
                    <input 
                      v-model.number="config.anticipacion_maxima" 
                      type="number" 
                      min="1" 
                      class="setting-input"
                    />
                    <span class="setting-unit">días</span>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Tiempos -->
          <div class="citas-section">
            <div class="section-title">
              <i class="fa fa-stopwatch"></i>
              <span>Tiempos</span>
            </div>
            <div class="citas-grid">
              <div class="cita-setting">
                <div class="setting-icon buffer">
                  <i class="fa fa-pause-circle"></i>
                </div>
                <div class="setting-content">
                  <label>Buffer entre citas</label>
                  <div class="setting-input-wrapper">
                    <input 
                      v-model.number="config.buffer_citas" 
                      type="number" 
                      min="0" 
                      step="5" 
                      class="setting-input"
                    />
                    <span class="setting-unit">min</span>
                  </div>
                </div>
              </div>
              <div class="cita-setting">
                <div class="setting-icon cancel">
                  <i class="fa fa-times-circle"></i>
                </div>
                <div class="setting-content">
                  <label>Cancelación</label>
                  <div class="setting-input-wrapper">
                    <input 
                      v-model.number="config.tiempo_cancelacion" 
                      type="number" 
                      min="0" 
                      class="setting-input"
                    />
                    <span class="setting-unit">horas</span>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Notificaciones -->
      <div class="config-card">
        <div class="card-header">
          <div class="card-icon orange">
            <i class="fa fa-bell"></i>
          </div>
          <h3>Notificaciones</h3>
        </div>
        <div class="card-body">
          <!-- Confirmación -->
          <div class="notif-item">
            <div class="notif-icon confirm">
              <i class="fa fa-check-circle"></i>
            </div>
            <div class="notif-content">
              <div class="notif-title">Confirmación de cita</div>
              <div class="notif-desc">Enviar mensaje automático al confirmar</div>
            </div>
            <label class="notif-toggle">
              <input 
                type="checkbox" 
                v-model="config.notif_confirmacion"
              />
              <span class="notif-switch"></span>
            </label>
          </div>
          
          <!-- Recordatorio -->
          <div class="notif-item">
            <div class="notif-icon reminder">
              <i class="fa fa-clock"></i>
            </div>
            <div class="notif-content">
              <div class="notif-title">Recordatorio</div>
              <div class="notif-desc">Avisar 24 horas antes de la cita</div>
            </div>
            <label class="notif-toggle">
              <input 
                type="checkbox" 
                v-model="config.notif_recordatorio"
              />
              <span class="notif-switch"></span>
            </label>
          </div>
          
          <!-- Cancelación -->
          <div class="notif-item">
            <div class="notif-icon cancel">
              <i class="fa fa-times-circle"></i>
            </div>
            <div class="notif-content">
              <div class="notif-title">Cancelaciones</div>
              <div class="notif-desc">Notificar cuando se cancele una cita</div>
            </div>
            <label class="notif-toggle">
              <input 
                type="checkbox" 
                v-model="config.notif_cancelacion"
              />
              <span class="notif-switch"></span>
            </label>
          </div>
        </div>
      </div>

      <!-- Botón guardar móvil -->
      <button 
        class="btn-save-mobile" 
        @click="guardarConfiguracion" 
        :disabled="guardando"
      >
        <i :class="guardando ? 'fa fa-spinner fa-spin' : 'fa fa-save'"></i>
        {{ guardando ? 'Guardando...' : 'Guardar Cambios' }}
      </button>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, onMounted } from 'vue';
import { getConfiguracion, updateConfiguracion } from '@/services/adminService';

const loading = ref(true);
const guardando = ref(false);
const mostrarDias = ref(false);

const diasSemana = [
  { value: 1, label: 'Lunes', short: 'L', abbr: 'LUN' },
  { value: 2, label: 'Martes', short: 'M', abbr: 'MAR' },
  { value: 3, label: 'Miércoles', short: 'X', abbr: 'MIÉ' },
  { value: 4, label: 'Jueves', short: 'J', abbr: 'JUE' },
  { value: 5, label: 'Viernes', short: 'V', abbr: 'VIE' },
  { value: 6, label: 'Sábado', short: 'S', abbr: 'SÁB' },
  { value: 0, label: 'Domingo', short: 'D', abbr: 'DOM' },
];

const config = reactive({
  nombre_negocio: 'BeautySpa',
  telefono: '(555) 123-4567',
  direccion: 'Calle Principal #123, Ciudad',
  hora_apertura: '09:00',
  hora_cierre: '20:00',
  dias_laborales: [1, 2, 3, 4, 5, 6] as number[],
  anticipacion_minima: 2,
  anticipacion_maxima: 60,
  buffer_citas: 10,
  tiempo_cancelacion: 2,
  notif_confirmacion: true,
  notif_recordatorio: true,
  notif_cancelacion: true,
});

async function cargarConfiguracion() {
  loading.value = true;
  try {
    const response = await getConfiguracion();
    if (response.success && response.data) {
      // Mapear configuraciones del servidor
      response.data.forEach((c: any) => {
        if (c.clave in config) {
          (config as any)[c.clave] = c.valor;
        }
      });
    }
  } catch (error) {
    console.error('Error cargando configuración:', error);
  } finally {
    loading.value = false;
  }
}

function toggleDia(diaValue: number, event: Event) {
  const checked = (event.target as HTMLInputElement).checked;
  if (checked) {
    if (!config.dias_laborales.includes(diaValue)) {
      config.dias_laborales.push(diaValue);
    }
  } else {
    const index = config.dias_laborales.indexOf(diaValue);
    if (index > -1) {
      config.dias_laborales.splice(index, 1);
    }
  }
}

function toggleDiaDirecto(diaValue: number) {
  const index = config.dias_laborales.indexOf(diaValue);
  if (index > -1) {
    config.dias_laborales.splice(index, 1);
  } else {
    config.dias_laborales.push(diaValue);
  }
}

function getDiasSeleccionadosTexto(): string {
  if (config.dias_laborales.length === 0) return 'Ninguno';
  if (config.dias_laborales.length === 7) return 'Todos';
  
  const nombres = config.dias_laborales
    .map(val => diasSemana.find(d => d.value === val)?.short || '')
    .filter(Boolean)
    .join(', ');
  return nombres;
}

async function guardarConfiguracion() {
  guardando.value = true;
  try {
    const configuraciones = Object.entries(config).map(([clave, valor]) => ({
      clave,
      valor,
    }));
    
    await updateConfiguracion(configuraciones);
    alert('Configuración guardada correctamente');
  } catch (error) {
    console.error('Error guardando configuración:', error);
    alert('Error al guardar configuración');
  } finally {
    guardando.value = false;
  }
}

onMounted(() => {
  cargarConfiguracion();
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
  background: linear-gradient(135deg, #536976 0%, #292e49 100%);
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

.btn-save {
  width: 44px;
  height: 44px;
  background: rgba(255, 255, 255, 0.2);
  border: none;
  border-radius: 12px;
  color: white;
  font-size: 18px;
  cursor: pointer;
  transition: background 0.2s;
}

.btn-save:disabled {
  opacity: 0.6;
  cursor: not-allowed;
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
  border-top-color: #536976;
  border-radius: 50%;
  animation: spin 0.8s linear infinite;
  margin-bottom: 12px;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}

/* Config Container */
.config-container {
  display: flex;
  flex-direction: column;
  gap: 16px;
}

/* Config Card */
.config-card {
  background: white;
  border-radius: 18px;
  overflow: hidden;
  box-shadow: 0 2px 12px rgba(0, 0, 0, 0.08);
}

.card-header {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 16px;
  border-bottom: 1px solid #f0f0f0;
}

.card-icon {
  width: 40px;
  height: 40px;
  border-radius: 10px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 18px;
}

.card-icon.blue {
  background: rgba(79, 172, 254, 0.15);
  color: #4facfe;
}

.card-icon.green {
  background: rgba(56, 239, 125, 0.15);
  color: #11998e;
}

.card-icon.purple {
  background: rgba(118, 75, 162, 0.15);
  color: #764ba2;
}

.card-icon.orange {
  background: rgba(250, 112, 154, 0.15);
  color: #fa709a;
}

.card-header h3 {
  margin: 0;
  font-size: 16px;
  font-weight: 600;
  color: #333;
}

.card-body {
  padding: 16px;
}

/* Form */
.form-group {
  margin-bottom: 14px;
}

.form-group:last-child {
  margin-bottom: 0;
}

.form-group label {
  display: block;
  font-size: 12px;
  font-weight: 600;
  color: #666;
  margin-bottom: 6px;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.form-group input,
.form-group textarea {
  width: 100%;
  padding: 12px;
  border: 1px solid #e0e0e0;
  border-radius: 10px;
  font-size: 14px;
  color: #333;
  transition: border-color 0.2s;
  box-sizing: border-box;
}

/* Info Fields - Mejorado */
.info-field {
  display: flex;
  align-items: flex-start;
  gap: 12px;
  margin-bottom: 16px;
  padding: 12px;
  background: #f8f9fa;
  border-radius: 14px;
  transition: all 0.2s;
}

.info-field:last-child {
  margin-bottom: 0;
}

.info-field.full-width {
  width: 100%;
}

.info-field:focus-within {
  background: #fff;
  box-shadow: 0 0 0 2px rgba(79, 172, 254, 0.2);
}

.field-icon {
  width: 44px;
  height: 44px;
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 18px;
  flex-shrink: 0;
  transition: transform 0.2s;
}

.info-field:focus-within .field-icon {
  transform: scale(1.05);
}

.field-icon.name {
  background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
  color: white;
}

.field-icon.phone {
  background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);
  color: white;
}

.field-icon.email {
  background: linear-gradient(135deg, #fa709a 0%, #fee140 100%);
  color: white;
}

.field-icon.address {
  background: linear-gradient(135deg, #30cfd0 0%, #330867 100%);
  color: white;
}

.field-content {
  flex: 1;
  min-width: 0;
}

.field-content label {
  display: block;
  font-size: 11px;
  font-weight: 600;
  color: #999;
  margin-bottom: 6px;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.field-input,
.field-textarea {
  width: 100%;
  padding: 0;
  border: none;
  background: transparent;
  font-size: 15px;
  font-weight: 600;
  color: #333;
  outline: none;
  box-sizing: border-box;
}

.field-input::placeholder,
.field-textarea::placeholder {
  color: #bbb;
  font-weight: 400;
}

.field-textarea {
  resize: none;
  min-height: 50px;
  font-family: inherit;
  line-height: 1.5;
}

.form-group input:focus,
.form-group textarea:focus {
  outline: none;
  border-color: #667eea;
}

.form-row {
  display: flex;
  gap: 12px;
}

.form-row .form-group {
  flex: 1;
}

/* Time Row */
.time-row {
  display: flex;
  align-items: flex-end;
  gap: 8px;
  margin-bottom: 14px;
}

/* Horario Times - Nuevo diseño */
.horario-times {
  display: flex;
  align-items: center;
  gap: 8px;
  margin-bottom: 24px;
  width: 100%;
  box-sizing: border-box;
}

.time-block {
  flex: 1;
  min-width: 0;
  display: flex;
  align-items: center;
  gap: 10px;
  background: #f8f9fa;
  border-radius: 14px;
  padding: 10px 12px;
  transition: all 0.2s;
  box-sizing: border-box;
}

.time-block:focus-within {
  background: #fff;
  box-shadow: 0 0 0 2px #34c759;
}

.time-icon {
  width: 36px;
  height: 36px;
  border-radius: 10px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 14px;
  flex-shrink: 0;
}

.time-icon.apertura {
  background: linear-gradient(135deg, #f6d365 0%, #fda085 100%);
  color: white;
}

.time-icon.cierre {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
}

.time-content {
  display: flex;
  flex-direction: column;
  flex: 1;
  min-width: 0;
}

.time-label {
  font-size: 10px;
  color: #999;
  font-weight: 500;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  white-space: nowrap;
}

.time-input {
  border: none;
  background: transparent;
  font-size: 16px;
  font-weight: 700;
  color: #333;
  padding: 0;
  width: 100%;
  outline: none;
  min-width: 0;
}

.time-divider {
  display: flex;
  align-items: center;
  gap: 4px;
  padding: 0 2px;
  flex-shrink: 0;
}

.divider-line {
  width: 6px;
  height: 2px;
  background: #e0e0e0;
  border-radius: 1px;
}

.divider-text {
  font-size: 10px;
  color: #999;
  font-weight: 500;
  white-space: nowrap;
}

/* Días Section */
.dias-section {
  background: #f8f9fa;
  border-radius: 16px;
  padding: 16px;
}

.dias-title {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 16px;
}

.dias-title span:first-child {
  font-size: 14px;
  font-weight: 600;
  color: #333;
}

.dias-badge {
  background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
  color: white;
  font-size: 12px;
  font-weight: 700;
  padding: 4px 12px;
  border-radius: 20px;
}

.dias-grid-new {
  display: grid;
  grid-template-columns: repeat(7, 1fr);
  gap: 6px;
}

.dia-btn {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 10px 4px;
  background: white;
  border: 2px solid #e8e8e8;
  border-radius: 12px;
  cursor: pointer;
  transition: all 0.2s ease;
  position: relative;
  min-height: 60px;
}

.dia-btn .dia-abbr {
  font-size: 11px;
  font-weight: 700;
  color: #999;
  text-transform: uppercase;
}

.dia-btn .dia-full {
  display: none;
}

.dia-btn .dia-check {
  position: absolute;
  top: 4px;
  right: 4px;
  font-size: 8px;
  color: white;
  background: #34c759;
  width: 14px;
  height: 14px;
  border-radius: 50%;
  display: none;
  align-items: center;
  justify-content: center;
}

.dia-btn.active {
  background: linear-gradient(135deg, #34c759 0%, #30d158 100%);
  border-color: transparent;
  transform: scale(1.05);
  box-shadow: 0 4px 12px rgba(52, 199, 89, 0.3);
}

.dia-btn.active .dia-abbr {
  color: white;
  font-weight: 800;
}

.dia-btn.active .dia-check {
  display: flex;
  background: white;
  color: #34c759;
}

.dia-btn:not(.active):hover {
  border-color: #34c759;
  background: rgba(52, 199, 89, 0.05);
}

/* Citas Section */
.citas-section {
  margin-bottom: 24px;
}

.citas-section:last-child {
  margin-bottom: 0;
}

.section-title {
  display: flex;
  align-items: center;
  gap: 8px;
  margin-bottom: 12px;
  font-size: 13px;
  font-weight: 600;
  color: #666;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.section-title i {
  font-size: 14px;
  color: #999;
}

.citas-grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 12px;
}

.cita-setting {
  display: flex;
  align-items: center;
  gap: 12px;
  background: #f8f9fa;
  border-radius: 14px;
  padding: 12px;
  transition: all 0.2s;
}

.cita-setting:focus-within {
  background: #fff;
  box-shadow: 0 0 0 2px rgba(118, 75, 162, 0.2);
}

.setting-icon {
  width: 40px;
  height: 40px;
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 16px;
  flex-shrink: 0;
  transition: transform 0.2s;
}

.cita-setting:focus-within .setting-icon {
  transform: scale(1.05);
}

.setting-icon.min {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
}

.setting-icon.max {
  background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
  color: white;
}

.setting-icon.buffer {
  background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
  color: white;
}

.setting-icon.cancel {
  background: linear-gradient(135deg, #fa709a 0%, #fee140 100%);
  color: white;
}

.setting-content {
  flex: 1;
  min-width: 0;
}

.setting-content label {
  display: block;
  font-size: 11px;
  font-weight: 600;
  color: #999;
  margin-bottom: 4px;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.setting-input-wrapper {
  display: flex;
  align-items: center;
  gap: 6px;
}

.setting-input {
  flex: 1;
  border: none;
  background: transparent;
  font-size: 18px;
  font-weight: 700;
  color: #333;
  outline: none;
  padding: 0;
  min-width: 0;
}

.setting-input::-webkit-inner-spin-button,
.setting-input::-webkit-outer-spin-button {
  opacity: 1;
  height: auto;
}

.setting-unit {
  font-size: 12px;
  font-weight: 600;
  color: #999;
  white-space: nowrap;
}

/* Input Suffix */
.input-suffix {
  display: flex;
  align-items: center;
  border: 1px solid #e0e0e0;
  border-radius: 10px;
  overflow: hidden;
}

.input-suffix input {
  border: none;
  border-radius: 0;
  flex: 1;
  text-align: center;
}

.input-suffix span {
  padding: 12px;
  background: #f8f9fa;
  color: #666;
  font-size: 12px;
  font-weight: 500;
}

/* Horarios Container Legacy */
.horarios-container {
  display: flex;
  flex-direction: column;
  gap: 12px;
  margin-top: 8px;
  padding-top: 12px;
  border-top: 1px solid #f0f0f0;
  animation: slideDown 0.3s ease;
}

@keyframes slideDown {
  from {
    opacity: 0;
    transform: translateY(-10px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

.horario-day {
  background: #f8f9fa;
  border-radius: 12px;
  padding: 12px 16px;
}

.day-header {
  display: flex;
  align-items: center;
  width: 100%;
}

.toggle-day {
  display: flex;
  align-items: center;
  justify-content: space-between;
  cursor: pointer;
  width: 100%;
}

.toggle-day input {
  position: absolute;
  opacity: 0;
  width: 0;
  height: 0;
}

.day-name {
  font-weight: 600;
  color: #333;
  font-size: 15px;
}

.toggle-switch {
  width: 51px;
  height: 31px;
  background: #e9e9eb;
  border-radius: 31px;
  position: relative;
  transition: background-color 0.3s ease;
  flex-shrink: 0;
}

.toggle-switch::after {
  content: '';
  position: absolute;
  width: 27px;
  height: 27px;
  background: white;
  border-radius: 50%;
  top: 2px;
  left: 2px;
  transition: transform 0.3s cubic-bezier(0.4, 0.0, 0.2, 1);
  box-shadow: 0 3px 8px rgba(0, 0, 0, 0.15), 0 1px 1px rgba(0, 0, 0, 0.16), 0 3px 1px rgba(0, 0, 0, 0.1);
}

.toggle-day input:checked ~ .toggle-switch {
  background: #34c759;
}

.toggle-day input:checked ~ .toggle-switch::after {
  transform: translateX(20px);
}

/* Notificaciones - Nuevo diseño */
.notif-item {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 14px;
  background: #f8f9fa;
  border-radius: 14px;
  margin-bottom: 12px;
  transition: all 0.2s;
}

.notif-item:last-child {
  margin-bottom: 0;
}

.notif-item:hover {
  background: #f0f0f0;
}

.notif-icon {
  width: 44px;
  height: 44px;
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 18px;
  flex-shrink: 0;
}

.notif-icon.confirm {
  background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
  color: white;
}

.notif-icon.reminder {
  background: linear-gradient(135deg, #f6d365 0%, #fda085 100%);
  color: white;
}

.notif-icon.cancel {
  background: linear-gradient(135deg, #fa709a 0%, #fee140 100%);
  color: white;
}

.notif-content {
  flex: 1;
  min-width: 0;
}

.notif-title {
  font-size: 15px;
  font-weight: 600;
  color: #333;
  margin-bottom: 4px;
}

.notif-desc {
  font-size: 12px;
  color: #999;
  line-height: 1.4;
}

.notif-toggle {
  flex-shrink: 0;
  cursor: pointer;
}

.notif-switch {
  position: relative;
  display: block;
  width: 51px;
  height: 31px;
  background: #e9e9eb;
  border-radius: 31px;
  transition: background-color 0.3s ease;
}

.notif-switch::after {
  content: '';
  position: absolute;
  width: 27px;
  height: 27px;
  background: white;
  border-radius: 50%;
  top: 2px;
  left: 2px;
  transition: transform 0.3s cubic-bezier(0.4, 0.0, 0.2, 1);
  box-shadow: 0 3px 8px rgba(0, 0, 0, 0.15), 0 1px 1px rgba(0, 0, 0, 0.16), 0 3px 1px rgba(0, 0, 0, 0.1);
}

.notif-toggle input {
  position: absolute;
  opacity: 0;
  width: 0;
  height: 0;
}

.notif-toggle input:checked + .notif-switch {
  background: #34c759;
}

.notif-toggle input:checked + .notif-switch::after {
  transform: translateX(20px);
}

/* Toggle Items Legacy */
.toggle-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 12px 0;
  border-bottom: 1px solid #f0f0f0;
}

.toggle-item:last-child {
  border-bottom: none;
  padding-bottom: 0;
}

.toggle-info {
  display: flex;
  flex-direction: column;
  gap: 2px;
}

.toggle-title {
  font-size: 14px;
  font-weight: 500;
  color: #333;
}

.toggle-desc {
  font-size: 12px;
  color: #999;
}

.toggle-switch {
  position: relative;
  width: 50px;
  height: 28px;
  cursor: pointer;
}

.toggle-switch input {
  display: none;
}

.toggle-slider {
  position: absolute;
  inset: 0;
  background: #e0e0e0;
  border-radius: 14px;
  transition: background 0.2s;
}

.toggle-slider::after {
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

.toggle-switch input:checked + .toggle-slider {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
}

.toggle-switch input:checked + .toggle-slider::after {
  transform: translateX(22px);
}

/* Mobile Save Button */
.btn-save-mobile {
  width: 100%;
  padding: 16px;
  background: linear-gradient(135deg, #536976 0%, #292e49 100%);
  color: white;
  border: none;
  border-radius: 14px;
  font-size: 16px;
  font-weight: 600;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 10px;
  margin-top: 8px;
}

.btn-save-mobile:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.btn-save-mobile i {
  font-size: 18px;
}
</style>
