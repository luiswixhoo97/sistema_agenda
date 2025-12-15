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
        <!-- Promoción -->
        <div 
          v-for="servicio in serviciosFiltrados" 
          :key="servicio.id"
          class="servicio-card"
          :class="{ 
            'selected': servicio.es_promocion ? store.promocionSeleccionada === servicio.promocion_id : servicioSeleccionado(servicio.id),
            'promocion-card': servicio.es_promocion
          }"
          @click="toggleServicio(servicio)"
        >
          <div class="servicio-check">
            <i v-if="servicio.es_promocion ? store.promocionSeleccionada === servicio.promocion_id : servicioSeleccionado(servicio.id)" class="fa fa-check"></i>
            <i v-else-if="servicio.es_promocion" class="fa fa-tag"></i>
          </div>
          
          <div class="servicio-content">
            <div v-if="servicio.es_promocion" class="promocion-badge">
              <span>{{ servicio.descuento }}</span>
            </div>
            <h4>{{ servicio.nombre }}</h4>
            <p v-if="servicio.descripcion" class="servicio-desc">{{ servicio.descripcion }}</p>
            
            <!-- Servicios incluidos en la promoción -->
            <div v-if="servicio.es_promocion && servicio.servicios_incluidos && servicio.servicios_incluidos.length > 0" class="promocion-servicios">
              <div class="promocion-servicios-label">
                <i class="fa fa-cut"></i>
                <span>Incluye {{ servicio.servicios_incluidos.length }} servicio(s):</span>
              </div>
              <div class="promocion-servicios-list">
                <span v-for="serv in servicio.servicios_incluidos" :key="serv.id" class="servicio-tag">
                  {{ serv.nombre }}
                </span>
              </div>
            </div>
            
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
            <span v-if="servicio.es_promocion && servicio.precio_con_descuento" class="precio-con-descuento">
              <span class="precio-original">{{ servicio.precio_texto }}</span>
              <span class="precio-final">{{ servicio.precio_con_descuento_texto }}</span>
            </span>
            <span v-else>{{ servicio.precio_texto }}</span>
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
  padding: 20px 16px;
  padding-bottom: 100px;
  font-family: -apple-system, BlinkMacSystemFont, 'SF Pro Display', 'SF Pro Text', 'Helvetica Neue', sans-serif;
  -webkit-font-smoothing: antialiased;
  -moz-osx-font-smoothing: grayscale;
}

/* Header */
.paso-header {
  text-align: center;
  padding: 24px 0 32px;
}

.header-icon {
  width: 64px;
  height: 64px;
  border-radius: 16px;
  background: #007aff;
  display: flex;
  align-items: center;
  justify-content: center;
  margin: 0 auto 20px;
  box-shadow: 0 2px 8px rgba(0, 122, 255, 0.2);
}

.header-icon i {
  font-size: 28px;
  color: white;
}

.paso-header h2 {
  font-size: 28px;
  font-weight: 600;
  color: #1d1d1f;
  margin: 0 0 8px;
  letter-spacing: -0.5px;
}

.theme-dark .paso-header h2 {
  color: #f5f5f7;
}

.paso-header p {
  font-size: 17px;
  color: #86868b;
  margin: 0;
  font-weight: 400;
}

.theme-dark .paso-header p {
  color: #a1a1a6;
}

/* Loading */
.loading-state {
  text-align: center;
  padding: 60px 20px;
}

.spinner {
  width: 40px;
  height: 40px;
  border: 3px solid rgba(0, 122, 255, 0.2);
  border-top-color: #007aff;
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
  border-radius: 20px;
  background: rgba(255, 255, 255, 0.8);
  backdrop-filter: saturate(180%) blur(20px);
  -webkit-backdrop-filter: saturate(180%) blur(20px);
  border: 0.5px solid rgba(0, 0, 0, 0.1);
  color: #1d1d1f;
  font-size: 15px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
  display: flex;
  align-items: center;
  gap: 6px;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
}

.theme-dark .categoria-chip {
  background: rgba(44, 44, 46, 0.8);
  border-color: rgba(255, 255, 255, 0.1);
  color: #f5f5f7;
}

.categoria-chip i {
  font-size: 12px;
}

.categoria-chip:hover {
  border-color: #007aff;
  background: rgba(0, 122, 255, 0.05);
}

.theme-dark .categoria-chip:hover {
  background: rgba(0, 122, 255, 0.1);
}

.categoria-chip.active {
  background: #007aff;
  border-color: #007aff;
  color: white;
  box-shadow: 0 2px 8px rgba(0, 122, 255, 0.3);
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
  padding: 18px;
  background: rgba(255, 255, 255, 0.8);
  backdrop-filter: saturate(180%) blur(20px);
  -webkit-backdrop-filter: saturate(180%) blur(20px);
  border-radius: 16px;
  border: 0.5px solid rgba(0, 0, 0, 0.08);
  cursor: pointer;
  transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
  position: relative;
  overflow: hidden;
  box-shadow: 
    0 2px 8px rgba(0, 0, 0, 0.04),
    0 0 0 0.5px rgba(0, 0, 0, 0.06);
}

.theme-dark .servicio-card {
  background: rgba(28, 28, 30, 0.8);
  border-color: rgba(255, 255, 255, 0.1);
  box-shadow: 
    0 2px 8px rgba(0, 0, 0, 0.3),
    0 0 0 0.5px rgba(255, 255, 255, 0.05);
}

.servicio-card::before {
  content: '';
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 122, 255, 0.05);
  opacity: 0;
  transition: opacity 0.2s;
}

.servicio-card:hover {
  transform: translateY(-1px);
  border-color: rgba(0, 122, 255, 0.2);
  box-shadow: 
    0 4px 16px rgba(0, 0, 0, 0.08),
    0 0 0 0.5px rgba(0, 122, 255, 0.15);
}

.servicio-card.selected {
  border-color: #007aff;
  background: rgba(0, 122, 255, 0.08);
  box-shadow: 
    0 2px 8px rgba(0, 122, 255, 0.2),
    0 0 0 1px rgba(0, 122, 255, 0.3);
}

.theme-dark .servicio-card.selected {
  background: rgba(0, 122, 255, 0.15);
}

.servicio-card.selected::before {
  opacity: 1;
}

/* Check circle */
.servicio-check {
  width: 28px;
  height: 28px;
  border-radius: 8px;
  border: 1px solid #d1d1d6;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
  transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
  margin-top: 2px;
  background: rgba(255, 255, 255, 1);
}

.theme-dark .servicio-check {
  border-color: rgba(255, 255, 255, 0.2);
  background: rgba(44, 44, 46, 1);
}

.servicio-card.selected .servicio-check {
  background: #007aff;
  border-color: #007aff;
}

.servicio-check i {
  font-size: 14px;
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
  font-size: 13px;
  color: #86868b;
  margin: 0 0 8px;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
  line-height: 1.4;
}

.theme-dark .servicio-desc {
  color: #a1a1a6;
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
  color: #007aff;
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
  font-size: 18px;
  font-weight: 600;
  color: #007aff;
  flex-shrink: 0;
  text-align: right;
  letter-spacing: -0.2px;
}

/* Estilos para promociones */
.promocion-card {
  border: 1px solid rgba(0, 122, 255, 0.3);
  background: rgba(0, 122, 255, 0.05);
}

.theme-dark .promocion-card {
  background: rgba(0, 122, 255, 0.1);
}

.promocion-card.selected {
  border-color: #007aff;
  background: rgba(0, 122, 255, 0.1);
}

.theme-dark .promocion-card.selected {
  background: rgba(0, 122, 255, 0.15);
}

.promocion-badge {
  display: inline-block;
  background: #007aff;
  color: white;
  padding: 5px 12px;
  border-radius: 12px;
  font-size: 12px;
  font-weight: 700;
  margin-bottom: 8px;
  text-transform: uppercase;
  letter-spacing: 0.3px;
  box-shadow: 0 1px 3px rgba(0, 122, 255, 0.3);
}

.promocion-servicios {
  margin: 12px 0;
  padding: 12px;
  background: rgba(0, 122, 255, 0.05);
  border-radius: 10px;
  border-left: 2px solid #007aff;
}

.theme-dark .promocion-servicios {
  background: rgba(0, 122, 255, 0.08);
}

.promocion-servicios-label {
  display: flex;
  align-items: center;
  gap: 6px;
  font-size: 13px;
  font-weight: 600;
  color: #1d1d1f;
  margin-bottom: 8px;
}

.theme-dark .promocion-servicios-label {
  color: #f5f5f7;
}

.promocion-servicios-label i {
  color: #007aff;
  font-size: 12px;
}

.promocion-servicios-list {
  display: flex;
  flex-wrap: wrap;
  gap: 6px;
}

.servicio-tag {
  display: inline-block;
  padding: 5px 10px;
  background: rgba(0, 122, 255, 0.1);
  border-radius: 8px;
  font-size: 12px;
  color: #1d1d1f;
  border: 0.5px solid rgba(0, 122, 255, 0.2);
  font-weight: 500;
}

.theme-dark .servicio-tag {
  background: rgba(0, 122, 255, 0.15);
  color: #f5f5f7;
  border-color: rgba(0, 122, 255, 0.3);
}

.precio-con-descuento {
  display: flex;
  flex-direction: column;
  align-items: flex-end;
  gap: 2px;
}

.precio-original {
  font-size: 13px;
  color: var(--color-text-secondary);
  text-decoration: line-through;
  font-weight: 400;
}

.precio-final {
  font-size: 18px;
  color: #007aff;
  font-weight: 600;
  letter-spacing: -0.2px;
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
  background: rgba(255, 255, 255, 0.95);
  backdrop-filter: saturate(180%) blur(20px);
  -webkit-backdrop-filter: saturate(180%) blur(20px);
  padding: 16px;
  border-top: 0.5px solid rgba(0, 0, 0, 0.1);
  z-index: 40;
  box-shadow: 0 -4px 16px rgba(0, 0, 0, 0.04);
}

.theme-dark .seleccion-footer {
  background: rgba(28, 28, 30, 0.95);
  border-top-color: rgba(255, 255, 255, 0.1);
  box-shadow: 0 -4px 16px rgba(0, 0, 0, 0.3);
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
  font-size: 20px;
  font-weight: 600;
  color: #007aff;
  letter-spacing: -0.3px;
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
  padding: 6px 12px;
  background: #007aff;
  color: white;
  border-radius: 20px;
  font-size: 13px;
  font-weight: 500;
  box-shadow: 0 1px 3px rgba(0, 122, 255, 0.3);
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
