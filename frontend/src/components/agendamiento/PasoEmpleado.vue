<script setup lang="ts">
import { useAgendamiento } from '@/composables/useAgendamiento'

const { 
  empleados, 
  loadingCatalogo,
  seleccionarEmpleadoYAvanzar,
  store
} = useAgendamiento()
</script>

<template>
  <div class="paso-empleado">
    <!-- Header -->
    <div class="paso-header">
      <div class="header-icon">
        <i class="fa fa-user-tie"></i>
      </div>
      <h2>¿Con quién prefieres?</h2>
      <p>Selecciona un profesional</p>
    </div>

    <!-- Loading -->
    <div v-if="loadingCatalogo" class="loading-state">
      <div class="spinner"></div>
      <p>Cargando profesionales...</p>
    </div>

    <template v-else>
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
</style>
