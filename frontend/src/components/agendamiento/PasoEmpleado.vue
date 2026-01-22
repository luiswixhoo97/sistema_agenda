<script setup lang="ts">
import { computed, watch, onMounted } from 'vue'
import { useAgendamiento } from '@/composables/useAgendamiento'
import AppIcons from './AppIcons.vue'

const { 
  empleados, 
  loadingCatalogo,
  seleccionarEmpleadoYAvanzar,
  store,
  cargarDisponibilidadMes
} = useAgendamiento()

const tieneMultiplesServicios = computed(() => store.serviciosSeleccionados.length > 1)

// Calcular precio con descuento para un servicio individual
function precioConDescuento(servicio: any): number {
  const precioBase = Number(servicio.precio)
  
  // Si hay una promoción seleccionada, aplicar el descuento
  if (store.promocionSeleccionada && store.promocionInfo) {
    const promocion = store.promocionInfo
    
    // Verificar que los servicios seleccionados coincidan con los de la promoción
    const serviciosPromoIds = (promocion.servicios_info?.map((s: any) => Number(s.id)) || []).sort((a: number, b: number) => a - b)
    const serviciosSeleccionadosIds = store.serviciosSeleccionados.map(s => Number(s.id)).sort((a: number, b: number) => a - b)
    
    // Verificar que todos los servicios de la promoción estén seleccionados
    const todosServiciosCoinciden = serviciosPromoIds.length > 0 && 
      serviciosPromoIds.every((id: number) => serviciosSeleccionadosIds.includes(id)) &&
      serviciosSeleccionadosIds.length === serviciosPromoIds.length
    
    // Solo aplicar descuento si los servicios coinciden exactamente con la promoción
    // y el servicio actual está incluido en la promoción
    if (todosServiciosCoinciden && serviciosPromoIds.includes(Number(servicio.id))) {
      const promo = promocion as any
      // Aplicar descuento por porcentaje
      if (promo.descuento_porcentaje && Number(promo.descuento_porcentaje) > 0) {
        const descuento = precioBase * (Number(promo.descuento_porcentaje) / 100)
        const precioFinal = precioBase - descuento
        console.log('💰 [PasoEmpleado] Aplicando descuento porcentual:', {
          servicioId: servicio.id,
          servicioNombre: servicio.nombre,
          precioBase,
          descuentoPorcentaje: promo.descuento_porcentaje,
          descuento,
          precioFinal,
          promocionId: store.promocionSeleccionada
        })
        return precioFinal
      } 
      // Aplicar descuento fijo (distribuir proporcionalmente)
      else if (promo.descuento_fijo && Number(promo.descuento_fijo) > 0) {
        const precioTotal = store.serviciosSeleccionados.reduce((sum, s) => sum + Number(s.precio), 0)
        if (precioTotal > 0) {
          const proporcion = precioBase / precioTotal
          const descuento = Number(promo.descuento_fijo) * proporcion
          const precioFinal = Math.max(0, precioBase - descuento)
          console.log('💰 [PasoEmpleado] Aplicando descuento fijo:', {
            servicioId: servicio.id,
            servicioNombre: servicio.nombre,
            precioBase,
            descuentoFijo: promo.descuento_fijo,
            proporcion,
            descuento,
            precioFinal
          })
          return precioFinal
        }
      }
    } else {
      console.log('ℹ️ [PasoEmpleado] No se aplica descuento:', {
        servicioId: servicio.id,
        servicioNombre: servicio.nombre,
        todosServiciosCoinciden,
        servicioEnPromo: serviciosPromoIds.includes(Number(servicio.id)),
        serviciosPromo: serviciosPromoIds,
        serviciosSeleccionados: serviciosSeleccionadosIds,
        promocionId: store.promocionSeleccionada,
        tienePromocionInfo: !!store.promocionInfo
      })
    }
  } else {
    console.log('ℹ️ [PasoEmpleado] No hay promoción activa:', {
      servicioId: servicio.id,
      promocionSeleccionada: store.promocionSeleccionada,
      tienePromocionInfo: !!store.promocionInfo
    })
  }
  
  return precioBase
}

// Computed para verificar si hay promoción activa
const tienePromocionActiva = computed(() => {
  return store.promocionSeleccionada !== null && store.promocionInfo !== null
})

function empleadosParaServicio(servicioId: number) {
  return store.empleadosDisponiblesPorServicio[servicioId] || []
}

function empleadoSeleccionadoParaServicio(servicioId: number, empleadoId: number) {
  const asignacion = store.empleadoAsignadoAServicio(servicioId)
  return asignacion?.empleadoId === empleadoId
}

function seleccionarEmpleadoParaServicio(servicioId: number, empleado: any) {
  if (empleadoSeleccionadoParaServicio(servicioId, empleado.id)) return
  store.asignarEmpleadoAServicio(servicioId, empleado)
}

function quitarEmpleadoSeleccionado(servicioId: number) {
  store.quitarEmpleadoDeServicio(servicioId)
}

watch(() => store.modoMultiplesEmpleados, (activo) => {
  if (activo) store.cargarEmpleadosPorServicio()
}, { immediate: true })

async function continuarModoMultiples() {
  if (store.todosServiciosTienenEmpleado) {
    store.siguientePaso()
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
    <div class="page-header">
      <div class="header-icon">
        <AppIcons name="users" :size="24" />
      </div>
      <h1>¿Con quién prefieres?</h1>
      <p v-if="!store.modoMultiplesEmpleados">Selecciona un profesional</p>
      <p v-else>Selecciona un profesional para cada servicio</p>
    </div>

    <!-- Notice for Multiple Mode -->
    <div v-if="store.modoMultiplesEmpleados && empleados.length === 0 && tieneMultiplesServicios" class="notice">
      <AppIcons name="info" :size="18" />
      <span>Los servicios seleccionados requieren diferentes profesionales.</span>
    </div>

    <!-- Mode Toggle -->
    <div v-if="tieneMultiplesServicios && empleados.length > 0" class="mode-toggle">
      <button 
        class="mode-btn"
        :class="{ 'active': !store.modoMultiplesEmpleados }"
        @click="store.modoMultiplesEmpleados && store.toggleModoMultiplesEmpleados()"
      >
        <AppIcons name="user" :size="16" />
        Un profesional
      </button>
      <button 
        class="mode-btn"
        :class="{ 'active': store.modoMultiplesEmpleados }"
        @click="!store.modoMultiplesEmpleados && store.toggleModoMultiplesEmpleados()"
      >
        <AppIcons name="users" :size="16" />
        Por servicio
      </button>
    </div>

    <!-- Loading -->
    <div v-if="loadingCatalogo" class="loading-state">
      <AppIcons name="loader" :size="32" class="spinner" />
      <p>Cargando profesionales...</p>
    </div>

    <!-- Single Employee Mode -->
    <template v-else-if="!store.modoMultiplesEmpleados">
      <!-- No Employees -->
      <div v-if="empleados.length === 0" class="empty-state">
        <AppIcons name="user-x" :size="48" />
        <p>No hay profesionales disponibles para los servicios seleccionados</p>
        <button class="btn-back" @click="store.pasoAnterior()">
          <AppIcons name="arrow-left" :size="16" />
          Modificar servicios
        </button>
      </div>

      <!-- Employees List -->
      <div v-else class="employees-list">
        <div 
          v-for="empleado in empleados" 
          :key="empleado.id"
          class="employee-card"
          @click="seleccionarEmpleadoYAvanzar(empleado)"
        >
          <!-- Avatar -->
          <div class="employee-avatar">
            <img v-if="empleado.foto" :src="empleado.foto" :alt="empleado.nombre" />
            <span v-else class="avatar-initial">{{ empleado.nombre.charAt(0) }}</span>
          </div>

          <!-- Info -->
          <div class="employee-info">
            <h3>{{ empleado.nombre }}</h3>
            
            <!-- Rating -->
            <div v-if="empleado.promedio_calificacion > 0" class="employee-rating">
              <div class="stars">
                <AppIcons 
                  v-for="i in 5" 
                  :key="i" 
                  name="star-filled" 
                  :size="12"
                  :class="{ 'filled': i <= empleado.promedio_calificacion }"
                />
              </div>
              <span>{{ empleado.promedio_calificacion.toFixed(1) }}</span>
            </div>

            <!-- Bio -->
            <p v-if="empleado.bio" class="employee-bio">{{ empleado.bio }}</p>

            <!-- Specialty -->
            <p v-if="empleado.especialidades" class="employee-specialty">
              <AppIcons name="award" :size="12" />
              {{ empleado.especialidades }}
            </p>

            <!-- Services Tags -->
            <div class="employee-services">
              <span v-for="servicio in empleado.servicios.slice(0, 3)" :key="servicio.id" class="service-tag">
                {{ servicio.nombre }}
              </span>
              <span v-if="empleado.servicios.length > 3" class="more-tag">
                +{{ empleado.servicios.length - 3 }}
              </span>
            </div>
          </div>

          <!-- Arrow -->
          <div class="employee-arrow">
            <AppIcons name="chevron-right" :size="20" />
          </div>
        </div>
      </div>
    </template>

    <!-- Multiple Employees Mode -->
    <template v-else>
      <div class="services-assignments">
        <div 
          v-for="servicio in store.serviciosSeleccionados" 
          :key="servicio.id"
          class="service-assignment"
        >
          <!-- Service Header -->
          <div class="assignment-header">
            <div class="assignment-icon">
              <AppIcons name="spa" :size="18" />
            </div>
            <div class="assignment-info">
              <h4>{{ servicio.nombre }}</h4>
              <span class="assignment-duration">
                <AppIcons name="clock" :size="12" />
                {{ servicio.duracion }} min
              </span>
            </div>
            <div class="assignment-price">
              <template v-if="tienePromocionActiva">
                <template v-if="precioConDescuento(servicio) < Number(servicio.precio)">
                  <span class="price-original">${{ Number(servicio.precio).toFixed(0) }}</span>
                  <span class="price-final">${{ Number(precioConDescuento(servicio)).toFixed(0) }}</span>
                </template>
                <template v-else>
                  ${{ Number(servicio.precio).toFixed(0) }}
                </template>
              </template>
              <template v-else>
                ${{ Number(servicio.precio).toFixed(0) }}
              </template>
            </div>
          </div>

          <!-- Selected Employee -->
          <div 
            v-if="store.empleadoAsignadoAServicio(servicio.id)"
            class="selected-employee"
            @click="quitarEmpleadoSeleccionado(servicio.id)"
          >
            <div class="mini-avatar">
              <img 
                v-if="store.empleadoAsignadoAServicio(servicio.id)?.empleadoFoto" 
                :src="store.empleadoAsignadoAServicio(servicio.id)?.empleadoFoto" 
              />
              <span v-else>{{ store.empleadoAsignadoAServicio(servicio.id)?.empleadoNombre.charAt(0) }}</span>
            </div>
            <span class="selected-name">{{ store.empleadoAsignadoAServicio(servicio.id)?.empleadoNombre }}</span>
            <button class="btn-remove" @click.stop="quitarEmpleadoSeleccionado(servicio.id)">
              <AppIcons name="x" :size="14" />
            </button>
          </div>

          <!-- Available Employees Grid -->
          <div v-if="!store.empleadoAsignadoAServicio(servicio.id)" class="employees-grid">
            <div 
              v-for="empleado in empleadosParaServicio(servicio.id)" 
              :key="empleado.id"
              class="mini-employee-card"
              @click="seleccionarEmpleadoParaServicio(servicio.id, empleado)"
            >
              <div class="mini-avatar">
                <img v-if="empleado.foto" :src="empleado.foto" :alt="empleado.nombre" />
                <span v-else>{{ empleado.nombre.charAt(0) }}</span>
              </div>
              <span class="mini-name">{{ empleado.nombre }}</span>
              <div v-if="empleado.promedio_calificacion > 0" class="mini-rating">
                <AppIcons name="star-filled" :size="10" />
                {{ empleado.promedio_calificacion.toFixed(1) }}
              </div>
            </div>
            
            <!-- No Employees -->
            <div v-if="empleadosParaServicio(servicio.id).length === 0" class="no-employees">
              <AppIcons name="user-x" :size="20" />
              <span>Sin profesionales disponibles</span>
            </div>
          </div>
        </div>
      </div>

      <!-- Continue Button -->
      <div class="footer-multiples">
        <div class="assignment-status">
          <span v-if="store.todosServiciosTienenEmpleado" class="status-complete">
            <AppIcons name="check-circle" :size="16" />
            Todos los servicios asignados
          </span>
          <span v-else class="status-pending">
            <AppIcons name="alert-circle" :size="16" />
            {{ store.empleadosPorServicio.length }} de {{ store.serviciosSeleccionados.length }} asignados
          </span>
        </div>
        <button 
          class="btn-continue"
          :disabled="!store.todosServiciosTienenEmpleado"
          @click="continuarModoMultiples"
        >
          Continuar
          <AppIcons name="chevron-right" :size="18" />
        </button>
      </div>
    </template>
  </div>
</template>

<style scoped>
.paso-empleado {
  padding: 20px 16px;
  padding-bottom: 120px;
  font-family: -apple-system, BlinkMacSystemFont, 'SF Pro Display', system-ui, sans-serif;
  -webkit-font-smoothing: antialiased;
}

/* Header */
.page-header {
  text-align: center;
  padding: 20px 0 28px;
}

.header-icon {
  width: 56px;
  height: 56px;
  border-radius: 16px;
  background: #007aff;
  display: flex;
  align-items: center;
  justify-content: center;
  margin: 0 auto 16px;
  color: white;
}

.page-header h1 {
  font-size: 24px;
  font-weight: 600;
  color: #1d1d1f;
  margin: 0 0 6px;
  letter-spacing: -0.02em;
}

.theme-dark .page-header h1 {
  color: #f5f5f7;
}

.page-header p {
  font-size: 15px;
  color: #86868b;
  margin: 0;
}

.theme-dark .page-header p {
  color: #98989d;
}

/* Notice */
.notice {
  display: flex;
  align-items: flex-start;
  gap: 10px;
  padding: 14px 16px;
  background: rgba(255, 149, 0, 0.08);
  border: 1px solid rgba(255, 149, 0, 0.2);
  border-radius: 12px;
  margin-bottom: 20px;
  color: #cc7700;
  font-size: 13px;
  line-height: 1.4;
}

.theme-dark .notice {
  color: #ffb74d;
}

/* Mode Toggle */
.mode-toggle {
  display: flex;
  gap: 4px;
  padding: 4px;
  background: rgba(0,0,0,0.04);
  border-radius: 12px;
  margin-bottom: 24px;
}

.theme-dark .mode-toggle {
  background: rgba(255,255,255,0.06);
}

.mode-btn {
  flex: 1;
  padding: 10px 14px;
  border: none;
  border-radius: 8px;
  background: transparent;
  color: #86868b;
  font-size: 14px;
  font-weight: 600;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  transition: all 0.2s;
  font-family: inherit;
}

.theme-dark .mode-btn {
  color: #98989d;
}

.mode-btn.active {
  background: white;
  color: #1d1d1f;
  box-shadow: 0 2px 8px rgba(0,0,0,0.08);
}

.theme-dark .mode-btn.active {
  background: rgba(255,255,255,0.1);
  color: #f5f5f7;
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
  margin: 16px 0 24px;
  font-size: 15px;
}

.btn-back {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  padding: 12px 20px;
  background: #007aff;
  color: white;
  border: none;
  border-radius: 12px;
  font-size: 15px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s;
  font-family: inherit;
}

.btn-back:hover {
  background: #0066d6;
}

/* Employees List */
.employees-list {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.employee-card {
  display: flex;
  align-items: flex-start;
  gap: 14px;
  padding: 16px;
  background: rgba(255, 255, 255, 0.95);
  backdrop-filter: blur(20px);
  border-radius: 16px;
  border: 1px solid rgba(0,0,0,0.04);
  cursor: pointer;
  transition: all 0.2s;
}

.theme-dark .employee-card {
  background: rgba(28, 28, 30, 0.95);
  border-color: rgba(255,255,255,0.06);
}

.employee-card:hover {
  border-color: rgba(0, 122, 255, 0.2);
  transform: translateX(2px);
}

.employee-card:active {
  transform: scale(0.99);
}

/* Avatar */
.employee-avatar {
  width: 56px;
  height: 56px;
  border-radius: 28px;
  overflow: hidden;
  flex-shrink: 0;
  background: #007aff;
}

.employee-avatar img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.avatar-initial {
  width: 100%;
  height: 100%;
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
  font-size: 20px;
  font-weight: 600;
}

/* Info */
.employee-info {
  flex: 1;
  min-width: 0;
}

.employee-info h3 {
  font-size: 16px;
  font-weight: 600;
  color: #1d1d1f;
  margin: 0 0 4px;
}

.theme-dark .employee-info h3 {
  color: #f5f5f7;
}

/* Rating */
.employee-rating {
  display: flex;
  align-items: center;
  gap: 6px;
  margin-bottom: 6px;
}

.stars {
  display: flex;
  gap: 2px;
}

.stars svg {
  color: #d1d1d6;
}

.stars svg.filled {
  color: #ffcc00;
}

.employee-rating span {
  font-size: 12px;
  color: #86868b;
}

/* Bio */
.employee-bio {
  font-size: 13px;
  color: #86868b;
  margin: 0 0 6px;
  line-height: 1.4;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

.theme-dark .employee-bio {
  color: #98989d;
}

/* Specialty */
.employee-specialty {
  font-size: 12px;
  color: #007aff;
  margin: 0 0 8px;
  display: flex;
  align-items: center;
  gap: 4px;
  font-weight: 500;
}

/* Services */
.employee-services {
  display: flex;
  flex-wrap: wrap;
  gap: 4px;
}

.service-tag {
  font-size: 10px;
  padding: 3px 8px;
  background: rgba(0,0,0,0.04);
  border-radius: 6px;
  color: #86868b;
}

.theme-dark .service-tag {
  background: rgba(255,255,255,0.06);
  color: #98989d;
}

.more-tag {
  font-size: 10px;
  color: #86868b;
}

/* Arrow */
.employee-arrow {
  color: #c7c7cc;
  align-self: center;
}

.theme-dark .employee-arrow {
  color: #48484a;
}

/* Multiple Employees Mode */
.services-assignments {
  display: flex;
  flex-direction: column;
  gap: 16px;
}

.service-assignment {
  background: rgba(255, 255, 255, 0.95);
  backdrop-filter: blur(20px);
  border-radius: 16px;
  padding: 18px;
  border: 1px solid rgba(0,0,0,0.04);
}

.theme-dark .service-assignment {
  background: rgba(28, 28, 30, 0.95);
  border-color: rgba(255,255,255,0.06);
}

.assignment-header {
  display: flex;
  align-items: center;
  gap: 12px;
  padding-bottom: 14px;
  border-bottom: 1px solid rgba(0,0,0,0.06);
  margin-bottom: 14px;
}

.theme-dark .assignment-header {
  border-color: rgba(255,255,255,0.08);
}

.assignment-icon {
  width: 40px;
  height: 40px;
  border-radius: 10px;
  background: #007aff;
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
}

.assignment-info {
  flex: 1;
}

.assignment-info h4 {
  font-size: 14px;
  font-weight: 600;
  color: #1d1d1f;
  margin: 0 0 2px;
}

.theme-dark .assignment-info h4 {
  color: #f5f5f7;
}

.assignment-duration {
  font-size: 12px;
  color: #86868b;
  display: flex;
  align-items: center;
  gap: 4px;
}

.theme-dark .assignment-duration {
  color: #98989d;
}

.assignment-price {
  font-size: 16px;
  font-weight: 600;
  color: #007aff;
  display: flex;
  flex-direction: column;
  align-items: flex-end;
  gap: 2px;
}

.assignment-price .price-original {
  font-size: 12px;
  font-weight: 400;
  color: #86868b;
  text-decoration: line-through;
}

.theme-dark .assignment-price .price-original {
  color: #98989d;
}

.assignment-price .price-final {
  font-size: 16px;
  font-weight: 600;
  color: #007aff;
}

/* Selected Employee */
.selected-employee {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 12px;
  background: rgba(52, 199, 89, 0.1);
  border-radius: 10px;
  border: 1px solid rgba(52, 199, 89, 0.2);
  cursor: pointer;
  transition: all 0.2s;
}

.theme-dark .selected-employee {
  background: rgba(52, 199, 89, 0.15);
}

.selected-name {
  flex: 1;
  font-size: 14px;
  font-weight: 600;
  color: #34c759;
}

.btn-remove {
  width: 28px;
  height: 28px;
  border-radius: 8px;
  background: rgba(255, 59, 48, 0.1);
  border: none;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  color: #ff3b30;
  transition: all 0.2s;
}

.btn-remove:hover {
  background: rgba(255, 59, 48, 0.2);
}

/* Employees Grid */
.employees-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(90px, 1fr));
  gap: 10px;
}

.mini-employee-card {
  display: flex;
  flex-direction: column;
  align-items: center;
  padding: 12px 8px;
  background: rgba(0, 122, 255, 0.04);
  border-radius: 12px;
  cursor: pointer;
  transition: all 0.2s;
  border: 1px solid rgba(0, 122, 255, 0.1);
}

.theme-dark .mini-employee-card {
  background: rgba(0, 122, 255, 0.08);
}

.mini-employee-card:hover {
  background: rgba(0, 122, 255, 0.08);
  border-color: rgba(0, 122, 255, 0.2);
}

.mini-avatar {
  width: 40px;
  height: 40px;
  border-radius: 20px;
  overflow: hidden;
  background: #007aff;
  margin-bottom: 6px;
}

.mini-avatar img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.mini-avatar span {
  width: 100%;
  height: 100%;
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
  font-size: 14px;
  font-weight: 600;
}

.mini-name {
  font-size: 11px;
  font-weight: 600;
  color: #1d1d1f;
  text-align: center;
  line-height: 1.2;
}

.theme-dark .mini-name {
  color: #f5f5f7;
}

.mini-rating {
  display: flex;
  align-items: center;
  gap: 3px;
  font-size: 10px;
  color: #ffcc00;
  margin-top: 4px;
}

.no-employees {
  grid-column: 1 / -1;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  padding: 20px;
  color: #86868b;
  font-size: 13px;
}

/* Footer Multiple */
.footer-multiples {
  position: fixed;
  bottom: 0;
  left: 0;
  right: 0;
  padding: 16px;
  background: rgba(255, 255, 255, 0.95);
  backdrop-filter: blur(20px);
  border-top: 1px solid rgba(0,0,0,0.06);
  display: flex;
  align-items: center;
  gap: 12px;
  z-index: 50;
}

.theme-dark .footer-multiples {
  background: rgba(28, 28, 30, 0.95);
  border-color: rgba(255,255,255,0.08);
}

.assignment-status {
  flex: 1;
}

.assignment-status span {
  display: flex;
  align-items: center;
  gap: 6px;
  font-size: 13px;
  font-weight: 600;
}

.status-complete {
  color: #34c759;
}

.status-pending {
  color: #ff9500;
}

.btn-continue {
  display: flex;
  align-items: center;
  gap: 6px;
  padding: 12px 20px;
  background: #007aff;
  color: white;
  border: none;
  border-radius: 12px;
  font-size: 15px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s;
  font-family: inherit;
}

.btn-continue:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.btn-continue:not(:disabled):hover {
  background: #0066d6;
}

/* Responsive */
@media (max-width: 380px) {
  .paso-empleado {
    padding: 16px 12px;
  }
  
  .header-icon {
    width: 48px;
    height: 48px;
  }
  
  .page-header h1 {
    font-size: 22px;
  }
  
  .employee-avatar {
    width: 48px;
    height: 48px;
  }
  
  .mode-btn {
    padding: 8px 12px;
    font-size: 13px;
  }
}
</style>
