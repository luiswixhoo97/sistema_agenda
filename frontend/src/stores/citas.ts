import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import citaService, { 
  type CitaResponse, 
  type AgendarCitaRequest,
  type CitaMultipleResponse 
} from '@/services/citaService'
import disponibilidadService, { 
  type DiaConDisponibilidad, 
  type SlotsResponse,
  type CalculoServicioResponse,
  type SlotsCoordinadosResponse,
  type SlotCoordinado
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

// Nuevo: Empleado asignado a un servicio específico
export interface EmpleadoPorServicio {
  servicioId: number
  servicioNombre: string
  empleadoId: number
  empleadoNombre: string
  empleadoFoto?: string
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
  const promocionSeleccionada = ref<number | null>(null)
  const promocionInfo = ref<any>(null) // Información completa de la promoción seleccionada
  
  // Nuevo: Modo múltiples empleados (un empleado por servicio)
  const modoMultiplesEmpleados = ref(false)
  const empleadosPorServicio = ref<EmpleadoPorServicio[]>([])
  const empleadosDisponiblesPorServicio = ref<Record<number, EmpleadoPublico[]>>({})
  
  // Estado de disponibilidad
  const diasDisponibles = ref<DiaConDisponibilidad[]>([])
  const slotsDisponibles = ref<SlotsResponse | null>(null)
  const slotsCoordinados = ref<SlotsCoordinadosResponse | null>(null)
  const slotCoordinadoSeleccionado = ref<SlotCoordinado | null>(null)
  const calculoServicio = ref<CalculoServicioResponse | null>(null)
  
  // Citas múltiples creadas
  const citasMultiples = ref<CitaMultipleResponse[]>([])
  
  // Reserva temporal
  const reservaTemporal = ref<{
    token: string | null
    tokens: string[] // Para múltiples reservas
    expiraAt: Date | null
    tiempoRestante: number // segundos
  }>({
    token: null,
    tokens: [],
    expiraAt: null,
    tiempoRestante: 0
  })
  const temporizadorInterval = ref<ReturnType<typeof setInterval> | null>(null)
  
  // Estado de citas
  const misCitas = ref<CitaResponse[]>([])
  const citaActual = ref<CitaResponse | null>(null)
  
  // Estado UI
  const loading = ref(false)
  const error = ref<string | null>(null)

  // Getters
  const totalPrecio = computed(() => {
    const precioBase = serviciosSeleccionados.value.reduce((sum, s) => sum + Number(s.precio), 0)
    
    // Si hay una promoción seleccionada, aplicar el descuento
    if (promocionSeleccionada.value && promocionInfo.value) {
      const promocion = promocionInfo.value
      
      // Verificar que los servicios seleccionados coincidan con los de la promoción
      const serviciosPromoIds = promocion.servicios_info?.map((s: any) => s.id) || []
      const serviciosSeleccionadosIds = serviciosSeleccionados.value.map(s => s.id)
      const todosServiciosCoinciden = serviciosPromoIds.length > 0 && 
        serviciosPromoIds.every((id: number) => serviciosSeleccionadosIds.includes(id)) &&
        serviciosSeleccionadosIds.length === serviciosPromoIds.length
      
      // Solo aplicar descuento si los servicios coinciden exactamente con la promoción
      if (todosServiciosCoinciden) {
        const promo = promocion as any
        if (promo.descuento_porcentaje) {
          return precioBase * (1 - promo.descuento_porcentaje / 100)
        } else if (promo.descuento_fijo) {
          return Math.max(0, precioBase - Number(promo.descuento_fijo))
        }
      }
    }
    
    return precioBase
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

  // Verificar si todos los servicios tienen empleado asignado
  const todosServiciosTienenEmpleado = computed(() => {
    if (!modoMultiplesEmpleados.value) return true
    return serviciosSeleccionados.value.every(servicio => 
      empleadosPorServicio.value.some(ep => ep.servicioId === servicio.id)
    )
  })

  const puedeAvanzar = computed(() => {
    switch (paso.value) {
      case 1: return datosClienteValidos.value
      case 2: return serviciosSeleccionados.value.length > 0
      case 3: 
        if (modoMultiplesEmpleados.value) {
          return todosServiciosTienenEmpleado.value
        }
        return empleadoSeleccionado.value !== null
      case 4: 
        if (modoMultiplesEmpleados.value) {
          return slotCoordinadoSeleccionado.value !== null
        }
        return fechaSeleccionada.value !== null && horaSeleccionada.value !== null
      default: return false
    }
  })

  // Getter - Filtrar servicios por categoría (incluye promociones)
  const serviciosFiltrados = computed(() => {
    if (!categoriaActiva.value) return servicios.value
    
    // Si la categoría activa es 999999 (Promociones), retornar servicios de promociones
    if (categoriaActiva.value === 999999) {
      const categoriaPromociones = categorias.value.find(c => c.id === 999999)
      
      if (categoriaPromociones) {
        const categoriaPromo = categoriaPromociones as any
        const serviciosPromo = categoriaPromo.servicios || []
        
        // Verificar si es la categoría de promociones y tiene servicios
        if (categoriaPromo.es_promociones && Array.isArray(serviciosPromo) && serviciosPromo.length > 0) {
          // Convertir promociones a formato de servicios para mostrar
          return serviciosPromo.map((promo: any) => ({
            id: promo.id,
            nombre: promo.nombre,
            descripcion: promo.descripcion,
            precio: promo.precio || 0,
            precio_texto: `$${Number(promo.precio || 0).toFixed(2)}`,
            precio_con_descuento: promo.precio_con_descuento,
            precio_con_descuento_texto: `$${Number(promo.precio_con_descuento || promo.precio || 0).toFixed(2)}`,
            duracion: promo.duracion || 0,
            duracion_texto: `${promo.duracion || 0} min`,
            categoria: { id: 999999, nombre: 'Promociones' },
            es_promocion: true,
            promocion_id: promo.promocion_id,
            descuento: promo.descuento,
            servicios_incluidos: promo.servicios_incluidos || [],
            aplica_a_todos: promo.aplica_a_todos || false,
          }))
        }
      }
      return []
    }
    
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
      
      // Debug: verificar que la categoría Promociones se cargó
      const categoriaPromo = categorias.value.find(c => c.id === 999999)
      if (categoriaPromo) {
        console.log('✅ Categoría Promociones cargada:', {
          id: categoriaPromo.id,
          nombre: categoriaPromo.nombre,
          servicios_count: categoriaPromo.servicios_count,
          es_promociones: (categoriaPromo as any).es_promociones,
          servicios: (categoriaPromo as any).servicios?.length || 0
        })
      } else {
        console.log('❌ Categoría Promociones NO encontrada. Categorías cargadas:', categorias.value.map(c => ({ id: c.id, nombre: c.nombre })))
      }
      
      // Si hay una promoción seleccionada y estamos en el paso de servicios,
      // pre-seleccionar los servicios de la promoción
      if (promocionSeleccionada.value && paso.value === 2) {
        await preSeleccionarServiciosDePromocion(promocionSeleccionada.value)
      }
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
      // Filtrar empleados activos que ofrecen todos los servicios seleccionados
      empleados.value = emps.filter(emp => {
        // Solo mostrar empleados activos
        if (emp.activo === false) return false
        const serviciosEmpleado = emp.servicios.map(s => s.id)
        return servicioIds.value.every(id => serviciosEmpleado.includes(id))
      })
      
      // 🔄 Auto-activar modo múltiples si:
      // - Hay más de 1 servicio seleccionado
      // - No hay ningún empleado que pueda hacer TODOS los servicios
      if (servicioIds.value.length > 1 && empleados.value.length === 0) {
        console.log('🔄 Auto-activando modo múltiples empleados (ningún empleado ofrece todos los servicios)')
        modoMultiplesEmpleados.value = true
        // Cargar empleados por servicio automáticamente
        await cargarEmpleadosPorServicio()
      } else if (servicioIds.value.length === 1) {
        // Si solo hay 1 servicio, desactivar modo múltiples
        modoMultiplesEmpleados.value = false
      }
    } catch (e) {
      console.error('Error cargando empleados:', e)
    } finally {
      loadingCatalogo.value = false
    }
  }

  // Actions - Cargar empleados disponibles por cada servicio (modo múltiples)
  async function cargarEmpleadosPorServicio() {
    if (servicioIds.value.length === 0) {
      empleadosDisponiblesPorServicio.value = {}
      return
    }

    loadingCatalogo.value = true
    try {
      const resultado: Record<number, EmpleadoPublico[]> = {}
      
      for (const servicio of serviciosSeleccionados.value) {
        const emps = await catalogoService.obtenerEmpleados(servicio.id)
        // Filtrar empleados activos que ofrecen este servicio
        resultado[servicio.id] = emps.filter(emp => {
          if (emp.activo === false) return false
          return emp.servicios.some(s => s.id === servicio.id)
        })
      }
      
      empleadosDisponiblesPorServicio.value = resultado
    } catch (e) {
      console.error('Error cargando empleados por servicio:', e)
    } finally {
      loadingCatalogo.value = false
    }
  }

  // Actions - Activar/desactivar modo múltiples empleados
  function toggleModoMultiplesEmpleados() {
    modoMultiplesEmpleados.value = !modoMultiplesEmpleados.value
    // Limpiar selecciones al cambiar de modo
    if (modoMultiplesEmpleados.value) {
      empleadoSeleccionado.value = null
    } else {
      empleadosPorServicio.value = []
    }
    // Limpiar slots
    slotsCoordinados.value = null
    slotCoordinadoSeleccionado.value = null
    slotsDisponibles.value = null
    fechaSeleccionada.value = null
    horaSeleccionada.value = null
  }

  // Actions - Asignar empleado a un servicio específico
  function asignarEmpleadoAServicio(servicioId: number, empleado: EmpleadoPublico) {
    const servicio = serviciosSeleccionados.value.find(s => s.id === servicioId)
    if (!servicio) return

    // Remover asignación previa del servicio
    empleadosPorServicio.value = empleadosPorServicio.value.filter(
      ep => ep.servicioId !== servicioId
    )
    
    // Agregar nueva asignación
    empleadosPorServicio.value.push({
      servicioId: servicio.id,
      servicioNombre: servicio.nombre,
      empleadoId: empleado.id,
      empleadoNombre: empleado.nombre,
      empleadoFoto: empleado.foto,
    })
    
    // Limpiar slots al cambiar empleados
    slotsCoordinados.value = null
    slotCoordinadoSeleccionado.value = null
    fechaSeleccionada.value = null
  }

  // Actions - Verificar si un empleado está asignado a un servicio
  function empleadoAsignadoAServicio(servicioId: number): EmpleadoPorServicio | undefined {
    return empleadosPorServicio.value.find(ep => ep.servicioId === servicioId)
  }

  // Actions - Quitar empleado de un servicio
  function quitarEmpleadoDeServicio(servicioId: number) {
    empleadosPorServicio.value = empleadosPorServicio.value.filter(
      ep => ep.servicioId !== servicioId
    )
    
    // Limpiar slots al quitar empleado
    slotsCoordinados.value = null
    slotCoordinadoSeleccionado.value = null
    fechaSeleccionada.value = null
  }

  // Actions - Cargar slots coordinados
  async function cargarSlotsCoordinados(fecha: string) {
    console.log('🕐 cargarSlotsCoordinados:', {
      fecha,
      modoMultiplesEmpleados: modoMultiplesEmpleados.value,
      empleadosPorServicio: empleadosPorServicio.value,
      serviciosSeleccionados: serviciosSeleccionados.value
    })
    
    if (!modoMultiplesEmpleados.value || empleadosPorServicio.value.length === 0) {
      console.warn('⚠️ No se pueden cargar slots coordinados')
      return
    }

    loading.value = true
    error.value = null
    fechaSeleccionada.value = fecha
    slotCoordinadoSeleccionado.value = null

    try {
      // Preparar datos en el orden de los servicios seleccionados
      const serviciosConEmpleados = serviciosSeleccionados.value.map(servicio => {
        const asignacion = empleadosPorServicio.value.find(ep => ep.servicioId === servicio.id)
        return {
          servicio_id: servicio.id,
          empleado_id: asignacion?.empleadoId || 0
        }
      })

      console.log('📡 Llamando a obtenerSlotsCoordinados:', { fecha, serviciosConEmpleados })

      slotsCoordinados.value = await disponibilidadService.obtenerSlotsCoordinados(
        fecha,
        serviciosConEmpleados
      )
      
      console.log('✅ Slots coordinados:', slotsCoordinados.value)
    } catch (e: any) {
      console.error('❌ Error cargando slots coordinados:', e)
      error.value = e.response?.data?.message || 'Error al cargar horarios coordinados'
    } finally {
      loading.value = false
    }
  }

  // Actions - Seleccionar slot coordinado
  function seleccionarSlotCoordinado(slot: SlotCoordinado) {
    slotCoordinadoSeleccionado.value = slot
    horaSeleccionada.value = slot.hora
  }

  // =====================================================
  // RESERVAS TEMPORALES
  // =====================================================

  // Iniciar temporizador de cuenta regresiva
  function iniciarTemporizador() {
    detenerTemporizador() // Limpiar si hay uno anterior
    
    temporizadorInterval.value = setInterval(() => {
      if (reservaTemporal.value.tiempoRestante > 0) {
        reservaTemporal.value.tiempoRestante--
      } else {
        // Tiempo expirado
        detenerTemporizador()
        limpiarReservaTemporal()
        error.value = 'Tu reserva de horario ha expirado. Por favor, selecciona otro horario.'
      }
    }, 1000)
  }

  // Detener temporizador
  function detenerTemporizador() {
    if (temporizadorInterval.value) {
      clearInterval(temporizadorInterval.value)
      temporizadorInterval.value = null
    }
  }

  // Limpiar reserva temporal
  function limpiarReservaTemporal() {
    reservaTemporal.value = {
      token: null,
      tokens: [],
      expiraAt: null,
      tiempoRestante: 0
    }
    detenerTemporizador()
  }

  // Reservar slot temporalmente (modo normal)
  async function reservarSlotTemporal(): Promise<boolean> {
    if (!empleadoSeleccionado.value || !fechaSeleccionada.value || !horaSeleccionada.value) {
      return false
    }

    loading.value = true
    error.value = null

    try {
      const response = await disponibilidadService.reservarTemporal(
        empleadoSeleccionado.value.id,
        fechaSeleccionada.value,
        horaSeleccionada.value,
        servicioIds.value,
        datosCliente.value.telefono || undefined
      )

      if (response.success && response.data) {
        reservaTemporal.value = {
          token: response.data.token,
          tokens: [],
          expiraAt: new Date(response.data.expira_at),
          tiempoRestante: response.data.tiempo_restante_segundos
        }
        iniciarTemporizador()
        console.log('🔒 Slot reservado temporalmente:', response.data)
        return true
      } else {
        error.value = response.message || 'No se pudo reservar el horario'
        // Si el slot ya estaba reservado, limpiar selección
        if (response.codigo === 'SLOT_YA_RESERVADO' || response.codigo === 'SLOT_NO_DISPONIBLE') {
          horaSeleccionada.value = null
        }
        return false
      }
    } catch (e: any) {
      console.error('Error reservando slot:', e)
      if (e.response?.status === 409) {
        error.value = e.response?.data?.message || 'Este horario acaba de ser reservado por otro usuario'
        horaSeleccionada.value = null
      } else {
        error.value = 'Error al reservar el horario'
      }
      return false
    } finally {
      loading.value = false
    }
  }

  // Reservar slots temporalmente (modo múltiples)
  async function reservarSlotsTemporalesMultiples(): Promise<boolean> {
    if (!slotCoordinadoSeleccionado.value || !fechaSeleccionada.value) {
      return false
    }

    loading.value = true
    error.value = null

    try {
      const serviciosParaReserva = slotCoordinadoSeleccionado.value.servicios.map(s => ({
        servicio_id: s.servicio_id,
        empleado_id: s.empleado_id,
        hora_inicio: s.hora_inicio,
        hora_fin: s.hora_fin
      }))

      const response = await disponibilidadService.reservarTemporalMultiple(
        fechaSeleccionada.value,
        serviciosParaReserva,
        datosCliente.value.telefono || undefined
      )

      if (response.success && response.data) {
        reservaTemporal.value = {
          token: null,
          tokens: response.data.tokens,
          expiraAt: new Date(response.data.expira_at),
          tiempoRestante: response.data.tiempo_restante_segundos
        }
        iniciarTemporizador()
        console.log('🔒 Slots múltiples reservados temporalmente:', response.data)
        return true
      } else {
        error.value = response.message || 'No se pudieron reservar los horarios'
        if (response.codigo === 'SLOT_YA_RESERVADO') {
          slotCoordinadoSeleccionado.value = null
          horaSeleccionada.value = null
        }
        return false
      }
    } catch (e: any) {
      console.error('Error reservando slots múltiples:', e)
      if (e.response?.status === 409) {
        error.value = e.response?.data?.message || 'Uno de los horarios acaba de ser reservado'
        slotCoordinadoSeleccionado.value = null
        horaSeleccionada.value = null
      } else {
        error.value = 'Error al reservar los horarios'
      }
      return false
    } finally {
      loading.value = false
    }
  }

  // Liberar reservas temporales
  async function liberarReservasTemporal() {
    try {
      if (reservaTemporal.value.token) {
        await disponibilidadService.liberarTemporal(reservaTemporal.value.token)
        console.log('🔓 Reserva liberada')
      }
      if (reservaTemporal.value.tokens.length > 0) {
        await disponibilidadService.liberarTemporalMultiple(reservaTemporal.value.tokens)
        console.log('🔓 Reservas múltiples liberadas')
      }
    } catch (e) {
      console.error('Error liberando reservas:', e)
    } finally {
      limpiarReservaTemporal()
    }
  }

  // Extender tiempo de reserva
  async function extenderReservaTemporal() {
    const token = reservaTemporal.value.token || reservaTemporal.value.tokens[0]
    if (!token) return

    try {
      const response = await disponibilidadService.extenderTemporal(token)
      if (response.success && response.data) {
        reservaTemporal.value.expiraAt = new Date(response.data.expira_at)
        reservaTemporal.value.tiempoRestante = response.data.tiempo_restante_segundos
        console.log('⏱️ Tiempo de reserva extendido')
      }
    } catch (e) {
      console.error('Error extendiendo reserva:', e)
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
    // En modo múltiples, usar el primer empleado asignado para cargar disponibilidad del calendario
    let empleadoIdParaCalendario: number | null = null
    
    console.log('📅 cargarDiasDisponibles:', {
      modoMultiplesEmpleados: modoMultiplesEmpleados.value,
      empleadosPorServicio: empleadosPorServicio.value,
      empleadoSeleccionado: empleadoSeleccionado.value,
      servicioIds: servicioIds.value,
      mes,
      anio
    })
    
    if (modoMultiplesEmpleados.value) {
      // Usar el primer empleado de la lista de asignaciones
      if (empleadosPorServicio.value.length > 0 && empleadosPorServicio.value[0]) {
        empleadoIdParaCalendario = empleadosPorServicio.value[0].empleadoId
      }
    } else {
      empleadoIdParaCalendario = empleadoSeleccionado.value?.id || null
    }
    
    if (!empleadoIdParaCalendario || servicioIds.value.length === 0) {
      console.warn('⚠️ No se puede cargar disponibilidad:', { empleadoIdParaCalendario, servicios: servicioIds.value.length })
      return
    }

    loading.value = true
    error.value = null

    try {
      // En modo múltiples, solo pedimos el primer servicio para el calendario
      // Los slots coordinados se calculan cuando se selecciona una fecha
      const serviciosParaCalendario = modoMultiplesEmpleados.value 
        ? (servicioIds.value[0] !== undefined ? [servicioIds.value[0]] : [])
        : servicioIds.value.filter((id): id is number => id !== undefined)
      
      if (serviciosParaCalendario.length === 0) {
        console.warn('⚠️ No hay servicios válidos para el calendario')
        return
      }
      
      console.log('📡 Llamando a obtenerDiasDisponibles:', { empleadoIdParaCalendario, serviciosParaCalendario })
      
      diasDisponibles.value = await disponibilidadService.obtenerDiasDisponibles(
        empleadoIdParaCalendario,
        mes,
        anio,
        serviciosParaCalendario
      )
      
      console.log('✅ Días disponibles cargados:', diasDisponibles.value.length)
    } catch (e: any) {
      console.error('❌ Error cargando días:', e)
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
    // Si está en modo múltiples empleados, usar agendarCitasMultiples
    if (modoMultiplesEmpleados.value) {
      return await agendarCitasMultiples()
    }

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
        promocion_id: promocionSeleccionada.value || undefined,
        token_reserva: reservaTemporal.value.token || undefined,
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

  // Actions - Agendar múltiples citas coordinadas
  async function agendarCitasMultiples(): Promise<boolean> {
    if (!slotCoordinadoSeleccionado.value || empleadosPorServicio.value.length === 0) {
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

      // Construir array de servicios con sus empleados y horarios
      const serviciosRequest = slotCoordinadoSeleccionado.value.servicios.map(s => ({
        servicio_id: s.servicio_id,
        empleado_id: s.empleado_id,
        fecha_hora: s.inicio
      }))

      const request = {
        cliente_nombre: nombreCompleto,
        cliente_telefono: datosCliente.value.telefono,
        cliente_email: datosCliente.value.email?.trim() || undefined,
        codigo_otp: otpCodigo.value,
        servicios: serviciosRequest,
        notas: notas.value || undefined,
        tokens_reserva: reservaTemporal.value.tokens.length > 0 ? reservaTemporal.value.tokens : undefined,
      }

      console.log('📅 Agendando citas múltiples:', request)

      const response = await citaService.agendarMultiples(request)

      if (response.success && response.citas) {
        citasMultiples.value = response.citas
        // También guardamos la primera cita como citaActual para compatibilidad
        if (response.citas.length > 0) {
          citaActual.value = response.citas[0] as any
        }
        return true
      } else {
        error.value = response.message || 'Error al agendar citas'
        return false
      }
    } catch (e: any) {
      console.error('❌ Error al agendar citas múltiples:', e.response?.data)
      if (e.response?.data?.errors) {
        const errores = Object.values(e.response.data.errors).flat().join('. ')
        error.value = errores
      } else {
        error.value = e.response?.data?.message || 'Error al agendar citas'
      }
      return false
    } finally {
      loading.value = false
    }
  }

  // Actions - Reiniciar proceso
  async function reiniciarAgendamiento() {
    // Liberar reservas temporales si existen
    await liberarReservasTemporal()
    
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
    promocionSeleccionada.value = null
    promocionInfo.value = null
    diasDisponibles.value = []
    slotsDisponibles.value = null
    calculoServicio.value = null
    citaActual.value = null
    error.value = null
    // Limpiar estado de múltiples empleados
    modoMultiplesEmpleados.value = false
    empleadosPorServicio.value = []
    empleadosDisponiblesPorServicio.value = {}
    slotsCoordinados.value = null
    slotCoordinadoSeleccionado.value = null
    citasMultiples.value = []
  }

  // Actions - Seleccionar promoción
  function seleccionarPromocion(promocionId: number | null, promocionCompleta?: any) {
    promocionSeleccionada.value = promocionId
    if (!promocionId) {
      promocionInfo.value = null
    } else if (promocionCompleta) {
      // Guardar información completa de la promoción para calcular descuentos
      promocionInfo.value = promocionCompleta
    }
    // NO pre-seleccionar aquí, se hace en toggleServicio para evitar duplicados
  }
  
  // Pre-seleccionar servicios de una promoción
  async function preSeleccionarServiciosDePromocion(promocionId: number) {
    try {
      // Obtener información de la promoción
      const promociones = await catalogoService.obtenerPromociones()
      const promocion = promociones.find((p: any) => p.id === promocionId)
      
      if (promocion) {
        // Guardar información de la promoción para calcular descuentos
        promocionInfo.value = promocion
        
        if (promocion.servicios_info && promocion.servicios_info.length > 0) {
          // Limpiar servicios seleccionados actuales
          serviciosSeleccionados.value = []
          
          // Cargar todos los servicios para obtener precios completos
          const todosServicios = await catalogoService.obtenerServicios()
          
          // Agregar servicios de la promoción (evitar duplicados)
          const serviciosAgregados = new Set<number>()
          for (const servicioInfo of promocion.servicios_info) {
            // Evitar duplicados
            if (serviciosAgregados.has(servicioInfo.id)) {
              continue
            }
            
            const servicioCompleto = todosServicios.find((s: any) => s.id === servicioInfo.id)
            if (servicioCompleto) {
              serviciosSeleccionados.value.push({
                id: servicioCompleto.id,
                nombre: servicioCompleto.nombre,
                precio: servicioCompleto.precio,
                duracion: servicioCompleto.duracion,
              })
              serviciosAgregados.add(servicioInfo.id)
            }
          }
          
          // Cargar empleados disponibles para estos servicios
          await cargarEmpleados()
        }
      }
    } catch (error) {
      console.error('Error pre-seleccionando servicios de promoción:', error)
    }
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
    promocionSeleccionada,
    promocionInfo,
    diasDisponibles,
    slotsDisponibles,
    calculoServicio,
    misCitas,
    citaActual,
    loading,
    error,
    // Estado múltiples empleados
    modoMultiplesEmpleados,
    empleadosPorServicio,
    empleadosDisponiblesPorServicio,
    slotsCoordinados,
    slotCoordinadoSeleccionado,
    citasMultiples,
    // Getters
    totalPrecio,
    totalDuracion,
    servicioIds,
    fechaHoraCompleta,
    puedeAvanzar,
    serviciosFiltrados,
    datosClienteValidos,
    todosServiciosTienenEmpleado,
    // Actions catálogo
    cargarCatalogo,
    seleccionarCategoria,
    cargarEmpleados,
    cargarEmpleadosPorServicio,
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
    seleccionarPromocion,
    preSeleccionarServiciosDePromocion,
    // Actions múltiples empleados
    toggleModoMultiplesEmpleados,
    asignarEmpleadoAServicio,
    empleadoAsignadoAServicio,
    quitarEmpleadoDeServicio,
    cargarSlotsCoordinados,
    seleccionarSlotCoordinado,
    agendarCitasMultiples,
    // Reserva temporal
    reservaTemporal,
    reservarSlotTemporal,
    reservarSlotsTemporalesMultiples,
    liberarReservasTemporal,
    extenderReservaTemporal,
    limpiarReservaTemporal,
  }
})

