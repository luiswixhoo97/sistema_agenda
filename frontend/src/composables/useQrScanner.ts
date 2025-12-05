import { ref } from 'vue'
import { Capacitor } from '@capacitor/core'
import { Camera, CameraPermissionState } from '@capacitor/camera'
import { Html5Qrcode, Html5QrcodeScanner } from 'html5-qrcode'

export function useQrScanner() {
  const scanning = ref(false)
  const error = ref<string | null>(null)
  const hasPermission = ref<boolean | null>(null)
  
  let html5QrCode: Html5Qrcode | null = null
  
  const isNative = Capacitor.isNativePlatform()

  /**
   * Verificar permisos de cámara
   */
  async function checkPermissions(): Promise<boolean> {
    try {
      if (isNative) {
        // En plataforma nativa, usar Capacitor Camera
        const status = await Camera.checkPermissions()
        hasPermission.value = status.camera === 'granted'
        return hasPermission.value
      } else {
        // En web, verificar permisos del navegador
        const result = await navigator.permissions.query({ name: 'camera' as PermissionName })
        hasPermission.value = result.state === 'granted'
        return hasPermission.value
      }
    } catch (e) {
      // En algunos navegadores no se puede consultar permisos
      hasPermission.value = null
      return true // Intentar de todas formas
    }
  }

  /**
   * Solicitar permisos de cámara
   */
  async function requestPermissions(): Promise<boolean> {
    try {
      if (isNative) {
        // En plataforma nativa
        const status = await Camera.requestPermissions({ permissions: ['camera'] })
        hasPermission.value = status.camera === 'granted'
        
        if (!hasPermission.value) {
          error.value = 'Se requieren permisos de cámara para escanear códigos QR'
        }
        
        return hasPermission.value
      } else {
        // En web, el permiso se solicita al acceder a getUserMedia
        // que se hace automáticamente al iniciar el escáner
        return true
      }
    } catch (e: any) {
      error.value = e.message || 'Error al solicitar permisos'
      return false
    }
  }

  /**
   * Iniciar escáner de QR
   */
  async function startScanner(
    elementId: string,
    onSuccess: (decodedText: string) => void,
    onError?: (errorMessage: string) => void
  ): Promise<boolean> {
    error.value = null
    
    // Verificar/solicitar permisos
    const hasAccess = await checkPermissions()
    if (!hasAccess) {
      const granted = await requestPermissions()
      if (!granted) {
        return false
      }
    }

    try {
      html5QrCode = new Html5Qrcode(elementId)
      
      const config = {
        fps: 10,
        qrbox: { width: 250, height: 250 },
        aspectRatio: 1,
        // En móviles usar la cámara trasera
        ...(isNative && { videoConstraints: { facingMode: 'environment' } })
      }

      await html5QrCode.start(
        { facingMode: 'environment' },
        config,
        (decodedText) => {
          // Detener escáner antes de procesar
          stopScanner().then(() => {
            onSuccess(decodedText)
          })
        },
        (errorMessage) => {
          // Ignorar errores de "no QR found" - es normal mientras busca
          if (!errorMessage.includes('No QR code found') && 
              !errorMessage.includes('NotFoundException')) {
            onError?.(errorMessage)
          }
        }
      )

      scanning.value = true
      return true
    } catch (e: any) {
      console.error('Error iniciando escáner:', e)
      
      // Manejar errores específicos
      if (e.message?.includes('Permission denied') || 
          e.name === 'NotAllowedError') {
        error.value = 'Permiso de cámara denegado. Por favor habilita el acceso a la cámara en la configuración de la app.'
      } else if (e.message?.includes('NotFoundError') || 
                 e.name === 'NotFoundError') {
        error.value = 'No se encontró ninguna cámara en el dispositivo.'
      } else if (e.message?.includes('NotReadableError')) {
        error.value = 'La cámara está siendo usada por otra aplicación.'
      } else {
        error.value = e.message || 'Error al iniciar el escáner de QR'
      }
      
      scanning.value = false
      return false
    }
  }

  /**
   * Detener escáner
   */
  async function stopScanner(): Promise<void> {
    if (html5QrCode) {
      try {
        if (html5QrCode.isScanning) {
          await html5QrCode.stop()
        }
        html5QrCode.clear()
      } catch (e) {
        console.error('Error deteniendo escáner:', e)
      }
      html5QrCode = null
    }
    scanning.value = false
  }

  /**
   * Extraer token de una URL de QR
   * Formatos soportados:
   * - https://miapp.com/scan-qr/TOKEN
   * - /scan-qr/TOKEN
   * - TOKEN (directo)
   */
  function extractToken(qrContent: string): string | null {
    // Intentar extraer de URL
    const urlMatch = qrContent.match(/scan-qr\/([a-zA-Z0-9]+)/)
    if (urlMatch) {
      return urlMatch[1]
    }
    
    // Si es un token directo (alfanumérico de 32+ caracteres)
    if (/^[a-zA-Z0-9]{32,}$/.test(qrContent.trim())) {
      return qrContent.trim()
    }
    
    return null
  }

  /**
   * Estado del escáner
   */
  function isScanning(): boolean {
    return scanning.value
  }

  return {
    scanning,
    error,
    hasPermission,
    isNative,
    checkPermissions,
    requestPermissions,
    startScanner,
    stopScanner,
    extractToken,
    isScanning
  }
}

