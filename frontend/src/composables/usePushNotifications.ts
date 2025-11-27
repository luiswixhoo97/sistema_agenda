import { ref } from 'vue';
import { Capacitor } from '@capacitor/core';
import { PushNotifications } from '@capacitor/push-notifications';
import type { Token, PushNotificationSchema, ActionPerformed } from '@capacitor/push-notifications';
import api from '@/services/api';

export function usePushNotifications() {
  const token = ref<string | null>(null);
  const notificaciones = ref<PushNotificationSchema[]>([]);
  const permisoConcedido = ref(false);

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
        return false;
      }

      permisoConcedido.value = true;
      return true;
    } catch (error) {
      console.error('Error solicitando permisos:', error);
      return false;
    }
  }

  /**
   * Registrar el dispositivo para push notifications
   */
  async function registrar(): Promise<string | null> {
    if (!esNativo) return null;

    const tienePermiso = await solicitarPermiso();
    if (!tienePermiso) return null;

    return new Promise((resolve) => {
      // Escuchar el evento de registro exitoso
      PushNotifications.addListener('registration', async (tokenData: Token) => {
        console.log('Push registration success, token:', tokenData.value);
        token.value = tokenData.value;

        // Enviar token al backend
        await enviarTokenAlServidor(tokenData.value);

        resolve(tokenData.value);
      });

      // Escuchar errores de registro
      PushNotifications.addListener('registrationError', (error: any) => {
        console.error('Error on registration:', error);
        resolve(null);
      });

      // Iniciar registro
      PushNotifications.register();
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
    esNativo,
    solicitarPermiso,
    registrar,
    configurarListeners,
    obtenerEntregadas,
    limpiarNotificaciones,
    eliminarListeners,
  };
}

