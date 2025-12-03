<script setup lang="ts">
import { computed, watch, onMounted } from 'vue'
import { useAgendamiento } from '@/composables/useAgendamiento'

const { 
  empleados, 
  loadingCatalogo,
  seleccionarEmpleadoYAvanzar,
  store,
  cargarDisponibilidadMes
} = useAgendamiento()

// Verificar si hay más de un servicio seleccionado
const tieneMultiplesServicios = computed(() => store.serviciosSeleccionados.length > 1)

// Obtener empleados para un servicio específico
function empleadosParaServicio(servicioId: number) {
  return store.empleadosDisponiblesPorServicio[servicioId] || []
}

// Verificar si un empleado está seleccionado para un servicio
function empleadoSeleccionadoParaServicio(servicioId: number, empleadoId: number) {
  const asignacion = store.empleadoAsignadoAServicio(servicioId)
  return asignacion?.empleadoId === empleadoId
}

// Seleccionar empleado para un servicio específico
function seleccionarEmpleadoParaServicio(servicioId: number, empleado: any) {
  store.asignarEmpleadoAServicio(servicioId, empleado)
}

// Cargar empleados cuando se activa el modo múltiples
watch(() => store.modoMultiplesEmpleados, (activo) => {
  if (activo) {
    store.cargarEmpleadosPorServicio()
  }
}, { immediate: true })

// Continuar al siguiente paso en modo múltiples
async function continuarModoMultiples() {
  if (store.todosServiciosTienenEmpleado) {
    store.siguientePaso()
    // Cargar disponibilidad del mes para el calendario
    await cargarDisponibilidadMes()
  }
}

onMounted(() => {
  if (store.modoMultiplesEmpleados) {
    store.cargarEmpleadosPorServicio()
  }
})
</script>

<template>
  <div class="paso-empleado">
    <!-- Header -->
    <div class="paso-header">
      <div class="header-icon">
        <i class="fa fa-user-tie"></i>
      </div>
      <h2>¿Con quién prefieres?</h2>
      <p v-if="!store.modoMultiplesEmpleados">Selecciona un profesional</p>
      <p v-else>Selecciona un profesional para cada servicio</p>
    </div>

    <!-- Aviso cuando se auto-activa el modo múltiples -->
    <div v-if="store.modoMultiplesEmpleados && empleados.length === 0 && tieneMultiplesServicios" class="aviso-multiples">
      <i class="fa fa-info-circle"></i>
      <span>Los servicios seleccionados requieren diferentes profesionales. Asigna uno para cada servicio.</span>
    </div>

    <!-- Toggle modo múltiples empleados (solo si hay más de 1 servicio Y hay empleados que ofrecen todos) -->
    <div v-if="tieneMultiplesServicios && empleados.length > 0" class="modo-toggle">
      <button 
        class="modo-btn"
        :class="{ 'activo': !store.modoMultiplesEmpleados }"
        @click="store.modoMultiplesEmpleados && store.toggleModoMultiplesEmpleados()"
      >
        <i class="fa fa-user"></i>
        Un profesional
      </button>
      <button 
        class="modo-btn"
        :class="{ 'activo': store.modoMultiplesEmpleados }"
        @click="!store.modoMultiplesEmpleados && store.toggleModoMultiplesEmpleados()"
      >
        <i class="fa fa-users"></i>
        Profesional por servicio
      </button>
    </div>

    <!-- Loading -->
    <div v-if="loadingCatalogo" class="loading-state">
      <div class="spinner"></div>
      <p>Cargando profesionales...</p>
    </div>

    <!-- =============================================== -->
    <!-- MODO: Un empleado para todos los servicios -->
    <!-- =============================================== -->
    <template v-else-if="!store.modoMultiplesEmpleados">
      <!-- Sin empleados -->
      <div v-if="empleados.length === 0" class="empty-state">
        <i class="fa fa-user-slash"></i>
        <p>No hay profesionales disponibles para los servicios seleccionados</p>
        <button class="btn-volver" @click="store.pasoAnterior()">
          <i class="fa fa-arrow-left"></i>
          Modificar servicios
        </button>
      </div>

      <!-- Lista de empleados -->
      <div v-else class="empleados-grid">
        <div 
          v-for="empleado in empleados" 
          :key="empleado.id"
          class="empleado-card"
          @click="seleccionarEmpleadoYAvanzar(empleado)"
        >
          <!-- Avatar -->
          <div class="empleado-avatar">
            <img 
              v-if="empleado.foto" 
              :src="empleado.foto" 
              :alt="empleado.nombre"
            />
            <div v-else class="avatar-placeholder">
              {{ empleado.nombre.charAt(0).toUpperCase() }}
            </div>
          </div>

          <!-- Info -->
          <div class="empleado-info">
            <h4>{{ empleado.nombre }}</h4>
            
            <!-- Rating -->
            <div v-if="empleado.promedio_calificacion > 0" class="empleado-rating">
              <div class="stars">
                <i 
                  v-for="i in 5" 
                  :key="i" 
                  class="fa fa-star"
                  :class="{ 'active': i <= empleado.promedio_calificacion }"
                ></i>
              </div>
              <span>{{ empleado.promedio_calificacion.toFixed(1) }}</span>
            </div>

            <!-- Bio -->
            <p v-if="empleado.bio" class="empleado-bio">{{ empleado.bio }}</p>

            <!-- Especialidades -->
            <p v-if="empleado.especialidades" class="empleado-especialidad">
              <i class="fa fa-certificate"></i>
              {{ empleado.especialidades }}
            </p>

            <!-- Servicios -->
            <div class="empleado-servicios">
              <span 
                v-for="servicio in empleado.servicios.slice(0, 3)" 
                :key="servicio.id"
                class="servicio-tag"
              >
                {{ servicio.nombre }}
              </span>
              <span v-if="empleado.servicios.length > 3" class="mas">
                +{{ empleado.servicios.length - 3 }}
              </span>
            </div>
          </div>

          <!-- Arrow -->
          <div class="empleado-arrow">
            <i class="fa fa-chevron-right"></i>
          </div>
        </div>
      </div>
    </template>

    <!-- =============================================== -->
    <!-- MODO: Un empleado diferente por cada servicio -->
    <!-- =============================================== -->
    <template v-else>
      <div class="servicios-empleados-list">
        <div 
          v-for="servicio in store.serviciosSeleccionados" 
          :key="servicio.id"
          class="servicio-empleado-item"
        >
          <!-- Header del servicio -->
          <div class="servicio-header">
            <div class="servicio-icon">
              <i class="fa fa-spa"></i>
            </div>
            <div class="servicio-info">
              <h4>{{ servicio.nombre }}</h4>
              <span class="servicio-duracion">
                <i class="fa fa-clock"></i> {{ servicio.duracion }} min
              </span>
            </div>
            <div class="servicio-precio">${{ servicio.precio }}</div>
          </div>

          <!-- Empleado seleccionado -->
          <div 
            v-if="store.empleadoAsignadoAServicio(servicio.id)"
            class="empleado-seleccionado"
          >
            <div class="empleado-mini-avatar">
              <img 
                v-if="store.empleadoAsignadoAServicio(servicio.id)?.empleadoFoto" 
                :src="store.empleadoAsignadoAServicio(servicio.id)?.empleadoFoto" 
                :alt="store.empleadoAsignadoAServicio(servicio.id)?.empleadoNombre"
              />
              <div v-else class="avatar-placeholder-mini">
                {{ store.empleadoAsignadoAServicio(servicio.id)?.empleadoNombre.charAt(0).toUpperCase() }}
              </div>
            </div>
            <span class="empleado-nombre">
              {{ store.empleadoAsignadoAServicio(servicio.id)?.empleadoNombre }}
            </span>
            <i class="fa fa-check-circle check-icon"></i>
          </div>

          <!-- Lista de empleados disponibles -->
          <div class="empleados-mini-grid">
            <div 
              v-for="empleado in empleadosParaServicio(servicio.id)" 
              :key="empleado.id"
              class="empleado-mini-card"
              :class="{ 'seleccionado': empleadoSeleccionadoParaServicio(servicio.id, empleado.id) }"
              @click="seleccionarEmpleadoParaServicio(servicio.id, empleado)"
            >
              <div class="empleado-mini-avatar">
                <img 
                  v-if="empleado.foto" 
                  :src="empleado.foto" 
                  :alt="empleado.nombre"
                />
                <div v-else class="avatar-placeholder-mini">
                  {{ empleado.nombre.charAt(0).toUpperCase() }}
                </div>
              </div>
              <span class="empleado-mini-nombre">{{ empleado.nombre }}</span>
              <div v-if="empleado.promedio_calificacion > 0" class="empleado-mini-rating">
                <i class="fa fa-star"></i>
                {{ empleado.promedio_calificacion.toFixed(1) }}
              </div>
            </div>
            
            <!-- Sin empleados -->
            <div v-if="empleadosParaServicio(servicio.id).length === 0" class="no-empleados">
              <i class="fa fa-user-slash"></i>
              <span>Sin profesionales disponibles</span>
            </div>
          </div>
        </div>
      </div>

      <!-- Botón continuar (modo múltiples) -->
      <div class="footer-multiples">
        <div class="resumen-asignaciones">
          <span v-if="store.todosServiciosTienenEmpleado" class="completo">
            <i class="fa fa-check-circle"></i>
            Todos los servicios asignados
          </span>
          <span v-else class="pendiente">
            <i class="fa fa-exclamation-circle"></i>
            {{ store.empleadosPorServicio.length }} de {{ store.serviciosSeleccionados.length }} asignados
          </span>
        </div>
        <button 
          class="btn-continuar-multiples"
          :disabled="!store.todosServiciosTienenEmpleado"
          @click="continuarModoMultiples"
        >
          Continuar
          <i class="fa fa-chevron-right"></i>
        </button>
      </div>
    </template>
  </div>
</template>

<style scoped>
.paso-empleado {
  padding: 16px;
  padding-bottom: 100px;
}

/* Header */
.paso-header {
  text-align: center;
  padding: 16px 0 24px;
}

.header-icon {
  width: 70px;
  height: 70px;
  border-radius: 50%;
  background: linear-gradient(135deg, rgba(236,64,122,0.1), rgba(236,64,122,0.2));
  display: flex;
  align-items: center;
  justify-content: center;
  margin: 0 auto 16px;
}

.header-icon i {
  font-size: 32px;
  color: #ec407a;
}

.paso-header h2 {
  font-size: 22px;
  font-weight: 700;
  color: var(--color-text);
  margin: 0 0 8px;
}

.paso-header p {
  font-size: 14px;
  color: var(--color-text-secondary);
  margin: 0;
}

/* Aviso auto-múltiples */
.aviso-multiples {
  display: flex;
  align-items: flex-start;
  gap: 10px;
  padding: 14px 16px;
  background: linear-gradient(135deg, rgba(255, 152, 0, 0.1), rgba(255, 152, 0, 0.15));
  border: 1px solid rgba(255, 152, 0, 0.3);
  border-radius: 12px;
  margin-bottom: 16px;
  color: #e65100;
}

.theme-dark .aviso-multiples {
  color: #ffb74d;
}

.aviso-multiples i {
  font-size: 18px;
  margin-top: 2px;
  flex-shrink: 0;
}

.aviso-multiples span {
  font-size: 13px;
  line-height: 1.4;
}

/* Toggle modo */
.modo-toggle {
  display: flex;
  gap: 8px;
  padding: 8px;
  background: var(--color-card);
  border-radius: 16px;
  margin-bottom: 20px;
}

.modo-btn {
  flex: 1;
  padding: 12px 16px;
  border: none;
  border-radius: 12px;
  background: transparent;
  color: var(--color-text-secondary);
  font-size: 13px;
  font-weight: 600;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  transition: all 0.2s;
}

.modo-btn.activo {
  background: linear-gradient(135deg, #ec407a, #d81b60);
  color: white;
  box-shadow: 0 4px 12px rgba(236, 64, 122, 0.3);
}

.modo-btn:not(.activo):hover {
  background: rgba(0,0,0,0.05);
}

.theme-dark .modo-btn:not(.activo):hover {
  background: rgba(255,255,255,0.1);
}

/* Loading */
.loading-state {
  text-align: center;
  padding: 60px 20px;
}

.spinner {
  width: 40px;
  height: 40px;
  border: 3px solid rgba(236,64,122,0.2);
  border-top-color: #ec407a;
  border-radius: 50%;
  animation: spin 0.8s linear infinite;
  margin: 0 auto 16px;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}

.loading-state p {
  color: var(--color-text-secondary);
  font-size: 14px;
}

/* Empty state */
.empty-state {
  text-align: center;
  padding: 50px 20px;
}

.empty-state i {
  font-size: 50px;
  color: var(--color-border);
  margin-bottom: 16px;
}

.empty-state p {
  color: var(--color-text-secondary);
  font-size: 14px;
  margin-bottom: 20px;
}

.btn-volver {
  padding: 12px 24px;
  background: linear-gradient(135deg, #ec407a, #d81b60);
  color: white;
  border: none;
  border-radius: 12px;
  font-size: 14px;
  font-weight: 600;
  cursor: pointer;
  display: inline-flex;
  align-items: center;
  gap: 8px;
}

/* Empleados grid */
.empleados-grid {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.empleado-card {
  display: flex;
  align-items: flex-start;
  gap: 14px;
  padding: 16px;
  background: var(--color-card);
  border-radius: 16px;
  cursor: pointer;
  transition: all 0.2s;
  border: 2px solid transparent;
}

.empleado-card:hover {
  transform: translateX(4px);
  border-color: rgba(236,64,122,0.3);
  box-shadow: 0 4px 20px rgba(0,0,0,0.08);
}

.empleado-card:active {
  transform: scale(0.98);
}

/* Avatar */
.empleado-avatar {
  width: 60px;
  height: 60px;
  border-radius: 50%;
  overflow: hidden;
  flex-shrink: 0;
}

.empleado-avatar img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.avatar-placeholder {
  width: 100%;
  height: 100%;
  background: linear-gradient(135deg, #ec407a, #d81b60);
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
  font-size: 22px;
  font-weight: 700;
}

/* Info */
.empleado-info {
  flex: 1;
  min-width: 0;
}

.empleado-info h4 {
  font-size: 16px;
  font-weight: 600;
  color: var(--color-text);
  margin: 0 0 4px;
}

/* Rating */
.empleado-rating {
  display: flex;
  align-items: center;
  gap: 6px;
  margin-bottom: 6px;
}

.stars {
  display: flex;
  gap: 2px;
}

.stars i {
  font-size: 10px;
  color: #ddd;
}

.stars i.active {
  color: #ffc107;
}

.empleado-rating span {
  font-size: 12px;
  color: var(--color-text-secondary);
}

/* Bio */
.empleado-bio {
  font-size: 12px;
  color: var(--color-text-secondary);
  margin: 0 0 6px;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

/* Especialidad */
.empleado-especialidad {
  font-size: 11px;
  color: #ec407a;
  margin: 0 0 8px;
  display: flex;
  align-items: center;
  gap: 4px;
}

.empleado-especialidad i {
  font-size: 10px;
}

/* Servicios */
.empleado-servicios {
  display: flex;
  flex-wrap: wrap;
  gap: 4px;
  align-items: center;
}

.servicio-tag {
  font-size: 10px;
  padding: 3px 8px;
  background: rgba(0,0,0,0.05);
  border-radius: 8px;
  color: var(--color-text-secondary);
}

.theme-dark .servicio-tag {
  background: rgba(255,255,255,0.08);
}

.mas {
  font-size: 10px;
  color: var(--color-text-secondary);
}

/* Arrow */
.empleado-arrow {
  display: flex;
  align-items: center;
  color: #ccc;
  font-size: 14px;
  align-self: center;
}

/* ===================================== */
/* Estilos para modo múltiples empleados */
/* ===================================== */

.servicios-empleados-list {
  display: flex;
  flex-direction: column;
  gap: 16px;
}

.servicio-empleado-item {
  background: var(--color-card);
  border-radius: 16px;
  padding: 16px;
}

.servicio-header {
  display: flex;
  align-items: center;
  gap: 12px;
  padding-bottom: 12px;
  border-bottom: 1px solid var(--color-border);
  margin-bottom: 12px;
}

.servicio-icon {
  width: 44px;
  height: 44px;
  border-radius: 12px;
  background: linear-gradient(135deg, rgba(236,64,122,0.1), rgba(236,64,122,0.2));
  display: flex;
  align-items: center;
  justify-content: center;
  color: #ec407a;
  font-size: 18px;
}

.servicio-info {
  flex: 1;
}

.servicio-info h4 {
  font-size: 14px;
  font-weight: 600;
  color: var(--color-text);
  margin: 0 0 2px;
}

.servicio-duracion {
  font-size: 12px;
  color: var(--color-text-secondary);
}

.servicio-duracion i {
  font-size: 10px;
  margin-right: 4px;
}

.servicio-precio {
  font-size: 16px;
  font-weight: 700;
  color: #ec407a;
}

/* Empleado seleccionado */
.empleado-seleccionado {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 10px 14px;
  background: rgba(76, 175, 80, 0.1);
  border-radius: 10px;
  margin-bottom: 12px;
}

.empleado-seleccionado .empleado-nombre {
  flex: 1;
  font-size: 13px;
  font-weight: 600;
  color: #2e7d32;
}

.check-icon {
  color: #4caf50;
  font-size: 16px;
}

/* Mini grid de empleados */
.empleados-mini-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(100px, 1fr));
  gap: 10px;
}

.empleado-mini-card {
  display: flex;
  flex-direction: column;
  align-items: center;
  padding: 12px 8px;
  background: rgba(0,0,0,0.03);
  border-radius: 12px;
  cursor: pointer;
  transition: all 0.2s;
  border: 2px solid transparent;
}

.theme-dark .empleado-mini-card {
  background: rgba(255,255,255,0.05);
}

.empleado-mini-card:hover {
  background: rgba(236,64,122,0.08);
  border-color: rgba(236,64,122,0.3);
}

.empleado-mini-card.seleccionado {
  background: linear-gradient(135deg, rgba(236,64,122,0.1), rgba(236,64,122,0.15));
  border-color: #ec407a;
}

.empleado-mini-avatar {
  width: 44px;
  height: 44px;
  border-radius: 50%;
  overflow: hidden;
  margin-bottom: 6px;
}

.empleado-mini-avatar img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.avatar-placeholder-mini {
  width: 100%;
  height: 100%;
  background: linear-gradient(135deg, #ec407a, #d81b60);
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
  font-size: 16px;
  font-weight: 700;
}

.empleado-mini-nombre {
  font-size: 12px;
  font-weight: 600;
  color: var(--color-text);
  text-align: center;
  line-height: 1.2;
}

.empleado-mini-rating {
  display: flex;
  align-items: center;
  gap: 3px;
  font-size: 10px;
  color: #ffc107;
  margin-top: 4px;
}

.empleado-mini-rating i {
  font-size: 9px;
}

.no-empleados {
  grid-column: 1 / -1;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  padding: 20px;
  color: var(--color-text-secondary);
  font-size: 13px;
}

.no-empleados i {
  font-size: 18px;
  opacity: 0.5;
}

/* Footer múltiples */
.footer-multiples {
  position: fixed;
  bottom: 0;
  left: 0;
  right: 0;
  padding: 12px 16px;
  background: var(--color-card);
  border-top: 1px solid var(--color-border);
  display: flex;
  align-items: center;
  gap: 12px;
  z-index: 50;
}

.resumen-asignaciones {
  flex: 1;
}

.resumen-asignaciones span {
  display: flex;
  align-items: center;
  gap: 6px;
  font-size: 13px;
}

.resumen-asignaciones .completo {
  color: #4caf50;
}

.resumen-asignaciones .pendiente {
  color: #ff9800;
}

.btn-continuar-multiples {
  padding: 12px 24px;
  background: linear-gradient(135deg, #ec407a, #d81b60);
  color: white;
  border: none;
  border-radius: 12px;
  font-size: 14px;
  font-weight: 600;
  cursor: pointer;
  display: flex;
  align-items: center;
  gap: 8px;
  transition: all 0.2s;
}

.btn-continuar-multiples:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.btn-continuar-multiples:not(:disabled):hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(236, 64, 122, 0.4);
}
</style>
