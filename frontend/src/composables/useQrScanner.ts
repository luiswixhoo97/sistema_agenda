import { ref } from 'vue'
import { Capacitor } from '@capacitor/core'
import { Camera } from '@capacitor/camera'
import { Html5Qrcode } from 'html5-qrcode'

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
      console.warn('No se pudo verificar permisos de cámara:', e)
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
      console.error('Error solicitando permisos:', e)
      error.value = e.message || 'Error al solicitar permisos'
      return false
    }
  }

  /**
   * Obtener la lista de cámaras disponibles
   */
  async function getCameras(): Promise<{ id: string; label: string }[]> {
    try {
      const devices = await Html5Qrcode.getCameras()
      return devices.map(d => ({ id: d.id, label: d.label }))
    } catch (e) {
      console.error('Error obteniendo cámaras:', e)
      return []
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
    
    // Primero solicitar permisos explícitamente en Android
    if (isNative) {
      try {
        const permStatus = await Camera.checkPermissions()
        if (permStatus.camera !== 'granted') {
          const requestResult = await Camera.requestPermissions({ permissions: ['camera'] })
          if (requestResult.camera !== 'granted') {
            error.value = 'Permiso de cámara denegado. Por favor habilita el acceso a la cámara en la configuración de la app.'
            return false
          }
        }
        hasPermission.value = true
      } catch (e: any) {
        console.error('Error con permisos de cámara:', e)
        error.value = 'Error al solicitar permisos de cámara'
        return false
      }
    }

    try {
      // Esperar un momento para que el DOM esté listo
      await new Promise(resolve => setTimeout(resolve, 200))
      
      // Verificar que el elemento exista
      const element = document.getElementById(elementId)
      if (!element) {
        error.value = 'Error interno: contenedor de cámara no encontrado'
        return false
      }

      html5QrCode = new Html5Qrcode(elementId)
      
      // Intentar obtener las cámaras disponibles
      let cameraId: string | { facingMode: string } = { facingMode: 'environment' }
      
      try {
        const cameras = await Html5Qrcode.getCameras()
        if (cameras && cameras.length > 0) {
          // Buscar cámara trasera
          const backCamera = cameras.find(c => 
            c.label.toLowerCase().includes('back') || 
            c.label.toLowerCase().includes('trasera') ||
            c.label.toLowerCase().includes('rear') ||
            c.label.toLowerCase().includes('environment')
          )
          if (backCamera) {
            cameraId = backCamera.id
          } else {
            // Usar la última cámara (generalmente la trasera)
            const lastCamera = cameras[cameras.length - 1]
            if (lastCamera) {
              cameraId = lastCamera.id
            }
          }
          console.log('Cámaras disponibles:', cameras.map(c => c.label))
          console.log('Usando cámara:', cameraId)
        }
      } catch (camError) {
        console.warn('No se pudieron listar cámaras, usando facingMode:', camError)
      }

      const config = {
        fps: 10,
        qrbox: { width: 250, height: 250 },
        aspectRatio: 1,
        disableFlip: false,
        experimentalFeatures: {
          useBarCodeDetectorIfSupported: true
        }
      }

      await html5QrCode.start(
        cameraId,
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
              !errorMessage.includes('NotFoundException') &&
              !errorMessage.includes('No MultiFormat Readers')) {
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
          e.name === 'NotAllowedError' ||
          e.message?.includes('Permission')) {
        error.value = 'Permiso de cámara denegado. Por favor habilita el acceso a la cámara en la configuración de la app.'
      } else if (e.message?.includes('NotFoundError') || 
                 e.name === 'NotFoundError' ||
                 e.message?.includes('Requested device not found')) {
        error.value = 'No se encontró ninguna cámara en el dispositivo.'
      } else if (e.message?.includes('NotReadableError') ||
                 e.name === 'NotReadableError' ||
                 e.message?.includes('Could not start video source')) {
        error.value = 'La cámara está siendo usada por otra aplicación o no está disponible.'
      } else if (e.message?.includes('OverconstrainedError')) {
        error.value = 'La configuración de cámara no es compatible con tu dispositivo.'
      } else {
        error.value = 'Error al iniciar el escáner de QR. Verifica que la app tenga permisos de cámara.'
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
    if (urlMatch && urlMatch[1]) {
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

