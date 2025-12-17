<template>
  <div class="ventas-view">
    <!-- Header -->
    <div class="page-header">
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
          <p class="header-subtitle">Punto de venta</p>
        </div>
      </div>
      <button class="btn-primary" @click="nuevaVenta">
        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <line x1="12" y1="5" x2="12" y2="19"></line>
          <line x1="5" y1="12" x2="19" y2="12"></line>
        </svg>
        <span class="btn-text">Nueva Venta</span>
      </button>
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

    <!-- Quick Stats -->
    <div class="stats-row">
      <div class="stat-mini">
        <span class="sm-value">${{ formatPrecio(ventasHoy) }}</span>
        <span class="sm-label">Ventas hoy</span>
      </div>
      <div class="stat-mini">
        <span class="sm-value">{{ totalVentasHoy }}</span>
        <span class="sm-label">Transacciones</span>
      </div>
    </div>

    <!-- Content -->
    <div class="content-section">
      <!-- Filters -->
      <div class="filters-row">
        <div class="search-box">
          <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <circle cx="11" cy="11" r="8"></circle>
            <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
          </svg>
          <input 
            v-model="busqueda" 
            type="text" 
            placeholder="Buscar venta..."
          />
        </div>
        <select v-model="filtroEstado" class="filter-select">
          <option value="">Todos los estados</option>
          <option value="pendiente_pago">Pendiente</option>
          <option value="parcial">Pago parcial</option>
          <option value="completada">Completada</option>
          <option value="cancelada">Cancelada</option>
        </select>
      </div>

      <!-- Loading -->
      <div v-if="loading" class="loading-state">
        <div class="loader"></div>
        <p>Cargando ventas...</p>
      </div>

      <!-- Empty -->
      <div v-else-if="ventasFiltradas.length === 0" class="empty-state">
        <div class="empty-icon">
          <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
            <circle cx="9" cy="21" r="1"></circle>
            <circle cx="20" cy="21" r="1"></circle>
            <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path>
          </svg>
        </div>
        <p>{{ busqueda || filtroEstado ? 'No se encontraron ventas' : 'No hay ventas registradas' }}</p>
        <button class="btn-empty" @click="nuevaVenta">
          <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <line x1="12" y1="5" x2="12" y2="19"></line>
            <line x1="5" y1="12" x2="19" y2="12"></line>
          </svg>
          Crear primera venta
        </button>
      </div>

      <!-- Ventas List -->
      <div v-else class="ventas-list">
        <div 
          v-for="venta in ventasFiltradas" 
          :key="venta.id" 
          class="venta-card"
          @click="verDetalle(venta)"
        >
          <div class="venta-header">
            <div class="venta-id">
              <span class="id-label">#</span>
              <span class="id-value">{{ venta.id }}</span>
            </div>
            <span :class="['estado-badge', getEstadoClass(venta.estado)]">
              {{ getEstadoLabel(venta.estado) }}
            </span>
          </div>
          
          <div class="venta-body">
            <div class="venta-cliente" v-if="venta.cliente">
              <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                <circle cx="12" cy="7" r="4"></circle>
              </svg>
              {{ venta.cliente.nombre }}
            </div>
            <div class="venta-fecha">
              <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                <line x1="16" y1="2" x2="16" y2="6"></line>
                <line x1="8" y1="2" x2="8" y2="6"></line>
                <line x1="3" y1="10" x2="21" y2="10"></line>
              </svg>
              {{ formatFecha(venta.fecha_venta) }}
            </div>
            
            <div class="venta-items" v-if="venta.detalles && venta.detalles.length > 0">
              <span class="items-count">{{ venta.detalles.length }} artículo(s)</span>
            </div>
          </div>
          
          <div class="venta-footer">
            <div class="venta-totales">
              <div class="total-row">
                <span class="total-label">Total:</span>
                <span class="total-value">${{ formatPrecio(venta.total) }}</span>
              </div>
              <div class="total-row pagado" v-if="venta.total_pagado > 0">
                <span class="total-label">Pagado:</span>
                <span class="total-value">${{ formatPrecio(venta.total_pagado) }}</span>
              </div>
              <div class="total-row pendiente" v-if="venta.saldo_pendiente > 0">
                <span class="total-label">Pendiente:</span>
                <span class="total-value">${{ formatPrecio(venta.saldo_pendiente) }}</span>
              </div>
            </div>
            
            <div class="venta-actions" @click.stop>
              <button 
                v-if="venta.estado !== 'completada' && venta.estado !== 'cancelada'"
                class="action-btn pagar" 
                @click="abrirPago(venta)"
                title="Registrar pago"
              >
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <rect x="1" y="4" width="22" height="16" rx="2" ry="2"></rect>
                  <line x1="1" y1="10" x2="23" y2="10"></line>
                </svg>
              </button>
              <button 
                v-if="venta.estado !== 'cancelada'"
                class="action-btn cancel" 
                @click="cancelarVentaConfirm(venta)"
                title="Cancelar venta"
              >
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <circle cx="12" cy="12" r="10"></circle>
                  <line x1="15" y1="9" x2="9" y2="15"></line>
                  <line x1="9" y1="9" x2="15" y2="15"></line>
                </svg>
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Modal Nueva Venta -->
    <Teleport to="body">
      <div v-if="showNuevaVentaModal" class="modal-overlay" @click.self="closeNuevaVentaModal">
        <div class="modal-content modal-fullscreen">
          <div class="modal-header">
            <h3>Nueva Venta</h3>
            <button class="modal-close" @click="closeNuevaVentaModal">
              <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <line x1="18" y1="6" x2="6" y2="18"></line>
                <line x1="6" y1="6" x2="18" y2="18"></line>
              </svg>
            </button>
          </div>
          <div class="modal-body pos-layout">
            <!-- Left: Product Search -->
            <div class="pos-productos">
              <div class="pos-search">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <circle cx="11" cy="11" r="8"></circle>
                  <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                </svg>
                <input 
                  v-model="busquedaProducto" 
                  type="text" 
                  placeholder="Buscar producto por código o nombre..."
                  @input="buscarProductos"
                />
              </div>
              
              <div class="productos-grid">
                <div 
                  v-for="p in productosDisponibles" 
                  :key="p.id" 
                  class="producto-tile"
                  @click="agregarAlCarrito(p)"
                >
                  <div class="tile-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                      <path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"></path>
                      <line x1="3" y1="6" x2="21" y2="6"></line>
                      <path d="M16 10a4 4 0 0 1-8 0"></path>
                    </svg>
                  </div>
                  <div class="tile-info">
                    <span class="tile-codigo">{{ p.codigo }}</span>
                    <span class="tile-nombre">{{ p.nombre }}</span>
                    <span class="tile-precio">${{ formatPrecio(p.precio) }}</span>
                  </div>
                  <div class="tile-stock" :class="{ bajo: p.inventario_actual <= (p.inventario_minimo || 0) }">
                    Stock: {{ p.inventario_actual || 0 }}
                  </div>
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
                    <span class="item-precio">${{ formatPrecio(item.precio_unitario) }}</span>
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

    <!-- Modal Pago -->
    <Teleport to="body">
      <div v-if="showPagoModal" class="modal-overlay" @click.self="closePagoModal">
        <div class="modal-content modal-medium">
          <div class="modal-header pago-header">
            <h3>Registrar Pago</h3>
            <button class="modal-close" @click="closePagoModal">
              <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <line x1="18" y1="6" x2="6" y2="18"></line>
                <line x1="6" y1="6" x2="18" y2="18"></line>
              </svg>
            </button>
          </div>
          <div class="modal-body">
            <div class="pago-info" v-if="pagoVenta">
              <div class="pago-venta-id">Venta #{{ pagoVenta.id }}</div>
              <div class="pago-totales">
                <div class="pt-row">
                  <span>Total de venta:</span>
                  <span class="pt-value">${{ formatPrecio(pagoVenta.total) }}</span>
                </div>
                <div class="pt-row">
                  <span>Ya pagado:</span>
                  <span class="pt-value">${{ formatPrecio(pagoVenta.total_pagado) }}</span>
                </div>
                <div class="pt-row pendiente">
                  <span>Saldo pendiente:</span>
                  <span class="pt-value">${{ formatPrecio(pagoVenta.saldo_pendiente) }}</span>
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
                  @click="pagoMetodoId = m.id; pagoEsEfectivo = m.es_efectivo"
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
                  :max="pagoVenta?.saldo_pendiente"
                  class="form-input monto-input"
                  placeholder="0.00"
                />
              </div>
              <div class="monto-quick-btns">
                <button @click="pagoMonto = pagoVenta?.saldo_pendiente || 0">Total</button>
                <button @click="pagoMonto = Math.ceil((pagoVenta?.saldo_pendiente || 0) / 100) * 100">Redondear</button>
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
              <button type="button" class="btn-cancel" @click="closePagoModal">
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
import { ref, computed, onMounted } from 'vue';
import { 
  getVentas,
  getProductos,
  createVenta,
  cancelarVenta,
  getMetodosPago,
  registrarPago
} from '@/services/inventarioService';
import Swal from 'sweetalert2';

const ventas = ref<any[]>([]);
const productos = ref<any[]>([]);
const metodosPago = ref<any[]>([]);
const loading = ref(true);
const vistaActiva = ref<'historial' | 'pendientes'>('historial');
const busqueda = ref('');
const filtroEstado = ref('');
const busquedaProducto = ref('');

// Modal states
const showNuevaVentaModal = ref(false);
const showPagoModal = ref(false);
const showDetalleModal = ref(false);

// Cart
const carrito = ref<any[]>([]);
const productosDisponibles = ref<any[]>([]);

// Pago
const pagoVenta = ref<any>(null);
const pagoMetodoId = ref<number | null>(null);
const pagoMonto = ref<number>(0);
const pagoMontoRecibido = ref<number>(0);
const pagoEsEfectivo = ref(false);

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

const subtotalCarrito = computed(() => {
  return carrito.value.reduce((sum, item) => sum + (item.cantidad * item.precio_unitario), 0);
});

const totalCarrito = computed(() => subtotalCarrito.value);

const pagoCambio = computed(() => {
  if (pagoEsEfectivo.value && pagoMontoRecibido.value > pagoMonto.value) {
    return pagoMontoRecibido.value - pagoMonto.value;
  }
  return 0;
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
    const [ventasRes, productosRes, metodosRes] = await Promise.all([
      getVentas(),
      getProductos(),
      getMetodosPago()
    ]);
    
    if (ventasRes.success) {
      ventas.value = ventasRes.data || [];
    }
    if (productosRes.success) {
      productos.value = productosRes.data || [];
      productosDisponibles.value = productos.value.filter((p: any) => p.active);
    }
    if (metodosRes.success) {
      metodosPago.value = metodosRes.data || [];
    }
  } catch (error) {
    console.error('Error cargando datos:', error);
  } finally {
    loading.value = false;
  }
}

function nuevaVenta() {
  carrito.value = [];
  busquedaProducto.value = '';
  productosDisponibles.value = productos.value.filter((p: any) => p.active);
  showNuevaVentaModal.value = true;
}

function buscarProductos() {
  const term = busquedaProducto.value.toLowerCase().trim();
  if (!term) {
    productosDisponibles.value = productos.value.filter((p: any) => p.active);
    return;
  }
  productosDisponibles.value = productos.value.filter((p: any) => 
    p.active && (
      p.codigo.toLowerCase().includes(term) ||
      p.nombre.toLowerCase().includes(term)
    )
  );
}

function agregarAlCarrito(producto: any) {
  const existing = carrito.value.find(item => item.producto_id === producto.id);
  if (existing) {
    if (existing.cantidad < (producto.inventario_actual || 0)) {
      existing.cantidad++;
    } else {
      Swal.fire({
        icon: 'warning',
        title: 'Stock insuficiente',
        text: `Solo hay ${producto.inventario_actual} unidades disponibles`,
        toast: true,
        position: 'top-end',
        showConfirmButton: false,
        timer: 2000
      });
    }
  } else {
    carrito.value.push({
      tipo: 'producto',
      producto_id: producto.id,
      nombre: producto.nombre,
      precio_unitario: producto.precio,
      cantidad: 1,
      stock_disponible: producto.inventario_actual || 0
    });
  }
}

function cambiarCantidad(index: number, delta: number) {
  const item = carrito.value[index];
  const newQty = item.cantidad + delta;
  
  if (newQty < 1) {
    quitarDelCarrito(index);
    return;
  }
  
  if (newQty > item.stock_disponible) {
    Swal.fire({
      icon: 'warning',
      title: 'Stock insuficiente',
      toast: true,
      position: 'top-end',
      showConfirmButton: false,
      timer: 2000
    });
    return;
  }
  
  item.cantidad = newQty;
}

function quitarDelCarrito(index: number) {
  carrito.value.splice(index, 1);
}

async function procesarVenta() {
  if (carrito.value.length === 0) return;
  
  try {
    const detalles = carrito.value.map(item => ({
      tipo: item.tipo,
      producto_id: item.producto_id,
      cantidad: item.cantidad,
      precio_unitario: item.precio_unitario
    }));
    
    const response = await createVenta({ detalles });
    
    if (response.success) {
      Swal.fire({
        icon: 'success',
        title: 'Venta creada',
        text: `Venta #${response.data.id} creada correctamente`,
        confirmButtonColor: '#fa709a'
      });
      
      closeNuevaVentaModal();
      await cargarDatos();
      
      // Abrir modal de pago automáticamente
      abrirPago(response.data);
    }
  } catch (error: any) {
    console.error('Error creando venta:', error);
    Swal.fire({
      icon: 'error',
      title: 'Error',
      text: error.response?.data?.message || 'Error al crear venta',
      confirmButtonColor: '#fa709a'
    });
  }
}

function abrirPago(venta: any) {
  pagoVenta.value = venta;
  pagoMetodoId.value = null;
  pagoMonto.value = venta.saldo_pendiente || venta.total;
  pagoMontoRecibido.value = 0;
  pagoEsEfectivo.value = false;
  showPagoModal.value = true;
}

async function ejecutarPago() {
  if (!pagoVenta.value || !pagoMetodoId.value || !pagoMonto.value) return;
  
  try {
    const data: any = {
      venta_id: pagoVenta.value.id,
      metodo_pago_id: pagoMetodoId.value,
      monto: pagoMonto.value
    };
    
    if (pagoEsEfectivo.value && pagoMontoRecibido.value > 0) {
      data.monto_recibido = pagoMontoRecibido.value;
    }
    
    const response = await registrarPago(data);
    
    if (response.success) {
      Swal.fire({
        icon: 'success',
        title: 'Pago registrado',
        toast: true,
        position: 'top-end',
        showConfirmButton: false,
        timer: 2000
      });
      
      closePagoModal();
      await cargarDatos();
    }
  } catch (error: any) {
    console.error('Error registrando pago:', error);
    Swal.fire({
      icon: 'error',
      title: 'Error',
      text: error.response?.data?.message || 'Error al registrar pago',
      confirmButtonColor: '#fa709a'
    });
  }
}

async function cancelarVentaConfirm(venta: any) {
  const result = await Swal.fire({
    icon: 'warning',
    title: '¿Cancelar venta?',
    text: `Se cancelará la venta #${venta.id}. Esta acción no se puede deshacer.`,
    showCancelButton: true,
    confirmButtonColor: '#ff3b30',
    cancelButtonColor: '#86868b',
    confirmButtonText: 'Cancelar venta',
    cancelButtonText: 'No, mantener'
  });
  
  if (!result.isConfirmed) return;
  
  try {
    await cancelarVenta(venta.id, 'Cancelada por administrador');
    
    Swal.fire({
      icon: 'success',
      title: 'Venta cancelada',
      toast: true,
      position: 'top-end',
      showConfirmButton: false,
      timer: 2000
    });
    
    await cargarDatos();
  } catch (error: any) {
    console.error('Error cancelando venta:', error);
    Swal.fire({
      icon: 'error',
      title: 'Error',
      text: error.response?.data?.message || 'Error al cancelar venta',
      confirmButtonColor: '#fa709a'
    });
  }
}

function verDetalle(venta: any) {
  detalleVenta.value = venta;
  showDetalleModal.value = true;
}

function closeNuevaVentaModal() {
  showNuevaVentaModal.value = false;
  carrito.value = [];
}

function closePagoModal() {
  showPagoModal.value = false;
  pagoVenta.value = null;
}

function closeDetalleModal() {
  showDetalleModal.value = false;
  detalleVenta.value = null;
}

onMounted(() => {
  cargarDatos();
});
</script>

<style scoped>
/* ===== Modern Pink/Rose Design - Ventas ===== */

.ventas-view {
  min-height: 100vh;
  background: linear-gradient(135deg, #ffeef8 0%, #fff5f8 100%);
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
  background: linear-gradient(135deg, #fa709a 0%, #fee140 100%);
  border-radius: 20px;
  color: white;
  box-shadow: 0 10px 40px rgba(250, 112, 154, 0.3);
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
}

/* View Tabs */
.view-tabs {
  display: flex;
  gap: 8px;
  margin-bottom: 16px;
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
  transition: all 0.2s ease;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
}

.view-tab.active {
  background: linear-gradient(135deg, #fa709a 0%, #fee140 100%);
  color: white;
  box-shadow: 0 4px 15px rgba(250, 112, 154, 0.4);
}

.badge {
  background: white;
  color: #fa709a;
  padding: 2px 8px;
  border-radius: 10px;
  font-size: 12px;
  font-weight: 700;
}

/* Stats Row */
.stats-row {
  display: flex;
  gap: 12px;
  margin-bottom: 16px;
}

.stat-mini {
  flex: 1;
  background: white;
  padding: 14px 16px;
  border-radius: 14px;
  box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
  text-align: center;
}

.sm-value {
  display: block;
  font-size: 20px;
  font-weight: 700;
  color: #fa709a;
}

.sm-label {
  font-size: 11px;
  color: #86868b;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

/* Content */
.content-section {
  background: white;
  border-radius: 20px;
  padding: 20px;
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
}

.filters-row {
  display: flex;
  gap: 10px;
  margin-bottom: 16px;
  flex-wrap: wrap;
}

.search-box {
  display: flex;
  align-items: center;
  gap: 10px;
  flex: 1;
  min-width: 200px;
  background: #f8f9fa;
  border-radius: 12px;
  padding: 10px 14px;
  border: 2px solid transparent;
  transition: all 0.2s ease;
}

.search-box:focus-within {
  border-color: #fa709a;
  background: white;
}

.search-box svg {
  color: #86868b;
}

.search-box input {
  flex: 1;
  border: none;
  background: none;
  font-size: 14px;
  color: #1d1d1f;
  outline: none;
}

.filter-select {
  padding: 10px 14px;
  border: 2px solid #e5e5ea;
  border-radius: 12px;
  font-size: 14px;
  color: #1d1d1f;
  background: white;
  min-width: 140px;
}

.filter-select:focus {
  outline: none;
  border-color: #fa709a;
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
  border: 3px solid #f0f0f0;
  border-top-color: #fa709a;
  border-radius: 50%;
  animation: spin 0.8s linear infinite;
  margin-bottom: 16px;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}

.empty-icon {
  color: #d1d1d6;
  margin-bottom: 16px;
}

.empty-state p {
  color: #86868b;
  margin: 0 0 16px;
}

.btn-empty {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 12px 24px;
  background: linear-gradient(135deg, #fa709a 0%, #fee140 100%);
  border: none;
  border-radius: 12px;
  color: white;
  font-size: 14px;
  font-weight: 600;
  cursor: pointer;
}

/* Ventas List */
.ventas-list {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.venta-card {
  background: #f8f9fa;
  border-radius: 16px;
  padding: 16px;
  cursor: pointer;
  transition: all 0.2s ease;
  border: 2px solid transparent;
}

.venta-card:hover {
  border-color: #fa709a;
  background: white;
}

.venta-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 12px;
}

.venta-id {
  display: flex;
  align-items: baseline;
  gap: 2px;
}

.id-label {
  font-size: 12px;
  color: #86868b;
}

.id-value {
  font-size: 18px;
  font-weight: 700;
  color: #1d1d1f;
}

.estado-badge {
  padding: 4px 10px;
  border-radius: 8px;
  font-size: 11px;
  font-weight: 600;
  text-transform: uppercase;
}

.estado-badge.completada {
  background: rgba(52, 199, 89, 0.15);
  color: #34c759;
}

.estado-badge.pendiente {
  background: rgba(255, 149, 0, 0.15);
  color: #ff9500;
}

.estado-badge.parcial {
  background: rgba(88, 86, 214, 0.15);
  color: #5856d6;
}

.estado-badge.cancelada {
  background: rgba(255, 59, 48, 0.15);
  color: #ff3b30;
}

.venta-body {
  display: flex;
  flex-wrap: wrap;
  gap: 12px;
  margin-bottom: 12px;
}

.venta-cliente,
.venta-fecha {
  display: flex;
  align-items: center;
  gap: 6px;
  font-size: 13px;
  color: #86868b;
}

.venta-items {
  font-size: 12px;
  color: #a0a0a5;
}

.venta-footer {
  display: flex;
  justify-content: space-between;
  align-items: flex-end;
  padding-top: 12px;
  border-top: 1px solid #e5e5ea;
}

.venta-totales {
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.total-row {
  display: flex;
  gap: 8px;
  font-size: 13px;
}

.total-label {
  color: #86868b;
}

.total-value {
  font-weight: 600;
  color: #1d1d1f;
}

.total-row.pendiente .total-value {
  color: #ff9500;
}

.total-row.pagado .total-value {
  color: #34c759;
}

.venta-actions {
  display: flex;
  gap: 6px;
}

.action-btn {
  width: 36px;
  height: 36px;
  border: none;
  border-radius: 10px;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all 0.2s ease;
}

.action-btn.pagar {
  background: linear-gradient(135deg, #34c759 0%, #30d158 100%);
  color: white;
}

.action-btn.cancel {
  background: rgba(255, 59, 48, 0.15);
  color: #ff3b30;
}

.action-btn:active {
  transform: scale(0.95);
}

/* Modals */
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
  border-radius: 24px 24px 0 0;
  overflow: hidden;
  animation: slideUp 0.3s cubic-bezier(0.16, 1, 0.3, 1);
}

.modal-content.modal-fullscreen {
  max-height: 95vh;
  height: 95vh;
}

.modal-content.modal-medium {
  max-height: 85vh;
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
  background: linear-gradient(135deg, #fa709a 0%, #fee140 100%);
  color: white;
}

.modal-header.pago-header {
  background: linear-gradient(135deg, #34c759 0%, #30d158 100%);
}

.modal-header.detalle-header {
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
  max-height: calc(85vh - 80px);
}

/* POS Layout */
.pos-layout {
  display: flex;
  gap: 20px;
  height: calc(95vh - 80px);
  max-height: calc(95vh - 80px);
  overflow: hidden;
  padding: 0;
}

.pos-productos {
  flex: 2;
  display: flex;
  flex-direction: column;
  padding: 20px;
  overflow: hidden;
}

.pos-search {
  display: flex;
  align-items: center;
  gap: 12px;
  background: #f5f5f7;
  border-radius: 14px;
  padding: 14px 18px;
  margin-bottom: 16px;
  border: 2px solid transparent;
  transition: all 0.2s ease;
}

.pos-search:focus-within {
  border-color: #fa709a;
  background: white;
}

.pos-search svg {
  color: #86868b;
}

.pos-search input {
  flex: 1;
  border: none;
  background: none;
  font-size: 15px;
  color: #1d1d1f;
  outline: none;
}

.productos-grid {
  flex: 1;
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(140px, 1fr));
  gap: 12px;
  overflow-y: auto;
  padding-right: 8px;
}

.producto-tile {
  background: #f8f9fa;
  border-radius: 14px;
  padding: 14px;
  cursor: pointer;
  transition: all 0.2s ease;
  border: 2px solid transparent;
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.producto-tile:hover {
  border-color: #fa709a;
  background: white;
  transform: translateY(-2px);
}

.tile-icon {
  width: 48px;
  height: 48px;
  background: linear-gradient(135deg, #fa709a 0%, #fee140 100%);
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
}

.tile-info {
  display: flex;
  flex-direction: column;
  gap: 2px;
}

.tile-codigo {
  font-size: 10px;
  font-weight: 700;
  color: #fa709a;
  text-transform: uppercase;
}

.tile-nombre {
  font-size: 13px;
  font-weight: 600;
  color: #1d1d1f;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.tile-precio {
  font-size: 14px;
  font-weight: 700;
  color: #1d1d1f;
}

.tile-stock {
  font-size: 11px;
  color: #86868b;
}

.tile-stock.bajo {
  color: #ff9500;
}

/* Carrito */
.pos-carrito {
  flex: 1;
  background: #f8f9fa;
  border-radius: 20px 0 0 0;
  display: flex;
  flex-direction: column;
  max-width: 350px;
}

.carrito-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 20px;
  border-bottom: 1px solid #e5e5ea;
}

.carrito-header h4 {
  margin: 0;
  display: flex;
  align-items: center;
  gap: 8px;
  font-size: 16px;
  color: #1d1d1f;
}

.carrito-count {
  font-size: 13px;
  color: #86868b;
}

.carrito-items {
  flex: 1;
  overflow-y: auto;
  padding: 16px;
}

.carrito-item {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 12px;
  background: white;
  border-radius: 12px;
  margin-bottom: 10px;
}

.item-info {
  flex: 1;
  min-width: 0;
}

.item-nombre {
  display: block;
  font-size: 13px;
  font-weight: 600;
  color: #1d1d1f;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.item-precio {
  font-size: 11px;
  color: #86868b;
}

.item-controls {
  display: flex;
  align-items: center;
  gap: 6px;
}

.qty-btn {
  width: 26px;
  height: 26px;
  border: none;
  border-radius: 8px;
  background: #f0f0f0;
  color: #1d1d1f;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
}

.item-qty {
  width: 24px;
  text-align: center;
  font-weight: 600;
}

.item-subtotal {
  font-size: 14px;
  font-weight: 700;
  color: #fa709a;
}

.item-remove {
  width: 26px;
  height: 26px;
  border: none;
  border-radius: 8px;
  background: rgba(255, 59, 48, 0.1);
  color: #ff3b30;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
}

.carrito-empty {
  flex: 1;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  color: #c7c7cc;
  gap: 12px;
}

.carrito-totales {
  padding: 16px 20px;
  border-top: 1px solid #e5e5ea;
  background: white;
}

.total-line {
  display: flex;
  justify-content: space-between;
  font-size: 14px;
  color: #86868b;
  margin-bottom: 8px;
}

.total-line.total-final {
  font-size: 18px;
  font-weight: 700;
  color: #1d1d1f;
  margin-bottom: 0;
}

.total-line.total-final span:last-child {
  color: #fa709a;
}

.carrito-actions {
  padding: 16px 20px;
}

.btn-venta {
  width: 100%;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  padding: 16px;
  background: linear-gradient(135deg, #fa709a 0%, #fee140 100%);
  border: none;
  border-radius: 14px;
  color: white;
  font-size: 16px;
  font-weight: 700;
  cursor: pointer;
  transition: all 0.2s ease;
}

.btn-venta:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.btn-venta:not(:disabled):active {
  transform: scale(0.98);
}

/* Pago Modal */
.pago-info {
  text-align: center;
  padding: 16px;
  background: #f5f5f7;
  border-radius: 14px;
  margin-bottom: 20px;
}

.pago-venta-id {
  font-size: 14px;
  color: #86868b;
  margin-bottom: 12px;
}

.pago-totales {
  display: flex;
  flex-direction: column;
  gap: 6px;
}

.pt-row {
  display: flex;
  justify-content: space-between;
  font-size: 14px;
  color: #86868b;
}

.pt-value {
  font-weight: 600;
  color: #1d1d1f;
}

.pt-row.pendiente {
  font-size: 16px;
}

.pt-row.pendiente .pt-value {
  color: #ff9500;
  font-size: 18px;
  font-weight: 700;
}

.form-group {
  margin-bottom: 18px;
}

.form-label {
  display: block;
  font-size: 12px;
  font-weight: 600;
  color: #34c759;
  margin-bottom: 10px;
  text-transform: uppercase;
  letter-spacing: 0.5px;
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
  gap: 6px;
  padding: 14px;
  background: #f5f5f7;
  border: 2px solid transparent;
  border-radius: 12px;
  font-size: 13px;
  font-weight: 600;
  color: #86868b;
  cursor: pointer;
  transition: all 0.2s ease;
}

.metodo-btn.active {
  background: white;
  border-color: #34c759;
  color: #34c759;
}

.monto-input-container {
  display: flex;
  align-items: center;
  background: #f5f5f7;
  border-radius: 14px;
  border: 2px solid transparent;
  transition: all 0.2s ease;
}

.monto-input-container:focus-within {
  border-color: #34c759;
  background: white;
}

.monto-prefix {
  padding: 14px 16px;
  font-size: 20px;
  font-weight: 700;
  color: #86868b;
}

.monto-input {
  flex: 1;
  border: none;
  background: none;
  font-size: 24px;
  font-weight: 700;
  color: #1d1d1f;
  outline: none;
  padding: 14px 16px 14px 0;
}

.monto-quick-btns {
  display: flex;
  gap: 8px;
  margin-top: 10px;
}

.monto-quick-btns button {
  padding: 8px 16px;
  background: #e5e5ea;
  border: none;
  border-radius: 8px;
  font-size: 12px;
  font-weight: 600;
  color: #1d1d1f;
  cursor: pointer;
}

.cambio-display {
  margin-top: 12px;
  padding: 12px;
  background: rgba(52, 199, 89, 0.1);
  border-radius: 10px;
  text-align: center;
  font-size: 14px;
  color: #34c759;
}

.cambio-display strong {
  font-size: 18px;
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
  background: linear-gradient(135deg, #34c759 0%, #30d158 100%);
  color: white;
}

.btn-submit:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

/* Detalle Modal */
.detalle-info {
  background: #f5f5f7;
  border-radius: 14px;
  padding: 16px;
  margin-bottom: 20px;
}

.di-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 8px;
}

.di-row:last-child {
  margin-bottom: 0;
}

.di-label {
  font-size: 13px;
  color: #86868b;
}

.di-value {
  font-size: 14px;
  font-weight: 600;
  color: #1d1d1f;
}

.detalle-items h4 {
  font-size: 14px;
  color: #5856d6;
  margin: 0 0 12px;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.items-list {
  background: #f5f5f7;
  border-radius: 12px;
  overflow: hidden;
}

.item-row {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 12px;
  border-bottom: 1px solid #e5e5ea;
}

.item-row:last-child {
  border-bottom: none;
}

.ir-info {
  flex: 1;
  min-width: 0;
}

.ir-nombre {
  display: block;
  font-size: 13px;
  font-weight: 600;
  color: #1d1d1f;
}

.ir-tipo {
  font-size: 11px;
  color: #86868b;
  text-transform: capitalize;
}

.ir-qty,
.ir-precio {
  font-size: 13px;
  color: #86868b;
}

.ir-subtotal {
  font-size: 14px;
  font-weight: 700;
  color: #1d1d1f;
}

.detalle-totales {
  margin-top: 20px;
  padding-top: 16px;
  border-top: 1px solid #e5e5ea;
}

.dt-row {
  display: flex;
  justify-content: space-between;
  font-size: 14px;
  color: #86868b;
  margin-bottom: 8px;
}

.dt-row.total {
  font-size: 18px;
  font-weight: 700;
  color: #1d1d1f;
}

.dt-row.pagado span:last-child {
  color: #34c759;
}

.dt-row.pendiente span:last-child {
  color: #ff9500;
}

.detalle-actions {
  margin-top: 20px;
}

.btn-pagar-detalle {
  width: 100%;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  padding: 14px;
  background: linear-gradient(135deg, #34c759 0%, #30d158 100%);
  border: none;
  border-radius: 14px;
  color: white;
  font-size: 15px;
  font-weight: 600;
  cursor: pointer;
}

/* Responsive */
@media (max-width: 768px) {
  .pos-layout {
    flex-direction: column;
  }
  
  .pos-productos {
    flex: none;
    height: 50%;
  }
  
  .pos-carrito {
    flex: none;
    max-width: 100%;
    border-radius: 20px 20px 0 0;
  }
  
  .productos-grid {
    grid-template-columns: repeat(auto-fill, minmax(100px, 1fr));
  }
}
</style>


