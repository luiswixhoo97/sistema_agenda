<template>
  <div class="login-cliente">
    <div class="login-container">
      <!-- Header con logo -->
      <div class="login-header">
        <div class="logo-container">
          <div class="logo-icon">💅</div>
          <h1 class="app-name">BeautySpa</h1>
        </div>
        <p class="subtitle">Tu espacio de belleza y bienestar</p>
      </div>

      <!-- Aviso de sesión activa -->
      <div v-if="tieneSesionPrevia" class="sesion-previa">
        <p>Ya tienes una sesión activa como <strong>{{ nombreUsuarioActual }}</strong></p>
        <div class="sesion-acciones">
          <button class="btn-secondary" @click="irAInicio">
            Continuar →
          </button>
          <button class="btn-text" @click="limpiarSesion">
            Usar otra cuenta
          </button>
        </div>
      </div>

      <!-- Paso 1: Ingresar teléfono -->
      <div v-if="paso === 'telefono' && !tieneSesionPrevia" class="form-section">
        <h2 class="form-title">Iniciar Sesión</h2>
        <p class="form-description">
          Ingresa tu número de teléfono para continuar
        </p>

        <form @submit.prevent="solicitarOtp">
          <div class="input-group">
            <label for="telefono">Número de teléfono</label>
            <div class="phone-input">
              <span class="country-code">+52</span>
              <input
                id="telefono"
                v-model="telefono"
                type="tel"
                placeholder="10 dígitos"
                maxlength="10"
                pattern="[0-9]*"
                inputmode="numeric"
                :disabled="loading"
                required
              />
            </div>
          </div>

          <div v-if="error" class="error-message">
            {{ error }}
          </div>

          <button type="submit" class="btn-primary" :disabled="loading || telefono.length < 10">
            <span v-if="loading" class="spinner"></span>
            {{ loading ? 'Enviando...' : 'Continuar' }}
          </button>
        </form>

        <div class="divider">
          <span>¿Primera vez?</span>
        </div>

        <button class="btn-secondary" @click="irARegistro">
          Crear cuenta
        </button>
      </div>

      <!-- Paso 2: Verificar OTP -->
      <div v-else-if="paso === 'otp' && !tieneSesionPrevia" class="form-section">
        <button class="back-button" @click="paso = 'telefono'">
          ← Cambiar número
        </button>

        <h2 class="form-title">Verificación</h2>
        <p class="form-description">
          Ingresa el código de 6 dígitos enviado a<br />
          <strong>+52 {{ telefonoFormateado }}</strong>
        </p>

        <!-- Código de debug (solo desarrollo) -->
        <div v-if="codigoDebug" class="debug-code">
          <span>🔐 Código (dev):</span>
          <strong>{{ codigoDebug }}</strong>
        </div>

        <form @submit.prevent="verificarOtp">
          <div class="otp-inputs">
            <input
              v-for="(digit, index) in 6"
              :key="index"
              :ref="(el) => (otpRefs[index] = el as HTMLInputElement | null)"
              v-model="otpDigits[index]"
              type="text"
              maxlength="1"
              inputmode="numeric"
              pattern="[0-9]*"
              class="otp-digit"
              :disabled="loading"
              @input="onOtpInput(index)"
              @keydown="onOtpKeydown($event, index)"
              @paste="onOtpPaste"
            />
          </div>

          <div v-if="error" class="error-message">
            {{ error }}
          </div>

          <button
            type="submit"
            class="btn-primary"
            :disabled="loading || otpCompleto.length < 6"
          >
            <span v-if="loading" class="spinner"></span>
            {{ loading ? 'Verificando...' : 'Verificar' }}
          </button>
        </form>

        <div class="resend-section">
          <p v-if="tiempoRestante > 0">
            Reenviar código en {{ tiempoRestante }}s
          </p>
          <button
            v-else
            class="btn-link"
            @click="reenviarOtp"
            :disabled="loading"
          >
            Reenviar código
          </button>
        </div>
      </div>

      <!-- Paso 3: Registro (nuevo usuario) -->
      <div v-else-if="paso === 'registro' && !tieneSesionPrevia" class="form-section">
        <button class="back-button" @click="paso = 'telefono'">
          ← Volver
        </button>

        <h2 class="form-title">Crear cuenta</h2>
        <p class="form-description">
          Completa tus datos para poder agendar
        </p>

        <form @submit.prevent="registrar">
          <div class="form-row">
            <div class="input-group">
              <label for="nombre">Nombre <span class="required">*</span></label>
              <input
                id="nombre"
                v-model="registroData.nombre"
                type="text"
                placeholder="Tu nombre"
                :disabled="loading"
                required
              />
            </div>

            <div class="input-group">
              <label for="apellido">Apellido <span class="required">*</span></label>
              <input
                id="apellido"
                v-model="registroData.apellido"
                type="text"
                placeholder="Tu apellido"
                :disabled="loading"
                required
              />
            </div>
          </div>

          <div class="input-group">
            <label for="reg-telefono">Teléfono <span class="required">*</span></label>
            <div class="phone-input">
              <span class="country-code">+52</span>
              <input
                id="reg-telefono"
                v-model="telefono"
                type="tel"
                placeholder="10 dígitos"
                maxlength="10"
                :disabled="loading"
                required
              />
            </div>
          </div>

          <div class="input-group">
            <label for="email">Email <span class="optional">(opcional)</span></label>
            <input
              id="email"
              v-model="registroData.email"
              type="email"
              placeholder="tu@email.com"
              :disabled="loading"
            />
          </div>

          <div v-if="error" class="error-message">
            {{ error }}
          </div>

          <button type="submit" class="btn-primary" :disabled="loading || !registroValido">
            <span v-if="loading" class="spinner"></span>
            {{ loading ? 'Registrando...' : 'Continuar' }}
          </button>
        </form>

        <p class="terms-text">
          Al continuar aceptas nuestros términos y condiciones
        </p>
      </div>
    </div>

    <!-- Footer -->
    <div class="login-footer">
      <p>¿Eres empleado o administrador?</p>
      <router-link to="/login" class="staff-link">
        Acceso personal →
      </router-link>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onUnmounted } from 'vue';
import { useRouter } from 'vue-router';
import { useAuthStore } from '@/stores/auth';
import { authService } from '@/services/authService';

const router = useRouter();
const authStore = useAuthStore();

// Estados
const paso = ref<'telefono' | 'otp' | 'registro'>('telefono');
const telefono = ref('');
const otpDigits = ref(['', '', '', '', '', '']);
const otpRefs = ref<(HTMLInputElement | null)[]>([]);
const loading = ref(false);
const error = ref('');
const tiempoRestante = ref(0);
let timerInterval: number | null = null;

const registroData = ref({
  nombre: '',
  apellido: '',
  email: '',
});

// Validación del registro
const registroValido = computed(() => {
  return registroData.value.nombre.trim().length >= 2 && 
         registroData.value.apellido.trim().length >= 2 &&
         telefono.value.length === 10;
});

// Computed
const telefonoFormateado = computed(() => {
  const t = telefono.value;
  if (t.length >= 10) {
    return `${t.slice(0, 3)} ${t.slice(3, 6)} ${t.slice(6)}`;
  }
  return t;
});

const otpCompleto = computed(() => otpDigits.value.join(''));

const tieneSesionPrevia = computed(() => authStore.isAuthenticated);

const nombreUsuarioActual = computed(() => authStore.user?.nombre || 'Usuario');

// Métodos
const codigoDebug = ref('');

async function solicitarOtp() {
  error.value = '';
  loading.value = true;

  try {
    const response = await authService.solicitarOtpCliente(telefono.value);
    paso.value = 'otp';
    iniciarTemporizador();
    
    // Mostrar código de debug en desarrollo
    if (response.codigo_debug) {
      codigoDebug.value = response.codigo_debug;
      console.log('🔐 Código OTP (desarrollo):', response.codigo_debug);
    }
  } catch (e: any) {
    error.value = e.response?.data?.message || 'Error al enviar código';
  } finally {
    loading.value = false;
  }
}

async function verificarOtp() {
  error.value = '';
  loading.value = true;

  try {
    const response = await authService.verificarOtpCliente(
      telefono.value,
      otpCompleto.value
    );
    
    console.log('✅ Respuesta OTP:', response);
    
    // Si requiere registro, ir al paso de registro
    if (response.requiere_registro) {
      paso.value = 'registro';
      return;
    }
    
    // Login exitoso - usar cliente, no user
    if (response.token && response.cliente) {
      authStore.setAuth(response.cliente, response.token);
      router.push('/agendar');
    } else {
      error.value = 'Error en la respuesta del servidor';
    }
  } catch (e: any) {
    console.error('❌ Error verificando OTP:', e.response?.data);
    error.value = e.response?.data?.message || 'Código incorrecto';
    otpDigits.value = ['', '', '', '', '', ''];
    otpRefs.value[0]?.focus();
  } finally {
    loading.value = false;
  }
}

async function registrar() {
  error.value = '';
  
  // Validar campos obligatorios
  if (!registroData.value.nombre.trim()) {
    error.value = 'El nombre es obligatorio';
    return;
  }
  if (!registroData.value.apellido.trim()) {
    error.value = 'El apellido es obligatorio';
    return;
  }
  if (telefono.value.length !== 10) {
    error.value = 'El teléfono debe tener 10 dígitos';
    return;
  }

  loading.value = true;

  try {
    // Combinar nombre y apellido
    const nombreCompleto = `${registroData.value.nombre.trim()} ${registroData.value.apellido.trim()}`;
    
    await authService.registrarCliente({
      nombre: nombreCompleto,
      email: registroData.value.email?.trim() || undefined,
      telefono: telefono.value,
    });
    
    // Después del registro, solicitar OTP para verificar
    await solicitarOtp();
  } catch (e: any) {
    error.value = e.response?.data?.message || 'Error al registrar';
  } finally {
    loading.value = false;
  }
}

function reenviarOtp() {
  solicitarOtp();
}

function irARegistro() {
  paso.value = 'registro';
}

function limpiarSesion() {
  localStorage.clear();
  authStore.logout();
  window.location.reload();
}

function irAInicio() {
  const userType = authStore.userType;
  if (userType === 'cliente') {
    router.push('/agendar');
  } else if (userType === 'empleado') {
    router.push('/empleado/calendario');
  } else if (userType === 'admin') {
    router.push('/admin');
  } else {
    router.push('/');
  }
}

function iniciarTemporizador() {
  tiempoRestante.value = 60;
  timerInterval = setInterval(() => {
    tiempoRestante.value--;
    if (tiempoRestante.value <= 0 && timerInterval) {
      clearInterval(timerInterval);
    }
  }, 1000) as unknown as number;
}

// Manejo de inputs OTP
function onOtpInput(index: number) {
  const value = otpDigits.value[index];
  if (value && index < 5) {
    otpRefs.value[index + 1]?.focus();
  }
}

function onOtpKeydown(event: KeyboardEvent, index: number) {
  if (event.key === 'Backspace' && !otpDigits.value[index] && index > 0) {
    otpRefs.value[index - 1]?.focus();
  }
}

function onOtpPaste(event: ClipboardEvent) {
  event.preventDefault();
  const pastedData = event.clipboardData?.getData('text') || '';
  const digits = pastedData.replace(/\D/g, '').slice(0, 6).split('');
  
  digits.forEach((digit, index) => {
    if (index < 6) {
      otpDigits.value[index] = digit;
    }
  });

  const focusIndex = Math.min(digits.length, 5);
  otpRefs.value[focusIndex]?.focus();
}

onUnmounted(() => {
  if (timerInterval) clearInterval(timerInterval);
});
</script>

<style scoped>
.login-cliente {
  min-height: 100vh;
  display: flex;
  flex-direction: column;
  background: linear-gradient(135deg, #fce4ec 0%, #f8bbd9 50%, #f48fb1 100%);
  padding: 20px;
}

.sesion-previa {
  background: white;
  border-radius: 16px;
  padding: 24px;
  text-align: center;
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
  margin-bottom: 20px;
}

.sesion-previa p {
  margin-bottom: 16px;
  color: #333;
}

.sesion-acciones {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.btn-text {
  background: none;
  border: none;
  color: #e91e63;
  cursor: pointer;
  font-size: 0.9rem;
  padding: 8px;
}

.btn-text:hover {
  text-decoration: underline;
}

.login-container {
  flex: 1;
  display: flex;
  flex-direction: column;
  max-width: 400px;
  margin: 0 auto;
  width: 100%;
}

.login-header {
  text-align: center;
  padding: 40px 0 30px;
}

.logo-container {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 12px;
  margin-bottom: 8px;
}

.logo-icon {
  font-size: 48px;
}

.app-name {
  font-family: 'Playfair Display', serif;
  font-size: 36px;
  color: #880e4f;
  margin: 0;
  font-weight: 700;
}

.subtitle {
  color: #ad1457;
  font-size: 14px;
  margin: 0;
}

.form-section {
  background: white;
  border-radius: 24px;
  padding: 32px 24px;
  box-shadow: 0 10px 40px rgba(136, 14, 79, 0.15);
}

.back-button {
  background: none;
  border: none;
  color: #ad1457;
  font-size: 14px;
  cursor: pointer;
  padding: 0;
  margin-bottom: 16px;
}

.form-title {
  font-size: 24px;
  font-weight: 600;
  color: #333;
  margin: 0 0 8px;
}

.form-description {
  color: #666;
  font-size: 14px;
  margin: 0 0 24px;
  line-height: 1.5;
}

.form-row {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 12px;
}

.form-row .input-group {
  margin-bottom: 16px;
}

.input-group {
  margin-bottom: 20px;
}

.input-group label {
  display: block;
  font-size: 14px;
  font-weight: 500;
  color: #333;
  margin-bottom: 8px;
}

.input-group label .required {
  color: #e94560;
  font-weight: 600;
}

.input-group label .optional {
  color: #888;
  font-weight: 400;
  font-size: 12px;
}

.terms-text {
  text-align: center;
  font-size: 12px;
  color: #888;
  margin-top: 16px;
}

.input-group input {
  width: 100%;
  padding: 14px 16px;
  border: 2px solid #e0e0e0;
  border-radius: 12px;
  font-size: 16px;
  transition: border-color 0.2s;
  box-sizing: border-box;
}

.input-group input:focus {
  outline: none;
  border-color: #ec407a;
}

.phone-input {
  display: flex;
  align-items: center;
  border: 2px solid #e0e0e0;
  border-radius: 12px;
  overflow: hidden;
  transition: border-color 0.2s;
}

.phone-input:focus-within {
  border-color: #ec407a;
}

.country-code {
  background: #f5f5f5;
  padding: 14px 12px;
  color: #666;
  font-weight: 500;
  border-right: 1px solid #e0e0e0;
}

.phone-input input {
  border: none;
  flex: 1;
  padding: 14px 16px;
  font-size: 16px;
}

.phone-input input:focus {
  outline: none;
}

.otp-inputs {
  display: flex;
  gap: 10px;
  justify-content: center;
  margin-bottom: 24px;
}

.otp-digit {
  width: 48px;
  height: 56px;
  text-align: center;
  font-size: 24px;
  font-weight: 600;
  border: 2px solid #e0e0e0;
  border-radius: 12px;
  transition: border-color 0.2s;
}

.otp-digit:focus {
  outline: none;
  border-color: #ec407a;
}

.btn-primary {
  width: 100%;
  padding: 16px;
  background: linear-gradient(135deg, #ec407a 0%, #d81b60 100%);
  color: white;
  border: none;
  border-radius: 12px;
  font-size: 16px;
  font-weight: 600;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  transition: transform 0.2s, box-shadow 0.2s;
}

.btn-primary:hover:not(:disabled) {
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(216, 27, 96, 0.4);
}

.btn-primary:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.btn-secondary {
  width: 100%;
  padding: 16px;
  background: transparent;
  color: #d81b60;
  border: 2px solid #d81b60;
  border-radius: 12px;
  font-size: 16px;
  font-weight: 600;
  cursor: pointer;
  transition: background 0.2s;
}

.btn-secondary:hover {
  background: #fce4ec;
}

.btn-link {
  background: none;
  border: none;
  color: #d81b60;
  font-size: 14px;
  cursor: pointer;
  text-decoration: underline;
}

.divider {
  display: flex;
  align-items: center;
  margin: 24px 0;
}

.divider::before,
.divider::after {
  content: '';
  flex: 1;
  height: 1px;
  background: #e0e0e0;
}

.divider span {
  padding: 0 16px;
  color: #999;
  font-size: 14px;
}

.error-message {
  background: #ffebee;
  color: #c62828;
  padding: 12px;
  border-radius: 8px;
  font-size: 14px;
  margin-bottom: 16px;
}

.debug-code {
  background: #e3f2fd;
  border: 2px dashed #1976d2;
  padding: 12px;
  border-radius: 8px;
  text-align: center;
  margin-bottom: 16px;
  font-size: 14px;
}

.debug-code strong {
  display: block;
  font-size: 24px;
  color: #1976d2;
  letter-spacing: 4px;
  margin-top: 4px;
}

.resend-section {
  text-align: center;
  margin-top: 20px;
}

.resend-section p {
  color: #999;
  font-size: 14px;
  margin: 0;
}

.spinner {
  width: 20px;
  height: 20px;
  border: 2px solid transparent;
  border-top-color: white;
  border-radius: 50%;
  animation: spin 0.8s linear infinite;
}

@keyframes spin {
  to {
    transform: rotate(360deg);
  }
}

.login-footer {
  text-align: center;
  padding: 24px 0;
}

.login-footer p {
  color: #880e4f;
  font-size: 14px;
  margin: 0 0 8px;
}

.staff-link {
  color: #880e4f;
  font-weight: 600;
  text-decoration: none;
}

.staff-link:hover {
  text-decoration: underline;
}
</style>

