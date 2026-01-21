<script setup lang="ts">
import { onMounted, watch, computed, defineAsyncComponent } from 'vue'
import { useAgendamiento } from '@/composables/useAgendamiento'
import { useAppkit } from '@/composables/useAppkit'
import AppIcons from '@/components/agendamiento/AppIcons.vue'

// Lazy loading de componentes de pasos para mejor rendimiento
const PasoDatosCliente = defineAsyncComponent(() => 
  import('@/components/agendamiento/PasoDatosCliente.vue')
)
const PasoServicios = defineAsyncComponent(() =>
  import('@/components/agendamiento/PasoServicios.vue')
)
const PasoEmpleado = defineAsyncComponent(() =>
  import('@/components/agendamiento/PasoEmpleado.vue')
)
const PasoFechaHora = defineAsyncComponent(() =>
  import('@/components/agendamiento/PasoFechaHora.vue')
)
const PasoConfirmacion = defineAsyncComponent(() =>
  import('@/components/agendamiento/PasoConfirmacion.vue')
)

const { 
  store, 
  cargarCatalogo, 
  cargarEmpleados 
} = useAgendamiento()

const { theme, toggleTheme } = useAppkit()

onMounted(() => {
  cargarCatalogo()
  store.reiniciarAgendamiento()
})

watch(() => store.paso, async (nuevoPaso) => {
  if (nuevoPaso === 3) {
    await cargarEmpleados()
  }
  if (nuevoPaso === 5) {
    await store.validarAnticipo()
  }
})

const pasos = [
  { numero: 1, titulo: 'Datos', icon: 'user' },
  { numero: 2, titulo: 'Servicios', icon: 'spa' },
  { numero: 3, titulo: 'Profesional', icon: 'briefcase' },
  { numero: 4, titulo: 'Fecha', icon: 'calendar' },
  { numero: 5, titulo: 'Confirmar', icon: 'check' },
]

// Computed para el porcentaje de progreso
const progressPercentage = computed(() => {
  return ((store.paso - 1) / (pasos.length - 1)) * 100
})
</script>

<template>
  <div class="agendar-view" :class="{ 'theme-dark': theme === 'dark' }">
    <!-- Theme Toggle -->
    <button class="theme-toggle" @click="toggleTheme" :aria-label="theme === 'light' ? 'Cambiar a modo oscuro' : 'Cambiar a modo claro'">
      <AppIcons :name="theme === 'light' ? 'moon' : 'sun'" :size="20" />
    </button>

    <!-- Progress Steps -->
    <div class="steps-container" role="navigation" aria-label="Indicador de progreso">
      <!-- Steps -->
      <div class="steps">
        <div 
          v-for="paso in pasos" 
          :key="paso.numero"
          class="step"
          :class="{ 
            'active': store.paso === paso.numero,
            'completed': store.paso > paso.numero 
          }"
          role="listitem"
          :aria-current="store.paso === paso.numero ? 'step' : undefined"
        >
          <!-- Progress Line (behind icons) -->
          <div v-if="paso.numero < pasos.length" class="progress-line">
            <div 
              class="progress-fill" 
              :class="{ 'filled': store.paso > paso.numero }"
            ></div>
          </div>
          
          <div class="step-icon">
            <AppIcons v-if="store.paso > paso.numero" name="check" :size="16" />
            <AppIcons v-else :name="paso.icon" :size="16" />
          </div>
          <span class="step-label">{{ paso.titulo }}</span>
        </div>
      </div>
    </div>

    <!-- Content -->
    <div class="content">
      <Transition name="slide" mode="out-in">
        <PasoDatosCliente v-if="store.paso === 1" key="1" />
        <PasoServicios v-else-if="store.paso === 2" key="2" />
        <PasoEmpleado v-else-if="store.paso === 3" key="3" />
        <PasoFechaHora v-else-if="store.paso === 4" key="4" />
        <PasoConfirmacion v-else-if="store.paso === 5" key="5" />
      </Transition>
    </div>

    <!-- Error Toast -->
    <Transition name="toast">
      <div v-if="store.error" class="error-toast">
        <AppIcons name="alert-circle" :size="20" />
        <span>{{ store.error }}</span>
        <button @click="store.clearError()" class="toast-close">
          <AppIcons name="x" :size="16" />
        </button>
      </div>
    </Transition>

    <!-- Footer Navigation -->
    <footer class="footer" v-if="store.paso < 5" role="navigation" aria-label="Navegación">
      <button 
        class="btn btn-secondary"
        @click="store.pasoAnterior()"
        :disabled="store.paso === 1"
      >
        <AppIcons name="chevron-left" :size="18" />
        <span>Anterior</span>
      </button>
      
      <div class="footer-summary" v-if="store.calculoServicio && store.paso > 1">
        <span class="price">${{ Number(store.totalPrecio || 0).toFixed(0) }}</span>
        <span class="duration">{{ store.calculoServicio.duracion_texto }}</span>
      </div>

      <button 
        class="btn btn-primary"
        @click="store.siguientePaso()"
        :disabled="!store.puedeAvanzar || store.loading"
      >
        <span v-if="store.loading" class="loading">
          <AppIcons name="loader" :size="18" />
        </span>
        <span v-else>
          {{ store.paso === 4 ? 'Confirmar' : 'Siguiente' }}
          <AppIcons name="chevron-right" :size="18" />
        </span>
      </button>
    </footer>
  </div>
</template>

<style scoped>
/* ============================================
   APPLE MINIMAL DESIGN SYSTEM
   ============================================ */

.agendar-view {
  min-height: 100dvh;
  display: flex;
  flex-direction: column;
  background: #fafafa;
  font-family: -apple-system, BlinkMacSystemFont, 'SF Pro Display', 'SF Pro Text', system-ui, sans-serif;
  -webkit-font-smoothing: antialiased;
  color: #1d1d1f;
}

.theme-dark.agendar-view {
  background: #000;
  color: #f5f5f7;
}

/* Theme Toggle */
.theme-toggle {
  position: fixed;
  top: 16px;
  right: 16px;
  width: 44px;
  height: 44px;
  border-radius: 22px;
  background: rgba(255, 255, 255, 0.9);
  backdrop-filter: blur(20px);
  -webkit-backdrop-filter: blur(20px);
  border: 1px solid rgba(0, 0, 0, 0.06);
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  z-index: 100;
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  color: #1d1d1f;
  box-shadow: 0 2px 12px rgba(0, 0, 0, 0.08);
}

.theme-dark .theme-toggle {
  background: rgba(44, 44, 46, 0.9);
  border-color: rgba(255, 255, 255, 0.1);
  color: #f5f5f7;
  box-shadow: 0 2px 12px rgba(0, 0, 0, 0.3);
}

.theme-toggle:hover {
  transform: scale(1.05);
}

.theme-toggle:active {
  transform: scale(0.95);
}

/* Steps Container */
.steps-container {
  padding: 24px 20px;
  margin: 8px 16px 0;
  background: rgba(255, 255, 255, 0.95);
  backdrop-filter: blur(20px);
  -webkit-backdrop-filter: blur(20px);
  border-radius: 20px;
  border: 1px solid rgba(0, 0, 0, 0.04);
  box-shadow: 0 2px 20px rgba(0, 0, 0, 0.04);
}

.theme-dark .steps-container {
  background: rgba(28, 28, 30, 0.95);
  border-color: rgba(255, 255, 255, 0.06);
  box-shadow: 0 2px 20px rgba(0, 0, 0, 0.3);
}

/* Steps */
.steps {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  gap: 0;
  position: relative;
}

.step {
  display: flex;
  flex-direction: column;
  align-items: center;
  flex: 1;
  opacity: 0.35;
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  position: relative;
}

.step.active {
  opacity: 1;
}

.step.completed {
  opacity: 0.85;
}

/* Progress Line (behind icons) */
.progress-line {
  position: absolute;
  top: 18px;
  left: 50%;
  width: 100%;
  height: 2px;
  background: rgba(0, 0, 0, 0.06);
  z-index: 0;
}

.theme-dark .progress-line {
  background: rgba(255, 255, 255, 0.08);
}

.progress-fill {
  height: 100%;
  background: transparent;
  transition: all 0.5s cubic-bezier(0.4, 0, 0.2, 1);
}

.progress-fill.filled {
  background: #34c759;
}

.step-icon {
  width: 36px;
  height: 36px;
  border-radius: 12px;
  background: rgba(0, 0, 0, 0.04);
  display: flex;
  align-items: center;
  justify-content: center;
  margin-bottom: 8px;
  color: #86868b;
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  position: relative;
  z-index: 1;
}

.theme-dark .step-icon {
  background: rgba(255, 255, 255, 0.06);
  color: #98989d;
}

.step.active .step-icon {
  background: #007aff;
  color: white;
  transform: scale(1.1);
  box-shadow: 0 4px 12px rgba(0, 122, 255, 0.3);
  z-index: 2;
}

.step.completed .step-icon {
  background: #34c759;
  color: white;
  z-index: 2;
}

.step-label {
  font-size: 11px;
  font-weight: 500;
  color: #86868b;
  text-align: center;
  letter-spacing: 0.01em;
  position: relative;
  z-index: 1;
}

.theme-dark .step-label {
  color: #98989d;
}

.step.active .step-label {
  color: #007aff;
  font-weight: 600;
}

.step.completed .step-label {
  color: #34c759;
}

/* Content */
.content {
  flex: 1;
  overflow-y: auto;
  padding-bottom: 100px;
}

/* Error Toast */
.error-toast {
  position: fixed;
  bottom: 100px;
  left: 16px;
  right: 16px;
  background: #ff3b30;
  color: white;
  padding: 14px 16px;
  border-radius: 14px;
  display: flex;
  align-items: center;
  gap: 12px;
  box-shadow: 0 8px 32px rgba(255, 59, 48, 0.4);
  z-index: 200;
}

.error-toast span {
  flex: 1;
  font-size: 14px;
  font-weight: 500;
}

.toast-close {
  background: rgba(255, 255, 255, 0.2);
  border: none;
  width: 28px;
  height: 28px;
  border-radius: 8px;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  color: white;
}

/* Footer */
.footer {
  position: fixed;
  bottom: 0;
  left: 0;
  right: 0;
  background: rgba(255, 255, 255, 0.95);
  backdrop-filter: blur(20px);
  -webkit-backdrop-filter: blur(20px);
  padding: 16px 20px;
  padding-bottom: max(16px, env(safe-area-inset-bottom));
  display: flex;
  align-items: center;
  gap: 12px;
  border-top: 1px solid rgba(0, 0, 0, 0.06);
  z-index: 50;
}

.theme-dark .footer {
  background: rgba(28, 28, 30, 0.95);
  border-color: rgba(255, 255, 255, 0.08);
}

.footer-summary {
  flex: 1;
  text-align: center;
}

.footer-summary .price {
  display: block;
  font-size: 20px;
  font-weight: 600;
  color: #007aff;
  letter-spacing: -0.02em;
}

.footer-summary .duration {
  font-size: 12px;
  color: #86868b;
}

.theme-dark .footer-summary .duration {
  color: #98989d;
}

/* Buttons */
.btn {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 6px;
  padding: 14px 20px;
  border-radius: 14px;
  font-size: 16px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
  border: none;
  font-family: inherit;
  letter-spacing: -0.01em;
}

.btn-secondary {
  background: rgba(0, 0, 0, 0.04);
  color: #1d1d1f;
}

.theme-dark .btn-secondary {
  background: rgba(255, 255, 255, 0.08);
  color: #f5f5f7;
}

.btn-secondary:hover:not(:disabled) {
  background: rgba(0, 0, 0, 0.08);
}

.theme-dark .btn-secondary:hover:not(:disabled) {
  background: rgba(255, 255, 255, 0.12);
}

.btn-secondary:disabled {
  opacity: 0.3;
  cursor: not-allowed;
}

.btn-primary {
  background: #007aff;
  color: white;
  flex: 1;
  box-shadow: 0 2px 8px rgba(0, 122, 255, 0.25);
}

.btn-primary:hover:not(:disabled) {
  background: #0066d6;
  transform: translateY(-1px);
  box-shadow: 0 4px 12px rgba(0, 122, 255, 0.35);
}

.btn-primary:active:not(:disabled) {
  transform: translateY(0) scale(0.98);
}

.btn-primary:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.btn .loading svg {
  animation: spin 1s linear infinite;
}

@keyframes spin {
  from { transform: rotate(0deg); }
  to { transform: rotate(360deg); }
}

/* Transitions */
.slide-enter-active {
  animation: slideIn 0.35s cubic-bezier(0.4, 0, 0.2, 1);
}

.slide-leave-active {
  animation: slideOut 0.3s cubic-bezier(0.4, 0, 1, 1);
}

@keyframes slideIn {
  from {
    opacity: 0;
    transform: translateX(24px);
  }
  to {
    opacity: 1;
    transform: translateX(0);
  }
}

@keyframes slideOut {
  from {
    opacity: 1;
    transform: translateX(0);
  }
  to {
    opacity: 0;
    transform: translateX(-24px);
  }
}

.toast-enter-active {
  animation: toastIn 0.35s cubic-bezier(0.34, 1.56, 0.64, 1);
}

.toast-leave-active {
  animation: toastOut 0.25s cubic-bezier(0.4, 0, 1, 1);
}

@keyframes toastIn {
  from {
    opacity: 0;
    transform: translateY(20px) scale(0.95);
  }
  to {
    opacity: 1;
    transform: translateY(0) scale(1);
  }
}

@keyframes toastOut {
  from {
    opacity: 1;
    transform: translateY(0) scale(1);
  }
  to {
    opacity: 0;
    transform: translateY(10px) scale(0.95);
  }
}

/* Responsive */
@media (max-width: 380px) {
  .steps-container {
    padding: 20px 16px;
    margin: 8px 12px 0;
  }
  
  .step-icon {
    width: 32px;
    height: 32px;
    border-radius: 10px;
  }
  
  .step-label {
    font-size: 10px;
  }
  
  .footer {
    padding: 12px 16px;
  }
  
  .btn {
    padding: 12px 16px;
    font-size: 15px;
  }
}
</style>
