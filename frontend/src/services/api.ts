import axios from 'axios'
import type { AxiosInstance, AxiosError, InternalAxiosRequestConfig } from 'axios'

// Configuración base - usar variable de entorno o detectar automáticamente
const getApiUrl = () => {
  // Si hay variable de entorno, usarla (prioridad)
  if (import.meta.env.VITE_API_URL) {
    return import.meta.env.VITE_API_URL
  }
  

  // Por defecto: URL de producción
  return 'https://salmon-eland-125157.hostingersite.com/backend/public/api'
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
  // No enviar cookies para evitar problemas con CSRF en apps móviles
  withCredentials: false,
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

// Contador de errores 401 consecutivos
let consecutive401Errors = 0
const MAX_401_BEFORE_LOGOUT = 3

// Interceptor para manejar errores 401 - Con tolerancia a errores temporales
api.interceptors.response.use(
  (response) => {
    // Reset contador en respuestas exitosas
    consecutive401Errors = 0
    return response
  },
  (error: AxiosError) => {
    // Si es error de red (sin respuesta), NO desloguear
    if (!error.response) {
      console.warn('Error de red, no se deslogueará al usuario')
      return Promise.reject(error)
    }

    // Si es 401, verificar si debemos desloguear
    if (error.response?.status === 401) {
      const url = error.config?.url || ''
      
      // No limpiar si es una ruta de login/auth
      const isAuthRoute = url.includes('/auth/') || url.includes('/publico/') || url.includes('/cliente/auth/')
      
      if (!isAuthRoute) {
        consecutive401Errors++
        
        console.warn(`Error 401 #${consecutive401Errors} de ${MAX_401_BEFORE_LOGOUT}`)
        
        // Solo desloguear después de varios errores 401 consecutivos
        // Esto evita desloguear por errores temporales del servidor
        if (consecutive401Errors >= MAX_401_BEFORE_LOGOUT) {
          console.warn('Demasiados errores 401, cerrando sesión')
          localStorage.removeItem('auth_token')
          localStorage.removeItem('user')
          localStorage.removeItem('user_type')
          localStorage.removeItem('empleado')
          consecutive401Errors = 0
          
          // Redirigir solo si no estamos en login
          if (!window.location.pathname.includes('login') && !window.location.pathname.includes('home')) {
            window.location.href = '/'
          }
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
