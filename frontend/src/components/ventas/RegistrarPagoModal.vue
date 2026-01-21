<script setup lang="ts">
import { ref, computed, watch, onMounted } from 'vue';
import inventarioService from '@/services/inventarioService';
import Swal from 'sweetalert2';

interface Props {
  visible: boolean;
  venta: any;
}

const props = defineProps<Props>();
const emit = defineEmits(['update:visible', 'pago-registrado', 'close']);

const metodosPago = ref<any[]>([]);
const loadingMetodos = ref(false);
const pagoMetodoId = ref<number | null>(null);
const pagoMonto = ref<number>(0);
const pagoMontoRecibido = ref<number>(0);
const pagoEsEfectivo = ref(false);

const pagoCambio = computed(() => {
  if (pagoEsEfectivo.value && pagoMontoRecibido.value > pagoMonto.value) {
    return pagoMontoRecibido.value - pagoMonto.value;
  }
  return 0;
});

const formatPrecio = (precio: any) => (Number(precio) || 0).toFixed(2);

const fetchMetodosPago = async () => {
  loadingMetodos.value = true;
  try {
    const response = await inventarioService.getMetodosPago();
    metodosPago.value = response.data || [];
  } catch (error) {
    console.error('Error al obtener métodos de pago:', error);
  } finally {
    loadingMetodos.value = false;
  }
};

const seleccionarMetodo = (m: any) => {
  pagoMetodoId.value = m.id;
  pagoEsEfectivo.value = m.es_efectivo;
};

const ejecutarPago = async () => {
  if (!pagoMetodoId.value || !pagoMonto.value || pagoMonto.value <= 0) return;

  try {
    const datosPago = {
      venta_id: props.venta.id,
      metodo_pago_id: pagoMetodoId.value,
      monto: pagoMonto.value,
      monto_recibido: pagoEsEfectivo.value ? pagoMontoRecibido.value : pagoMonto.value,
      cambio: pagoCambio.value
    };

    const response = await inventarioService.registrarPago(datosPago);
    if (response.success) {
      Swal.fire({
        icon: 'success',
        title: 'Pago registrado',
        text: 'El pago se ha procesado correctamente.',
        timer: 1500,
        showConfirmButton: false
      });
      emit('pago-registrado', response.data);
      closeModal();
    }
  } catch (error: any) {
    Swal.fire({
      icon: 'error',
      title: 'Error',
      text: error.response?.data?.message || 'Hubo un error al registrar el pago.'
    });
  }
};

const closeModal = () => {
  pagoMetodoId.value = null;
  pagoMonto.value = 0;
  pagoMontoRecibido.value = 0;
  pagoEsEfectivo.value = false;
  emit('update:visible', false);
  emit('close');
};

watch(() => props.visible, (newVal) => {
  if (newVal && props.venta) {
    pagoMonto.value = props.venta.saldo_pendiente || 0;
    pagoMontoRecibido.value = 0;
  }
});

onMounted(fetchMetodosPago);

</script>

<template>
  <Teleport to="body">
    <div v-if="visible" class="modal-overlay" @click.self="closeModal">
      <div class="modal-content modal-medium">
        <div class="modal-header pago-header">
          <h3>Registrar Pago</h3>
          <button class="modal-close" @click="closeModal">
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <line x1="18" y1="6" x2="6" y2="18"></line>
              <line x1="6" y1="6" x2="18" y2="18"></line>
            </svg>
          </button>
        </div>
        <div class="modal-body">
          <div class="pago-info" v-if="venta">
            <div class="pago-venta-id">Venta #{{ venta.id }}</div>
            <div class="pago-totales">
              <div class="pt-row">
                <span>Total de venta:</span>
                <span class="pt-value">${{ formatPrecio(venta.total) }}</span>
              </div>
              <div class="pt-row">
                <span>Ya pagado:</span>
                <span class="pt-value">${{ formatPrecio(venta.total_pagado) }}</span>
              </div>
              <div class="pt-row pendiente">
                <span>Saldo pendiente:</span>
                <span class="pt-value">${{ formatPrecio(venta.saldo_pendiente) }}</span>
              </div>
            </div>
          </div>
          
          <div class="form-group">
            <label class="form-label">Método de pago</label>
            <div class="metodos-grid">
              <button 
                v-for="m in metodosPago" 
                :key="m.id"
                :class="['metodo-btn', { active: pagoMetodoId === m.id }]"
                @click="seleccionarMetodo(m)"
              >
                <svg v-if="m.es_efectivo" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <line x1="12" y1="1" x2="12" y2="23"></line>
                  <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path>
                </svg>
                <svg v-else xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <rect x="1" y="4" width="22" height="16" rx="2" ry="2"></rect>
                  <line x1="1" y1="10" x2="23" y2="10"></line>
                </svg>
                {{ m.nombre }}
              </button>
            </div>
            <div v-if="metodosPago.length === 0 && !loadingMetodos" class="no-methods">
              No hay métodos de pago configurados
            </div>
          </div>
          
          <div class="form-group">
            <label class="form-label">Monto a pagar</label>
            <div class="monto-input-container">
              <span class="monto-prefix">$</span>
              <input 
                v-model.number="pagoMonto" 
                type="number" 
                step="0.01"
                min="0"
                :max="venta?.saldo_pendiente"
                class="form-input monto-input"
                placeholder="0.00"
              />
            </div>
            <div class="monto-quick-btns">
              <button @click="pagoMonto = venta?.saldo_pendiente || 0">Total</button>
              <button @click="pagoMonto = Math.ceil((venta?.saldo_pendiente || 0) / 100) * 100">Redondear</button>
            </div>
          </div>
          
          <div class="form-group" v-if="pagoEsEfectivo">
            <label class="form-label">Monto recibido</label>
            <div class="monto-input-container">
              <span class="monto-prefix">$</span>
              <input 
                v-model.number="pagoMontoRecibido" 
                type="number" 
                step="0.01"
                min="0"
                class="form-input monto-input"
                placeholder="0.00"
              />
            </div>
            <div class="cambio-display" v-if="pagoCambio > 0">
              Cambio: <strong>${{ formatPrecio(pagoCambio) }}</strong>
            </div>
          </div>
          
          <div class="form-actions">
            <button type="button" class="btn-cancel" @click="closeModal">
              Cancelar
            </button>
            <button 
              type="button" 
              class="btn-submit"
              @click="ejecutarPago"
              :disabled="!pagoMetodoId || !pagoMonto || pagoMonto <= 0"
            >
              <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <polyline points="20 6 9 17 4 12"></polyline>
              </svg>
              Registrar Pago
            </button>
          </div>
        </div>
      </div>
    </div>
  </Teleport>
</template>

<style scoped>
.modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.4);
  backdrop-filter: blur(8px);
  -webkit-backdrop-filter: blur(8px);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1000;
  padding: 20px;
}

.modal-content {
  background: #ffffff;
  border-radius: 24px;
  box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
  display: flex;
  flex-direction: column;
  overflow: hidden;
  max-width: 500px;
  width: 100%;
}

.modal-header {
  padding: 20px 24px;
  border-bottom: 0.5px solid rgba(0, 0, 0, 0.06);
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.modal-header h3 {
  margin: 0;
  font-size: 20px;
  font-weight: 700;
}

.modal-close {
  background: #f5f5f7;
  border: none;
  width: 32px;
  height: 32px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  color: #86868b;
}

.modal-body {
  padding: 24px;
}

.pago-info {
  text-align: center;
  padding: 20px;
  background: #fbfbfd;
  border-radius: 16px;
  margin-bottom: 24px;
}

.pago-venta-id {
  font-size: 14px;
  color: #86868b;
  font-weight: 600;
  margin-bottom: 12px;
}

.pago-totales {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.pt-row {
  display: flex;
  justify-content: space-between;
  font-size: 14px;
  color: #86868b;
}

.pt-value {
  font-weight: 600;
  color: #000000;
}

.pt-row.pendiente {
  margin-top: 8px;
  padding-top: 8px;
  border-top: 0.5px solid rgba(0, 0, 0, 0.06);
}

.pt-row.pendiente .pt-value {
  color: #ff9500;
  font-size: 20px;
  font-weight: 800;
}

.form-group {
  margin-bottom: 24px;
}

.form-label {
  display: block;
  font-size: 13px;
  font-weight: 600;
  margin-bottom: 12px;
}

.metodos-grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 12px;
}

.metodo-btn {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 8px;
  padding: 16px;
  background: #f5f5f7;
  border: 2px solid transparent;
  border-radius: 12px;
  font-size: 14px;
  font-weight: 600;
  color: #1d1d1f;
  cursor: pointer;
  transition: all 0.2s ease;
}

.metodo-btn.active {
  background: #000000;
  color: #ffffff;
}

.monto-input-container {
  display: flex;
  align-items: center;
  background: #f5f5f7;
  border-radius: 12px;
  padding: 0 16px;
}

.monto-prefix {
  font-size: 24px;
  font-weight: 700;
  color: #86868b;
  margin-right: 8px;
}

.monto-input {
  flex: 1;
  border: none;
  background: transparent;
  padding: 16px 0;
  font-size: 28px;
  font-weight: 700;
  outline: none;
}

.monto-quick-btns {
  display: flex;
  gap: 8px;
  margin-top: 12px;
}

.monto-quick-btns button {
  padding: 8px 16px;
  background: #f5f5f7;
  border: none;
  border-radius: 8px;
  font-size: 12px;
  font-weight: 600;
  cursor: pointer;
}

.cambio-display {
  margin-top: 16px;
  padding: 12px;
  background: rgba(52, 199, 89, 0.1);
  color: #34c759;
  border-radius: 8px;
  text-align: center;
}

.cambio-display strong {
  display: block;
  font-size: 20px;
}

.form-actions {
  display: flex;
  gap: 12px;
}

.btn-cancel, .btn-submit {
  flex: 1;
  padding: 16px;
  border-radius: 14px;
  font-size: 16px;
  font-weight: 700;
  cursor: pointer;
  border: none;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
}

.btn-cancel {
  background: #f5f5f7;
}

.btn-submit {
  background: #000000;
  color: #ffffff;
}

.btn-submit:disabled {
  opacity: 0.4;
}

.no-methods {
  text-align: center;
  color: #86868b;
  font-size: 14px;
  padding: 20px;
}
</style>
