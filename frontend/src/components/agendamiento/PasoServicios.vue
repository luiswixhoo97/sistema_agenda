<script setup lang="ts">
import { computed } from 'vue'
import { useAgendamiento } from '@/composables/useAgendamiento'
import AppIcons from './AppIcons.vue'

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

function calcularAhorro(servicio: any): string {
  if (!servicio.es_promocion || !servicio.precio_con_descuento) return '0'
  const ahorro = servicio.precio - servicio.precio_con_descuento
  return ahorro.toFixed(0)
}

function calcularPorcentajeAhorro(servicio: any): number {
  if (!servicio.es_promocion || !servicio.precio_con_descuento || servicio.precio === 0) return 0
  const ahorro = servicio.precio - servicio.precio_con_descuento
  return Math.round((ahorro / servicio.precio) * 100)
}
</script>

<template>
  <div class="paso-servicios">
    <!-- Header -->
    <div class="page-header">
      <div class="header-icon">
        <AppIcons name="spa" :size="20" />
      </div>
      <h1>¿Qué servicio deseas?</h1>
      <p>Selecciona uno o más servicios</p>
    </div>

    <!-- Loading -->
    <div v-if="loadingCatalogo" class="loading-state">
      <AppIcons name="loader" :size="32" class="spinner" />
      <p>Cargando servicios...</p>
    </div>

    <template v-else>
      <!-- Categories -->
      <div class="categories">
        <button 
          class="category"
          :class="{ 'active': categoriaActiva === null }"
          @click="seleccionarCategoria(null)"
        >
          <AppIcons name="grid" :size="14" />
          Todos
        </button>
        <button 
          v-for="cat in categorias" 
          :key="cat.id"
          class="category"
          :class="{ 'active': categoriaActiva === cat.id }"
          @click="seleccionarCategoria(cat.id)"
        >
          {{ cat.nombre }}
        </button>
      </div>

      <!-- Services List -->
      <div class="services-list">
        <div 
          v-for="servicio in serviciosFiltrados" 
          :key="servicio.id"
          class="service-card"
          :class="{ 
            'selected': servicio.es_promocion ? store.promocionSeleccionada === servicio.promocion_id : servicioSeleccionado(servicio.id),
            'promo': servicio.es_promocion
          }"
          @click="toggleServicio(servicio)"
          role="button"
          tabindex="0"
        >
          <!-- Promo Badge -->
          <div v-if="servicio.es_promocion" class="promo-badge">
            <AppIcons name="tag" :size="12" />
            {{ servicio.descuento }}
          </div>

          <!-- Check -->
          <div class="service-check">
            <Transition name="check" mode="out-in">
              <div v-if="servicio.es_promocion ? store.promocionSeleccionada === servicio.promocion_id : servicioSeleccionado(servicio.id)" class="check-active">
                <AppIcons name="check" :size="14" />
              </div>
              <div v-else class="check-empty"></div>
            </Transition>
          </div>

          <!-- Content -->
          <div class="service-content">
            <h3>{{ servicio.nombre }}</h3>
            <p v-if="servicio.descripcion" class="service-desc">{{ servicio.descripcion }}</p>
            
            <!-- Included Services -->
            <div v-if="servicio.es_promocion && servicio.servicios_incluidos?.length" class="included-services">
              <span class="included-label">Incluye:</span>
              <div class="included-tags">
                <span v-for="serv in servicio.servicios_incluidos" :key="serv.id" class="included-tag">
                  {{ serv.nombre }}
                </span>
              </div>
            </div>

            <!-- Savings Badge -->
            <div v-if="servicio.es_promocion && calcularAhorro(servicio) !== '0'" class="savings-badge">
              <AppIcons name="piggy-bank" :size="14" />
              Ahorras ${{ calcularAhorro(servicio) }} ({{ calcularPorcentajeAhorro(servicio) }}%)
            </div>

            <!-- Meta -->
            <div class="service-meta">
              <span class="duration">
                <AppIcons name="clock" :size="12" />
                {{ servicio.duracion_texto }}
              </span>
              <span v-if="!servicio.es_promocion && servicio.categoria?.nombre" class="category-tag">
                {{ servicio.categoria.nombre }}
              </span>
            </div>
          </div>

          <!-- Price -->
          <div class="service-price">
            <template v-if="servicio.es_promocion && servicio.precio_con_descuento">
              <span class="price-original">${{ Number(servicio.precio || 0).toFixed(0) }}</span>
              <span class="price-final">${{ Number(servicio.precio_con_descuento || 0).toFixed(0) }}</span>
            </template>
            <template v-else>
              <span class="price-normal">${{ Number(servicio.precio || 0).toFixed(0) }}</span>
            </template>
          </div>
        </div>
      </div>

      <!-- Empty State -->
      <div v-if="serviciosFiltrados.length === 0" class="empty-state">
        <AppIcons name="search" :size="40" />
        <p>No hay servicios en esta categoría</p>
      </div>

      <!-- Selection Summary -->
      <Transition name="slide-up">
        <div v-if="store.serviciosSeleccionados.length > 0" class="selection-summary">
        <div class="summary-info">
          <span class="summary-count">
            {{ store.serviciosSeleccionados.length }} 
            servicio{{ store.serviciosSeleccionados.length > 1 ? 's' : '' }}
          </span>
          <span class="summary-total">
            ${{ Number(store.totalPrecio || 0).toFixed(0) }}
          </span>
        </div>
          <div class="summary-tags">
            <span v-for="s in store.serviciosSeleccionados" :key="s.id" class="summary-tag">
              {{ s.nombre }}
              <button @click.stop="store.quitarServicio(s.id)" class="tag-remove">
                <AppIcons name="x" :size="12" />
              </button>
            </span>
          </div>
        </div>
      </Transition>
    </template>
  </div>
</template>

<style scoped>
.paso-servicios {
  padding: 20px 16px;
  padding-bottom: 180px;
  font-family: -apple-system, BlinkMacSystemFont, 'SF Pro Display', system-ui, sans-serif;
  -webkit-font-smoothing: antialiased;
}

/* Header */
.page-header {
  padding: 20px 16px 24px;
  text-align: center;
  background: rgba(255, 255, 255, 0.6);
  backdrop-filter: blur(20px);
  -webkit-backdrop-filter: blur(20px);
  border-radius: 20px;
  margin-bottom: 20px;
  border: 1px solid rgba(0, 0, 0, 0.06);
  box-shadow: 0 2px 12px rgba(0, 0, 0, 0.04);
}

.theme-dark .page-header {
  background: rgba(28, 28, 30, 0.6);
  border-color: rgba(255, 255, 255, 0.08);
  box-shadow: 0 2px 12px rgba(0, 0, 0, 0.2);
}

.header-icon {
  width: 44px;
  height: 44px;
  border-radius: 12px;
  background: rgba(0, 122, 255, 0.08);
  display: flex;
  align-items: center;
  justify-content: center;
  margin: 0 auto 14px;
  color: #007aff;
  border: 1px solid rgba(0, 122, 255, 0.12);
}

.theme-dark .header-icon {
  background: rgba(0, 122, 255, 0.12);
  border-color: rgba(0, 122, 255, 0.2);
}

.page-header h1 {
  font-size: 22px;
  font-weight: 600;
  color: #1d1d1f;
  margin: 0 0 6px;
  letter-spacing: -0.02em;
  line-height: 1.3;
}

.theme-dark .page-header h1 {
  color: #f5f5f7;
}

.page-header p {
  font-size: 15px;
  color: #86868b;
  margin: 0;
  letter-spacing: 0;
  line-height: 1.4;
}

.theme-dark .page-header p {
  color: #98989d;
}

/* Loading */
.loading-state {
  text-align: center;
  padding: 60px 20px;
}

.loading-state .spinner {
  color: #007aff;
  margin-bottom: 16px;
}

.loading-state p {
  color: #86868b;
  font-size: 15px;
}

/* Categories */
.categories {
  display: flex;
  gap: 8px;
  overflow-x: auto;
  padding: 4px 0 20px;
  margin: 0 -16px;
  padding-left: 16px;
  padding-right: 16px;
  scrollbar-width: none;
  -webkit-overflow-scrolling: touch;
}

.categories::-webkit-scrollbar {
  display: none;
}

.category {
  flex-shrink: 0;
  padding: 10px 16px;
  border-radius: 12px;
  background: rgba(255, 255, 255, 0.9);
  backdrop-filter: blur(10px);
  border: 1px solid rgba(0,0,0,0.06);
  color: #1d1d1f;
  font-size: 14px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s;
  display: flex;
  align-items: center;
  gap: 6px;
  font-family: inherit;
}

.theme-dark .category {
  background: rgba(44,44,46,0.9);
  border-color: rgba(255,255,255,0.08);
  color: #f5f5f7;
}

.category:hover {
  border-color: #007aff;
}

.category.active {
  background: #007aff;
  border-color: #007aff;
  color: white;
}

/* Services List */
.services-list {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.service-card {
  display: flex;
  align-items: flex-start;
  gap: 12px;
  padding: 16px;
  background: rgba(255, 255, 255, 0.95);
  backdrop-filter: blur(20px);
  border-radius: 16px;
  border: 1px solid rgba(0,0,0,0.04);
  cursor: pointer;
  transition: all 0.2s;
  position: relative;
}

.theme-dark .service-card {
  background: rgba(28, 28, 30, 0.95);
  border-color: rgba(255,255,255,0.06);
}

.service-card:hover {
  border-color: rgba(0, 122, 255, 0.2);
  transform: translateY(-1px);
  box-shadow: 0 4px 20px rgba(0,0,0,0.06);
}

.service-card.selected {
  border-color: #007aff;
  background: rgba(0, 122, 255, 0.04);
}

.theme-dark .service-card.selected {
  background: rgba(0, 122, 255, 0.1);
}

/* Promo Card */
.service-card.promo {
  border-color: rgba(255, 149, 0, 0.2);
  background: linear-gradient(135deg, rgba(255, 149, 0, 0.04) 0%, rgba(255, 59, 48, 0.04) 100%);
}

.theme-dark .service-card.promo {
  background: linear-gradient(135deg, rgba(255, 149, 0, 0.08) 0%, rgba(255, 59, 48, 0.08) 100%);
}

.service-card.promo.selected {
  border-color: #ff9500;
  background: linear-gradient(135deg, rgba(255, 149, 0, 0.08) 0%, rgba(255, 59, 48, 0.08) 100%);
}

/* Promo Badge */
.promo-badge {
  position: absolute;
  top: -12px;
  right: 12px;
  background: linear-gradient(135deg, #ff9500, #ff3b30);
  color: white;
  padding: 6px 12px;
  border-radius: 8px;
  font-size: 11px;
  font-weight: 700;
  display: flex;
  align-items: center;
  gap: 4px;
  letter-spacing: 0.02em;
  box-shadow: 0 4px 12px rgba(255, 59, 48, 0.3);
}

/* Check */
.service-check {
  width: 24px;
  height: 24px;
  flex-shrink: 0;
  margin-top: 2px;
}

.check-empty {
  width: 24px;
  height: 24px;
  border: 2px solid rgba(0,0,0,0.15);
  border-radius: 12px;
  transition: all 0.2s;
}

.theme-dark .check-empty {
  border-color: rgba(255,255,255,0.2);
}

.service-card:hover .check-empty {
  border-color: #007aff;
}

.service-card.promo:hover .check-empty {
  border-color: #ff9500;
}

.check-active {
  width: 24px;
  height: 24px;
  background: #007aff;
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
}

.service-card.promo .check-active {
  background: #ff9500;
}

.check-enter-active {
  animation: checkPop 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
}

@keyframes checkPop {
  0% { transform: scale(0); }
  100% { transform: scale(1); }
}

/* Content */
.service-content {
  flex: 1;
  min-width: 0;
}

.service-content h3 {
  font-size: 15px;
  font-weight: 600;
  color: #1d1d1f;
  margin: 0 0 4px;
  line-height: 1.3;
}

.theme-dark .service-content h3 {
  color: #f5f5f7;
}

.service-desc {
  font-size: 13px;
  color: #86868b;
  margin: 0 0 8px;
  line-height: 1.4;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

.theme-dark .service-desc {
  color: #98989d;
}

/* Included Services */
.included-services {
  margin: 10px 0 8px;
}

.included-label {
  font-size: 11px;
  font-weight: 600;
  color: #86868b;
  text-transform: uppercase;
  letter-spacing: 0.04em;
  display: block;
  margin-bottom: 6px;
}

.theme-dark .included-label {
  color: #98989d;
}

.included-tags {
  display: flex;
  flex-wrap: wrap;
  gap: 4px;
}

.included-tag {
  padding: 4px 8px;
  background: rgba(255, 149, 0, 0.1);
  border-radius: 6px;
  font-size: 11px;
  color: #1d1d1f;
  font-weight: 500;
}

.theme-dark .included-tag {
  background: rgba(255, 149, 0, 0.15);
  color: #f5f5f7;
}

/* Savings Badge */
.savings-badge {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 6px 12px;
  background: #34c759;
  color: white;
  border-radius: 8px;
  font-size: 12px;
  font-weight: 600;
  margin-top: 8px;
}

/* Meta */
.service-meta {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
  align-items: center;
  margin-top: 8px;
}

.duration {
  display: flex;
  align-items: center;
  gap: 4px;
  font-size: 12px;
  color: #86868b;
}

.theme-dark .duration {
  color: #98989d;
}

.duration svg {
  color: #007aff;
}

.category-tag {
  font-size: 11px;
  padding: 3px 8px;
  background: rgba(0,0,0,0.04);
  border-radius: 6px;
  color: #86868b;
}

.theme-dark .category-tag {
  background: rgba(255,255,255,0.06);
  color: #98989d;
}

/* Price */
.service-price {
  display: flex;
  flex-direction: column;
  align-items: flex-end;
  flex-shrink: 0;
  min-width: 70px;
}

.price-normal {
  font-size: 20px;
  font-weight: 700;
  color: #007aff;
  letter-spacing: -0.02em;
}

.price-original {
  font-size: 15px;
  margin-top:5px;
  color: #86868b;
  text-decoration: line-through;
}

.price-final {
  font-size: 22px;
  font-weight: 700;
  color: #ff9500;
  letter-spacing: -0.02em;
}

/* Empty State */
.empty-state {
  text-align: center;
  padding: 50px 20px;
  color: #86868b;
}

.theme-dark .empty-state {
  color: #98989d;
}

.empty-state p {
  margin-top: 12px;
  font-size: 15px;
}

/* Selection Summary */
.selection-summary {
  position: fixed;
  bottom: 70px;
  left: 0;
  right: 0;
  background: rgba(255, 255, 255, 0.95);
  backdrop-filter: blur(20px);
  -webkit-backdrop-filter: blur(20px);
  padding: 16px;
  border-top: 1px solid rgba(0,0,0,0.06);
  z-index: 40;
}

.theme-dark .selection-summary {
  background: rgba(28, 28, 30, 0.95);
  border-color: rgba(255,255,255,0.08);
}

.summary-info {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 10px;
}

.summary-count {
  font-size: 13px;
  color: #86868b;
}

.theme-dark .summary-count {
  color: #98989d;
}

.summary-total {
  font-size: 20px;
  font-weight: 700;
  color: #007aff;
  letter-spacing: -0.02em;
}

.summary-tags {
  display: flex;
  flex-wrap: wrap;
  gap: 6px;
}

.summary-tag {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 6px 10px;
  background: #007aff;
  color: white;
  border-radius: 8px;
  font-size: 13px;
  font-weight: 500;
}

.tag-remove {
  background: rgba(255,255,255,0.2);
  border: none;
  width: 18px;
  height: 18px;
  border-radius: 9px;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  color: white;
  padding: 0;
}

.tag-remove:hover {
  background: rgba(255,255,255,0.3);
}

/* Slide Up Transition */
.slide-up-enter-active,
.slide-up-leave-active {
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.slide-up-enter-from,
.slide-up-leave-to {
  opacity: 0;
  transform: translateY(20px);
}

/* Responsive */
@media (max-width: 380px) {
  .paso-servicios {
    padding: 16px 12px;
    padding-bottom: 180px;
  }
  
  .page-header {
    padding: 18px 14px 20px;
    border-radius: 18px;
  }

  .header-icon {
    width: 40px;
    height: 40px;
    margin-bottom: 12px;
  }
  
  .page-header h1 {
    font-size: 20px;
  }

  .page-header p {
    font-size: 14px;
  }
  
  .service-card {
    padding: 14px;
  }
  
  .service-content h3 {
    font-size: 14px;
  }
}
</style>
