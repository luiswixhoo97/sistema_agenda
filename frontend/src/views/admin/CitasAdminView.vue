<template>
  <div class="citas-view">
    <!-- Header -->
    <header class="citas-header">
      <div class="header-left">
        <div class="header-icon">
          <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
            <line x1="16" y1="2" x2="16" y2="6"></line>
            <line x1="8" y1="2" x2="8" y2="6"></line>
            <line x1="3" y1="10" x2="21" y2="10"></line>
          </svg>
        </div>
        <div class="header-text">
          <h1>Citas</h1>
          <p class="header-subtitle">{{ totalCitas }} registradas</p>
        </div>
      </div>
      <button class="btn-new-cita" @click="nuevaCita">
        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
          <line x1="12" y1="5" x2="12" y2="19"></line>
          <line x1="5" y1="12" x2="19" y2="12"></line>
        </svg>
        Nueva cita
      </button>
    </header>

    <!-- Filters -->
    <section class="filters-section">
      <div class="search-container">
        <div class="search-icon">
          <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <circle cx="11" cy="11" r="8"></circle>
            <path d="m21 21-4.35-4.35"></path>
          </svg>
        </div>
        <input 
          v-model="busqueda" 
          type="text" 
          class="search-input"
          placeholder="Buscar cliente..." 
          @input="debouncedSearch"
        />
      </div>
      <div class="filters-row">
        <select 
          v-model="filtroEstado" 
          @change="() => cargarCitas()" 
          @focus="onSelectFocus"
          class="filter-select"
          ref="selectEstadoRef"
        >
          <option value="">Todos los estados</option>
          <option value="pendiente">Pendiente</option>
          <option value="confirmada">Confirmada</option>
          <option value="en_proceso">En proceso</option>
          <option value="completada">Completada</option>
          <option value="cancelada">Cancelada</option>
          <option value="no_show">No Show</option>
          <option value="reagendada">Reagendada</option>
        </select>
        <input 
          v-model="filtroFecha" 
          type="date" 
          class="filter-date"
          @change="() => cargarCitas()"
          @focus="onDateFocus"
          ref="dateInputRef"
        />
      </div>
    </section>

    <!-- Citas List -->
    <div class="citas-container">
      <div v-if="loading" class="loading-container">
        <div class="loader"></div>
        <p>Cargando citas...</p>
      </div>
      
      <div v-else-if="citas.length === 0" class="empty-state">
        <div class="empty-icon">
          <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
            <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
            <line x1="16" y1="2" x2="16" y2="6"></line>
            <line x1="8" y1="2" x2="8" y2="6"></line>
            <line x1="3" y1="10" x2="21" y2="10"></line>
          </svg>
        </div>
        <p>No hay citas que mostrar</p>
      </div>
      
      <div v-else class="citas-list">
        <div 
          v-for="cita in citas" 
          :key="cita.id" 
          class="cita-card"
          @click="verCita(cita)"
        >
          <div class="cita-card-body">
            <div class="cita-time-row">
              <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="12" cy="12" r="10"></circle>
                <polyline points="12 6 12 12 16 14"></polyline>
              </svg>
              <span class="cita-time">{{ formatFecha(cita.fecha_hora) }}</span>
              <span :class="['cita-status', cita.estado]">
                {{ estadoLabel(cita.estado) }}
              </span>
            </div>
            
            <div class="cita-info-grid">
              <div class="cita-info-item">
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                  <circle cx="12" cy="7" r="4"></circle>
                </svg>
                <span class="cita-info-label">Cliente</span>
                <span class="cita-info-value">{{ cita.cliente?.nombre || 'Sin cliente' }}</span>
              </div>
              
              <div class="cita-info-item">
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                  <circle cx="9" cy="7" r="4"></circle>
                  <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                  <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                </svg>
                <span class="cita-info-label">Empleado</span>
                <span class="cita-info-value">{{ cita.empleado?.nombre || 'Sin asignar' }}</span>
              </div>
            </div>
            
            <div class="cita-servicios-row">
              <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"></path>
              </svg>
              <span class="cita-servicios-text">{{ getServiciosNombres(cita) }}</span>
            </div>
          </div>
          
          <div class="cita-card-footer">
            <div class="cita-price">
              <span class="price-label">Total</span>
              <span class="price-value">${{ formatPrecio(cita.precio_final || cita.precio_total) }}</span>
            </div>
            <div class="cita-actions">
              <button class="action-btn" @click.stop="editarCita(cita)" title="Ver detalle">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                  <circle cx="12" cy="12" r="3"></circle>
                </svg>
              </button>
              <button 
                v-if="puedeReagendar(cita)" 
                class="action-btn reagendar" 
                @click.stop="abrirModalReagendar(cita)" 
                title="Reagendar"
              >
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                  <line x1="16" y1="2" x2="16" y2="6"></line>
                  <line x1="8" y1="2" x2="8" y2="6"></line>
                  <line x1="3" y1="10" x2="21" y2="10"></line>
                </svg>
              </button>
              <button 
                v-if="cita.estado !== 'cancelada' && cita.estado !== 'completada'" 
                class="action-btn danger" 
                @click.stop="confirmarCancelacion(cita)" 
                title="Cancelar"
              >
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <line x1="18" y1="6" x2="6" y2="18"></line>
                  <line x1="6" y1="6" x2="18" y2="18"></line>
                </svg>
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- Pagination -->
      <div v-if="pagination.last_page > 1" class="pagination">
        <button 
          class="page-btn" 
          :disabled="pagination.current_page === 1"
          @click="cambiarPagina(pagination.current_page - 1)"
        >
          <i class="fa fa-chevron-left"></i>
        </button>
        <span class="page-info">{{ pagination.current_page }} / {{ pagination.last_page }}</span>
        <button 
          class="page-btn" 
          :disabled="pagination.current_page === pagination.last_page"
          @click="cambiarPagina(pagination.current_page + 1)"
        >
          <i class="fa fa-chevron-right"></i>
        </button>
      </div>
    </div>

    <!-- Modal Ver/Editar Cita -->
    <Teleport to="body">
      <div v-if="showModal" class="modal-overlay" @click.self="closeModal">
        <div class="modal-content">
          <div class="modal-header">
            <h3>{{ editingCita ? 'Detalle de Cita' : 'Nueva Cita' }}</h3>
            <button class="modal-close" @click="closeModal">
              <i class="fa fa-times"></i>
            </button>
          </div>
          <div class="modal-body" v-if="editingCita">
            <!-- Vista Detalle -->
            <div class="cita-detail">
              <!-- Header con ID y Estado -->
              <div class="detail-header">
                <div class="detail-id">
                  <span class="id-label">Cita #</span>
                  <span class="id-value">{{ editingCita.id }}</span>
                </div>
                <span :class="['status-badge-large', editingCita.estado]">
                  {{ estadoLabel(editingCita.estado) }}
                </span>
              </div>

              <!-- Información Principal -->
              <div class="detail-section">
                <div class="section-title">
                  <i class="fa fa-calendar-alt"></i>
                  <span>Fecha y Hora</span>
                </div>
                <div class="info-block">
                  <div class="info-item">
                    <i class="fa fa-clock"></i>
                    <div class="info-content">
                      <span class="info-label">Fecha</span>
                      <span class="info-value">{{ formatFechaCompleta(editingCita.fecha_hora) }}</span>
                    </div>
                  </div>
                  <div class="info-item">
                    <i class="fa fa-hourglass-half"></i>
                    <div class="info-content">
                      <span class="info-label">Duración estimada</span>
                      <span class="info-value">{{ getDuracionTotal(editingCita) }} minutos</span>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Cliente -->
              <div class="detail-section">
                <div class="section-title">
                  <i class="fa fa-user"></i>
                  <span>Cliente</span>
                </div>
                <div class="info-block">
                  <div class="info-item">
                    <div class="info-content">
                      <span class="info-label">Nombre</span>
                      <span class="info-value">{{ editingCita.cliente?.nombre || 'Sin cliente' }}</span>
                    </div>
                  </div>
                  <div class="info-item" v-if="editingCita.cliente?.telefono">
                    <i class="fa fa-phone"></i>
                    <div class="info-content">
                      <span class="info-label">Teléfono</span>
                      <span class="info-value">
                        <a :href="`tel:${editingCita.cliente.telefono}`" class="phone-link">
                          {{ editingCita.cliente.telefono }}
                        </a>
                      </span>
                    </div>
                  </div>
                  <div class="info-item" v-if="editingCita.cliente?.email">
                    <i class="fa fa-envelope"></i>
                    <div class="info-content">
                      <span class="info-label">Email</span>
                      <span class="info-value">{{ editingCita.cliente.email }}</span>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Empleado -->
              <div class="detail-section">
                <div class="section-title">
                  <i class="fa fa-user-tie"></i>
                  <span>Empleado</span>
                </div>
                <div class="info-block">
                  <div class="info-item">
                    <div class="info-content">
                      <span class="info-label">Asignado a</span>
                      <span class="info-value">{{ editingCita.empleado?.nombre || 'Sin asignar' }}</span>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Servicios -->
              <div class="detail-section">
                <div class="section-title">
                  <i class="fa fa-cut"></i>
                  <span>Servicios</span>
                </div>
                <div class="servicios-list">
                  <div 
                    v-for="(servicio, index) in getServiciosList(editingCita)" 
                    :key="index"
                    class="servicio-item"
                  >
                    <div class="servicio-info">
                      <span class="servicio-nombre">{{ servicio.nombre }}</span>
                      <span class="servicio-duracion">{{ servicio.duracion || servicio.duracion_minutos }} min</span>
                    </div>
                    <span class="servicio-precio">${{ formatPrecio(servicio.precio || servicio.precio_aplicado) }}</span>
                  </div>
                </div>
              </div>

              <!-- Precio Total -->
              <div class="detail-section total-section">
                <div class="total-row">
                  <span class="total-label">Total</span>
                  <span class="total-value">${{ formatPrecio(editingCita.precio_final || editingCita.precio_total) }}</span>
                </div>
              </div>

              <!-- Notas -->
              <div class="detail-section" v-if="editingCita.notas">
                <div class="section-title">
                  <i class="fa fa-sticky-note"></i>
                  <span>Notas</span>
                </div>
                <div class="notas-content">
                  {{ editingCita.notas }}
                </div>
              </div>

              <!-- Acciones -->
              <div class="detail-actions">
                <button 
                  v-if="editingCita.estado !== 'cancelada' && editingCita.estado !== 'completada'"
                  class="btn-action-primary"
                  @click="cambiarEstado(editingCita, 'completada')"
                >
                  <i class="fa fa-check"></i>
                  Marcar como Completada
                </button>
                <button 
                  v-if="editingCita.estado === 'pendiente' || editingCita.estado === 'confirmada'"
                  class="btn-action-secondary"
                  @click="cambiarEstado(editingCita, 'en_proceso')"
                >
                  <i class="fa fa-play"></i>
                  Iniciar Cita
                </button>
                <button 
                  v-if="puedeReagendar(editingCita)"
                  class="btn-action-reagendar"
                  @click="abrirModalReagendar(editingCita)"
                >
                  <i class="fa fa-calendar-alt"></i>
                  Reagendar Cita
                </button>
                <button 
                  v-if="editingCita.estado !== 'cancelada' && editingCita.estado !== 'completada'"
                  class="btn-action-danger"
                  @click="confirmarCancelacion(editingCita)"
                >
                  <i class="fa fa-times"></i>
                  Cancelar Cita
                </button>
              </div>
            </div>
          </div>
          <div v-else class="modal-body">
            <!-- Formulario Nueva Cita -->
            <form @submit.prevent="guardarCita" class="cita-form">
              <!-- Cliente -->
              <div class="form-section">
                <label class="form-label">
                  <i class="fa fa-user"></i>
                  Cliente <span class="required">*</span>
                </label>
                <div class="form-row">
                  <div class="search-client-wrapper">
                    <div class="search-client-input">
                      <i class="fa fa-search"></i>
                      <input 
                        v-model="busquedaCliente"
                        type="text"
                        class="form-input search-input"
                        placeholder="Buscar por nombre o teléfono..."
                        @input="onBusquedaCliente"
                      />
                      <button 
                        v-if="busquedaCliente"
                        type="button"
                        class="btn-clear-search"
                        @click="limpiarBusquedaCliente"
                      >
                        <i class="fa fa-times"></i>
                      </button>
                    </div>
                    <div v-if="busquedaCliente && clientesFiltrados.length > 0" class="clientes-dropdown">
                      <div 
                        v-for="cliente in clientesFiltrados" 
                        :key="cliente.id"
                        class="cliente-option"
                        :class="{ selected: nuevaCitaData.cliente_id == cliente.id }"
                        @click="seleccionarCliente(cliente)"
                      >
                        <div class="cliente-info">
                          <span class="cliente-nombre">{{ cliente.nombre }} {{ cliente.apellido || '' }}</span>
                          <span class="cliente-telefono" v-if="cliente.telefono">
                            <i class="fa fa-phone"></i> {{ cliente.telefono }}
                          </span>
                        </div>
                        <i v-if="nuevaCitaData.cliente_id == cliente.id" class="fa fa-check"></i>
                      </div>
                    </div>
                    <div v-if="busquedaCliente && clientesFiltrados.length === 0" class="clientes-dropdown empty">
                      <div class="no-results">
                        <i class="fa fa-user-times"></i>
                        <p>No se encontraron clientes</p>
                        <button 
                          type="button"
                          class="btn-create-client"
                          @click="mostrarCamposNuevoCliente = true"
                        >
                          <i class="fa fa-plus"></i> Crear nuevo cliente
                        </button>
                      </div>
                    </div>
                  </div>
                  <button 
                    type="button" 
                    class="btn-add-client"
                    @click="mostrarCamposNuevoCliente = !mostrarCamposNuevoCliente"
                    :class="{ active: mostrarCamposNuevoCliente }"
                    title="Agregar nuevo cliente"
                  >
                    <i class="fa" :class="mostrarCamposNuevoCliente ? 'fa-times' : 'fa-plus'"></i>
                  </button>
                </div>
                
                <!-- Campos para nuevo cliente (inline) -->
                <Transition name="slide-down">
                  <div v-if="mostrarCamposNuevoCliente" class="nuevo-cliente-fields">
                    <div class="nuevo-cliente-header">
                      <h4>
                        <i class="fa fa-user-plus"></i>
                        Nuevo Cliente
                      </h4>
                    </div>
                    <div class="form-row">
                      <div class="form-group">
                        <label class="form-label-small">
                          Nombre <span class="required">*</span>
                        </label>
                        <input 
                          v-model="nuevoClienteData.nombre" 
                          type="text" 
                          class="form-input"
                          placeholder="Juan"
                        />
                      </div>
                      <div class="form-group">
                        <label class="form-label-small">
                          Apellido
                        </label>
                        <input 
                          v-model="nuevoClienteData.apellido" 
                          type="text" 
                          class="form-input"
                          placeholder="Pérez"
                        />
                      </div>
                    </div>
                    <div class="form-row">
                      <div class="form-group">
                        <label class="form-label-small">
                          Teléfono <span class="required">*</span>
                        </label>
                        <input 
                          v-model="nuevoClienteData.telefono" 
                          type="tel" 
                          class="form-input"
                          placeholder="5512345678"
                          maxlength="10"
                        />
                      </div>
                      <div class="form-group">
                        <label class="form-label-small">
                          Email
                        </label>
                        <input 
                          v-model="nuevoClienteData.email" 
                          type="email" 
                          class="form-input"
                          placeholder="cliente@email.com"
                        />
                      </div>
                    </div>
                    <div class="nuevo-cliente-actions">
                      <button 
                        type="button"
                        class="btn-cancel-small"
                        @click="cancelarNuevoCliente"
                      >
                        Cancelar
                      </button>
                      <button 
                        type="button"
                        class="btn-submit-small"
                        @click="guardarCliente"
                        :disabled="!nuevoClienteData.nombre || !nuevoClienteData.telefono"
                      >
                        <i class="fa fa-save"></i>
                        Guardar Cliente
                      </button>
                    </div>
                  </div>
                </Transition>
                
                <div v-if="clienteSeleccionado" class="cliente-selected">
                  <div class="selected-info">
                    <i class="fa fa-check-circle"></i>
                    <span>{{ clienteSeleccionado.nombre }} {{ clienteSeleccionado.apellido || '' }}</span>
                    <span v-if="clienteSeleccionado.telefono" class="selected-phone">{{ clienteSeleccionado.telefono }}</span>
                  </div>
                  <button 
                    type="button"
                    class="btn-remove-selection"
                    @click="deseleccionarCliente"
                  >
                    <i class="fa fa-times"></i>
                  </button>
                </div>
              </div>

              <!-- Empleado -->
              <div class="form-section">
                <label class="form-label">
                  <i class="fa fa-user-tie"></i>
                  Empleado <span class="required">*</span>
                </label>
                <select 
                  v-model="nuevaCitaData.empleado_id" 
                  class="form-select"
                  required
                  :disabled="!nuevaCitaData.cliente_id"
                  @change="onEmpleadoChange"
                >
                  <option value="">Seleccionar empleado...</option>
                  <option v-for="empleado in empleados" :key="empleado.id" :value="empleado.id">
                    {{ empleado.nombre }} {{ empleado.apellido || '' }}
                  </option>
                </select>
                <div v-if="!nuevaCitaData.cliente_id" class="form-hint">
                  <i class="fa fa-info-circle"></i>
                  Primero selecciona un cliente
                </div>
              </div>

              <!-- Servicios -->
              <div class="form-section">
                <label class="form-label">
                  <i class="fa fa-cut"></i>
                  Servicios <span class="required">*</span>
                </label>
                <div v-if="!nuevaCitaData.empleado_id" class="servicios-disabled">
                  <div class="disabled-message">
                    <i class="fa fa-info-circle"></i>
                    <p>Selecciona un empleado para ver sus servicios disponibles</p>
                  </div>
                </div>
                <div v-else-if="cargandoServiciosEmpleado" class="servicios-loading">
                  <div class="loading-message">
                    <i class="fa fa-spinner fa-spin"></i>
                    <p>Cargando servicios del empleado...</p>
                  </div>
                </div>
                <div v-else-if="serviciosEmpleado.length === 0" class="servicios-empty">
                  <div class="empty-message">
                    <i class="fa fa-exclamation-triangle"></i>
                    <p>Este empleado no tiene servicios asignados</p>
                  </div>
                </div>
                <div v-else class="servicios-selector">
                  <div 
                    v-for="servicio in serviciosEmpleado" 
                    :key="servicio.id"
                    class="servicio-option"
                    :class="{ selected: isServicioSelected(servicio.id) }"
                    @click="toggleServicio(servicio.id)"
                  >
                    <div class="servicio-checkbox">
                      <i class="fa fa-check" v-if="isServicioSelected(servicio.id)"></i>
                    </div>
                    <div class="servicio-info">
                      <span class="servicio-name">{{ servicio.nombre }}</span>
                      <span class="servicio-details" v-if="getDuracionServicio(servicio) > 0 || getPrecioServicio(servicio) > 0">
                        {{ getDuracionServicio(servicio) }} min • 
                        ${{ formatPrecio(getPrecioServicio(servicio)) }}
                      </span>
                      <span class="servicio-details" v-else style="color: #999; font-style: italic;">
                        Sin información de precio/duración
                      </span>
                    </div>
                  </div>
                </div>
                <div v-if="serviciosSeleccionados.length === 0 && nuevaCitaData.empleado_id && serviciosEmpleado.length > 0" class="form-error">
                  Debes seleccionar al menos un servicio
                </div>
              </div>

              <!-- Fecha y Hora -->
              <div class="form-section">
                <label class="form-label">
                  <i class="fa fa-calendar-alt"></i>
                  Fecha y Hora <span class="required">*</span>
                </label>
                <div class="fecha-hora-container">
                  <input 
                    v-model="nuevaCitaData.fecha" 
                    type="date" 
                    class="form-input"
                    required
                    :min="minDate"
                    :disabled="!nuevaCitaData.empleado_id || serviciosSeleccionados.length === 0"
                    @change="cargarHorariosDisponibles"
                  />
                  <div class="hora-select-wrapper">
                    <select 
                      v-model="nuevaCitaData.hora" 
                      class="form-select"
                      required
                      :disabled="!nuevaCitaData.fecha || !nuevaCitaData.empleado_id || serviciosSeleccionados.length === 0 || cargandoHorarios"
                    >
                      <option value="">
                        {{ getHoraPlaceholder() }}
                      </option>
                      <option 
                        v-for="slot in horariosDisponibles" 
                        :key="slot.hora || slot.hora_inicio"
                        :value="slot.hora || slot.hora_inicio"
                      >
                        {{ formatHora(slot.hora || slot.hora_inicio) }}
                      </option>
                    </select>
                    <div v-if="cargandoHorarios" class="loading-horarios">
                      <i class="fa fa-spinner fa-spin"></i>
                    </div>
                  </div>
                </div>
                <div v-if="!nuevaCitaData.empleado_id" class="form-hint">
                  <i class="fa fa-info-circle"></i>
                  Selecciona un empleado para habilitar la fecha
                </div>
                <div v-if="nuevaCitaData.empleado_id && serviciosSeleccionados.length === 0" class="form-hint">
                  <i class="fa fa-info-circle"></i>
                  Selecciona al menos un servicio para habilitar la fecha
                </div>
                <div v-if="!nuevaCitaData.fecha && nuevaCitaData.empleado_id && serviciosSeleccionados.length > 0" class="form-hint">
                  <i class="fa fa-info-circle"></i>
                  Selecciona una fecha para ver los horarios disponibles
                </div>
                <div v-if="horariosDisponibles.length === 0 && nuevaCitaData.fecha && nuevaCitaData.empleado_id && serviciosSeleccionados.length > 0 && !cargandoHorarios" class="form-error">
                  <i class="fa fa-exclamation-triangle"></i>
                  No hay horarios disponibles para esta fecha
                </div>
              </div>

              <!-- Precio Total -->
              <div class="form-section total-preview">
                <div class="total-row">
                  <span class="total-label">Total estimado:</span>
                  <span class="total-value">${{ formatPrecio(precioTotal) }}</span>
                </div>
                <div class="total-duration">
                  <i class="fa fa-clock"></i>
                  Duración: {{ duracionTotal }} minutos
                </div>
              </div>

              <!-- Notas -->
              <div class="form-section">
                <label class="form-label">
                  <i class="fa fa-sticky-note"></i>
                  Notas <span class="optional">(opcional)</span>
                </label>
                <textarea 
                  v-model="nuevaCitaData.notas" 
                  class="form-textarea"
                  placeholder="Agregar notas adicionales sobre la cita..."
                  rows="3"
                ></textarea>
              </div>

              <!-- Estado -->
              <div class="form-section">
                <label class="form-label">
                  <i class="fa fa-info-circle"></i>
                  Estado inicial
                </label>
                <select v-model="nuevaCitaData.estado" class="form-select">
                  <option value="pendiente">Pendiente</option>
                  <option value="confirmada">Confirmada</option>
                </select>
              </div>

              <!-- Botones -->
              <div class="form-actions">
                <button type="button" class="btn-cancel" @click="closeModal">
                  Cancelar
                </button>
                <button type="submit" class="btn-submit" :disabled="guardando">
                  <i class="fa fa-save"></i>
                  {{ guardando ? 'Guardando...' : 'Guardar Cita' }}
                </button>
              </div>
            </form>
          </div>
        </div>
      </div>
    </Teleport>
    <!-- Modal Reagendar Cita -->
    <Teleport to="body">
      <div v-if="showModalReagendar" class="modal-overlay" @click.self="cerrarModalReagendar">
        <div class="modal-content">
          <div class="modal-header">
            <h3>Reagendar Cita #{{ citaAReagendar?.id }}</h3>
            <button class="modal-close" @click="cerrarModalReagendar">
              <i class="fa fa-times"></i>
            </button>
          </div>
          <div class="modal-body">
            <div class="reagendar-info">
              <div class="info-actual">
                <h4>Fecha y hora actual</h4>
                <div class="fecha-actual">
                  <i class="fa fa-calendar"></i>
                  <span>{{ formatFechaCompleta(citaAReagendar?.fecha_hora) }}</span>
                </div>
              </div>
              
              <div class="info-cliente">
                <span><i class="fa fa-user"></i> {{ citaAReagendar?.cliente?.nombre }}</span>
                <span><i class="fa fa-user-tie"></i> {{ citaAReagendar?.empleado?.nombre }}</span>
              </div>
            </div>

            <div class="reagendar-form">
              <div class="form-section">
                <label class="form-label">
                  <i class="fa fa-calendar-alt"></i>
                  Nueva Fecha <span class="required">*</span>
                </label>
                <input 
                  v-model="reagendarData.fecha" 
                  type="date" 
                  class="form-input"
                  :min="minDateReagendar"
                  @change="cargarHorariosReagendar"
                />
              </div>

              <div class="form-section">
                <label class="form-label">
                  <i class="fa fa-clock"></i>
                  Nueva Hora <span class="required">*</span>
                </label>
                <div class="hora-select-wrapper">
                  <select 
                    v-model="reagendarData.hora" 
                    class="form-select"
                    :disabled="!reagendarData.fecha || cargandoHorariosReagendar"
                  >
                    <option value="">
                      {{ getPlaceholderHoraReagendar() }}
                    </option>
                    <option 
                      v-for="slot in horariosReagendar" 
                      :key="slot.hora"
                      :value="slot.hora"
                    >
                      {{ formatHora(slot.hora) }}
                    </option>
                  </select>
                  <div v-if="cargandoHorariosReagendar" class="loading-horarios">
                    <i class="fa fa-spinner fa-spin"></i>
                  </div>
                </div>
              </div>

              <div class="form-section">
                <label class="form-label">
                  <i class="fa fa-sticky-note"></i>
                  Motivo del cambio <span class="optional">(opcional)</span>
                </label>
                <textarea 
                  v-model="reagendarData.motivo" 
                  class="form-textarea"
                  placeholder="Indicar motivo del reagendamiento..."
                  rows="2"
                ></textarea>
              </div>
            </div>

            <div class="form-actions">
              <button type="button" class="btn-cancel" @click="cerrarModalReagendar">
                Cancelar
              </button>
              <button 
                type="button" 
                class="btn-submit" 
                :disabled="!puedeConfirmarReagendar || guardandoReagendar"
                @click="confirmarReagendar"
              >
                <i class="fa fa-calendar-check"></i>
                {{ guardandoReagendar ? 'Reagendando...' : 'Confirmar Reagendamiento' }}
              </button>
            </div>
          </div>
        </div>
      </div>
    </Teleport>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, watch, nextTick } from 'vue';
import { 
  getCitas, 
  updateCita, 
  createCita,
  getClientes,
  getEmpleados,
  getServicios,
  createCliente,
  getEmpleadoServicios,
  reagendarCitaAdmin,
  cancelarCitaAdmin
} from '@/services/adminService';
import disponibilidadService from '@/services/disponibilidadService';
import Swal from 'sweetalert2';

const citas = ref<any[]>([]);
const busqueda = ref('');
const filtroEstado = ref('');
// Inicializar con la fecha de hoy
const hoy = new Date();
const fechaHoy = hoy.toISOString().split('T')[0];
const filtroFecha = ref(fechaHoy);
const loading = ref(true);
const showModal = ref(false);
const editingCita = ref<any>(null);
const pagination = ref({
  current_page: 1,
  last_page: 1,
  total: 0,
});

// Refs para los elementos de filtro
const selectEstadoRef = ref<HTMLSelectElement | null>(null);
const dateInputRef = ref<HTMLInputElement | null>(null);

// Datos para formulario de nueva cita
const clientes = ref<any[]>([]);
const empleados = ref<any[]>([]);
const servicios = ref<any[]>([]);
const serviciosSeleccionados = ref<number[]>([]);
const guardando = ref(false);
const mostrarCamposNuevoCliente = ref(false);
const busquedaCliente = ref('');
const horariosDisponibles = ref<any[]>([]);
const cargandoHorarios = ref(false);
const serviciosEmpleado = ref<any[]>([]);
const cargandoServiciosEmpleado = ref(false);

const nuevaCitaData = ref({
  cliente_id: '',
  empleado_id: '',
  fecha: '',
  hora: '',
  estado: 'pendiente',
  notas: '',
});

const nuevoClienteData = ref({
  nombre: '',
  apellido: '',
  telefono: '',
  email: '',
});

// Reagendar cita
const showModalReagendar = ref(false);
const citaAReagendar = ref<any>(null);
const horariosReagendar = ref<any[]>([]);
const cargandoHorariosReagendar = ref(false);
const guardandoReagendar = ref(false);
const reagendarData = ref({
  fecha: '',
  hora: '',
  motivo: '',
});

let searchTimeout: ReturnType<typeof setTimeout>;

const totalCitas = computed(() => pagination.value.total);

const minDate = computed(() => {
  const today = new Date();
  return today.toISOString().split('T')[0];
});

const minDateReagendar = computed(() => {
  const today = new Date();
  return today.toISOString().split('T')[0];
});

const puedeConfirmarReagendar = computed(() => {
  return reagendarData.value.fecha && reagendarData.value.hora;
});

const precioTotal = computed(() => {
  return serviciosSeleccionados.value.reduce((total, servicioId) => {
    const servicio = serviciosEmpleado.value.find(s => s.id === servicioId);
    if (!servicio) return total;
    return total + getPrecioServicio(servicio);
  }, 0);
});

const duracionTotal = computed(() => {
  return serviciosSeleccionados.value.reduce((total, servicioId) => {
    const servicio = serviciosEmpleado.value.find(s => s.id === servicioId);
    if (!servicio) return total;
    return total + getDuracionServicio(servicio);
  }, 0);
});

const clientesFiltrados = computed(() => {
  if (!busquedaCliente.value.trim()) {
    return [];
  }
  
  const busqueda = busquedaCliente.value.toLowerCase().trim();
  
  return clientes.value.filter(cliente => {
    const nombre = (cliente.nombre || '').toLowerCase();
    const apellido = (cliente.apellido || '').toLowerCase();
    const telefono = (cliente.telefono || '').toString();
    const nombreCompleto = `${nombre} ${apellido}`.trim();
    
    return nombreCompleto.includes(busqueda) || 
           telefono.includes(busqueda) ||
           nombre.includes(busqueda) ||
           apellido.includes(busqueda);
  });
});

const clienteSeleccionado = computed(() => {
  if (!nuevaCitaData.value.cliente_id) {
    return null;
  }
  return clientes.value.find(c => c.id == nuevaCitaData.value.cliente_id);
});

async function cargarCitas(page = 1) {
  loading.value = true;
  try {
    const params: any = { 
      per_page: 10,
      page,
    };
    
    if (filtroEstado.value) {
      params.estado = filtroEstado.value;
    }
    // Si hay filtro de fecha, usarlo; si no, usar fecha de hoy por defecto
    const fechaFiltro = filtroFecha.value || fechaHoy;
    if (fechaFiltro) {
      params.desde = fechaFiltro + ' 00:00:00';
      params.hasta = fechaFiltro + ' 23:59:59';
    }
    
    const response = await getCitas(params);
    if (response.success) {
      citas.value = response.data || [];
      if (response.pagination) {
        pagination.value = response.pagination;
      }
    }
  } catch (error) {
    console.error('Error cargando citas:', error);
  } finally {
    loading.value = false;
  }
}

function debouncedSearch() {
  clearTimeout(searchTimeout);
  searchTimeout = setTimeout(() => {
    cargarCitas(1);
  }, 500);
}

function cambiarPagina(page: number) {
  cargarCitas(page);
}

function formatFecha(fecha: string) {
  if (!fecha) return '--';
  const date = new Date(fecha.replace(' ', 'T'));
  return date.toLocaleDateString('es-MX', { 
    weekday: 'short',
    day: 'numeric', 
    month: 'short',
    hour: '2-digit',
    minute: '2-digit'
  });
}

function formatPrecio(precio: any): string {
  return (Number(precio) || 0).toFixed(2);
}

function estadoLabel(estado: string): string {
  const labels: Record<string, string> = {
    pendiente: 'Pendiente',
    confirmada: 'Confirmada',
    en_proceso: 'En proceso',
    completada: 'Completada',
    cancelada: 'Cancelada',
    no_show: 'No Show',
    reagendada: 'Reagendada',
  };
  return labels[estado] || estado;
}

function getServiciosNombres(cita: any): string {
  if (cita.servicios && cita.servicios.length > 0) {
    return cita.servicios.map((s: any) => s.nombre || s.servicio?.nombre).filter(Boolean).join(', ');
  }
  if (cita.servicio) {
    return cita.servicio.nombre;
  }
  return 'Sin servicio';
}

function nuevaCita() { 
  editingCita.value = null;
  resetForm();
  showModal.value = true;
}

function verCita(cita: any) { 
  editingCita.value = cita;
  showModal.value = true;
}

function editarCita(cita: any) { 
  editingCita.value = cita;
  showModal.value = true;
}

function getInitials(nombre: string): string {
  if (!nombre) return '?';
  return nombre
    .split(' ')
    .map(n => n.charAt(0))
    .slice(0, 2)
    .join('')
    .toUpperCase();
}

function formatFechaCompleta(fecha: string): string {
  if (!fecha) return '--';
  const date = new Date(fecha.replace(' ', 'T'));
  return date.toLocaleDateString('es-MX', { 
    weekday: 'long',
    day: 'numeric', 
    month: 'long',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  });
}

function getServiciosList(cita: any): any[] {
  if (cita.servicios && cita.servicios.length > 0) {
    return cita.servicios.map((s: any) => ({
      nombre: s.nombre || s.servicio?.nombre || 'Sin nombre',
      duracion: s.duracion || s.duracion_minutos || s.servicio?.duracion_minutos || 0,
      precio: s.precio || s.precio_aplicado || s.servicio?.precio || 0,
    }));
  }
  if (cita.servicio) {
    return [{
      nombre: cita.servicio.nombre,
      duracion: cita.servicio.duracion_minutos || cita.servicio.duracion || 0,
      precio: cita.servicio.precio || 0,
    }];
  }
  return [];
}

function getDuracionTotal(cita: any): number {
  const servicios = getServiciosList(cita);
  return servicios.reduce((total, s) => total + (s.duracion || 0), 0);
}

async function cambiarEstado(cita: any, nuevoEstado: string) {
  try {
    await updateCita(cita.id, { estado: nuevoEstado });
    cargarCitas(pagination.value.current_page);
    closeModal();
  } catch (error) {
    console.error('Error cambiando estado:', error);
    Swal.fire({
      icon: 'error',
      title: 'Error',
      text: 'Error al cambiar el estado de la cita'
    });
  }
}

async function confirmarCancelacion(cita: any) {
  const result = await Swal.fire({
    title: '¿Cancelar esta cita?',
    html: `
      <div style="text-align: left; padding: 10px 0;">
        <p><strong>Cliente:</strong> ${cita.cliente?.nombre || 'Sin cliente'}</p>
        <p><strong>Fecha:</strong> ${formatFechaCompleta(cita.fecha_hora)}</p>
        <p><strong>Empleado:</strong> ${cita.empleado?.nombre || 'Sin asignar'}</p>
      </div>
      <p style="color: #c62828; font-weight: 600;">Esta acción no se puede deshacer.</p>
    `,
    icon: 'warning',
    showCancelButton: true,
    confirmButtonColor: '#c62828',
    cancelButtonColor: '#6c757d',
    confirmButtonText: 'Sí, cancelar cita',
    cancelButtonText: 'No, mantener',
    reverseButtons: true
  });

  if (result.isConfirmed) {
    await cancelarCita(cita);
  }
}

async function cancelarCita(cita: any) { 
  try {
    // Usar el nuevo endpoint de cancelación
    const response = await cancelarCitaAdmin(cita.id, 'Cancelado por administrador');
    
    if (response.success) {
      await cargarCitas(pagination.value.current_page);
      closeModal();
      
      await Swal.fire({
        icon: 'success',
        title: '¡Cita cancelada!',
        text: 'La cita ha sido cancelada exitosamente',
        confirmButtonColor: '#667eea',
        timer: 2000,
        showConfirmButton: true
      });
    } else {
      throw new Error(response.message || 'Error al cancelar');
    }
  } catch (error: any) {
    console.error('Error cancelando cita:', error);
    Swal.fire({
      icon: 'error',
      title: 'Error',
      text: error.response?.data?.message || error.message || 'No se pudo cancelar la cita',
      confirmButtonColor: '#c62828'
    });
  }
}

function puedeReagendar(cita: any): boolean {
  if (!cita) return false;
  // Solo se pueden reagendar citas pendientes o confirmadas
  return ['pendiente', 'confirmada'].includes(cita.estado);
}

function abrirModalReagendar(cita: any) {
  citaAReagendar.value = cita;
  reagendarData.value = {
    fecha: '',
    hora: '',
    motivo: '',
  };
  horariosReagendar.value = [];
  showModalReagendar.value = true;
  closeModal(); // Cerrar modal de detalle
}

function cerrarModalReagendar() {
  showModalReagendar.value = false;
  citaAReagendar.value = null;
  reagendarData.value = {
    fecha: '',
    hora: '',
    motivo: '',
  };
  horariosReagendar.value = [];
}

async function cargarHorariosReagendar() {
  if (!reagendarData.value.fecha || !citaAReagendar.value) {
    horariosReagendar.value = [];
    return;
  }

  cargandoHorariosReagendar.value = true;
  reagendarData.value.hora = '';

  try {
    // Obtener los IDs de los servicios de la cita
    const serviciosIds = citaAReagendar.value.servicios?.map((s: any) => s.id || s.servicio_id) || [];
    
    if (serviciosIds.length === 0) {
      console.warn('La cita no tiene servicios');
      horariosReagendar.value = [];
      return;
    }

    // Obtener el ID del empleado (puede venir como objeto o como ID directo)
    const empleadoId = citaAReagendar.value.empleado?.id || citaAReagendar.value.empleado_id;
    
    if (!empleadoId) {
      console.warn('La cita no tiene empleado asignado');
      horariosReagendar.value = [];
      return;
    }

    const response = await disponibilidadService.obtenerSlots(
      empleadoId,
      reagendarData.value.fecha,
      serviciosIds,
      'admin' // Usar endpoint de admin (sin restricción de anticipación)
    );

    // Filtrar solo los slots disponibles
    horariosReagendar.value = (response.slots || []).filter((slot: any) => {
      return slot.disponible !== false;
    }).map((slot: any) => ({
      hora: slot.hora || slot.hora_inicio,
      hora_fin: slot.hora_fin,
      disponible: true
    }));

  } catch (error) {
    console.error('Error cargando horarios para reagendar:', error);
    horariosReagendar.value = [];
  } finally {
    cargandoHorariosReagendar.value = false;
  }
}

function getPlaceholderHoraReagendar(): string {
  if (cargandoHorariosReagendar.value) return 'Cargando horarios...';
  if (!reagendarData.value.fecha) return 'Selecciona una fecha primero';
  if (horariosReagendar.value.length === 0) return 'Sin horarios disponibles';
  return 'Seleccionar hora';
}

async function confirmarReagendar() {
  if (!puedeConfirmarReagendar.value || !citaAReagendar.value) return;

  const result = await Swal.fire({
    title: '¿Confirmar reagendamiento?',
    html: `
      <div style="text-align: left; padding: 10px 0;">
        <p><strong>Nueva fecha:</strong> ${formatFechaCompleta(reagendarData.value.fecha + ' ' + reagendarData.value.hora)}</p>
        <p><strong>Nueva hora:</strong> ${formatHora(reagendarData.value.hora)}</p>
        <p style="color: #666; font-size: 12px; margin-top: 10px;">
          <i>La cita original quedará marcada como "reagendada" y se creará una nueva cita.</i>
        </p>
      </div>
    `,
    icon: 'question',
    showCancelButton: true,
    confirmButtonColor: '#667eea',
    cancelButtonColor: '#6c757d',
    confirmButtonText: 'Sí, reagendar',
    cancelButtonText: 'Cancelar',
    reverseButtons: true
  });

  if (!result.isConfirmed) return;

  guardandoReagendar.value = true;

  try {
    // Formatear fecha y hora correctamente (Y-m-d H:i:s)
    let hora = reagendarData.value.hora;
    // Asegurar formato HH:mm:ss
    if (hora.split(':').length === 2) {
      hora = `${hora}:00`;
    }
    const fechaHora = `${reagendarData.value.fecha} ${hora}`;
    
    console.log('Enviando fecha_hora:', fechaHora);
    
    // Usar el nuevo endpoint de reagendamiento
    const response = await reagendarCitaAdmin(citaAReagendar.value.id, {
      fecha_hora: fechaHora,
      motivo: reagendarData.value.motivo || undefined
    });
    
    if (response.success) {
      await cargarCitas(pagination.value.current_page);
      cerrarModalReagendar();

      await Swal.fire({
        icon: 'success',
        title: '¡Cita reagendada!',
        text: 'Se ha creado una nueva cita con la fecha actualizada',
        confirmButtonColor: '#667eea',
        timer: 2500,
        showConfirmButton: true
      });
    } else {
      throw new Error(response.message || 'Error al reagendar');
    }

  } catch (error: any) {
    console.error('Error reagendando cita:', error);
    Swal.fire({
      icon: 'error',
      title: 'Error',
      text: error.response?.data?.message || error.message || 'No se pudo reagendar la cita',
      confirmButtonColor: '#c62828'
    });
  } finally {
    guardandoReagendar.value = false;
  }
}

function closeModal() {
  showModal.value = false;
  editingCita.value = null;
  resetForm();
}

function resetForm() {
  nuevaCitaData.value = {
    cliente_id: '',
    empleado_id: '',
    fecha: '',
    hora: '',
    estado: 'pendiente',
    notas: '',
  };
  serviciosSeleccionados.value = [];
  busquedaCliente.value = '';
  horariosDisponibles.value = [];
  cargandoHorarios.value = false;
  serviciosEmpleado.value = [];
  cargandoServiciosEmpleado.value = false;
  mostrarCamposNuevoCliente.value = false;
  nuevoClienteData.value = {
    nombre: '',
    apellido: '',
    telefono: '',
    email: '',
  };
}

async function cargarClientes() {
  try {
    const response = await getClientes({ per_page: 1000 });
    if (response.success) {
      clientes.value = response.data || [];
    }
  } catch (error) {
    console.error('Error cargando clientes:', error);
  }
}

async function cargarEmpleados() {
  try {
    const response = await getEmpleados({ active: true });
    if (response.success) {
      empleados.value = response.data || [];
    }
  } catch (error) {
    console.error('Error cargando empleados:', error);
  }
}

async function cargarServicios() {
  try {
    const response = await getServicios();
    if (response.success) {
      servicios.value = response.data || [];
    }
  } catch (error) {
    console.error('Error cargando servicios:', error);
  }
}

function isServicioSelected(servicioId: number): boolean {
  return serviciosSeleccionados.value.includes(servicioId);
}

async function onEmpleadoChange() {
  // Limpiar servicios seleccionados y fecha/hora al cambiar empleado
  serviciosSeleccionados.value = [];
  nuevaCitaData.value.fecha = '';
  nuevaCitaData.value.hora = '';
  horariosDisponibles.value = [];
  
  // Cargar servicios del empleado
  if (nuevaCitaData.value.empleado_id) {
    await cargarServiciosEmpleado(Number(nuevaCitaData.value.empleado_id));
  } else {
    serviciosEmpleado.value = [];
  }
}

async function cargarServiciosEmpleado(empleadoId: number) {
  cargandoServiciosEmpleado.value = true;
  try {
    const response = await getEmpleadoServicios(empleadoId);
    console.log('Respuesta servicios empleado:', response);
    
    if (response.success && response.data) {
      // Mapear los servicios para asegurar que tengan la estructura correcta
      console.log('Respuesta completa de servicios:', JSON.stringify(response.data, null, 2));
      serviciosEmpleado.value = response.data.map((item: any) => {
        console.log('Item completo:', JSON.stringify(item, null, 2));
        console.log('Valores de duración en item:', {
          'item.duracion_minutos': item?.duracion_minutos,
          'item.duracion': item?.duracion,
          'tipo duracion_minutos': typeof item?.duracion_minutos,
          'tipo duracion': typeof item?.duracion,
        });
        
        // Si viene con relación servicio (pivot), usar esa
        const servicioData = item.servicio || item;
        
        // Obtener valores de diferentes posibles estructuras
        const id = servicioData.id || item.id;
        const nombre = servicioData.nombre || item.nombre || 'Sin nombre';
        
        // Buscar duración en diferentes lugares (más exhaustivo)
        // Priorizar duracion_minutos que es lo que devuelve el backend
        let duracion: any = null;
        
        // Buscar primero en item directamente (lo que viene del API)
        if (item?.duracion_minutos !== undefined && item?.duracion_minutos !== null && item?.duracion_minutos !== '') {
          duracion = item.duracion_minutos;
          console.log('Encontrado duracion_minutos en item:', duracion);
        } else if (item?.duracion !== undefined && item?.duracion !== null && item?.duracion !== '') {
          duracion = item.duracion;
          console.log('Encontrado duracion en item:', duracion);
        } else if (servicioData?.duracion_minutos !== undefined && servicioData?.duracion_minutos !== null && servicioData?.duracion_minutos !== '') {
          duracion = servicioData.duracion_minutos;
          console.log('Encontrado duracion_minutos en servicioData:', duracion);
        } else if (servicioData?.duracion !== undefined && servicioData?.duracion !== null && servicioData?.duracion !== '') {
          duracion = servicioData.duracion;
          console.log('Encontrado duracion en servicioData:', duracion);
        } else if (item?.pivot?.duracion_minutos !== undefined && item?.pivot?.duracion_minutos !== null && item?.pivot?.duracion_minutos !== '') {
          duracion = item.pivot.duracion_minutos;
          console.log('Encontrado duracion_minutos en pivot:', duracion);
        } else if (item?.pivot?.duracion !== undefined && item?.pivot?.duracion !== null && item?.pivot?.duracion !== '') {
          duracion = item.pivot.duracion;
          console.log('Encontrado duracion en pivot:', duracion);
        } else if (servicioData?.pivot?.duracion_minutos !== undefined && servicioData?.pivot?.duracion_minutos !== null && servicioData?.pivot?.duracion_minutos !== '') {
          duracion = servicioData.pivot.duracion_minutos;
          console.log('Encontrado duracion_minutos en servicioData.pivot:', duracion);
        } else if (servicioData?.pivot?.duracion !== undefined && servicioData?.pivot?.duracion !== null && servicioData?.pivot?.duracion !== '') {
          duracion = servicioData.pivot.duracion;
          console.log('Encontrado duracion en servicioData.pivot:', duracion);
        } else {
          console.warn('No se encontró duración en ningún lugar para el servicio:', item);
        }
        
        // Si no se encontró, usar 0
        if (duracion === null || duracion === undefined || duracion === '') {
          duracion = 0;
        }
        
        // Buscar precio en diferentes lugares (más exhaustivo)
        let precio = 0;
        // Priorizar precio_estandar que es el que viene del API
        if (item?.precio_estandar !== undefined && item?.precio_estandar !== null) {
          precio = item.precio_estandar;
        } else if (servicioData?.precio_estandar !== undefined && servicioData?.precio_estandar !== null) {
          precio = servicioData.precio_estandar;
        } else if (item?.precio_especial !== undefined && item?.precio_especial !== null) {
          precio = item.precio_especial;
        } else if (servicioData?.precio_especial !== undefined && servicioData?.precio_especial !== null) {
          precio = servicioData.precio_especial;
        } else if (item?.precio !== undefined && item?.precio !== null) {
          precio = item.precio;
        } else if (servicioData?.precio !== undefined && servicioData?.precio !== null) {
          precio = servicioData.precio;
        } else if (item?.precio_aplicado !== undefined && item?.precio_aplicado !== null) {
          precio = item.precio_aplicado;
        } else if (servicioData?.precio_aplicado !== undefined && servicioData?.precio_aplicado !== null) {
          precio = servicioData.precio_aplicado;
        } else if (item?.pivot?.precio !== undefined && item?.pivot?.precio !== null) {
          precio = item.pivot.precio;
        } else if (item?.pivot?.precio_aplicado !== undefined && item?.pivot?.precio_aplicado !== null) {
          precio = item.pivot.precio_aplicado;
        } else if (servicioData?.pivot?.precio !== undefined && servicioData?.pivot?.precio !== null) {
          precio = servicioData.pivot.precio;
        } else if (servicioData?.pivot?.precio_aplicado !== undefined && servicioData?.pivot?.precio_aplicado !== null) {
          precio = servicioData.pivot.precio_aplicado;
        }
        
        // Convertir a números
        duracion = Number(duracion) || 0;
        precio = Number(precio) || 0;
        
        console.log('Servicio mapeado:', { 
          id, 
          nombre, 
          duracion, 
          precio, 
          'item.duracion_minutos': item?.duracion_minutos,
          'item.duracion': item?.duracion,
          'item completo': item 
        });
        
        const servicioMapeado = {
          id,
          nombre,
          duracion_minutos: duracion,
          duracion: duracion,
          precio: precio,
          precio_aplicado: precio,
        };
        
        console.log('Servicio mapeado final:', servicioMapeado);
        
        return servicioMapeado;
      });
      console.log('Servicios mapeados finales:', serviciosEmpleado.value);
    } else {
      serviciosEmpleado.value = [];
    }
  } catch (error) {
    console.error('Error cargando servicios del empleado:', error);
    serviciosEmpleado.value = [];
  } finally {
    cargandoServiciosEmpleado.value = false;
  }
}

function toggleServicio(servicioId: number) {
  const index = serviciosSeleccionados.value.indexOf(servicioId);
  if (index > -1) {
    serviciosSeleccionados.value.splice(index, 1);
  } else {
    serviciosSeleccionados.value.push(servicioId);
  }
  // Limpiar fecha y hora al cambiar servicios
  nuevaCitaData.value.fecha = '';
  nuevaCitaData.value.hora = '';
  horariosDisponibles.value = [];
}

function onBusquedaCliente() {
  // La búsqueda se maneja automáticamente con el computed
}

function limpiarBusquedaCliente() {
  busquedaCliente.value = '';
}

function seleccionarCliente(cliente: any) {
  nuevaCitaData.value.cliente_id = cliente.id.toString();
  busquedaCliente.value = '';
}

function deseleccionarCliente() {
  nuevaCitaData.value.cliente_id = '';
  busquedaCliente.value = '';
  // Limpiar empleado y servicios al deseleccionar cliente
  nuevaCitaData.value.empleado_id = '';
  serviciosSeleccionados.value = [];
  serviciosEmpleado.value = [];
  nuevaCitaData.value.fecha = '';
  nuevaCitaData.value.hora = '';
  horariosDisponibles.value = [];
}

async function cargarHorariosDisponibles() {
  // Validar que tengamos los datos necesarios
  if (!nuevaCitaData.value.fecha || !nuevaCitaData.value.empleado_id || serviciosSeleccionados.value.length === 0) {
    horariosDisponibles.value = [];
    nuevaCitaData.value.hora = '';
    return;
  }

  cargandoHorarios.value = true;
  nuevaCitaData.value.hora = ''; // Limpiar hora seleccionada

  try {
    console.log('Cargando horarios con:', {
      empleado_id: nuevaCitaData.value.empleado_id,
      fecha: nuevaCitaData.value.fecha,
      servicios: serviciosSeleccionados.value
    });

    const response = await disponibilidadService.obtenerSlots(
      Number(nuevaCitaData.value.empleado_id),
      nuevaCitaData.value.fecha,
      serviciosSeleccionados.value
    );

    console.log('Respuesta horarios:', response);

    if (response && response.slots) {
      // Filtrar solo los slots disponibles y normalizar la estructura
      const slotsDisponibles = response.slots
        .filter((slot: any) => {
          // Verificar si disponible es true o si no existe la propiedad (asumir disponible)
          return slot.disponible !== false;
        })
        .map((slot: any) => {
          // Normalizar: usar hora o hora_inicio
          return {
            ...slot,
            hora: slot.hora || slot.hora_inicio,
            hora_fin: slot.hora_fin || slot.hora_final,
            disponible: slot.disponible !== false
          };
        });
      horariosDisponibles.value = slotsDisponibles;
      console.log('Slots disponibles:', horariosDisponibles.value);
    } else {
      console.warn('No se encontraron slots en la respuesta');
      horariosDisponibles.value = [];
    }
  } catch (error) {
    console.error('Error cargando horarios disponibles:', error);
    horariosDisponibles.value = [];
  } finally {
    cargandoHorarios.value = false;
  }
}

function getDuracionServicio(servicio: any): number {
  if (!servicio) return 0;
  
  // Log detallado para ver todos los campos
  console.log('getDuracionServicio - servicio completo:', JSON.stringify(servicio, null, 2));
  console.log('getDuracionServicio - campos:', {
    'servicio.duracion_minutos': servicio.duracion_minutos,
    'servicio.duracion': servicio.duracion,
    'servicio.pivot?.duracion_minutos': servicio.pivot?.duracion_minutos,
    'servicio.pivot?.duracion': servicio.pivot?.duracion,
    'todas las keys': Object.keys(servicio)
  });
  
  // Intentar obtener duración de diferentes propiedades
  let duracion = 0;
  if (servicio.duracion_minutos !== undefined && servicio.duracion_minutos !== null) {
    duracion = servicio.duracion_minutos;
  } else if (servicio.duracion !== undefined && servicio.duracion !== null) {
    duracion = servicio.duracion;
  } else if (servicio.pivot?.duracion_minutos !== undefined && servicio.pivot?.duracion_minutos !== null) {
    duracion = servicio.pivot.duracion_minutos;
  } else if (servicio.pivot?.duracion !== undefined && servicio.pivot?.duracion !== null) {
    duracion = servicio.pivot.duracion;
  }
  const resultado = Number(duracion);
  console.log('getDuracionServicio - resultado final:', { duracion, resultado });
  return resultado || 0;
}

function getPrecioServicio(servicio: any): number {
  if (!servicio) return 0;
  // Intentar obtener precio de diferentes propiedades
  const precio = servicio.precio || 
                 servicio.precio_aplicado || 
                 servicio.pivot?.precio || 
                 0;
  return Number(precio) || 0;
}

function formatHora(hora: string): string {
  if (!hora) return '';
  // Formato HH:mm a HH:mm AM/PM
  const [hours, minutes] = hora.split(':');
  if (!hours) return '';
  const hour = parseInt(hours);
  const ampm = hour >= 12 ? 'PM' : 'AM';
  const hour12 = hour % 12 || 12;
  return `${hour12.toString().padStart(2, '0')}:${minutes} ${ampm}`;
}

function getHoraPlaceholder(): string {
  if (cargandoHorarios.value) {
    return 'Cargando horarios...';
  }
  if (!nuevaCitaData.value.fecha) {
    return 'Selecciona una fecha';
  }
  if (!nuevaCitaData.value.empleado_id) {
    return 'Selecciona un empleado';
  }
  if (serviciosSeleccionados.value.length === 0) {
    return 'Selecciona servicios';
  }
  if (horariosDisponibles.value.length === 0) {
    return 'Sin horarios disponibles';
  }
  return 'Selecciona un horario';
}

async function guardarCita() {
  // Validaciones
  if (!nuevaCitaData.value.cliente_id) {
    Swal.fire({
      icon: 'warning',
      title: 'Campo requerido',
      text: 'Debes seleccionar un cliente'
    });
    return;
  }
  if (!nuevaCitaData.value.empleado_id) {
    Swal.fire({
      icon: 'warning',
      title: 'Campo requerido',
      text: 'Debes seleccionar un empleado'
    });
    return;
  }
  if (!nuevaCitaData.value.fecha || !nuevaCitaData.value.hora) {
    Swal.fire({
      icon: 'warning',
      title: 'Campo requerido',
      text: 'Debes seleccionar fecha y hora'
    });
    return;
  }
  if (serviciosSeleccionados.value.length === 0) {
    Swal.fire({
      icon: 'warning',
      title: 'Campo requerido',
      text: 'Debes seleccionar al menos un servicio'
    });
    return;
  }

  guardando.value = true;
  try {
    // Formatear fecha y hora correctamente
    let horaFormateada = nuevaCitaData.value.hora;
    // Si la hora no tiene formato completo (HH:mm:ss), agregar :00
    if (horaFormateada && horaFormateada.split(':').length === 2) {
      horaFormateada = `${horaFormateada}:00`;
    }
    const fechaHora = `${nuevaCitaData.value.fecha} ${horaFormateada}`;
    
    const data = {
      cliente_id: Number(nuevaCitaData.value.cliente_id),
      empleado_id: Number(nuevaCitaData.value.empleado_id),
      fecha_hora: fechaHora,
      servicios: serviciosSeleccionados.value,
      estado: nuevaCitaData.value.estado,
      notas: nuevaCitaData.value.notas || null,
    };

    console.log('Datos a enviar para crear cita:', data);
    console.log('Fecha formateada:', fechaHora);

    const response = await createCita(data);
    if (response.success) {
      await cargarCitas(pagination.value.current_page);
      closeModal();
      Swal.fire({
        icon: 'success',
        title: '¡Cita creada!',
        text: 'La cita se ha creado exitosamente',
        timer: 2500,
        showConfirmButton: false
      });
    } else {
      Swal.fire({
        icon: 'error',
        title: 'Error',
        text: response.message || 'Error al crear la cita'
      });
    }
  } catch (error: any) {
    console.error('Error guardando cita:', error);
    console.error('Error response:', error.response);
    console.error('Error data:', error.response?.data);
    console.error('Error status:', error.response?.status);
    
    let errorMessage = 'Error al crear la cita';
    if (error.response?.data?.message) {
      errorMessage = error.response.data.message;
    } else if (error.response?.data?.error) {
      errorMessage = error.response.data.error;
    } else if (error.message) {
      errorMessage = error.message;
    }
    
    // Mostrar más detalles en consola
    if (error.response?.data?.errors) {
      console.error('Errores de validación:', error.response.data.errors);
    }
    
    Swal.fire({
      icon: 'error',
      title: 'Error al crear cita',
      text: errorMessage
    });
  } finally {
    guardando.value = false;
  }
}

async function guardarCliente() {
  if (!nuevoClienteData.value.nombre || !nuevoClienteData.value.telefono) {
    Swal.fire({
      icon: 'warning',
      title: 'Campos requeridos',
      text: 'Nombre y teléfono son obligatorios'
    });
    return;
  }

  try {
    const response = await createCliente({
      nombre: nuevoClienteData.value.nombre,
      apellido: nuevoClienteData.value.apellido || null,
      telefono: nuevoClienteData.value.telefono,
      email: nuevoClienteData.value.email || null,
    });

    if (response.success) {
      await cargarClientes();
      nuevaCitaData.value.cliente_id = response.data.id.toString();
      mostrarCamposNuevoCliente.value = false;
      busquedaCliente.value = '';
      nuevoClienteData.value = {
        nombre: '',
        apellido: '',
        telefono: '',
        email: '',
      };
      Swal.fire({
        icon: 'success',
        title: '¡Cliente creado!',
        text: 'El cliente se ha registrado exitosamente',
        timer: 2000,
        showConfirmButton: false
      });
    } else {
      Swal.fire({
        icon: 'error',
        title: 'Error',
        text: response.message || 'Error al crear el cliente'
      });
    }
  } catch (error: any) {
    console.error('Error guardando cliente:', error);
    const errorMessage = error.response?.data?.message || 'Error al crear el cliente';
    Swal.fire({
      icon: 'error',
      title: 'Error',
      text: errorMessage
    });
  }
}

function cancelarNuevoCliente() {
  mostrarCamposNuevoCliente.value = false;
  nuevoClienteData.value = {
    nombre: '',
    apellido: '',
    telefono: '',
    email: '',
  };
}

// Funciones para forzar que los dropdowns se abran hacia abajo
function onSelectFocus(event: Event) {
  const target = event.target as HTMLSelectElement;
  if (!target) return;
  
  nextTick(() => {
    // Hacer scroll para que haya más espacio debajo
    const rect = target.getBoundingClientRect();
    const spaceBelow = window.innerHeight - rect.bottom;
    const spaceAbove = rect.top;
    
    // Si hay más espacio arriba que abajo, hacer scroll para crear más espacio debajo
    if (spaceAbove > spaceBelow || spaceBelow < 400) {
      // Scroll para posicionar el elemento más arriba y dejar espacio debajo
      target.scrollIntoView({ behavior: 'smooth', block: 'start' });
      // Scroll adicional para asegurar espacio
      setTimeout(() => {
        window.scrollBy({ top: 300, behavior: 'smooth' });
      }, 100);
    }
  });
}

function onDateFocus(event: Event) {
  const target = event.target as HTMLInputElement;
  if (!target) return;
  
  nextTick(() => {
    // Hacer scroll para que haya más espacio debajo
    const rect = target.getBoundingClientRect();
    const spaceBelow = window.innerHeight - rect.bottom;
    const spaceAbove = rect.top;
    
    // Si hay más espacio arriba que abajo, hacer scroll para crear más espacio debajo
    if (spaceAbove > spaceBelow || spaceBelow < 400) {
      // Scroll para posicionar el elemento más arriba y dejar espacio debajo
      target.scrollIntoView({ behavior: 'smooth', block: 'start' });
      // Scroll adicional para asegurar espacio
      setTimeout(() => {
        window.scrollBy({ top: 300, behavior: 'smooth' });
      }, 100);
    }
  });
}

onMounted(() => {
  cargarCitas();
  cargarClientes();
  cargarEmpleados();
});
</script>

<style scoped>
/* ===== Apple-inspired Citas View Design ===== */

.citas-view {
  min-height: 100vh;
  background: #f5f5f7;
  padding: 24px;
  padding-bottom: 120px;
  font-family: -apple-system, BlinkMacSystemFont, 'SF Pro Display', 'SF Pro Text', 'Helvetica Neue', sans-serif;
  -webkit-font-smoothing: antialiased;
  -moz-osx-font-smoothing: grayscale;
}

/* Header */
.citas-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 24px;
  padding: 20px;
  background: linear-gradient(135deg, #1d1d1f 0%, #3a3a3c 100%);
  border-radius: 20px;
  color: white;
}

.header-left {
  display: flex;
  align-items: center;
  gap: 14px;
}

.header-icon {
  width: 46px;
  height: 46px;
  background: rgba(255, 255, 255, 0.1);
  border-radius: 14px;
  display: flex;
  align-items: center;
  justify-content: center;
  backdrop-filter: blur(10px);
}

.header-text h1 {
  font-size: 22px;
  font-weight: 600;
  margin: 0;
  letter-spacing: -0.3px;
}

.header-subtitle {
  font-size: 13px;
  opacity: 0.7;
  margin: 4px 0 0;
}

.btn-new-cita {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 12px 20px;
  background: rgba(255, 255, 255, 0.15);
  border: 1px solid rgba(255, 255, 255, 0.2);
  border-radius: 12px;
  color: white;
  font-size: 15px;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.2s;
  backdrop-filter: blur(10px);
}

.btn-new-cita:active {
  background: rgba(255, 255, 255, 0.25);
  transform: scale(0.98);
}

/* Filters */
.filters-section {
  margin-bottom: 10px;
  padding-bottom: 20px;
}

.search-container {
  position: relative;
  margin-bottom: 12px;
}

.search-icon {
  position: absolute;
  left: 16px;
  top: 50%;
  transform: translateY(-50%);
  color: #86868b;
  pointer-events: none;
  z-index: 1;
}

.search-input {
  width: 100%;
  padding: 14px 16px 14px 48px;
  background: #ffffff;
  border: 1px solid #e5e5ea;
  border-radius: 14px;
  font-size: 15px;
  color: #1d1d1f;
  transition: all 0.2s;
  font-family: inherit;
}

.search-input:focus {
  outline: none;
  border-color: #007aff;
  box-shadow: 0 0 0 4px rgba(0, 122, 255, 0.1);
}

.search-input::placeholder {
  color: #86868b;
}

.filters-row {
  display: flex;
  gap: 10px;
  position: relative;
  z-index: 1;
  flex-wrap: wrap;
}

.filter-select,
.filter-date {
  flex: 1;
  min-width: 0;
  padding: 14px 16px;
  background: #ffffff;
  border: 1px solid #e5e5ea;
  border-radius: 14px;
  font-size: 15px;
  color: #1d1d1f;
  font-family: inherit;
  transition: all 0.2s;
  position: relative;
  /* Forzar que el dropdown se abra hacia abajo */
  appearance: none;
  -webkit-appearance: none;
  -moz-appearance: none;
  box-sizing: border-box;
  width: 100%;
  max-width: 100%;
}

/* Forzar que el select se abra hacia abajo en móviles */
.filter-select {
  /* En iOS Safari, esto ayuda a que se abra hacia abajo */
  transform: translateZ(0);
  -webkit-transform: translateZ(0);
}

/* Estilo para el icono del select (flecha) */
.filter-select {
  background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%231d1d1f' d='M6 9L1 4h10z'/%3E%3C/svg%3E");
  background-repeat: no-repeat;
  background-position: right 16px center;
  background-size: 12px;
  padding-right: 40px;
}

.filter-select:focus,
.filter-date:focus {
  outline: none;
  border-color: #007aff;
  box-shadow: 0 0 0 4px rgba(0, 122, 255, 0.1);
}

/* Asegurar que el datepicker se abra hacia abajo */
.filter-date {
  /* En algunos navegadores móviles, esto ayuda */
  position: relative;
  z-index: 10;
}

/* En iOS, forzar que el datepicker se abra hacia abajo */
@supports (-webkit-touch-callout: none) {
  .filter-date {
    /* iOS Safari específico */
    transform: translateZ(0);
  }
}

/* Media Queries para Responsividad del Selector de Fechas */
@media (max-width: 640px) {
  .filters-row {
    flex-direction: column;
    gap: 8px;
  }
  
  .filter-select,
  .filter-date {
    width: 100% !important;
    min-width: 100% !important;
    flex: none !important;
    font-size: 16px; /* Prevenir zoom en iOS */
    padding: 12px 14px;
  }
  
  .filter-date {
    /* Asegurar que el datepicker nativo no se salga de la vista */
    max-width: 100%;
    overflow: visible;
  }
}

@media (min-width: 481px) and (max-width: 768px) {
  .filters-row {
    gap: 8px;
  }
  
  .filter-select,
  .filter-date {
    min-width: 140px;
    font-size: 14px;
    padding: 12px 14px;
  }
}

@media (min-width: 769px) and (max-width: 1024px) {
  .filters-row {
    gap: 10px;
  }
  
  .filter-select,
  .filter-date {
    min-width: 180px;
  }
}

@media (min-width: 1025px) {
  .filters-row {
    gap: 12px;
  }
  
  .filter-select,
  .filter-date {
    min-width: 200px;
    max-width: 300px;
  }
}

/* Prevenir que el datepicker se salga de la vista en pantallas pequeñas */
@media (max-width: 768px) {
  .filter-date:focus {
    position: relative;
    z-index: 100;
  }
  
  /* Asegurar que el contenedor de filtros no cause overflow */
  .filters-section {
    overflow-x: visible;
    width: 100%;
    max-width: 100%;
  }
}

/* Loading & Empty */
.loading-container {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 60px 20px;
  color: #86868b;
}

.loading-container p {
  margin: 16px 0 0;
  font-size: 15px;
  color: #86868b;
}

.loader {
  width: 32px;
  height: 32px;
  border: 3px solid #f5f5f7;
  border-top-color: #007aff;
  border-radius: 50%;
  animation: spin 0.8s linear infinite;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}

.empty-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 60px 20px;
  text-align: center;
}

.empty-icon {
  color: #d1d1d6;
  margin-bottom: 16px;
}

.empty-state p {
  color: #86868b;
  font-size: 15px;
  margin: 0;
}

/* Citas List */
.citas-list {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.cita-card {
  background: #ffffff;
  border-radius: 16px;
  overflow: hidden;
  border: 1px solid #e5e5ea;
  transition: all 0.2s ease;
  cursor: pointer;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
}

.cita-card:active {
  transform: scale(0.98);
  box-shadow: 0 1px 4px rgba(0, 0, 0, 0.1);
}

.cita-card-header {
  display: flex;
  justify-content: flex-end;
  align-items: center;
  padding: 16px 18px;
  background: #f8f9fa;
  border-bottom: 1px solid #f0f0f0;
}

.cita-status {
  padding: 5px 11px;
  border-radius: 12px;
  font-size: 11px;
  font-weight: 600;
  text-transform: capitalize;
  letter-spacing: 0.2px;
}

.cita-status.pendiente {
  background: rgba(255, 149, 0, 0.12);
  color: #ff9500;
}

.cita-status.confirmada {
  background: rgba(52, 199, 89, 0.12);
  color: #34c759;
}

.cita-status.en_proceso {
  background: rgba(0, 122, 255, 0.12);
  color: #007aff;
}

.cita-status.completada {
  background: rgba(52, 199, 89, 0.12);
  color: #34c759;
}

.cita-status.cancelada {
  background: rgba(255, 59, 48, 0.12);
  color: #ff3b30;
}

.cita-status.no_show {
  background: rgba(255, 59, 48, 0.12);
  color: #ff3b30;
}

.cita-status.reagendada {
  background: rgba(255, 149, 0, 0.12);
  color: #ff9500;
}

.cita-card-body {
  padding: 18px;
  display: flex;
  flex-direction: column;
  gap: 14px;
}

.cita-time-row {
  display: flex;
  align-items: center;
  gap: 8px;
  padding-bottom: 12px;
  border-bottom: 1px solid #f0f0f0;
  flex-wrap: wrap;
}

.cita-time-row svg {
  color: #007aff;
  flex-shrink: 0;
}

.cita-time {
  font-size: 16px;
  font-weight: 600;
  color: #1d1d1f;
  letter-spacing: -0.2px;
  flex: 1;
}

.cita-time-row .cita-status {
  margin-left: auto;
}

.cita-info-grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 12px;
}

.cita-info-item {
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.cita-info-item svg {
  color: #86868b;
  margin-bottom: 2px;
}

.cita-info-label {
  font-size: 11px;
  font-weight: 500;
  color: #86868b;
  text-transform: uppercase;
  letter-spacing: 0.3px;
}

.cita-info-value {
  font-size: 14px;
  font-weight: 600;
  color: #1d1d1f;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.cita-servicios-row {
  display: flex;
  align-items: flex-start;
  gap: 8px;
  padding-top: 12px;
  border-top: 1px solid #f0f0f0;
}

.cita-servicios-row svg {
  color: #86868b;
  flex-shrink: 0;
  margin-top: 2px;
}

.cita-servicios-text {
  font-size: 13px;
  color: #86868b;
  line-height: 1.5;
  flex: 1;
}

.cita-card-footer {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 14px 18px;
  background: #f8f9fa;
  border-top: 1px solid #f0f0f0;
}

.cita-price {
  display: flex;
  flex-direction: column;
  gap: 2px;
}

.price-label {
  font-size: 11px;
  font-weight: 500;
  color: #86868b;
  text-transform: uppercase;
  letter-spacing: 0.3px;
}

.price-value {
  font-size: 18px;
  font-weight: 700;
  color: #1d1d1f;
  letter-spacing: -0.3px;
}

.cita-actions {
  display: flex;
  gap: 6px;
}

.action-btn {
  width: 36px;
  height: 36px;
  border: none;
  border-radius: 10px;
  background: #f0f0f0;
  color: #86868b;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all 0.2s;
}

.action-btn:active {
  transform: scale(0.95);
  background: #e5e5ea;
}

.action-btn.reagendar {
  background: rgba(0, 122, 255, 0.12);
  color: #007aff;
}

.action-btn.danger {
  background: rgba(255, 59, 48, 0.12);
  color: #ff3b30;
}

/* Pagination */
.pagination {
  display: flex;
  justify-content: center;
  align-items: center;
  gap: 16px;
  margin-top: 24px;
  padding: 20px;
  background: #ffffff;
  border-radius: 16px;
  border: 1px solid #f0f0f0;
}

.page-btn {
  width: 40px;
  height: 40px;
  border: none;
  border-radius: 12px;
  background: #f5f5f7;
  color: #007aff;
  font-size: 14px;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all 0.2s;
}

.page-btn:active:not(:disabled) {
  transform: scale(0.95);
  background: #e5e5ea;
}

.page-btn:disabled {
  opacity: 0.4;
  cursor: not-allowed;
}

.page-info {
  font-size: 15px;
  color: #1d1d1f;
  font-weight: 600;
  font-variant-numeric: tabular-nums;
}

/* Modal */
.modal-overlay {
  position: fixed;
  inset: 0;
  background: rgba(0, 0, 0, 0.4);
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
  max-height: 90vh;
  border-radius: 24px 24px 0 0;
  overflow: hidden;
  animation: slideUp 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  box-shadow: 0 -8px 32px rgba(0, 0, 0, 0.2);
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
  padding: 20px 24px;
  border-bottom: 1px solid #f0f0f0;
  background: #ffffff;
}

.modal-header h3 {
  margin: 0;
  font-size: 20px;
  font-weight: 600;
  color: #1d1d1f;
  letter-spacing: -0.3px;
}

.modal-close {
  width: 36px;
  height: 36px;
  border: none;
  border-radius: 10px;
  background: #f5f5f7;
  color: #86868b;
  font-size: 16px;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all 0.2s;
}

.modal-close:active {
  background: #e5e5ea;
  transform: scale(0.95);
}

.modal-body {
  padding: 24px;
  overflow-y: auto;
  max-height: calc(90vh - 80px);
  background: #f5f5f7;
}

.modal-body::-webkit-scrollbar {
  width: 6px;
}

.modal-body::-webkit-scrollbar-track {
  background: #f1f1f1;
}

.modal-body::-webkit-scrollbar-thumb {
  background: #c1c1c1;
  border-radius: 3px;
}

.modal-body::-webkit-scrollbar-thumb:hover {
  background: #a8a8a8;
}

.coming-soon {
  text-align: center;
  color: #999;
  padding: 60px 40px;
  font-size: 16px;
}

/* Cita Detail */
.cita-detail {
  display: flex;
  flex-direction: column;
  gap: 20px;
}

.detail-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 20px;
  background: linear-gradient(135deg, #1d1d1f 0%, #3a3a3c 100%);
  border-radius: 16px;
  color: white;
  margin-bottom: 16px;
}

.detail-id {
  display: flex;
  flex-direction: column;
}

.id-label {
  font-size: 11px;
  opacity: 0.95;
  text-transform: uppercase;
  letter-spacing: 1px;
  margin-bottom: 4px;
}

.id-value {
  font-size: 28px;
  font-weight: 800;
  letter-spacing: -0.5px;
}

.status-badge-large {
  padding: 10px 18px;
  border-radius: 20px;
  font-size: 12px;
  font-weight: 600;
  text-transform: capitalize;
  letter-spacing: 0.3px;
  background: rgba(255, 255, 255, 0.2);
  backdrop-filter: blur(10px);
  color: white;
}

.detail-section {
  background: #ffffff;
  border-radius: 16px;
  padding: 18px;
  border: 1px solid #f0f0f0;
  transition: all 0.2s;
}

.section-title {
  display: flex;
  align-items: center;
  gap: 10px;
  margin-bottom: 14px;
  font-size: 12px;
  font-weight: 600;
  color: #007aff;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.section-title i {
  font-size: 14px;
  width: 24px;
  height: 24px;
  display: flex;
  align-items: center;
  justify-content: center;
  background: rgba(0, 122, 255, 0.1);
  color: #007aff;
  border-radius: 8px;
}

.info-block {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.info-item {
  display: flex;
  align-items: center;
  gap: 12px;
}

.avatar-small {
  width: 48px;
  height: 48px;
  border-radius: 50%;
  background: #007aff;
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
  font-weight: 700;
  font-size: 16px;
  flex-shrink: 0;
  border: 3px solid white;
}

.avatar-small.empleado {
  background: #34c759;
}

.info-content {
  display: flex;
  flex-direction: column;
  flex: 1;
  min-width: 0;
  gap: 4px;
}

.info-label {
  font-size: 10px;
  color: #86868b;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  font-weight: 500;
}

.info-value {
  font-size: 16px;
  font-weight: 600;
  color: #1d1d1f;
  line-height: 1.3;
}

.phone-link {
  color: #007aff;
  text-decoration: none;
}

.phone-link:active {
  opacity: 0.7;
}

.info-item i {
  width: 20px;
  color: #007aff;
  text-align: center;
  flex-shrink: 0;
}

.servicios-list {
  display: flex;
  flex-direction: column;
  gap: 10px;
}

.servicio-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 14px;
  background: white;
  border-radius: 12px;
  border: 1px solid #f0f0f0;
  transition: all 0.2s;
}

.servicio-item:hover {
  background: #f8f9fa;
  border-color: #e5e5ea;
}

.servicio-info {
  display: flex;
  flex-direction: column;
  gap: 6px;
  flex: 1;
}

.servicio-nombre {
  font-size: 15px;
  font-weight: 600;
  color: #1d1d1f;
}

.servicio-duracion {
  font-size: 12px;
  color: #999;
  font-weight: 500;
}

.servicio-precio {
  font-size: 18px;
  font-weight: 700;
  color: #1d1d1f;
  letter-spacing: -0.3px;
}

.total-section {
  background: linear-gradient(135deg, #1d1d1f 0%, #3a3a3c 100%);
  color: white;
  border: none;
}

.total-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.total-label {
  font-size: 14px;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  opacity: 0.9;
}

.total-value {
  font-size: 32px;
  font-weight: 700;
  letter-spacing: -1px;
}

.notas-content {
  padding: 16px;
  background: white;
  border-radius: 12px;
  font-size: 14px;
  color: #86868b;
  line-height: 1.7;
  border: 1px solid #f0f0f0;
  font-style: italic;
}

.detail-actions {
  display: flex;
  flex-direction: column;
  gap: 12px;
  margin-top: 12px;
}

.btn-action-primary,
.btn-action-secondary,
.btn-action-danger,
.btn-action-reagendar {
  width: 100%;
  padding: 16px;
  border: none;
  border-radius: 14px;
  font-size: 15px;
  font-weight: 600;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 10px;
  transition: all 0.2s ease;
  letter-spacing: -0.1px;
}

.btn-action-primary {
  background: #34c759;
  color: white;
}

.btn-action-primary:active {
  transform: scale(0.98);
  background: #30d158;
}

.btn-action-secondary {
  background: #007aff;
  color: white;
}

.btn-action-secondary:active {
  transform: scale(0.98);
  background: #0051d5;
}

.btn-action-danger {
  background: #ff3b30;
  color: white;
}

.btn-action-danger:active {
  transform: scale(0.98);
  background: #ff2d55;
}

.btn-action-reagendar {
  background: #007aff;
  color: white;
}

.btn-action-reagendar:active {
  transform: scale(0.98);
  background: #0051d5;
}

/* Formulario Nueva Cita */
.modal-small {
  max-width: 500px;
}

/* Búsqueda de Cliente */
.search-client-wrapper {
  position: relative;
  flex: 1;
}

.search-client-input {
  position: relative;
  display: flex;
  align-items: center;
  gap: 10px;
}

.search-client-input i.fa-search {
  position: absolute;
  left: 16px;
  color: #999;
  font-size: 14px;
  z-index: 1;
}

.search-input {
  padding-left: 44px !important;
  padding-right: 44px !important;
}

.btn-clear-search {
  position: absolute;
  right: 12px;
  width: 28px;
  height: 28px;
  border: none;
  background: #f0f0f0;
  border-radius: 50%;
  color: #666;
  font-size: 12px;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all 0.2s;
  z-index: 1;
}

.btn-clear-search:hover {
  background: #e0e0e0;
  transform: scale(1.1);
}

.clientes-dropdown {
  position: absolute;
  top: calc(100% + 8px);
  left: 0;
  right: 0;
  background: white;
  border-radius: 12px;
  box-shadow: 0 8px 24px rgba(0, 0, 0, 0.15);
  max-height: 300px;
  overflow-y: auto;
  z-index: 1000;
  border: 2px solid #e9ecef;
  animation: slideDown 0.2s ease;
}

@keyframes slideDown {
  from {
    opacity: 0;
    transform: translateY(-10px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

.cliente-option {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 14px 16px;
  cursor: pointer;
  transition: all 0.2s;
  border-bottom: 1px solid #f0f0f0;
}

.cliente-option:last-child {
  border-bottom: none;
}

.cliente-option:hover {
  background: rgba(0, 122, 255, 0.05);
}

.cliente-option.selected {
  background: rgba(0, 122, 255, 0.1);
}

.cliente-info {
  display: flex;
  flex-direction: column;
  gap: 4px;
  flex: 1;
}

.cliente-nombre {
  font-size: 15px;
  font-weight: 700;
  color: #333;
}

.cliente-telefono {
  font-size: 12px;
  color: #999;
  display: flex;
  align-items: center;
  gap: 6px;
}

.cliente-telefono i {
  font-size: 10px;
}

.cliente-option i.fa-check {
  color: #007aff;
  font-size: 16px;
  flex-shrink: 0;
}

.clientes-dropdown.empty {
  padding: 40px 20px;
}

.no-results {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 12px;
  text-align: center;
}

.no-results i {
  font-size: 48px;
  color: #ccc;
}

.no-results p {
  margin: 0;
  color: #999;
  font-size: 14px;
}

.btn-create-client {
  padding: 12px 20px;
  border: none;
  border-radius: 12px;
  background: #007aff;
  color: white;
  font-size: 14px;
  font-weight: 600;
  cursor: pointer;
  display: flex;
  align-items: center;
  gap: 8px;
  transition: all 0.2s;
}

.btn-create-client:active {
  transform: scale(0.98);
  background: #0051d5;
}

.cliente-selected {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 12px 16px;
  background: rgba(52, 199, 89, 0.1);
  border-radius: 12px;
  border: 1px solid rgba(52, 199, 89, 0.2);
  margin-top: 10px;
}

.selected-info {
  display: flex;
  align-items: center;
  gap: 10px;
  flex: 1;
}

.selected-info i {
  color: #34c759;
  font-size: 18px;
}

.selected-info span:first-of-type {
  font-size: 15px;
  font-weight: 700;
  color: #333;
}

.selected-phone {
  font-size: 13px;
  color: #999;
  margin-left: 8px;
}

.btn-remove-selection {
  width: 32px;
  height: 32px;
  border: none;
  background: rgba(255, 59, 48, 0.1);
  border-radius: 8px;
  color: #ff3b30;
  font-size: 14px;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all 0.2s;
  flex-shrink: 0;
}

.btn-remove-selection:active {
  background: rgba(255, 59, 48, 0.2);
  transform: scale(0.95);
}

.clientes-dropdown::-webkit-scrollbar {
  width: 6px;
}

.clientes-dropdown::-webkit-scrollbar-track {
  background: #f1f1f1;
  border-radius: 3px;
}

.clientes-dropdown::-webkit-scrollbar-thumb {
  background: #c1c1c1;
  border-radius: 3px;
}

.clientes-dropdown::-webkit-scrollbar-thumb:hover {
  background: #a8a8a8;
}

/* Campos Nuevo Cliente Inline */
.nuevo-cliente-fields {
  margin-top: 16px;
  padding: 20px;
  background: rgba(0, 122, 255, 0.05);
  border-radius: 16px;
  border: 1px solid rgba(0, 122, 255, 0.2);
}

.nuevo-cliente-header {
  margin-bottom: 16px;
  padding-bottom: 12px;
  border-bottom: 1px solid rgba(0, 122, 255, 0.1);
}

.nuevo-cliente-header h4 {
  margin: 0;
  font-size: 16px;
  font-weight: 600;
  color: #007aff;
  display: flex;
  align-items: center;
  gap: 8px;
}

.nuevo-cliente-header i {
  font-size: 18px;
}

.nuevo-cliente-fields .form-row {
  margin-bottom: 12px;
}

.nuevo-cliente-fields .form-group {
  flex: 1;
}

.form-label-small {
  display: block;
  font-size: 12px;
  font-weight: 600;
  color: #007aff;
  margin-bottom: 6px;
  text-transform: uppercase;
  letter-spacing: 0.3px;
}

.nuevo-cliente-actions {
  display: flex;
  gap: 10px;
  margin-top: 16px;
  padding-top: 16px;
  border-top: 1px solid rgba(0, 122, 255, 0.1);
}

.btn-cancel-small,
.btn-submit-small {
  flex: 1;
  padding: 12px 16px;
  border: none;
  border-radius: 10px;
  font-size: 14px;
  font-weight: 700;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 6px;
  transition: all 0.2s;
}

.btn-cancel-small {
  background: #f5f5f7;
  color: #1d1d1f;
}

.btn-cancel-small:active {
  background: #e5e5ea;
  transform: scale(0.98);
}

.btn-submit-small {
  background: #007aff;
  color: white;
}

.btn-submit-small:active:not(:disabled) {
  transform: scale(0.98);
  background: #0051d5;
}

.btn-submit-small:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.btn-add-client.active {
  background: #ff3b30;
  transform: rotate(45deg);
}

/* Transición para campos nuevo cliente */
.slide-down-enter-active {
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.slide-down-leave-active {
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.slide-down-enter-from {
  opacity: 0;
  transform: translateY(-20px);
  max-height: 0;
}

.slide-down-leave-to {
  opacity: 0;
  transform: translateY(-20px);
  max-height: 0;
}

.slide-down-enter-to,
.slide-down-leave-from {
  opacity: 1;
  transform: translateY(0);
  max-height: 500px;
}

/* Contenedor Fecha y Hora */
.fecha-hora-container {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

/* Selector de Horarios */
.hora-select-wrapper {
  position: relative;
  width: 100%;
}

.hora-select-wrapper .form-select:disabled {
  background: #f5f5f5;
  color: #999;
  cursor: not-allowed;
}

.loading-horarios {
  position: absolute;
  right: 16px;
  top: 50%;
  transform: translateY(-50%);
  color: #667eea;
  pointer-events: none;
}

.form-hint {
  display: flex;
  align-items: center;
  gap: 8px;
  margin-top: 8px;
  padding: 10px 12px;
  background: rgba(0, 122, 255, 0.1);
  border-radius: 10px;
  font-size: 12px;
  color: #007aff;
  font-weight: 500;
}

.form-hint i {
  font-size: 14px;
  flex-shrink: 0;
}

/* Estados de Servicios */
.servicios-disabled,
.servicios-loading,
.servicios-empty {
  padding: 40px 20px;
  text-align: center;
  border-radius: 12px;
  background: #f8f9fa;
  border: 2px dashed #e9ecef;
}

.disabled-message,
.loading-message,
.empty-message {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 12px;
}

.disabled-message i,
.loading-message i,
.empty-message i {
  font-size: 48px;
  color: #ccc;
}

.loading-message i {
  color: #667eea;
}

.empty-message i {
  color: #fa709a;
}

.disabled-message p,
.loading-message p,
.empty-message p {
  margin: 0;
  color: #999;
  font-size: 14px;
  font-weight: 600;
}

.cita-form {
  display: flex;
  flex-direction: column;
  gap: 20px;
}

.form-section {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.form-label {
  display: flex;
  align-items: center;
  gap: 8px;
  font-size: 13px;
  font-weight: 600;
  color: #007aff;
  text-transform: uppercase;
  letter-spacing: 0.3px;
}

.form-label i {
  font-size: 14px;
  width: 20px;
  text-align: center;
}

.required {
  color: #fa709a;
}

.optional {
  color: #999;
  font-weight: 400;
  text-transform: none;
  font-size: 11px;
}

.form-row {
  display: flex;
  gap: 10px;
  align-items: center;
}

.form-select,
.form-input,
.form-textarea {
  width: 100%;
  padding: 14px 16px;
  border: 1px solid #e5e5ea;
  border-radius: 14px;
  font-size: 15px;
  background: white;
  color: #1d1d1f;
  transition: all 0.2s;
  font-family: inherit;
}

.form-select:focus,
.form-input:focus,
.form-textarea:focus {
  outline: none;
  border-color: #007aff;
  box-shadow: 0 0 0 4px rgba(0, 122, 255, 0.1);
}

.form-textarea {
  resize: vertical;
  min-height: 80px;
}

.btn-add-client {
  width: 44px;
  height: 44px;
  border: none;
  border-radius: 12px;
  background: #007aff;
  color: white;
  font-size: 16px;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
  transition: all 0.2s;
}

.btn-add-client:active {
  transform: scale(0.95);
  background: #0051d5;
}

/* Selector de Servicios */
.servicios-selector {
  display: flex;
  flex-direction: column;
  gap: 10px;
  max-height: 300px;
  overflow-y: auto;
  padding: 4px;
}

.servicio-option {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 14px;
  background: white;
  border: 2px solid #e9ecef;
  border-radius: 12px;
  cursor: pointer;
  transition: all 0.2s;
}

.servicio-option:hover {
  border-color: #007aff;
  background: rgba(0, 122, 255, 0.05);
}

.servicio-option.selected {
  border-color: #007aff;
  background: rgba(0, 122, 255, 0.1);
}

.servicio-checkbox {
  width: 24px;
  height: 24px;
  border: 2px solid #e9ecef;
  border-radius: 6px;
  display: flex;
  align-items: center;
  justify-content: center;
  background: white;
  transition: all 0.2s;
  flex-shrink: 0;
}

.servicio-option.selected .servicio-checkbox {
  background: #007aff;
  border-color: #007aff;
  color: white;
}

.servicio-option.selected .servicio-checkbox i {
  font-size: 12px;
}

.servicio-info {
  display: flex;
  flex-direction: column;
  gap: 4px;
  flex: 1;
}

.servicio-name {
  font-size: 15px;
  font-weight: 700;
  color: #333;
}

.servicio-details {
  font-size: 12px;
  color: #999;
  font-weight: 500;
}

.total-preview {
  background: #ffffff;
  padding: 18px;
  border-radius: 16px;
  border: 1px solid #f0f0f0;
}

.total-preview .total-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 8px;
}

.total-preview .total-label {
  font-size: 14px;
  font-weight: 600;
  color: #86868b;
  text-transform: uppercase;
  letter-spacing: 0.3px;
}

.total-preview .total-value {
  font-size: 24px;
  font-weight: 700;
  color: #1d1d1f;
  letter-spacing: -0.5px;
}

.total-duration {
  display: flex;
  align-items: center;
  gap: 6px;
  font-size: 12px;
  color: #86868b;
  font-weight: 500;
}

.total-duration i {
  font-size: 11px;
}

.form-error {
  font-size: 12px;
  color: #ff3b30;
  font-weight: 500;
  margin-top: 4px;
  display: flex;
  align-items: center;
  gap: 6px;
}

.form-actions {
  display: flex;
  gap: 12px;
  margin-top: 8px;
  padding-top: 20px;
  border-top: 1px solid #e9ecef;
}

.btn-cancel,
.btn-submit {
  flex: 1;
  padding: 16px;
  border: none;
  border-radius: 12px;
  font-size: 15px;
  font-weight: 700;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.btn-cancel {
  background: #f5f5f7;
  color: #1d1d1f;
}

.btn-cancel:active {
  background: #e5e5ea;
  transform: scale(0.98);
}

.btn-submit {
  background: #34c759;
  color: white;
}

.btn-submit:active:not(:disabled) {
  transform: scale(0.98);
  background: #30d158;
}

.btn-submit:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.btn-cancel:active,
.btn-submit:active:not(:disabled) {
  transform: translateY(0) scale(0.98);
}

/* Scrollbar para servicios */
.servicios-selector::-webkit-scrollbar {
  width: 6px;
}

.servicios-selector::-webkit-scrollbar-track {
  background: #f1f1f1;
  border-radius: 3px;
}

.servicios-selector::-webkit-scrollbar-thumb {
  background: #c1c1c1;
  border-radius: 3px;
}

.servicios-selector::-webkit-scrollbar-thumb:hover {
  background: #a8a8a8;
}

/* Reagendar Modal */
.reagendar-info {
  background: #ffffff;
  border-radius: 16px;
  padding: 20px;
  margin-bottom: 24px;
  border: 1px solid #f0f0f0;
}

.info-actual {
  margin-bottom: 16px;
}

.info-actual h4 {
  margin: 0 0 10px;
  font-size: 12px;
  font-weight: 700;
  color: #999;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.fecha-actual {
  display: flex;
  align-items: center;
  gap: 12px;
  font-size: 16px;
  font-weight: 700;
  color: #333;
}

.fecha-actual i {
  width: 32px;
  height: 32px;
  display: flex;
  align-items: center;
  justify-content: center;
  background: #007aff;
  color: white;
  border-radius: 10px;
  font-size: 14px;
}

.info-cliente {
  display: flex;
  flex-wrap: wrap;
  gap: 16px;
  padding-top: 16px;
  border-top: 1px solid #e0e0e0;
}

.info-cliente span {
  display: flex;
  align-items: center;
  gap: 8px;
  font-size: 14px;
  color: #666;
}

.info-cliente i {
  color: #007aff;
  font-size: 14px;
}

.reagendar-form {
  display: flex;
  flex-direction: column;
  gap: 20px;
}
</style>
