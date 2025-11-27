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
      <div class="pasos-progress">
        <div 
          class="pasos-progress-bar" 
          :style="{ width: `${((store.paso - 1) / (pasos.length - 1)) * 100}%` }"
        ></div>
      </div>
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
  background: #f5f5f5;
}

.theme-dark .agendar-view {
  background: #0f0f0f;
}

/* Pasos */
.pasos-container {
  background: white;
  padding: 16px;
  border-bottom: 1px solid rgba(0,0,0,0.08);
}

.theme-dark .pasos-container {
  background: #1e1e1e;
  border-color: rgba(255,255,255,0.08);
}

.pasos-progress {
  height: 3px;
  background: rgba(0,0,0,0.1);
  border-radius: 2px;
  margin-bottom: 16px;
  overflow: hidden;
}

.theme-dark .pasos-progress {
  background: rgba(255,255,255,0.1);
}

.pasos-progress-bar {
  height: 100%;
  background: linear-gradient(90deg, #ec407a, #d81b60);
  border-radius: 2px;
  transition: width 0.3s ease;
}

.pasos-items {
  display: flex;
  justify-content: space-between;
}

.paso-item {
  display: flex;
  flex-direction: column;
  align-items: center;
  flex: 1;
  opacity: 0.4;
  transition: opacity 0.3s;
}

.paso-item.activo,
.paso-item.completado {
  opacity: 1;
}

.paso-circulo {
  width: 36px;
  height: 36px;
  border-radius: 50%;
  background: rgba(0,0,0,0.08);
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 14px;
  color: #666;
  margin-bottom: 4px;
  transition: all 0.3s;
}

.theme-dark .paso-circulo {
  background: rgba(255,255,255,0.1);
  color: #999;
}

.paso-item.activo .paso-circulo {
  background: linear-gradient(135deg, #ec407a, #d81b60);
  color: white;
  box-shadow: 0 4px 12px rgba(236, 64, 122, 0.4);
  transform: scale(1.1);
}

.paso-item.completado .paso-circulo {
  background: #4caf50;
  color: white;
}

.paso-titulo {
  font-size: 11px;
  font-weight: 500;
  color: #666;
}

.theme-dark .paso-titulo {
  color: #aaa;
}

.paso-item.activo .paso-titulo {
  color: #ec407a;
  font-weight: 600;
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
  background: #ef4444;
  color: white;
  padding: 12px 16px;
  border-radius: 12px;
  display: flex;
  align-items: center;
  gap: 10px;
  box-shadow: 0 4px 20px rgba(0,0,0,0.2);
  z-index: 100;
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
  background: white;
  padding: 12px 16px;
  display: flex;
  align-items: center;
  gap: 12px;
  border-top: 1px solid rgba(0,0,0,0.08);
  z-index: 50;
}

.theme-dark .agendar-footer {
  background: #1e1e1e;
  border-color: rgba(255,255,255,0.08);
}

.footer-resumen {
  flex: 1;
  text-align: center;
}

.footer-resumen .precio {
  display: block;
  font-size: 18px;
  font-weight: 700;
  color: #ec407a;
}

.footer-resumen .duracion {
  font-size: 11px;
  color: #888;
}

.btn-nav {
  padding: 12px 20px;
  border-radius: 10px;
  font-size: 14px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s;
  display: flex;
  align-items: center;
  gap: 6px;
  border: none;
}

.btn-anterior {
  background: rgba(0,0,0,0.05);
  color: #666;
}

.theme-dark .btn-anterior {
  background: rgba(255,255,255,0.1);
  color: #aaa;
}

.btn-anterior:disabled {
  opacity: 0.3;
  cursor: not-allowed;
}

.btn-siguiente {
  background: linear-gradient(135deg, #ec407a, #d81b60);
  color: white;
  flex: 1;
  justify-content: center;
}

.btn-siguiente:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.btn-siguiente:not(:disabled):hover {
  transform: translateY(-1px);
  box-shadow: 0 4px 12px rgba(236, 64, 122, 0.4);
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
