import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import authService, { type User, type Cliente } from '@/services/authService'

export const useAuthStore = defineStore('auth', () => {
  // Estado
  const token = ref<string | null>(authService.getToken())
  const user = ref<User | Cliente | null>(authService.getUser())
  const userType = ref<'cliente' | 'empleado' | 'admin' | null>(authService.getUserType())
  const loading = ref(false)
  const error = ref<string | null>(null)

  // Getters
  const isAuthenticated = computed(() => !!token.value)
  const isCliente = computed(() => userType.value === 'cliente')
  const isEmpleado = computed(() => userType.value === 'empleado')
  const isAdmin = computed(() => userType.value === 'admin')
  const userName = computed(() => user.value?.nombre || '')

  // Actions
  async function loginEmpleado(email: string, password: string) {
    loading.value = true
    error.value = null

    try {
      const response = await authService.login({ email, password })
      
      if (response.success) {
        token.value = response.token
        user.value = response.user!
        userType.value = response.user!.role as 'empleado' | 'admin'
        return true
      }
      
      return false
    } catch (e: any) {
      error.value = e.response?.data?.message || 'Error al iniciar sesión'
      return false
    } finally {
      loading.value = false
    }
  }

  async function solicitarOtp(telefono: string) {
    loading.value = true
    error.value = null

    try {
      const response = await authService.solicitarOtp(telefono)
      return response
    } catch (e: any) {
      error.value = e.response?.data?.message || 'Error al solicitar código'
      throw e
    } finally {
      loading.value = false
    }
  }

  async function verificarOtp(telefono: string, codigo: string) {
    loading.value = true
    error.value = null

    try {
      const response = await authService.verificarOtp({ telefono, codigo })
      
      if (response.success && response.token) {
        token.value = response.token
        user.value = response.cliente!
        userType.value = 'cliente'
      }
      
      return response
    } catch (e: any) {
      error.value = e.response?.data?.message || 'Código incorrecto'
      throw e
    } finally {
      loading.value = false
    }
  }

  async function registrarCliente(data: {
    telefono: string
    codigo: string
    nombre: string
    email?: string
    fecha_nacimiento?: string
  }) {
    loading.value = true
    error.value = null

    try {
      const response = await authService.registrarCliente(data)
      
      if (response.success) {
        token.value = response.token
        user.value = response.cliente!
        userType.value = 'cliente'
        return true
      }
      
      return false
    } catch (e: any) {
      error.value = e.response?.data?.message || 'Error en el registro'
      return false
    } finally {
      loading.value = false
    }
  }

  async function logout() {
    loading.value = true
    
    try {
      await authService.logout()
    } finally {
      token.value = null
      user.value = null
      userType.value = null
      loading.value = false
    }
  }

  async function checkAuth() {
    if (!token.value) return false

    try {
      const response = await authService.me()
      user.value = response.usuario
      userType.value = response.tipo as 'cliente' | 'empleado' | 'admin'
      return true
    } catch {
      logout()
      return false
    }
  }

  function clearError() {
    error.value = null
  }

  function initFromStorage() {
    token.value = authService.getToken()
    user.value = authService.getUser()
    userType.value = authService.getUserType()
  }

  function setAuth(userData: User | Cliente, authToken: string) {
    token.value = authToken
    user.value = userData
    // Determinar tipo de usuario
    if ('role' in userData && userData.role) {
      const roleName = typeof userData.role === 'string' ? userData.role : (userData.role as any).nombre
      userType.value = roleName as 'cliente' | 'empleado' | 'admin'
    } else {
      userType.value = 'cliente'
    }
    // Guardar en localStorage
    localStorage.setItem('auth_token', authToken)
    localStorage.setItem('user', JSON.stringify(userData))
    localStorage.setItem('user_type', userType.value)
  }

  return {
    // Estado
    token,
    user,
    userType,
    loading,
    error,
    // Getters
    isAuthenticated,
    isCliente,
    isEmpleado,
    isAdmin,
    userName,
    // Actions
    loginEmpleado,
    solicitarOtp,
    verificarOtp,
    registrarCliente,
    logout,
    checkAuth,
    clearError,
    initFromStorage,
    setAuth,
  }
})

