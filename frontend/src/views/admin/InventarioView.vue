<template>
  <div class="inventario-view">
    <!-- Header -->
    <div class="page-header">
      <div class="header-left">
        <div class="header-icon">
          <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
            <circle cx="12" cy="7" r="4"></circle>
          </svg>
        </div>
        <div class="header-text">
          <h1>Inventario</h1>
          <p class="header-subtitle">Control de stock y movimientos</p>
        </div>
      </div>
    </div>

    <!-- Quick Stats -->
    <div class="stats-grid">
      <div class="stat-card">
        <div class="stat-icon productos">
          <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"></path>
            <line x1="3" y1="6" x2="21" y2="6"></line>
            <path d="M16 10a4 4 0 0 1-8 0"></path>
          </svg>
        </div>
        <div class="stat-info">
          <span class="stat-value">{{ totalProductos }}</span>
          <span class="stat-label">Productos</span>
        </div>
      </div>
      
      <div class="stat-card warning" v-if="productosBajoStock.length > 0">
        <div class="stat-icon warning">
          <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path>
            <line x1="12" y1="9" x2="12" y2="13"></line>
            <line x1="12" y1="17" x2="12.01" y2="17"></line>
          </svg>
        </div>
        <div class="stat-info">
          <span class="stat-value">{{ productosBajoStock.length }}</span>
          <span class="stat-label">Stock bajo</span>
        </div>
      </div>
      
      <div class="stat-card">
        <div class="stat-icon movimientos">
          <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <polyline points="23 4 23 10 17 10"></polyline>
            <polyline points="1 20 1 14 7 14"></polyline>
            <path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15"></path>
          </svg>
        </div>
        <div class="stat-info">
          <span class="stat-value">{{ movimientosHoy }}</span>
          <span class="stat-label">Movimientos hoy</span>
        </div>
      </div>
    </div>

    <!-- Tabs -->
    <div class="view-tabs">
      <button 
        :class="['view-tab', { active: vistaActiva === 'movimientos' }]"
        @click="vistaActiva = 'movimientos'"
      >
        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <polyline points="23 4 23 10 17 10"></polyline>
          <path d="M20.49 15a9 9 0 1 1-2.12-9.36L23 10"></path>
        </svg>
        Movimientos
      </button>
      <button 
        :class="['view-tab', { active: vistaActiva === 'kardex' }]"
        @click="vistaActiva = 'kardex'"
      >
        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
          <polyline points="14 2 14 8 20 8"></polyline>
          <line x1="16" y1="13" x2="8" y2="13"></line>
          <line x1="16" y1="17" x2="8" y2="17"></line>
        </svg>
        Kardex
      </button>
      <button 
        :class="['view-tab', { active: vistaActiva === 'bajo-stock' }]"
        @click="vistaActiva = 'bajo-stock'"
      >
        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path>
          <line x1="12" y1="9" x2="12" y2="13"></line>
          <line x1="12" y1="17" x2="12.01" y2="17"></line>
        </svg>
        Bajo Stock
      </button>
    </div>

    <!-- Vista Movimientos -->
    <div v-if="vistaActiva === 'movimientos'" class="content-section">
      <!-- Filters -->
      <div class="filters-row">
        <div class="filter-group">
          <select v-model="filtroTipo" class="filter-select">
            <option value="">Todos los tipos</option>
            <option value="entrada_manual">Entradas</option>
            <option value="salida_manual">Salidas</option>
            <option value="venta">Ventas</option>
          </select>
        </div>
        <div class="filter-group">
          <input 
            type="date" 
            v-model="filtroFechaInicio" 
            class="filter-input"
            placeholder="Desde"
          />
        </div>
        <div class="filter-group">
          <input 
            type="date" 
            v-model="filtroFechaFin" 
            class="filter-input"
            placeholder="Hasta"
          />
        </div>
        <button class="filter-btn" @click="cargarMovimientos">
          <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <circle cx="11" cy="11" r="8"></circle>
            <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
          </svg>
          Filtrar
        </button>
      </div>

      <!-- Movimientos List -->
      <div v-if="loadingMovimientos" class="loading-state">
        <div class="loader"></div>
        <p>Cargando movimientos...</p>
      </div>
      
      <div v-else-if="movimientos.length === 0" class="empty-state">
        <div class="empty-icon">
          <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
            <polyline points="23 4 23 10 17 10"></polyline>
            <path d="M20.49 15a9 9 0 1 1-2.12-9.36L23 10"></path>
          </svg>
        </div>
        <p>No hay movimientos registrados</p>
      </div>
      
      <div v-else class="movimientos-list">
        <div 
          v-for="mov in movimientos" 
          :key="mov.id" 
          class="movimiento-card"
          :class="getTipoClass(mov.tipo)"
        >
          <div class="mov-icon" :class="getTipoClass(mov.tipo)">
            <svg v-if="mov.tipo === 'entrada_manual'" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <line x1="12" y1="5" x2="12" y2="19"></line>
              <polyline points="19 12 12 19 5 12"></polyline>
            </svg>
            <svg v-else-if="mov.tipo === 'salida_manual'" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <line x1="12" y1="19" x2="12" y2="5"></line>
              <polyline points="5 12 12 5 19 12"></polyline>
            </svg>
            <svg v-else xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <circle cx="9" cy="21" r="1"></circle>
              <circle cx="20" cy="21" r="1"></circle>
              <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path>
            </svg>
          </div>
          
          <div class="mov-content">
            <div class="mov-header">
              <span class="mov-producto">{{ mov.producto?.nombre || 'Producto desconocido' }}</span>
              <span :class="['mov-cantidad', getTipoClass(mov.tipo)]">
                {{ mov.tipo === 'entrada_manual' ? '+' : '-' }}{{ Math.abs(mov.cantidad) }}
              </span>
            </div>
            <div class="mov-details">
              <span class="mov-tipo">{{ getTipoLabel(mov.tipo) }}</span>
              <span class="mov-separator">•</span>
              <span class="mov-fecha">{{ formatFecha(mov.created_at) }}</span>
            </div>
            <div class="mov-motivo" v-if="mov.motivo">{{ mov.motivo }}</div>
          </div>
        </div>
      </div>
    </div>

    <!-- Vista Kardex -->
    <div v-if="vistaActiva === 'kardex'" class="content-section">
      <!-- Product Selector -->
      <div class="kardex-selector">
        <label class="selector-label">Seleccionar producto:</label>
        <select v-model="kardexProductoId" class="filter-select large" @change="cargarKardex">
          <option :value="null">-- Selecciona un producto --</option>
          <option v-for="p in productos" :key="p.id" :value="p.id">
            {{ p.codigo }} - {{ p.nombre }}
          </option>
        </select>
      </div>

      <!-- Kardex Info -->
      <div v-if="kardexProducto" class="kardex-info">
        <div class="kardex-producto">
          <h3>{{ kardexProducto.nombre }}</h3>
          <span class="kardex-codigo">{{ kardexProducto.codigo }}</span>
        </div>
        <div class="kardex-stats">
          <div class="kardex-stat">
            <span class="ks-value">{{ kardexProducto.inventario_actual || 0 }}</span>
            <span class="ks-label">Stock Actual</span>
          </div>
          <div class="kardex-stat">
            <span class="ks-value">{{ kardexProducto.inventario_minimo || 0 }}</span>
            <span class="ks-label">Stock Mínimo</span>
          </div>
        </div>
      </div>

      <!-- Kardex Table -->
      <div v-if="loadingKardex" class="loading-state">
        <div class="loader"></div>
        <p>Cargando kardex...</p>
      </div>
      
      <div v-else-if="kardexProductoId && kardexMovimientos.length === 0" class="empty-state">
        <p>No hay movimientos para este producto</p>
      </div>
      
      <div v-else-if="kardexMovimientos.length > 0" class="kardex-table-container">
        <table class="kardex-table">
          <thead>
            <tr>
              <th>Fecha</th>
              <th>Tipo</th>
              <th class="text-right">Entrada</th>
              <th class="text-right">Salida</th>
              <th class="text-right">Saldo</th>
              <th>Motivo</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="(k, index) in kardexMovimientos" :key="index">
              <td class="fecha-cell">{{ formatFechaCorta(k.fecha) }}</td>
              <td>
                <span :class="['tipo-badge', getTipoClass(k.tipo)]">
                  {{ getTipoLabel(k.tipo) }}
                </span>
              </td>
              <td class="text-right entrada">{{ k.entrada > 0 ? '+' + k.entrada : '' }}</td>
              <td class="text-right salida">{{ k.salida > 0 ? '-' + k.salida : '' }}</td>
              <td class="text-right saldo">{{ k.saldo }}</td>
              <td class="motivo-cell">{{ k.motivo || '-' }}</td>
            </tr>
          </tbody>
        </table>
      </div>
      
      <div v-else class="empty-state">
        <p>Selecciona un producto para ver su kardex</p>
      </div>
    </div>

    <!-- Vista Bajo Stock -->
    <div v-if="vistaActiva === 'bajo-stock'" class="content-section">
      <div v-if="loadingProductos" class="loading-state">
        <div class="loader"></div>
        <p>Cargando productos...</p>
      </div>
      
      <div v-else-if="productosBajoStock.length === 0" class="empty-state success">
        <div class="empty-icon success">
          <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
            <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
            <polyline points="22 4 12 14.01 9 11.01"></polyline>
          </svg>
        </div>
        <p>¡Todos los productos tienen stock suficiente!</p>
      </div>
      
      <div v-else class="bajo-stock-list">
        <div 
          v-for="p in productosBajoStock" 
          :key="p.id" 
          class="bajo-stock-card"
        >
          <div class="bs-icon">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path>
              <line x1="12" y1="9" x2="12" y2="13"></line>
              <line x1="12" y1="17" x2="12.01" y2="17"></line>
            </svg>
          </div>
          
          <div class="bs-content">
            <div class="bs-header">
              <span class="bs-codigo">{{ p.codigo }}</span>
              <span class="bs-nombre">{{ p.nombre }}</span>
            </div>
            <div class="bs-stock-info">
              <div class="bs-stock">
                <span class="bs-label">Stock actual:</span>
                <span class="bs-value danger">{{ p.inventario_actual || 0 }}</span>
              </div>
              <div class="bs-stock">
                <span class="bs-label">Stock mínimo:</span>
                <span class="bs-value">{{ p.inventario_minimo || 0 }}</span>
              </div>
              <div class="bs-stock">
                <span class="bs-label">Faltante:</span>
                <span class="bs-value warning">{{ (p.inventario_minimo || 0) - (p.inventario_actual || 0) }}</span>
              </div>
            </div>
          </div>
          
          <button class="bs-action" @click="abrirEntradaRapida(p)">
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <line x1="12" y1="5" x2="12" y2="19"></line>
              <line x1="5" y1="12" x2="19" y2="12"></line>
            </svg>
            Agregar
          </button>
        </div>
      </div>
    </div>

    <!-- Modal Entrada Rápida -->
    <Teleport to="body">
      <div v-if="showEntradaModal" class="modal-overlay" @click.self="closeEntradaModal">
        <div class="modal-content modal-small">
          <div class="modal-header">
            <h3>Entrada de Stock</h3>
            <button class="modal-close" @click="closeEntradaModal">
              <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <line x1="18" y1="6" x2="6" y2="18"></line>
                <line x1="6" y1="6" x2="18" y2="18"></line>
              </svg>
            </button>
          </div>
          <div class="modal-body">
            <div class="entrada-producto-info" v-if="entradaProducto">
              <div class="ep-nombre">{{ entradaProducto.nombre }}</div>
              <div class="ep-stock">
                Stock actual: <strong>{{ entradaProducto.inventario_actual || 0 }}</strong>
              </div>
            </div>
            
            <div class="form-group">
              <label class="form-label">Cantidad a agregar</label>
              <input 
                v-model.number="entradaCantidad" 
                type="number" 
                min="1"
                placeholder="0"
                class="form-input entrada-input"
              />
            </div>
            
            <div class="form-group">
              <label class="form-label">Motivo (opcional)</label>
              <input 
                v-model="entradaMotivo" 
                type="text" 
                placeholder="Ej: Compra de proveedor..."
                class="form-input"
              />
            </div>
            
            <div class="form-actions">
              <button type="button" class="btn-cancel" @click="closeEntradaModal">
                Cancelar
              </button>
              <button 
                type="button" 
                class="btn-submit entrada"
                @click="ejecutarEntrada"
                :disabled="!entradaCantidad || entradaCantidad < 1"
              >
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <line x1="12" y1="5" x2="12" y2="19"></line>
                  <polyline points="19 12 12 19 5 12"></polyline>
                </svg>
                Registrar Entrada
              </button>
            </div>
          </div>
        </div>
      </div>
    </Teleport>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue';
import { 
  getProductos,
  getMovimientos,
  getKardex,
  registrarEntrada
} from '@/services/inventarioService';
import Swal from 'sweetalert2';

const vistaActiva = ref<'movimientos' | 'kardex' | 'bajo-stock'>('movimientos');
const productos = ref<any[]>([]);
const movimientos = ref<any[]>([]);
const kardexMovimientos = ref<any[]>([]);
const kardexProductoId = ref<number | null>(null);
const kardexProducto = ref<any>(null);

const loadingProductos = ref(true);
const loadingMovimientos = ref(false);
const loadingKardex = ref(false);

const filtroTipo = ref('');
const filtroFechaInicio = ref('');
const filtroFechaFin = ref('');

const showEntradaModal = ref(false);
const entradaProducto = ref<any>(null);
const entradaCantidad = ref<number>(0);
const entradaMotivo = ref('');

const totalProductos = computed(() => productos.value.length);

const productosBajoStock = computed(() => {
  return productos.value.filter(p => {
    const actual = p.inventario_actual || 0;
    const minimo = p.inventario_minimo || 0;
    return actual <= minimo && minimo > 0;
  });
});

const movimientosHoy = computed(() => {
  const hoy = new Date().toISOString().split('T')[0];
  return movimientos.value.filter(m => {
    const fecha = new Date(m.created_at).toISOString().split('T')[0];
    return fecha === hoy;
  }).length;
});

function getTipoClass(tipo: string): string {
  if (tipo === 'entrada_manual') return 'entrada';
  if (tipo === 'salida_manual') return 'salida';
  if (tipo === 'venta') return 'venta';
  return '';
}

function getTipoLabel(tipo: string): string {
  if (tipo === 'entrada_manual') return 'Entrada';
  if (tipo === 'salida_manual') return 'Salida';
  if (tipo === 'venta') return 'Venta';
  return tipo;
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

function formatFechaCorta(fecha: string): string {
  if (!fecha) return '';
  const d = new Date(fecha);
  return d.toLocaleDateString('es-MX', {
    day: '2-digit',
    month: 'short'
  });
}

async function cargarProductos() {
  loadingProductos.value = true;
  try {
    const response = await getProductos();
    if (response.success) {
      productos.value = response.data || [];
    }
  } catch (error) {
    console.error('Error cargando productos:', error);
  } finally {
    loadingProductos.value = false;
  }
}

async function cargarMovimientos() {
  loadingMovimientos.value = true;
  try {
    const filters: any = {};
    if (filtroTipo.value) filters.tipo = filtroTipo.value;
    if (filtroFechaInicio.value) filters.fecha_inicio = filtroFechaInicio.value;
    if (filtroFechaFin.value) filters.fecha_fin = filtroFechaFin.value;
    
    const response = await getMovimientos(filters);
    if (response.success) {
      movimientos.value = response.data || [];
    }
  } catch (error) {
    console.error('Error cargando movimientos:', error);
  } finally {
    loadingMovimientos.value = false;
  }
}

async function cargarKardex() {
  if (!kardexProductoId.value) {
    kardexMovimientos.value = [];
    kardexProducto.value = null;
    return;
  }
  
  loadingKardex.value = true;
  try {
    kardexProducto.value = productos.value.find(p => p.id === kardexProductoId.value);
    
    const response = await getKardex(kardexProductoId.value);
    if (response.success) {
      // Procesar kardex para mostrar entradas/salidas/saldo
      const data = response.data || [];
      let saldo = 0;
      kardexMovimientos.value = data.map((m: any) => {
        const cantidad = m.cantidad || 0;
        const entrada = cantidad > 0 ? cantidad : 0;
        const salida = cantidad < 0 ? Math.abs(cantidad) : 0;
        saldo += cantidad;
        return {
          ...m,
          fecha: m.created_at,
          entrada,
          salida,
          saldo
        };
      }).reverse();
    }
  } catch (error) {
    console.error('Error cargando kardex:', error);
  } finally {
    loadingKardex.value = false;
  }
}

function abrirEntradaRapida(producto: any) {
  entradaProducto.value = producto;
  entradaCantidad.value = 0;
  entradaMotivo.value = '';
  showEntradaModal.value = true;
}

async function ejecutarEntrada() {
  if (!entradaProducto.value || !entradaCantidad.value) return;
  
  try {
    await registrarEntrada({
      producto_id: entradaProducto.value.id,
      cantidad: entradaCantidad.value,
      motivo: entradaMotivo.value || 'Entrada manual',
    });
    
    Swal.fire({
      icon: 'success',
      title: 'Entrada registrada',
      toast: true,
      position: 'top-end',
      showConfirmButton: false,
      timer: 2000
    });
    
    closeEntradaModal();
    await Promise.all([cargarProductos(), cargarMovimientos()]);
  } catch (error: any) {
    console.error('Error registrando entrada:', error);
    Swal.fire({
      icon: 'error',
      title: 'Error',
      text: error.response?.data?.message || 'Error al registrar entrada',
      confirmButtonColor: '#43e97b'
    });
  }
}

function closeEntradaModal() {
  showEntradaModal.value = false;
  entradaProducto.value = null;
}

onMounted(async () => {
  await Promise.all([cargarProductos(), cargarMovimientos()]);
});
</script>

<style scoped>
/* ===== Modern Green Design - Inventario ===== */

.inventario-view {
  min-height: 100vh;
  background: linear-gradient(135deg, #f0fff4 0%, #e8f5e9 100%);
  padding: 20px;
  padding-bottom: 100px;
  font-family: -apple-system, BlinkMacSystemFont, 'SF Pro Display', 'Segoe UI', Roboto, sans-serif;
  -webkit-font-smoothing: antialiased;
}

/* Header */
.page-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 20px;
  padding: 20px;
  background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);
  border-radius: 20px;
  color: white;
  box-shadow: 0 10px 40px rgba(67, 233, 123, 0.3);
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
  backdrop-filter: blur(10px);
}

.header-text h1 {
  font-size: 22px;
  font-weight: 700;
  margin: 0;
}

.header-subtitle {
  font-size: 13px;
  opacity: 0.85;
  margin: 4px 0 0;
}

/* Stats Grid */
.stats-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
  gap: 12px;
  margin-bottom: 20px;
}

.stat-card {
  display: flex;
  align-items: center;
  gap: 12px;
  background: white;
  padding: 16px;
  border-radius: 16px;
  box-shadow: 0 4px 15px rgba(0, 0, 0, 0.06);
}

.stat-card.warning {
  background: linear-gradient(135deg, #fff3cd 0%, #ffe8a1 100%);
}

.stat-icon {
  width: 44px;
  height: 44px;
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
}

.stat-icon.productos {
  background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);
}

.stat-icon.warning {
  background: linear-gradient(135deg, #ff9500 0%, #ff6b00 100%);
}

.stat-icon.movimientos {
  background: linear-gradient(135deg, #5856d6 0%, #af52de 100%);
}

.stat-info {
  display: flex;
  flex-direction: column;
}

.stat-value {
  font-size: 24px;
  font-weight: 700;
  color: #1d1d1f;
}

.stat-label {
  font-size: 12px;
  color: #86868b;
}

/* View Tabs */
.view-tabs {
  display: flex;
  gap: 8px;
  margin-bottom: 20px;
  overflow-x: auto;
  padding-bottom: 4px;
}

.view-tab {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 12px 18px;
  background: white;
  border: 2px solid transparent;
  border-radius: 14px;
  font-size: 14px;
  font-weight: 600;
  color: #86868b;
  cursor: pointer;
  white-space: nowrap;
  transition: all 0.2s ease;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
}

.view-tab.active {
  background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);
  color: white;
  box-shadow: 0 4px 15px rgba(67, 233, 123, 0.4);
}

/* Filters */
.filters-row {
  display: flex;
  flex-wrap: wrap;
  gap: 10px;
  margin-bottom: 16px;
}

.filter-group {
  flex: 1;
  min-width: 120px;
}

.filter-select,
.filter-input {
  width: 100%;
  padding: 10px 14px;
  border: 2px solid #e5e5ea;
  border-radius: 12px;
  font-size: 14px;
  color: #1d1d1f;
  background: white;
  transition: all 0.2s ease;
}

.filter-select:focus,
.filter-input:focus {
  outline: none;
  border-color: #43e97b;
}

.filter-select.large {
  padding: 14px 16px;
  font-size: 15px;
}

.filter-btn {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 10px 18px;
  background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);
  border: none;
  border-radius: 12px;
  color: white;
  font-size: 14px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s ease;
}

.filter-btn:active {
  transform: scale(0.98);
}

/* Content */
.content-section {
  background: white;
  border-radius: 20px;
  padding: 20px;
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.06);
}

.loading-state,
.empty-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 40px 20px;
  text-align: center;
}

.loader {
  width: 36px;
  height: 36px;
  border: 3px solid #e5e5ea;
  border-top-color: #43e97b;
  border-radius: 50%;
  animation: spin 0.8s linear infinite;
  margin-bottom: 16px;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}

.empty-icon {
  color: #c7c7cc;
  margin-bottom: 16px;
}

.empty-icon.success {
  color: #34c759;
}

.empty-state p {
  color: #86868b;
  font-size: 15px;
  margin: 0;
}

.empty-state.success p {
  color: #34c759;
}

/* Movimientos List */
.movimientos-list {
  display: flex;
  flex-direction: column;
  gap: 10px;
}

.movimiento-card {
  display: flex;
  gap: 14px;
  padding: 14px;
  background: #f8f9fa;
  border-radius: 14px;
  border-left: 4px solid #e5e5ea;
  transition: all 0.2s ease;
}

.movimiento-card.entrada {
  border-left-color: #34c759;
}

.movimiento-card.salida {
  border-left-color: #ff9500;
}

.movimiento-card.venta {
  border-left-color: #5856d6;
}

.mov-icon {
  width: 44px;
  height: 44px;
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
  background: #e5e5ea;
  color: #86868b;
}

.mov-icon.entrada {
  background: rgba(52, 199, 89, 0.15);
  color: #34c759;
}

.mov-icon.salida {
  background: rgba(255, 149, 0, 0.15);
  color: #ff9500;
}

.mov-icon.venta {
  background: rgba(88, 86, 214, 0.15);
  color: #5856d6;
}

.mov-content {
  flex: 1;
  min-width: 0;
}

.mov-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 4px;
}

.mov-producto {
  font-size: 15px;
  font-weight: 600;
  color: #1d1d1f;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.mov-cantidad {
  font-size: 16px;
  font-weight: 700;
  flex-shrink: 0;
}

.mov-cantidad.entrada {
  color: #34c759;
}

.mov-cantidad.salida,
.mov-cantidad.venta {
  color: #ff3b30;
}

.mov-details {
  display: flex;
  align-items: center;
  gap: 6px;
  font-size: 12px;
  color: #86868b;
}

.mov-separator {
  color: #d1d1d6;
}

.mov-motivo {
  font-size: 12px;
  color: #86868b;
  margin-top: 6px;
  font-style: italic;
}

/* Kardex */
.kardex-selector {
  margin-bottom: 20px;
}

.selector-label {
  display: block;
  font-size: 13px;
  font-weight: 600;
  color: #43e97b;
  margin-bottom: 8px;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.kardex-info {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 16px;
  background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);
  border-radius: 14px;
  margin-bottom: 20px;
  color: white;
}

.kardex-producto h3 {
  margin: 0;
  font-size: 16px;
  font-weight: 600;
}

.kardex-codigo {
  font-size: 12px;
  opacity: 0.85;
}

.kardex-stats {
  display: flex;
  gap: 20px;
}

.kardex-stat {
  text-align: center;
}

.ks-value {
  display: block;
  font-size: 24px;
  font-weight: 700;
}

.ks-label {
  font-size: 11px;
  opacity: 0.85;
}

.kardex-table-container {
  overflow-x: auto;
}

.kardex-table {
  width: 100%;
  border-collapse: collapse;
  font-size: 13px;
}

.kardex-table th,
.kardex-table td {
  padding: 10px 12px;
  text-align: left;
  border-bottom: 1px solid #f0f0f0;
}

.kardex-table th {
  background: #f8f9fa;
  font-weight: 600;
  color: #86868b;
  text-transform: uppercase;
  font-size: 11px;
  letter-spacing: 0.5px;
}

.kardex-table .text-right {
  text-align: right;
}

.kardex-table .fecha-cell {
  white-space: nowrap;
}

.kardex-table .motivo-cell {
  max-width: 150px;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.tipo-badge {
  display: inline-block;
  padding: 3px 8px;
  border-radius: 6px;
  font-size: 10px;
  font-weight: 600;
}

.tipo-badge.entrada {
  background: rgba(52, 199, 89, 0.15);
  color: #34c759;
}

.tipo-badge.salida {
  background: rgba(255, 149, 0, 0.15);
  color: #ff9500;
}

.tipo-badge.venta {
  background: rgba(88, 86, 214, 0.15);
  color: #5856d6;
}

.kardex-table .entrada {
  color: #34c759;
  font-weight: 600;
}

.kardex-table .salida {
  color: #ff3b30;
  font-weight: 600;
}

.kardex-table .saldo {
  font-weight: 700;
  color: #1d1d1f;
}

/* Bajo Stock */
.bajo-stock-list {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.bajo-stock-card {
  display: flex;
  gap: 14px;
  padding: 16px;
  background: #fff8e1;
  border-radius: 14px;
  border: 1px solid rgba(255, 149, 0, 0.2);
  align-items: center;
}

.bs-icon {
  width: 44px;
  height: 44px;
  border-radius: 12px;
  background: linear-gradient(135deg, #ff9500 0%, #ff6b00 100%);
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
  flex-shrink: 0;
}

.bs-content {
  flex: 1;
  min-width: 0;
}

.bs-header {
  display: flex;
  gap: 8px;
  align-items: center;
  margin-bottom: 6px;
}

.bs-codigo {
  font-size: 11px;
  font-weight: 700;
  color: #ff9500;
  background: rgba(255, 149, 0, 0.15);
  padding: 2px 6px;
  border-radius: 4px;
}

.bs-nombre {
  font-size: 14px;
  font-weight: 600;
  color: #1d1d1f;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.bs-stock-info {
  display: flex;
  gap: 16px;
  flex-wrap: wrap;
}

.bs-stock {
  display: flex;
  gap: 4px;
  font-size: 12px;
}

.bs-label {
  color: #86868b;
}

.bs-value {
  font-weight: 600;
  color: #1d1d1f;
}

.bs-value.danger {
  color: #ff3b30;
}

.bs-value.warning {
  color: #ff9500;
}

.bs-action {
  display: flex;
  align-items: center;
  gap: 6px;
  padding: 10px 16px;
  background: linear-gradient(135deg, #34c759 0%, #30d158 100%);
  border: none;
  border-radius: 10px;
  color: white;
  font-size: 13px;
  font-weight: 600;
  cursor: pointer;
  flex-shrink: 0;
  transition: all 0.2s ease;
}

.bs-action:active {
  transform: scale(0.98);
}

/* Modal */
.modal-overlay {
  position: fixed;
  inset: 0;
  background: rgba(0, 0, 0, 0.5);
  backdrop-filter: blur(8px);
  display: flex;
  align-items: flex-end;
  justify-content: center;
  z-index: 1000;
  animation: fadeIn 0.2s ease;
}

@keyframes fadeIn {
  from { opacity: 0; }
  to { opacity: 1; }
}

.modal-content {
  background: white;
  width: 100%;
  max-height: 85vh;
  border-radius: 24px 24px 0 0;
  overflow: hidden;
  animation: slideUp 0.3s cubic-bezier(0.16, 1, 0.3, 1);
}

.modal-content.modal-small {
  max-height: 70vh;
}

@keyframes slideUp {
  from { transform: translateY(100%); }
  to { transform: translateY(0); }
}

.modal-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 20px 24px;
  background: linear-gradient(135deg, #34c759 0%, #30d158 100%);
  color: white;
}

.modal-header h3 {
  margin: 0;
  font-size: 20px;
  font-weight: 700;
}

.modal-close {
  width: 36px;
  height: 36px;
  border: none;
  border-radius: 10px;
  background: rgba(255, 255, 255, 0.2);
  color: white;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
}

.modal-body {
  padding: 24px;
}

/* Form */
.entrada-producto-info {
  text-align: center;
  padding: 16px;
  background: #f5f5f7;
  border-radius: 14px;
  margin-bottom: 20px;
}

.ep-nombre {
  font-size: 16px;
  font-weight: 600;
  color: #1d1d1f;
  margin-bottom: 6px;
}

.ep-stock {
  font-size: 14px;
  color: #86868b;
}

.ep-stock strong {
  color: #34c759;
  font-size: 18px;
}

.form-group {
  margin-bottom: 16px;
}

.form-label {
  display: block;
  font-size: 12px;
  font-weight: 600;
  color: #34c759;
  margin-bottom: 8px;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.form-input {
  width: 100%;
  padding: 12px 14px;
  border: 2px solid #e5e5ea;
  border-radius: 12px;
  font-size: 15px;
  color: #1d1d1f;
  background: #f8f9fa;
  transition: all 0.2s ease;
  box-sizing: border-box;
}

.form-input:focus {
  outline: none;
  border-color: #34c759;
  background: white;
}

.entrada-input {
  text-align: center;
  font-size: 28px;
  font-weight: 700;
}

.form-actions {
  display: flex;
  gap: 12px;
  margin-top: 20px;
}

.btn-cancel,
.btn-submit {
  flex: 1;
  padding: 14px 20px;
  border: none;
  border-radius: 14px;
  font-size: 15px;
  font-weight: 600;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  transition: all 0.2s ease;
}

.btn-cancel {
  background: #f5f5f7;
  color: #1d1d1f;
}

.btn-submit {
  background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);
  color: white;
  box-shadow: 0 4px 15px rgba(67, 233, 123, 0.4);
}

.btn-submit.entrada {
  background: linear-gradient(135deg, #34c759 0%, #30d158 100%);
  box-shadow: 0 4px 15px rgba(52, 199, 89, 0.4);
}

.btn-submit:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}
</style>


