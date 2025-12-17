<template>
  <div class="productos-view">
    <!-- Header -->
    <div class="page-header">
      <div class="header-left">
        <div class="header-icon">
          <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"></path>
            <line x1="3" y1="6" x2="21" y2="6"></line>
            <path d="M16 10a4 4 0 0 1-8 0"></path>
          </svg>
        </div>
        <div class="header-text">
          <h1>Productos</h1>
          <p class="header-subtitle">{{ productos.length }} en inventario</p>
        </div>
      </div>
      <button class="btn-primary" @click="nuevoProducto">
        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <line x1="12" y1="5" x2="12" y2="19"></line>
          <line x1="5" y1="12" x2="19" y2="12"></line>
        </svg>
        <span class="btn-text">Nuevo</span>
      </button>
    </div>

    <!-- Search Bar -->
    <div class="search-section">
      <div class="search-box">
        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <circle cx="11" cy="11" r="8"></circle>
          <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
        </svg>
        <input 
          v-model="busqueda" 
          type="text" 
          placeholder="Buscar por código o nombre..."
          @input="filtrarProductos"
        />
        <button v-if="busqueda" class="clear-btn" @click="limpiarBusqueda">
          <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <line x1="18" y1="6" x2="6" y2="18"></line>
            <line x1="6" y1="6" x2="18" y2="18"></line>
          </svg>
        </button>
      </div>
    </div>

    <!-- Category Tabs -->
    <div class="filter-section">
      <div class="tabs-container">
        <button 
          :class="['tab-btn', { active: categoriaActiva === null }]"
          @click="categoriaActiva = null"
        >
          <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <rect x="3" y="3" width="7" height="7"></rect>
            <rect x="14" y="3" width="7" height="7"></rect>
            <rect x="14" y="14" width="7" height="7"></rect>
            <rect x="3" y="14" width="7" height="7"></rect>
          </svg>
          Todos
        </button>
        <button 
          v-for="cat in categorias" 
          :key="cat.id" 
          :class="['tab-btn', { active: categoriaActiva === cat.id }]"
          @click="categoriaActiva = cat.id"
        >
          {{ cat.nombre }}
        </button>
      </div>
    </div>

    <!-- Stock Alert -->
    <div v-if="productosBajoStock.length > 0" class="alert-banner">
      <div class="alert-icon">
        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path>
          <line x1="12" y1="9" x2="12" y2="13"></line>
          <line x1="12" y1="17" x2="12.01" y2="17"></line>
        </svg>
      </div>
      <span>{{ productosBajoStock.length }} producto(s) con stock bajo</span>
    </div>

    <!-- Products List -->
    <div class="content-container">
      <div v-if="loading" class="loading-state">
        <div class="loader"></div>
        <p>Cargando productos...</p>
      </div>
      
      <div v-else-if="productosFiltrados.length === 0" class="empty-state">
        <div class="empty-icon">
          <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
            <path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"></path>
            <line x1="3" y1="6" x2="21" y2="6"></line>
            <path d="M16 10a4 4 0 0 1-8 0"></path>
          </svg>
        </div>
        <p>{{ busqueda ? 'No se encontraron productos' : 'No hay productos registrados' }}</p>
        <button v-if="!busqueda" class="btn-empty" @click="nuevoProducto">
          <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <line x1="12" y1="5" x2="12" y2="19"></line>
            <line x1="5" y1="12" x2="19" y2="12"></line>
          </svg>
          Agregar primer producto
        </button>
      </div>
      
      <div v-else class="productos-list">
        <div 
          v-for="producto in productosFiltrados" 
          :key="producto.id" 
          class="producto-card"
          :class="{ 
            inactivo: !isActivo(producto),
            'bajo-stock': esBajoStock(producto)
          }"
        >
          <div class="producto-image">
            <img 
              v-if="producto.foto" 
              :src="getImageUrl(producto.foto)" 
              :alt="producto.nombre"
              @error="handleImageError"
            />
            <div v-else class="image-placeholder">
              <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                <path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"></path>
                <line x1="3" y1="6" x2="21" y2="6"></line>
                <path d="M16 10a4 4 0 0 1-8 0"></path>
              </svg>
            </div>
            <span 
              v-if="esBajoStock(producto)" 
              class="stock-warning"
              title="Stock bajo"
            >
              <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path>
                <line x1="12" y1="9" x2="12" y2="13"></line>
                <line x1="12" y1="17" x2="12.01" y2="17"></line>
              </svg>
            </span>
          </div>
          
          <div class="producto-info">
            <div class="producto-header">
              <span class="producto-codigo">{{ producto.codigo }}</span>
              <span :class="['status-badge', isActivo(producto) ? 'activo' : 'inactivo']">
                {{ isActivo(producto) ? 'Activo' : 'Inactivo' }}
              </span>
            </div>
            
            <h3 class="producto-nombre">{{ producto.nombre }}</h3>
            
            <div class="producto-categoria" v-if="producto.categoria">
              <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"></path>
              </svg>
              {{ producto.categoria.nombre }}
            </div>
            
            <div class="producto-stats">
              <div class="stat-item precio">
                <span class="stat-label">Precio</span>
                <span class="stat-value">${{ formatPrecio(producto.precio) }}</span>
              </div>
              <div class="stat-item stock" :class="{ warning: esBajoStock(producto) }">
                <span class="stat-label">Stock</span>
                <span class="stat-value">{{ producto.inventario_actual || 0 }}</span>
              </div>
              <div class="stat-item costo">
                <span class="stat-label">Costo</span>
                <span class="stat-value">${{ formatPrecio(producto.costo) }}</span>
              </div>
            </div>
          </div>
          
          <div class="producto-actions">
            <button class="action-btn edit" @click="editarProducto(producto)" title="Editar">
              <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
              </svg>
            </button>
            <button class="action-btn inventory" @click="gestionarStock(producto)" title="Ajustar stock">
              <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"></path>
                <line x1="3" y1="6" x2="21" y2="6"></line>
                <path d="M16 10a4 4 0 0 1-8 0"></path>
              </svg>
            </button>
            <button 
              class="action-btn"
              :class="isActivo(producto) ? 'warning' : 'success'"
              @click="toggleProducto(producto)"
              :title="isActivo(producto) ? 'Desactivar' : 'Activar'"
            >
              <svg v-if="isActivo(producto)" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path>
                <line x1="1" y1="1" x2="23" y2="23"></line>
              </svg>
              <svg v-else xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                <circle cx="12" cy="12" r="3"></circle>
              </svg>
            </button>
            <button class="action-btn danger" @click="eliminarProducto(producto)" title="Eliminar">
              <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <polyline points="3 6 5 6 21 6"></polyline>
                <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
              </svg>
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Modal Producto -->
    <Teleport to="body">
      <div v-if="showModal" class="modal-overlay" @click.self="closeModal">
        <div class="modal-content">
          <div class="modal-header">
            <h3>{{ selectedProducto ? 'Editar Producto' : 'Nuevo Producto' }}</h3>
            <button class="modal-close" @click="closeModal">
              <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <line x1="18" y1="6" x2="6" y2="18"></line>
                <line x1="6" y1="6" x2="18" y2="18"></line>
              </svg>
            </button>
          </div>
          <div class="modal-body">
            <form class="form-container" @submit.prevent="guardarProducto">
              <!-- Foto preview -->
              <div class="foto-section">
                <div class="foto-preview" @click="triggerFileInput">
                  <img v-if="fotoPreview" :src="fotoPreview" alt="Preview" />
                  <div v-else class="foto-placeholder">
                    <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                      <rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
                      <circle cx="8.5" cy="8.5" r="1.5"></circle>
                      <polyline points="21 15 16 10 5 21"></polyline>
                    </svg>
                    <span>Agregar foto</span>
                  </div>
                </div>
                <input 
                  ref="fileInput"
                  type="file" 
                  accept="image/*"
                  @change="handleFileChange"
                  style="display: none"
                />
              </div>
              
              <div class="form-row">
                <div class="form-group">
                  <label class="form-label">
                    Código <span class="required">*</span>
                  </label>
                  <input 
                    v-model="formData.codigo" 
                    type="text" 
                    placeholder="SKU-001" 
                    maxlength="100"
                    class="form-input"
                    required
                  />
                </div>
                <div class="form-group">
                  <label class="form-label">
                    Categoría
                  </label>
                  <select v-model="formData.categoria_id" class="form-select">
                    <option :value="null">Sin categoría</option>
                    <option v-for="cat in categorias" :key="cat.id" :value="cat.id">
                      {{ cat.nombre }}
                    </option>
                  </select>
                </div>
              </div>
              
              <div class="form-group">
                <label class="form-label">
                  Nombre <span class="required">*</span>
                </label>
                <input 
                  v-model="formData.nombre" 
                  type="text" 
                  placeholder="Nombre del producto" 
                  maxlength="255"
                  class="form-input"
                  required
                />
              </div>
              
              <div class="form-row three-cols">
                <div class="form-group">
                  <label class="form-label">Precio <span class="required">*</span></label>
                  <div class="input-prefix">
                    <span>$</span>
                    <input 
                      v-model.number="formData.precio" 
                      type="number" 
                      step="0.01"
                      min="0"
                      placeholder="0.00"
                      class="form-input"
                      required
                    />
                  </div>
                </div>
                <div class="form-group">
                  <label class="form-label">Costo</label>
                  <div class="input-prefix">
                    <span>$</span>
                    <input 
                      v-model.number="formData.costo" 
                      type="number" 
                      step="0.01"
                      min="0"
                      placeholder="0.00"
                      class="form-input"
                    />
                  </div>
                </div>
                <div class="form-group">
                  <label class="form-label">Stock mínimo</label>
                  <input 
                    v-model.number="formData.inventario_minimo" 
                    type="number" 
                    min="0"
                    placeholder="0"
                    class="form-input"
                  />
                </div>
              </div>
              
              <div class="form-group" v-if="selectedProducto">
                <label class="form-label">Estado</label>
                <div class="toggle-row">
                  <label class="toggle-switch">
                    <input type="checkbox" v-model="formData.active" />
                    <span class="toggle-slider"></span>
                  </label>
                  <span class="toggle-label">{{ formData.active ? 'Activo' : 'Inactivo' }}</span>
                </div>
              </div>
              
              <div class="form-actions">
                <button type="button" class="btn-cancel" @click="closeModal">
                  Cancelar
                </button>
                <button 
                  type="submit" 
                  class="btn-submit" 
                  :disabled="!formData.nombre.trim() || !formData.codigo.trim()"
                >
                  <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path>
                    <polyline points="17 21 17 13 7 13 7 21"></polyline>
                    <polyline points="7 3 7 8 15 8"></polyline>
                  </svg>
                  {{ selectedProducto ? 'Actualizar' : 'Crear' }}
                </button>
              </div>
            </form>
          </div>
        </div>
      </div>
    </Teleport>

    <!-- Modal Stock -->
    <Teleport to="body">
      <div v-if="showStockModal" class="modal-overlay" @click.self="closeStockModal">
        <div class="modal-content modal-small">
          <div class="modal-header stock-header">
            <h3>Ajustar Stock</h3>
            <button class="modal-close" @click="closeStockModal">
              <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <line x1="18" y1="6" x2="6" y2="18"></line>
                <line x1="6" y1="6" x2="18" y2="18"></line>
              </svg>
            </button>
          </div>
          <div class="modal-body">
            <div class="stock-producto-info" v-if="stockProducto">
              <div class="stock-producto-nombre">{{ stockProducto.nombre }}</div>
              <div class="stock-actual">
                Stock actual: <strong>{{ stockProducto.inventario_actual || 0 }}</strong>
              </div>
            </div>
            
            <div class="stock-actions-grid">
              <button 
                :class="['stock-action-btn', { active: stockTipo === 'entrada' }]"
                @click="stockTipo = 'entrada'"
              >
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <line x1="12" y1="5" x2="12" y2="19"></line>
                  <polyline points="19 12 12 19 5 12"></polyline>
                </svg>
                Entrada
              </button>
              <button 
                :class="['stock-action-btn', { active: stockTipo === 'salida' }]"
                @click="stockTipo = 'salida'"
              >
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <line x1="12" y1="19" x2="12" y2="5"></line>
                  <polyline points="5 12 12 5 19 12"></polyline>
                </svg>
                Salida
              </button>
              <button 
                :class="['stock-action-btn', { active: stockTipo === 'ajuste' }]"
                @click="stockTipo = 'ajuste'"
              >
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <path d="M12 20h9"></path>
                  <path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"></path>
                </svg>
                Ajuste
              </button>
            </div>
            
            <div class="form-group">
              <label class="form-label">
                {{ stockTipo === 'ajuste' ? 'Nuevo stock' : 'Cantidad' }}
              </label>
              <input 
                v-model.number="stockCantidad" 
                type="number" 
                :min="stockTipo === 'ajuste' ? 0 : 1"
                :placeholder="stockTipo === 'ajuste' ? 'Stock final' : 'Cantidad'"
                class="form-input stock-input"
              />
            </div>
            
            <div class="form-group">
              <label class="form-label">Motivo {{ stockTipo === 'ajuste' ? '(requerido)' : '' }}</label>
              <input 
                v-model="stockMotivo" 
                type="text" 
                placeholder="Ej: Compra, Devolución..."
                class="form-input"
              />
            </div>
            
            <div class="form-actions">
              <button type="button" class="btn-cancel" @click="closeStockModal">
                Cancelar
              </button>
              <button 
                type="button" 
                class="btn-submit"
                :class="stockTipo === 'entrada' ? 'entrada' : stockTipo === 'salida' ? 'salida' : ''"
                @click="ejecutarMovimientoStock"
                :disabled="!stockCantidad || (stockTipo === 'ajuste' && !stockMotivo)"
              >
                {{ stockTipo === 'entrada' ? 'Registrar Entrada' : stockTipo === 'salida' ? 'Registrar Salida' : 'Aplicar Ajuste' }}
              </button>
            </div>
          </div>
        </div>
      </div>
    </Teleport>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, reactive, onMounted } from 'vue';
import { 
  getProductos, 
  getCategoriasProductos,
  createProducto, 
  updateProducto, 
  deleteProducto,
  registrarEntrada,
  registrarSalida,
  ajustarInventario
} from '@/services/inventarioService';
import Swal from 'sweetalert2';

const productos = ref<any[]>([]);
const categorias = ref<any[]>([]);
const categoriaActiva = ref<number | null>(null);
const busqueda = ref('');
const loading = ref(true);
const showModal = ref(false);
const showStockModal = ref(false);
const selectedProducto = ref<any>(null);
const stockProducto = ref<any>(null);
const stockTipo = ref<'entrada' | 'salida' | 'ajuste'>('entrada');
const stockCantidad = ref<number>(0);
const stockMotivo = ref('');
const fileInput = ref<HTMLInputElement | null>(null);
const fotoFile = ref<File | null>(null);
const fotoPreview = ref<string | null>(null);

const formData = reactive({
  codigo: '',
  nombre: '',
  categoria_id: null as number | null,
  precio: 0,
  costo: 0,
  inventario_minimo: 0,
  active: true,
});

const productosFiltrados = computed(() => {
  let filtered = productos.value;
  
  if (categoriaActiva.value !== null) {
    filtered = filtered.filter(p => p.categoria_id === categoriaActiva.value);
  }
  
  if (busqueda.value.trim()) {
    const term = busqueda.value.toLowerCase();
    filtered = filtered.filter(p => 
      p.codigo.toLowerCase().includes(term) || 
      p.nombre.toLowerCase().includes(term)
    );
  }
  
  return filtered;
});

const productosBajoStock = computed(() => {
  return productos.value.filter(p => esBajoStock(p));
});

function isActivo(producto: any): boolean {
  const activo = producto.active !== undefined ? producto.active : producto.activo;
  return activo === true || activo === 1 || activo === '1' || activo === 'true';
}

function esBajoStock(producto: any): boolean {
  const actual = producto.inventario_actual || 0;
  const minimo = producto.inventario_minimo || 0;
  return actual <= minimo && minimo > 0;
}

function formatPrecio(precio: any): string {
  return (Number(precio) || 0).toFixed(2);
}

function getImageUrl(path: string): string {
  if (!path) return '';
  if (path.startsWith('http')) return path;
  const baseUrl = import.meta.env.VITE_API_URL || 'http://localhost:8000';
  return `${baseUrl}/storage/${path}`;
}

function handleImageError(e: Event) {
  const target = e.target as HTMLImageElement;
  target.style.display = 'none';
}

async function cargarDatos() {
  loading.value = true;
  try {
    const [productosRes, categoriasRes] = await Promise.all([
      getProductos(),
      getCategoriasProductos()
    ]);
    
    if (productosRes.success) {
      productos.value = productosRes.data || [];
    }
    if (categoriasRes.success) {
      categorias.value = categoriasRes.data || [];
    }
  } catch (error) {
    console.error('Error cargando datos:', error);
    Swal.fire({
      icon: 'error',
      title: 'Error',
      text: 'No se pudieron cargar los productos',
      confirmButtonColor: '#4facfe'
    });
  } finally {
    loading.value = false;
  }
}

function filtrarProductos() {
  // La filtración es reactiva mediante computed
}

function limpiarBusqueda() {
  busqueda.value = '';
}

function nuevoProducto() {
  selectedProducto.value = null;
  formData.codigo = '';
  formData.nombre = '';
  formData.categoria_id = null;
  formData.precio = 0;
  formData.costo = 0;
  formData.inventario_minimo = 0;
  formData.active = true;
  fotoFile.value = null;
  fotoPreview.value = null;
  showModal.value = true;
}

function editarProducto(p: any) {
  selectedProducto.value = p;
  formData.codigo = p.codigo;
  formData.nombre = p.nombre;
  formData.categoria_id = p.categoria_id;
  formData.precio = p.precio;
  formData.costo = p.costo || 0;
  formData.inventario_minimo = p.inventario_minimo || 0;
  formData.active = isActivo(p);
  fotoFile.value = null;
  fotoPreview.value = p.foto ? getImageUrl(p.foto) : null;
  showModal.value = true;
}

function triggerFileInput() {
  fileInput.value?.click();
}

function handleFileChange(e: Event) {
  const target = e.target as HTMLInputElement;
  const file = target.files?.[0];
  if (file) {
    fotoFile.value = file;
    const reader = new FileReader();
    reader.onload = (e) => {
      fotoPreview.value = e.target?.result as string;
    };
    reader.readAsDataURL(file);
  }
}

async function toggleProducto(p: any) {
  try {
    const nuevoEstado = !isActivo(p);
    await updateProducto(p.id, { active: nuevoEstado });
    p.active = nuevoEstado;
    
    Swal.fire({
      icon: 'success',
      title: nuevoEstado ? 'Producto activado' : 'Producto desactivado',
      toast: true,
      position: 'top-end',
      showConfirmButton: false,
      timer: 2000
    });
  } catch (error: any) {
    console.error('Error actualizando producto:', error);
    Swal.fire({
      icon: 'error',
      title: 'Error',
      text: error.response?.data?.message || 'Error al actualizar producto',
      confirmButtonColor: '#4facfe'
    });
    await cargarDatos();
  }
}

async function eliminarProducto(p: any) {
  const result = await Swal.fire({
    icon: 'warning',
    title: '¿Eliminar producto?',
    text: `Se eliminará "${p.nombre}". Esta acción no se puede deshacer.`,
    showCancelButton: true,
    confirmButtonColor: '#ff3b30',
    cancelButtonColor: '#86868b',
    confirmButtonText: 'Eliminar',
    cancelButtonText: 'Cancelar'
  });
  
  if (!result.isConfirmed) return;
  
  try {
    await deleteProducto(p.id);
    Swal.fire({
      icon: 'success',
      title: 'Producto eliminado',
      toast: true,
      position: 'top-end',
      showConfirmButton: false,
      timer: 2000
    });
    await cargarDatos();
  } catch (error: any) {
    console.error('Error eliminando producto:', error);
    Swal.fire({
      icon: 'error',
      title: 'Error',
      text: error.response?.data?.message || 'Error al eliminar producto',
      confirmButtonColor: '#4facfe'
    });
  }
}

async function guardarProducto() {
  if (!formData.nombre.trim() || !formData.codigo.trim()) {
    Swal.fire({
      icon: 'warning',
      title: 'Campos requeridos',
      text: 'El código y nombre son requeridos',
      confirmButtonColor: '#4facfe'
    });
    return;
  }

  try {
    const data: any = {
      codigo: formData.codigo.trim(),
      nombre: formData.nombre.trim(),
      categoria_id: formData.categoria_id,
      precio: formData.precio,
      costo: formData.costo,
      inventario_minimo: formData.inventario_minimo,
    };
    
    if (selectedProducto.value) {
      data.active = formData.active;
    }
    
    if (fotoFile.value) {
      data.foto = fotoFile.value;
    }
    
    if (selectedProducto.value) {
      await updateProducto(selectedProducto.value.id, data);
    } else {
      await createProducto(data);
    }
    
    Swal.fire({
      icon: 'success',
      title: selectedProducto.value ? 'Producto actualizado' : 'Producto creado',
      toast: true,
      position: 'top-end',
      showConfirmButton: false,
      timer: 2000
    });
    
    closeModal();
    await cargarDatos();
  } catch (error: any) {
    console.error('Error guardando producto:', error);
    Swal.fire({
      icon: 'error',
      title: 'Error',
      text: error.response?.data?.message || 'Error al guardar producto',
      confirmButtonColor: '#4facfe'
    });
  }
}

function gestionarStock(p: any) {
  stockProducto.value = p;
  stockTipo.value = 'entrada';
  stockCantidad.value = 0;
  stockMotivo.value = '';
  showStockModal.value = true;
}

async function ejecutarMovimientoStock() {
  if (!stockProducto.value || !stockCantidad.value) return;
  
  if (stockTipo.value === 'ajuste' && !stockMotivo.value) {
    Swal.fire({
      icon: 'warning',
      title: 'Motivo requerido',
      text: 'El motivo es requerido para ajustes de inventario',
      confirmButtonColor: '#4facfe'
    });
    return;
  }
  
  try {
    if (stockTipo.value === 'entrada') {
      await registrarEntrada({
        producto_id: stockProducto.value.id,
        cantidad: stockCantidad.value,
        motivo: stockMotivo.value || 'Entrada manual',
      });
    } else if (stockTipo.value === 'salida') {
      await registrarSalida({
        producto_id: stockProducto.value.id,
        cantidad: stockCantidad.value,
        motivo: stockMotivo.value || 'Salida manual',
      });
    } else {
      await ajustarInventario({
        producto_id: stockProducto.value.id,
        nuevo_stock: stockCantidad.value,
        motivo: stockMotivo.value,
      });
    }
    
    Swal.fire({
      icon: 'success',
      title: 'Stock actualizado',
      toast: true,
      position: 'top-end',
      showConfirmButton: false,
      timer: 2000
    });
    
    closeStockModal();
    await cargarDatos();
  } catch (error: any) {
    console.error('Error actualizando stock:', error);
    Swal.fire({
      icon: 'error',
      title: 'Error',
      text: error.response?.data?.message || 'Error al actualizar stock',
      confirmButtonColor: '#4facfe'
    });
  }
}

function closeModal() {
  showModal.value = false;
  selectedProducto.value = null;
  fotoFile.value = null;
  fotoPreview.value = null;
}

function closeStockModal() {
  showStockModal.value = false;
  stockProducto.value = null;
}

onMounted(() => {
  cargarDatos();
});
</script>

<style scoped>
/* ===== Modern Ocean Blue Design - Productos ===== */

.productos-view {
  min-height: 100vh;
  background: linear-gradient(135deg, #f0f4f8 0%, #e2e8f0 100%);
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
  margin-bottom: 16px;
  padding: 20px;
  background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
  border-radius: 20px;
  color: white;
  box-shadow: 0 10px 40px rgba(79, 172, 254, 0.3);
}

.header-left {
  display: flex;
  align-items: center;
  gap: 14px;
  flex: 1;
  min-width: 0;
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
  flex-shrink: 0;
}

.header-text h1 {
  font-size: 20px;
  font-weight: 700;
  margin: 0;
  letter-spacing: -0.5px;
}

.header-subtitle {
  font-size: 13px;
  opacity: 0.85;
  margin: 4px 0 0;
  font-weight: 500;
}

.btn-primary {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 12px 20px;
  background: rgba(255, 255, 255, 0.2);
  border: 1px solid rgba(255, 255, 255, 0.3);
  border-radius: 12px;
  color: white;
  font-size: 14px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.3s ease;
  backdrop-filter: blur(10px);
}

.btn-primary:active {
  transform: scale(0.96);
}

.btn-text {
  display: none;
}

@media (min-width: 480px) {
  .btn-text { display: inline; }
  .header-text h1 { font-size: 24px; }
}

/* Search */
.search-section {
  margin-bottom: 16px;
}

.search-box {
  display: flex;
  align-items: center;
  gap: 12px;
  background: white;
  border-radius: 14px;
  padding: 12px 16px;
  box-shadow: 0 2px 10px rgba(0, 0, 0, 0.06);
  border: 1px solid #e5e5ea;
}

.search-box svg {
  color: #86868b;
  flex-shrink: 0;
}

.search-box input {
  flex: 1;
  border: none;
  background: none;
  font-size: 15px;
  color: #1d1d1f;
  outline: none;
}

.search-box input::placeholder {
  color: #a0a0a5;
}

.clear-btn {
  background: #f0f0f0;
  border: none;
  border-radius: 8px;
  padding: 6px;
  cursor: pointer;
  color: #86868b;
  display: flex;
  align-items: center;
  justify-content: center;
}

/* Tabs */
.filter-section {
  margin-bottom: 16px;
}

.tabs-container {
  display: flex;
  gap: 10px;
  overflow-x: auto;
  padding-bottom: 4px;
  -webkit-overflow-scrolling: touch;
  scrollbar-width: none;
}

.tabs-container::-webkit-scrollbar {
  display: none;
}

.tab-btn {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 10px 16px;
  background: white;
  border: 1px solid #e5e5ea;
  border-radius: 12px;
  font-size: 14px;
  font-weight: 500;
  color: #1d1d1f;
  cursor: pointer;
  white-space: nowrap;
  transition: all 0.2s ease;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
}

.tab-btn.active {
  background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
  border-color: transparent;
  color: white;
  box-shadow: 0 4px 15px rgba(79, 172, 254, 0.4);
}

/* Alert Banner */
.alert-banner {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 14px 18px;
  background: linear-gradient(135deg, #fff3cd 0%, #ffe8a1 100%);
  border-radius: 14px;
  margin-bottom: 16px;
  color: #856404;
  font-size: 14px;
  font-weight: 500;
  border: 1px solid rgba(255, 193, 7, 0.3);
}

.alert-icon {
  color: #ff9500;
}

/* Content */
.loading-state,
.empty-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 60px 20px;
  text-align: center;
}

.loader {
  width: 36px;
  height: 36px;
  border: 3px solid #e5e5ea;
  border-top-color: #4facfe;
  border-radius: 50%;
  animation: spin 0.8s linear infinite;
  margin-bottom: 16px;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}

.loading-state p,
.empty-state p {
  color: #86868b;
  font-size: 15px;
  margin: 0 0 20px;
}

.empty-icon {
  color: #c7c7cc;
  margin-bottom: 16px;
}

.btn-empty {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 12px 24px;
  background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
  border: none;
  border-radius: 12px;
  color: white;
  font-size: 14px;
  font-weight: 600;
  cursor: pointer;
}

/* Products List */
.productos-list {
  display: flex;
  flex-direction: column;
  gap: 14px;
}

.producto-card {
  display: flex;
  gap: 14px;
  background: white;
  border-radius: 18px;
  padding: 14px;
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.06);
  transition: all 0.3s ease;
  border: 1px solid rgba(0, 0, 0, 0.04);
}

.producto-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 8px 30px rgba(0, 0, 0, 0.1);
}

.producto-card.inactivo {
  opacity: 0.65;
}

.producto-card.bajo-stock {
  border-left: 4px solid #ff9500;
}

.producto-image {
  position: relative;
  width: 80px;
  height: 80px;
  flex-shrink: 0;
  border-radius: 14px;
  overflow: hidden;
  background: #f5f5f7;
}

.producto-image img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.image-placeholder {
  width: 100%;
  height: 100%;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  color: #c7c7cc;
}

.stock-warning {
  position: absolute;
  top: 4px;
  right: 4px;
  width: 24px;
  height: 24px;
  background: #ff9500;
  border-radius: 6px;
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
}

.producto-info {
  flex: 1;
  min-width: 0;
  display: flex;
  flex-direction: column;
  gap: 6px;
}

.producto-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 8px;
}

.producto-codigo {
  font-size: 11px;
  font-weight: 700;
  color: #4facfe;
  background: rgba(79, 172, 254, 0.1);
  padding: 3px 8px;
  border-radius: 6px;
  text-transform: uppercase;
}

.status-badge {
  padding: 3px 8px;
  border-radius: 6px;
  font-size: 10px;
  font-weight: 600;
  text-transform: uppercase;
}

.status-badge.activo {
  background: rgba(52, 199, 89, 0.12);
  color: #34c759;
}

.status-badge.inactivo {
  background: rgba(255, 59, 48, 0.12);
  color: #ff3b30;
}

.producto-nombre {
  margin: 0;
  font-size: 16px;
  font-weight: 600;
  color: #1d1d1f;
  letter-spacing: -0.2px;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.producto-categoria {
  display: flex;
  align-items: center;
  gap: 4px;
  font-size: 12px;
  color: #86868b;
}

.producto-stats {
  display: flex;
  gap: 12px;
  margin-top: 4px;
}

.stat-item {
  display: flex;
  flex-direction: column;
  gap: 2px;
}

.stat-label {
  font-size: 10px;
  color: #a0a0a5;
  text-transform: uppercase;
  letter-spacing: 0.3px;
}

.stat-value {
  font-size: 14px;
  font-weight: 700;
  color: #1d1d1f;
}

.stat-item.precio .stat-value {
  color: #4facfe;
}

.stat-item.stock.warning .stat-value {
  color: #ff9500;
}

.producto-actions {
  display: flex;
  flex-direction: column;
  gap: 6px;
  flex-shrink: 0;
}

.action-btn {
  width: 36px;
  height: 36px;
  border: none;
  border-radius: 10px;
  background: #f5f5f7;
  color: #86868b;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all 0.2s ease;
}

.action-btn:active {
  transform: scale(0.95);
}

.action-btn.edit {
  color: #4facfe;
}

.action-btn.inventory {
  color: #5856d6;
  background: rgba(88, 86, 214, 0.1);
}

.action-btn.warning {
  background: #fff3cd;
  color: #ff9500;
}

.action-btn.success {
  background: #d4edda;
  color: #34c759;
}

.action-btn.danger {
  background: #f8d7da;
  color: #ff3b30;
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
  max-height: 92vh;
  border-radius: 24px 24px 0 0;
  overflow: hidden;
  animation: slideUp 0.3s cubic-bezier(0.16, 1, 0.3, 1);
}

.modal-content.modal-small {
  max-height: 80vh;
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
  background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
  color: white;
}

.modal-header.stock-header {
  background: linear-gradient(135deg, #5856d6 0%, #af52de 100%);
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
  overflow-y: auto;
  max-height: calc(92vh - 80px);
}

/* Form */
.form-container {
  display: flex;
  flex-direction: column;
  gap: 18px;
}

.form-row {
  display: flex;
  gap: 12px;
}

.form-row.three-cols {
  flex-wrap: wrap;
}

.form-row.three-cols .form-group {
  flex: 1;
  min-width: 80px;
}

.form-group {
  display: flex;
  flex-direction: column;
  gap: 8px;
  flex: 1;
}

.form-label {
  font-size: 12px;
  font-weight: 600;
  color: #4facfe;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.required {
  color: #ff3b30;
}

.form-input,
.form-select {
  width: 100%;
  padding: 12px 14px;
  border: 2px solid #e5e5ea;
  border-radius: 12px;
  font-size: 15px;
  color: #1d1d1f;
  background: #f8f9fa;
  transition: all 0.2s ease;
  box-sizing: border-box;
  font-family: inherit;
}

.form-input:focus,
.form-select:focus {
  outline: none;
  border-color: #4facfe;
  background: white;
}

.input-prefix {
  display: flex;
  align-items: center;
  border: 2px solid #e5e5ea;
  border-radius: 12px;
  overflow: hidden;
  background: #f8f9fa;
  transition: all 0.2s ease;
}

.input-prefix:focus-within {
  border-color: #4facfe;
  background: white;
}

.input-prefix span {
  padding: 12px;
  background: #e5e5ea;
  color: #86868b;
  font-weight: 600;
}

.input-prefix .form-input {
  border: none;
  border-radius: 0;
  background: transparent;
}

/* Foto Section */
.foto-section {
  display: flex;
  justify-content: center;
  margin-bottom: 8px;
}

.foto-preview {
  width: 120px;
  height: 120px;
  border-radius: 16px;
  overflow: hidden;
  background: #f5f5f7;
  cursor: pointer;
  border: 2px dashed #d1d1d6;
  transition: all 0.2s ease;
}

.foto-preview:hover {
  border-color: #4facfe;
}

.foto-preview img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.foto-placeholder {
  width: 100%;
  height: 100%;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 8px;
  color: #a0a0a5;
  font-size: 12px;
}

/* Toggle */
.toggle-row {
  display: flex;
  align-items: center;
  gap: 14px;
}

.toggle-switch {
  position: relative;
  display: inline-block;
  width: 52px;
  height: 32px;
  cursor: pointer;
}

.toggle-switch input {
  opacity: 0;
  width: 0;
  height: 0;
}

.toggle-slider {
  position: absolute;
  cursor: pointer;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background-color: #e5e5ea;
  transition: 0.3s;
  border-radius: 16px;
}

.toggle-slider::before {
  position: absolute;
  content: "";
  height: 28px;
  width: 28px;
  left: 2px;
  bottom: 2px;
  background-color: white;
  transition: 0.3s;
  border-radius: 50%;
  box-shadow: 0 2px 6px rgba(0, 0, 0, 0.2);
}

.toggle-switch input:checked + .toggle-slider {
  background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
}

.toggle-switch input:checked + .toggle-slider::before {
  transform: translateX(20px);
}

.toggle-label {
  font-size: 14px;
  font-weight: 600;
  color: #1d1d1f;
}

/* Stock Modal */
.stock-producto-info {
  text-align: center;
  padding: 16px;
  background: #f5f5f7;
  border-radius: 14px;
  margin-bottom: 20px;
}

.stock-producto-nombre {
  font-size: 16px;
  font-weight: 600;
  color: #1d1d1f;
  margin-bottom: 6px;
}

.stock-actual {
  font-size: 14px;
  color: #86868b;
}

.stock-actual strong {
  color: #5856d6;
  font-size: 18px;
}

.stock-actions-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 10px;
  margin-bottom: 20px;
}

.stock-action-btn {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 8px;
  padding: 16px 12px;
  background: #f5f5f7;
  border: 2px solid transparent;
  border-radius: 14px;
  font-size: 13px;
  font-weight: 600;
  color: #86868b;
  cursor: pointer;
  transition: all 0.2s ease;
}

.stock-action-btn.active {
  background: white;
  border-color: #5856d6;
  color: #5856d6;
  box-shadow: 0 4px 15px rgba(88, 86, 214, 0.2);
}

.stock-input {
  text-align: center;
  font-size: 24px;
  font-weight: 700;
}

/* Form Actions */
.form-actions {
  display: flex;
  gap: 12px;
  margin-top: 12px;
  padding-top: 20px;
  border-top: 1px solid #f0f0f0;
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
  background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
  color: white;
  box-shadow: 0 4px 15px rgba(79, 172, 254, 0.4);
}

.btn-submit.entrada {
  background: linear-gradient(135deg, #34c759 0%, #30d158 100%);
  box-shadow: 0 4px 15px rgba(52, 199, 89, 0.4);
}

.btn-submit.salida {
  background: linear-gradient(135deg, #ff9500 0%, #ff3b30 100%);
  box-shadow: 0 4px 15px rgba(255, 149, 0, 0.4);
}

.btn-submit:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}
</style>


