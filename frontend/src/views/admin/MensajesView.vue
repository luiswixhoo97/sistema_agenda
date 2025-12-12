<template>
  <div class="mensajes-view">
    <!-- Header -->
    <div class="mensajes-header">
      <div class="header-left">
        <div class="header-icon">
          <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path>
            <line x1="8" y1="10" x2="16" y2="10"></line>
            <line x1="8" y1="14" x2="14" y2="14"></line>
          </svg>
        </div>
        <div class="header-text">
          <h1>Mensajes y Comunicaciones</h1>
          <p class="header-subtitle">Personaliza plantillas y envía comunicados</p>
        </div>
      </div>
    </div>

    <!-- Tabs -->
    <div class="tabs-container">
      <button 
        :class="['tab-btn', { active: activeTab === 'plantillas' }]"
        @click="activeTab = 'plantillas'"
      >
        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
          <polyline points="14 2 14 8 20 8"></polyline>
          <line x1="16" y1="13" x2="8" y2="13"></line>
          <line x1="16" y1="17" x2="8" y2="17"></line>
          <polyline points="10 9 9 9 8 9"></polyline>
        </svg>
        <span class="tab-text">Plantillas</span>
      </button>
      <button 
        :class="['tab-btn', { active: activeTab === 'comunicacion' }]"
        @click="activeTab = 'comunicacion'"
      >
        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <path d="M22 2L11 13"></path>
          <polygon points="22 2 15 22 11 13 2 9 22 2"></polygon>
        </svg>
        <span class="tab-text">Enviar</span>
      </button>
      <button 
        :class="['tab-btn', { active: activeTab === 'estadisticas' }]"
        @click="activeTab = 'estadisticas'; cargarEstadisticas()"
      >
        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <line x1="18" y1="20" x2="18" y2="10"></line>
          <line x1="12" y1="20" x2="12" y2="4"></line>
          <line x1="6" y1="20" x2="6" y2="14"></line>
        </svg>
        <span class="tab-text">Estadísticas</span>
      </button>
    </div>

    <!-- Loading -->
    <div v-if="loading" class="loading-container">
      <div class="loader"></div>
      <p>Cargando...</p>
    </div>

    <!-- Tab: Plantillas -->
    <div v-else-if="activeTab === 'plantillas'" class="tab-content">
      <div class="plantillas-grid">
        <div 
          v-for="grupo in plantillas" 
          :key="grupo.tipo"
          class="plantilla-card"
          @click="editarPlantilla(grupo)"
        >
          <div class="plantilla-icon" :class="getIconClass(grupo.tipo)">
            <component :is="getIconSvg(grupo.tipo)" />
          </div>
          <div class="plantilla-info">
            <h3>{{ grupo.nombre }}</h3>
            <p>{{ grupo.descripcion }}</p>
          </div>
          <div class="plantilla-action">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <polyline points="9 18 15 12 9 6"></polyline>
            </svg>
          </div>
        </div>
      </div>
    </div>

    <!-- Tab: Comunicación -->
    <div v-else-if="activeTab === 'comunicacion'" class="tab-content">
      <div class="comunicacion-container">
        <!-- Tipo de destinatarios -->
        <div class="form-section">
          <label class="section-label">
            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
              <circle cx="9" cy="7" r="4"></circle>
              <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
              <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
            </svg>
            Destinatarios
          </label>
          <div class="tipo-selector">
            <label 
              v-for="tipo in tiposDestinatarios" 
              :key="tipo.value"
              :class="['tipo-option', { selected: comunicacion.tipo === tipo.value }]"
            >
              <input 
                type="radio" 
                v-model="comunicacion.tipo" 
                :value="tipo.value"
              />
              <component :is="tipo.iconSvg" />
              <span>{{ tipo.label }}</span>
            </label>
          </div>
        </div>

        <!-- Selección de clientes -->
        <div v-if="comunicacion.tipo === 'seleccion'" class="form-section">
          <label class="section-label">
            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <circle cx="11" cy="11" r="8"></circle>
              <path d="M21 21l-4.35-4.35"></path>
            </svg>
            Seleccionar Clientes
          </label>
          <div class="buscar-cliente">
            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <circle cx="11" cy="11" r="8"></circle>
              <path d="M21 21l-4.35-4.35"></path>
            </svg>
            <input 
              v-model="busquedaCliente"
              type="text"
              placeholder="Buscar por nombre o teléfono..."
              @input="buscarClientes"
            />
          </div>
          <div class="clientes-lista" v-if="clientesEncontrados.length">
            <div 
              v-for="cliente in clientesEncontrados"
              :key="cliente.id"
              :class="['cliente-item', { selected: clientesSeleccionados.includes(cliente.telefono) }]"
              @click="toggleCliente(cliente)"
            >
              <div class="cliente-avatar">
                {{ cliente.nombre.charAt(0).toUpperCase() }}
              </div>
              <div class="cliente-info">
                <span class="nombre">{{ cliente.nombre }}</span>
                <span class="telefono">{{ cliente.telefono }}</span>
              </div>
              <div class="cliente-check">
                <svg v-if="clientesSeleccionados.includes(cliente.telefono)" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                  <polyline points="22 4 12 14.01 9 11.01"></polyline>
                </svg>
                <svg v-else xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <circle cx="12" cy="12" r="10"></circle>
                </svg>
              </div>
            </div>
          </div>
          <div v-if="clientesSeleccionados.length" class="seleccionados-count">
            <span>{{ clientesSeleccionados.length }} cliente(s) seleccionado(s)</span>
            <button class="btn-limpiar" @click="clientesSeleccionados = []">
              Limpiar
            </button>
          </div>
        </div>

        <!-- Teléfono individual -->
        <div v-if="comunicacion.tipo === 'individual'" class="form-section">
          <label class="section-label">
            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"></path>
            </svg>
            Número de teléfono
          </label>
          <input 
            v-model="telefonoIndividual"
            type="tel"
            placeholder="Ej: 3312345678"
            maxlength="10"
            class="form-input"
          />
        </div>

        <!-- Mensaje -->
        <div class="form-section">
          <label class="section-label">
            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path>
            </svg>
            Mensaje
          </label>
          <input 
            v-model="comunicacion.titulo"
            type="text"
            placeholder="Título del comunicado (opcional)"
            class="form-input"
          />
          <textarea
            v-model="comunicacion.mensaje"
            rows="6"
            placeholder="Escribe tu mensaje aquí...&#10;&#10;Puedes usar emojis y formato:&#10;*texto en negritas*&#10;_texto en cursiva_"
            class="form-textarea"
          ></textarea>
          <div class="char-count">
            {{ comunicacion.mensaje.length }} / 2000 caracteres
          </div>
        </div>

        <!-- Vista previa -->
        <div class="form-section preview-section">
          <label class="section-label">
            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
              <circle cx="12" cy="12" r="3"></circle>
            </svg>
            Vista previa
          </label>
          <div class="whatsapp-preview">
            <div class="preview-header">
              <div class="preview-avatar">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path>
                  <polyline points="9 22 9 12 15 12 15 22"></polyline>
                </svg>
              </div>
              <span>Tu Negocio</span>
            </div>
            <div class="preview-bubble">
              <p v-if="comunicacion.titulo" class="preview-titulo">📢 <strong>{{ comunicacion.titulo }}</strong></p>
              <p class="preview-mensaje" v-html="formatearMensaje(comunicacion.mensaje)"></p>
              <span class="preview-time">{{ horaActual }}</span>
            </div>
          </div>
        </div>

        <!-- Botón enviar -->
        <div class="form-actions">
          <button 
            class="btn-enviar"
            @click="enviarComunicacion"
            :disabled="enviando || !puedeEnviar"
          >
            <svg v-if="!enviando" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <line x1="22" y1="2" x2="11" y2="13"></line>
              <polygon points="22 2 15 22 11 13 2 9 22 2"></polygon>
            </svg>
            <div v-else class="spinner-small"></div>
            {{ enviando ? 'Enviando...' : 'Enviar Comunicado' }}
          </button>
        </div>
      </div>
    </div>

    <!-- Tab: Estadísticas -->
    <div v-else-if="activeTab === 'estadisticas'" class="tab-content">
      <div class="stats-grid">
        <div class="stat-card">
          <div class="stat-icon blue">
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <line x1="22" y1="2" x2="11" y2="13"></line>
              <polygon points="22 2 15 22 11 13 2 9 22 2"></polygon>
            </svg>
          </div>
          <div class="stat-info">
            <span class="stat-value">{{ estadisticas.hoy }}</span>
            <span class="stat-label">Mensajes hoy</span>
          </div>
        </div>
        <div class="stat-card">
          <div class="stat-icon green">
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
              <line x1="16" y1="2" x2="16" y2="6"></line>
              <line x1="8" y1="2" x2="8" y2="6"></line>
              <line x1="3" y1="10" x2="21" y2="10"></line>
            </svg>
          </div>
          <div class="stat-info">
            <span class="stat-value">{{ estadisticas.semana }}</span>
            <span class="stat-label">Esta semana</span>
          </div>
        </div>
        <div class="stat-card">
          <div class="stat-icon purple">
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
              <line x1="16" y1="2" x2="16" y2="6"></line>
              <line x1="8" y1="2" x2="8" y2="6"></line>
              <line x1="3" y1="10" x2="21" y2="10"></line>
            </svg>
          </div>
          <div class="stat-info">
            <span class="stat-value">{{ estadisticas.mes }}</span>
            <span class="stat-label">Este mes</span>
          </div>
        </div>
        <div class="stat-card">
          <div class="stat-icon orange">
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
              <circle cx="9" cy="7" r="4"></circle>
              <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
              <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
            </svg>
          </div>
          <div class="stat-info">
            <span class="stat-value">{{ estadisticas.clientes_whatsapp }} / {{ estadisticas.clientes_total }}</span>
            <span class="stat-label">Clientes con WhatsApp</span>
          </div>
        </div>
      </div>
    </div>

    <!-- Modal Editar Plantilla -->
    <Teleport to="body">
      <Transition name="bottom-sheet">
        <div v-if="modalPlantilla" class="bottom-sheet-overlay" @click.self="cerrarModal">
          <div class="bottom-sheet-content">
            <div class="bottom-sheet-handle"></div>
            <div class="bottom-sheet-header">
              <h2>
                <component :is="getIconSvg(plantillaEditando?.tipo || '')" />
                {{ plantillaEditando?.nombre }}
              </h2>
              <button class="btn-close" @click="cerrarModal">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <line x1="18" y1="6" x2="6" y2="18"></line>
                  <line x1="6" y1="6" x2="18" y2="18"></line>
                </svg>
              </button>
            </div>
            <div class="bottom-sheet-body">
              <p class="modal-descripcion">{{ plantillaEditando?.descripcion }}</p>
              
              <!-- Variables disponibles -->
              <div class="variables-section">
                <label class="section-label">
                  <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="16 18 22 12 16 6"></polyline>
                    <polyline points="8 6 2 12 8 18"></polyline>
                  </svg>
                  Variables disponibles
                </label>
                <div class="variables-list">
                  <div 
                    v-for="(desc, variable) in plantillaEditando?.variables" 
                    :key="variable"
                    class="variable-chip"
                    @click="insertarVariable(String(variable))"
                  >
                    <code v-text="formatVariable(String(variable))"></code>
                    <span>{{ desc }}</span>
                  </div>
                </div>
              </div>

              <!-- Editor de contenido -->
              <div class="editor-section">
                <label class="section-label">Contenido del mensaje</label>
                <textarea
                  v-model="contenidoEditando"
                  ref="textareaRef"
                  rows="8"
                  class="editor-textarea"
                ></textarea>
              </div>

              <!-- Vista previa -->
              <div class="preview-section-modal">
                <label class="section-label">
                  <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                    <circle cx="12" cy="12" r="3"></circle>
                  </svg>
                  Vista previa
                </label>
                <div class="whatsapp-preview">
                  <div class="preview-header">
                    <div class="preview-avatar">
                      <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path>
                        <polyline points="9 22 9 12 15 12 15 22"></polyline>
                      </svg>
                    </div>
                    <span>Tu Negocio</span>
                  </div>
                  <div class="preview-bubble">
                    <p class="preview-mensaje" v-html="formatearMensaje(previewContenido)"></p>
                    <span class="preview-time">{{ horaActual }}</span>
                  </div>
                </div>
              </div>
            </div>
            <div class="bottom-sheet-footer">
              <button class="btn-secondary" @click="restablecerPlantilla">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <polyline points="1 4 1 10 7 10"></polyline>
                  <path d="M3.51 15a9 9 0 1 0 2.13-9.36L1 10"></path>
                </svg>
                Restablecer
              </button>
              <button class="btn-primary" @click="guardarPlantilla" :disabled="guardando">
                <div v-if="guardando" class="spinner-small"></div>
                <svg v-else xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path>
                  <polyline points="17 21 17 13 7 13 7 21"></polyline>
                  <polyline points="7 3 7 8 15 8"></polyline>
                </svg>
                {{ guardando ? 'Guardando...' : 'Guardar cambios' }}
              </button>
            </div>
          </div>
        </div>
      </Transition>
    </Teleport>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, h } from 'vue'
import { useAuthStore } from '@/stores/auth'
import Swal from 'sweetalert2'

const authStore = useAuthStore()
const API_URL = import.meta.env.VITE_API_URL || 'https://salmon-eland-125157.hostingersite.com/backend/public/api'

// State
const loading = ref(true)
const guardando = ref(false)
const enviando = ref(false)
const activeTab = ref('plantillas')

// Plantillas
const plantillas = ref<any[]>([])
const modalPlantilla = ref(false)
const plantillaEditando = ref<any>(null)
const contenidoEditando = ref('')
const textareaRef = ref<HTMLTextAreaElement | null>(null)

// Comunicación
const comunicacion = ref({
  tipo: 'seleccion',
  titulo: '',
  mensaje: ''
})
const telefonoIndividual = ref('')
const busquedaCliente = ref('')
const clientesEncontrados = ref<any[]>([])
const clientesSeleccionados = ref<string[]>([])

// Estadísticas
const estadisticas = ref({
  hoy: 0,
  semana: 0,
  mes: 0,
  clientes_whatsapp: 0,
  clientes_total: 0
})

// SVG Components
const UserIcon = () => h('svg', { xmlns: 'http://www.w3.org/2000/svg', width: '16', height: '16', viewBox: '0 0 24 24', fill: 'none', stroke: 'currentColor', 'stroke-width': '2', 'stroke-linecap': 'round', 'stroke-linejoin': 'round' }, [
  h('path', { d: 'M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2' }),
  h('circle', { cx: '12', cy: '7', r: '4' })
])

const UserCheckIcon = () => h('svg', { xmlns: 'http://www.w3.org/2000/svg', width: '16', height: '16', viewBox: '0 0 24 24', fill: 'none', stroke: 'currentColor', 'stroke-width': '2', 'stroke-linecap': 'round', 'stroke-linejoin': 'round' }, [
  h('path', { d: 'M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2' }),
  h('circle', { cx: '8.5', cy: '7', r: '4' }),
  h('polyline', { points: '17 11 19 13 23 9' })
])

const UsersIcon = () => h('svg', { xmlns: 'http://www.w3.org/2000/svg', width: '16', height: '16', viewBox: '0 0 24 24', fill: 'none', stroke: 'currentColor', 'stroke-width': '2', 'stroke-linecap': 'round', 'stroke-linejoin': 'round' }, [
  h('path', { d: 'M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2' }),
  h('circle', { cx: '9', cy: '7', r: '4' }),
  h('path', { d: 'M23 21v-2a4 4 0 0 0-3-3.87' }),
  h('path', { d: 'M16 3.13a4 4 0 0 1 0 7.75' })
])

const tiposDestinatarios = [
  { value: 'individual', label: 'Un número', iconSvg: UserIcon },
  { value: 'seleccion', label: 'Seleccionar clientes', iconSvg: UserCheckIcon },
  { value: 'todos', label: 'Todos los clientes', iconSvg: UsersIcon }
]

// Computed
const horaActual = computed(() => {
  return new Date().toLocaleTimeString('es-MX', { hour: '2-digit', minute: '2-digit' })
})

const puedeEnviar = computed(() => {
  if (!comunicacion.value.mensaje.trim()) return false
  if (comunicacion.value.tipo === 'individual' && telefonoIndividual.value.length < 10) return false
  if (comunicacion.value.tipo === 'seleccion' && clientesSeleccionados.value.length === 0) return false
  return true
})

const previewContenido = computed(() => {
  if (!plantillaEditando.value) return contenidoEditando.value
  
  const datosEjemplo: Record<string, string> = {
    cliente_nombre: 'María García',
    codigo_otp: '123456',
    expiracion_minutos: '3',
    fecha: '15/12/2025',
    hora: '14:30',
    servicios: 'Corte de cabello, Tinte',
    empleado_nombre: 'Ana López',
    precio_total: '450.00',
    duracion_total: '90',
    negocio_nombre: 'Mi Negocio',
    negocio_direccion: 'Av. Principal #123',
    motivo_cancelacion: '📋 Motivo: Solicitud del cliente'
  }
  
  let contenido = contenidoEditando.value
  for (const [variable, valor] of Object.entries(datosEjemplo)) {
    contenido = contenido.replace(new RegExp(`\\{\\{${variable}\\}\\}`, 'g'), valor)
  }
  return contenido
})

// Methods
const fetchApi = async (url: string, options: RequestInit = {}) => {
  const response = await fetch(`${API_URL}${url}`, {
    ...options,
    headers: {
      'Content-Type': 'application/json',
      'Accept': 'application/json',
      'Authorization': `Bearer ${authStore.token}`,
      ...options.headers
    }
  })
  return response.json()
}

const cargarPlantillas = async () => {
  loading.value = true
  try {
    const data = await fetchApi('/admin/plantillas')
    if (data.success) {
      plantillas.value = data.plantillas
    }
  } catch (error) {
    console.error('Error cargando plantillas:', error)
  } finally {
    loading.value = false
  }
}

const cargarEstadisticas = async () => {
  try {
    const data = await fetchApi('/admin/comunicaciones/estadisticas')
    if (data.success) {
      estadisticas.value = data.estadisticas
    }
  } catch (error) {
    console.error('Error cargando estadísticas:', error)
  }
}

const editarPlantilla = (grupo: any) => {
  plantillaEditando.value = grupo
  const plantillaWa = grupo.plantillas?.find((p: any) => p.medio === 'whatsapp') || grupo.plantillas?.[0]
  contenidoEditando.value = plantillaWa?.contenido || ''
  modalPlantilla.value = true
}

const cerrarModal = () => {
  modalPlantilla.value = false
  plantillaEditando.value = null
  contenidoEditando.value = ''
}

const insertarVariable = (variable: string) => {
  const textarea = textareaRef.value
  if (!textarea) return
  
  const start = textarea.selectionStart
  const end = textarea.selectionEnd
  const text = contenidoEditando.value
  const variableText = `{{${variable}}}`
  
  contenidoEditando.value = text.substring(0, start) + variableText + text.substring(end)
  
  setTimeout(() => {
    textarea.focus()
    textarea.setSelectionRange(start + variableText.length, start + variableText.length)
  }, 0)
}

const guardarPlantilla = async () => {
  if (!plantillaEditando.value) return
  
  guardando.value = true
  try {
    const plantillaWa = plantillaEditando.value.plantillas?.find((p: any) => p.medio === 'whatsapp') || plantillaEditando.value.plantillas?.[0]
    if (!plantillaWa) return
    
    const data = await fetchApi(`/admin/plantillas/${plantillaWa.id}`, {
      method: 'PUT',
      body: JSON.stringify({
        contenido: contenidoEditando.value
      })
    })
    
    if (data.success) {
      await cargarPlantillas()
      cerrarModal()
      Swal.fire({
        icon: 'success',
        title: '¡Guardado!',
        text: 'Plantilla guardada correctamente',
        timer: 2000,
        showConfirmButton: false,
        confirmButtonColor: '#34c759'
      })
    } else {
      Swal.fire({
        icon: 'error',
        title: 'Error',
        text: data.message || 'No se pudo guardar la plantilla',
        confirmButtonColor: '#ff3b30'
      })
    }
  } catch (error) {
    console.error('Error guardando plantilla:', error)
    Swal.fire({
      icon: 'error',
      title: 'Error',
      text: 'Error al guardar la plantilla',
      confirmButtonColor: '#ff3b30'
    })
  } finally {
    guardando.value = false
  }
}

const restablecerPlantilla = async () => {
  if (!plantillaEditando.value) return
  
  const result = await Swal.fire({
    title: '¿Restablecer plantilla?',
    text: 'Se perderán todos los cambios y volverá al mensaje por defecto',
    icon: 'warning',
    showCancelButton: true,
    confirmButtonColor: '#007aff',
    cancelButtonColor: '#86868b',
    confirmButtonText: 'Sí, restablecer',
    cancelButtonText: 'Cancelar'
  })
  
  if (!result.isConfirmed) return
  
  guardando.value = true
  try {
    const plantillaWa = plantillaEditando.value.plantillas?.find((p: any) => p.medio === 'whatsapp') || plantillaEditando.value.plantillas?.[0]
    if (!plantillaWa) return
    
    const data = await fetchApi(`/admin/plantillas/${plantillaWa.id}/restablecer`, {
      method: 'POST'
    })
    
    if (data.success) {
      contenidoEditando.value = data.plantilla?.contenido || ''
      await cargarPlantillas()
      Swal.fire({
        icon: 'success',
        title: '¡Restablecido!',
        text: 'Plantilla restablecida a valores por defecto',
        timer: 2000,
        showConfirmButton: false,
        confirmButtonColor: '#34c759'
      })
    }
  } catch (error) {
    console.error('Error restableciendo plantilla:', error)
    Swal.fire({
      icon: 'error',
      title: 'Error',
      text: 'No se pudo restablecer la plantilla',
      confirmButtonColor: '#ff3b30'
    })
  } finally {
    guardando.value = false
  }
}

let busquedaTimeout: number | null = null
const buscarClientes = async () => {
  if (busquedaTimeout) clearTimeout(busquedaTimeout)
  
  busquedaTimeout = window.setTimeout(async () => {
    if (busquedaCliente.value.length < 2) {
      clientesEncontrados.value = []
      return
    }
    
    try {
      const data = await fetchApi(`/admin/comunicaciones/clientes?buscar=${encodeURIComponent(busquedaCliente.value)}`)
      if (data.success) {
        clientesEncontrados.value = data.clientes
      }
    } catch (error) {
      console.error('Error buscando clientes:', error)
    }
  }, 300)
}

const toggleCliente = (cliente: any) => {
  const index = clientesSeleccionados.value.indexOf(cliente.telefono)
  if (index === -1) {
    clientesSeleccionados.value.push(cliente.telefono)
  } else {
    clientesSeleccionados.value.splice(index, 1)
  }
}

const enviarComunicacion = async () => {
  if (!puedeEnviar.value) return
  
  let telefonos: string[] = []
  
  if (comunicacion.value.tipo === 'individual') {
    telefonos = [telefonoIndividual.value]
  } else if (comunicacion.value.tipo === 'seleccion') {
    telefonos = clientesSeleccionados.value
  }
  
  const totalDestinatarios = comunicacion.value.tipo === 'todos' 
    ? estadisticas.value.clientes_whatsapp 
    : telefonos.length
  
  const result = await Swal.fire({
    title: '¿Enviar comunicado?',
    html: `
      <div style="text-align: left; padding: 10px 0;">
        <p><strong>Destinatarios:</strong> ${totalDestinatarios} persona(s)</p>
        ${comunicacion.value.titulo ? `<p><strong>Título:</strong> ${comunicacion.value.titulo}</p>` : ''}
        <p style="color: #666; font-size: 0.9em;">El mensaje se enviará por WhatsApp</p>
      </div>
    `,
    icon: 'question',
    showCancelButton: true,
    confirmButtonColor: '#34c759',
    cancelButtonColor: '#86868b',
    confirmButtonText: 'Enviar',
    cancelButtonText: 'Cancelar'
  })
  
  if (!result.isConfirmed) return
  
  enviando.value = true
  try {
    const data = await fetchApi('/admin/comunicaciones/enviar', {
      method: 'POST',
      body: JSON.stringify({
        tipo: comunicacion.value.tipo,
        telefonos: telefonos,
        titulo: comunicacion.value.titulo,
        mensaje: comunicacion.value.mensaje
      })
    })
    
    if (data.success) {
      Swal.fire({
        icon: 'success',
        title: '¡Enviado!',
        text: data.message || `Comunicado enviado a ${totalDestinatarios} destinatario(s)`,
        timer: 3000,
        showConfirmButton: false,
        confirmButtonColor: '#34c759'
      })
      comunicacion.value = { tipo: 'seleccion', titulo: '', mensaje: '' }
      clientesSeleccionados.value = []
      telefonoIndividual.value = ''
    } else {
      Swal.fire({
        icon: 'error',
        title: 'Error',
        text: data.message || 'No se pudo enviar el comunicado',
        confirmButtonColor: '#ff3b30'
      })
    }
  } catch (error) {
    console.error('Error enviando comunicación:', error)
    Swal.fire({
      icon: 'error',
      title: 'Error',
      text: 'Error al enviar la comunicación',
      confirmButtonColor: '#ff3b30'
    })
  } finally {
    enviando.value = false
  }
}

const formatearMensaje = (mensaje: string) => {
  if (!mensaje) return ''
  return mensaje
    .replace(/\*([^*]+)\*/g, '<strong>$1</strong>')
    .replace(/_([^_]+)_/g, '<em>$1</em>')
    .replace(/\n/g, '<br>')
}

const getIconSvg = (tipo: string) => {
  const icons: Record<string, any> = {
    otp: () => h('svg', { xmlns: 'http://www.w3.org/2000/svg', width: '20', height: '20', viewBox: '0 0 24 24', fill: 'none', stroke: 'currentColor', 'stroke-width': '2', 'stroke-linecap': 'round', 'stroke-linejoin': 'round' }, [
      h('path', { d: 'M21 2l-2 2m-7.61 7.61a5.5 5.5 0 1 1-7.778 7.778 5.5 5.5 0 0 1 7.777-7.777zm0 0L15.5 7.5m0 0l3 3L22 7l-3-3m-3.5 3.5L19 4' })
    ]),
    confirmacion_cita: () => h('svg', { xmlns: 'http://www.w3.org/2000/svg', width: '20', height: '20', viewBox: '0 0 24 24', fill: 'none', stroke: 'currentColor', 'stroke-width': '2', 'stroke-linecap': 'round', 'stroke-linejoin': 'round' }, [
      h('rect', { x: '3', y: '4', width: '18', height: '18', rx: '2', ry: '2' }),
      h('line', { x1: '16', y1: '2', x2: '16', y2: '6' }),
      h('line', { x1: '8', y1: '2', x2: '8', y2: '6' }),
      h('line', { x1: '3', y1: '10', x2: '21', y2: '10' }),
      h('path', { d: 'M9 16l2 2 4-4' })
    ]),
    modificacion_cita: () => h('svg', { xmlns: 'http://www.w3.org/2000/svg', width: '20', height: '20', viewBox: '0 0 24 24', fill: 'none', stroke: 'currentColor', 'stroke-width': '2', 'stroke-linecap': 'round', 'stroke-linejoin': 'round' }, [
      h('rect', { x: '3', y: '4', width: '18', height: '18', rx: '2', ry: '2' }),
      h('line', { x1: '16', y1: '2', x2: '16', y2: '6' }),
      h('line', { x1: '8', y1: '2', x2: '8', y2: '6' }),
      h('line', { x1: '3', y1: '10', x2: '21', y2: '10' }),
      h('path', { d: 'M8 14h.01M12 14h.01M16 14h.01M8 18h.01M12 18h.01M16 18h.01' })
    ]),
    cancelacion_cita: () => h('svg', { xmlns: 'http://www.w3.org/2000/svg', width: '20', height: '20', viewBox: '0 0 24 24', fill: 'none', stroke: 'currentColor', 'stroke-width': '2', 'stroke-linecap': 'round', 'stroke-linejoin': 'round' }, [
      h('rect', { x: '3', y: '4', width: '18', height: '18', rx: '2', ry: '2' }),
      h('line', { x1: '16', y1: '2', x2: '16', y2: '6' }),
      h('line', { x1: '8', y1: '2', x2: '8', y2: '6' }),
      h('line', { x1: '3', y1: '10', x2: '21', y2: '10' }),
      h('line', { x1: '10', y1: '14', x2: '14', y2: '18' }),
      h('line', { x1: '14', y1: '14', x2: '10', y2: '18' })
    ]),
    recordatorio_cita: () => h('svg', { xmlns: 'http://www.w3.org/2000/svg', width: '20', height: '20', viewBox: '0 0 24 24', fill: 'none', stroke: 'currentColor', 'stroke-width': '2', 'stroke-linecap': 'round', 'stroke-linejoin': 'round' }, [
      h('circle', { cx: '12', cy: '12', r: '10' }),
      h('polyline', { points: '12 6 12 12 16 14' })
    ])
  }
  return icons[tipo] || (() => h('svg', { xmlns: 'http://www.w3.org/2000/svg', width: '20', height: '20', viewBox: '0 0 24 24', fill: 'none', stroke: 'currentColor', 'stroke-width': '2', 'stroke-linecap': 'round', 'stroke-linejoin': 'round' }, [
    h('path', { d: 'M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z' })
  ]))
}

const getIconClass = (tipo: string) => {
  const classes: Record<string, string> = {
    otp: 'purple',
    confirmacion_cita: 'green',
    modificacion_cita: 'blue',
    cancelacion_cita: 'red',
    recordatorio_cita: 'orange'
  }
  return classes[tipo] || 'blue'
}

const formatVariable = (variable: string) => {
  return `{{${variable}}}`
}

// Lifecycle
onMounted(() => {
  cargarPlantillas()
})
</script>

<style scoped>
/* ===== Apple-inspired Mensajes View Design ===== */

.mensajes-view {
  min-height: 100vh;
  background: #f5f5f7;
  padding: 24px;
  padding-bottom: 120px;
  font-family: -apple-system, BlinkMacSystemFont, 'SF Pro Display', 'SF Pro Text', 'Helvetica Neue', sans-serif;
  -webkit-font-smoothing: antialiased;
  -moz-osx-font-smoothing: grayscale;
}

/* Header */
.mensajes-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 20px;
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

/* Tabs */
.tabs-container {
  display: flex;
  gap: 8px;
  margin-bottom: 20px;
  background: #ffffff;
  padding: 6px;
  border-radius: 14px;
  box-shadow: 0 2px 12px rgba(0, 0, 0, 0.08);
  border: 1px solid #e5e5ea;
  overflow-x: auto;
  scrollbar-width: none;
  -ms-overflow-style: none;
}

.tabs-container::-webkit-scrollbar {
  display: none;
}

.tab-btn {
  flex: 1;
  min-width: 0;
  padding: 10px 14px;
  border: none;
  background: transparent;
  border-radius: 10px;
  font-size: 14px;
  font-weight: 500;
  color: #86868b;
  cursor: pointer;
  transition: all 0.2s;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 6px;
  white-space: nowrap;
}

.tab-btn:active {
  background: #f5f5f7;
}

.tab-btn.active {
  background: linear-gradient(135deg, #007aff 0%, #5856d6 100%);
  color: white;
  box-shadow: 0 2px 8px rgba(0, 122, 255, 0.3);
}

.tab-btn svg {
  flex-shrink: 0;
}

.tab-text {
  display: none;
}

@media (min-width: 480px) {
  .tab-text {
    display: inline;
  }
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

@keyframes spin {
  to { transform: rotate(360deg); }
}

.loading-container p {
  color: #86868b;
  font-size: 15px;
  margin: 0;
}

/* Tab Content */
.tab-content {
  animation: fadeIn 0.2s ease;
}

@keyframes fadeIn {
  from { opacity: 0; transform: translateY(8px); }
  to { opacity: 1; transform: translateY(0); }
}

/* Plantillas Grid */
.plantillas-grid {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.plantilla-card {
  display: flex;
  align-items: center;
  gap: 14px;
  background: #ffffff;
  padding: 16px;
  border-radius: 16px;
  box-shadow: 0 2px 12px rgba(0, 0, 0, 0.08);
  border: 1px solid #e5e5ea;
  cursor: pointer;
  transition: all 0.2s;
}

.plantilla-card:active {
  transform: scale(0.98);
  box-shadow: 0 1px 6px rgba(0, 0, 0, 0.12);
}

.plantilla-icon {
  width: 48px;
  height: 48px;
  border-radius: 14px;
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
  flex-shrink: 0;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
}

.plantilla-icon.purple { background: linear-gradient(135deg, #5856d6 0%, #af52de 100%); }
.plantilla-icon.green { background: linear-gradient(135deg, #34c759 0%, #30d158 100%); }
.plantilla-icon.blue { background: linear-gradient(135deg, #007aff 0%, #5ac8fa 100%); }
.plantilla-icon.red { background: linear-gradient(135deg, #ff3b30 0%, #ff453a 100%); }
.plantilla-icon.orange { background: linear-gradient(135deg, #ff9500 0%, #ffad33 100%); }

.plantilla-info {
  flex: 1;
  min-width: 0;
}

.plantilla-info h3 {
  margin: 0 0 4px;
  font-size: 16px;
  font-weight: 600;
  color: #1d1d1f;
  letter-spacing: -0.2px;
}

.plantilla-info p {
  margin: 0;
  font-size: 13px;
  color: #86868b;
  line-height: 1.4;
}

.plantilla-action {
  color: #c7c7cc;
  flex-shrink: 0;
}

/* Comunicación Container */
.comunicacion-container {
  background: #ffffff;
  border-radius: 20px;
  padding: 20px;
  box-shadow: 0 2px 12px rgba(0, 0, 0, 0.08);
  border: 1px solid #e5e5ea;
}

.form-section {
  margin-bottom: 24px;
}

.form-section:last-child {
  margin-bottom: 0;
}

.section-label {
  display: flex;
  align-items: center;
  gap: 8px;
  font-size: 13px;
  font-weight: 600;
  color: #1d1d1f;
  margin-bottom: 12px;
  letter-spacing: -0.1px;
}

.section-label svg {
  color: #007aff;
  flex-shrink: 0;
}

/* Tipo Selector */
.tipo-selector {
  display: flex;
  gap: 10px;
  flex-wrap: wrap;
}

.tipo-option {
  flex: 1;
  min-width: 120px;
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 12px 14px;
  border: 2px solid #e5e5ea;
  border-radius: 12px;
  cursor: pointer;
  transition: all 0.2s;
  font-size: 14px;
  font-weight: 500;
  color: #86868b;
  background: #ffffff;
}

.tipo-option input {
  display: none;
}

.tipo-option:active {
  transform: scale(0.98);
}

.tipo-option.selected {
  border-color: #007aff;
  background: #e8f4fd;
  color: #007aff;
  box-shadow: 0 0 0 4px rgba(0, 122, 255, 0.1);
}

.tipo-option svg {
  flex-shrink: 0;
}

/* Búsqueda Cliente */
.buscar-cliente {
  position: relative;
  display: flex;
  align-items: center;
}

.buscar-cliente svg {
  position: absolute;
  left: 14px;
  color: #86868b;
  z-index: 1;
  pointer-events: none;
}

.buscar-cliente input {
  width: 100%;
  padding: 14px 14px 14px 40px;
  border: 1px solid #e5e5ea;
  border-radius: 12px;
  font-size: 15px;
  color: #1d1d1f;
  background: #f5f5f7;
  transition: all 0.2s;
  box-sizing: border-box;
  font-family: inherit;
}

.buscar-cliente input:focus {
  outline: none;
  border-color: #007aff;
  background: #ffffff;
  box-shadow: 0 0 0 4px rgba(0, 122, 255, 0.1);
}

.buscar-cliente input::placeholder {
  color: #86868b;
}

/* Clientes Lista */
.clientes-lista {
  margin-top: 12px;
  max-height: 250px;
  overflow-y: auto;
  border: 1px solid #e5e5ea;
  border-radius: 12px;
  background: #f5f5f7;
}

.cliente-item {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 12px 14px;
  cursor: pointer;
  transition: background 0.2s;
  border-bottom: 1px solid #e5e5ea;
}

.cliente-item:last-child {
  border-bottom: none;
}

.cliente-item:active {
  background: #e8f4fd;
}

.cliente-item.selected {
  background: #e8f4fd;
}

.cliente-avatar {
  width: 40px;
  height: 40px;
  background: linear-gradient(135deg, #007aff 0%, #5856d6 100%);
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
  font-weight: 600;
  font-size: 16px;
  flex-shrink: 0;
  box-shadow: 0 2px 6px rgba(0, 122, 255, 0.2);
}

.cliente-info {
  flex: 1;
  min-width: 0;
  display: flex;
  flex-direction: column;
  gap: 2px;
}

.cliente-info .nombre {
  font-weight: 500;
  color: #1d1d1f;
  font-size: 15px;
}

.cliente-info .telefono {
  font-size: 13px;
  color: #86868b;
}

.cliente-check {
  flex-shrink: 0;
  color: #c7c7cc;
}

.cliente-item.selected .cliente-check {
  color: #007aff;
}

.seleccionados-count {
  margin-top: 12px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 10px 14px;
  background: #e8f4fd;
  border-radius: 12px;
  font-size: 13px;
  color: #007aff;
  font-weight: 500;
}

.btn-limpiar {
  background: none;
  border: none;
  color: #ff3b30;
  cursor: pointer;
  font-size: 13px;
  font-weight: 600;
  padding: 4px 8px;
  border-radius: 6px;
  transition: background 0.2s;
}

.btn-limpiar:active {
  background: rgba(255, 59, 48, 0.1);
}

/* Form Inputs */
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

.form-input {
  margin-bottom: 12px;
}

.form-textarea {
  resize: vertical;
  min-height: 120px;
  line-height: 1.5;
}

.char-count {
  text-align: right;
  font-size: 12px;
  color: #86868b;
  margin-top: 6px;
}

/* WhatsApp Preview */
.whatsapp-preview {
  background: #e5ddd5;
  background-image: url("data:image/svg+xml,%3Csvg width='60' height='60' xmlns='http://www.w3.org/2000/svg'%3E%3Cpath d='M54.627 0l.83.828-1.415 1.415L51.8 0h2.827zM5.373 0l-.83.828L5.96 2.243 8.2 0H5.374zM48.97 0l3.657 3.657-1.414 1.414L46.143 0h2.828zM11.03 0L7.372 3.657 8.787 5.07 13.857 0H11.03zm32.284 0L49.8 6.485 48.384 7.9l-7.9-7.9h2.83zM16.686 0L10.2 6.485 11.616 7.9l7.9-7.9h-2.83zm20.97 0l9.315 9.314-1.414 1.414L34.828 0h2.83zM22.344 0L13.03 9.314l1.414 1.414L25.172 0h-2.83zM32 0l12.142 12.142-1.414 1.414L30 .828 17.272 13.556l-1.414-1.414L28 0h4zM.284 0l28 28-1.414 1.414L0 2.544v-2.26zm0 5.373l25.456 25.456-1.414 1.414L0 7.83v-2.46zm0 5.656l22.627 22.627-1.414 1.414L0 13.485v-2.456zm0 5.656l19.8 19.8-1.415 1.413L0 19.14v-2.456zm0 5.657l16.97 16.97-1.414 1.415L0 24.8v-2.456zm0 5.657l14.142 14.142-1.414 1.414L0 30.456v-2.457zM0 33.8l11.314 11.314-1.414 1.414L0 36.657v-2.86zM0 39.8l8.485 8.485-1.414 1.414L0 42.627v-2.83zM0 45.8l5.657 5.657-1.414 1.414L0 48.485v-2.686zM0 51.8l2.828 2.83-1.414 1.413L0 54.63v-2.83zM60 5.373L54.627 0H60v5.373zM60 11.03l-8.485 8.485-1.414-1.414L57.172 0H60v11.03zm0 5.656l-8.485 8.485-1.414-1.414 7.07-7.07L60 13.857v2.83zM60 22.344L47.658 34.686l-1.414-1.414 10.928-10.928L60 19.515v2.83zM60 28l-10.142 10.142-1.414-1.414 8.728-8.728L60 25.172v2.83zM60 33.657L52.343 41.314l-1.414-1.414 6.243-6.243L60 30.83v2.83zm0 5.657l-5.657 5.657-1.414-1.414 4.243-4.243L60 36.485v2.83zm0 5.657l-2.828 2.83-1.414-1.415.585-.585L60 42.14v2.83z' fill='%23d5d0c9' fill-opacity='.4' fill-rule='evenodd'/%3E%3C/svg%3E");
  padding: 16px;
  border-radius: 12px;
  border: 1px solid #d4c5b9;
}

.preview-header {
  display: flex;
  align-items: center;
  gap: 8px;
  margin-bottom: 12px;
}

.preview-avatar {
  width: 32px;
  height: 32px;
  background: linear-gradient(135deg, #007aff 0%, #5856d6 100%);
  border-radius: 10px;
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
  box-shadow: 0 2px 6px rgba(0, 122, 255, 0.2);
}

.preview-header span {
  font-weight: 600;
  font-size: 14px;
  color: #1d1d1f;
}

.preview-bubble {
  background: #ffffff;
  padding: 12px 14px;
  border-radius: 0 12px 12px 12px;
  max-width: 85%;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.preview-titulo {
  margin: 0 0 8px;
  font-size: 14px;
}

.preview-mensaje {
  margin: 0;
  font-size: 14px;
  line-height: 1.5;
  color: #1d1d1f;
  white-space: pre-wrap;
  word-wrap: break-word;
}

.preview-time {
  display: block;
  text-align: right;
  font-size: 11px;
  color: #86868b;
  margin-top: 6px;
}

/* Form Actions */
.form-actions {
  margin-top: 24px;
  text-align: center;
}

.btn-enviar {
  padding: 14px 28px;
  background: linear-gradient(135deg, #34c759 0%, #30d158 100%);
  color: white;
  border: none;
  border-radius: 12px;
  font-size: 15px;
  font-weight: 600;
  cursor: pointer;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  transition: all 0.2s;
  box-shadow: 0 4px 12px rgba(52, 199, 89, 0.3);
}

.btn-enviar:active:not(:disabled) {
  transform: scale(0.98);
  box-shadow: 0 2px 8px rgba(52, 199, 89, 0.4);
}

.btn-enviar:disabled {
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

/* Estadísticas */
.stats-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
  gap: 12px;
}

.stat-card {
  background: #ffffff;
  padding: 16px;
  border-radius: 16px;
  box-shadow: 0 2px 12px rgba(0, 0, 0, 0.08);
  border: 1px solid #e5e5ea;
  display: flex;
  align-items: center;
  gap: 12px;
}

.stat-icon {
  width: 44px;
  height: 44px;
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
  flex-shrink: 0;
  box-shadow: 0 2px 6px rgba(0, 0, 0, 0.15);
}

.stat-icon.blue { background: linear-gradient(135deg, #007aff 0%, #5ac8fa 100%); }
.stat-icon.green { background: linear-gradient(135deg, #34c759 0%, #30d158 100%); }
.stat-icon.purple { background: linear-gradient(135deg, #5856d6 0%, #af52de 100%); }
.stat-icon.orange { background: linear-gradient(135deg, #ff9500 0%, #ffad33 100%); }

.stat-info {
  display: flex;
  flex-direction: column;
  flex: 1;
  min-width: 0;
}

.stat-value {
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
  margin-top: 2px;
}

/* Bottom Sheet Modal */
.bottom-sheet-overlay {
  position: fixed;
  inset: 0;
  background: rgba(0, 0, 0, 0.4);
  backdrop-filter: blur(8px);
  z-index: 1000;
  display: flex;
  align-items: flex-end;
  justify-content: center;
  animation: fadeIn 0.2s ease;
}

.bottom-sheet-content {
  background: #ffffff;
  border-radius: 24px 24px 0 0;
  width: 100%;
  max-width: 600px;
  max-height: 90vh;
  overflow-y: auto;
  box-shadow: 0 -4px 24px rgba(0, 0, 0, 0.15);
  animation: slideUp 0.3s cubic-bezier(0.16, 1, 0.3, 1);
}

@keyframes slideUp {
  from { transform: translateY(100%); }
  to { transform: translateY(0); }
}

.bottom-sheet-handle {
  width: 40px;
  height: 4px;
  background: #c7c7cc;
  border-radius: 2px;
  margin: 12px auto 8px;
}

.bottom-sheet-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 16px 20px;
  border-bottom: 1px solid #e5e5ea;
  position: sticky;
  top: 0;
  background: #ffffff;
  z-index: 10;
}

.bottom-sheet-header h2 {
  margin: 0;
  font-size: 18px;
  font-weight: 600;
  color: #1d1d1f;
  display: flex;
  align-items: center;
  gap: 10px;
  letter-spacing: -0.3px;
}

.bottom-sheet-header h2 svg {
  color: #007aff;
  flex-shrink: 0;
}

.btn-close {
  width: 36px;
  height: 36px;
  border-radius: 10px;
  border: none;
  background: #f5f5f7;
  color: #1d1d1f;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all 0.2s;
}

.btn-close:active {
  background: #e5e5ea;
  transform: scale(0.95);
}

.bottom-sheet-body {
  padding: 20px;
}

.modal-descripcion {
  color: #86868b;
  margin: 0 0 20px;
  font-size: 14px;
  line-height: 1.5;
}

/* Variables Section */
.variables-section {
  margin-bottom: 24px;
}

.variables-list {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
}

.variable-chip {
  display: flex;
  flex-direction: column;
  gap: 4px;
  padding: 10px 12px;
  background: #f5f5f7;
  border-radius: 10px;
  cursor: pointer;
  transition: all 0.2s;
  border: 1px solid transparent;
}

.variable-chip:active {
  background: #e8f4fd;
  border-color: #007aff;
  transform: scale(0.98);
}

.variable-chip code {
  font-size: 12px;
  color: #007aff;
  font-weight: 600;
  font-family: 'Consolas', 'Monaco', monospace;
}

.variable-chip span {
  font-size: 11px;
  color: #86868b;
}

/* Editor Section */
.editor-section {
  margin-bottom: 24px;
}

.editor-textarea {
  width: 100%;
  padding: 14px;
  border: 1px solid #e5e5ea;
  border-radius: 12px;
  font-size: 14px;
  font-family: 'Consolas', 'Monaco', monospace;
  resize: vertical;
  min-height: 200px;
  line-height: 1.6;
  color: #1d1d1f;
  background: #f5f5f7;
  transition: all 0.2s;
  box-sizing: border-box;
}

.editor-textarea:focus {
  outline: none;
  border-color: #007aff;
  background: #ffffff;
  box-shadow: 0 0 0 4px rgba(0, 122, 255, 0.1);
}

.preview-section-modal {
  margin-bottom: 24px;
}

/* Bottom Sheet Footer */
.bottom-sheet-footer {
  display: flex;
  justify-content: flex-end;
  gap: 10px;
  padding: 16px 20px;
  border-top: 1px solid #e5e5ea;
  background: #fafafa;
  position: sticky;
  bottom: 0;
}

.btn-secondary,
.btn-primary {
  padding: 12px 20px;
  border-radius: 12px;
  font-size: 14px;
  font-weight: 600;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  transition: all 0.2s;
}

.btn-secondary {
  background: #ffffff;
  border: 1px solid #e5e5ea;
  color: #1d1d1f;
}

.btn-secondary:active {
  background: #f5f5f7;
  transform: scale(0.98);
}

.btn-primary {
  background: linear-gradient(135deg, #007aff 0%, #5856d6 100%);
  border: none;
  color: white;
  box-shadow: 0 4px 12px rgba(0, 122, 255, 0.3);
}

.btn-primary:active:not(:disabled) {
  transform: scale(0.98);
  box-shadow: 0 2px 8px rgba(0, 122, 255, 0.4);
}

.btn-primary:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

/* Bottom Sheet Transition */
.bottom-sheet-enter-active,
.bottom-sheet-leave-active {
  transition: all 0.3s ease;
}

.bottom-sheet-enter-from,
.bottom-sheet-leave-to {
  opacity: 0;
}

.bottom-sheet-enter-from .bottom-sheet-content,
.bottom-sheet-leave-to .bottom-sheet-content {
  transform: translateY(100%);
}

/* Responsive */
@media (max-width: 640px) {
  .mensajes-view {
    padding: 16px;
  }
  
  .mensajes-header {
    padding: 16px;
    margin-bottom: 16px;
  }
  
  .header-icon {
    width: 40px;
    height: 40px;
  }
  
  .header-text h1 {
    font-size: 20px;
  }
  
  .header-subtitle {
    font-size: 12px;
  }
  
  .tabs-container {
    margin-bottom: 16px;
  }
  
  .tab-btn {
    padding: 10px 12px;
    font-size: 13px;
  }
  
  .comunicacion-container {
    padding: 16px;
  }
  
  .tipo-selector {
    flex-direction: column;
  }
  
  .tipo-option {
    min-width: 0;
  }
  
  .stats-grid {
    grid-template-columns: 1fr;
  }
}
</style>
