<script setup lang="ts">
import { onMounted, watch } from 'vue'
import { useAgendamiento } from '@/composables/useAgendamiento'
import PasoDatosCliente from '@/components/agendamiento/PasoDatosCliente.vue'
import PasoServicios from '@/components/agendamiento/PasoServicios.vue'
import PasoEmpleado from '@/components/agendamiento/PasoEmpleado.vue'
import PasoFechaHora from '@/components/agendamiento/PasoFechaHora.vue'
import PasoConfirmacion from '@/components/agendamiento/PasoConfirmacion.vue'

const { 
  store, 
  cargarCatalogo, 
  cargarEmpleados 
} = useAgendamiento()

onMounted(() => {
  cargarCatalogo()
  store.reiniciarAgendamiento()
})

watch(() => store.paso, async (nuevoPaso) => {
  if (nuevoPaso === 3) {
    await cargarEmpleados()
  }
})

const pasos = [
  { numero: 1, titulo: 'Datos', icon: 'fa-user' },
  { numero: 2, titulo: 'Servicios', icon: 'fa-spa' },
  { numero: 3, titulo: 'Profesional', icon: 'fa-user-tie' },
  { numero: 4, titulo: 'Fecha', icon: 'fa-calendar' },
  { numero: 5, titulo: 'Confirmar', icon: 'fa-check' },
]
</script>

<template>
  <div class="agendar-view">
    <!-- Indicador de pasos -->
    <div class="pasos-container">
  
      <div class="pasos-items">
        <div 
          v-for="paso in pasos" 
          :key="paso.numero"
          class="paso-item"
          :class="{ 
            'activo': store.paso === paso.numero,
            'completado': store.paso > paso.numero 
          }"
        >
          <div class="paso-circulo">
            <i v-if="store.paso > paso.numero" class="fa fa-check"></i>
            <i v-else :class="['fa', paso.icon]"></i>
          </div>
          <span class="paso-titulo">{{ paso.titulo }}</span>
        </div>
      </div>
    </div>

    <!-- Contenido del paso -->
    <div class="paso-contenido">
      <Transition name="slide" mode="out-in">
        <PasoDatosCliente v-if="store.paso === 1" key="1" />
        <PasoServicios v-else-if="store.paso === 2" key="2" />
        <PasoEmpleado v-else-if="store.paso === 3" key="3" />
        <PasoFechaHora v-else-if="store.paso === 4" key="4" />
        <PasoConfirmacion v-else-if="store.paso === 5" key="5" />
      </Transition>
    </div>

    <!-- Error toast -->
    <Transition name="toast">
      <div v-if="store.error" class="error-toast">
        <i class="fa fa-exclamation-circle"></i>
        <span>{{ store.error }}</span>
        <button @click="store.clearError()">
          <i class="fa fa-times"></i>
        </button>
      </div>
    </Transition>

    <!-- Footer navegación -->
    <footer class="agendar-footer" v-if="store.paso < 5">
      <button 
        class="btn-nav btn-anterior"
        @click="store.pasoAnterior()"
        :disabled="store.paso === 1"
      >
        <i class="fa fa-chevron-left"></i>
        Anterior
      </button>
      
      <div class="footer-resumen" v-if="store.calculoServicio && store.paso > 1">
        <span class="precio">${{ Number(store.totalPrecio || 0).toFixed(0) }}</span>
        <span class="duracion">{{ store.calculoServicio.duracion_texto }}</span>
      </div>

      <button 
        class="btn-nav btn-siguiente"
        @click="store.siguientePaso()"
        :disabled="!store.puedeAvanzar || store.loading"
      >
        <span v-if="store.loading">
          <i class="fa fa-spinner fa-spin"></i>
        </span>
        <span v-else>
          {{ store.paso === 4 ? 'Confirmar' : 'Siguiente' }}
          <i class="fa fa-chevron-right"></i>
        </span>
      </button>
    </footer>
  </div>
</template>

<style scoped>
.agendar-view {
  width: 100%;
  min-height: 100%;
  flex: 1;
  display: flex;
  flex-direction: column;
  background: #f5f5f7;
  font-family: -apple-system, BlinkMacSystemFont, 'SF Pro Display', 'SF Pro Text', 'Helvetica Neue', sans-serif;
  -webkit-font-smoothing: antialiased;
  -moz-osx-font-smoothing: grayscale;
}

.theme-dark .agendar-view {
  background: #000000;
}

/* Pasos */
.pasos-container {
  background: rgba(255, 255, 255, 0.95);
  backdrop-filter: saturate(180%) blur(20px);
  -webkit-backdrop-filter: saturate(180%) blur(20px);
  padding: 16px 12px;
  margin: 25px 16px 0;
  border-radius: 16px;
  border: 0.5px solid rgba(0, 0, 0, 0.1);
  box-shadow: 
    0 2px 8px rgba(0, 0, 0, 0.04),
    0 0 0 0.5px rgba(0, 0, 0, 0.06);
}

.theme-dark .pasos-container {
  background: rgba(28, 28, 30, 0.95);
  border-color: rgba(255, 255, 255, 0.1);
  box-shadow: 
    0 2px 8px rgba(0, 0, 0, 0.3),
    0 0 0 0.5px rgba(255, 255, 255, 0.05);
}

.pasos-progress {
  height: 2px;
  background: rgba(0, 0, 0, 0.08);
  border-radius: 1px;
  margin-bottom: 16px;
  overflow: hidden;
  position: relative;
}

.theme-dark .pasos-progress {
  background: rgba(255, 255, 255, 0.1);
}

.pasos-progress-bar {
  height: 100%;
  background: linear-gradient(90deg, #007aff, #0051d5);
  border-radius: 1px;
  transition: width 0.4s cubic-bezier(0.4, 0, 0.2, 1);
  box-shadow: 0 0 6px rgba(0, 122, 255, 0.3);
}

.pasos-items {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  gap: 8px;
}

.paso-item {
  display: flex;
  flex-direction: column;
  align-items: center;
  flex: 1;
  min-width: 0;
  opacity: 0.5;
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  position: relative;
}

.paso-item.activo {
  opacity: 1;
  transform: translateY(-2px);
}

.paso-item.completado {
  opacity: 1;
}

.paso-circulo {
  width: 36px;
  height: 36px;
  border-radius: 10px;
  background: rgba(0, 0, 0, 0.06);
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 15px;
  color: #86868b;
  margin-bottom: 8px;
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  position: relative;
  border: 2px solid transparent;
}

.theme-dark .paso-circulo {
  background: rgba(255, 255, 255, 0.1);
  color: #6e6e73;
}

.paso-item.activo .paso-circulo {
  background: #007aff;
  color: white;
  box-shadow: 
    0 3px 10px rgba(0, 122, 255, 0.35),
    0 0 0 3px rgba(0, 122, 255, 0.1);
  transform: scale(1.08);
  border-color: rgba(255, 255, 255, 0.2);
}

.paso-item.completado .paso-circulo {
  background: #34c759;
  color: white;
  box-shadow: 0 2px 6px rgba(52, 199, 89, 0.3);
}

.paso-item.completado .paso-circulo i {
  font-size: 14px;
}

.paso-titulo {
  font-size: 11px;
  font-weight: 500;
  color: #86868b;
  letter-spacing: -0.1px;
  text-align: center;
  line-height: 1.3;
  transition: all 0.3s;
  max-width: 100%;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.theme-dark .paso-titulo {
  color: #a1a1a6;
}

.paso-item.activo .paso-titulo {
  color: #007aff;
  font-weight: 700;
  font-size: 12px;
  transform: scale(1.03);
}

.paso-item.completado .paso-titulo {
  color: #34c759;
  font-weight: 600;
}

/* Línea conectora entre pasos */
.paso-item:not(:last-child)::after {
  content: '';
  position: absolute;
  top: 18px;
  left: calc(50% + 18px);
  width: calc(100% - 36px);
  height: 2px;
  background: rgba(0, 0, 0, 0.08);
  z-index: 0;
  transition: all 0.3s;
}

.theme-dark .paso-item:not(:last-child)::after {
  background: rgba(255, 255, 255, 0.1);
}

.paso-item.completado:not(:last-child)::after {
  background: #34c759;
  height: 2px;
}

.paso-item.activo:not(:last-child)::after {
  background: linear-gradient(90deg, #34c759, #007aff);
  height: 2px;
}

/* Contenido */
.paso-contenido {
  flex: 1;
  overflow-y: auto;
  padding-bottom: 80px;
}

/* Error toast */
.error-toast {
  position: fixed;
  bottom: 100px;
  left: 16px;
  right: 16px;
  background: #ff3b30;
  color: white;
  padding: 14px 18px;
  border-radius: 14px;
  display: flex;
  align-items: center;
  gap: 12px;
  box-shadow: 
    0 8px 24px rgba(255, 59, 48, 0.3),
    0 0 0 0.5px rgba(255, 59, 48, 0.2);
  z-index: 100;
  backdrop-filter: saturate(180%) blur(20px);
  -webkit-backdrop-filter: saturate(180%) blur(20px);
}

.error-toast i:first-child {
  font-size: 18px;
}

.error-toast span {
  flex: 1;
  font-size: 14px;
}

.error-toast button {
  background: none;
  border: none;
  color: white;
  padding: 4px;
  cursor: pointer;
  opacity: 0.8;
}

/* Footer */
.agendar-footer {
  position: fixed;
  bottom: 0;
  left: 0;
  right: 0;
  background: rgba(255, 255, 255, 0.95);
  backdrop-filter: saturate(180%) blur(20px);
  -webkit-backdrop-filter: saturate(180%) blur(20px);
  padding: 16px;
  display: flex;
  align-items: center;
  gap: 12px;
  border-top: 0.5px solid rgba(0, 0, 0, 0.1);
  z-index: 50;
  box-shadow: 0 -4px 16px rgba(0, 0, 0, 0.04);
}

.theme-dark .agendar-footer {
  background: rgba(28, 28, 30, 0.95);
  border-color: rgba(255, 255, 255, 0.1);
  box-shadow: 0 -4px 16px rgba(0, 0, 0, 0.3);
}

.footer-resumen {
  flex: 1;
  text-align: center;
}

.footer-resumen .precio {
  display: block;
  font-size: 20px;
  font-weight: 600;
  color: #007aff;
  letter-spacing: -0.3px;
}

.footer-resumen .duracion {
  font-size: 13px;
  color: #86868b;
  font-weight: 400;
}

.theme-dark .footer-resumen .duracion {
  color: #a1a1a6;
}

.btn-nav {
  padding: 12px 20px;
  border-radius: 12px;
  font-size: 15px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
  display: flex;
  align-items: center;
  gap: 6px;
  border: none;
  font-family: -apple-system, BlinkMacSystemFont, 'SF Pro Text', sans-serif;
  letter-spacing: -0.2px;
}

.btn-anterior {
  background: rgba(0, 0, 0, 0.05);
  color: #1d1d1f;
}

.theme-dark .btn-anterior {
  background: rgba(255, 255, 255, 0.1);
  color: #f5f5f7;
}

.btn-anterior:hover:not(:disabled) {
  background: rgba(0, 0, 0, 0.08);
}

.theme-dark .btn-anterior:hover:not(:disabled) {
  background: rgba(255, 255, 255, 0.15);
}

.btn-anterior:active:not(:disabled) {
  transform: scale(0.98);
}

.btn-anterior:disabled {
  opacity: 0.3;
  cursor: not-allowed;
}

.btn-siguiente {
  background: #007aff;
  color: white;
  flex: 1;
  justify-content: center;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.btn-siguiente:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.btn-siguiente:not(:disabled):hover {
  background: #0051d5;
  box-shadow: 0 2px 8px rgba(0, 122, 255, 0.3);
}

.btn-siguiente:not(:disabled):active {
  transform: scale(0.98);
  background: #0040b3;
}

/* Transiciones */
.slide-enter-active,
.slide-leave-active {
  transition: all 0.2s ease;
}

.slide-enter-from {
  opacity: 0;
  transform: translateX(20px);
}

.slide-leave-to {
  opacity: 0;
  transform: translateX(-20px);
}

.toast-enter-active,
.toast-leave-active {
  transition: all 0.3s ease;
}

.toast-enter-from,
.toast-leave-to {
  opacity: 0;
  transform: translateY(20px);
}
</style>
