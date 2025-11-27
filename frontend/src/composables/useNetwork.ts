import { ref } from 'vue';
import { Network } from '@capacitor/network';
import type { ConnectionStatus } from '@capacitor/network';
import { Capacitor } from '@capacitor/core';

export function useNetwork() {
  const conectado = ref(true);
  const tipoConexion = ref<string>('unknown');
  const esNativo = Capacitor.isNativePlatform();

  let listener: any = null;

  /**
   * Verificar estado actual de la conexión
   */
  async function verificarEstado(): Promise<ConnectionStatus> {
    const status = await Network.getStatus();
    conectado.value = status.connected;
    tipoConexion.value = status.connectionType;
    return status;
  }

  /**
   * Iniciar monitoreo de la conexión
   */
  async function iniciarMonitoreo(
    onCambio?: (conectado: boolean) => void
  ) {
    // Verificar estado inicial
    await verificarEstado();

    // Escuchar cambios
    listener = Network.addListener('networkStatusChange', (status: ConnectionStatus) => {
      console.log('Network status changed:', status);
      conectado.value = status.connected;
      tipoConexion.value = status.connectionType;

      if (onCambio) {
        onCambio(status.connected);
      }
    });
  }

  /**
   * Detener monitoreo
   */
  function detenerMonitoreo() {
    if (listener) {
      listener.remove();
      listener = null;
    }
  }

  /**
   * Verificar si hay conexión WiFi
   */
  function esWifi(): boolean {
    return tipoConexion.value === 'wifi';
  }

  /**
   * Verificar si hay conexión celular
   */
  function esCelular(): boolean {
    return tipoConexion.value === 'cellular';
  }

  return {
    conectado,
    tipoConexion,
    esNativo,
    verificarEstado,
    iniciarMonitoreo,
    detenerMonitoreo,
    esWifi,
    esCelular,
  };
}

