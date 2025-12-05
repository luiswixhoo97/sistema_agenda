<template>
  <div class="admin-view">
    <!-- Header -->
    <div class="view-header">
      <div class="header-info">
        <div class="header-icon">
          <i class="fa fa-comment-dots"></i>
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
        <i class="fa fa-file-alt"></i>
        Plantillas de Mensajes
      </button>
      <button 
        :class="['tab-btn', { active: activeTab === 'comunicacion' }]"
        @click="activeTab = 'comunicacion'"
      >
        <i class="fa fa-bullhorn"></i>
        Enviar Comunicado
      </button>
      <button 
        :class="['tab-btn', { active: activeTab === 'estadisticas' }]"
        @click="activeTab = 'estadisticas'; cargarEstadisticas()"
      >
        <i class="fa fa-chart-bar"></i>
        Estadísticas
      </button>
    </div>

    <!-- Loading -->
    <div v-if="loading" class="loading-state">
      <div class="spinner"></div>
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
            <i :class="getIcon(grupo.tipo)"></i>
          </div>
          <div class="plantilla-info">
            <h3>{{ grupo.nombre }}</h3>
            <p>{{ grupo.descripcion }}</p>
          </div>
          <div class="plantilla-action">
            <i class="fa fa-chevron-right"></i>
          </div>
        </div>
      </div>
    </div>

    <!-- Tab: Comunicación -->
    <div v-else-if="activeTab === 'comunicacion'" class="tab-content">
      <div class="comunicacion-container">
        <!-- Tipo de destinatarios -->
        <div class="form-section">
          <h3><i class="fa fa-users"></i> Destinatarios</h3>
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
              <i :class="tipo.icon"></i>
              <span>{{ tipo.label }}</span>
            </label>
          </div>
        </div>

        <!-- Selección de clientes -->
        <div v-if="comunicacion.tipo === 'seleccion'" class="form-section">
          <h3><i class="fa fa-search"></i> Seleccionar Clientes</h3>
          <div class="buscar-cliente">
            <input 
              v-model="busquedaCliente"
              type="text"
              placeholder="Buscar por nombre o teléfono..."
              @input="buscarClientes"
            />
            <i class="fa fa-search"></i>
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
                <i :class="clientesSeleccionados.includes(cliente.telefono) ? 'fa fa-check-circle' : 'fa fa-circle'"></i>
              </div>
            </div>
          </div>
          <div v-if="clientesSeleccionados.length" class="seleccionados-count">
            <span>{{ clientesSeleccionados.length }} cliente(s) seleccionado(s)</span>
            <button class="btn-limpiar" @click="clientesSeleccionados = []">
              Limpiar selección
            </button>
          </div>
        </div>

        <!-- Teléfono individual -->
        <div v-if="comunicacion.tipo === 'individual'" class="form-section">
          <h3><i class="fa fa-phone"></i> Número de teléfono</h3>
          <input 
            v-model="telefonoIndividual"
            type="tel"
            placeholder="Ej: 3312345678"
            maxlength="10"
            class="input-telefono"
          />
        </div>

        <!-- Mensaje -->
        <div class="form-section">
          <h3><i class="fa fa-comment"></i> Mensaje</h3>
          <input 
            v-model="comunicacion.titulo"
            type="text"
            placeholder="Título del comunicado (opcional)"
            class="input-titulo"
          />
          <textarea
            v-model="comunicacion.mensaje"
            rows="6"
            placeholder="Escribe tu mensaje aquí...&#10;&#10;Puedes usar emojis y formato:&#10;*texto en negritas*&#10;_texto en cursiva_"
            class="textarea-mensaje"
          ></textarea>
          <div class="char-count">
            {{ comunicacion.mensaje.length }} / 2000 caracteres
          </div>
        </div>

        <!-- Vista previa -->
        <div class="form-section preview-section">
          <h3><i class="fa fa-eye"></i> Vista previa</h3>
          <div class="whatsapp-preview">
            <div class="preview-header">
              <div class="preview-avatar">
                <i class="fa fa-store"></i>
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
            <i :class="enviando ? 'fa fa-spinner fa-spin' : 'fa fa-paper-plane'"></i>
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
            <i class="fa fa-paper-plane"></i>
          </div>
          <div class="stat-info">
            <span class="stat-value">{{ estadisticas.hoy }}</span>
            <span class="stat-label">Mensajes hoy</span>
          </div>
        </div>
        <div class="stat-card">
          <div class="stat-icon green">
            <i class="fa fa-calendar-week"></i>
          </div>
          <div class="stat-info">
            <span class="stat-value">{{ estadisticas.semana }}</span>
            <span class="stat-label">Esta semana</span>
          </div>
        </div>
        <div class="stat-card">
          <div class="stat-icon purple">
            <i class="fa fa-calendar-alt"></i>
          </div>
          <div class="stat-info">
            <span class="stat-value">{{ estadisticas.mes }}</span>
            <span class="stat-label">Este mes</span>
          </div>
        </div>
        <div class="stat-card">
          <div class="stat-icon orange">
            <i class="fa fa-users"></i>
          </div>
          <div class="stat-info">
            <span class="stat-value">{{ estadisticas.clientes_whatsapp }} / {{ estadisticas.clientes_total }}</span>
            <span class="stat-label">Clientes con WhatsApp</span>
          </div>
        </div>
      </div>
    </div>

    <!-- Modal Editar Plantilla (Bottom Sheet) -->
    <Transition name="bottom-sheet">
      <div v-if="modalPlantilla" class="bottom-sheet-overlay" @click.self="cerrarModal">
        <div class="bottom-sheet-content">
          <div class="bottom-sheet-handle"></div>
          <div class="bottom-sheet-header">
            <h2>
              <i :class="getIcon(plantillaEditando?.tipo || '')"></i>
              {{ plantillaEditando?.nombre }}
            </h2>
            <button class="btn-close" @click="cerrarModal">
              <i class="fa fa-times"></i>
            </button>
          </div>
        <div class="bottom-sheet-body">
          <p class="modal-descripcion">{{ plantillaEditando?.descripcion }}</p>
          
          <!-- Variables disponibles -->
          <div class="variables-section">
            <h4><i class="fa fa-code"></i> Variables disponibles</h4>
            <div class="variables-list">
              <div 
                v-for="(desc, variable) in plantillaEditando?.variables" 
                :key="variable"
                class="variable-chip"
                @click="insertarVariable(variable as string)"
              >
                <code v-text="formatVariable(variable as string)"></code>
                <span>{{ desc }}</span>
              </div>
            </div>
          </div>

          <!-- Editor de contenido -->
          <div class="editor-section">
            <label>Contenido del mensaje</label>
            <textarea
              v-model="contenidoEditando"
              ref="textareaRef"
              rows="8"
              class="editor-textarea"
            ></textarea>
          </div>

          <!-- Vista previa -->
          <div class="preview-section-modal">
            <h4><i class="fa fa-eye"></i> Vista previa</h4>
            <div class="whatsapp-preview">
              <div class="preview-header">
                <div class="preview-avatar">
                  <i class="fa fa-store"></i>
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
            <i class="fa fa-undo"></i>
            Restablecer
          </button>
          <button class="btn-primary" @click="guardarPlantilla" :disabled="guardando">
            <i :class="guardando ? 'fa fa-spinner fa-spin' : 'fa fa-save'"></i>
            {{ guardando ? 'Guardando...' : 'Guardar cambios' }}
          </button>
        </div>
        </div>
      </div>
    </Transition>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useAuthStore } from '@/stores/auth'
import Swal from 'sweetalert2'

const authStore = useAuthStore()
const API_URL = import.meta.env.VITE_API_URL || 'http://localhost:8000/api'

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

const tiposDestinatarios = [
  { value: 'individual', label: 'Un número', icon: 'fa fa-user' },
  { value: 'seleccion', label: 'Seleccionar clientes', icon: 'fa fa-user-check' },
  { value: 'todos', label: 'Todos los clientes', icon: 'fa fa-users' }
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
  // Obtener el contenido de la primera plantilla (WhatsApp)
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
  
  // Posicionar cursor después de la variable
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
        showConfirmButton: false
      })
    } else {
      Swal.fire({
        icon: 'error',
        title: 'Error',
        text: data.message || 'No se pudo guardar la plantilla'
      })
    }
  } catch (error) {
    console.error('Error guardando plantilla:', error)
    Swal.fire({
      icon: 'error',
      title: 'Error',
      text: 'Error al guardar la plantilla'
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
    confirmButtonColor: '#667eea',
    cancelButtonColor: '#6b7280',
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
        showConfirmButton: false
      })
    }
  } catch (error) {
    console.error('Error restableciendo plantilla:', error)
    Swal.fire({
      icon: 'error',
      title: 'Error',
      text: 'No se pudo restablecer la plantilla'
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
    confirmButtonColor: '#10b981',
    cancelButtonColor: '#6b7280',
    confirmButtonText: '<i class="fa fa-paper-plane"></i> Enviar',
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
        showConfirmButton: false
      })
      // Limpiar formulario
      comunicacion.value = { tipo: 'seleccion', titulo: '', mensaje: '' }
      clientesSeleccionados.value = []
      telefonoIndividual.value = ''
    } else {
      Swal.fire({
        icon: 'error',
        title: 'Error',
        text: data.message || 'No se pudo enviar el comunicado'
      })
    }
  } catch (error) {
    console.error('Error enviando comunicación:', error)
    Swal.fire({
      icon: 'error',
      title: 'Error',
      text: 'Error al enviar la comunicación'
    })
  } finally {
    enviando.value = false
  }
}

const formatearMensaje = (mensaje: string) => {
  if (!mensaje) return ''
  // Convertir *texto* a negritas y _texto_ a cursiva
  return mensaje
    .replace(/\*([^*]+)\*/g, '<strong>$1</strong>')
    .replace(/_([^_]+)_/g, '<em>$1</em>')
    .replace(/\n/g, '<br>')
}

const getIcon = (tipo: string) => {
  const icons: Record<string, string> = {
    otp: 'fa fa-key',
    confirmacion_cita: 'fa fa-calendar-check',
    modificacion_cita: 'fa fa-calendar-alt',
    cancelacion_cita: 'fa fa-calendar-times',
    recordatorio_cita: 'fa fa-bell'
  }
  return icons[tipo] || 'fa fa-comment'
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
.admin-view {
  padding: 1.5rem;
  max-width: 1200px;
  margin: 0 auto;
}

.view-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 1.5rem;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  padding: 1.5rem;
  border-radius: 16px;
  color: white;
}

.header-info {
  display: flex;
  align-items: center;
  gap: 1rem;
}

.header-icon {
  width: 56px;
  height: 56px;
  background: rgba(255, 255, 255, 0.2);
  border-radius: 14px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.5rem;
}

.header-text h1 {
  margin: 0;
  font-size: 1.5rem;
  font-weight: 700;
}

.header-subtitle {
  margin: 0.25rem 0 0;
  opacity: 0.9;
  font-size: 0.9rem;
}

/* Tabs */
.tabs-container {
  display: flex;
  gap: 0.5rem;
  margin-bottom: 1.5rem;
  background: white;
  padding: 0.5rem;
  border-radius: 12px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
}

.tab-btn {
  flex: 1;
  padding: 0.75rem 1rem;
  border: none;
  background: transparent;
  border-radius: 8px;
  font-size: 0.9rem;
  font-weight: 500;
  color: #666;
  cursor: pointer;
  transition: all 0.2s;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.5rem;
}

.tab-btn:hover {
  background: #f5f5f5;
}

.tab-btn.active {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
}

/* Loading */
.loading-state {
  text-align: center;
  padding: 4rem 2rem;
  color: #888;
}

.spinner {
  width: 40px;
  height: 40px;
  border: 3px solid #f0f0f0;
  border-top-color: #667eea;
  border-radius: 50%;
  animation: spin 0.8s linear infinite;
  margin: 0 auto 1rem;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}

/* Plantillas Grid */
.plantillas-grid {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.plantilla-card {
  display: flex;
  align-items: center;
  gap: 1rem;
  background: white;
  padding: 1.25rem;
  border-radius: 12px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
  cursor: pointer;
  transition: all 0.2s;
}

.plantilla-card:hover {
  transform: translateX(4px);
  box-shadow: 0 4px 16px rgba(102, 126, 234, 0.2);
}

.plantilla-icon {
  width: 48px;
  height: 48px;
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.25rem;
  color: white;
  flex-shrink: 0;
}

.plantilla-icon.purple { background: linear-gradient(135deg, #9333ea 0%, #7c3aed 100%); }
.plantilla-icon.green { background: linear-gradient(135deg, #10b981 0%, #059669 100%); }
.plantilla-icon.blue { background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%); }
.plantilla-icon.red { background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%); }
.plantilla-icon.orange { background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%); }

.plantilla-info {
  flex: 1;
}

.plantilla-info h3 {
  margin: 0 0 0.25rem;
  font-size: 1rem;
  font-weight: 600;
  color: #1a1a2e;
}

.plantilla-info p {
  margin: 0;
  font-size: 0.85rem;
  color: #666;
}

.plantilla-action {
  color: #ccc;
  font-size: 1rem;
}

/* Comunicación */
.comunicacion-container {
  background: white;
  border-radius: 16px;
  padding: 1.5rem;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
}

.form-section {
  margin-bottom: 1.5rem;
}

.form-section h3 {
  font-size: 1rem;
  font-weight: 600;
  color: #1a1a2e;
  margin: 0 0 1rem;
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.form-section h3 i {
  color: #667eea;
}

.tipo-selector {
  display: flex;
  gap: 0.75rem;
  flex-wrap: wrap;
}

.tipo-option {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.75rem 1rem;
  border: 2px solid #e0e0e0;
  border-radius: 10px;
  cursor: pointer;
  transition: all 0.2s;
  font-size: 0.9rem;
}

.tipo-option input {
  display: none;
}

.tipo-option:hover {
  border-color: #667eea;
}

.tipo-option.selected {
  border-color: #667eea;
  background: linear-gradient(135deg, rgba(102, 126, 234, 0.1) 0%, rgba(118, 75, 162, 0.1) 100%);
  color: #667eea;
}

.tipo-option i {
  font-size: 1rem;
}

/* Búsqueda y lista de clientes */
.buscar-cliente {
  position: relative;
}

.buscar-cliente input {
  width: 100%;
  padding: 0.75rem 1rem 0.75rem 2.5rem;
  border: 2px solid #e0e0e0;
  border-radius: 10px;
  font-size: 0.95rem;
  transition: border-color 0.2s;
}

.buscar-cliente input:focus {
  outline: none;
  border-color: #667eea;
}

.buscar-cliente i {
  position: absolute;
  left: 1rem;
  top: 50%;
  transform: translateY(-50%);
  color: #999;
}

.clientes-lista {
  margin-top: 1rem;
  max-height: 250px;
  overflow-y: auto;
  border: 1px solid #e0e0e0;
  border-radius: 10px;
}

.cliente-item {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  padding: 0.75rem 1rem;
  cursor: pointer;
  transition: background 0.2s;
  border-bottom: 1px solid #f0f0f0;
}

.cliente-item:last-child {
  border-bottom: none;
}

.cliente-item:hover {
  background: #f8f8f8;
}

.cliente-item.selected {
  background: rgba(102, 126, 234, 0.1);
}

.cliente-avatar {
  width: 36px;
  height: 36px;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
  font-weight: 600;
  font-size: 0.9rem;
}

.cliente-info {
  flex: 1;
  display: flex;
  flex-direction: column;
}

.cliente-info .nombre {
  font-weight: 500;
  color: #1a1a2e;
}

.cliente-info .telefono {
  font-size: 0.8rem;
  color: #888;
}

.cliente-check i {
  font-size: 1.25rem;
  color: #ccc;
}

.cliente-item.selected .cliente-check i {
  color: #667eea;
}

.seleccionados-count {
  margin-top: 0.75rem;
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 0.5rem 0.75rem;
  background: rgba(102, 126, 234, 0.1);
  border-radius: 8px;
  font-size: 0.85rem;
  color: #667eea;
}

.btn-limpiar {
  background: none;
  border: none;
  color: #ef4444;
  cursor: pointer;
  font-size: 0.85rem;
}

/* Inputs */
.input-telefono,
.input-titulo {
  width: 100%;
  padding: 0.75rem 1rem;
  border: 2px solid #e0e0e0;
  border-radius: 10px;
  font-size: 0.95rem;
  transition: border-color 0.2s;
}

.input-titulo {
  margin-bottom: 0.75rem;
}

.input-telefono:focus,
.input-titulo:focus,
.textarea-mensaje:focus {
  outline: none;
  border-color: #667eea;
}

.textarea-mensaje {
  width: 100%;
  padding: 1rem;
  border: 2px solid #e0e0e0;
  border-radius: 10px;
  font-size: 0.95rem;
  resize: vertical;
  min-height: 120px;
  font-family: inherit;
}

.char-count {
  text-align: right;
  font-size: 0.8rem;
  color: #888;
  margin-top: 0.25rem;
}

/* WhatsApp Preview */
.whatsapp-preview {
  background: #e5ddd5;
  background-image: url("data:image/svg+xml,%3Csvg width='60' height='60' xmlns='http://www.w3.org/2000/svg'%3E%3Cpath d='M54.627 0l.83.828-1.415 1.415L51.8 0h2.827zM5.373 0l-.83.828L5.96 2.243 8.2 0H5.374zM48.97 0l3.657 3.657-1.414 1.414L46.143 0h2.828zM11.03 0L7.372 3.657 8.787 5.07 13.857 0H11.03zm32.284 0L49.8 6.485 48.384 7.9l-7.9-7.9h2.83zM16.686 0L10.2 6.485 11.616 7.9l7.9-7.9h-2.83zm20.97 0l9.315 9.314-1.414 1.414L34.828 0h2.83zM22.344 0L13.03 9.314l1.414 1.414L25.172 0h-2.83zM32 0l12.142 12.142-1.414 1.414L30 .828 17.272 13.556l-1.414-1.414L28 0h4zM.284 0l28 28-1.414 1.414L0 2.544v-2.26zm0 5.373l25.456 25.456-1.414 1.414L0 7.83v-2.46zm0 5.656l22.627 22.627-1.414 1.414L0 13.485v-2.456zm0 5.656l19.8 19.8-1.415 1.413L0 19.14v-2.456zm0 5.657l16.97 16.97-1.414 1.415L0 24.8v-2.456zm0 5.657l14.142 14.142-1.414 1.414L0 30.456v-2.457zM0 33.8l11.314 11.314-1.414 1.414L0 36.657v-2.86zM0 39.8l8.485 8.485-1.414 1.414L0 42.627v-2.83zM0 45.8l5.657 5.657-1.414 1.414L0 48.485v-2.686zM0 51.8l2.828 2.83-1.414 1.413L0 54.63v-2.83zM60 5.373L54.627 0H60v5.373zM60 11.03l-8.485 8.485-1.414-1.414L57.172 0H60v11.03zm0 5.656l-8.485 8.485-1.414-1.414 7.07-7.07L60 13.857v2.83zM60 22.344L47.658 34.686l-1.414-1.414 10.928-10.928L60 19.515v2.83zM60 28l-10.142 10.142-1.414-1.414 8.728-8.728L60 25.172v2.83zM60 33.657L52.343 41.314l-1.414-1.414 6.243-6.243L60 30.83v2.83zm0 5.657l-5.657 5.657-1.414-1.414 4.243-4.243L60 36.485v2.83zm0 5.657l-2.828 2.83-1.414-1.415.585-.585L60 42.14v2.83z' fill='%23d5d0c9' fill-opacity='.4' fill-rule='evenodd'/%3E%3C/svg%3E");
  padding: 1rem;
  border-radius: 10px;
}

.preview-header {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  margin-bottom: 0.75rem;
}

.preview-avatar {
  width: 32px;
  height: 32px;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
  font-size: 0.8rem;
}

.preview-header span {
  font-weight: 600;
  font-size: 0.9rem;
  color: #333;
}

.preview-bubble {
  background: white;
  padding: 0.75rem 1rem;
  border-radius: 0 10px 10px 10px;
  max-width: 85%;
  box-shadow: 0 1px 2px rgba(0, 0, 0, 0.1);
  position: relative;
}

.preview-titulo {
  margin: 0 0 0.5rem;
  font-size: 0.95rem;
}

.preview-mensaje {
  margin: 0;
  font-size: 0.9rem;
  line-height: 1.5;
  color: #333;
  white-space: pre-wrap;
  word-wrap: break-word;
}

.preview-time {
  display: block;
  text-align: right;
  font-size: 0.7rem;
  color: #999;
  margin-top: 0.5rem;
}

/* Form Actions */
.form-actions {
  margin-top: 1.5rem;
  text-align: center;
}

.btn-enviar {
  padding: 1rem 2rem;
  background: linear-gradient(135deg, #10b981 0%, #059669 100%);
  color: white;
  border: none;
  border-radius: 12px;
  font-size: 1rem;
  font-weight: 600;
  cursor: pointer;
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  transition: all 0.2s;
}

.btn-enviar:hover:not(:disabled) {
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(16, 185, 129, 0.4);
}

.btn-enviar:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

/* Estadísticas */
.stats-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 1rem;
}

.stat-card {
  background: white;
  padding: 1.25rem;
  border-radius: 12px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
  display: flex;
  align-items: center;
  gap: 1rem;
}

.stat-icon {
  width: 48px;
  height: 48px;
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.25rem;
  color: white;
}

.stat-icon.blue { background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%); }
.stat-icon.green { background: linear-gradient(135deg, #10b981 0%, #059669 100%); }
.stat-icon.purple { background: linear-gradient(135deg, #9333ea 0%, #7c3aed 100%); }
.stat-icon.orange { background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%); }

.stat-info {
  display: flex;
  flex-direction: column;
}

.stat-value {
  font-size: 1.5rem;
  font-weight: 700;
  color: #1a1a2e;
}

.stat-label {
  font-size: 0.85rem;
  color: #888;
}

/* Bottom Sheet Modal */
.bottom-sheet-overlay {
  position: fixed;
  inset: 0;
  background: rgba(0, 0, 0, 0.5);
  z-index: 1000;
  display: flex;
  align-items: flex-end;
  justify-content: center;
}

.bottom-sheet-content {
  background: white;
  border-radius: 24px 24px 0 0;
  width: 100%;
  max-width: 600px;
  max-height: 90vh;
  overflow-y: auto;
  box-shadow: 0 -10px 40px rgba(0, 0, 0, 0.2);
}

.bottom-sheet-handle {
  width: 40px;
  height: 4px;
  background: #ddd;
  border-radius: 2px;
  margin: 12px auto 8px;
}

.bottom-sheet-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 0.75rem 1.5rem 1rem;
  border-bottom: 1px solid #f0f0f0;
  position: sticky;
  top: 0;
  background: white;
  z-index: 10;
}

.bottom-sheet-header h2 {
  margin: 0;
  font-size: 1.15rem;
  display: flex;
  align-items: center;
  gap: 0.75rem;
}

.bottom-sheet-header h2 i {
  color: #667eea;
}

.btn-close {
  width: 36px;
  height: 36px;
  border-radius: 50%;
  border: none;
  background: #f0f0f0;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: background 0.2s;
}

.btn-close:hover {
  background: #e0e0e0;
}

.bottom-sheet-body {
  padding: 1.25rem 1.5rem;
}

.modal-descripcion {
  color: #666;
  margin: 0 0 1.25rem;
  font-size: 0.9rem;
}

.bottom-sheet-footer {
  display: flex;
  justify-content: flex-end;
  gap: 0.75rem;
  padding: 1rem 1.5rem;
  border-top: 1px solid #f0f0f0;
  background: #fafafa;
  position: sticky;
  bottom: 0;
}

/* Bottom Sheet Transition */
.bottom-sheet-enter-active,
.bottom-sheet-leave-active {
  transition: all 0.3s ease;
}

.bottom-sheet-enter-active .bottom-sheet-content,
.bottom-sheet-leave-active .bottom-sheet-content {
  transition: transform 0.3s ease;
}

.bottom-sheet-enter-from,
.bottom-sheet-leave-to {
  background: rgba(0, 0, 0, 0);
}

.bottom-sheet-enter-from .bottom-sheet-content,
.bottom-sheet-leave-to .bottom-sheet-content {
  transform: translateY(100%);
}

.variables-section {
  margin-bottom: 1.5rem;
}

.variables-section h4 {
  font-size: 0.9rem;
  font-weight: 600;
  color: #1a1a2e;
  margin: 0 0 0.75rem;
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.variables-list {
  display: flex;
  flex-wrap: wrap;
  gap: 0.5rem;
}

.variable-chip {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
  padding: 0.5rem 0.75rem;
  background: #f8f8f8;
  border-radius: 8px;
  cursor: pointer;
  transition: all 0.2s;
  border: 1px solid transparent;
}

.variable-chip:hover {
  background: rgba(102, 126, 234, 0.1);
  border-color: #667eea;
}

.variable-chip code {
  font-size: 0.75rem;
  color: #667eea;
  font-weight: 600;
}

.variable-chip span {
  font-size: 0.7rem;
  color: #888;
}

.editor-section {
  margin-bottom: 1.5rem;
}

.editor-section label {
  display: block;
  font-size: 0.9rem;
  font-weight: 600;
  color: #1a1a2e;
  margin-bottom: 0.5rem;
}

.editor-textarea {
  width: 100%;
  padding: 1rem;
  border: 2px solid #e0e0e0;
  border-radius: 10px;
  font-size: 0.9rem;
  font-family: 'Consolas', 'Monaco', monospace;
  resize: vertical;
  min-height: 200px;
  line-height: 1.6;
}

.editor-textarea:focus {
  outline: none;
  border-color: #667eea;
}

.preview-section-modal h4 {
  font-size: 0.9rem;
  font-weight: 600;
  color: #1a1a2e;
  margin: 0 0 0.75rem;
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.btn-secondary,
.btn-primary {
  padding: 0.75rem 1.25rem;
  border-radius: 10px;
  font-size: 0.9rem;
  font-weight: 600;
  cursor: pointer;
  display: flex;
  align-items: center;
  gap: 0.5rem;
  transition: all 0.2s;
}

.btn-secondary {
  background: white;
  border: 2px solid #e0e0e0;
  color: #666;
}

.btn-secondary:hover {
  border-color: #ccc;
  background: #f8f8f8;
}

.btn-primary {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  border: none;
  color: white;
}

.btn-primary:hover:not(:disabled) {
  transform: translateY(-1px);
  box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
}

.btn-primary:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

/* Responsive */
@media (max-width: 640px) {
  .admin-view {
    padding: 1rem;
  }
  
  .tabs-container {
    flex-direction: column;
  }
  
  .tab-btn {
    justify-content: flex-start;
  }
  
  .tipo-selector {
    flex-direction: column;
  }
  
  .stats-grid {
    grid-template-columns: 1fr;
  }
}
</style>

