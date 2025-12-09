import { ref } from 'vue';
import { Capacitor } from '@capacitor/core';
import { PushNotifications } from '@capacitor/push-notifications';
import { LocalNotifications } from '@capacitor/local-notifications';
import type { Token, PushNotificationSchema, ActionPerformed } from '@capacitor/push-notifications';
import api from '@/services/api';

export function usePushNotifications() {
  const token = ref<string | null>(null);
  const notificaciones = ref<PushNotificationSchema[]>([]);
  const permisoConcedido = ref(false);
  const errorRegistro = ref<string | null>(null);

  const esNativo = Capacitor.isNativePlatform();

  /**
   * Solicitar permisos para notificaciones push
   */
  async function solicitarPermiso(): Promise<boolean> {
    if (!esNativo) {
      console.log('Push notifications solo disponibles en dispositivos nativos');
      return false;
    }

    try {
      let permStatus = await PushNotifications.checkPermissions();

      if (permStatus.receive === 'prompt') {
        permStatus = await PushNotifications.requestPermissions();
      }

      if (permStatus.receive !== 'granted') {
        console.log('Permiso de push notifications denegado');
        errorRegistro.value = 'Permiso de notificaciones denegado';
        return false;
      }

      // También solicitar permisos para notificaciones locales
      try {
        const localPermStatus = await LocalNotifications.checkPermissions();
        if (localPermStatus.display !== 'granted') {
          await LocalNotifications.requestPermissions();
        }
      } catch (localError) {
        console.warn('Error con notificaciones locales:', localError);
      }

      permisoConcedido.value = true;
      return true;
    } catch (error) {
      console.error('Error solicitando permisos:', error);
      errorRegistro.value = 'Error al solicitar permisos de notificaciones';
      return false;
    }
  }

  /**
   * Registrar el dispositivo para push notifications
   */
  async function registrar(): Promise<string | null> {
    if (!esNativo) {
      console.log('No es plataforma nativa, saltando registro push');
      return null;
    }

    const tienePermiso = await solicitarPermiso();
    if (!tienePermiso) {
      console.log('Sin permisos para push notifications');
      return null;
    }

    return new Promise((resolve) => {
      // Timeout para evitar espera indefinida
      const timeout = setTimeout(() => {
        console.warn('Timeout esperando registro de push notifications');
        errorRegistro.value = 'Firebase no está configurado correctamente. Verifica google-services.json';
        resolve(null);
      }, 10000);

      // Escuchar el evento de registro exitoso
      PushNotifications.addListener('registration', async (tokenData: Token) => {
        clearTimeout(timeout);
        console.log('Push registration success, token:', tokenData.value);
        token.value = tokenData.value;
        errorRegistro.value = null;

        // Enviar token al backend
        try {
          await enviarTokenAlServidor(tokenData.value);
        } catch (e) {
          console.error('Error enviando token al servidor:', e);
        }

        resolve(tokenData.value);
      });

      // Escuchar errores de registro
      PushNotifications.addListener('registrationError', (error: any) => {
        clearTimeout(timeout);
        console.error('Error on registration:', error);
        
        // Determinar mensaje de error específico
        if (error?.error?.includes('SERVICE_NOT_AVAILABLE') || 
            error?.error?.includes('AUTHENTICATION_FAILED')) {
          errorRegistro.value = 'Firebase no está configurado. Agrega google-services.json';
        } else {
          errorRegistro.value = error?.error || 'Error al registrar notificaciones push';
        }
        
        resolve(null);
      });

      // Iniciar registro
      try {
        PushNotifications.register();
      } catch (e) {
        clearTimeout(timeout);
        console.error('Error llamando a register():', e);
        errorRegistro.value = 'Error al iniciar registro de notificaciones';
        resolve(null);
      }
    });
  }

  /**
   * Enviar token al servidor para guardarlo
   */
  async function enviarTokenAlServidor(fcmToken: string): Promise<void> {
    try {
      await api.post('/dispositivos/registrar', {
        token: fcmToken,
        plataforma: Capacitor.getPlatform(),
      });
      console.log('Token registrado en el servidor');
    } catch (error) {
      console.error('Error registrando token en servidor:', error);
    }
  }

  /**
   * Configurar listeners para notificaciones
   */
  function configurarListeners(
    onNotificacion?: (notificacion: PushNotificationSchema) => void,
    onAccion?: (accion: ActionPerformed) => void
  ) {
    if (!esNativo) return;

    // Notificación recibida con la app en primer plano
    PushNotifications.addListener('pushNotificationReceived', (notification: PushNotificationSchema) => {
      console.log('Push notification received:', notification);
      notificaciones.value.unshift(notification);

      if (onNotificacion) {
        onNotificacion(notification);
      }
    });

    // Usuario tocó la notificación
    PushNotifications.addListener('pushNotificationActionPerformed', (notification: ActionPerformed) => {
      console.log('Push notification action performed:', notification);

      if (onAccion) {
        onAccion(notification);
      }
    });
  }

  /**
   * Obtener notificaciones entregadas (en el centro de notificaciones)
   */
  async function obtenerEntregadas(): Promise<PushNotificationSchema[]> {
    if (!esNativo) return [];

    const result = await PushNotifications.getDeliveredNotifications();
    return result.notifications;
  }

  /**
   * Eliminar todas las notificaciones del centro de notificaciones
   */
  async function limpiarNotificaciones(): Promise<void> {
    if (!esNativo) return;

    await PushNotifications.removeAllDeliveredNotifications();
    notificaciones.value = [];
  }

  /**
   * Eliminar listeners al desmontar
   */
  function eliminarListeners() {
    if (!esNativo) return;
    PushNotifications.removeAllListeners();
  }

  return {
    token,
    notificaciones,
    permisoConcedido,
    errorRegistro,
    esNativo,
    solicitarPermiso,
    registrar,
    configurarListeners,
    obtenerEntregadas,
    limpiarNotificaciones,
    eliminarListeners,
  };
}

