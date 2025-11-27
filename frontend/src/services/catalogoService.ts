import api from './api'
import type { Categoria, Servicio, Empleado } from '@/types'

export interface CategoriaConServicios {
  id: number
  nombre: string
  descripcion?: string
  servicios_count: number
  servicios: {
    id: number
    nombre: string
    precio: number
    duracion: number
  }[]
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
}

export interface EmpleadoPublico {
  id: number
  nombre: string
  foto?: string
  bio?: string
  especialidades?: string
  promedio_calificacion: number
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
}

export default catalogoService

