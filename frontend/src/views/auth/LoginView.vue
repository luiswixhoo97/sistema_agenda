<template>
  <div class="login-view">
    <!-- Fondo con gradiente animado -->
    <div class="bg-gradient"></div>
    
    <div class="login-container">
      <!-- Card principal -->
      <div class="login-card">
        <!-- Header -->
        <div class="login-header">
          <div class="logo-wrapper">
            <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="logo-icon">
              <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
              <circle cx="12" cy="7" r="4"></circle>
            </svg>
          </div>
          <h1>BeautySpa</h1>
          <p>Inicia sesión para continuar</p>
        </div>

        <!-- Formulario -->
        <form @submit.prevent="handleLogin" class="login-form">
          <!-- Email -->
          <div class="input-wrapper">
            <div class="input-icon">
              <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path>
                <polyline points="22,6 12,13 2,6"></polyline>
              </svg>
            </div>
            <input
              id="email"
              v-model="credentials.email"
              type="email"
              placeholder="Correo electrónico"
              :disabled="loading"
              required
              autocomplete="email"
              class="input-field"
            />
          </div>

          <!-- Password -->
          <div class="input-wrapper">
            <div class="input-icon">
              <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
                <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
              </svg>
            </div>
            <input
              id="password"
              v-model="credentials.password"
              :type="showPassword ? 'text' : 'password'"
              placeholder="Contraseña"
              :disabled="loading"
              required
              autocomplete="current-password"
              class="input-field"
            />
            <button
              type="button"
              class="toggle-password"
              @click="showPassword = !showPassword"
              tabindex="-1"
            >
              <svg v-if="showPassword" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path>
                <line x1="1" y1="1" x2="23" y2="23"></line>
              </svg>
              <svg v-else xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                <circle cx="12" cy="12" r="3"></circle>
              </svg>
            </button>
          </div>

          <!-- Remember & Forgot -->
          <div class="form-options">
            <label class="remember-checkbox">
              <input type="checkbox" v-model="rememberMe" />
              <span>Recordarme</span>
            </label>
            <a href="#" class="forgot-link">¿Olvidaste tu contraseña?</a>
          </div>

          <!-- Error message -->
          <Transition name="fade">
            <div v-if="error" class="error-message">
              <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="12" cy="12" r="10"></circle>
                <line x1="12" y1="8" x2="12" y2="12"></line>
                <line x1="12" y1="16" x2="12.01" y2="16"></line>
              </svg>
              <span>{{ error }}</span>
            </div>
          </Transition>

          <!-- Submit button -->
          <button type="submit" class="btn-login" :disabled="loading">
            <span v-if="loading" class="spinner"></span>
            <span v-else>Iniciar Sesión</span>
          </button>
        </form>

        <!-- Footer -->
        <div class="login-footer">
          <p>¿Problemas para acceder?</p>
          <a href="#">Contactar soporte</a>
        </div>
      </div>

      <!-- Link para clientes -->
      <div class="client-link">
        <p>¿Eres cliente?</p>
        <router-link to="/" class="client-link-btn">
          Agendar cita
          <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <polyline points="9 18 15 12 9 6"></polyline>
          </svg>
        </router-link>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive } from 'vue';
import { useRouter } from 'vue-router';
import { useAuthStore } from '@/stores/auth';
import { authService } from '@/services/authService';

const router = useRouter();
const authStore = useAuthStore();

const credentials = reactive({
  email: '',
  password: '',
});

const showPassword = ref(false);
const rememberMe = ref(false);
const loading = ref(false);
const error = ref('');

async function handleLogin() {
  error.value = '';
  loading.value = true;

  try {
    const response = await authService.login(credentials.email, credentials.password);
    
    if (!response.success || !response.user || !response.token) {
      throw new Error('Respuesta inválida del servidor');
    }
    
    authStore.setAuth(response.user, response.token);

    // Redirigir según el rol (role viene como string desde el backend)
    const role = response.user.role;
    console.log('🔐 Login exitoso, rol:', role);
    
    if (role === 'admin') {
      router.push('/admin');
    } else if (role === 'empleado') {
      router.push('/empleado/calendario');
    } else {
      router.push('/');
    }
  } catch (e: any) {
    console.error('❌ Error login:', e);
    error.value = e.response?.data?.message || 'Credenciales incorrectas';
  } finally {
    loading.value = false;
  }
}
</script>

<style scoped>
/* ===== LAYOUT ===== */
.login-view {
  min-height: 100vh;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 20px;
  position: relative;
  overflow: hidden;
  background: #f5f5f7;
  font-family: -apple-system, BlinkMacSystemFont, 'SF Pro Display', 'SF Pro Text', 'Helvetica Neue', sans-serif;
  -webkit-font-smoothing: antialiased;
  -moz-osx-font-smoothing: grayscale;
}

.theme-dark .login-view {
  background: #000000;
}

.bg-gradient {
  position: absolute;
  inset: 0;
  background: linear-gradient(180deg, #f5f5f7 0%, #ffffff 100%);
  opacity: 1;
}

.theme-dark .bg-gradient {
  background: linear-gradient(180deg, #000000 0%, #1c1c1e 100%);
}

.login-container {
  position: relative;
  z-index: 1;
  width: 100%;
  max-width: 440px;
}

/* ===== LOGIN CARD ===== */
.login-card {
  background: rgba(255, 255, 255, 0.8);
  backdrop-filter: saturate(180%) blur(20px);
  -webkit-backdrop-filter: saturate(180%) blur(20px);
  border-radius: 20px;
  padding: 56px 48px;
  box-shadow: 
    0 4px 16px rgba(0, 0, 0, 0.04),
    0 0 0 0.5px rgba(0, 0, 0, 0.06);
  border: 0.5px solid rgba(0, 0, 0, 0.08);
}

.theme-dark .login-card {
  background: rgba(28, 28, 30, 0.8);
  border-color: rgba(255, 255, 255, 0.1);
  box-shadow: 
    0 4px 16px rgba(0, 0, 0, 0.3),
    0 0 0 0.5px rgba(255, 255, 255, 0.1);
}

/* ===== HEADER ===== */
.login-header {
  text-align: center;
  margin-bottom: 40px;
}

.logo-wrapper {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 64px;
  height: 64px;
  border-radius: 16px;
  background: #007aff;
  margin-bottom: 24px;
  box-shadow: 0 2px 8px rgba(0, 122, 255, 0.2);
}

.logo-icon {
  width: 36px;
  height: 36px;
  color: white;
}

.login-header h1 {
  font-size: 34px;
  font-weight: 600;
  color: #1d1d1f;
  margin: 0 0 8px;
  letter-spacing: -0.8px;
  font-family: -apple-system, BlinkMacSystemFont, 'SF Pro Display', sans-serif;
}

.theme-dark .login-header h1 {
  color: #f5f5f7;
}

.login-header p {
  color: #86868b;
  font-size: 17px;
  margin: 0;
  font-weight: 400;
  letter-spacing: -0.2px;
}

.theme-dark .login-header p {
  color: #a1a1a6;
}

/* ===== FORM ===== */
.login-form {
  margin-bottom: 32px;
}

.input-wrapper {
  position: relative;
  margin-bottom: 16px;
}

.input-icon {
  position: absolute;
  left: 16px;
  top: 50%;
  transform: translateY(-50%);
  color: #86868b;
  pointer-events: none;
  z-index: 1;
}

.theme-dark .input-icon {
  color: #a1a1a6;
}

.input-field {
  width: 100%;
  padding: 14px 16px 14px 48px;
  border: 1px solid #d1d1d6;
  border-radius: 12px;
  font-size: 17px;
  background: rgba(255, 255, 255, 1);
  color: #1d1d1f;
  transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
  box-sizing: border-box;
  font-family: -apple-system, BlinkMacSystemFont, 'SF Pro Text', sans-serif;
}

.theme-dark .input-field {
  background: rgba(44, 44, 46, 1);
  border-color: rgba(255, 255, 255, 0.15);
  color: #f5f5f7;
}

.input-field:focus {
  outline: none;
  border-color: #007aff;
  background: rgba(255, 255, 255, 1);
  box-shadow: 0 0 0 4px rgba(0, 122, 255, 0.1);
}

.theme-dark .input-field:focus {
  background: rgba(58, 58, 60, 1);
  box-shadow: 0 0 0 4px rgba(0, 122, 255, 0.15);
}

.input-field::placeholder {
  color: #86868b;
}

.theme-dark .input-field::placeholder {
  color: #6e6e73;
}

.input-field:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.toggle-password {
  position: absolute;
  right: 16px;
  top: 50%;
  transform: translateY(-50%);
  background: none;
  border: none;
  color: #86868b;
  cursor: pointer;
  padding: 4px;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: color 0.2s;
}

.theme-dark .toggle-password {
  color: #a1a1a6;
}

.toggle-password:hover {
  color: #007aff;
}

/* ===== FORM OPTIONS ===== */
.form-options {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 28px;
  font-size: 15px;
}

.remember-checkbox {
  display: flex;
  align-items: center;
  gap: 8px;
  cursor: pointer;
  color: #86868b;
  user-select: none;
}

.theme-dark .remember-checkbox {
  color: #a1a1a6;
}

.remember-checkbox input[type="checkbox"] {
  width: 18px;
  height: 18px;
  accent-color: #007aff;
  cursor: pointer;
}

.forgot-link {
  color: #007aff;
  text-decoration: none;
  font-weight: 500;
  transition: opacity 0.2s;
}

.forgot-link:hover {
  opacity: 0.7;
}

/* ===== ERROR MESSAGE ===== */
.error-message {
  display: flex;
  align-items: center;
  gap: 10px;
  background: rgba(255, 59, 48, 0.1);
  color: #ff3b30;
  padding: 12px 16px;
  border-radius: 10px;
  font-size: 15px;
  margin-bottom: 20px;
  border: 0.5px solid rgba(255, 59, 48, 0.2);
}

.theme-dark .error-message {
  background: rgba(255, 59, 48, 0.15);
  border-color: rgba(255, 59, 48, 0.25);
}

.error-message svg {
  flex-shrink: 0;
}

/* ===== BUTTON ===== */
.btn-login {
  width: 100%;
  padding: 14px 24px;
  background: #007aff;
  color: white;
  border: none;
  border-radius: 12px;
  font-size: 17px;
  font-weight: 600;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 10px;
  transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
  position: relative;
  overflow: hidden;
  font-family: -apple-system, BlinkMacSystemFont, 'SF Pro Text', sans-serif;
  letter-spacing: -0.2px;
}

.btn-login:hover:not(:disabled) {
  background: #0051d5;
  box-shadow: 0 2px 8px rgba(0, 122, 255, 0.3);
}

.btn-login:active:not(:disabled) {
  transform: scale(0.98);
  background: #0040b3;
}

.btn-login:disabled {
  opacity: 0.5;
  cursor: not-allowed;
  transform: none;
}

.btn-login span {
  position: relative;
  z-index: 1;
}

.spinner {
  width: 20px;
  height: 20px;
  border: 2px solid rgba(255, 255, 255, 0.3);
  border-top-color: white;
  border-radius: 50%;
  animation: spin 0.8s linear infinite;
  position: relative;
  z-index: 1;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}

/* ===== FOOTER ===== */
.login-footer {
  text-align: center;
  padding-top: 28px;
  border-top: 0.5px solid rgba(0, 0, 0, 0.1);
}

.theme-dark .login-footer {
  border-top-color: rgba(255, 255, 255, 0.1);
}

.login-footer p {
  color: #86868b;
  font-size: 15px;
  margin: 0 0 8px;
  font-weight: 400;
}

.theme-dark .login-footer p {
  color: #a1a1a6;
}

.login-footer a {
  color: #007aff;
  font-size: 15px;
  font-weight: 500;
  text-decoration: none;
  transition: opacity 0.2s;
}

.login-footer a:hover {
  opacity: 0.7;
}

/* ===== CLIENT LINK ===== */
.client-link {
  text-align: center;
  margin-top: 40px;
}

.client-link p {
  color: #86868b;
  font-size: 15px;
  margin: 0 0 8px;
  font-weight: 400;
}

.theme-dark .client-link p {
  color: #a1a1a6;
}

.client-link-btn {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  color: #007aff;
  font-weight: 500;
  text-decoration: none;
  font-size: 17px;
  transition: all 0.2s;
  padding: 0;
  letter-spacing: -0.2px;
}

.client-link-btn:hover {
  opacity: 0.7;
}

.client-link-btn svg {
  transition: transform 0.2s;
}

.client-link-btn:hover svg {
  transform: translateX(2px);
}

/* ===== TRANSITIONS ===== */
.fade-enter-active,
.fade-leave-active {
  transition: all 0.3s ease;
}

.fade-enter-from,
.fade-leave-to {
  opacity: 0;
  transform: translateY(-10px);
}
</style>

