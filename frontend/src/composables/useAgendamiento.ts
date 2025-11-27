import { ref, computed, onMounted } from 'vue'
import { useCitasStore } from '@/stores/citas'
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

  // Seleccionar servicio
  function toggleServicio(servicio: ServicioPublico) {
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
