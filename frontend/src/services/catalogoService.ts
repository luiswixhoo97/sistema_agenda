import api from './api'
import type { Categoria, Servicio, Empleado } from '@/types'

export interface ServicioEnCategoria {
  id: number | string
  nombre: string
  precio: number
  duracion: number
  es_promocion?: boolean
  promocion_id?: number
  descuento?: string
  precio_con_descuento?: number
  servicios_incluidos?: Array<{
    id: number
    nombre: string
    precio: number
    duracion: number
  }>
}

export interface CategoriaConServicios {
  id: number
  nombre: string
  descripcion?: string
  servicios_count: number
  es_promociones?: boolean
  servicios: ServicioEnCategoria[]
  [key: string]: any // Permitir propiedades adicionales
}

export interface ServicioPublico {
  id: number
  nombre: string
  descripcion?: string
  precio: number
  precio_texto: string
  duracion: number
  duracion_texto: string
  categoria: {
    id: number
    nombre: string
  }
  // Propiedades de promoción (opcionales)
  es_promocion?: boolean
  promocion_id?: number
  descuento?: string
  servicios_incluidos?: Array<{
    id: number
    nombre: string
    precio: number
    duracion: number
  }>
  precio_con_descuento?: number
  precio_con_descuento_texto?: string
}

export interface EmpleadoPublico {
  id: number
  nombre: string
  foto?: string
  bio?: string
  especialidades?: string
  promedio_calificacion: number
  activo?: boolean
  servicios: {
    id: number
    nombre: string
  }[]
}

export interface PromocionPublica {
  id: number
  nombre: string
  descripcion?: string
  descuento: string
  fecha_fin: string
  dias_restantes: number
  horas_restantes?: number
  minutos_restantes?: number
  imagen?: string | null
  servicios_aplicables?: number[] | null
  servicios_info?: Array<{
    id: number
    nombre: string
    duracion: number
  }>
  tiempo_total?: number
}

const catalogoService = {
  /**
   * Obtener categorías con servicios
   */
  async obtenerCategorias(): Promise<CategoriaConServicios[]> {
    const response = await api.get('/publico/categorias')
    return response.data.data
  },

  /**
   * Obtener servicios
   */
  async obtenerServicios(categoriaId?: number): Promise<ServicioPublico[]> {
    const params = categoriaId ? `?categoria_id=${categoriaId}` : ''
    const response = await api.get(`/publico/servicios${params}`)
    return response.data.data
  },

  /**
   * Obtener empleados
   */
  async obtenerEmpleados(servicioId?: number): Promise<EmpleadoPublico[]> {
    const params = servicioId ? `?servicio_id=${servicioId}` : ''
    const response = await api.get(`/publico/empleados${params}`)
    return response.data.data
  },

  /**
   * Obtener promociones vigentes
   */
  async obtenerPromociones(): Promise<PromocionPublica[]> {
    const response = await api.get('/publico/promociones')
    return response.data.data
  },

  /**
   * Buscar cliente por teléfono
   */
  async buscarClientePorTelefono(telefono: string): Promise<{
    success: boolean
    data?: {
      nombre: string
      email: string | null
    }
    message?: string
  }> {
    try {
      const response = await api.get(`/publico/cliente/telefono/${telefono}`)
      return response.data
    } catch (error: any) {
      if (error.response?.status === 404) {
        return {
          success: false,
          message: 'Cliente no encontrado'
        }
      }
      throw error
    }
  },
}

export default catalogoService

