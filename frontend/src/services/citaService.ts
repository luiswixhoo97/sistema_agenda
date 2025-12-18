import api from './api'
import type { Cita, PaginatedResponse } from '@/types'

export interface AgendarCitaRequest {
  empleado_id: number
  servicios: number[]
  fecha_hora: string
  promocion_id?: number
  notas?: string
}

export interface CitaResponse {
  id: number
  fecha_hora: string
  fecha: string
  hora: string
  fecha_texto: string
  hora_texto: string
  duracion_total: number
  estado: string
  precio_final: number
  metodo_pago: string
  notas?: string
  cliente?: {
    id: number
    nombre: string
    telefono: string
  }
  empleado?: {
    id: number
    nombre: string
    foto?: string
  }
  servicios: {
    id: number
    nombre: string
    precio_aplicado: number
    duracion: number
  }[]
  puede_cancelarse: boolean
  puede_modificarse: boolean
  fotos?: {
    id: number
    tipo: string
    url: string
    descripcion?: string
  }[]
  promocion?: {
    id: number
    nombre: string
    descuento: string
  }
}

export interface CitasListResponse {
  citas: CitaResponse[]
  pagination: {
    current_page: number
    last_page: number
    per_page: number
    total: number
  }
}

export interface CalificarRequest {
  puntuacion: number
  comentario?: string
}

export interface AgendarPublicoRequest {
  cliente_nombre: string
  cliente_telefono: string
  cliente_email?: string
  codigo_otp: string
  empleado_id: number
  servicios: number[]
  fecha_hora: string
  notas?: string
  token_reserva?: string
  promocion_id?: number
  anticipo_transferencia?: boolean
  anticipo_pagado?: boolean
}

export interface ServicioCitaMultiple {
  servicio_id: number
  empleado_id: number
  fecha_hora: string
}

export interface AgendarMultiplesRequest {
  cliente_nombre: string
  cliente_telefono: string
  cliente_email?: string
  codigo_otp: string
  tokens_reserva?: string[]
  servicios: ServicioCitaMultiple[]
  notas?: string
  anticipo_transferencia?: boolean
  anticipo_pagado?: boolean
}

export interface CitaMultipleResponse {
  id: number
  fecha: string
  hora: string
  fecha_texto: string
  hora_texto: string
  duracion_total: number
  estado: string
  precio_final: number
  empleado: {
    id: number
    nombre: string
  }
  servicios: {
    id: number
    nombre: string
    precio_aplicado: number
    duracion: number
  }[]
}

export interface AgendarMultiplesResponse {
  success: boolean
  message: string
  cliente?: {
    id: number
    nombre: string
    telefono: string
  }
  citas?: CitaMultipleResponse[]
  resumen?: {
    total_citas: number
    precio_total: number
    duracion_total: number
  }
}

const citaService = {
  // =====================================================
  // AGENDAMIENTO PÚBLICO (sin sesión)
  // =====================================================

  /**
   * Enviar OTP para confirmar agendamiento
   */
  async enviarOtpAgendamiento(telefono: string): Promise<{ 
    success: boolean
    message: string
    codigo_debug?: string 
  }> {
    const response = await api.post('/publico/agendar/otp', { telefono })
    return response.data
  },

  /**
   * Agendar cita pública (crea cliente + cita en una transacción)
   */
  async agendarPublico(data: AgendarPublicoRequest): Promise<{ 
    success: boolean
    message: string
    cita?: CitaResponse 
  }> {
    const response = await api.post('/publico/agendar', data)
    return response.data
  },

  /**
   * Agendar múltiples citas coordinadas (diferentes empleados por servicio)
   */
  async agendarMultiples(data: AgendarMultiplesRequest): Promise<AgendarMultiplesResponse> {
    const response = await api.post('/publico/agendar/multiples', data)
    return response.data
  },

  /**
   * Validar si se requiere anticipo para una cita
   */
  async validarAnticipo(data: {
    servicios: number[]
    total: number
    fecha_cita: string
  }): Promise<{
    success: boolean
    data: {
      requiere_anticipo: boolean
      monto_anticipo: number
      regla_aplicada: {
        id: number
        nombre: string
      } | null
    }
  }> {
    const response = await api.post('/publico/anticipo/validar', data)
    return response.data
  },

  // =====================================================
  // CITAS DEL CLIENTE (con sesión)
  // =====================================================

  /**
   * Obtener mis citas (cliente)
   */
  async misCitas(params?: {
    estado?: string
    desde?: string
    hasta?: string
    per_page?: number
    page?: number
  }): Promise<CitasListResponse> {
    const queryParams = new URLSearchParams()
    if (params?.estado) queryParams.append('estado', params.estado)
    if (params?.desde) queryParams.append('desde', params.desde)
    if (params?.hasta) queryParams.append('hasta', params.hasta)
    if (params?.per_page) queryParams.append('per_page', params.per_page.toString())
    if (params?.page) queryParams.append('page', params.page.toString())

    const response = await api.get(`/cliente/citas?${queryParams}`)
    return response.data.data
  },

  /**
   * Ver detalle de mi cita
   */
  async verMiCita(id: number): Promise<CitaResponse> {
    const response = await api.get(`/cliente/citas/${id}`)
    return response.data.data
  },

  /**
   * Agendar nueva cita
   */
  async agendar(data: AgendarCitaRequest): Promise<{ success: boolean; message: string; cita?: CitaResponse }> {
    const response = await api.post('/cliente/citas', data)
    return response.data
  },

  /**
   * Modificar mi cita
   */
  async modificar(id: number, data: { fecha_hora?: string; notas?: string }): Promise<{ success: boolean; message: string; cita?: CitaResponse }> {
    const response = await api.put(`/cliente/citas/${id}`, data)
    return response.data
  },

  /**
   * Cancelar mi cita
   */
  async cancelar(id: number, motivo?: string): Promise<{ success: boolean; message: string }> {
    const response = await api.post(`/cliente/citas/${id}/cancelar`, { motivo })
    return response.data
  },

  /**
   * Calificar una cita
   */
  async calificar(id: number, data: CalificarRequest): Promise<{ success: boolean; message: string }> {
    const response = await api.post(`/cliente/citas/${id}/calificar`, data)
    return response.data
  },

  // =====================================================
  // CITAS DEL EMPLEADO
  // =====================================================

  /**
   * Obtener citas del día (empleado)
   */
  async citasDelDia(fecha?: string): Promise<{ fecha: string; citas: CitaResponse[] }> {
    const params = fecha ? `?fecha=${fecha}` : ''
    const response = await api.get(`/empleado/calendario/dia${params}`)
    return response.data.data
  },

  /**
   * Obtener citas de la semana (empleado)
   */
  async citasDeLaSemana(fecha?: string): Promise<{
    inicio_semana: string
    fin_semana: string
    citas_por_dia: Record<string, CitaResponse[]>
  }> {
    const params = fecha ? `?fecha=${fecha}` : ''
    const response = await api.get(`/empleado/calendario/semana${params}`)
    return response.data.data
  },

  /**
   * Mis citas como empleado
   */
  async misCitasEmpleado(params?: {
    estado?: string
    desde?: string
    hasta?: string
    per_page?: number
  }): Promise<CitasListResponse> {
    const queryParams = new URLSearchParams()
    if (params?.estado) queryParams.append('estado', params.estado)
    if (params?.desde) queryParams.append('desde', params.desde)
    if (params?.hasta) queryParams.append('hasta', params.hasta)
    if (params?.per_page) queryParams.append('per_page', params.per_page.toString())

    const response = await api.get(`/empleado/citas?${queryParams}`)
    return response.data.data
  },

  /**
   * Cambiar estado de cita (empleado)
   */
  async cambiarEstado(
    id: number,
    estado: 'confirmada' | 'en_proceso' | 'completada' | 'no_show'
  ): Promise<{ success: boolean; message: string; estado: string }> {
    const response = await api.put(`/empleado/citas/${id}/estado`, { estado })
    return response.data
  },

  /**
   * Subir foto a cita (empleado)
   */
  async subirFoto(
    id: number,
    foto: File,
    tipo: 'antes' | 'despues' | 'proceso',
    descripcion?: string
  ): Promise<{ success: boolean; message: string; foto?: { id: number; tipo: string; url: string } }> {
    const formData = new FormData()
    formData.append('foto', foto)
    formData.append('tipo', tipo)
    if (descripcion) formData.append('descripcion', descripcion)

    const response = await api.post(`/empleado/citas/${id}/fotos`, formData, {
      headers: { 'Content-Type': 'multipart/form-data' },
    })
    return response.data
  },

  // =====================================================
  // CITAS ADMIN
  // =====================================================

  /**
   * Listar todas las citas (admin)
   */
  async listarTodas(params?: {
    estado?: string
    empleado_id?: number
    cliente_id?: number
    desde?: string
    hasta?: string
    per_page?: number
    page?: number
  }): Promise<CitasListResponse> {
    const queryParams = new URLSearchParams()
    if (params?.estado) queryParams.append('estado', params.estado)
    if (params?.empleado_id) queryParams.append('empleado_id', params.empleado_id.toString())
    if (params?.cliente_id) queryParams.append('cliente_id', params.cliente_id.toString())
    if (params?.desde) queryParams.append('desde', params.desde)
    if (params?.hasta) queryParams.append('hasta', params.hasta)
    if (params?.per_page) queryParams.append('per_page', params.per_page.toString())
    if (params?.page) queryParams.append('page', params.page.toString())

    const response = await api.get(`/admin/citas?${queryParams}`)
    return {
      citas: response.data.data,
      pagination: response.data.pagination,
    }
  },

  /**
   * Ver cita específica (admin)
   */
  async ver(id: number): Promise<CitaResponse> {
    const response = await api.get(`/admin/citas/${id}`)
    return response.data.data
  },

  /**
   * Calendario de citas (admin)
   */
  async calendario(mes?: number, anio?: number): Promise<{
    mes: number
    anio: number
    citas_por_dia: Record<string, {
      id: number
      hora: string
      cliente: string
      empleado: string
      servicio: string
      estado: string
    }[]>
  }> {
    const params = new URLSearchParams()
    if (mes) params.append('mes', mes.toString())
    if (anio) params.append('anio', anio.toString())

    const response = await api.get(`/admin/citas-calendario?${params}`)
    return response.data.data
  },
}

export default citaService

