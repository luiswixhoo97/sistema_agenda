<script setup lang="ts">
import { onMounted } from 'vue'
import { useAgendamiento } from '@/composables/useAgendamiento'

const { 
  store,
  mesActual,
  anioActual,
  nombreMesActual,
  diasDelMes,
  mesAnterior,
  mesSiguiente,
  seleccionarFecha,
  cargarDisponibilidadMes
} = useAgendamiento()

const diasSemana = ['L', 'M', 'X', 'J', 'V', 'S', 'D']

onMounted(() => {
  cargarDisponibilidadMes()
})
</script>

<template>
  <div class="paso-fecha">
    <!-- Header -->
    <div class="paso-header">
      <div class="header-icon">
        <i class="fa fa-calendar-alt"></i>
      </div>
      <h2>¿Cuándo te viene bien?</h2>
      <p>Selecciona fecha y hora</p>
    </div>

    <!-- Empleado badge -->
    <div v-if="store.empleadoSeleccionado" class="empleado-badge">
      <i class="fa fa-user-check"></i>
      <span>Con <strong>{{ store.empleadoSeleccionado.nombre }}</strong></span>
    </div>

    <!-- Calendario -->
    <div class="calendario-card">
      <!-- Header calendario -->
      <div class="calendario-header">
        <button class="nav-btn" @click="mesAnterior">
          <i class="fa fa-chevron-left"></i>
        </button>
        <h3>{{ nombreMesActual }} {{ anioActual }}</h3>
        <button class="nav-btn" @click="mesSiguiente">
          <i class="fa fa-chevron-right"></i>
        </button>
      </div>

      <!-- Días semana -->
      <div class="dias-semana">
        <span v-for="dia in diasSemana" :key="dia">{{ dia }}</span>
      </div>

      <!-- Días mes -->
      <div class="dias-grid" :class="{ 'loading': store.loading }">
        <div 
          v-for="(dia, index) in diasDelMes" 
          :key="index"
          class="dia-cell"
          :class="{ 
            'vacio': dia.dia === 0,
            'disponible': dia.disponible,
            'seleccionado': dia.fecha === store.fechaSeleccionada,
            'hoy': dia.esHoy,
            'pasado': dia.esPasado || !dia.disponible,
          }"
          @click="dia.disponible && seleccionarFecha(dia.fecha)"
        >
          <span v-if="dia.dia > 0">{{ dia.dia }}</span>
        </div>
      </div>

      <!-- Leyenda -->
      <div class="leyenda">
        <div class="leyenda-item">
          <span class="dot disponible"></span>
          Disponible
        </div>
        <div class="leyenda-item">
          <span class="dot"></span>
          No disponible
        </div>
      </div>
    </div>

    <!-- Horarios -->
    <div v-if="store.fechaSeleccionada" class="horarios-card">
      <h4>
        <i class="fa fa-clock"></i>
        Horarios disponibles
      </h4>

      <!-- Loading horarios -->
      <div v-if="store.loading" class="loading-horarios">
        <div class="spinner-sm"></div>
        <span>Cargando horarios...</span>
      </div>

      <!-- Mensaje -->
      <div v-else-if="store.slotsDisponibles?.mensaje" class="horarios-mensaje">
        <i class="fa fa-info-circle"></i>
        {{ store.slotsDisponibles.mensaje }}
      </div>

      <!-- Sin horarios -->
      <div v-else-if="store.slotsDisponibles?.slots.length === 0" class="horarios-mensaje">
        <i class="fa fa-calendar-times"></i>
        No hay horarios disponibles
      </div>

      <!-- Grid horarios -->
      <div v-else class="horarios-grid">
        <button 
          v-for="slot in store.slotsDisponibles?.slots" 
          :key="slot.hora"
          class="horario-btn"
          :class="{ 'selected': store.horaSeleccionada === slot.hora }"
          @click="store.seleccionarHora(slot.hora)"
        >
          {{ slot.hora }}
        </button>
      </div>
    </div>

    <!-- Resumen -->
    <div v-if="store.fechaSeleccionada && store.horaSeleccionada" class="resumen-card">
      <div class="resumen-item">
        <i class="fa fa-calendar"></i>
        <div>
          <span class="label">Fecha</span>
          <span class="value">{{ store.fechaSeleccionada }}</span>
        </div>
      </div>
      <div class="resumen-item">
        <i class="fa fa-clock"></i>
        <div>
          <span class="label">Hora</span>
          <span class="value">{{ store.horaSeleccionada }}</span>
        </div>
      </div>
      <div class="resumen-item">
        <i class="fa fa-hourglass-half"></i>
        <div>
          <span class="label">Duración</span>
          <span class="value">{{ store.slotsDisponibles?.duracion_total || 0 }} min</span>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.paso-fecha {
  padding: 16px;
  padding-bottom: 100px;
}

/* Header */
.paso-header {
  text-align: center;
  padding: 16px 0 20px;
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

/* Empleado badge */
.empleado-badge {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  padding: 10px 16px;
  background: linear-gradient(135deg, #ec407a, #d81b60);
  color: white;
  border-radius: 25px;
  font-size: 13px;
  margin-bottom: 16px;
}

.empleado-badge i {
  font-size: 12px;
}

/* Calendario card */
.calendario-card {
  background: var(--color-card);
  border-radius: 20px;
  padding: 20px;
  margin-bottom: 16px;
}

.calendario-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 20px;
}

.calendario-header h3 {
  font-size: 16px;
  font-weight: 700;
  color: var(--color-text);
  text-transform: capitalize;
  margin: 0;
}

.nav-btn {
  width: 36px;
  height: 36px;
  border-radius: 10px;
  background: rgba(0,0,0,0.05);
  border: none;
  color: var(--color-text);
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all 0.2s;
}

.theme-dark .nav-btn {
  background: rgba(255,255,255,0.1);
}

.nav-btn:hover {
  background: #ec407a;
  color: white;
}

/* Días semana */
.dias-semana {
  display: grid;
  grid-template-columns: repeat(7, 1fr);
  margin-bottom: 8px;
}

.dias-semana span {
  text-align: center;
  font-size: 11px;
  font-weight: 600;
  color: var(--color-text-secondary);
  padding: 8px 0;
}

/* Días grid */
.dias-grid {
  display: grid;
  grid-template-columns: repeat(7, 1fr);
  gap: 4px;
  transition: opacity 0.3s;
}

.dias-grid.loading {
  opacity: 0.5;
}

.dia-cell {
  aspect-ratio: 1;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 10px;
  font-size: 13px;
  font-weight: 500;
  color: var(--color-text);
  cursor: pointer;
  transition: all 0.2s;
}

.dia-cell.vacio {
  cursor: default;
}

.dia-cell.pasado {
  opacity: 0.3;
  cursor: not-allowed;
}

.dia-cell.disponible {
  background: rgba(76, 175, 80, 0.12);
  color: #2e7d32;
}

.theme-dark .dia-cell.disponible {
  background: rgba(76, 175, 80, 0.2);
  color: #81c784;
}

.dia-cell.disponible:hover {
  background: rgba(76, 175, 80, 0.25);
  transform: scale(1.1);
}

.dia-cell.hoy {
  border: 2px solid #ec407a;
}

.dia-cell.seleccionado {
  background: linear-gradient(135deg, #ec407a, #d81b60) !important;
  color: white !important;
  box-shadow: 0 4px 12px rgba(236, 64, 122, 0.4);
  transform: scale(1.05);
}

/* Leyenda */
.leyenda {
  display: flex;
  justify-content: center;
  gap: 20px;
  margin-top: 16px;
  padding-top: 16px;
  border-top: 1px solid var(--color-border);
}

.leyenda-item {
  display: flex;
  align-items: center;
  gap: 6px;
  font-size: 11px;
  color: var(--color-text-secondary);
}

.dot {
  width: 10px;
  height: 10px;
  border-radius: 50%;
  background: #ddd;
}

.dot.disponible {
  background: #4caf50;
}

/* Horarios card */
.horarios-card {
  background: var(--color-card);
  border-radius: 20px;
  padding: 20px;
  margin-bottom: 16px;
}

.horarios-card h4 {
  font-size: 15px;
  font-weight: 600;
  color: var(--color-text);
  margin: 0 0 16px;
  display: flex;
  align-items: center;
  gap: 8px;
}

.horarios-card h4 i {
  color: #ec407a;
}

/* Loading horarios */
.loading-horarios {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 10px;
  padding: 20px;
  color: var(--color-text-secondary);
  font-size: 13px;
}

.spinner-sm {
  width: 20px;
  height: 20px;
  border: 2px solid rgba(236,64,122,0.2);
  border-top-color: #ec407a;
  border-radius: 50%;
  animation: spin 0.8s linear infinite;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}

/* Horarios mensaje */
.horarios-mensaje {
  text-align: center;
  padding: 20px;
  color: var(--color-text-secondary);
  font-size: 13px;
}

.horarios-mensaje i {
  display: block;
  font-size: 30px;
  margin-bottom: 10px;
  opacity: 0.5;
}

/* Horarios grid */
.horarios-grid {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 8px;
}

.horario-btn {
  padding: 12px 8px;
  border: 2px solid var(--color-border);
  border-radius: 10px;
  background: transparent;
  font-size: 13px;
  font-weight: 600;
  color: var(--color-text);
  cursor: pointer;
  transition: all 0.2s;
}

.horario-btn:hover {
  border-color: #ec407a;
  color: #ec407a;
}

.horario-btn.selected {
  background: linear-gradient(135deg, #ec407a, #d81b60);
  border-color: transparent;
  color: white;
  box-shadow: 0 4px 12px rgba(236, 64, 122, 0.3);
}

/* Resumen card */
.resumen-card {
  background: var(--color-card);
  border-radius: 20px;
  padding: 16px;
  display: flex;
  justify-content: space-around;
  border: 2px solid rgba(76, 175, 80, 0.3);
}

.resumen-item {
  display: flex;
  align-items: center;
  gap: 10px;
  text-align: left;
}

.resumen-item i {
  width: 36px;
  height: 36px;
  border-radius: 10px;
  background: linear-gradient(135deg, rgba(236,64,122,0.1), rgba(236,64,122,0.2));
  display: flex;
  align-items: center;
  justify-content: center;
  color: #ec407a;
  font-size: 14px;
}

.resumen-item .label {
  display: block;
  font-size: 10px;
  color: var(--color-text-secondary);
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.resumen-item .value {
  display: block;
  font-size: 13px;
  font-weight: 600;
  color: var(--color-text);
}

@media (max-width: 360px) {
  .horarios-grid {
    grid-template-columns: repeat(3, 1fr);
  }
  
  .resumen-card {
    flex-direction: column;
    gap: 12px;
  }
}
</style>
