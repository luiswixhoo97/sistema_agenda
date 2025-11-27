<template>
  <div class="paso-datos">
    <!-- Banner de Promociones - Carrusel -->
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
            class="promo-banner"
          >
            <div 
              class="promo-image" 
              :style="{ backgroundImage: `url(${promociones[indiceActual]?.imagen || ''})` }"
            >
              <div class="promo-overlay"></div>
              <div class="promo-content">
                <div class="promo-badge" v-if="promociones[indiceActual]?.descuento">
                  <span>{{ promociones[indiceActual].descuento }}</span>
                </div>
                <h2 class="promo-title">{{ promociones[indiceActual]?.nombre }}.</h2>
                <p class="promo-description">
                  {{ promociones[indiceActual]?.descripcion || 'Aprovecha esta increíble oferta y agenda tu cita ahora. ¡No te lo pierdas!' }}
                </p>
                <div class="promo-footer-info" v-if="promociones[indiceActual]?.dias_restantes !== undefined && promociones[indiceActual].dias_restantes > 0">
                  <i class="fa fa-clock"></i>
                  <span>{{ promociones[indiceActual].dias_restantes }} {{ promociones[indiceActual].dias_restantes === 1 ? 'día' : 'días' }} restantes</span>
                </div>
              </div>
            </div>
          </div>
        </Transition>
      </div>
      
      <!-- Indicadores del carrusel -->
      <div class="carousel-indicators" v-if="promociones.length > 1">
        <button
          v-for="(promo, index) in promociones"
          :key="index"
          class="indicator-dot"
          :class="{ active: index === indiceActual }"
          @click="cambiarBanner(index)"
          :aria-label="`Ir a promoción ${index + 1}`"
        ></button>
      </div>
    </div>

    <!-- Formulario -->
    <div class="form-card">
      <div class="form-title-wrapper">
        <div class="title-icon">
          <i class="fa fa-user-edit"></i>
        </div>
        <h2 class="form-title">Agendar tu cita</h2>
       
      </div>
      <div class="form-row">
        <div class="form-group">
          <label>Nombre <span class="required">*</span></label>
          <input
            type="text"
            v-model="store.datosCliente.nombre"
            placeholder="Juan"
            maxlength="50"
          />
        </div>
        <div class="form-group">
          <label>Apellido <span class="required">*</span></label>
          <input
            type="text"
            v-model="store.datosCliente.apellido"
            placeholder="Pérez"
            maxlength="50"
          />
        </div>
      </div>

      <div class="form-group">
        <label>Teléfono (10 dígitos) <span class="required">*</span></label>
        <div class="input-with-icon">
          <i class="fa fa-phone"></i>
          <input
            type="tel"
            v-model="store.datosCliente.telefono"
            placeholder="5512345678"
            maxlength="10"
            @input="onlyNumbers"
          />
        </div>
        <span class="input-hint">
          <i class="fa fa-whatsapp"></i>
          Te enviaremos un código por WhatsApp para confirmar tu cita
        </span>
      </div>

      <div class="form-group">
        <label>Email <span class="optional">(opcional)</span></label>
        <div class="input-with-icon">
          <i class="fa fa-envelope"></i>
          <input
            type="email"
            v-model="store.datosCliente.email"
            placeholder="tu@email.com"
            maxlength="100"
          />
        </div>
        <span class="input-hint">
          <i class="fa fa-info-circle"></i>
          Para enviarte confirmación y recordatorios
        </span>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { onMounted, onUnmounted, ref } from 'vue'
import { useCitasStore } from '@/stores/citas'
import catalogoService from '@/services/catalogoService'

const store = useCitasStore()

const promociones = ref<any[]>([])
const indiceActual = ref(0)
let autoPlayInterval: number | null = null

onMounted(async () => {
  await cargarPromociones()
  iniciarAutoPlay()
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
      // Si no hay promociones, mostrar promociones de ejemplo
      promociones.value = [
        {
          id: 0,
          nombre: 'Oferta Especial',
          descripcion: 'Aprovecha nuestros servicios con descuentos increíbles. Agenda tu cita ahora y disfruta de la mejor experiencia.',
          descuento: '20% OFF',
          dias_restantes: 7,
          imagen: null
        },
        {
          id: 1,
          nombre: 'Promoción de Verano',
          descripcion: 'Disfruta de tratamientos especiales con hasta 30% de descuento. Perfecto para cuidar tu piel este verano.',
          descuento: '30% OFF',
          dias_restantes: 15,
          imagen: null
        },
        {
          id: 2,
          nombre: 'Paquete Premium',
          descripcion: 'Combo especial con múltiples servicios. Ahorra más y obtén el mejor cuidado para ti.',
          descuento: '25% OFF',
          dias_restantes: 10,
          imagen: null
        }
      ]
    }
  } catch (error) {
    console.error('Error cargando promociones:', error)
    // En caso de error, mostrar promociones de ejemplo
    promociones.value = [
      {
        id: 0,
        nombre: 'Oferta Especial',
        descripcion: 'Aprovecha nuestros servicios con descuentos increíbles. Agenda tu cita ahora y disfruta de la mejor experiencia.',
        descuento: '20% OFF',
        dias_restantes: 7,
        imagen: null
      },
      {
        id: 1,
        nombre: 'Promoción de Verano',
        descripcion: 'Disfruta de tratamientos especiales con hasta 30% de descuento. Perfecto para cuidar tu piel este verano.',
        descuento: '30% OFF',
        dias_restantes: 15,
        imagen: null
      },
      {
        id: 2,
        nombre: 'Paquete Premium',
        descripcion: 'Combo especial con múltiples servicios. Ahorra más y obtén el mejor cuidado para ti.',
        descuento: '25% OFF',
        dias_restantes: 10,
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
    autoPlayInterval = window.setInterval(() => {
      siguienteBanner()
    }, 5000) // Cambia cada 5 segundos
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

function onlyNumbers(e: Event) {
  const input = e.target as HTMLInputElement
  input.value = input.value.replace(/\D/g, '')
  store.datosCliente.telefono = input.value
}
</script>

<style scoped>
.paso-datos {
  padding: 16px;
}

/* Banner de Promociones - Carrusel */
.promo-carousel {
  width: 100%;
  margin-bottom: 24px;
  position: relative;
}

.carousel-container {
  position: relative;
  width: 100%;
  overflow: hidden;
  border-radius: 24px;
}

.promo-banner {
  width: 100%;
}

.promo-image {
  position: relative;
  width: 100%;
  min-height: 220px;
  border-radius: 24px;
  overflow: hidden;
  background: linear-gradient(135deg, #ec407a 0%, #c2185b 100%);
  background-size: cover;
  background-position: center;
  box-shadow: 0 10px 30px rgba(0,0,0,0.2);
}

.promo-overlay {
  position: absolute;
  inset: 0;
  background: linear-gradient(
    180deg,
    rgba(0,0,0,0.15) 0%,
    rgba(0,0,0,0.7) 100%
  );
}

.promo-content {
  position: relative;
  min-height: 220px;
  display: flex;
  flex-direction: column;
  justify-content: flex-end;
  padding: 24px;
  z-index: 1;
}

.promo-badge {
  display: inline-block;
  background: linear-gradient(135deg, #ff6b6b, #ee5a6f);
  color: white;
  padding: 8px 16px;
  border-radius: 24px;
  font-size: 11px;
  font-weight: 800;
  letter-spacing: 0.5px;
  margin-bottom: 16px;
  box-shadow: 0 4px 12px rgba(255, 107, 107, 0.4);
  align-self: flex-start;
  text-transform: uppercase;
}

.promo-title {
  color: white;
  font-size: 28px;
  font-weight: 900;
  margin: 0 0 12px 0;
  line-height: 1.15;
  text-shadow: 0 2px 10px rgba(0,0,0,0.4);
  letter-spacing: -0.5px;
}

.promo-description {
  color: rgba(255,255,255,0.95);
  font-size: 15px;
  line-height: 1.6;
  margin: 0 0 16px 0;
  text-shadow: 0 1px 4px rgba(0,0,0,0.3);
  max-width: 85%;
}

.promo-footer-info {
  display: flex;
  align-items: center;
  gap: 8px;
  color: rgba(255,255,255,0.9);
  font-size: 13px;
  font-weight: 600;
  margin-top: 4px;
}

.promo-footer-info i {
  font-size: 12px;
  opacity: 0.8;
}

/* Fallback si no hay imagen */
.promo-image:not([style*="background-image"]) {
  background: linear-gradient(135deg, #ec407a 0%, #c2185b 50%, #7b1fa2 100%);
}

.theme-dark .promo-image {
  box-shadow: 0 10px 30px rgba(0,0,0,0.4);
}

/* Indicadores del carrusel */
.carousel-indicators {
  display: flex;
  justify-content: center;
  align-items: center;
  gap: 8px;
  margin-top: 16px;
  padding: 0 16px;
}

.indicator-dot {
  width: 8px;
  height: 8px;
  border-radius: 50%;
  border: none;
  background: rgba(0, 0, 0, 0.2);
  cursor: pointer;
  transition: all 0.3s ease;
  padding: 0;
  flex-shrink: 0;
}

.theme-dark .indicator-dot {
  background: rgba(255, 255, 255, 0.3);
}

.indicator-dot:hover {
  background: rgba(236, 64, 122, 0.5);
  transform: scale(1.2);
}

.indicator-dot.active {
  width: 24px;
  height: 8px;
  border-radius: 4px;
  background: linear-gradient(90deg, #ec407a, #c2185b);
  box-shadow: 0 2px 8px rgba(236, 64, 122, 0.4);
}

/* Transiciones del carrusel */
.slide-enter-active {
  transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
}

.slide-leave-active {
  transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
}

.slide-enter-from {
  opacity: 0;
  transform: translateX(30px);
}

.slide-leave-to {
  opacity: 0;
  transform: translateX(-30px);
}

/* Form Card */
.form-card {
  background: white;
  border-radius: 20px;
  padding: 24px;
  box-shadow: 0 4px 20px rgba(0,0,0,0.08);
  border: 1px solid rgba(0,0,0,0.04);
}

.theme-dark .form-card {
  background: #1e1e1e;
  border-color: rgba(255,255,255,0.08);
  box-shadow: 0 4px 20px rgba(0,0,0,0.3);
}

/* Form Title Wrapper */
.form-title-wrapper {
  display: flex;
  align-items: center;
  gap: 14px;
  margin-bottom: 24px;
  padding-bottom: 18px;
  border-bottom: 2px solid #f0f0f0;
}

.theme-dark .form-title-wrapper {
  border-bottom-color: rgba(255,255,255,0.08);
}

.title-icon {
  width: 48px;
  height: 48px;
  border-radius: 12px;
  background: linear-gradient(135deg, rgba(236, 64, 122, 0.1), rgba(236, 64, 122, 0.15));
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
  transition: all 0.3s ease;
}

.title-icon i {
  font-size: 20px;
  color: #ec407a;
}

/* Form Title */
.form-title {
  font-size: 24px;
  font-weight: 800;
  color: #1a1a1a;
  margin: 0;
  padding: 0;
  letter-spacing: -0.4px;
  line-height: 1.3;
  flex: 1;
  background: linear-gradient(135deg, #1a1a1a 0%, #2d2d2d 100%);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
}

.theme-dark .form-title {
  background: linear-gradient(135deg, #fff 0%, #e0e0e0 100%);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
}

.title-divider {
  height: 3px;
  width: 50px;
  background: linear-gradient(90deg, #ec407a, #c2185b);
  border-radius: 2px;
  flex-shrink: 0;
}

.form-row {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 12px;
  margin-bottom: 20px;
}

.form-group {
  margin-bottom: 20px;
}

.form-group:last-child {
  margin-bottom: 0;
}

.form-group label {
  display: block;
  font-size: 14px;
  font-weight: 600;
  color: #2d2d2d;
  margin-bottom: 10px;
  letter-spacing: -0.1px;
}

.theme-dark .form-group label {
  color: #e0e0e0;
}

.form-group label .required {
  color: #ec407a;
  font-weight: 700;
  margin-left: 2px;
}

.form-group label .optional {
  color: #999;
  font-weight: 400;
  font-size: 13px;
  margin-left: 4px;
}

.theme-dark .form-group label .optional {
  color: #888;
}

.form-group input {
  width: 100%;
  padding: 16px 18px;
  border: 2px solid #e5e5e5;
  border-radius: 14px;
  font-size: 16px;
  background: #f8f8f8;
  color: #1a1a1a;
  transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
  font-weight: 500;
  box-sizing: border-box;
}

.theme-dark .form-group input {
  background: #2a2a2a;
  border-color: #3a3a3a;
  color: #fff;
}

.form-group input:hover {
  border-color: #d0d0d0;
  background: #f5f5f5;
}

.theme-dark .form-group input:hover {
  border-color: #4a4a4a;
  background: #2f2f2f;
}

.form-group input:focus {
  outline: none;
  border-color: #ec407a;
  background: white;
  box-shadow: 0 0 0 4px rgba(236, 64, 122, 0.12);
  transform: translateY(-1px);
}

.theme-dark .form-group input:focus {
  background: #333;
  border-color: #ec407a;
  box-shadow: 0 0 0 4px rgba(236, 64, 122, 0.2);
}

.form-group input::placeholder {
  color: #aaa;
  font-weight: 400;
}

.theme-dark .form-group input::placeholder {
  color: #666;
}

/* Input with icon */
.input-with-icon {
  position: relative;
}

.input-with-icon i {
  position: absolute;
  left: 18px;
  top: 50%;
  transform: translateY(-50%);
  color: #999;
  font-size: 16px;
  z-index: 1;
  transition: color 0.2s;
}

.theme-dark .input-with-icon i {
  color: #777;
}

.input-with-icon:focus-within i {
  color: #ec407a;
}

.input-with-icon input {
  padding-left: 50px;
}

/* Input hint */
.input-hint {
  display: flex;
  align-items: flex-start;
  gap: 8px;
  margin-top: 10px;
  font-size: 13px;
  color: #666;
  line-height: 1.5;
  padding-left: 2px;
}

.theme-dark .input-hint {
  color: #999;
}

.input-hint i {
  font-size: 14px;
  margin-top: 2px;
  flex-shrink: 0;
}

.input-hint i.fa-whatsapp {
  color: #25d366;
}

.input-hint i.fa-info-circle {
  color: #6366f1;
}

/* Responsive */
@media (max-width: 480px) {
  .form-card {
    padding: 20px;
    border-radius: 18px;
  }

  .form-title-wrapper {
    margin-bottom: 15px;
    padding-bottom: 12px;
    gap: 12px;
  }

  .title-icon {
    width: 40px;
    height: 40px;
  }

  .title-icon i {
    font-size: 18px;
  }

  .form-title {
    font-size: 20px;
  }

  .title-divider {
    width: 40px;
    height: 2px;
  }

  .form-row {
    grid-template-columns: 1fr 1fr;
    gap: 10px;
    margin-bottom: 1px;
  }

  .form-group {
    margin-bottom: 10px;
  }

  .form-group input {
    padding: 12px 16px;
    font-size: 13px;
  }

  .input-with-icon input {
    padding-left: 48px;
  }

  .input-with-icon i {
    left: 16px;
    font-size: 15px;
  }

  .validation-card {
    padding: 16px 18px;
    gap: 14px;
  }

  .validation-icon {
    width: 44px;
    height: 44px;
  }
}
</style>
