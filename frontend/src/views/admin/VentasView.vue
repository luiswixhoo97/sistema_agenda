<template>
  <div class="ventas-view">
    <!-- Header -->
    <header class="servicios-header">
      <div class="header-left">
        <div class="header-icon">
          <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <circle cx="9" cy="21" r="1"></circle>
            <circle cx="20" cy="21" r="1"></circle>
            <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path>
          </svg>
        </div>
        <div class="header-text">
          <h1>Ventas</h1>
          <p class="header-subtitle">{{ ventas.length }} registradas</p>
        </div>
      </div>
      <div class="header-actions">
        <button class="btn-new-servicio" @click="nuevaVenta">
          <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
            <line x1="12" y1="5" x2="12" y2="19"></line>
            <line x1="5" y1="12" x2="19" y2="12"></line>
          </svg>
          Nueva venta
        </button>
      </div>
    </header>

    <!-- Quick Stats -->
    <div class="stats-row">
      <div class="stat-card">
        <div class="stat-value">${{ formatPrecio(ventasHoy) }}</div>
        <div class="stat-label">Hoy</div>
      </div>
      <div class="stat-card">
        <div class="stat-value">{{ totalVentasHoy }}</div>
        <div class="stat-label">Transacciones</div>
      </div>
      <div class="stat-card" @click="vistaActiva = 'pendientes'">
        <div class="stat-value accent">{{ ventasPendientes.length }}</div>
        <div class="stat-label">Pendientes</div>
      </div>
    </div>

    <!-- Vista Tabs -->
    <div class="view-tabs">
      <button 
        :class="['view-tab', { active: vistaActiva === 'historial' }]"
        @click="vistaActiva = 'historial'"
      >
        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
          <polyline points="14 2 14 8 20 8"></polyline>
        </svg>
        Historial
      </button>
      <button 
        :class="['view-tab', { active: vistaActiva === 'pendientes' }]"
        @click="vistaActiva = 'pendientes'"
      >
        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <circle cx="12" cy="12" r="10"></circle>
          <polyline points="12 6 12 12 16 14"></polyline>
        </svg>
        Pendientes
        <span v-if="ventasPendientes.length > 0" class="badge">{{ ventasPendientes.length }}</span>
      </button>
    </div>

    <!-- Search & Filter -->
    <div class="search-section">
      <div class="search-input">
        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <circle cx="11" cy="11" r="8"></circle>
          <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
        </svg>
        <input 
          v-model="busqueda" 
          type="text" 
          placeholder="Buscar por ID o cliente"
        />
      </div>
    </div>

    <!-- Loading -->
    <div v-if="loading" class="loading-container">
      <div class="loading-spinner"></div>
    </div>

    <!-- Empty State -->
    <div v-else-if="ventasFiltradas.length === 0" class="empty-container">
      <div class="empty-icon">
        <svg xmlns="http://www.w3.org/2000/svg" width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1" stroke-linecap="round" stroke-linejoin="round">
          <circle cx="9" cy="21" r="1"></circle>
          <circle cx="20" cy="21" r="1"></circle>
          <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path>
        </svg>
      </div>
      <h3>{{ busqueda || filtroEstado ? 'Sin resultados' : 'Sin ventas' }}</h3>
      <p>{{ busqueda || filtroEstado ? 'Intenta con otros filtros' : 'Comienza creando tu primera venta' }}</p>
      <button class="btn-create" @click="nuevaVenta">Crear venta</button>
    </div>

    <!-- Ventas List -->
    <div v-else class="sales-list">
      <div 
        v-for="venta in ventasFiltradas" 
        :key="venta.id" 
        class="sale-card"
        @click="verDetalle(venta)"
      >
        <div class="sale-main">
          <div class="sale-info">
            <div class="sale-number">#{{ venta.id }}</div>
            <div class="sale-meta">
              <span v-if="venta.cliente">{{ venta.cliente.nombre }}</span>
              <span v-else class="meta-empty">Sin cliente</span>
            </div>
            <div class="sale-date">{{ formatFechaCompact(venta.fecha_venta) }}</div>
          </div>
          
          <div class="sale-amount">
            <div class="amount-total">${{ formatPrecio(venta.total) }}</div>
            <div :class="['sale-status', getEstadoClass(venta.estado)]">
              {{ getEstadoLabel(venta.estado) }}
            </div>
          </div>
        </div>

        <div v-if="venta.saldo_pendiente > 0" class="sale-pending">
          <span class="pending-label">Pendiente</span>
          <span class="pending-amount">${{ formatPrecio(venta.saldo_pendiente) }}</span>
        </div>

        <div class="sale-actions" @click.stop v-if="venta.estado !== 'completada' && venta.estado !== 'cancelada'">
          <button class="action-pay" @click="abrirPago(venta)">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <rect x="1" y="4" width="22" height="16" rx="2" ry="2"></rect>
              <line x1="1" y1="10" x2="23" y2="10"></line>
            </svg>
            Pagar
          </button>
        </div>
      </div>
    </div>

    <!-- Modal Nueva Venta -->
    <NuevaVentaModal 
      v-model:visible="showNuevaVentaModal"
      :cliente-preseleccionado="preselectedClient"
      :items-preseleccionados="preselectedItems"
      :citas-ids="activeCitasIds"
      @venta-created="onVentaCreada"
    />

    <!-- Modal Pago -->
    <RegistrarPagoModal
      v-model:visible="showPagoModal"
      :venta="activeVenta"
      @pago-registrado="cargarDatos"
    />

    <!-- Modal Detalle Venta -->
    <Teleport to="body">
      <div v-if="showDetalleModal" class="modal-overlay" @click.self="closeDetalleModal">
        <div class="modal-content modal-medium">
          <div class="modal-header detalle-header">
            <h3>Detalle de Venta #{{ detalleVenta?.id }}</h3>
            <button class="modal-close" @click="closeDetalleModal">
              <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <line x1="18" y1="6" x2="6" y2="18"></line>
                <line x1="6" y1="6" x2="18" y2="18"></line>
              </svg>
            </button>
          </div>
          <div class="modal-body" v-if="detalleVenta">
            <div class="detalle-info">
              <div class="di-row">
                <span class="di-label">Estado:</span>
                <span :class="['estado-badge', getEstadoClass(detalleVenta.estado)]">
                  {{ getEstadoLabel(detalleVenta.estado) }}
                </span>
              </div>
              <div class="di-row" v-if="detalleVenta.cliente">
                <span class="di-label">Cliente:</span>
                <span class="di-value">{{ detalleVenta.cliente.nombre }}</span>
              </div>
              <div class="di-row">
                <span class="di-label">Fecha:</span>
                <span class="di-value">{{ formatFecha(detalleVenta.fecha_venta) }}</span>
              </div>
            </div>
            
            <div class="detalle-items">
              <h4>Productos/Servicios</h4>
              <div class="items-list">
                <div 
                  v-for="d in detalleVenta.detalles" 
                  :key="d.id" 
                  class="item-row"
                >
                  <div class="ir-info">
                    <span class="ir-nombre">
                      {{ d.tipo === 'producto' ? d.producto?.nombre : d.servicio?.nombre }}
                    </span>
                    <span class="ir-tipo">{{ d.tipo }}</span>
                  </div>
                  <div class="ir-qty">x{{ d.cantidad }}</div>
                  <div class="ir-precio">${{ formatPrecio(d.precio_unitario) }}</div>
                  <div class="ir-subtotal">${{ formatPrecio(d.subtotal_linea) }}</div>
                </div>
              </div>
            </div>
            
            <div class="detalle-totales">
              <div class="dt-row">
                <span>Subtotal:</span>
                <span>${{ formatPrecio(detalleVenta.subtotal) }}</span>
              </div>
              <div class="dt-row" v-if="detalleVenta.descuento_general > 0">
                <span>Descuento:</span>
                <span>-${{ formatPrecio(detalleVenta.descuento_general) }}</span>
              </div>
              <div class="dt-row total">
                <span>Total:</span>
                <span>${{ formatPrecio(detalleVenta.total) }}</span>
              </div>
              <div class="dt-row pagado">
                <span>Pagado:</span>
                <span>${{ formatPrecio(detalleVenta.total_pagado) }}</span>
              </div>
              <div class="dt-row pendiente" v-if="detalleVenta.saldo_pendiente > 0">
                <span>Pendiente:</span>
                <span>${{ formatPrecio(detalleVenta.saldo_pendiente) }}</span>
              </div>
            </div>
            
            <div class="detalle-actions" v-if="detalleVenta.estado !== 'completada' && detalleVenta.estado !== 'cancelada'">
              <button class="btn-pagar-detalle" @click="abrirPago(detalleVenta); closeDetalleModal()">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <rect x="1" y="4" width="22" height="16" rx="2" ry="2"></rect>
                  <line x1="1" y1="10" x2="23" y2="10"></line>
                </svg>
                Registrar Pago
              </button>
            </div>
          </div>
        </div>
      </div>
    </Teleport>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, watch } from 'vue';
import { useRoute } from 'vue-router';
import { 
  getVentas,
  getVentasByUser
} from '@/services/inventarioService';
import NuevaVentaModal from '@/components/ventas/NuevaVentaModal.vue';
import RegistrarPagoModal from '@/components/ventas/RegistrarPagoModal.vue';
import Swal from 'sweetalert2';

// State
const ventas = ref<any[]>([]);
const loading = ref(true);
const vistaActiva = ref<'historial' | 'pendientes'>('historial');
const busqueda = ref('');
const filtroEstado = ref('');

// Modal states
const showNuevaVentaModal = ref(false);
const showPagoModal = ref(false);
const showDetalleModal = ref(false);

// Pre-loading data for sale
const preselectedItems = ref<any[]>([]);
const preselectedClient = ref<any>(null);
const activeCitasIds = ref<number[]>([]);

// Current sale for payment
const activeVenta = ref<any>(null);

// Detalle
const detalleVenta = ref<any>(null);

const ventasFiltradas = computed(() => {
  let filtered = ventas.value;
  
  if (vistaActiva.value === 'pendientes') {
    filtered = filtered.filter(v => v.estado === 'pendiente_pago' || v.estado === 'parcial');
  }
  
  if (filtroEstado.value) {
    filtered = filtered.filter(v => v.estado === filtroEstado.value);
  }
  
  if (busqueda.value.trim()) {
    const term = busqueda.value.toLowerCase();
    filtered = filtered.filter(v => 
      v.id.toString().includes(term) ||
      v.cliente?.nombre?.toLowerCase().includes(term)
    );
  }
  
  return filtered;
});

const ventasPendientes = computed(() => {
  return ventas.value.filter(v => v.estado === 'pendiente_pago' || v.estado === 'parcial');
});

const ventasHoy = computed(() => {
  const hoy = new Date().toISOString().split('T')[0];
  return ventas.value
    .filter(v => {
      const fecha = new Date(v.fecha_venta).toISOString().split('T')[0];
      return fecha === hoy && v.estado === 'completada';
    })
    .reduce((sum, v) => sum + Number(v.total || 0), 0);
});

const totalVentasHoy = computed(() => {
  const hoy = new Date().toISOString().split('T')[0];
  return ventas.value.filter(v => {
    const fecha = new Date(v.fecha_venta).toISOString().split('T')[0];
    return fecha === hoy;
  }).length;
});

function formatPrecio(precio: any): string {
  return (Number(precio) || 0).toFixed(2);
}

function formatFecha(fecha: string): string {
  if (!fecha) return '';
  const d = new Date(fecha);
  return d.toLocaleDateString('es-MX', {
    day: '2-digit',
    month: 'short',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  });
}

function formatFechaCompact(fecha: string): string {
  if (!fecha) return '';
  const d = new Date(fecha);
  const hoy = new Date();
  const ayer = new Date(hoy);
  ayer.setDate(ayer.getDate() - 1);
  
  const esFechaHoy = d.toDateString() === hoy.toDateString();
  const esFechaAyer = d.toDateString() === ayer.toDateString();
  
  if (esFechaHoy) {
    return `Hoy ${d.toLocaleTimeString('es-MX', { hour: '2-digit', minute: '2-digit' })}`;
  } else if (esFechaAyer) {
    return `Ayer ${d.toLocaleTimeString('es-MX', { hour: '2-digit', minute: '2-digit' })}`;
  } else {
    return d.toLocaleDateString('es-MX', { day: 'numeric', month: 'short' });
  }
}

function getEstadoClass(estado: string): string {
  if (estado === 'completada') return 'completada';
  if (estado === 'pendiente_pago') return 'pendiente';
  if (estado === 'parcial') return 'parcial';
  if (estado === 'cancelada') return 'cancelada';
  return '';
}

function getEstadoLabel(estado: string): string {
  if (estado === 'completada') return 'Completada';
  if (estado === 'pendiente_pago') return 'Pendiente';
  if (estado === 'parcial') return 'Pago parcial';
  if (estado === 'cancelada') return 'Cancelada';
  return estado;
}

async function cargarDatos() {
  loading.value = true;
  try {
    const res = await getVentasByUser();
    if (res.success) {
      ventas.value = res.data || [];
    }
  } catch (error) {
    console.error('Error cargando ventas:', error);
  } finally {
    loading.value = false;
  }
}

function nuevaVenta() {
  preselectedItems.value = [];
  preselectedClient.value = null;
  activeCitasIds.value = [];
  showNuevaVentaModal.value = true;
}



function abrirPago(venta: any) {
  activeVenta.value = venta;
  showPagoModal.value = true;
}

function verDetalle(venta: any) {
  detalleVenta.value = venta;
  showDetalleModal.value = true;
}

function onVentaCreada(venta: any) {
  cargarDatos();
  abrirPago(venta);
}

function closeDetalleModal() {
  showDetalleModal.value = false;
  detalleVenta.value = null;
}

const route = useRoute();

// Abrir venta específica si viene en query params
watch(() => route.query.venta_id, async (ventaId) => {
  if (ventaId && ventas.value.length > 0) {
    const venta = ventas.value.find(v => v.id === Number(ventaId));
    if (venta) {
      verDetalle(venta);
    }
  }
}, { immediate: true });

onMounted(async () => {
  await cargarDatos();
  
  // Después de cargar, verificar si hay venta_id en query
  if (route.query.venta_id) {
    const ventaId = Number(route.query.venta_id);
    const venta = ventas.value.find(v => v.id === ventaId);
    if (venta) {
      verDetalle(venta);
    }
  }
});
</script>

<style scoped>
/* ===== Apple Minimalist Design - Ventas ===== */

.ventas-view {
  min-height: 100vh;
  background: #f5f5f7;
  padding: 0;
  padding-bottom: 100px;
  font-family: -apple-system, BlinkMacSystemFont, 'SF Pro Display', 'SF Pro Text', 'Helvetica Neue', sans-serif;
  -webkit-font-smoothing: antialiased;
  -moz-osx-font-smoothing: grayscale;
  letter-spacing: -0.01em;
}

/* ===== Header ===== */
.servicios-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 20px;
  margin: 16px 20px;
  background: linear-gradient(135deg, #1d1d1f 0%, #3a3a3c 100%);
  border-radius: 20px;
  box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
}

.header-left {
  display: flex;
  align-items: center;
  gap: 14px;
}

.header-icon {
  width: 48px;
  height: 48px;
  background: rgba(255, 255, 255, 0.2);
  border-radius: 14px;
  display: flex;
  align-items: center;
  justify-content: center;
  color: #ffffff;
  backdrop-filter: blur(10px);
}

.header-text h1 {
  font-size: 22px;
  font-weight: 700;
  margin: 0;
  color: #ffffff;
  letter-spacing: -0.02em;
}

.header-subtitle {
  font-size: 13px;
  color: rgba(255, 255, 255, 0.85);
  margin: 4px 0 0;
  font-weight: 400;
}

.btn-new-servicio {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 6px;
  padding: 12px 20px;
  background: rgba(255, 255, 255, 0.15);
  border: 1px solid rgba(255, 255, 255, 0.2);
  border-radius: 12px;
  color: #ffffff;
  font-size: 14px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.3s ease;
  white-space: nowrap;
  backdrop-filter: blur(10px);
}

.btn-new-servicio:active {
  transform: scale(0.96);
  background: rgba(255, 255, 255, 0.25);
}

@media (max-width: 480px) {
  .btn-new-servicio {
    padding: 12px 16px;
    font-size: 13px;
  }
}

/* ===== Stats ===== */
.stats-row {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 12px;
  padding: 0 20px 16px;
  background: transparent;
}

.stat-card {
  background: #ffffff;
  padding: 16px;
  border-radius: 14px;
  text-align: center;
  transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
  cursor: pointer;
  box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
}

.stat-card:active {
  transform: scale(0.96);
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
}

.stat-value {
  font-size: 22px;
  font-weight: 700;
  color: #000000;
  letter-spacing: -0.02em;
  margin-bottom: 4px;
}

.stat-value.accent {
  color: #007aff;
}

.stat-label {
  font-size: 11px;
  font-weight: 500;
  color: #86868b;
  text-transform: uppercase;
  letter-spacing: 0.06em;
}

/* ===== Tabs ===== */
.view-tabs {
  display: flex;
  gap: 8px;
  padding: 0 20px 16px;
  background: transparent;
}

.view-tab {
  flex: 1;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  padding: 12px 18px;
  background: white;
  border: 2px solid transparent;
  border-radius: 14px;
  font-size: 14px;
  font-weight: 600;
  color: #86868b;
  cursor: pointer;
  transition: all 0.2s ease;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
}

.view-tab.active {
  background: #007aff;
  color: white;
  box-shadow: 0 2px 8px rgba(0, 122, 255, 0.3);
}

.view-tab svg {
  flex-shrink: 0;
}

.badge {
  background: white;
  color: #007aff;
  padding: 2px 8px;
  border-radius: 10px;
  font-size: 12px;
  font-weight: 700;
  margin-left: 4px;
}

.view-tab.active .badge {
  background: white;
  color: #007aff;
}

/* ===== Search ===== */
.search-section {
  padding: 0 20px 16px;
  background: transparent;
}

.search-input {
  display: flex;
  align-items: center;
  gap: 12px;
  background: #ffffff;
  border-radius: 14px;
  padding: 12px 16px;
  transition: all 0.2s ease;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
  border: 2px solid transparent;
}

.search-input:focus-within {
  border-color: #007aff;
  box-shadow: 0 2px 12px rgba(0, 122, 255, 0.15);
}

.search-input svg {
  color: #86868b;
  flex-shrink: 0;
}

.search-input input {
  flex: 1;
  border: none;
  background: none;
  font-size: 16px;
  color: #000000;
  outline: none;
  font-weight: 400;
}

.search-input input::placeholder {
  color: #86868b;
}

/* ===== Loading ===== */
.loading-container {
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 100px 20px;
}

.loading-spinner {
  width: 32px;
  height: 32px;
  border: 2px solid #e5e5ea;
  border-top-color: #000000;
  border-radius: 50%;
  animation: spin 0.8s linear infinite;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}

/* ===== Empty State ===== */
.empty-container {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 80px 40px;
  text-align: center;
}

.empty-icon {
  color: #d1d1d6;
  margin-bottom: 20px;
}

.empty-container h3 {
  font-size: 20px;
  font-weight: 600;
  color: #000000;
  margin: 0 0 8px;
  letter-spacing: -0.02em;
}

.empty-container p {
  font-size: 15px;
  color: #86868b;
  margin: 0 0 24px;
  font-weight: 400;
}

.btn-create {
  padding: 12px 32px;
  background: #000000;
  border: none;
  border-radius: 24px;
  color: #ffffff;
  font-size: 15px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
}

.btn-create:active {
  transform: scale(0.96);
  background: #1c1c1e;
}

/* ===== Sales List ===== */
.sales-list {
  padding: 0 20px 20px;
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.sale-card {
  background: #ffffff;
  border-radius: 16px;
  padding: 20px;
  cursor: pointer;
  transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
  border: 2px solid transparent;
}

.sale-card:active {
  transform: scale(0.98);
  box-shadow: 0 2px 12px rgba(0, 0, 0, 0.08);
  border-color: #007aff;
}

.sale-main {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  margin-bottom: 16px;
}

.sale-info {
  flex: 1;
}

.sale-number {
  font-size: 17px;
  font-weight: 700;
  color: #000000;
  margin-bottom: 6px;
  letter-spacing: -0.02em;
}

.sale-meta {
  font-size: 15px;
  color: #000000;
  margin-bottom: 4px;
  font-weight: 400;
}

.meta-empty {
  color: #86868b;
}

.sale-date {
  font-size: 13px;
  color: #86868b;
  font-weight: 400;
}

.sale-amount {
  text-align: right;
  flex-shrink: 0;
}

.amount-total {
  font-size: 22px;
  font-weight: 700;
  color: #000000;
  margin-bottom: 6px;
  letter-spacing: -0.02em;
}

.sale-status {
  font-size: 11px;
  font-weight: 600;
  padding: 4px 10px;
  border-radius: 6px;
  display: inline-block;
  letter-spacing: 0.02em;
  text-transform: uppercase;
}

.sale-status.completada {
  background: rgba(52, 199, 89, 0.12);
  color: #34c759;
}

.sale-status.pendiente {
  background: rgba(255, 149, 0, 0.12);
  color: #ff9500;
}

.sale-status.parcial {
  background: rgba(88, 86, 214, 0.12);
  color: #5856d6;
}

.sale-status.cancelada {
  background: rgba(255, 59, 48, 0.12);
  color: #ff3b30;
}

.sale-pending {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 12px;
  background: rgba(255, 149, 0, 0.08);
  border-radius: 10px;
  margin-bottom: 12px;
}

.pending-label {
  font-size: 13px;
  font-weight: 500;
  color: #ff9500;
}

.pending-amount {
  font-size: 16px;
  font-weight: 700;
  color: #ff9500;
  letter-spacing: -0.01em;
}

.sale-actions {
  display: flex;
  gap: 8px;
}

.action-pay {
  flex: 1;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  padding: 12px 20px;
  background: #000000;
  border: none;
  border-radius: 12px;
  color: #ffffff;
  font-size: 15px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
}

.action-pay:active {
  transform: scale(0.96);
  background: #1c1c1e;
}

/* ===== Modals ===== */
.modal-overlay {
  position: fixed;
  inset: 0;
  background: rgba(0, 0, 0, 0.4);
  backdrop-filter: blur(10px);
  display: flex;
  align-items: flex-end;
  justify-content: center;
  z-index: 1000;
  animation: fadeIn 0.25s cubic-bezier(0.4, 0, 0.2, 1);
}

@keyframes fadeIn {
  from { 
    opacity: 0;
  }
  to { 
    opacity: 1;
  }
}

.modal-content {
  background: #ffffff;
  width: 100%;
  max-width: 100%;
  border-radius: 20px 20px 0 0;
  overflow: hidden;
  animation: slideUp 0.35s cubic-bezier(0.4, 0, 0.2, 1);
}

.modal-content.modal-fullscreen {
  max-height: 95vh;
  height: 95vh;
}

.modal-content.modal-medium {
  max-height: 90vh;
}

@keyframes slideUp {
  from { 
    transform: translateY(100%);
    opacity: 0;
  }
  to { 
    transform: translateY(0);
    opacity: 1;
  }
}

.modal-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 24px 20px;
  background: #ffffff;
  border-bottom: 0.5px solid rgba(0, 0, 0, 0.06);
}

.modal-header h3 {
  margin: 0;
  font-size: 22px;
  font-weight: 700;
  color: #000000;
  letter-spacing: -0.02em;
}

.modal-close {
  width: 32px;
  height: 32px;
  border: none;
  border-radius: 50%;
  background: #f5f5f7;
  color: #86868b;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
}

.modal-close:active {
  transform: scale(0.9);
  background: #ebebed;
}

.modal-body {
  padding: 20px;
  overflow-y: auto;
  max-height: calc(90vh - 80px);
}

/* ===== POS Layout ===== */
.pos-layout {
  display: flex;
  flex-direction: column;
  height: calc(95vh - 80px);
  max-height: calc(95vh - 80px);
  overflow: hidden;
  padding: 0;
}

.pos-productos {
  flex: 1;
  display: flex;
  flex-direction: column;
  padding: 20px;
  overflow: hidden;
  background: #fafafa;
}

.pos-search {
  display: flex;
  align-items: center;
  gap: 12px;
  background: #ffffff;
  border-radius: 12px;
  padding: 12px 16px;
  margin-bottom: 16px;
  transition: all 0.2s ease;
  border: 0.5px solid rgba(0, 0, 0, 0.06);
}

.pos-search:focus-within {
  border-color: #000000;
}

.pos-search svg {
  color: #86868b;
  flex-shrink: 0;
}

.pos-search input {
  flex: 1;
  border: none;
  background: none;
  font-size: 16px;
  color: #000000;
  outline: none;
}

.pos-search input::placeholder {
  color: #86868b;
}

.productos-scroll {
  display: flex;
  gap: 12px;
  overflow-x: auto;
  overflow-y: hidden;
  padding: 4px 0 16px;
  -webkit-overflow-scrolling: touch;
  scroll-snap-type: x mandatory;
}

.productos-scroll::-webkit-scrollbar {
  height: 4px;
}

.productos-scroll::-webkit-scrollbar-track {
  background: #f5f5f7;
  border-radius: 2px;
}

.productos-scroll::-webkit-scrollbar-thumb {
  background: #d1d1d6;
  border-radius: 2px;
}

.producto-card {
  min-width: 180px;
  max-width: 180px;
  background: #ffffff;
  border-radius: 16px;
  padding: 18px;
  cursor: pointer;
  transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
  border: 1px solid rgba(0, 0, 0, 0.06);
  scroll-snap-align: start;
  flex-shrink: 0;
}

.producto-card:active {
  transform: scale(0.96);
  border-color: #007aff;
  box-shadow: 0 2px 8px rgba(0, 122, 255, 0.15);
}

.product-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 12px;
}

.product-code {
  font-size: 12px;
  font-weight: 700;
  color: #007aff;
  text-transform: uppercase;
  letter-spacing: 0.05em;
}

.product-stock {
  font-size: 13px;
  font-weight: 600;
  color: #34c759;
  background: rgba(52, 199, 89, 0.1);
  padding: 4px 10px;
  border-radius: 8px;
}

.product-stock.low {
  color: #ff9500;
  background: rgba(255, 149, 0, 0.1);
}

.product-name {
  font-size: 16px;
  font-weight: 600;
  color: #000000;
  margin: 0 0 12px;
  line-height: 1.3;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
  text-overflow: ellipsis;
  min-height: 42px;
}

.product-price {
  font-size: 22px;
  font-weight: 700;
  color: #000000;
  letter-spacing: -0.02em;
}

/* ===== Carrito ===== */
.pos-carrito {
  background: #ffffff;
  border-top: 0.5px solid rgba(0, 0, 0, 0.06);
  display: flex;
  flex-direction: column;
  max-height: 45vh;
}

.carrito-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 16px 20px;
  border-bottom: 0.5px solid rgba(0, 0, 0, 0.06);
}

.carrito-header h4 {
  margin: 0;
  display: flex;
  align-items: center;
  gap: 8px;
  font-size: 17px;
  font-weight: 700;
  color: #000000;
  letter-spacing: -0.02em;
}

.carrito-count {
  font-size: 13px;
  font-weight: 500;
  color: #86868b;
}

.carrito-items {
  flex: 1;
  overflow-y: auto;
  padding: 12px 20px;
}

.carrito-item {
  display: grid;
  grid-template-columns: 1fr auto auto auto;
  align-items: center;
  gap: 12px;
  padding: 12px 0;
  border-bottom: 0.5px solid rgba(0, 0, 0, 0.04);
}

.carrito-item:last-child {
  border-bottom: none;
}

.item-info {
  min-width: 0;
}

.item-nombre {
  display: block;
  font-size: 15px;
  font-weight: 600;
  color: #000000;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  margin-bottom: 2px;
}

.item-precio {
  font-size: 13px;
  color: #86868b;
  font-weight: 400;
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
  width: 28px;
  height: 28px;
  border: none;
  border-radius: 6px;
  background: transparent;
  color: #000000;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all 0.2s ease;
}

.qty-btn:active {
  background: rgba(0, 0, 0, 0.06);
}

.item-qty {
  min-width: 24px;
  text-align: center;
  font-weight: 600;
  font-size: 15px;
  color: #000000;
}

.item-subtotal {
  font-size: 16px;
  font-weight: 700;
  color: #000000;
  letter-spacing: -0.01em;
}

.item-remove {
  width: 32px;
  height: 32px;
  border: none;
  border-radius: 8px;
  background: rgba(255, 59, 48, 0.1);
  color: #ff3b30;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all 0.2s ease;
}

.item-remove:active {
  transform: scale(0.9);
}

.carrito-empty {
  flex: 1;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  color: #d1d1d6;
  gap: 12px;
  padding: 40px 20px;
}

.carrito-totales {
  padding: 16px 20px;
  border-top: 0.5px solid rgba(0, 0, 0, 0.06);
  background: #fafafa;
}

.total-line {
  display: flex;
  justify-content: space-between;
  font-size: 15px;
  color: #86868b;
  margin-bottom: 8px;
}

.total-line.total-final {
  font-size: 20px;
  font-weight: 700;
  color: #000000;
  margin-bottom: 0;
  margin-top: 4px;
  padding-top: 8px;
  border-top: 0.5px solid rgba(0, 0, 0, 0.06);
}

.carrito-actions {
  padding: 16px 20px 20px;
  background: #ffffff;
}

.btn-venta {
  width: 100%;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  padding: 16px;
  background: #000000;
  border: none;
  border-radius: 14px;
  color: #ffffff;
  font-size: 16px;
  font-weight: 700;
  cursor: pointer;
  transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
  letter-spacing: -0.01em;
}

.btn-venta:disabled {
  opacity: 0.4;
  cursor: not-allowed;
}

.btn-venta:not(:disabled):active {
  transform: scale(0.98);
  background: #1c1c1e;
}

/* ===== Pago Modal ===== */
.pago-info {
  text-align: center;
  padding: 20px;
  background: #fafafa;
  border-radius: 12px;
  margin-bottom: 24px;
}

.pago-venta-id {
  font-size: 15px;
  font-weight: 500;
  color: #86868b;
  margin-bottom: 16px;
}

.pago-totales {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.pt-row {
  display: flex;
  justify-content: space-between;
  font-size: 15px;
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
  font-weight: 700;
  letter-spacing: -0.02em;
}

.form-group {
  margin-bottom: 24px;
}

.form-label {
  display: block;
  font-size: 13px;
  font-weight: 600;
  color: #000000;
  margin-bottom: 12px;
  letter-spacing: -0.01em;
}

.metodos-grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 10px;
}

.metodo-btn {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 8px;
  padding: 16px;
  background: #fafafa;
  border: 1px solid transparent;
  border-radius: 12px;
  font-size: 14px;
  font-weight: 600;
  color: #86868b;
  cursor: pointer;
  transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
}

.metodo-btn:active {
  transform: scale(0.96);
}

.metodo-btn.active {
  background: #000000;
  border-color: #000000;
  color: #ffffff;
}

.monto-input-container {
  display: flex;
  align-items: center;
  background: #fafafa;
  border-radius: 12px;
  border: 1px solid transparent;
  transition: all 0.2s ease;
}

.monto-input-container:focus-within {
  border-color: #000000;
  background: #ffffff;
}

.monto-prefix {
  padding: 16px 20px;
  font-size: 24px;
  font-weight: 700;
  color: #86868b;
}

.monto-input {
  flex: 1;
  border: none;
  background: none;
  font-size: 28px;
  font-weight: 700;
  color: #000000;
  outline: none;
  padding: 16px 20px 16px 0;
  letter-spacing: -0.02em;
}

.monto-quick-btns {
  display: flex;
  gap: 8px;
  margin-top: 12px;
}

.monto-quick-btns button {
  padding: 10px 20px;
  background: #f5f5f7;
  border: none;
  border-radius: 10px;
  font-size: 13px;
  font-weight: 600;
  color: #000000;
  cursor: pointer;
  transition: all 0.2s ease;
}

.monto-quick-btns button:active {
  transform: scale(0.96);
  background: #ebebed;
}

.cambio-display {
  margin-top: 16px;
  padding: 16px;
  background: rgba(52, 199, 89, 0.1);
  border-radius: 12px;
  text-align: center;
  font-size: 15px;
  font-weight: 500;
  color: #34c759;
}

.cambio-display strong {
  font-size: 20px;
  font-weight: 700;
  display: block;
  margin-top: 4px;
  letter-spacing: -0.01em;
}

.form-actions {
  display: flex;
  gap: 12px;
  margin-top: 24px;
}

.btn-cancel,
.btn-submit {
  flex: 1;
  padding: 16px;
  border: none;
  border-radius: 14px;
  font-size: 16px;
  font-weight: 700;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
  letter-spacing: -0.01em;
}

.btn-cancel {
  background: #f5f5f7;
  color: #000000;
}

.btn-cancel:active {
  background: #ebebed;
}

.btn-submit {
  background: #000000;
  color: #ffffff;
}

.btn-submit:active {
  transform: scale(0.98);
  background: #1c1c1e;
}

.btn-submit:disabled {
  opacity: 0.4;
  cursor: not-allowed;
}

/* ===== Detalle Modal ===== */
.detalle-info {
  background: #fafafa;
  border-radius: 12px;
  padding: 20px;
  margin-bottom: 24px;
}

.di-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 8px 0;
  border-bottom: 0.5px solid rgba(0, 0, 0, 0.04);
}

.di-row:last-child {
  border-bottom: none;
}

.di-label {
  font-size: 15px;
  font-weight: 500;
  color: #86868b;
}

.di-value {
  font-size: 15px;
  font-weight: 600;
  color: #000000;
}

.detalle-items {
  margin-bottom: 24px;
}

.detalle-items h4 {
  font-size: 13px;
  font-weight: 600;
  color: #000000;
  margin: 0 0 12px;
  letter-spacing: -0.01em;
}

.items-list {
  background: #fafafa;
  border-radius: 12px;
  overflow: hidden;
}

.item-row {
  display: grid;
  grid-template-columns: 1fr auto auto auto;
  align-items: center;
  gap: 12px;
  padding: 16px;
  border-bottom: 0.5px solid rgba(0, 0, 0, 0.04);
}

.item-row:last-child {
  border-bottom: none;
}

.ir-info {
  min-width: 0;
}

.ir-nombre {
  display: block;
  font-size: 15px;
  font-weight: 600;
  color: #000000;
  margin-bottom: 2px;
}

.ir-tipo {
  font-size: 12px;
  color: #86868b;
  text-transform: capitalize;
  font-weight: 500;
}

.ir-qty {
  font-size: 14px;
  font-weight: 500;
  color: #86868b;
}

.ir-precio {
  font-size: 14px;
  color: #86868b;
  font-weight: 500;
}

.ir-subtotal {
  font-size: 16px;
  font-weight: 700;
  color: #000000;
  letter-spacing: -0.01em;
}

.detalle-totales {
  margin-top: 24px;
  padding: 20px;
  background: #fafafa;
  border-radius: 12px;
}

.dt-row {
  display: flex;
  justify-content: space-between;
  font-size: 15px;
  color: #86868b;
  margin-bottom: 10px;
  font-weight: 500;
}

.dt-row:last-child {
  margin-bottom: 0;
}

.dt-row.total {
  font-size: 20px;
  font-weight: 700;
  color: #000000;
  margin-top: 10px;
  padding-top: 10px;
  border-top: 0.5px solid rgba(0, 0, 0, 0.06);
  letter-spacing: -0.02em;
}

.dt-row.pagado span:last-child {
  color: #34c759;
  font-weight: 700;
}

.dt-row.pendiente span:last-child {
  color: #ff9500;
  font-weight: 700;
}

.detalle-actions {
  margin-top: 24px;
}

.btn-pagar-detalle {
  width: 100%;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  padding: 16px;
  background: #000000;
  border: none;
  border-radius: 14px;
  color: #ffffff;
  font-size: 16px;
  font-weight: 700;
  cursor: pointer;
  transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
  letter-spacing: -0.01em;
}

.btn-pagar-detalle:active {
  transform: scale(0.98);
  background: #1c1c1e;
}
</style>



