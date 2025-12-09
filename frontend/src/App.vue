<script setup lang="ts">
import { onMounted, ref, provide, computed } from 'vue';
import { RouterView, useRoute } from 'vue-router';
import { useAuthStore } from '@/stores/auth';
import { useNetwork } from '@/composables/useNetwork';
import { usePushNotifications } from '@/composables/usePushNotifications';
import { useAppkit } from '@/composables/useAppkit';
import { Capacitor } from '@capacitor/core';
import { StatusBar, Style } from '@capacitor/status-bar';
import { SplashScreen } from '@capacitor/splash-screen';
import { App } from '@capacitor/app';
import NavBar from '@/components/NavBar.vue';

const authStore = useAuthStore();
const route = useRoute();
const { init: initAppkit } = useAppkit();

// Mostrar navbar en rutas de app (no en login, landing ni agendar)
const mostrarNavbar = computed(() => {
  const rutasSinNavbar = ['/login', '/', '/home', '/agendar'];
  // Mostrar solo para usuarios autenticados
  return authStore.isAuthenticated && !rutasSinNavbar.includes(route.path);
});
const { conectado, iniciarMonitoreo } = useNetwork();
const { registrar, configurarListeners } = usePushNotifications();

const appListo = ref(false);
const mostrarOffline = ref(false);

// Proveer estado de conexión a toda la app
provide('conectado', conectado);

onMounted(async () => {
  try {
    // Inicializar autenticación desde localStorage
    authStore.initFromStorage();

    // Configurar app nativa
    if (Capacitor.isNativePlatform()) {
      await configurarAppNativa();
    }

    // Iniciar monitoreo de red
    await iniciarMonitoreo((conectadoStatus) => {
      mostrarOffline.value = !conectadoStatus;
      if (conectadoStatus) {
        // Reconectado - podrías sincronizar datos
        console.log('Conexión restaurada');
      }
    });

    // Si el usuario está autenticado, registrar push notifications
    if (authStore.isAuthenticated && Capacitor.isNativePlatform()) {
      await registrar();
      configurarListeners(
        (notificacion) => {
          // Manejar notificación en primer plano
          console.log('Notificación recibida:', notificacion);
        },
        (accion) => {
          // Manejar tap en notificación
          console.log('Acción en notificación:', accion);
          // Aquí puedes navegar a la pantalla correspondiente
        }
      );
    }

  } catch (error) {
    console.error('Error inicializando app:', error);
  } finally {
    appListo.value = true;
    
    // Ocultar splash screen
    if (Capacitor.isNativePlatform()) {
      await SplashScreen.hide();
    }
  }
});

async function configurarAppNativa() {
  // Configurar barra de estado
  try {
    await StatusBar.setStyle({ style: Style.Light });
    await StatusBar.setBackgroundColor({ color: '#ec407a' });
  } catch (error) {
    console.log('StatusBar no disponible:', error);
  }

  // Manejar botón atrás en Android
  App.addListener('backButton', ({ canGoBack }) => {
    if (!canGoBack) {
      App.exitApp();
    } else {
      window.history.back();
    }
  });

  // Manejar URL scheme / deep links
  App.addListener('appUrlOpen', (data) => {
    console.log('App opened with URL:', data.url);
    // Manejar deep links aquí
  });

  // Manejar estado de la app
  App.addListener('appStateChange', ({ isActive }) => {
    console.log('App state changed. Is active:', isActive);
    if (isActive) {
      // App volvió al primer plano
    }
  });
}
</script>

<template>
  <!-- Preloader -->
  <div id="preloader" :class="{ 'preloader-hide': appListo }">
    <div class="spinner-border color-highlight" role="status"></div>
  </div>

  <div id="app-container" :class="{ 'has-navbar': mostrarNavbar }">
    <!-- Indicador de offline -->
    <div v-if="mostrarOffline" class="offline-toast">
      <i class="fa fa-wifi me-2"></i>
      Sin conexión a internet
    </div>

    <!-- NavBar -->
    <NavBar v-if="mostrarNavbar" />

    <!-- Contenido principal -->
    <main class="main-content">
      <RouterView />
    </main>
  </div>
</template>

<style>
/* Reset completo */
*, *::before, *::after {
  box-sizing: border-box;
  margin: 0;
  padding: 0;
}

html, body, #app {
  width: 100%;
  height: 100%;
  margin: 0;
  padding: 0;
  overflow-x: hidden;
}

html {
  font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
  -webkit-font-smoothing: antialiased;
  -moz-osx-font-smoothing: grayscale;
  line-height: 1.5;
}

body {
  min-height: 100vh;
  min-height: -webkit-fill-available;
  background: #f5f5f5;
  color: #333;
}

/* Dark mode */
.theme-dark body,
body.theme-dark {
  background: #0f0f0f;
  color: #eee;
}

/* App container - FULLSCREEN */
#app-container {
  width: 100%;
  min-height: 100vh;
  min-height: -webkit-fill-available;
  display: flex;
  flex-direction: column;
}

#app-container.has-navbar {
  padding-bottom: calc(60px + env(safe-area-inset-bottom, 0px));
}

/* Main content - ocupa todo */
.main-content {
  flex: 1;
  width: 100%;
  display: flex;
  flex-direction: column;
}

/* Preloader */
#preloader {
  position: fixed;
  inset: 0;
  background: linear-gradient(135deg, #ec407a 0%, #d81b60 100%);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 99999;
  transition: opacity 0.3s ease;
}

#preloader.preloader-hide {
  opacity: 0;
  pointer-events: none;
}

/* Offline toast */
.offline-toast {
  position: fixed;
  top: calc(env(safe-area-inset-top, 0px) + 16px);
  left: 16px;
  right: 16px;
  padding: 12px 16px;
  background: #ef4444;
  color: white;
  border-radius: 10px;
  font-size: 14px;
  z-index: 9999;
  box-shadow: 0 4px 12px rgba(0,0,0,0.2);
  animation: slideDown 0.3s ease;
}

@keyframes slideDown {
  from {
    transform: translateY(-20px);
    opacity: 0;
  }
  to {
    transform: translateY(0);
    opacity: 1;
  }
}

/* Scrollbar */
::-webkit-scrollbar {
  width: 4px;
}

::-webkit-scrollbar-track {
  background: transparent;
}

::-webkit-scrollbar-thumb {
  background: rgba(0, 0, 0, 0.2);
  border-radius: 2px;
}

/* Colores highlight */
.gradient-highlight {
  background: linear-gradient(135deg, #ec407a 0%, #d81b60 100%) !important;
}

.color-highlight {
  color: #ec407a !important;
}

.bg-highlight {
  background-color: #ec407a !important;
}

.border-highlight {
  border-color: #ec407a !important;
}

/* Spinner */
.spinner-border {
  width: 40px;
  height: 40px;
  border: 3px solid rgba(255,255,255,0.3);
  border-top-color: white;
  border-radius: 50%;
  animation: spin 0.8s linear infinite;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}

/* Estilos para ML Kit Barcode Scanner */
body.barcode-scanner-active {
  visibility: hidden;
  --background: transparent;
  --ion-background-color: transparent;
}

body.barcode-scanner-active #app-container {
  visibility: hidden;
}
</style>
