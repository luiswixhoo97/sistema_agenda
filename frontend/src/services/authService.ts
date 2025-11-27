import api from './api'

export interface LoginCredentials {
  email: string
  password: string
}

export interface OtpRequest {
  telefono: string
}

export interface OtpVerification {
  telefono: string
  codigo: string
}

export interface ClienteRegistro {
  telefono: string
  codigo: string
  nombre: string
  email?: string
  fecha_nacimiento?: string
}

export interface User {
  id: number
  nombre: string
  email: string
  telefono?: string
  role?: string
}

export interface Cliente {
  id: number
  nombre: string
  telefono: string
  email?: string
}

export interface AuthResponse {
  success: boolean
  token?: string
  user?: User
  cliente?: Cliente
  empleado?: {
    id: number
    foto?: string
    bio?: string
  }
  requiere_registro?: boolean
  mensaje?: string
}

export interface OtpResponse {
  success: boolean
  es_nuevo: boolean
  mensaje: string
  expira_en: number
  reenviar_en: number
  codigo_debug?: string // Solo en desarrollo
}

export interface OtpVerifyResponse {
  success: boolean
  requiere_registro?: boolean
  telefono?: string
  token?: string
  cliente?: Cliente
  mensaje?: string
  intentos_restantes?: number
}

export const authService = {
  // =====================================================
  // Autenticación de Empleados/Admin
  // =====================================================
  
  async login(email: string, password: string): Promise<AuthResponse> {
    const response = await api.post('/auth/login', { email, password })
    
    if (response.data.success) {
      localStorage.setItem('auth_token', response.data.token)
      localStorage.setItem('user', JSON.stringify(response.data.user))
      localStorage.setItem('user_type', response.data.user?.role?.nombre || 'empleado')
    }
    
    return response.data
  },

  // =====================================================
  // Autenticación de Clientes (OTP)
  // =====================================================

  async solicitarOtp(telefono: string): Promise<OtpResponse> {
    const response = await api.post('/auth/cliente/otp/solicitar', { telefono })
    return response.data
  },

  async solicitarOtpCliente(telefono: string): Promise<OtpResponse> {
    return this.solicitarOtp(telefono)
  },

  async verificarOtp(data: OtpVerification): Promise<OtpVerifyResponse> {
    const response = await api.post('/auth/cliente/otp/verificar', data)
    
    if (response.data.success && response.data.token) {
      localStorage.setItem('auth_token', response.data.token)
      localStorage.setItem('user', JSON.stringify(response.data.cliente))
      localStorage.setItem('user_type', 'cliente')
    }
    
    return response.data
  },

  async verificarOtpCliente(telefono: string, codigo: string): Promise<AuthResponse> {
    const response = await api.post('/auth/cliente/otp/verificar', { telefono, codigo })
    
    if (response.data.success && response.data.token) {
      localStorage.setItem('auth_token', response.data.token)
      localStorage.setItem('user', JSON.stringify(response.data.cliente))
      localStorage.setItem('user_type', 'cliente')
    }
    
    return response.data
  },

  async registrarCliente(data: { nombre: string; telefono: string; email?: string }): Promise<AuthResponse> {
    const response = await api.post('/auth/cliente/registrar', data)
    
    if (response.data.success) {
      localStorage.setItem('auth_token', response.data.token)
      localStorage.setItem('user', JSON.stringify(response.data.cliente))
      localStorage.setItem('user_type', 'cliente')
    }
    
    return response.data
  },

  // =====================================================
  // Sesión
  // =====================================================

  async logout(): Promise<void> {
    try {
      await api.post('/auth/logout')
    } finally {
      this.clearSession()
    }
  },

  async me(): Promise<{ tipo: string; usuario: User | Cliente }> {
    const response = await api.get('/auth/me')
    return response.data
  },

  async refresh(): Promise<{ token: string }> {
    const response = await api.post('/auth/refresh')
    
    if (response.data.success) {
      localStorage.setItem('auth_token', response.data.token)
    }
    
    return response.data
  },

  // =====================================================
  // Helpers locales
  // =====================================================

  getToken(): string | null {
    return localStorage.getItem('auth_token')
  },

  getUser(): User | Cliente | null {
    const userStr = localStorage.getItem('user')
    return userStr ? JSON.parse(userStr) : null
  },

  getUserType(): 'cliente' | 'empleado' | 'admin' | null {
    return localStorage.getItem('user_type') as 'cliente' | 'empleado' | 'admin' | null
  },

  isAuthenticated(): boolean {
    return !!this.getToken()
  },

  isCliente(): boolean {
    return this.getUserType() === 'cliente'
  },

  isEmpleado(): boolean {
    return this.getUserType() === 'empleado'
  },

  isAdmin(): boolean {
    return this.getUserType() === 'admin'
  },

  clearSession(): void {
    localStorage.removeItem('auth_token')
    localStorage.removeItem('user')
    localStorage.removeItem('user_type')
  },
}

export default authService

