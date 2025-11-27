<script setup lang="ts">
import { useAgendamiento } from '@/composables/useAgendamiento'

const { 
  categorias, 
  serviciosFiltrados, 
  categoriaActiva,
  loadingCatalogo,
  seleccionarCategoria,
  toggleServicio,
  servicioSeleccionado,
  store
} = useAgendamiento()
</script>

<template>
  <div class="paso-servicios">
    <!-- Header -->
    <div class="paso-header">
      <div class="header-icon">
        <i class="fa fa-spa"></i>
      </div>
      <h2>¿Qué servicio deseas?</h2>
      <p>Selecciona uno o más servicios</p>
    </div>

    <!-- Loading -->
    <div v-if="loadingCatalogo" class="loading-state">
      <div class="spinner"></div>
      <p>Cargando servicios...</p>
    </div>

    <template v-else>
      <!-- Categorías -->
      <div class="categorias-scroll">
        <button 
          class="categoria-chip"
          :class="{ 'active': categoriaActiva === null }"
          @click="seleccionarCategoria(null)"
        >
          <i class="fa fa-th-large"></i>
          Todos
        </button>
        <button 
          v-for="cat in categorias" 
          :key="cat.id"
          class="categoria-chip"
          :class="{ 'active': categoriaActiva === cat.id }"
          @click="seleccionarCategoria(cat.id)"
        >
          {{ cat.nombre }}
        </button>
      </div>

      <!-- Servicios Grid -->
      <div class="servicios-grid">
        <div 
          v-for="servicio in serviciosFiltrados" 
          :key="servicio.id"
          class="servicio-card"
          :class="{ 'selected': servicioSeleccionado(servicio.id) }"
          @click="toggleServicio(servicio)"
        >
          <div class="servicio-check">
            <i v-if="servicioSeleccionado(servicio.id)" class="fa fa-check"></i>
          </div>
          
          <div class="servicio-content">
            <h4>{{ servicio.nombre }}</h4>
            <p v-if="servicio.descripcion" class="servicio-desc">{{ servicio.descripcion }}</p>
            <div class="servicio-meta">
              <span class="servicio-duracion">
                <i class="fa fa-clock"></i>
                {{ servicio.duracion_texto }}
              </span>
              <span class="servicio-categoria">
                {{ servicio.categoria?.nombre }}
              </span>
            </div>
          </div>
          
          <div class="servicio-precio">
            {{ servicio.precio_texto }}
          </div>
        </div>
      </div>

      <!-- Sin resultados -->
      <div v-if="serviciosFiltrados.length === 0" class="empty-state">
        <i class="fa fa-search"></i>
        <p>No hay servicios en esta categoría</p>
      </div>

      <!-- Resumen selección -->
      <div v-if="store.serviciosSeleccionados.length > 0" class="seleccion-footer">
        <div class="seleccion-info">
          <span class="seleccion-count">
            {{ store.serviciosSeleccionados.length }} 
            servicio{{ store.serviciosSeleccionados.length > 1 ? 's' : '' }}
          </span>
          <span class="seleccion-total">
            ${{ Number(store.totalPrecio || 0).toFixed(0) }}
          </span>
        </div>
        <div class="seleccion-tags">
          <span 
            v-for="s in store.serviciosSeleccionados" 
            :key="s.id"
            class="tag"
          >
            {{ s.nombre }}
            <button @click.stop="store.quitarServicio(s.id)">
              <i class="fa fa-times"></i>
            </button>
          </span>
        </div>
      </div>
    </template>
  </div>
</template>

<style scoped>
.paso-servicios {
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

/* Categorías */
.categorias-scroll {
  display: flex;
  gap: 8px;
  overflow-x: auto;
  padding: 4px 0 16px;
  margin: 0 -16px;
  padding-left: 16px;
  padding-right: 16px;
  scrollbar-width: none;
  -webkit-overflow-scrolling: touch;
}

.categorias-scroll::-webkit-scrollbar {
  display: none;
}

.categoria-chip {
  flex-shrink: 0;
  padding: 10px 18px;
  border-radius: 25px;
  background: var(--color-card);
  border: 2px solid var(--color-border);
  color: var(--color-text);
  font-size: 13px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s;
  display: flex;
  align-items: center;
  gap: 6px;
}

.categoria-chip i {
  font-size: 12px;
}

.categoria-chip:hover {
  border-color: #ec407a;
}

.categoria-chip.active {
  background: linear-gradient(135deg, #ec407a, #d81b60);
  border-color: transparent;
  color: white;
}

/* Servicios Grid */
.servicios-grid {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.servicio-card {
  display: flex;
  align-items: flex-start;
  gap: 12px;
  padding: 16px;
  background: var(--color-card);
  border-radius: 16px;
  border: 2px solid transparent;
  cursor: pointer;
  transition: all 0.2s;
  position: relative;
  overflow: hidden;
}

.servicio-card::before {
  content: '';
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: linear-gradient(135deg, rgba(236,64,122,0.05), rgba(236,64,122,0.1));
  opacity: 0;
  transition: opacity 0.2s;
}

.servicio-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 20px rgba(0,0,0,0.08);
}

.servicio-card.selected {
  border-color: #ec407a;
  background: linear-gradient(135deg, rgba(236,64,122,0.05), rgba(236,64,122,0.08));
}

.servicio-card.selected::before {
  opacity: 1;
}

/* Check circle */
.servicio-check {
  width: 28px;
  height: 28px;
  border-radius: 50%;
  border: 2px solid var(--color-border);
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
  transition: all 0.2s;
  margin-top: 2px;
}

.servicio-card.selected .servicio-check {
  background: linear-gradient(135deg, #ec407a, #d81b60);
  border-color: transparent;
}

.servicio-check i {
  font-size: 12px;
  color: white;
}

/* Content */
.servicio-content {
  flex: 1;
  min-width: 0;
}

.servicio-content h4 {
  font-size: 15px;
  font-weight: 600;
  color: var(--color-text);
  margin: 0 0 4px;
  line-height: 1.3;
}

.servicio-desc {
  font-size: 12px;
  color: var(--color-text-secondary);
  margin: 0 0 8px;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

.servicio-meta {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
  align-items: center;
}

.servicio-duracion {
  display: inline-flex;
  align-items: center;
  gap: 4px;
  font-size: 12px;
  color: var(--color-text-secondary);
}

.servicio-duracion i {
  font-size: 10px;
  color: #ec407a;
}

.servicio-categoria {
  font-size: 11px;
  padding: 3px 8px;
  background: rgba(0,0,0,0.05);
  border-radius: 10px;
  color: var(--color-text-secondary);
}

.theme-dark .servicio-categoria {
  background: rgba(255,255,255,0.08);
}

/* Precio */
.servicio-precio {
  font-size: 17px;
  font-weight: 700;
  color: #ec407a;
  flex-shrink: 0;
  text-align: right;
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
}

/* Selección footer */
.seleccion-footer {
  position: fixed;
  bottom: 70px;
  left: 0;
  right: 0;
  background: var(--color-card);
  padding: 12px 16px;
  border-top: 1px solid var(--color-border);
  z-index: 40;
}

.seleccion-info {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 10px;
}

.seleccion-count {
  font-size: 13px;
  color: var(--color-text-secondary);
}

.seleccion-total {
  font-size: 18px;
  font-weight: 700;
  color: #ec407a;
}

.seleccion-tags {
  display: flex;
  flex-wrap: wrap;
  gap: 6px;
}

.tag {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 6px 10px;
  background: linear-gradient(135deg, #ec407a, #d81b60);
  color: white;
  border-radius: 20px;
  font-size: 12px;
  font-weight: 500;
}

.tag button {
  background: none;
  border: none;
  color: white;
  padding: 0;
  cursor: pointer;
  opacity: 0.8;
  font-size: 10px;
  display: flex;
  align-items: center;
  justify-content: center;
}

.tag button:hover {
  opacity: 1;
}
</style>
