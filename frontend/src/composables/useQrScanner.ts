import { ref } from 'vue'
import { Capacitor } from '@capacitor/core'
import { Html5Qrcode } from 'html5-qrcode'
import { 
  BarcodeScanner, 
  BarcodeFormat,
  LensFacing
} from '@capacitor-mlkit/barcode-scanning'

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
        // En plataforma nativa, usar ML Kit BarcodeScanner
        const status = await BarcodeScanner.checkPermissions()
        hasPermission.value = status.camera === 'granted'
        return hasPermission.value
      } else {
        // En web, verificar permisos del navegador
        const result = await navigator.permissions.query({ name: 'camera' as PermissionName })
        hasPermission.value = result.state === 'granted'
        return hasPermission.value
      }
    } catch (e) {
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
        // En plataforma nativa, usar ML Kit
        const status = await BarcodeScanner.requestPermissions()
        hasPermission.value = status.camera === 'granted'
        
        if (!hasPermission.value) {
          error.value = 'Se requieren permisos de cámara para escanear códigos QR'
        }
        
        return hasPermission.value
      } else {
        // En web, el permiso se solicita al acceder a getUserMedia
        return true
      }
    } catch (e: any) {
      console.error('Error solicitando permisos:', e)
      error.value = e.message || 'Error al solicitar permisos'
      return false
    }
  }

  /**
   * Verificar si Google Barcode Scanner está disponible (Android)
   */
  async function checkGoogleBarcodeScanner(): Promise<boolean> {
    if (!isNative) return false
    
    try {
      const { available } = await BarcodeScanner.isGoogleBarcodeScannerModuleAvailable()
      if (!available) {
        console.log('Instalando módulo de Google Barcode Scanner...')
        await BarcodeScanner.installGoogleBarcodeScannerModule()
        return true
      }
      return true
    } catch (e) {
      console.warn('Google Barcode Scanner no disponible:', e)
      return false
    }
  }

  /**
   * Iniciar escáner de QR (NATIVO con ML Kit)
   */
  async function startNativeScanner(
    onSuccess: (decodedText: string) => void
  ): Promise<boolean> {
    error.value = null
    
    try {
      // Verificar/solicitar permisos
      const permStatus = await BarcodeScanner.checkPermissions()
      if (permStatus.camera !== 'granted') {
        const requestResult = await BarcodeScanner.requestPermissions()
        if (requestResult.camera !== 'granted') {
          error.value = 'Permiso de cámara denegado. Por favor habilita el acceso a la cámara en la configuración de la app.'
          return false
        }
      }
      hasPermission.value = true

      // Verificar que el módulo de Google esté disponible
      await checkGoogleBarcodeScanner()

      // Agregar clase al body para ocultar el fondo
      document.querySelector('body')?.classList.add('barcode-scanner-active')
      
      scanning.value = true

      // Iniciar escaneo con ML Kit
      const result = await BarcodeScanner.scan({
        formats: [BarcodeFormat.QrCode],
      })

      // Remover clase del body
      document.querySelector('body')?.classList.remove('barcode-scanner-active')
      scanning.value = false

      if (result.barcodes && result.barcodes.length > 0) {
        const barcode = result.barcodes[0]
        if (barcode && barcode.rawValue) {
          onSuccess(barcode.rawValue)
          return true
        }
      }

      error.value = 'No se detectó ningún código QR'
      return false

    } catch (e: any) {
      document.querySelector('body')?.classList.remove('barcode-scanner-active')
      scanning.value = false
      console.error('Error en escáner nativo:', e)
      
      if (e.message?.includes('canceled') || e.message?.includes('cancelled')) {
        // Usuario canceló el escaneo
        error.value = null
        return false
      }
      
      if (e.message?.includes('Permission') || e.message?.includes('permission')) {
        error.value = 'Permiso de cámara denegado. Por favor habilita el acceso a la cámara en la configuración de la app.'
      } else {
        error.value = e.message || 'Error al escanear código QR'
      }
      return false
    }
  }

  /**
   * Iniciar escáner de QR (WEB con html5-qrcode)
   */
  async function startWebScanner(
    elementId: string,
    onSuccess: (decodedText: string) => void,
    onError?: (errorMessage: string) => void
  ): Promise<boolean> {
    error.value = null

    try {
      await new Promise(resolve => setTimeout(resolve, 200))
      
      const element = document.getElementById(elementId)
      if (!element) {
        error.value = 'Error interno: contenedor de cámara no encontrado'
        return false
      }

      html5QrCode = new Html5Qrcode(elementId)
      
      let cameraId: string | { facingMode: string } = { facingMode: 'environment' }
      
      try {
        const cameras = await Html5Qrcode.getCameras()
        if (cameras && cameras.length > 0) {
          const backCamera = cameras.find(c => 
            c.label.toLowerCase().includes('back') || 
            c.label.toLowerCase().includes('trasera') ||
            c.label.toLowerCase().includes('rear') ||
            c.label.toLowerCase().includes('environment')
          )
          if (backCamera) {
            cameraId = backCamera.id
          } else {
            const lastCamera = cameras[cameras.length - 1]
            if (lastCamera) {
              cameraId = lastCamera.id
            }
          }
        }
      } catch (camError) {
        console.warn('No se pudieron listar cámaras:', camError)
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
          stopScanner().then(() => {
            onSuccess(decodedText)
          })
        },
        (errorMessage) => {
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
      console.error('Error iniciando escáner web:', e)
      
      if (e.message?.includes('Permission denied') || e.name === 'NotAllowedError') {
        error.value = 'Permiso de cámara denegado.'
      } else if (e.message?.includes('NotFoundError') || e.name === 'NotFoundError') {
        error.value = 'No se encontró ninguna cámara en el dispositivo.'
      } else if (e.message?.includes('NotReadableError') || e.name === 'NotReadableError') {
        error.value = 'La cámara está siendo usada por otra aplicación.'
      } else {
        error.value = 'Error al iniciar el escáner de QR.'
      }
      
      scanning.value = false
      return false
    }
  }

  /**
   * Iniciar escáner de QR (detecta automáticamente nativo vs web)
   */
  async function startScanner(
    elementId: string,
    onSuccess: (decodedText: string) => void,
    onError?: (errorMessage: string) => void
  ): Promise<boolean> {
    if (isNative) {
      // Usar escáner nativo con ML Kit
      return startNativeScanner(onSuccess)
    } else {
      // Usar html5-qrcode para web
      return startWebScanner(elementId, onSuccess, onError)
    }
  }

  /**
   * Detener escáner
   */
  async function stopScanner(): Promise<void> {
    if (isNative) {
      try {
        await BarcodeScanner.stopScan()
        document.querySelector('body')?.classList.remove('barcode-scanner-active')
      } catch (e) {
        // Ignorar error si no estaba escaneando
      }
    }
    
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
    startNativeScanner,
    startWebScanner,
    stopScanner,
    extractToken,
    isScanning
  }
}
