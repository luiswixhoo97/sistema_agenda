import api from './api'

export interface CrearPreferenciaRequest {
  monto: number
  descripcion: string
  payer_email?: string
  payer_name?: string
  payer_surname?: string
  external_reference: string
  back_url_success?: string
  back_url_failure?: string
  back_url_pending?: string
}

export interface PreferenciaResponse {
  success: boolean
  data?: {
    id: string
    init_point: string
    sandbox_init_point?: string
  }
  message?: string
}

export interface VerificarPagoResponse {
  success: boolean
  data?: {
    id: string | number
    status: string
    status_detail: string
    transaction_amount: number
    currency_id: string
    external_reference: string
    date_created: string
    date_approved?: string
  }
  message?: string
}

const mercadopagoService = {
  /**
   * Crear preferencia de pago para anticipo
   */
  async crearPreferencia(data: CrearPreferenciaRequest): Promise<PreferenciaResponse> {
    const response = await api.post('/publico/mercadopago/crear-preferencia', data)
    return response.data
  },

  /**
   * Verificar estado de un pago
   */
  async verificarPago(paymentId: string): Promise<VerificarPagoResponse> {
    const response = await api.get(`/publico/mercadopago/verificar/${paymentId}`)
    return response.data
  },

  /**
   * Redirigir al checkout de Mercado Pago
   */
  redirigirAlCheckout(initPoint: string): void {
    window.location.href = initPoint
  },

  /**
   * Obtener parámetros de la URL de retorno
   */
  obtenerParametrosRetorno(): {
    payment_id: string | null
    status: string | null
    external_reference: string | null
  } {
    const urlParams = new URLSearchParams(window.location.search)
    
    return {
      payment_id: urlParams.get('payment_id'),
      status: urlParams.get('status'),
      external_reference: urlParams.get('external_reference'),
    }
  },
}

export default mercadopagoService
