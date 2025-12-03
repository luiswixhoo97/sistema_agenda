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
  horario_empleado?: {
    hora_inicio: string
    hora_fin: string
  }
  bloqueos_count?: number
  citas_count?: number
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

// Tipos para slots coordinados
export interface ServicioConEmpleado {
  servicio_id: number
  empleado_id: number
}

export interface ServicioSlotInfo {
  empleado_id: number
  empleado_nombre: string
  servicio_id: number
  servicio_nombre: string
  inicio: string
  fin: string
  hora_inicio: string
  hora_fin: string
  duracion: number
}

export interface SlotCoordinado {
  hora: string
  hora_fin: string
  servicios: ServicioSlotInfo[]
}

export interface SlotsCoordinadosResponse {
  fecha: string
  slots_validos: SlotCoordinado[]
  total_slots: number
  servicios_info: {
    servicio_id: number
    servicio_nombre: string
    empleado_id: number
    empleado_nombre: string
    duracion: number
  }[]
  mensaje: string | null
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
   * Obtener slots disponibles para una fecha
   * @param tipoUsuario - 'publico' | 'empleado' | 'admin' (default: 'publico')
   */
  async obtenerSlots(
    empleadoId: number,
    fecha: string,
    servicios: number[],
    tipoUsuario: boolean | 'publico' | 'empleado' | 'admin' = 'publico'
  ): Promise<SlotsResponse> {
    const params = new URLSearchParams()
    params.append('empleado_id', empleadoId.toString())
    params.append('fecha', fecha)
    servicios.forEach(s => params.append('servicios[]', s.toString()))

    // Determinar el endpoint según el tipo de usuario
    // Mantener compatibilidad con boolean (true = empleado)
    let endpoint: string
    if (tipoUsuario === true || tipoUsuario === 'empleado') {
      endpoint = `/empleado/disponibilidad/slots?${params}`
    } else if (tipoUsuario === 'admin') {
      endpoint = `/admin/disponibilidad/slots?${params}`
    } else {
      endpoint = `/publico/disponibilidad/slots?${params}`
    }
    
    const response = await api.get(endpoint)
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

  /**
   * Obtener slots coordinados para múltiples servicios con diferentes empleados
   */
  async obtenerSlotsCoordinados(
    fecha: string,
    servicios: ServicioConEmpleado[]
  ): Promise<SlotsCoordinadosResponse> {
    const response = await api.post('/publico/disponibilidad/slots-coordinados', {
      fecha,
      servicios,
    })
    return response.data.data
  },

  // =====================================================
  // RESERVAS TEMPORALES
  // =====================================================

  /**
   * Reservar temporalmente un slot (para evitar conflictos)
   */
  async reservarTemporal(
    empleadoId: number,
    fecha: string,
    hora: string,
    servicios: number[],
    telefono?: string,
    sessionId?: string
  ): Promise<ReservaTemporalResponse> {
    const response = await api.post('/publico/disponibilidad/reservar-temporal', {
      empleado_id: empleadoId,
      fecha,
      hora,
      servicios,
      telefono,
      session_id: sessionId,
    })
    return response.data
  },

  /**
   * Reservar temporalmente múltiples slots coordinados
   */
  async reservarTemporalMultiple(
    fecha: string,
    servicios: {
      servicio_id: number
      empleado_id: number
      hora_inicio: string
      hora_fin: string
    }[],
    telefono?: string,
    sessionId?: string
  ): Promise<ReservaTemporalMultipleResponse> {
    const response = await api.post('/publico/disponibilidad/reservar-temporal-multiple', {
      fecha,
      servicios,
      telefono,
      session_id: sessionId,
    })
    return response.data
  },

  /**
   * Liberar una reserva temporal
   */
  async liberarTemporal(token: string): Promise<{ success: boolean; message: string }> {
    const response = await api.post('/publico/disponibilidad/liberar-temporal', { token })
    return response.data
  },

  /**
   * Liberar múltiples reservas temporales
   */
  async liberarTemporalMultiple(tokens: string[]): Promise<{ success: boolean; liberados: number }> {
    const response = await api.post('/publico/disponibilidad/liberar-temporal-multiple', { tokens })
    return response.data
  },

  /**
   * Extender tiempo de una reserva temporal
   */
  async extenderTemporal(token: string): Promise<ReservaTemporalResponse> {
    const response = await api.post('/publico/disponibilidad/extender-temporal', { token })
    return response.data
  },

  /**
   * Verificar estado de una reserva temporal
   */
  async verificarReserva(token: string): Promise<{ success: boolean; activa: boolean; data?: { expira_at: string; tiempo_restante_segundos: number } }> {
    const response = await api.get(`/publico/disponibilidad/verificar-reserva/${token}`)
    return response.data
  },
}

// Tipos para reservas temporales
export interface ReservaTemporalResponse {
  success: boolean
  message: string
  codigo?: string
  data?: {
    token: string
    expira_at: string
    tiempo_restante_segundos: number
    fecha: string
    hora_inicio: string
    hora_fin: string
  }
}

export interface ReservaTemporalMultipleResponse {
  success: boolean
  message: string
  codigo?: string
  data?: {
    tokens: string[]
    reservas: {
      token: string
      empleado_id: number
      servicio_id: number
      hora_inicio: string
      hora_fin: string
    }[]
    expira_at: string
    tiempo_restante_segundos: number
  }
}

export default disponibilidadService

