<template>
  <div class="configuracion-view">
    <!-- Header -->
    <div class="configuracion-header">
      <div class="header-left">
        <div class="header-icon">
          <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <circle cx="12" cy="12" r="3"></circle>
            <path d="M12 1v6m0 6v6M5.64 5.64l4.24 4.24m4.24 4.24l4.24 4.24M1 12h6m6 0h6M5.64 18.36l4.24-4.24m4.24-4.24l4.24-4.24"></path>
          </svg>
        </div>
        <div class="header-text">
          <h1>Configuración</h1>
          <p class="header-subtitle">Ajustes del sistema</p>
        </div>
      </div>
      <button 
        class="btn-save-header" 
        @click="guardarConfiguracion" 
        :disabled="guardando"
        title="Guardar cambios"
      >
        <svg v-if="guardando" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="spinning">
          <line x1="12" y1="2" x2="12" y2="6"></line>
          <line x1="12" y1="18" x2="12" y2="22"></line>
          <line x1="4.93" y1="4.93" x2="7.76" y2="7.76"></line>
          <line x1="16.24" y1="16.24" x2="19.07" y2="19.07"></line>
          <line x1="2" y1="12" x2="6" y2="12"></line>
          <line x1="18" y1="12" x2="22" y2="12"></line>
          <line x1="4.93" y1="19.07" x2="7.76" y2="16.24"></line>
          <line x1="16.24" y1="7.76" x2="19.07" y2="4.93"></line>
        </svg>
        <svg v-else xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path>
          <polyline points="17 21 17 13 7 13 7 21"></polyline>
          <polyline points="7 3 7 8 15 8"></polyline>
        </svg>
      </button>
    </div>

    <!-- Loading -->
    <div v-if="loading" class="loading-container">
      <div class="loader"></div>
      <p>Cargando configuración...</p>
    </div>

    <!-- Config Sections -->
    <div v-else class="config-container">
      <!-- Negocio -->
      <div class="config-card">
        <div class="card-header">
          <div class="card-icon blue">
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path>
              <polyline points="9 22 9 12 15 12 15 22"></polyline>
            </svg>
          </div>
          <h3>Información del Negocio</h3>
        </div>
        <div class="card-body">
          <!-- Nombre del negocio -->
          <div class="info-field">
            <div class="field-icon name">
              <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path>
                <polyline points="9 22 9 12 15 12 15 22"></polyline>
              </svg>
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
              <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"></path>
              </svg>
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
              <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path>
                <polyline points="22,6 12,13 2,6"></polyline>
              </svg>
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
              <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path>
                <circle cx="12" cy="10" r="3"></circle>
              </svg>
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
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <circle cx="12" cy="12" r="10"></circle>
              <polyline points="12 6 12 12 16 14"></polyline>
            </svg>
          </div>
          <h3>Horario de Atención</h3>
        </div>
        <div class="card-body">
          <!-- Horario de apertura y cierre -->
          <div class="horario-section">
            <div class="horario-header">
              <span class="horario-label">Horario de atención</span>
            </div>
            <div class="horario-times">
              <div class="time-block">
              <div class="time-icon-wrapper apertura">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <circle cx="12" cy="12" r="5"></circle>
                  <line x1="12" y1="1" x2="12" y2="3"></line>
                  <line x1="12" y1="21" x2="12" y2="23"></line>
                  <line x1="4.22" y1="4.22" x2="5.64" y2="5.64"></line>
                  <line x1="18.36" y1="18.36" x2="19.78" y2="19.78"></line>
                  <line x1="1" y1="12" x2="3" y2="12"></line>
                  <line x1="21" y1="12" x2="23" y2="12"></line>
                  <line x1="4.22" y1="19.78" x2="5.64" y2="18.36"></line>
                  <line x1="18.36" y1="5.64" x2="19.78" y2="4.22"></line>
                </svg>
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
              <div class="time-connector">
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <polyline points="9 18 15 12 9 6"></polyline>
                </svg>
              </div>
              <div class="time-block">
              <div class="time-icon-wrapper cierre">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"></path>
                </svg>
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
          </div>

          <!-- Días laborales -->
          <div class="dias-section">
            <div class="dias-header">
              <span class="dias-label">Días de atención</span>
              <span class="dias-badge">{{ config.dias_laborales.length }}/7</span>
            </div>
            <div class="dias-grid">
              <button
                v-for="dia in diasSemana"
                :key="dia.value"
                type="button"
                class="dia-btn"
                :class="{ active: config.dias_laborales.includes(dia.value) }"
                @click="toggleDiaDirecto(dia.value)"
              >
                <span class="dia-abbr">{{ dia.abbr }}</span>
                <svg v-if="config.dias_laborales.includes(dia.value)" xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" class="dia-check">
                  <polyline points="20 6 9 17 4 12"></polyline>
                </svg>
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- Citas -->
      <div class="config-card">
        <div class="card-header">
          <div class="card-icon purple">
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
              <line x1="16" y1="2" x2="16" y2="6"></line>
              <line x1="8" y1="2" x2="8" y2="6"></line>
              <line x1="3" y1="10" x2="21" y2="10"></line>
            </svg>
          </div>
          <h3>Configuración de Citas</h3>
        </div>
        <div class="card-body">
          <!-- Anticipación -->
          <div class="citas-section">
            <div class="section-title">
              <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="12" cy="12" r="10"></circle>
                <polyline points="12 6 12 12 16 14"></polyline>
              </svg>
              <span>Anticipación</span>
            </div>
            <div class="citas-grid">
              <div class="cita-setting">
                <div class="setting-icon min">
                  <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="22 12 18 12 15 21 9 3 6 12 2 12"></polyline>
                  </svg>
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
                  <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="2 12 6 12 9 3 15 21 18 12 22 12"></polyline>
                  </svg>
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
              <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="12" cy="12" r="10"></circle>
                <polyline points="12 6 12 12 16 14"></polyline>
              </svg>
              <span>Tiempos</span>
            </div>
            <div class="citas-grid">
              <div class="cita-setting">
                <div class="setting-icon buffer">
                  <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="12" r="10"></circle>
                    <line x1="10" y1="12" x2="14" y2="12"></line>
                  </svg>
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
                  <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="12" r="10"></circle>
                    <line x1="15" y1="9" x2="9" y2="15"></line>
                    <line x1="9" y1="9" x2="15" y2="15"></line>
                  </svg>
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
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path>
              <path d="M13.73 21a2 2 0 0 1-3.46 0"></path>
            </svg>
          </div>
          <h3>Notificaciones</h3>
        </div>
        <div class="card-body">
          <!-- Confirmación -->
          <div class="notif-item">
            <div class="notif-icon confirm">
              <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                <polyline points="22 4 12 14.01 9 11.01"></polyline>
              </svg>
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
              <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="12" cy="12" r="10"></circle>
                <polyline points="12 6 12 12 16 14"></polyline>
              </svg>
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
              <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="12" cy="12" r="10"></circle>
                <line x1="15" y1="9" x2="9" y2="15"></line>
                <line x1="9" y1="9" x2="15" y2="15"></line>
              </svg>
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
        <svg v-if="guardando" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="spinning">
          <line x1="12" y1="2" x2="12" y2="6"></line>
          <line x1="12" y1="18" x2="12" y2="22"></line>
          <line x1="4.93" y1="4.93" x2="7.76" y2="7.76"></line>
          <line x1="16.24" y1="16.24" x2="19.07" y2="19.07"></line>
          <line x1="2" y1="12" x2="6" y2="12"></line>
          <line x1="18" y1="12" x2="22" y2="12"></line>
          <line x1="4.93" y1="19.07" x2="7.76" y2="16.24"></line>
          <line x1="16.24" y1="7.76" x2="19.07" y2="4.93"></line>
        </svg>
        <svg v-else xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path>
          <polyline points="17 21 17 13 7 13 7 21"></polyline>
          <polyline points="7 3 7 8 15 8"></polyline>
        </svg>
        {{ guardando ? 'Guardando...' : 'Guardar Cambios' }}
      </button>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, onMounted } from 'vue';
import { getConfiguracion, updateConfiguracion } from '@/services/adminService';
import Swal from 'sweetalert2';

const loading = ref(true);
const guardando = ref(false);

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
          // Convertir valores según el tipo esperado
          if (c.clave === 'dias_laborales') {
            (config as any)[c.clave] = Array.isArray(c.valor) ? c.valor : JSON.parse(c.valor || '[]');
          } else if (c.clave === 'notif_confirmacion' || c.clave === 'notif_recordatorio' || c.clave === 'notif_cancelacion') {
            (config as any)[c.clave] = c.valor === true || c.valor === '1' || c.valor === 1 || c.valor === 'true';
          } else if (c.clave === 'anticipacion_minima' || c.clave === 'anticipacion_maxima' || c.clave === 'buffer_citas' || c.clave === 'tiempo_cancelacion') {
            (config as any)[c.clave] = Number(c.valor) || 0;
          } else {
            (config as any)[c.clave] = c.valor;
          }
        }
      });
    }
  } catch (error) {
    console.error('Error cargando configuración:', error);
  } finally {
    loading.value = false;
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

async function guardarConfiguracion() {
  guardando.value = true;
  try {
    const configuraciones = Object.entries(config).map(([clave, valor]) => ({
      clave,
      valor,
    }));
    
    await updateConfiguracion(configuraciones);
    Swal.fire({
      icon: 'success',
      title: '¡Éxito!',
      text: 'Configuración guardada correctamente',
      confirmButtonColor: '#34c759',
      timer: 1500,
      showConfirmButton: false
    });
  } catch (error: any) {
    console.error('Error guardando configuración:', error);
    Swal.fire({
      icon: 'error',
      title: 'Error',
      text: error.response?.data?.message || 'Error al guardar configuración',
      confirmButtonColor: '#ff3b30'
    });
  } finally {
    guardando.value = false;
  }
}

onMounted(() => {
  cargarConfiguracion();
});
</script>

<style scoped>
/* ===== Apple-inspired Configuración View Design ===== */

.configuracion-view {
  min-height: 100vh;
  background: #f5f5f7;
  padding: 24px;
  padding-bottom: 120px;
  font-family: -apple-system, BlinkMacSystemFont, 'SF Pro Display', 'SF Pro Text', 'Helvetica Neue', sans-serif;
  -webkit-font-smoothing: antialiased;
  -moz-osx-font-smoothing: grayscale;
}

/* Header */
.configuracion-header {
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
  flex: 1;
  min-width: 0;
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
  flex-shrink: 0;
}

.header-text {
  flex: 1;
  min-width: 0;
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

.btn-save-header {
  width: 44px;
  height: 44px;
  background: rgba(255, 255, 255, 0.15);
  border: 1px solid rgba(255, 255, 255, 0.2);
  border-radius: 12px;
  color: white;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all 0.2s;
  backdrop-filter: blur(10px);
  flex-shrink: 0;
}

.btn-save-header:active:not(:disabled) {
  background: rgba(255, 255, 255, 0.25);
  transform: scale(0.98);
}

.btn-save-header:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.spinning {
  animation: spin 0.8s linear infinite;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}

/* Loading */
.loading-container {
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

.loading-container p {
  color: #86868b;
  font-size: 15px;
  margin: 0;
}

/* Config Container */
.config-container {
  display: flex;
  flex-direction: column;
  gap: 16px;
}

/* Config Card */
.config-card {
  background: #ffffff;
  border-radius: 16px;
  overflow: hidden;
  box-shadow: 0 2px 12px rgba(0, 0, 0, 0.08);
  border: 1px solid #e5e5ea;
}

.card-header {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 18px 20px;
  border-bottom: 1px solid #e5e5ea;
  background: #fafafa;
}

.card-icon {
  width: 40px;
  height: 40px;
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

.card-icon.blue {
  background: #e8f4fd;
  color: #007aff;
}

.card-icon.green {
  background: #e8f5e9;
  color: #34c759;
}

.card-icon.purple {
  background: #f3e8ff;
  color: #5856d6;
}

.card-icon.orange {
  background: #fff3e0;
  color: #ff9500;
}

.card-header h3 {
  margin: 0;
  font-size: 17px;
  font-weight: 600;
  color: #1d1d1f;
  letter-spacing: -0.2px;
}

.card-body {
  padding: 20px;
}

/* Info Fields */
.info-field {
  display: flex;
  align-items: flex-start;
  gap: 12px;
  margin-bottom: 12px;
  padding: 12px;
  background: #f5f5f7;
  border-radius: 12px;
  transition: all 0.2s;
  border: 1px solid transparent;
}

.info-field:last-child {
  margin-bottom: 0;
}

.info-field.full-width {
  width: 100%;
}

.info-field:focus-within {
  background: #ffffff;
  border-color: #007aff;
  box-shadow: 0 0 0 3px rgba(0, 122, 255, 0.1);
}

.field-icon {
  width: 36px;
  height: 36px;
  border-radius: 10px;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
  transition: transform 0.2s;
  box-shadow: 0 2px 6px rgba(0, 0, 0, 0.1);
}

.info-field:focus-within .field-icon {
  transform: scale(1.05);
}

.field-icon.name {
  background: linear-gradient(135deg, #007aff 0%, #5856d6 100%);
  color: white;
}

.field-icon.phone {
  background: linear-gradient(135deg, #34c759 0%, #30d158 100%);
  color: white;
}

.field-icon.email {
  background: linear-gradient(135deg, #ff9500 0%, #ffad33 100%);
  color: white;
}

.field-icon.address {
  background: linear-gradient(135deg, #5856d6 0%, #af52de 100%);
  color: white;
}

.field-content {
  flex: 1;
  min-width: 0;
}

.field-content label {
  display: block;
  font-size: 10px;
  font-weight: 600;
  color: #86868b;
  margin-bottom: 4px;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  line-height: 1.2;
}

.field-input,
.field-textarea {
  width: 100%;
  padding: 0;
  border: none;
  background: transparent;
  font-size: 15px;
  font-weight: 600;
  color: #1d1d1f;
  outline: none;
  box-sizing: border-box;
  font-family: inherit;
  line-height: 1.4;
}

.field-input::placeholder,
.field-textarea::placeholder {
  color: #86868b;
  font-weight: 400;
}

.field-textarea {
  resize: none;
  min-height: 44px;
  line-height: 1.5;
}

/* Horario Section */
.horario-section {
  margin-bottom: 20px;
}

.horario-header {
  margin-bottom: 12px;
}

.horario-label {
  font-size: 12px;
  font-weight: 600;
  color: #86868b;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.horario-times {
  display: flex;
  align-items: stretch;
  gap: 8px;
  width: 100%;
  box-sizing: border-box;
}

.time-block {
  flex: 1;
  min-width: 0;
  display: flex;
  align-items: center;
  gap: 10px;
  background: #f5f5f7;
  border-radius: 12px;
  padding: 12px;
  transition: all 0.2s;
  box-sizing: border-box;
  border: 1px solid transparent;
}

.time-block:focus-within {
  background: #ffffff;
  border-color: #007aff;
  box-shadow: 0 0 0 3px rgba(0, 122, 255, 0.1);
}

.time-icon-wrapper {
  width: 36px;
  height: 36px;
  border-radius: 10px;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
  box-shadow: 0 2px 6px rgba(0, 0, 0, 0.1);
}

.time-icon-wrapper.apertura {
  background: linear-gradient(135deg, #ff9500 0%, #ffad33 100%);
  color: white;
}

.time-icon-wrapper.cierre {
  background: linear-gradient(135deg, #5856d6 0%, #af52de 100%);
  color: white;
}

.time-connector {
  display: flex;
  align-items: center;
  justify-content: center;
  color: #86868b;
  flex-shrink: 0;
  padding: 0 2px;
}

.time-content {
  display: flex;
  flex-direction: column;
  flex: 1;
  min-width: 0;
  justify-content: center;
}

.time-label {
  font-size: 10px;
  color: #86868b;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  margin-bottom: 4px;
  line-height: 1.2;
}

.time-input {
  border: none;
  background: transparent;
  font-size: 16px;
  font-weight: 700;
  color: #1d1d1f;
  padding: 0;
  width: 100%;
  outline: none;
  min-width: 0;
  font-family: inherit;
  letter-spacing: -0.2px;
  line-height: 1.2;
}

/* Días Section */
.dias-section {
  background: #f5f5f7;
  border-radius: 14px;
  padding: 14px;
  border: 1px solid #e5e5ea;
}

.dias-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 12px;
}

.dias-label {
  font-size: 12px;
  font-weight: 600;
  color: #86868b;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.dias-badge {
  background: linear-gradient(135deg, #007aff 0%, #5856d6 100%);
  color: white;
  font-size: 11px;
  font-weight: 700;
  padding: 3px 10px;
  border-radius: 10px;
  box-shadow: 0 2px 6px rgba(0, 122, 255, 0.3);
}

.dias-grid {
  display: grid;
  grid-template-columns: repeat(7, 1fr);
  gap: 6px;
  width: 100%;
  box-sizing: border-box;
}

.dia-btn {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 12px 4px;
  background: #ffffff;
  border: 2px solid #e5e5ea;
  border-radius: 12px;
  cursor: pointer;
  transition: all 0.2s ease;
  position: relative;
  min-height: 50px;
  box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
  box-sizing: border-box;
  width: 100%;
}

.dia-btn:active {
  transform: scale(0.95);
}

.dia-btn .dia-abbr {
  font-size: 11px;
  font-weight: 700;
  color: #86868b;
  text-transform: uppercase;
  letter-spacing: 0.2px;
  line-height: 1.2;
  text-align: center;
}

.dia-btn .dia-check {
  position: absolute;
  top: 6px;
  right: 6px;
  color: white;
  display: none;
  filter: drop-shadow(0 1px 2px rgba(0, 0, 0, 0.2));
  width: 14px;
  height: 14px;
  z-index: 1;
}

.dia-btn.active {
  background: linear-gradient(135deg, #007aff 0%, #5856d6 100%);
  border-color: transparent;
  box-shadow: 0 2px 8px rgba(0, 122, 255, 0.4);
}

.dia-btn.active .dia-abbr {
  color: white;
  font-weight: 800;
}

.dia-btn.active .dia-check {
  display: block;
}

/* Citas Section */
.citas-section {
  margin-bottom: 20px;
}

.citas-section:last-child {
  margin-bottom: 0;
}

.section-title {
  display: flex;
  align-items: center;
  gap: 8px;
  margin-bottom: 12px;
  font-size: 12px;
  font-weight: 600;
  color: #86868b;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.section-title svg {
  color: #007aff;
  flex-shrink: 0;
  width: 14px;
  height: 14px;
}

.citas-grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 10px;
  width: 100%;
  box-sizing: border-box;
}

.cita-setting {
  display: flex;
  align-items: center;
  gap: 10px;
  background: #f5f5f7;
  border-radius: 12px;
  padding: 12px;
  transition: all 0.2s;
  min-width: 0;
  box-sizing: border-box;
  border: 1px solid transparent;
}

.cita-setting:focus-within {
  background: #ffffff;
  border-color: #007aff;
  box-shadow: 0 0 0 3px rgba(0, 122, 255, 0.1);
}

.setting-icon {
  width: 36px;
  height: 36px;
  border-radius: 10px;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
  transition: transform 0.2s;
  box-shadow: 0 2px 6px rgba(0, 0, 0, 0.1);
}

.cita-setting:focus-within .setting-icon {
  transform: scale(1.05);
}

.setting-icon.min {
  background: linear-gradient(135deg, #007aff 0%, #5856d6 100%);
  color: white;
}

.setting-icon.max {
  background: linear-gradient(135deg, #ff9500 0%, #ffad33 100%);
  color: white;
}

.setting-icon.buffer {
  background: linear-gradient(135deg, #34c759 0%, #30d158 100%);
  color: white;
}

.setting-icon.cancel {
  background: linear-gradient(135deg, #ff3b30 0%, #ff6b6b 100%);
  color: white;
}

.setting-content {
  flex: 1;
  min-width: 0;
  overflow: hidden;
  box-sizing: border-box;
}

.setting-content label {
  display: block;
  font-size: 10px;
  font-weight: 600;
  color: #86868b;
  margin-bottom: 4px;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  line-height: 1.2;
}

.setting-input-wrapper {
  display: flex;
  align-items: center;
  gap: 6px;
  min-width: 0;
  flex: 1;
  box-sizing: border-box;
}

.setting-input {
  flex: 1;
  border: none;
  background: transparent;
  font-size: 16px;
  font-weight: 700;
  color: #1d1d1f;
  outline: none;
  padding: 0;
  min-width: 0;
  width: 100%;
  max-width: 100%;
  box-sizing: border-box;
  font-family: inherit;
  letter-spacing: -0.2px;
}

.setting-unit {
  font-size: 11px;
  font-weight: 600;
  color: #86868b;
  white-space: nowrap;
  flex-shrink: 0;
}

.setting-input::-webkit-inner-spin-button,
.setting-input::-webkit-outer-spin-button {
  opacity: 1;
  height: auto;
}

/* Notificaciones */
.notif-item {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 14px;
  background: #f5f5f7;
  border-radius: 12px;
  margin-bottom: 10px;
  transition: all 0.2s;
  border: 1px solid transparent;
}

.notif-item:last-child {
  margin-bottom: 0;
}

.notif-item:active {
  transform: scale(0.98);
}

.notif-icon {
  width: 40px;
  height: 40px;
  border-radius: 10px;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
  box-shadow: 0 2px 6px rgba(0, 0, 0, 0.1);
}

.notif-icon.confirm {
  background: linear-gradient(135deg, #34c759 0%, #30d158 100%);
  color: white;
}

.notif-icon.reminder {
  background: linear-gradient(135deg, #ff9500 0%, #ffad33 100%);
  color: white;
}

.notif-icon.cancel {
  background: linear-gradient(135deg, #ff3b30 0%, #ff6b6b 100%);
  color: white;
}

.notif-content {
  flex: 1;
  min-width: 0;
}

.notif-title {
  font-size: 14px;
  font-weight: 600;
  color: #1d1d1f;
  margin-bottom: 3px;
  letter-spacing: -0.1px;
  line-height: 1.2;
}

.notif-desc {
  font-size: 12px;
  color: #86868b;
  line-height: 1.3;
}

.notif-toggle {
  flex-shrink: 0;
  cursor: pointer;
}

.notif-switch {
  position: relative;
  display: block;
  width: 48px;
  height: 28px;
  background: #e5e5ea;
  border-radius: 14px;
  transition: background-color 0.3s ease;
}

.notif-switch::after {
  content: '';
  position: absolute;
  width: 24px;
  height: 24px;
  background: white;
  border-radius: 50%;
  top: 2px;
  left: 2px;
  transition: transform 0.3s cubic-bezier(0.4, 0.0, 0.2, 1);
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
}

.notif-toggle input {
  position: absolute;
  opacity: 0;
  width: 0;
  height: 0;
}

.notif-toggle input:checked + .notif-switch {
  background: linear-gradient(135deg, #007aff 0%, #5856d6 100%);
}

.notif-toggle input:checked + .notif-switch::after {
  transform: translateX(20px);
}

/* Mobile Save Button */
.btn-save-mobile {
  width: 100%;
  padding: 16px;
  background: linear-gradient(135deg, #007aff 0%, #5856d6 100%);
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
  transition: all 0.2s;
  box-shadow: 0 4px 12px rgba(0, 122, 255, 0.3);
}

.btn-save-mobile:active:not(:disabled) {
  transform: scale(0.98);
  box-shadow: 0 2px 8px rgba(0, 122, 255, 0.4);
}

.btn-save-mobile:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.btn-save-mobile svg {
  flex-shrink: 0;
}

@media (min-width: 768px) {
  .btn-save-mobile {
    display: none;
  }
}

@media (max-width: 767px) {
  .btn-save-header {
    display: none;
  }
}
</style>
