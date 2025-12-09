<template>
  <div class="scan-qr-view">
    <!-- Header -->
    <div class="scan-header">
      <button class="btn-back" @click="goBack">
        <i class="fa fa-arrow-left"></i>
      </button>
      <h1>Escanear QR</h1>
      <div class="header-spacer"></div>
    </div>

    <!-- Estado: Sin permisos -->
    <div v-if="permisoDenegado" class="scanner-container">
      <div class="permission-denied">
        <div class="denied-icon">
          <i class="fa fa-camera-slash"></i>
        </div>
        <h2>Permiso de cámara requerido</h2>
        <p>Para escanear códigos QR necesitamos acceso a tu cámara.</p>
        <button class="btn-permission" @click="solicitarPermisos">
          <i class="fa fa-lock-open"></i>
          Habilitar cámara
        </button>
        <p class="permission-hint">
          Si el permiso fue denegado, ve a <strong>Configuración > {{ isNative ? 'Apps > [Esta app] > Permisos' : 'Privacidad > Cámara' }}</strong>
        </p>
      </div>
    </div>

    <!-- Escáner -->
    <div v-else class="scanner-container">
      <div v-if="!scanning && !resultado" class="scanner-placeholder">
        <div class="placeholder-icon">
          <i class="fa fa-qrcode"></i>
        </div>
        <h2>Escanear código QR</h2>
        <p>Escanea el código QR del cliente para marcar su cita como completada</p>
        <button class="btn-scan" @click="iniciarEscaner" :disabled="iniciandoEscaner">
          <i :class="iniciandoEscaner ? 'fa fa-spinner fa-spin' : 'fa fa-camera'"></i>
          {{ iniciandoEscaner ? 'Iniciando...' : 'Escanear QR' }}
        </button>
        <p v-if="isNative" class="scan-native-hint">
          <i class="fa fa-info-circle"></i>
          Se abrirá la cámara en pantalla completa
        </p>
      </div>

      <!-- Scanner activo (web y fallback) -->
      <!-- IMPORTANTE: Usar v-show en lugar de v-if para que el elemento siempre exista en el DOM -->
      <div v-show="scanning || iniciandoEscaner" class="scanner-active">
        <div id="qr-reader" ref="qrReaderRef" class="qr-reader-container"></div>
        <div class="scanner-overlay" v-if="scanning && !isNative">
          <div class="scanner-frame">
            <div class="corner top-left"></div>
            <div class="corner top-right"></div>
            <div class="corner bottom-left"></div>
            <div class="corner bottom-right"></div>
            <div class="scan-line"></div>
          </div>
        </div>
        <p class="scan-hint" v-if="scanning && !isNative">Apunta hacia el código QR</p>
        <button class="btn-cancel" @click="detenerEscaner" v-if="scanning && !isNative">
          <i class="fa fa-times"></i>
          Cancelar
        </button>
      </div>
      
      <!-- Indicador de escaneo nativo -->
      <div v-if="iniciandoEscaner && isNative && !scanning" class="scanner-loading">
        <div class="spinner"></div>
        <p>Abriendo cámara...</p>
      </div>

      <!-- Resultado exitoso -->
      <div v-if="resultado && resultado.success" class="resultado-container success">
        <div class="resultado-icon">
          <i class="fa fa-check-circle"></i>
        </div>
        <h2>¡Cita completada!</h2>
        <div class="cita-details">
          <div class="detail-item">
            <i class="fa fa-user"></i>
            <span>{{ resultado.cita?.cliente_nombre }}</span>
          </div>
          <div class="detail-item">
            <i class="fa fa-cut"></i>
            <span>{{ resultado.cita?.servicios_nombres || resultado.cita?.servicio_nombre }}</span>
          </div>
          <div class="detail-item">
            <i class="fa fa-calendar"></i>
            <span>{{ resultado.cita?.fecha }} - {{ resultado.cita?.hora }}</span>
          </div>
          <div class="detail-item">
            <i class="fa fa-dollar-sign"></i>
            <span>${{ resultado.cita?.precio_final }}</span>
          </div>
        </div>
        <button class="btn-nueva" @click="resetear">
          <i class="fa fa-qrcode"></i>
          Escanear otro QR
        </button>
      </div>

      <!-- Resultado error -->
      <div v-if="resultado && !resultado.success" class="resultado-container error">
        <div class="resultado-icon">
          <i class="fa fa-times-circle"></i>
        </div>
        <h2>Error</h2>
        <p>{{ resultado.message }}</p>
        <button class="btn-nueva" @click="resetear">
          <i class="fa fa-redo"></i>
          Intentar de nuevo
        </button>
      </div>

      <!-- Procesando -->
      <div v-if="procesando" class="procesando-overlay">
        <div class="spinner"></div>
        <p>Procesando QR...</p>
      </div>
    </div>

    <!-- Instrucciones -->
    <div v-if="!scanning && !resultado && !permisoDenegado" class="instrucciones">
      <h3><i class="fa fa-info-circle"></i> Instrucciones</h3>
      <ul>
        <li>Pide al cliente que muestre el código QR de su cita</li>
        <li>Apunta la cámara hacia el código QR</li>
        <li>La cita se marcará como completada automáticamente</li>
      </ul>
    </div>

    <!-- Entrada manual de token -->
    <div v-if="!scanning && !resultado" class="manual-entry">
      <h3><i class="fa fa-keyboard"></i> Entrada manual</h3>
      <p>Si no puedes escanear, ingresa el código manualmente:</p>
      <div class="input-group">
        <input 
          v-model="tokenManual" 
          type="text" 
          placeholder="Código de la cita..."
          @keyup.enter="procesarTokenManual"
        />
        <button 
          class="btn-verificar" 
          @click="procesarTokenManual"
          :disabled="!tokenManual.trim() || procesando"
        >
          <i class="fa fa-check"></i>
        </button>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onUnmounted } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { useQrScanner } from '@/composables/useQrScanner'
import { Haptics, ImpactStyle } from '@capacitor/haptics'
import Swal from 'sweetalert2'

const router = useRouter()
const authStore = useAuthStore()
const API_URL = import.meta.env.VITE_API_URL || 'https://salmon-eland-125157.hostingersite.com/backend/public/api'

// Composable de QR Scanner
const { 
  scanning, 
  error: scannerError, 
  isNative,
  checkPermissions,
  requestPermissions,
  startScanner, 
  stopScanner: stopQrScanner, 
  extractToken 
} = useQrScanner()

// State
const procesando = ref(false)
const resultado = ref<any>(null)
const tokenManual = ref('')
const permisoDenegado = ref(false)
const iniciandoEscaner = ref(false)
const qrReaderRef = ref<HTMLElement | null>(null)

// Métodos
const goBack = () => {
  router.back()
}

const solicitarPermisos = async () => {
  const granted = await requestPermissions()
  if (granted) {
    permisoDenegado.value = false
  } else {
    Swal.fire({
      icon: 'warning',
      title: 'Permiso requerido',
      text: 'Debes habilitar el permiso de cámara en la configuración de tu dispositivo.',
      confirmButtonColor: '#667eea'
    })
  }
}

const iniciarEscaner = async () => {
  iniciandoEscaner.value = true
  resultado.value = null
  
  // Timeout de seguridad para evitar estado de carga infinito
  const safetyTimeout = setTimeout(() => {
    if (iniciandoEscaner.value) {
      console.warn('⚠️ Safety timeout: La cámara tardó más de 8 segundos')
      iniciandoEscaner.value = false
      
      if (!scanning.value) {
        Swal.fire({
          icon: 'error',
          title: 'Tiempo de espera agotado',
          html: 'La cámara tardó demasiado en responder.<br><br><strong>Intenta:</strong><br>• Cerrar otras apps que usen la cámara<br>• Reiniciar la aplicación<br>• Reiniciar tu dispositivo',
          confirmButtonColor: '#667eea'
        })
      }
    }
  }, 8000)
  
  // Esperar a que el DOM se actualice completamente
  await new Promise(resolve => setTimeout(resolve, 300))
  
  try {
    let success = await startScanner(
      'qr-reader',
      onScanSuccess,
      onScanError
    )
    
    // Si el escáner nativo falló en dispositivo móvil, intentar con web scanner
    if (!success && isNative && scannerError.value) {
      const errorMsg = scannerError.value
      // Solo usar fallback si NO es error de permisos
      if (!errorMsg.includes('Permiso') && !errorMsg.includes('Permission') && !errorMsg.includes('denegado')) {
        console.log('🔄 Intentando con escáner web como alternativa...')
        
        // Mostrar notificación al usuario
        const Toast = Swal.mixin({
          toast: true,
          position: 'top',
          showConfirmButton: false,
          timer: 3000,
          timerProgressBar: true
        })
        
        Toast.fire({
          icon: 'info',
          title: 'Usando escáner alternativo...'
        })
        
        // Limpiar error y reintentar con web scanner
        scannerError.value = null
        
        // Dar tiempo para que el mensaje se muestre
        await new Promise(resolve => setTimeout(resolve, 500))
        
        // Intentar de nuevo - el composable debería usar web scanner como fallback
        success = await startScanner(
          'qr-reader',
          onScanSuccess,
          onScanError
        )
      }
    }
    
    // Limpiar timeout de seguridad
    clearTimeout(safetyTimeout)
    
    if (!success) {
      if (scannerError.value?.includes('Permiso') || 
          scannerError.value?.includes('denegado') ||
          scannerError.value?.includes('Permission')) {
        permisoDenegado.value = true
      } else {
        // Mostrar error con formato mejorado
        const errorText = scannerError.value || 'Error al iniciar el escáner de QR'
        const errorLines = errorText.split('\n')
        
        Swal.fire({
          icon: 'error',
          title: 'Error de cámara',
          html: errorLines.join('<br>'),
          confirmButtonColor: '#667eea',
          footer: isNative 
            ? '<small>Si el problema persiste, ve a Configuración > Apps > [Esta app] > Permisos y verifica que la cámara esté habilitada</small>'
            : undefined
        })
      }
    }
  } catch (e: any) {
    console.error('Error inesperado:', e)
    clearTimeout(safetyTimeout)
    
    Swal.fire({
      icon: 'error',
      title: 'Error de cámara',
      text: 'Ocurrió un error inesperado al iniciar la cámara',
      confirmButtonColor: '#667eea'
    })
  } finally {
    // Asegurar que el estado se resetee
    iniciandoEscaner.value = false
  }
}

const detenerEscaner = async () => {
  await stopQrScanner()
}

const onScanSuccess = async (decodedText: string) => {
  // Vibrar para feedback
  try {
    if (isNative) {
      await Haptics.impact({ style: ImpactStyle.Medium })
    } else if (navigator.vibrate) {
      navigator.vibrate([100, 50, 100])
    }
  } catch (e) {
    // Ignorar error de vibración
  }
  
  // Extraer el token del URL escaneado
  const token = extractToken(decodedText)
  
  if (token) {
    await procesarToken(token)
  } else {
    resultado.value = {
      success: false,
      message: 'Código QR no válido. Asegúrate de escanear el QR de una cita.'
    }
  }
}

const onScanError = (errorMessage: string) => {
  console.warn('Scan error:', errorMessage)
}

const procesarToken = async (token: string) => {
  procesando.value = true
  
  try {
    // Verificar que el usuario esté autenticado
    if (!authStore.isAuthenticated || !authStore.token) {
      resultado.value = {
        success: false,
        message: 'No estás autenticado. Por favor inicia sesión nuevamente.'
      }
      return
    }

    // Verificar tipo de usuario
    if (!authStore.userType || (authStore.userType !== 'admin' && authStore.userType !== 'empleado')) {
      resultado.value = {
        success: false,
        message: 'Solo empleados y administradores pueden escanear códigos QR. Tu tipo de usuario: ' + (authStore.userType || 'no definido')
      }
      return
    }

    // Determinar endpoint según tipo de usuario
    const endpoint = authStore.userType === 'admin' 
      ? `/admin/citas/scan-qr/${token}`
      : `/empleado/citas/scan-qr/${token}`
    
    console.log('🔍 Escaneando QR:', { token, endpoint, userType: authStore.userType })
    
    const response = await fetch(`${API_URL}${endpoint}`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'Authorization': `Bearer ${authStore.token}`
      }
    })
    
    const data = await response.json()
    console.log('📥 Respuesta del servidor:', { status: response.status, data })
    
    // Manejar diferentes códigos de estado HTTP
    if (!response.ok) {
      // Error del servidor (403, 404, 422, etc.)
      let mensajeError = data.message || `Error ${response.status}: ${response.statusText}`
      
      // Si es error de permisos, mostrar mensaje más claro
      if (response.status === 403) {
        if (data.message?.includes('permisos') || data.message?.includes('No tienes permisos')) {
          mensajeError = 'No tienes permisos para escanear QR. Solo empleados y administradores pueden usar esta función. Verifica que tu cuenta esté correctamente configurada.'
        } else if (data.message?.includes('asignada')) {
          mensajeError = 'Esta cita no está asignada a ti. Solo puedes escanear QR de tus propias citas.'
        }
      } else if (response.status === 401) {
        mensajeError = 'Sesión expirada. Por favor inicia sesión nuevamente.'
      } else if (response.status === 404) {
        mensajeError = 'Código QR no válido o cita no encontrada. Verifica que el código sea correcto.'
      }
      
      resultado.value = {
        success: false,
        message: mensajeError
      }
      
      return
    }
    
    resultado.value = data
    
    if (data.success) {
      // Vibración de éxito
      try {
        if (isNative) {
          await Haptics.impact({ style: ImpactStyle.Heavy })
        }
      } catch (e) {}
      
      // Mostrar mensaje de éxito
      Swal.fire({
        icon: 'success',
        title: '¡Cita completada!',
        html: `
          <p>La cita se ha marcado como <strong>completada</strong> exitosamente.</p>
          <p class="mt-2"><small>Esta cita ahora aparecerá en el dashboard y contará en las estadísticas.</small></p>
        `,
        confirmButtonColor: '#667eea',
        timer: 4000,
        timerProgressBar: true
      })
    } else {
      // Si success es false pero no hay error HTTP
      resultado.value = {
        success: false,
        message: data.message || 'Error al procesar el código QR'
      }
    }
  } catch (error: any) {
    console.error('❌ Error procesando QR:', error)
    resultado.value = {
      success: false,
      message: 'Error de conexión. Verifica tu internet e intenta de nuevo.'
    }
  } finally {
    procesando.value = false
  }
}

const procesarTokenManual = async () => {
  const token = tokenManual.value.trim()
  if (!token) return
  
  await procesarToken(token)
}

const resetear = () => {
  resultado.value = null
  tokenManual.value = ''
}

// Lifecycle
onUnmounted(async () => {
  await stopQrScanner()
})
</script>

<style scoped>
.scan-qr-view {
  min-height: 100vh;
  background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%);
  padding-bottom: 2rem;
}

/* Header */
.scan-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 1rem 1.25rem;
  background: rgba(255, 255, 255, 0.05);
  backdrop-filter: blur(10px);
  position: sticky;
  top: 0;
  z-index: 100;
}

.btn-back {
  width: 40px;
  height: 40px;
  border-radius: 12px;
  border: none;
  background: rgba(255, 255, 255, 0.1);
  color: white;
  font-size: 1rem;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: background 0.2s;
}

.btn-back:hover {
  background: rgba(255, 255, 255, 0.2);
}

.scan-header h1 {
  margin: 0;
  font-size: 1.25rem;
  color: white;
  font-weight: 600;
}

.header-spacer {
  width: 40px;
}

/* Scanner Container */
.scanner-container {
  margin: 1.5rem;
  background: white;
  border-radius: 24px;
  overflow: hidden;
  position: relative;
  min-height: 350px;
}

/* Permiso denegado */
.permission-denied {
  padding: 3rem 2rem;
  text-align: center;
}

.denied-icon {
  width: 100px;
  height: 100px;
  background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
  border-radius: 24px;
  display: flex;
  align-items: center;
  justify-content: center;
  margin: 0 auto 1.5rem;
  font-size: 2.5rem;
  color: white;
}

.permission-denied h2 {
  margin: 0 0 0.5rem;
  color: #1a1a2e;
  font-size: 1.25rem;
}

.permission-denied p {
  color: #666;
  font-size: 0.9rem;
  margin: 0 0 1.5rem;
  line-height: 1.5;
}

.btn-permission {
  padding: 1rem 2rem;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  border: none;
  border-radius: 14px;
  font-size: 1rem;
  font-weight: 600;
  cursor: pointer;
  display: inline-flex;
  align-items: center;
  gap: 0.75rem;
  margin-bottom: 1rem;
}

.permission-hint {
  font-size: 0.8rem;
  color: #888;
  margin: 0;
  padding: 0 1rem;
}

.scanner-placeholder {
  padding: 3rem 2rem;
  text-align: center;
}

.placeholder-icon {
  width: 100px;
  height: 100px;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  border-radius: 24px;
  display: flex;
  align-items: center;
  justify-content: center;
  margin: 0 auto 1.5rem;
  font-size: 2.5rem;
  color: white;
}

.scanner-placeholder h2 {
  margin: 0 0 0.5rem;
  color: #1a1a2e;
  font-size: 1.25rem;
}

.scanner-placeholder p {
  color: #666;
  font-size: 0.9rem;
  margin: 0 0 1.5rem;
  line-height: 1.5;
}

.btn-scan {
  padding: 1rem 2rem;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  border: none;
  border-radius: 14px;
  font-size: 1rem;
  font-weight: 600;
  cursor: pointer;
  display: inline-flex;
  align-items: center;
  gap: 0.75rem;
  transition: transform 0.2s, box-shadow 0.2s;
}

.btn-scan:hover:not(:disabled) {
  transform: translateY(-2px);
  box-shadow: 0 8px 20px rgba(102, 126, 234, 0.4);
}

.btn-scan:disabled {
  opacity: 0.7;
  cursor: not-allowed;
}

.scan-native-hint {
  margin-top: 1rem;
  color: #888;
  font-size: 0.85rem;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.5rem;
}

.scan-native-hint i {
  color: #667eea;
}

/* Scanner loading (nativo) */
.scanner-loading {
  padding: 3rem 2rem;
  text-align: center;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  min-height: 200px;
}

.scanner-loading p {
  color: #666;
  margin-top: 1rem;
  font-size: 0.95rem;
}

/* Scanner activo */
.scanner-active {
  position: relative;
  background: #000;
  min-height: 350px; /* Asegurar altura mínima incluso cuando está oculto */
}

.qr-reader-container,
#qr-reader {
  width: 100%;
  min-height: 350px;
  position: relative;
  z-index: 1;
  background: #000;
  overflow: hidden;
  display: block !important;
}

#qr-reader video {
  width: 100% !important;
  height: 100% !important;
  min-height: 350px;
  max-height: 100vh;
  object-fit: cover;
  display: block !important;
  visibility: visible !important;
  opacity: 1 !important;
  position: absolute;
  top: 0;
  left: 0;
  z-index: 1;
  background: #000;
  transform: scaleX(1);
}

/* Forzar visibilidad del video */
#qr-reader video[autoplay] {
  display: block !important;
  visibility: visible !important;
  opacity: 1 !important;
}

#qr-reader canvas {
  display: none !important;
  visibility: hidden !important;
  position: absolute;
  z-index: -1;
}

#qr-reader img {
  display: none !important;
}

/* Asegurar que cualquier elemento hijo del contenedor sea visible */
#qr-reader > *:not(canvas):not(img) {
  display: block !important;
  visibility: visible !important;
}

.scanner-overlay {
  position: absolute;
  inset: 0;
  display: flex;
  align-items: center;
  justify-content: center;
  pointer-events: none;
  z-index: 2;
}

.scanner-frame {
  width: 250px;
  height: 250px;
  position: relative;
  box-shadow: 0 0 0 9999px rgba(0, 0, 0, 0.6);
}

.corner {
  position: absolute;
  width: 30px;
  height: 30px;
  border-color: #667eea;
  border-style: solid;
  border-width: 0;
}

.corner.top-left {
  top: 0;
  left: 0;
  border-top-width: 4px;
  border-left-width: 4px;
  border-top-left-radius: 8px;
}

.corner.top-right {
  top: 0;
  right: 0;
  border-top-width: 4px;
  border-right-width: 4px;
  border-top-right-radius: 8px;
}

.corner.bottom-left {
  bottom: 0;
  left: 0;
  border-bottom-width: 4px;
  border-left-width: 4px;
  border-bottom-left-radius: 8px;
}

.corner.bottom-right {
  bottom: 0;
  right: 0;
  border-bottom-width: 4px;
  border-right-width: 4px;
  border-bottom-right-radius: 8px;
}

.scan-line {
  position: absolute;
  left: 10px;
  right: 10px;
  height: 2px;
  background: linear-gradient(90deg, transparent, #667eea, transparent);
  animation: scan 2s ease-in-out infinite;
}

@keyframes scan {
  0%, 100% { top: 10px; }
  50% { top: calc(100% - 10px); }
}

.scan-hint {
  position: absolute;
  bottom: 80px;
  left: 50%;
  transform: translateX(-50%);
  color: white;
  font-size: 0.9rem;
  background: rgba(0, 0, 0, 0.7);
  padding: 0.5rem 1rem;
  border-radius: 8px;
  white-space: nowrap;
}

.btn-cancel {
  position: absolute;
  bottom: 1.5rem;
  left: 50%;
  transform: translateX(-50%);
  padding: 0.75rem 1.5rem;
  background: rgba(255, 255, 255, 0.95);
  color: #ef4444;
  border: none;
  border-radius: 12px;
  font-size: 0.9rem;
  font-weight: 600;
  cursor: pointer;
  display: flex;
  align-items: center;
  gap: 0.5rem;
  backdrop-filter: blur(10px);
}

/* Resultado */
.resultado-container {
  padding: 3rem 2rem;
  text-align: center;
}

.resultado-icon {
  width: 80px;
  height: 80px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  margin: 0 auto 1.5rem;
  font-size: 2.5rem;
}

.resultado-container.success .resultado-icon {
  background: linear-gradient(135deg, #10b981 0%, #059669 100%);
  color: white;
}

.resultado-container.error .resultado-icon {
  background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
  color: white;
}

.resultado-container h2 {
  margin: 0 0 1rem;
  color: #1a1a2e;
  font-size: 1.5rem;
}

.resultado-container.success h2 {
  color: #059669;
}

.resultado-container.error h2 {
  color: #dc2626;
}

.resultado-container p {
  color: #666;
  margin: 0 0 1.5rem;
}

.cita-details {
  background: #f8f9fa;
  border-radius: 16px;
  padding: 1.25rem;
  margin-bottom: 1.5rem;
  text-align: left;
}

.detail-item {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  padding: 0.5rem 0;
  color: #333;
}

.detail-item:not(:last-child) {
  border-bottom: 1px solid #e5e7eb;
}

.detail-item i {
  width: 20px;
  color: #667eea;
}

.btn-nueva {
  padding: 1rem 2rem;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  border: none;
  border-radius: 14px;
  font-size: 1rem;
  font-weight: 600;
  cursor: pointer;
  display: inline-flex;
  align-items: center;
  gap: 0.75rem;
}

/* Procesando */
.procesando-overlay {
  position: absolute;
  inset: 0;
  background: rgba(255, 255, 255, 0.95);
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  z-index: 10;
}

.spinner {
  width: 50px;
  height: 50px;
  border: 4px solid #f0f0f0;
  border-top-color: #667eea;
  border-radius: 50%;
  animation: spin 0.8s linear infinite;
  margin-bottom: 1rem;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}

.procesando-overlay p {
  color: #666;
  font-weight: 500;
}

/* Instrucciones */
.instrucciones {
  margin: 0 1.5rem;
  background: rgba(255, 255, 255, 0.05);
  border-radius: 16px;
  padding: 1.25rem;
}

.instrucciones h3 {
  color: white;
  font-size: 1rem;
  margin: 0 0 1rem;
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.instrucciones h3 i {
  color: #667eea;
}

.instrucciones ul {
  margin: 0;
  padding-left: 1.25rem;
  color: rgba(255, 255, 255, 0.7);
  font-size: 0.9rem;
}

.instrucciones li {
  margin-bottom: 0.5rem;
  line-height: 1.5;
}

/* Entrada manual */
.manual-entry {
  margin: 1.5rem;
  background: rgba(255, 255, 255, 0.05);
  border-radius: 16px;
  padding: 1.25rem;
}

.manual-entry h3 {
  color: white;
  font-size: 1rem;
  margin: 0 0 0.5rem;
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.manual-entry h3 i {
  color: #667eea;
}

.manual-entry p {
  color: rgba(255, 255, 255, 0.6);
  font-size: 0.85rem;
  margin: 0 0 1rem;
}

.input-group {
  display: flex;
  gap: 0.5rem;
}

.input-group input {
  flex: 1;
  padding: 0.875rem 1rem;
  border: 2px solid rgba(255, 255, 255, 0.1);
  border-radius: 12px;
  background: rgba(255, 255, 255, 0.05);
  color: white;
  font-size: 0.95rem;
}

.input-group input::placeholder {
  color: rgba(255, 255, 255, 0.4);
}

.input-group input:focus {
  outline: none;
  border-color: #667eea;
  background: rgba(255, 255, 255, 0.1);
}

.btn-verificar {
  width: 50px;
  height: 50px;
  border-radius: 12px;
  border: none;
  background: linear-gradient(135deg, #10b981 0%, #059669 100%);
  color: white;
  font-size: 1rem;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
}

.btn-verificar:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}
</style>
