<script setup lang="ts">
import { ref, computed, watch, onMounted } from 'vue';
import inventarioService from '@/services/inventarioService';
import ClienteSelector from './ClienteSelector.vue';
import Swal from 'sweetalert2';

interface Props {
  visible: boolean;
  clientePreseleccionado?: { id: number; nombre: string } | null;
  itemsPreseleccionados?: Array<{
    tipo: 'producto' | 'servicio';
    id: number;
    nombre: string;
    precio: number;
    cantidad: number;
    cita_id?: number;
  }>;
  citasIds?: number[];
}

const props = defineProps<Props>();
const emit = defineEmits(['update:visible', 'venta-created', 'close']);

const idCliente = ref<number | null>(null);
const busquedaTermino = ref('');
const vistaActiva = ref<'productos' | 'servicios'>('productos');
const loading = ref(false);
const productosDisponibles = ref<any[]>([]);
const serviciosDisponibles = ref<any[]>([]);
const carrito = ref<any[]>([]);

const subtotalCarrito = computed(() => {
  return carrito.value.reduce((sum, item) => sum + (item.cantidad * item.precio_unitario), 0);
});

const totalCarrito = computed(() => subtotalCarrito.value);

const formatPrecio = (precio: any) => (Number(precio) || 0).toFixed(2);

const buscar = async () => {
  if (busquedaTermino.value.length < 1) {
    productosDisponibles.value = [];
    serviciosDisponibles.value = [];
    return;
  }

  loading.value = true;
  try {
    if (vistaActiva.value === 'productos') {
      const response = await inventarioService.buscarProductosVenta(busquedaTermino.value);
      productosDisponibles.value = response.data || [];
    } else {
      const response = await inventarioService.buscarServiciosVenta(busquedaTermino.value);
      serviciosDisponibles.value = response.data || [];
    }
  } catch (error) {
    console.error('Error al buscar:', error);
  } finally {
    loading.value = false;
  }
};

const agregarAlCarrito = (item: any, tipo: 'producto' | 'servicio') => {
  const index = carrito.value.findIndex(i => i.id === item.id && i.tipo === tipo);
  if (index >= 0) {
    carrito.value[index].cantidad++;
  } else {
    carrito.value.push({
      tipo,
      producto_id: tipo === 'producto' ? item.id : null,
      servicio_id: tipo === 'servicio' ? item.id : null,
      id: item.id,
      nombre: item.nombre,
      precio_unitario: item.precio,
      cantidad: 1,
      cita_id: item.cita_id || null
    });
  }
};

const quitarDelCarrito = (index: number) => {
  carrito.value.splice(index, 1);
};

const cambiarCantidad = (index: number, delta: number) => {
  const nuevaCantidad = carrito.value[index].cantidad + delta;
  if (nuevaCantidad > 0) {
    carrito.value[index].cantidad = nuevaCantidad;
  }
};

const procesarVenta = async () => {
  if (carrito.value.length === 0) return;

  try {
    const ventaData = {
      cliente_id: idCliente.value || undefined,
      detalles: carrito.value.map(item => ({
        tipo: item.tipo,
        producto_id: item.producto_id || undefined,
        servicio_id: item.servicio_id || undefined,
        cantidad: item.cantidad,
        precio_unitario: item.precio_unitario,
        cita_id: item.cita_id || undefined
      })),
      citas_ids: props.citasIds || []
    };

    const response = await inventarioService.createVenta(ventaData);
    if (response.success) {
      Swal.fire({
        icon: 'success',
        title: 'Venta creada',
        text: 'La venta se ha registrado exitosamente.',
        timer: 1500,
        showConfirmButton: false
      });
      emit('venta-created', response.data);
      closeModal();
    }
  } catch (error: any) {
    Swal.fire({
      icon: 'error',
      title: 'Error',
      text: error.response?.data?.message || 'Hubo un error al procesar la venta.'
    });
  }
};

const closeModal = () => {
  carrito.value = [];
  busquedaTermino.value = '';
  productosDisponibles.value = [];
  serviciosDisponibles.value = [];
  emit('update:visible', false);
  emit('close');
};

watch(() => props.visible, (newVal) => {
  if (newVal) {
    if (props.clientePreseleccionado) {
      idCliente.value = props.clientePreseleccionado.id;
    }
    if (props.itemsPreseleccionados) {
      carrito.value = props.itemsPreseleccionados.map(item => ({
        tipo: item.tipo,
        producto_id: item.tipo === 'producto' ? item.id : null,
        servicio_id: item.tipo === 'servicio' ? item.id : null,
        id: item.id,
        nombre: item.nombre,
        precio_unitario: item.precio,
        cantidad: item.cantidad,
        cita_id: item.cita_id
      }));
    }
  }
});

// Cargar productos iniciales al abrir
watch(vistaActiva, () => {
  busquedaTermino.value = '';
  productosDisponibles.value = [];
  serviciosDisponibles.value = [];
});

</script>

<template>
  <Teleport to="body">
    <div v-if="visible" class="modal-overlay" @click.self="closeModal">
      <div class="modal-content modal-fullscreen">
        <div class="modal-header">
          <div class="header-main">
            <h3>Nueva Venta</h3>
            <div class="header-client">
              <ClienteSelector v-model="idCliente" />
            </div>
          </div>
          <button class="modal-close" @click="closeModal">
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <line x1="18" y1="6" x2="6" y2="18"></line>
              <line x1="6" y1="6" x2="18" y2="18"></line>
            </svg>
          </button>
        </div>

        <div class="modal-body pos-layout">
          <!-- Left: Search & Products/Services -->
          <div class="pos-productos">
            <div class="pos-tabs">
              <button 
                :class="['tab-btn', { active: vistaActiva === 'productos' }]"
                @click="vistaActiva = 'productos'"
              >Productos</button>
              <button 
                :class="['tab-btn', { active: vistaActiva === 'servicios' }]"
                @click="vistaActiva = 'servicios'"
              >Servicios</button>
            </div>

            <div class="pos-search">
              <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="11" cy="11" r="8"></circle>
                <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
              </svg>
              <input 
                v-model="busquedaTermino" 
                type="text" 
                :placeholder="'Buscar ' + vistaActiva + '...'"
                @input="buscar"
              />
            </div>
            
            <div class="productos-scroll">
              <template v-if="vistaActiva === 'productos'">
                <div 
                  v-for="p in productosDisponibles" 
                  :key="p.id" 
                  class="producto-card"
                  @click="agregarAlCarrito(p, 'producto')"
                >
                  <div class="product-header">
                    <span class="product-code">{{ p.codigo }}</span>
                    <span class="product-stock" :class="{ low: p.inventario_actual <= 5 }">
                      {{ p.inventario_actual || 0 }}
                    </span>
                  </div>
                  <h4 class="product-name">{{ p.nombre }}</h4>
                  <div class="product-price">${{ formatPrecio(p.precio) }}</div>
                </div>
              </template>
              <template v-else>
                <div 
                  v-for="s in serviciosDisponibles" 
                  :key="s.id" 
                  class="producto-card servicio-card"
                  @click="agregarAlCarrito(s, 'servicio')"
                >
                  <div class="product-header">
                    <span class="product-code">SERV</span>
                    <span class="product-duration">{{ s.duracion }} min</span>
                  </div>
                  <h4 class="product-name">{{ s.nombre }}</h4>
                  <div class="product-price">${{ formatPrecio(s.precio) }}</div>
                </div>
              </template>
              <div v-if="loading" class="loading-state">Buscando...</div>
              <div v-if="!loading && busquedaTermino && productosDisponibles.length === 0 && serviciosDisponibles.length === 0" class="no-results">
                No se encontraron resultados
              </div>
            </div>
          </div>
          
          <!-- Right: Cart -->
          <div class="pos-carrito">
            <div class="carrito-header">
              <h4>
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <circle cx="9" cy="21" r="1"></circle>
                  <circle cx="20" cy="21" r="1"></circle>
                  <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path>
                </svg>
                Carrito
              </h4>
              <span class="carrito-count">{{ carrito.length }} items</span>
            </div>
            
            <div class="carrito-items" v-if="carrito.length > 0">
              <div 
                v-for="(item, index) in carrito" 
                :key="index" 
                class="carrito-item"
              >
                <div class="item-info">
                  <span class="item-nombre">{{ item.nombre }}</span>
                  <div class="item-meta">
                    <span class="item-tipo">{{ item.tipo }}</span>
                    <span class="item-precio">${{ formatPrecio(item.precio_unitario) }}</span>
                  </div>
                </div>
                <div class="item-controls">
                  <button class="qty-btn" @click="cambiarCantidad(index, -1)">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                      <line x1="5" y1="12" x2="19" y2="12"></line>
                    </svg>
                  </button>
                  <span class="item-qty">{{ item.cantidad }}</span>
                  <button class="qty-btn" @click="cambiarCantidad(index, 1)">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                      <line x1="12" y1="5" x2="12" y2="19"></line>
                      <line x1="5" y1="12" x2="19" y2="12"></line>
                    </svg>
                  </button>
                </div>
                <div class="item-subtotal">
                  ${{ formatPrecio(item.cantidad * item.precio_unitario) }}
                </div>
                <button class="item-remove" @click="quitarDelCarrito(index)">
                  <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="18" y1="6" x2="6" y2="18"></line>
                    <line x1="6" y1="6" x2="18" y2="18"></line>
                  </svg>
                </button>
              </div>
            </div>
            
            <div class="carrito-empty" v-else>
              <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="9" cy="21" r="1"></circle>
                <circle cx="20" cy="21" r="1"></circle>
                <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path>
              </svg>
              <p>Carrito vacío</p>
            </div>
            
            <div class="carrito-totales">
              <div class="total-line">
                <span>Subtotal</span>
                <span>${{ formatPrecio(subtotalCarrito) }}</span>
              </div>
              <div class="total-line total-final">
                <span>Total</span>
                <span>${{ formatPrecio(totalCarrito) }}</span>
              </div>
            </div>
            
            <div class="carrito-actions">
              <button 
                class="btn-venta" 
                @click="procesarVenta"
                :disabled="carrito.length === 0"
              >
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <polyline points="20 6 9 17 4 12"></polyline>
                </svg>
                Finalizar Venta
              </button>
            </div>
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
  background: #f5f5f7;
  border-radius: 24px;
  box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
  display: flex;
  flex-direction: column;
  overflow: hidden;
  max-width: 1200px;
  width: 100%;
}

.modal-fullscreen {
  width: 95vw;
  height: 90vh;
}

.modal-header {
  padding: 20px 24px;
  background: #ffffff;
  border-bottom: 0.5px solid rgba(0, 0, 0, 0.06);
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.header-main {
  display: flex;
  align-items: center;
  gap: 32px;
  flex: 1;
}

.header-client {
  width: 350px;
}

.modal-header h3 {
  margin: 0;
  font-size: 24px;
  font-weight: 800;
  color: #000000;
  letter-spacing: -0.02em;
}

.modal-close {
  background: #f5f5f7;
  border: none;
  width: 36px;
  height: 36px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  color: #86868b;
  transition: all 0.2s ease;
}

.modal-close:hover {
  background: #e5e5e7;
  color: #000000;
}

.pos-layout {
  display: grid;
  grid-template-columns: 1fr 400px;
  flex: 1;
  overflow: hidden;
}

.pos-productos {
  background: #f5f5f7;
  border-right: 0.5px solid rgba(0, 0, 0, 0.06);
  display: flex;
  flex-direction: column;
  padding: 20px;
  overflow: hidden;
}

.pos-tabs {
  display: flex;
  gap: 8px;
  margin-bottom: 20px;
  background: rgba(0, 0, 0, 0.04);
  padding: 4px;
  border-radius: 12px;
  width: fit-content;
}

.tab-btn {
  padding: 8px 16px;
  border: none;
  border-radius: 8px;
  background: transparent;
  color: #86868b;
  font-size: 14px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s ease;
}

.tab-btn.active {
  background: #ffffff;
  color: #000000;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
}

.pos-search {
  margin-bottom: 20px;
  display: flex;
  align-items: center;
  background: #ffffff;
  border-radius: 14px;
  padding: 0 16px;
  border: 1px solid rgba(0, 0, 0, 0.06);
}

.pos-search svg {
  color: #86868b;
  margin-right: 12px;
}

.pos-search input {
  flex: 1;
  border: none;
  padding: 14px 0;
  background: transparent;
  font-size: 16px;
  font-weight: 500;
  outline: none;
  color: #000000;
}

.productos-scroll {
  flex: 1;
  overflow-y: auto;
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
  gap: 16px;
  padding-bottom: 20px;
}

.producto-card {
  background: #ffffff;
  border-radius: 16px;
  padding: 18px;
  cursor: pointer;
  transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
  border: 1px solid rgba(0, 0, 0, 0.06);
}

.producto-card:active {
  transform: scale(0.96);
}

.product-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 12px;
}

.product-code {
  font-size: 11px;
  font-weight: 700;
  color: #007aff;
  text-transform: uppercase;
}

.product-stock, .product-duration {
  font-size: 12px;
  font-weight: 600;
  padding: 4px 8px;
  border-radius: 6px;
}

.product-stock {
  color: #34c759;
  background: rgba(52, 199, 89, 0.1);
}

.product-stock.low {
  color: #ff3b30;
  background: rgba(255, 59, 48, 0.1);
}

.product-duration {
  color: #5856d6;
  background: rgba(88, 86, 214, 0.1);
}

.product-name {
  font-size: 15px;
  font-weight: 600;
  color: #000000;
  margin: 0 0 12px;
  line-height: 1.3;
}

.product-price {
  font-size: 20px;
  font-weight: 700;
  color: #000000;
}

.pos-carrito {
  background: #ffffff;
  display: flex;
  flex-direction: column;
  overflow: hidden;
}

.carrito-header {
  padding: 24px;
  border-bottom: 0.5px solid rgba(0, 0, 0, 0.06);
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.carrito-header h4 {
  margin: 0;
  display: flex;
  align-items: center;
  gap: 10px;
  font-size: 18px;
  font-weight: 700;
}

.carrito-count {
  font-size: 13px;
  color: #86868b;
  font-weight: 500;
}

.carrito-items {
  flex: 1;
  overflow-y: auto;
  padding: 12px 24px;
}

.carrito-item {
  display: grid;
  grid-template-columns: 1fr auto auto auto;
  align-items: center;
  gap: 16px;
  padding: 16px 0;
  border-bottom: 0.5px solid rgba(0, 0, 0, 0.04);
}

.item-info {
  min-width: 0;
}

.item-nombre {
  display: block;
  font-size: 15px;
  font-weight: 600;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.item-meta {
  display: flex;
  gap: 8px;
  align-items: center;
  font-size: 12px;
  color: #86868b;
}

.item-tipo {
  text-transform: capitalize;
}

.item-controls {
  display: flex;
  align-items: center;
  gap: 8px;
  background: #f5f5f7;
  border-radius: 8px;
  padding: 4px;
}

.qty-btn {
  width: 24px;
  height: 24px;
  border: none;
  border-radius: 6px;
  background: transparent;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
}

.qty-btn:active {
  background: rgba(0,0,0,0.05);
}

.item-qty {
  font-weight: 600;
  min-width: 20px;
  text-align: center;
}

.item-subtotal {
  font-weight: 700;
  font-size: 16px;
}

.item-remove {
  color: #ff3b30;
  background: rgba(255, 59, 48, 0.1);
  border: none;
  width: 28px;
  height: 28px;
  border-radius: 6px;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
}

.carrito-totales {
  padding: 24px;
  background: #fbfbfd;
  border-top: 0.5px solid rgba(0, 0, 0, 0.06);
}

.total-line {
  display: flex;
  justify-content: space-between;
  margin-bottom: 8px;
  font-size: 15px;
  color: #86868b;
}

.total-final {
  font-size: 22px;
  font-weight: 800;
  color: #000000;
  margin-top: 12px;
  padding-top: 12px;
  border-top: 0.5px solid rgba(0, 0, 0, 0.1);
}

.carrito-actions {
  padding: 0 24px 24px;
}

.btn-venta {
  width: 100%;
  padding: 16px;
  background: #000000;
  color: #ffffff;
  border: none;
  border-radius: 14px;
  font-size: 16px;
  font-weight: 700;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 10px;
}

.btn-venta:disabled {
  opacity: 0.4;
}

.loading-state, .no-results {
  grid-column: 1 / -1;
  padding: 40px;
  text-align: center;
  color: #86868b;
}
</style>
