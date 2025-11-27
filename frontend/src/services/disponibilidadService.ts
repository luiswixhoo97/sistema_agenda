import api from './api'
import type {
  SlotDisponible,
  DiaDisponible,
  Empleado,
} from '@/types'

export interface DiaConDisponibilidad {
  fecha: string
  dia_semana: number
  tiene_disponibilidad: boolean
  es_festivo: boolean
}

export interface SlotsResponse {
  fecha: string
  empleado_id: number
  duracion_total: number
  slots: SlotDisponible[]
  mensaje: string | null
}

export interface VerificarResponse {
  disponible: boolean
  duracion_total: number
  hora_fin: string
  mensaje: string | null
}

export interface EmpleadoDisponible {
  id: number
  nombre: string
  foto?: string
  bio?: string
  promedio_calificacion: number
}

export interface CalculoServicioResponse {
  duracion_total: number
  duracion_texto: string
  precio_total: number
  precio_texto: string
}

const disponibilidadService = {
  /**
   * Obtener días con disponibilidad en un mes (público)
   */
  async obtenerDiasDisponibles(
    empleadoId: number,
    mes: number,
    anio: number,
    servicios: number[]
  ): Promise<DiaConDisponibilidad[]> {
    const params = new URLSearchParams()
    params.append('empleado_id', empleadoId.toString())
    params.append('mes', mes.toString())
    params.append('anio', anio.toString())
    servicios.forEach(s => params.append('servicios[]', s.toString()))

    const response = await api.get(`/publico/disponibilidad/dias?${params}`)
    return response.data.data.dias
  },

  /**
   * Obtener slots disponibles para una fecha (público)
   */
  async obtenerSlots(
    empleadoId: number,
    fecha: string,
    servicios: number[]
  ): Promise<SlotsResponse> {
    const params = new URLSearchParams()
    params.append('empleado_id', empleadoId.toString())
    params.append('fecha', fecha)
    servicios.forEach(s => params.append('servicios[]', s.toString()))

    const response = await api.get(`/publico/disponibilidad/slots?${params}`)
    return response.data.data
  },

  /**
   * Verificar disponibilidad específica
   */
  async verificarDisponibilidad(
    empleadoId: number,
    fechaHora: string,
    servicios: number[]
  ): Promise<VerificarResponse> {
    const response = await api.post('/cliente/disponibilidad/verificar', {
      empleado_id: empleadoId,
      fecha_hora: fechaHora,
      servicios,
    })
    return response.data.data
  },

  /**
   * Obtener empleados disponibles para una fecha/hora
   */
  async obtenerEmpleadosDisponibles(
    fechaHora: string,
    servicios: number[]
  ): Promise<EmpleadoDisponible[]> {
    const params = new URLSearchParams()
    params.append('fecha_hora', fechaHora)
    servicios.forEach(s => params.append('servicios[]', s.toString()))

    const response = await api.get(`/cliente/disponibilidad/empleados?${params}`)
    return response.data.data.empleados
  },

  /**
   * Calcular precio y duración de servicios (público)
   */
  async calcularServicio(
    servicios: number[],
    empleadoId?: number
  ): Promise<CalculoServicioResponse> {
    const response = await api.post('/publico/disponibilidad/calcular', {
      servicios,
      empleado_id: empleadoId,
    })
    return response.data.data
  },
}

export default disponibilidadService

