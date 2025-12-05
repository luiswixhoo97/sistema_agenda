import axios from 'axios'
import type { AxiosInstance, AxiosError, InternalAxiosRequestConfig } from 'axios'

// Configuración base - detectar si estamos en localhost o en red local
const getApiUrl = () => {
  // Si hay variable de entorno, usarla
  if (import.meta.env.VITE_API_URL) {
    return import.meta.env.VITE_API_URL
  }
  
  // URL del servidor de producción
  return 'https://salmon-eland-125157.hostingersite.com/backend/public/api'
  
  /* Para desarrollo local, descomenta esto:
  if (window.location.hostname === 'localhost' || window.location.hostname === '127.0.0.1') {
    return 'http://localhost:8000/api'
  }
  return `http://${window.location.hostname}:8000/api`
  */
}

const API_URL = getApiUrl()

// Crear instancia de axios
const api: AxiosInstance = axios.create({
  baseURL: API_URL,
  headers: {
    'Content-Type': 'application/json',
    'Accept': 'application/json',
  },
  timeout: 30000,
})

// Interceptor para agregar token
api.interceptors.request.use(
  (config: InternalAxiosRequestConfig) => {
    const token = localStorage.getItem('auth_token')
    if (token) {
      config.headers.Authorization = `Bearer ${token}`
    }
    return config
  },
  (error) => Promise.reject(error)
)

// Interceptor para manejar errores 401 - SIN refresh automático
api.interceptors.response.use(
  (response) => response,
  (error: AxiosError) => {
    // Si es 401, limpiar sesión (token inválido/expirado)
    if (error.response?.status === 401) {
      const url = error.config?.url || ''
      
      // No limpiar si es una ruta de login/auth
      const isAuthRoute = url.includes('/auth/') || url.includes('/publico/')
      
      if (!isAuthRoute) {
        localStorage.removeItem('auth_token')
        localStorage.removeItem('user')
        localStorage.removeItem('user_type')
        
        // Redirigir solo si no estamos en login
        if (!window.location.pathname.includes('login')) {
          window.location.href = '/login-cliente'
        }
      }
    }

    return Promise.reject(error)
  }
)

export default api

// Tipos de respuesta comunes
export interface ApiResponse<T> {
  success: boolean
  message?: string
  data?: T
}

export interface PaginatedResponse<T> {
  data: T[]
  current_page: number
  last_page: number
  per_page: number
  total: number
}
