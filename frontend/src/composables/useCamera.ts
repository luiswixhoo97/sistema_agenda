import { ref } from 'vue';
import { Camera, CameraResultType, CameraSource } from '@capacitor/camera';
import type { Photo } from '@capacitor/camera';
import { Capacitor } from '@capacitor/core';

export function useCamera() {
  const foto = ref<string | null>(null);
  const loading = ref(false);
  const error = ref<string | null>(null);

  /**
   * Verificar si estamos en una plataforma nativa
   */
  const esNativo = Capacitor.isNativePlatform();

  /**
   * Tomar una foto con la cámara
   */
  async function tomarFoto(): Promise<string | null> {
    loading.value = true;
    error.value = null;

    try {
      const image = await Camera.getPhoto({
        quality: 90,
        allowEditing: false,
        resultType: CameraResultType.Base64,
        source: CameraSource.Camera,
        width: 1200,
        height: 1200,
        correctOrientation: true,
      });

      if (image.base64String) {
        foto.value = `data:image/${image.format};base64,${image.base64String}`;
        return foto.value;
      }

      return null;
    } catch (e: any) {
      error.value = e.message || 'Error al tomar foto';
      return null;
    } finally {
      loading.value = false;
    }
  }

  /**
   * Seleccionar foto de la galería
   */
  async function seleccionarDeGaleria(): Promise<string | null> {
    loading.value = true;
    error.value = null;

    try {
      const image = await Camera.getPhoto({
        quality: 90,
        allowEditing: false,
        resultType: CameraResultType.Base64,
        source: CameraSource.Photos,
        width: 1200,
        height: 1200,
        correctOrientation: true,
      });

      if (image.base64String) {
        foto.value = `data:image/${image.format};base64,${image.base64String}`;
        return foto.value;
      }

      return null;
    } catch (e: any) {
      error.value = e.message || 'Error al seleccionar foto';
      return null;
    } finally {
      loading.value = false;
    }
  }

  /**
   * Mostrar opciones (cámara o galería)
   */
  async function seleccionarFoto(): Promise<string | null> {
    loading.value = true;
    error.value = null;

    try {
      const image = await Camera.getPhoto({
        quality: 90,
        allowEditing: false,
        resultType: CameraResultType.Base64,
        source: CameraSource.Prompt, // Muestra opción de elegir
        promptLabelHeader: 'Seleccionar foto',
        promptLabelPhoto: 'Galería',
        promptLabelPicture: 'Cámara',
        promptLabelCancel: 'Cancelar',
        width: 1200,
        height: 1200,
        correctOrientation: true,
      });

      if (image.base64String) {
        foto.value = `data:image/${image.format};base64,${image.base64String}`;
        return foto.value;
      }

      return null;
    } catch (e: any) {
      // Usuario canceló
      if (e.message === 'User cancelled photos app') {
        return null;
      }
      error.value = e.message || 'Error al seleccionar foto';
      return null;
    } finally {
      loading.value = false;
    }
  }

  /**
   * Seleccionar múltiples fotos
   */
  async function seleccionarMultiples(limite: number = 5): Promise<string[]> {
    loading.value = true;
    error.value = null;

    try {
      const result = await Camera.pickImages({
        quality: 90,
        limit: limite,
        width: 1200,
        height: 1200,
        correctOrientation: true,
      });

      const fotos: string[] = [];

      for (const photo of result.photos) {
        // Leer el archivo y convertir a base64
        const response = await fetch(photo.webPath!);
        const blob = await response.blob();
        const base64 = await blobToBase64(blob);
        fotos.push(base64);
      }

      return fotos;
    } catch (e: any) {
      error.value = e.message || 'Error al seleccionar fotos';
      return [];
    } finally {
      loading.value = false;
    }
  }

  /**
   * Convertir Blob a Base64
   */
  function blobToBase64(blob: Blob): Promise<string> {
    return new Promise((resolve, reject) => {
      const reader = new FileReader();
      reader.onloadend = () => resolve(reader.result as string);
      reader.onerror = reject;
      reader.readAsDataURL(blob);
    });
  }

  /**
   * Convertir base64 a Blob para enviar al servidor
   */
  function base64ToBlob(base64: string): Blob {
    const parts = base64.split(';base64,');
    const contentTypePart = parts[0]?.split(':');
    const contentType = contentTypePart?.[1] || 'image/png';
    const base64Data = parts[1];
    if (!base64Data) {
      throw new Error('Invalid base64 data');
    }
    const raw = window.atob(base64Data);
    const rawLength = raw.length;
    const uInt8Array = new Uint8Array(rawLength);

    for (let i = 0; i < rawLength; ++i) {
      uInt8Array[i] = raw.charCodeAt(i);
    }

    return new Blob([uInt8Array], { type: contentType });
  }

  /**
   * Crear FormData con la foto para enviar al servidor
   */
  function crearFormData(nombreCampo: string = 'foto'): FormData | null {
    if (!foto.value) return null;

    const formData = new FormData();
    const blob = base64ToBlob(foto.value);
    formData.append(nombreCampo, blob, 'foto.jpg');

    return formData;
  }

  /**
   * Limpiar foto actual
   */
  function limpiar() {
    foto.value = null;
    error.value = null;
  }

  return {
    foto,
    loading,
    error,
    esNativo,
    tomarFoto,
    seleccionarDeGaleria,
    seleccionarFoto,
    seleccionarMultiples,
    base64ToBlob,
    crearFormData,
    limpiar,
  };
}

