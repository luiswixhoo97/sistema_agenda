import { ref, computed, onMounted } from 'vue'
import { useCitasStore } from '@/stores/citas'
import catalogoService from '@/services/catalogoService'
import type { ServicioPublico, EmpleadoPublico } from '@/services/catalogoService'

export function useAgendamiento() {
  const store = useCitasStore()

  // Calendario
  const mesActual = ref(new Date().getMonth() + 1)
  const anioActual = ref(new Date().getFullYear())

  // Navegar mes en calendario
  function mesAnterior() {
    if (mesActual.value === 1) {
      mesActual.value = 12
      anioActual.value--
    } else {
      mesActual.value--
    }
    cargarDisponibilidadMes()
  }

  function mesSiguiente() {
    if (mesActual.value === 12) {
      mesActual.value = 1
      anioActual.value++
    } else {
      mesActual.value++
    }
    cargarDisponibilidadMes()
  }

  // Cargar disponibilidad del mes actual
  async function cargarDisponibilidadMes() {
    await store.cargarDiasDisponibles(mesActual.value, anioActual.value)
  }

  // Seleccionar servicio o promoción
  async function toggleServicio(servicio: ServicioPublico | any) {
    // Si es una promoción, seleccionarla y pre-seleccionar sus servicios automáticamente
    if (servicio.es_promocion && servicio.promocion_id) {
      // Si ya está seleccionada, deseleccionarla
      if (store.promocionSeleccionada === servicio.promocion_id) {
        store.seleccionarPromocion(null)
        store.serviciosSeleccionados = []
        return
      }
      
      // Seleccionar la promoción y cargar su información
      store.seleccionarPromocion(servicio.promocion_id)
      
      // Cargar información completa de la promoción para calcular descuentos
      const promociones = await catalogoService.obtenerPromociones()
      const promocionCompleta = promociones.find((p: any) => p.id === servicio.promocion_id)
      if (promocionCompleta) {
        store.promocionInfo = promocionCompleta
      }
      
      // Pre-seleccionar automáticamente los servicios de la promoción
      if (servicio.servicios_incluidos && servicio.servicios_incluidos.length > 0) {
        // Limpiar servicios seleccionados actuales
        store.serviciosSeleccionados = []
        
        // Cargar todos los servicios para obtener información completa
        const todosServicios = await catalogoService.obtenerServicios()
        
        // Agregar servicios de la promoción (evitar duplicados)
        const serviciosAgregados = new Set<number>()
        for (const serv of servicio.servicios_incluidos) {
          // Evitar duplicados
          if (serviciosAgregados.has(serv.id)) {
            continue
          }
          
          const servicioCompleto = todosServicios.find((s: any) => s.id === serv.id)
          if (servicioCompleto) {
            store.agregarServicio({
              id: servicioCompleto.id,
              nombre: servicioCompleto.nombre,
              precio: servicioCompleto.precio,
              duracion: servicioCompleto.duracion,
            })
            serviciosAgregados.add(serv.id)
          }
        }
        
        // Cargar empleados disponibles para estos servicios
        await store.cargarEmpleados()
      } else {
        // Si no tiene servicios_incluidos en el objeto, usar la función del store
        await store.preSeleccionarServiciosDePromocion(servicio.promocion_id)
      }
      return
    }
    
    // Si es un servicio normal
    const existe = store.serviciosSeleccionados.find(s => s.id === servicio.id)
    if (existe) {
      store.quitarServicio(servicio.id)
    } else {
      store.agregarServicio({
        id: servicio.id,
        nombre: servicio.nombre,
        precio: servicio.precio,
        duracion: servicio.duracion,
      })
    }
  }

  // Verificar si servicio está seleccionado
  function servicioSeleccionado(servicioId: number): boolean {
    return store.serviciosSeleccionados.some(s => s.id === servicioId)
  }

  // Seleccionar empleado y avanzar
  function seleccionarEmpleadoYAvanzar(empleado: EmpleadoPublico) {
    store.seleccionarEmpleado({
      id: empleado.id,
      nombre: empleado.nombre,
      foto: empleado.foto,
    })
    store.siguientePaso()
    cargarDisponibilidadMes()
  }

  // Seleccionar fecha
  async function seleccionarFecha(fecha: string) {
    await store.cargarSlots(fecha)
  }

  // Confirmar agendamiento
  async function confirmarAgendamiento(): Promise<boolean> {
    return await store.agendarCita()
  }

  // Nombres de los meses
  const nombresMeses = [
    'Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio',
    'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'
  ]

  const nombreMesActual = computed(() => nombresMeses[mesActual.value - 1])

  // Generar días del mes para el calendario
  const diasDelMes = computed(() => {
    const primerDia = new Date(anioActual.value, mesActual.value - 1, 1)
    const ultimoDia = new Date(anioActual.value, mesActual.value, 0)
    const diasEnMes = ultimoDia.getDate()
    const primerDiaSemana = primerDia.getDay() || 7 // Convertir 0 (domingo) a 7
    
    const dias: { fecha: string; dia: number; disponible: boolean; esFestivo: boolean; esHoy: boolean; esPasado: boolean }[] = []
    const hoy = new Date().toISOString().split('T')[0]
    if (!hoy) return []
    
    for (let i = 1; i <= diasEnMes; i++) {
      const fecha = `${anioActual.value}-${String(mesActual.value).padStart(2, '0')}-${String(i).padStart(2, '0')}`
      const diaDisponible = store.diasDisponibles.find(d => d.fecha === fecha)
      
      dias.push({
        fecha,
        dia: i,
        disponible: diaDisponible?.tiene_disponibilidad ?? false,
        esFestivo: diaDisponible?.es_festivo ?? false,
        esHoy: fecha === hoy,
        esPasado: fecha < hoy,
      })
    }
    
    // Agregar días vacíos al inicio para alinear con el día de la semana
    const diasVacios = primerDiaSemana - 1
    for (let i = 0; i < diasVacios; i++) {
      dias.unshift({ fecha: '', dia: 0, disponible: false, esFestivo: false, esHoy: false, esPasado: true })
    }
    
    return dias
  })

  return {
    // Store (todo el estado viene del store ahora)
    store,
    // Computed del calendario
    mesActual,
    anioActual,
    nombreMesActual,
    diasDelMes,
    // Accesos directos al store (para compatibilidad con componentes existentes)
    categorias: computed(() => store.categorias),
    serviciosFiltrados: computed(() => store.serviciosFiltrados),
    empleados: computed(() => store.empleados),
    categoriaActiva: computed(() => store.categoriaActiva),
    loadingCatalogo: computed(() => store.loadingCatalogo),
    // Actions
    cargarCatalogo: () => store.cargarCatalogo(),
    seleccionarCategoria: (id: number | null) => store.seleccionarCategoria(id),
    cargarEmpleados: () => store.cargarEmpleados(),
    toggleServicio,
    servicioSeleccionado,
    seleccionarEmpleadoYAvanzar,
    seleccionarFecha,
    mesAnterior,
    mesSiguiente,
    cargarDisponibilidadMes,
    confirmarAgendamiento,
  }
}
