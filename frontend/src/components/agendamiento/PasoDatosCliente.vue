<template>
  <div class="paso-datos">
    <!-- Promotions Carousel -->
    <div 
      v-if="promociones.length > 0" 
      class="promo-carousel"
      @mouseenter="detenerAutoPlay"
      @mouseleave="iniciarAutoPlay"
    >
      <div class="carousel-container">
        <Transition name="slide" mode="out-in">
          <div 
            :key="indiceActual" 
            class="promo-card"
            @click="seleccionarPromocion(promociones[indiceActual])"
            :class="{ 'selected': promocionSeleccionada?.id === promociones[indiceActual]?.id }"
          >
            <div 
              class="promo-bg" 
              :style="{ backgroundImage: promociones[indiceActual]?.imagen ? `url(${promociones[indiceActual].imagen})` : 'none' }"
            >
              <div class="promo-gradient"></div>
              
              <!-- Selected Badge -->
              <div v-if="promocionSeleccionada?.id === promociones[indiceActual]?.id" class="promo-selected">
                <AppIcons name="check-circle" :size="16" />
                <span>Seleccionada</span>
              </div>

              <!-- Content -->
              <div class="promo-content">
                <div class="promo-header">
                  <h2>{{ promociones[indiceActual]?.nombre }}</h2>
                  <div v-if="promociones[indiceActual]?.descuento" class="promo-discount">
                    {{ promociones[indiceActual].descuento }}
                  </div>
                </div>
                
                <p class="promo-desc">{{ promociones[indiceActual]?.descripcion }}</p>
                
                <div v-if="promociones[indiceActual]?.servicios_info?.length" class="promo-services">
                  <AppIcons name="scissors" :size="14" />
                  <span>{{ promociones[indiceActual].servicios_info.length }} servicio{{ promociones[indiceActual].servicios_info.length > 1 ? 's' : '' }}</span>
                  <span v-if="promociones[indiceActual]?.tiempo_total" class="promo-time">
                    · {{ promociones[indiceActual].tiempo_total }} min
                  </span>
                </div>

                <div v-if="promociones[indiceActual]?.dias_restantes > 0 || promociones[indiceActual]?.horas_restantes > 0" class="promo-expires">
                  <AppIcons name="clock" :size="14" />
                  <span>{{ formatearTiempoRestante(promociones[indiceActual]) }}</span>
                </div>
              </div>
            </div>
          </div>
        </Transition>
      </div>
      
      <!-- Carousel Dots -->
      <div v-if="promociones.length > 1" class="carousel-dots">
        <button
          v-for="(_, index) in promociones"
          :key="index"
          class="dot"
          :class="{ 'active': index === indiceActual }"
          @click="cambiarBanner(index)"
        ></button>
      </div>
    </div>

    <!-- Form -->
    <div class="form-section">
      <div class="section-header">
        <div class="section-icon">
          <AppIcons name="edit" :size="20" />
        </div>
        <div class="section-text">
          <h2>Agendar tu cita</h2>
          <p>Ingresa tus datos de contacto</p>
        </div>
      </div>

      <div class="form-fields">
        <!-- Phone -->
        <div class="field">
          <label>
            Teléfono
            <span class="required">*</span>
          </label>
          <div class="input-wrapper">
            <AppIcons name="phone" :size="18" class="input-icon" />
            <input
              type="tel"
              v-model="store.datosCliente.telefono"
              placeholder="10 dígitos"
              maxlength="10"
              @input="onTelefonoInput"
            />
            <AppIcons v-if="buscandoCliente" name="loader" :size="18" class="input-loading" />
          </div>
          <span class="field-hint">
            <AppIcons name="whatsapp" :size="14" />
            Te enviaremos confirmación por WhatsApp
          </span>
        </div>

        <!-- Name -->
        <div class="field">
          <label>
            Nombre completo
            <span class="required">*</span>
          </label>
          <div class="input-wrapper">
            <AppIcons name="user" :size="18" class="input-icon" />
            <input
              type="text"
              v-model="store.datosCliente.nombre"
              placeholder="Tu nombre"
              maxlength="100"
            />
          </div>
        </div>

        <!-- Email -->
        <div class="field">
          <label>
            Email
            <span class="optional">(opcional)</span>
          </label>
          <div class="input-wrapper">
            <AppIcons name="mail" :size="18" class="input-icon" />
            <input
              type="email"
              v-model="store.datosCliente.email"
              placeholder="tu@email.com"
              maxlength="100"
            />
          </div>
          <span class="field-hint">
            <AppIcons name="info" :size="14" />
            Para enviarte confirmación y recordatorios
          </span>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { onMounted, onUnmounted, ref, computed } from 'vue'
import { useCitasStore } from '@/stores/citas'
import catalogoService from '@/services/catalogoService'
import AppIcons from './AppIcons.vue'

const store = useCitasStore()

const promociones = ref<any[]>([])
const indiceActual = ref(0)
let autoPlayInterval: number | null = null
const buscandoCliente = ref(false)

const STORAGE_KEY_TELEFONO = 'sistema_agenda_telefono_cliente'

const promocionSeleccionada = computed(() => {
  if (!store.promocionSeleccionada) return null
  return promociones.value.find(p => p.id === store.promocionSeleccionada) || null
})

onMounted(async () => {
  await cargarPromociones()
  iniciarAutoPlay()
  await cargarTelefonoGuardado()
})

onUnmounted(() => {
  detenerAutoPlay()
})

async function cargarPromociones() {
  try {
    const data = await catalogoService.obtenerPromociones()
    const promos = data || []
    if (promos.length > 0) {
      promociones.value = promos
    } else {
      promociones.value = [
        {
          id: 0,
          nombre: 'Oferta Especial',
          descripcion: 'Aprovecha nuestros servicios con descuentos increíbles.',
          descuento: '20% OFF',
          dias_restantes: 7,
          imagen: null
        }
      ]
    }
  } catch (error) {
    console.error('Error cargando promociones:', error)
    promociones.value = [
      {
        id: 0,
        nombre: 'Oferta Especial',
        descripcion: 'Aprovecha nuestros servicios con descuentos increíbles.',
        descuento: '20% OFF',
        dias_restantes: 7,
        imagen: null
      }
    ]
  }
}

function cambiarBanner(index: number) {
  indiceActual.value = index
  reiniciarAutoPlay()
}

function siguienteBanner() {
  indiceActual.value = (indiceActual.value + 1) % promociones.value.length
}

function iniciarAutoPlay() {
  if (promociones.value.length > 1) {
    autoPlayInterval = window.setInterval(siguienteBanner, 5000)
  }
}

function detenerAutoPlay() {
  if (autoPlayInterval) {
    clearInterval(autoPlayInterval)
    autoPlayInterval = null
  }
}

function reiniciarAutoPlay() {
  detenerAutoPlay()
  iniciarAutoPlay()
}

async function seleccionarPromocion(promo: any) {
  if (promo && promo.id !== undefined) {
    if (store.promocionSeleccionada === promo.id) {
      store.seleccionarPromocion(null)
      store.serviciosSeleccionados = []
    } else {
      store.seleccionarPromocion(promo.id, promo)
      
      if (promo.servicios_info && promo.servicios_info.length > 0) {
        store.serviciosSeleccionados = []
        const todosServicios = await catalogoService.obtenerServicios()
        
        for (const servicioInfo of promo.servicios_info) {
          const servicioCompleto = todosServicios.find((s: any) => s.id === servicioInfo.id)
          if (servicioCompleto) {
            store.agregarServicio({
              id: servicioCompleto.id,
              nombre: servicioCompleto.nombre,
              precio: servicioCompleto.precio,
              duracion: servicioCompleto.duracion,
            })
          }
        }
        store.seleccionarCategoria(999999)
      }
    }
  }
}

function formatearTiempoRestante(promo: any): string {
  if (!promo) return ''
  
  const dias = promo.dias_restantes || 0
  const horas = promo.horas_restantes || 0
  const minutos = promo.minutos_restantes || 0
  
  if (dias === 0 && horas === 0 && minutos === 0) return 'Finaliza hoy'
  
  const partes: string[] = []
  if (dias > 0) partes.push(`${dias}d`)
  if (horas > 0) partes.push(`${horas}h`)
  if (minutos > 0 && dias === 0) partes.push(`${minutos}m`)
  
  return partes.join(' ') + ' restantes'
}

async function onTelefonoInput(e: Event) {
  const input = e.target as HTMLInputElement
  input.value = input.value.replace(/\D/g, '')
  store.datosCliente.telefono = input.value

  if (input.value.length === 10) {
    await buscarClientePorTelefono(input.value)
  }
}

async function buscarClientePorTelefono(telefono: string) {
  if (buscandoCliente.value) return
  
  buscandoCliente.value = true
  try {
    const resultado = await catalogoService.buscarClientePorTelefono(telefono)
    
    if (resultado.success && resultado.data) {
      store.datosCliente.nombre = resultado.data.nombre.trim()
      store.datosCliente.apellido = ''
      if (resultado.data.email) {
        store.datosCliente.email = resultado.data.email
      }
      if (telefono.length === 10) {
        localStorage.setItem(STORAGE_KEY_TELEFONO, telefono)
      }
    }
  } catch (error) {
    console.error('Error buscando cliente:', error)
  } finally {
    buscandoCliente.value = false
  }
}

async function cargarTelefonoGuardado() {
  try {
    const telefonoGuardado = localStorage.getItem(STORAGE_KEY_TELEFONO)
    if (telefonoGuardado && telefonoGuardado.length === 10) {
      store.datosCliente.telefono = telefonoGuardado
      await buscarClientePorTelefono(telefonoGuardado)
    }
  } catch (error) {
    console.error('Error cargando teléfono guardado:', error)
    localStorage.removeItem(STORAGE_KEY_TELEFONO)
  }
}
</script>

<style scoped>
.paso-datos {
  padding: 20px 16px;
  font-family: -apple-system, BlinkMacSystemFont, 'SF Pro Display', system-ui, sans-serif;
  -webkit-font-smoothing: antialiased;
}

/* Promotions Carousel */
.promo-carousel {
  margin-bottom: 24px;
}

.carousel-container {
  position: relative;
  height: 200px;
  overflow: hidden;
  border-radius: 20px;
}

.promo-card {
  position: absolute;
  inset: 0;
  cursor: pointer;
  transition: transform 0.2s;
}

.promo-card:active {
  transform: scale(0.98);
}

.promo-card.selected {
  outline: 2px solid #007aff;
  outline-offset: 3px;
  border-radius: 24px;
}

.promo-bg {
  position: relative;
  height: 100%;
  background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%);
  background-size: cover;
  background-position: center;
  border-radius: 20px;
  overflow: hidden;
}

.promo-gradient {
  position: absolute;
  inset: 0;
  background: linear-gradient(180deg, rgba(0,0,0,0.1) 0%, rgba(0,0,0,0.7) 100%);
}

.promo-selected {
  position: absolute;
  top: 12px;
  right: 12px;
  background: #34c759;
  color: white;
  padding: 6px 12px;
  border-radius: 20px;
  display: flex;
  align-items: center;
  gap: 6px;
  font-size: 12px;
  font-weight: 600;
  z-index: 2;
}

.promo-content {
  position: relative;
  height: 100%;
  display: flex;
  flex-direction: column;
  justify-content: flex-end;
  padding: 20px;
  z-index: 1;
}

.promo-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
  margin-bottom: 8px;
}

.promo-header h2 {
  color: white;
  font-size: 22px;
  font-weight: 700;
  margin: 0;
  letter-spacing: -0.02em;
}

.promo-discount {
  background: rgba(255,255,255,0.2);
  backdrop-filter: blur(10px);
  color: white;
  padding: 6px 12px;
  border-radius: 8px;
  font-size: 13px;
  font-weight: 700;
  letter-spacing: 0.02em;
}

.promo-desc {
  color: rgba(255,255,255,0.85);
  font-size: 14px;
  font-weight: 400;
  line-height: 1.4;
  margin: 0 0 12px;
}

.promo-services,
.promo-expires {
  display: flex;
  align-items: center;
  gap: 6px;
  color: rgba(255,255,255,0.75);
  font-size: 12px;
  font-weight: 500;
}

.promo-services {
  margin-bottom: 4px;
}

.promo-time {
  opacity: 0.8;
}

/* Carousel Dots */
.carousel-dots {
  display: flex;
  justify-content: center;
  gap: 8px;
  margin-top: 14px;
}

.dot {
  width: 6px;
  height: 6px;
  border-radius: 3px;
  background: rgba(0,0,0,0.15);
  border: none;
  padding: 0;
  cursor: pointer;
  transition: all 0.3s;
}

.theme-dark .dot {
  background: rgba(255,255,255,0.2);
}

.dot.active {
  width: 20px;
  background: #007aff;
}

/* Slide Transition */
.slide-enter-active,
.slide-leave-active {
  transition: all 0.35s cubic-bezier(0.4, 0, 0.2, 1);
}

.slide-enter-from {
  opacity: 0;
  transform: translateX(20px);
}

.slide-leave-to {
  opacity: 0;
  transform: translateX(-20px);
}

/* Form Section */
.form-section {
  background: rgba(255, 255, 255, 0.95);
  backdrop-filter: blur(20px);
  -webkit-backdrop-filter: blur(20px);
  border-radius: 20px;
  padding: 24px;
  border: 1px solid rgba(0,0,0,0.04);
  box-shadow: 0 2px 20px rgba(0,0,0,0.04);
}

.theme-dark .form-section {
  background: rgba(28, 28, 30, 0.95);
  border-color: rgba(255,255,255,0.06);
  box-shadow: 0 2px 20px rgba(0,0,0,0.3);
}

.section-header {
  display: flex;
  align-items: center;
  gap: 14px;
  margin-bottom: 24px;
  padding-bottom: 20px;
  border-bottom: 1px solid rgba(0,0,0,0.06);
}

.theme-dark .section-header {
  border-color: rgba(255,255,255,0.08);
}

.section-icon {
  width: 44px;
  height: 44px;
  border-radius: 12px;
  background: #007aff;
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
  flex-shrink: 0;
}

.section-text h2 {
  font-size: 20px;
  font-weight: 600;
  color: #1d1d1f;
  margin: 0 0 4px;
  letter-spacing: -0.02em;
}

.theme-dark .section-text h2 {
  color: #f5f5f7;
}

.section-text p {
  font-size: 14px;
  color: #86868b;
  margin: 0;
}

.theme-dark .section-text p {
  color: #98989d;
}

/* Form Fields */
.form-fields {
  display: flex;
  flex-direction: column;
  gap: 20px;
}

.field label {
  display: block;
  font-size: 14px;
  font-weight: 600;
  color: #1d1d1f;
  margin-bottom: 8px;
  letter-spacing: -0.01em;
}

.theme-dark .field label {
  color: #f5f5f7;
}

.field .required {
  color: #ff3b30;
  margin-left: 2px;
}

.field .optional {
  color: #86868b;
  font-weight: 400;
  font-size: 13px;
  margin-left: 4px;
}

.theme-dark .field .optional {
  color: #98989d;
}

/* Input Wrapper */
.input-wrapper {
  position: relative;
  display: flex;
  align-items: center;
}

.input-icon {
  position: absolute;
  left: 14px;
  color: #86868b;
  pointer-events: none;
  transition: color 0.2s;
}

.theme-dark .input-icon {
  color: #98989d;
}

.input-wrapper:focus-within .input-icon {
  color: #007aff;
}

.input-loading {
  position: absolute;
  right: 14px;
  color: #007aff;
}

.input-wrapper input {
  width: 100%;
  padding: 14px 14px 14px 44px;
  border: 1px solid rgba(0,0,0,0.12);
  border-radius: 12px;
  font-size: 16px;
  font-family: inherit;
  background: white;
  color: #1d1d1f;
  transition: all 0.2s;
}

.theme-dark .input-wrapper input {
  background: rgba(44,44,46,1);
  border-color: rgba(255,255,255,0.12);
  color: #f5f5f7;
}

.input-wrapper input:hover {
  border-color: rgba(0,0,0,0.2);
}

.theme-dark .input-wrapper input:hover {
  border-color: rgba(255,255,255,0.2);
}

.input-wrapper input:focus {
  outline: none;
  border-color: #007aff;
  box-shadow: 0 0 0 3px rgba(0,122,255,0.1);
}

.theme-dark .input-wrapper input:focus {
  box-shadow: 0 0 0 3px rgba(0,122,255,0.2);
}

.input-wrapper input::placeholder {
  color: #a1a1a6;
}

.theme-dark .input-wrapper input::placeholder {
  color: #6e6e73;
}

/* Field Hint */
.field-hint {
  display: flex;
  align-items: center;
  gap: 6px;
  margin-top: 8px;
  font-size: 13px;
  color: #86868b;
}

.theme-dark .field-hint {
  color: #98989d;
}

.field-hint svg {
  flex-shrink: 0;
  color: #007aff;
}

/* Responsive */
@media (max-width: 380px) {
  .paso-datos {
    padding: 16px 12px;
  }
  
  .carousel-container {
    height: 180px;
  }
  
  .promo-header h2 {
    font-size: 20px;
  }
  
  .form-section {
    padding: 20px;
  }
  
  .section-icon {
    width: 40px;
    height: 40px;
  }
  
  .section-text h2 {
    font-size: 18px;
  }
}
</style>
