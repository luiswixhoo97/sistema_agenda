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
            @click="seleccionarPromocion(promociones[indiceActual])"
            :class="{ 'selected': promocionSeleccionada?.id === promociones[indiceActual]?.id }"
          >
            <div 
              class="promo-image" 
              :style="{ backgroundImage: `url(${promociones[indiceActual]?.imagen || ''})` }"
            >
              <div class="promo-overlay"></div>
              <div class="promo-content">
                <div class="promo-selected-indicator" v-if="promocionSeleccionada?.id === promociones[indiceActual]?.id">
                  <i class="fa fa-check-circle"></i>
                  <span>Promoción seleccionada</span>
                </div>
                <div class="promo-header">
                  <h2 class="promo-title">{{ promociones[indiceActual]?.nombre }}.</h2>
                  <div class="promo-badge" v-if="promociones[indiceActual]?.descuento">
                    <span>{{ promociones[indiceActual].descuento }}</span>
                  </div>
                </div>
                <p class="promo-description">
                  {{ promociones[indiceActual]?.descripcion || 'Aprovecha esta increíble oferta y agenda tu cita ahora. ¡No te lo pierdas!' }}
                </p>
                <div v-if="promociones[indiceActual]?.servicios_info && promociones[indiceActual].servicios_info.length > 0" class="promo-servicios-info">
                  <div class="promo-servicios-count">
                    <i class="fa fa-cut"></i>
                    <span>{{ promociones[indiceActual].servicios_info.length }} servicio(s)</span>
                    <span v-if="promociones[indiceActual]?.tiempo_total" class="promo-tiempo">
                      • {{ promociones[indiceActual].tiempo_total }} min
                    </span>
                  </div>
                  <div class="promo-servicios-nombres">
                    {{ promociones[indiceActual].servicios_info.map((s: any) => s.nombre).join(', ') }}
                  </div>
                </div>
                <div class="promo-footer-info" v-if="promociones[indiceActual]?.dias_restantes !== undefined && (promociones[indiceActual].dias_restantes > 0 || promociones[indiceActual].horas_restantes > 0 || promociones[indiceActual].minutos_restantes > 0)">
                  <i class="fa fa-clock"></i>
                  <span>{{ formatearTiempoRestante(promociones[indiceActual]) }}</span>
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
      <div class="form-group">
        <label>Teléfono (10 dígitos) <span class="required">*</span></label>
        <div class="input-with-icon">
          <i class="fa fa-phone"></i>
          <input
            type="tel"
            v-model="store.datosCliente.telefono"
            placeholder="5512345678"
            maxlength="10"
            @input="onTelefonoInput"
          />
        </div>
        <span class="input-hint">
          <i class="fab fa-whatsapp"></i>
          Te enviaremos un código por WhatsApp para confirmar tu cita
        </span>
        <span v-if="buscandoCliente" class="input-hint loading">
          <i class="fa fa-spinner fa-spin"></i>
          Buscando cliente...
        </span>
      </div>

      <div class="form-group">
        <label>Nombre completo <span class="required">*</span></label>
        <input
          type="text"
          v-model="store.datosCliente.nombre"
          placeholder="Juan Pérez"
          maxlength="100"
        />
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
import { onMounted, onUnmounted, ref, computed } from 'vue'
import { useCitasStore } from '@/stores/citas'
import catalogoService from '@/services/catalogoService'
import './PasoDatosCliente.css'

const store = useCitasStore()

const promociones = ref<any[]>([])
const indiceActual = ref(0)
let autoPlayInterval: number | null = null
const buscandoCliente = ref(false)

// Usar el store para la promoción seleccionada en lugar de un ref local
const promocionSeleccionada = computed(() => {
  if (!store.promocionSeleccionada) return null
  return promociones.value.find(p => p.id === store.promocionSeleccionada) || null
})

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

async function seleccionarPromocion(promo: any) {
  if (promo && promo.id !== undefined) {
    // Si ya está seleccionada, deseleccionarla
    if (store.promocionSeleccionada === promo.id) {
      store.seleccionarPromocion(null)
      store.serviciosSeleccionados = []
    } else {
      // Pasar la información completa de la promoción (incluyendo servicios)
      store.seleccionarPromocion(promo.id, promo)
      
      // Pre-seleccionar los servicios de la promoción automáticamente
      if (promo.servicios_info && promo.servicios_info.length > 0) {
        // Limpiar servicios anteriores
        store.serviciosSeleccionados = []
        
        // Cargar todos los servicios para obtener información completa
        const todosServicios = await catalogoService.obtenerServicios()
        
        // Agregar servicios de la promoción
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
        
        // Seleccionar la categoría de promociones (ID 999999) para que al ir al paso 2 ya esté seleccionada
        store.seleccionarCategoria(999999)
        
        // NO avanzar automáticamente - el usuario debe completar sus datos primero
        // Cuando presione "Siguiente", irá al paso 2 con los servicios ya seleccionados
      }
    }
  }
}

function formatearTiempoRestante(promo: any): string {
  if (!promo) return ''
  
  const dias = promo.dias_restantes || 0
  const horas = promo.horas_restantes || 0
  const minutos = promo.minutos_restantes || 0
  
  if (dias === 0 && horas === 0 && minutos === 0) {
    return 'Finaliza hoy'
  }
  
  const partes: string[] = []
  
  if (dias > 0) {
    partes.push(`${dias} ${dias === 1 ? 'día' : 'días'}`)
  }
  
  if (horas > 0) {
    partes.push(`${horas} ${horas === 1 ? 'hora' : 'horas'}`)
  }
  
  if (minutos > 0 && dias === 0) {
    // Solo mostrar minutos si no hay días
    partes.push(`${minutos} ${minutos === 1 ? 'minuto' : 'minutos'}`)
  }
  
  if (partes.length === 0) {
    return 'Finaliza pronto'
  }
  
  return partes.join(', ') + ' restantes'
}

async function onTelefonoInput(e: Event) {
  const input = e.target as HTMLInputElement
  input.value = input.value.replace(/\D/g, '')
  store.datosCliente.telefono = input.value

  // Si tiene 10 dígitos, buscar el cliente
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
      // Llenar nombre completo (sin separar)
      store.datosCliente.nombre = resultado.data.nombre.trim()
      store.datosCliente.apellido = '' // Dejar apellido vacío ya que no está separado en BD
      
      // Llenar email si existe
      if (resultado.data.email) {
        store.datosCliente.email = resultado.data.email
      }
    }
  } catch (error) {
    console.error('Error buscando cliente:', error)
    // No mostrar error al usuario, simplemente no llenar los campos
  } finally {
    buscandoCliente.value = false
  }
}
</script>
