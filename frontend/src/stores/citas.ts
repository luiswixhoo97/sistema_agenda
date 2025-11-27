import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import citaService, { type CitaResponse, type AgendarCitaRequest } from '@/services/citaService'
import disponibilidadService, { 
  type DiaConDisponibilidad, 
  type SlotsResponse,
  type CalculoServicioResponse 
} from '@/services/disponibilidadService'
import catalogoService, { 
  type CategoriaConServicios, 
  type ServicioPublico, 
  type EmpleadoPublico 
} from '@/services/catalogoService'

export interface ServicioSeleccionado {
  id: number
  nombre: string
  precio: number
  duracion: number
}

export interface EmpleadoSeleccionado {
  id: number
  nombre: string
  foto?: string
}

export interface DatosCliente {
  nombre: string
  apellido: string
  telefono: string
  email: string
}

export const useCitasStore = defineStore('citas', () => {
  // Estado del catálogo
  const categorias = ref<CategoriaConServicios[]>([])
  const servicios = ref<ServicioPublico[]>([])
  const empleados = ref<EmpleadoPublico[]>([])
  const categoriaActiva = ref<number | null>(null)
  const loadingCatalogo = ref(false)
  
  // Estado del proceso de agendamiento (5 pasos)
  // 1: Datos cliente, 2: Servicios, 3: Empleado, 4: Fecha/Hora, 5: Confirmación+OTP
  const paso = ref(1)
  
  // Datos del cliente (paso 1)
  const datosCliente = ref<DatosCliente>({
    nombre: '',
    apellido: '',
    telefono: '',
    email: ''
  })
  
  // OTP para confirmación
  const otpEnviado = ref(false)
  const otpCodigo = ref('')
  const otpDebug = ref('') // Solo desarrollo
  
  const serviciosSeleccionados = ref<ServicioSeleccionado[]>([])
  const empleadoSeleccionado = ref<EmpleadoSeleccionado | null>(null)
  const fechaSeleccionada = ref<string | null>(null)
  const horaSeleccionada = ref<string | null>(null)
  const notas = ref('')
  
  // Estado de disponibilidad
  const diasDisponibles = ref<DiaConDisponibilidad[]>([])
  const slotsDisponibles = ref<SlotsResponse | null>(null)
  const calculoServicio = ref<CalculoServicioResponse | null>(null)
  
  // Estado de citas
  const misCitas = ref<CitaResponse[]>([])
  const citaActual = ref<CitaResponse | null>(null)
  
  // Estado UI
  const loading = ref(false)
  const error = ref<string | null>(null)

  // Getters
  const totalPrecio = computed(() => {
    return serviciosSeleccionados.value.reduce((sum, s) => sum + Number(s.precio), 0)
  })

  const totalDuracion = computed(() => {
    return serviciosSeleccionados.value.reduce((sum, s) => sum + s.duracion, 0)
  })

  const servicioIds = computed(() => {
    return serviciosSeleccionados.value.map(s => s.id)
  })

  const fechaHoraCompleta = computed(() => {
    if (fechaSeleccionada.value && horaSeleccionada.value) {
      return `${fechaSeleccionada.value} ${horaSeleccionada.value}:00`
    }
    return null
  })

  // Validación de datos del cliente
  const datosClienteValidos = computed(() => {
    return datosCliente.value.nombre.trim().length >= 2 &&
           datosCliente.value.apellido.trim().length >= 2 &&
           datosCliente.value.telefono.length === 10
  })

  const puedeAvanzar = computed(() => {
    switch (paso.value) {
      case 1: return datosClienteValidos.value
      case 2: return serviciosSeleccionados.value.length > 0
      case 3: return empleadoSeleccionado.value !== null
      case 4: return fechaSeleccionada.value !== null && horaSeleccionada.value !== null
      default: return false
    }
  })

  // Getter - Filtrar servicios por categoría
  const serviciosFiltrados = computed(() => {
    if (!categoriaActiva.value) return servicios.value
    return servicios.value.filter(s => s.categoria.id === categoriaActiva.value)
  })

  // Actions - Cargar catálogo
  async function cargarCatalogo() {
    loadingCatalogo.value = true
    try {
      const [cats, servs] = await Promise.all([
        catalogoService.obtenerCategorias(),
        catalogoService.obtenerServicios(),
      ])
      categorias.value = cats
      servicios.value = servs
    } catch (e) {
      console.error('Error cargando catálogo:', e)
      error.value = 'Error al cargar servicios'
    } finally {
      loadingCatalogo.value = false
    }
  }

  // Actions - Seleccionar categoría
  function seleccionarCategoria(categoriaId: number | null) {
    categoriaActiva.value = categoriaId
  }

  // Actions - Cargar empleados
  async function cargarEmpleados() {
    if (servicioIds.value.length === 0) {
      empleados.value = []
      return
    }

    loadingCatalogo.value = true
    try {
      const emps = await catalogoService.obtenerEmpleados(servicioIds.value[0])
      // Filtrar empleados que ofrecen todos los servicios seleccionados
      empleados.value = emps.filter(emp => {
        const serviciosEmpleado = emp.servicios.map(s => s.id)
        return servicioIds.value.every(id => serviciosEmpleado.includes(id))
      })
    } catch (e) {
      console.error('Error cargando empleados:', e)
    } finally {
      loadingCatalogo.value = false
    }
  }

  // Actions - Selección de servicios
  function agregarServicio(servicio: ServicioSeleccionado) {
    if (!serviciosSeleccionados.value.find(s => s.id === servicio.id)) {
      serviciosSeleccionados.value.push(servicio)
      calcularServicio()
    }
  }

  function quitarServicio(servicioId: number) {
    serviciosSeleccionados.value = serviciosSeleccionados.value.filter(s => s.id !== servicioId)
    if (serviciosSeleccionados.value.length > 0) {
      calcularServicio()
    } else {
      calculoServicio.value = null
    }
  }

  function limpiarServicios() {
    serviciosSeleccionados.value = []
    calculoServicio.value = null
  }

  // Actions - Calcular servicio
  async function calcularServicio() {
    if (servicioIds.value.length === 0) return

    try {
      calculoServicio.value = await disponibilidadService.calcularServicio(
        servicioIds.value,
        empleadoSeleccionado.value?.id
      )
    } catch (e) {
      console.error('Error al calcular servicio:', e)
    }
  }

  // Actions - Selección de empleado
  function seleccionarEmpleado(empleado: EmpleadoSeleccionado) {
    empleadoSeleccionado.value = empleado
    // Recalcular por si tiene precios especiales
    calcularServicio()
    // Limpiar fecha/hora si cambia empleado
    fechaSeleccionada.value = null
    horaSeleccionada.value = null
    diasDisponibles.value = []
    slotsDisponibles.value = null
  }

  // Actions - Disponibilidad
  async function cargarDiasDisponibles(mes: number, anio: number) {
    if (!empleadoSeleccionado.value || servicioIds.value.length === 0) return

    loading.value = true
    error.value = null

    try {
      diasDisponibles.value = await disponibilidadService.obtenerDiasDisponibles(
        empleadoSeleccionado.value.id,
        mes,
        anio,
        servicioIds.value
      )
    } catch (e: any) {
      error.value = e.response?.data?.message || 'Error al cargar disponibilidad'
    } finally {
      loading.value = false
    }
  }

  async function cargarSlots(fecha: string) {
    if (!empleadoSeleccionado.value || servicioIds.value.length === 0) return

    loading.value = true
    error.value = null
    fechaSeleccionada.value = fecha
    horaSeleccionada.value = null

    try {
      slotsDisponibles.value = await disponibilidadService.obtenerSlots(
        empleadoSeleccionado.value.id,
        fecha,
        servicioIds.value
      )
    } catch (e: any) {
      error.value = e.response?.data?.message || 'Error al cargar horarios'
    } finally {
      loading.value = false
    }
  }

  function seleccionarHora(hora: string) {
    horaSeleccionada.value = hora
  }

  // Actions - Navegación
  function siguientePaso() {
    if (puedeAvanzar.value && paso.value < 5) {
      paso.value++
    }
  }

  function pasoAnterior() {
    if (paso.value > 1) {
      paso.value--
    }
  }

  function irAPaso(numeroPaso: number) {
    if (numeroPaso >= 1 && numeroPaso <= 5) {
      paso.value = numeroPaso
    }
  }

  // Actions - OTP
  async function enviarOtpConfirmacion(): Promise<boolean> {
    if (!datosCliente.value.telefono || datosCliente.value.telefono.length !== 10) {
      error.value = 'Teléfono inválido'
      return false
    }

    loading.value = true
    error.value = null

    try {
      const response = await citaService.enviarOtpAgendamiento(datosCliente.value.telefono)
      if (response.success) {
        otpEnviado.value = true
        otpDebug.value = response.codigo_debug || ''
        return true
      } else {
        error.value = response.message || 'Error al enviar código'
        return false
      }
    } catch (e: any) {
      error.value = e.response?.data?.message || 'Error al enviar código'
      return false
    } finally {
      loading.value = false
    }
  }

  // Actions - Agendar cita (público, sin sesión)
  async function agendarCita(): Promise<boolean> {
    if (!empleadoSeleccionado.value || !fechaHoraCompleta.value) {
      error.value = 'Datos incompletos para agendar'
      return false
    }

    if (!datosClienteValidos.value) {
      error.value = 'Datos del cliente incompletos'
      return false
    }

    if (!otpCodigo.value || otpCodigo.value.length !== 6) {
      error.value = 'Ingresa el código de verificación'
      return false
    }

    loading.value = true
    error.value = null

    try {
      // Combinar nombre y apellido
      const nombreCompleto = `${datosCliente.value.nombre.trim()} ${datosCliente.value.apellido.trim()}`

      const request = {
        // Datos del cliente
        cliente_nombre: nombreCompleto,
        cliente_telefono: datosCliente.value.telefono,
        cliente_email: datosCliente.value.email?.trim() || undefined,
        // Código OTP
        codigo_otp: otpCodigo.value,
        // Datos de la cita
        empleado_id: empleadoSeleccionado.value.id,
        servicios: servicioIds.value,
        fecha_hora: fechaHoraCompleta.value,
        notas: notas.value || undefined,
      }

      console.log('📅 Agendando cita (público):', request)

      const response = await citaService.agendarPublico(request)

      if (response.success && response.cita) {
        citaActual.value = response.cita
        return true
      } else {
        error.value = response.message || 'Error al agendar cita'
        return false
      }
    } catch (e: any) {
      console.error('❌ Error al agendar:', e.response?.data)
      if (e.response?.data?.errors) {
        const errores = Object.values(e.response.data.errors).flat().join('. ')
        error.value = errores
      } else {
        error.value = e.response?.data?.message || 'Error al agendar cita'
      }
      return false
    } finally {
      loading.value = false
    }
  }

  // Actions - Reiniciar proceso
  function reiniciarAgendamiento() {
    paso.value = 1
    datosCliente.value = { nombre: '', apellido: '', telefono: '', email: '' }
    otpEnviado.value = false
    otpCodigo.value = ''
    otpDebug.value = ''
    serviciosSeleccionados.value = []
    empleadoSeleccionado.value = null
    fechaSeleccionada.value = null
    horaSeleccionada.value = null
    notas.value = ''
    diasDisponibles.value = []
    slotsDisponibles.value = null
    calculoServicio.value = null
    citaActual.value = null
    error.value = null
  }

  // Actions - Cargar mis citas
  async function cargarMisCitas(params?: { estado?: string; desde?: string; hasta?: string }) {
    loading.value = true
    error.value = null

    try {
      const response = await citaService.misCitas(params)
      misCitas.value = response.citas
    } catch (e: any) {
      error.value = e.response?.data?.message || 'Error al cargar citas'
    } finally {
      loading.value = false
    }
  }

  // Actions - Cancelar cita
  async function cancelarCita(citaId: number, motivo?: string): Promise<boolean> {
    loading.value = true
    error.value = null

    try {
      const response = await citaService.cancelar(citaId, motivo)
      if (response.success) {
        // Actualizar lista
        await cargarMisCitas()
        return true
      } else {
        error.value = response.message
        return false
      }
    } catch (e: any) {
      error.value = e.response?.data?.message || 'Error al cancelar cita'
      return false
    } finally {
      loading.value = false
    }
  }

  function clearError() {
    error.value = null
  }

  return {
    // Estado catálogo
    categorias,
    servicios,
    empleados,
    categoriaActiva,
    loadingCatalogo,
    // Estado agendamiento
    paso,
    datosCliente,
    otpEnviado,
    otpCodigo,
    otpDebug,
    serviciosSeleccionados,
    empleadoSeleccionado,
    fechaSeleccionada,
    horaSeleccionada,
    notas,
    diasDisponibles,
    slotsDisponibles,
    calculoServicio,
    misCitas,
    citaActual,
    loading,
    error,
    // Getters
    totalPrecio,
    totalDuracion,
    servicioIds,
    fechaHoraCompleta,
    puedeAvanzar,
    serviciosFiltrados,
    datosClienteValidos,
    // Actions catálogo
    cargarCatalogo,
    seleccionarCategoria,
    cargarEmpleados,
    // Actions servicios
    agregarServicio,
    quitarServicio,
    limpiarServicios,
    calcularServicio,
    seleccionarEmpleado,
    cargarDiasDisponibles,
    cargarSlots,
    seleccionarHora,
    siguientePaso,
    pasoAnterior,
    irAPaso,
    enviarOtpConfirmacion,
    agendarCita,
    reiniciarAgendamiento,
    cargarMisCitas,
    cancelarCita,
    clearError,
  }
})

