<template>
  <div class="login-admin">
    <div class="login-container">
      <div class="login-card">
        <!-- Header -->
        <div class="login-header">
          <div class="logo-icon">💅</div>
          <h1>BeautySpa</h1>
          <p>Panel de Administración</p>
        </div>

        <!-- Formulario -->
        <form @submit.prevent="handleLogin" class="login-form">
          <div class="input-group">
            <label for="email">
              <span class="icon">📧</span>
              Email
            </label>
            <input
              id="email"
              v-model="credentials.email"
              type="email"
              placeholder="tu@email.com"
              :disabled="loading"
              required
              autocomplete="email"
            />
          </div>

          <div class="input-group">
            <label for="password">
              <span class="icon">🔒</span>
              Contraseña
            </label>
            <div class="password-input">
              <input
                id="password"
                v-model="credentials.password"
                :type="showPassword ? 'text' : 'password'"
                placeholder="••••••••"
                :disabled="loading"
                required
                autocomplete="current-password"
              />
              <button
                type="button"
                class="toggle-password"
                @click="showPassword = !showPassword"
              >
                {{ showPassword ? '👁️' : '👁️‍🗨️' }}
              </button>
            </div>
          </div>

          <div class="remember-forgot">
            <label class="remember">
              <input type="checkbox" v-model="rememberMe" />
              <span>Recordarme</span>
            </label>
            <a href="#" class="forgot-link">¿Olvidaste tu contraseña?</a>
          </div>

          <div v-if="error" class="error-message">
            {{ error }}
          </div>

          <button type="submit" class="btn-login" :disabled="loading">
            <span v-if="loading" class="spinner"></span>
            {{ loading ? 'Iniciando sesión...' : 'Iniciar Sesión' }}
          </button>
        </form>

        <!-- Footer del card -->
        <div class="card-footer">
          <p>¿Problemas para acceder?</p>
          <a href="#">Contactar soporte</a>
        </div>
      </div>

      <!-- Link para clientes -->
      <div class="client-link">
        <p>¿Eres cliente?</p>
        <router-link to="/">
          Agendar cita →
        </router-link>
      </div>
    </div>

    <!-- Decoración de fondo -->
    <div class="bg-decoration">
      <div class="circle circle-1"></div>
      <div class="circle circle-2"></div>
      <div class="circle circle-3"></div>
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
.login-admin {
  min-height: 100vh;
  display: flex;
  align-items: center;
  justify-content: center;
  background: linear-gradient(135deg, #1a1a2e 0%, #16213e 50%, #0f3460 100%);
  padding: 20px;
  position: relative;
  overflow: hidden;
}

.login-container {
  position: relative;
  z-index: 1;
  width: 100%;
  max-width: 420px;
}

.login-card {
  background: rgba(255, 255, 255, 0.95);
  border-radius: 24px;
  padding: 40px 32px;
  box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
}

.login-header {
  text-align: center;
  margin-bottom: 32px;
}

.logo-icon {
  font-size: 48px;
  margin-bottom: 12px;
}

.login-header h1 {
  font-family: 'Playfair Display', serif;
  font-size: 32px;
  color: #1a1a2e;
  margin: 0 0 4px;
  font-weight: 700;
}

.login-header p {
  color: #666;
  font-size: 14px;
  margin: 0;
}

.login-form {
  margin-bottom: 24px;
}

.input-group {
  margin-bottom: 20px;
}

.input-group label {
  display: flex;
  align-items: center;
  gap: 6px;
  font-size: 14px;
  font-weight: 500;
  color: #333;
  margin-bottom: 8px;
}

.input-group label .icon {
  font-size: 16px;
}

.input-group input {
  width: 100%;
  padding: 14px 16px;
  border: 2px solid #e0e0e0;
  border-radius: 12px;
  font-size: 16px;
  transition: all 0.2s;
  background: #fafafa;
  box-sizing: border-box;
}

.input-group input:focus {
  outline: none;
  border-color: #0f3460;
  background: white;
}

.password-input {
  position: relative;
}

.password-input input {
  padding-right: 50px;
}

.toggle-password {
  position: absolute;
  right: 12px;
  top: 50%;
  transform: translateY(-50%);
  background: none;
  border: none;
  cursor: pointer;
  font-size: 18px;
  opacity: 0.6;
}

.toggle-password:hover {
  opacity: 1;
}

.remember-forgot {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 24px;
  font-size: 14px;
}

.remember {
  display: flex;
  align-items: center;
  gap: 8px;
  cursor: pointer;
  color: #666;
}

.remember input[type="checkbox"] {
  width: 18px;
  height: 18px;
  accent-color: #0f3460;
}

.forgot-link {
  color: #0f3460;
  text-decoration: none;
}

.forgot-link:hover {
  text-decoration: underline;
}

.error-message {
  background: #ffebee;
  color: #c62828;
  padding: 12px;
  border-radius: 8px;
  font-size: 14px;
  margin-bottom: 16px;
  text-align: center;
}

.btn-login {
  width: 100%;
  padding: 16px;
  background: linear-gradient(135deg, #0f3460 0%, #16213e 100%);
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

.btn-login:hover:not(:disabled) {
  transform: translateY(-2px);
  box-shadow: 0 8px 20px rgba(15, 52, 96, 0.4);
}

.btn-login:disabled {
  opacity: 0.7;
  cursor: not-allowed;
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

.card-footer {
  text-align: center;
  padding-top: 20px;
  border-top: 1px solid #eee;
}

.card-footer p {
  color: #999;
  font-size: 13px;
  margin: 0 0 4px;
}

.card-footer a {
  color: #0f3460;
  font-size: 13px;
  text-decoration: none;
}

.card-footer a:hover {
  text-decoration: underline;
}

.client-link {
  text-align: center;
  margin-top: 24px;
}

.client-link p {
  color: rgba(255, 255, 255, 0.7);
  font-size: 14px;
  margin: 0 0 4px;
}

.client-link a {
  color: #e94560;
  font-weight: 600;
  text-decoration: none;
}

.client-link a:hover {
  text-decoration: underline;
}

/* Decoración de fondo */
.bg-decoration {
  position: absolute;
  inset: 0;
  overflow: hidden;
  pointer-events: none;
}

.circle {
  position: absolute;
  border-radius: 50%;
  opacity: 0.1;
}

.circle-1 {
  width: 400px;
  height: 400px;
  background: #e94560;
  top: -150px;
  right: -100px;
}

.circle-2 {
  width: 300px;
  height: 300px;
  background: #0f3460;
  bottom: -100px;
  left: -100px;
}

.circle-3 {
  width: 200px;
  height: 200px;
  background: #e94560;
  top: 50%;
  left: 50%;
  transform: translate(-50%, -50%);
}
</style>

