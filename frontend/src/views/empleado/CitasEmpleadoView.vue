<template>
  <div class="citas-view">
    <!-- Header Row -->
    <div class="header-row">
      <!-- Info Card -->
      <div class="header-info-card">
        <div class="header-icon">
          <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
            <line x1="16" y1="2" x2="16" y2="6"></line>
            <line x1="8" y1="2" x2="8" y2="6"></line>
            <line x1="3" y1="10" x2="21" y2="10"></line>
          </svg>
        </div>
        <div class="header-text">
          <h2>Mis Citas</h2>
          <p>{{ empleadoNombre }}</p>
        </div>
      </div>

      <!-- Stats -->
      <div class="stats-card">
        <div class="stat-item">
          <span class="stat-value">{{ citasPendientes }}</span>
          <span class="stat-label">Pendientes</span>
        </div>
      </div>
    </div>

    <!-- Filters -->
    <section class="filters-section">
      <div class="filters-row">
        <select 
          v-model="filtroEstado" 
          @change="cargarCitas" 
          class="filter-select"
        >
          <option value="">Todos los estados</option>
          <option value="pendiente">Pendiente</option>
          <option value="confirmada">Confirmada</option>
          <option value="en_proceso">En proceso</option>
          <option value="completada">Completada</option>
          <option value="cancelada">Cancelada</option>
          <option value="reagendada">Reagendada</option>
        </select>
        <input 
          type="date" 
          v-model="filtroFecha" 
          @change="cargarCitas"
          class="filter-date"
        />
      </div>
    </section>

    <!-- Citas List -->
    <div class="citas-container">
      <div v-if="loading" class="loading-container">
        <div class="loader"></div>
        <p>Cargando citas...</p>
      </div>
      
      <div v-else-if="citasFiltradas.length === 0" class="empty-state">
        <div class="empty-icon">
          <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
            <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
            <line x1="16" y1="2" x2="16" y2="6"></line>
            <line x1="8" y1="2" x2="8" y2="6"></line>
            <line x1="3" y1="10" x2="21" y2="10"></line>
          </svg>
        </div>
        <p>No hay citas que mostrar</p>
        <button v-if="filtroEstado || filtroFecha" class="clear-btn" @click="limpiarFiltros">
          Limpiar filtros
        </button>
      </div>
      
      <div v-else class="citas-list">
        <!-- Próxima cita destacada -->
        <div v-if="proximaCita" class="cita-card featured" @click="verDetalle(proximaCita)">
          <div class="featured-badge">
            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon>
            </svg>
            Próxima cita • {{ tiempoRestante(proximaCita) }}
          </div>
          <div class="cita-card-body">
            <div class="cita-time-row">
              <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="12" cy="12" r="10"></circle>
                <polyline points="12 6 12 12 16 14"></polyline>
              </svg>
              <span class="cita-time">{{ formatFecha(proximaCita.fecha_hora) }}</span>
              <span :class="['cita-status', proximaCita.estado]">
                {{ estadoTexto(proximaCita.estado) }}
              </span>
            </div>
            
            <div class="cita-info-grid">
              <div class="cita-info-item">
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                  <circle cx="12" cy="7" r="4"></circle>
                </svg>
                <span class="cita-info-label">Cliente</span>
                <span class="cita-info-value">{{ proximaCita.cliente?.nombre || 'Sin cliente' }}</span>
              </div>
            </div>
            
            <div class="cita-servicios-row">
              <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"></path>
              </svg>
              <span class="cita-servicios-text">{{ getServiciosNombres(proximaCita) }}</span>
            </div>
          </div>
        </div>

        <!-- Resto de citas -->
        <div 
          v-for="cita in otrasCitas" 
          :key="cita.id" 
          class="cita-card"
          @click="verDetalle(cita)"
        >
          <div class="cita-card-body">
            <div class="cita-time-row">
              <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="12" cy="12" r="10"></circle>
                <polyline points="12 6 12 12 16 14"></polyline>
              </svg>
              <span class="cita-time">{{ formatFecha(cita.fecha_hora) }}</span>
              <span :class="['cita-status', cita.estado]">
                {{ estadoTexto(cita.estado) }}
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
              <span class="price-value">${{ formatPrecio((cita as any).precio_final || (cita as any).precio_total || 0) }}</span>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Modal detalle -->
    <Teleport to="body">
      <Transition name="modal">
        <div v-if="citaDetalle" class="modal-overlay" @click="citaDetalle = null">
          <div class="modal-content" @click.stop>
            <div class="modal-header">
              <div>
                <h3>Detalle de Cita</h3>
                <p class="modal-subtitle">Cita #{{ citaDetalle.id }}</p>
              </div>
              <button class="modal-close" @click="citaDetalle = null">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <line x1="18" y1="6" x2="6" y2="18"></line>
                  <line x1="6" y1="6" x2="18" y2="18"></line>
                </svg>
              </button>
            </div>
            <div class="modal-body">
              <!-- Vista Detalle -->
              <div class="cita-detail">
                <!-- Estado Badge -->
                <div class="detail-status-badge">
                  <span :class="['status-badge-large', citaDetalle.estado]">
                    {{ estadoTexto(citaDetalle.estado) }}
                  </span>
                </div>

                <!-- Información Principal -->
                <div class="detail-section">
                  <div class="section-title">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                      <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                      <line x1="16" y1="2" x2="16" y2="6"></line>
                      <line x1="8" y1="2" x2="8" y2="6"></line>
                      <line x1="3" y1="10" x2="21" y2="10"></line>
                    </svg>
                    <span>Fecha y Hora</span>
                  </div>
                  <div class="info-block">
                    <div class="info-item">
                      <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="12" r="10"></circle>
                        <polyline points="12 6 12 12 16 14"></polyline>
                      </svg>
                      <div class="info-content">
                        <span class="info-label">Fecha</span>
                        <span class="info-value">{{ formatFechaCompleta(citaDetalle.fecha_hora) }}</span>
                      </div>
                    </div>
                    <div class="info-item">
                      <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="12" r="10"></circle>
                        <polyline points="12 6 12 12 16 14"></polyline>
                      </svg>
                      <div class="info-content">
                        <span class="info-label">Duración estimada</span>
                        <span class="info-value">{{ getDuracionTotal(citaDetalle) }} minutos</span>
                      </div>
                    </div>
                  </div>
                </div>

                <!-- Cliente -->
                <div class="detail-section">
                  <div class="section-title">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                      <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                      <circle cx="12" cy="7" r="4"></circle>
                    </svg>
                    <span>Cliente</span>
                  </div>
                  <div class="info-block">
                    <div class="info-item">
                      <div class="info-content">
                        <span class="info-label">Nombre</span>
                        <span class="info-value">{{ citaDetalle.cliente?.nombre || 'Sin cliente' }}</span>
                      </div>
                    </div>
                    <div class="info-item" v-if="citaDetalle.cliente?.telefono">
                      <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"></path>
                      </svg>
                      <div class="info-content">
                        <span class="info-label">Teléfono</span>
                        <span class="info-value">
                          <a :href="`tel:${citaDetalle.cliente.telefono}`" class="phone-link">
                            {{ citaDetalle.cliente.telefono }}
                          </a>
                        </span>
                      </div>
                    </div>
                    <div class="info-item" v-if="citaDetalle.cliente?.email">
                      <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path>
                        <polyline points="22,6 12,13 2,6"></polyline>
                      </svg>
                      <div class="info-content">
                        <span class="info-label">Email</span>
                        <span class="info-value">{{ citaDetalle.cliente.email }}</span>
                      </div>
                    </div>
                  </div>
                </div>

                <!-- Servicios -->
                <div class="detail-section">
                  <div class="section-title">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                      <path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"></path>
                    </svg>
                    <span>Servicios 
                      <span class="servicios-count-title" v-if="getServiciosList(citaDetalle).length > 1">
                        ({{ getServiciosList(citaDetalle).length }})
                      </span>
                    </span>
                  </div>
                  <div class="servicios-list">
                    <div 
                      v-for="(servicio, index) in getServiciosList(citaDetalle)" 
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
                    <span class="total-value">${{ formatPrecio((citaDetalle as any).precio_final || (citaDetalle as any).precio_total || 0) }}</span>
                  </div>
                </div>

                <!-- Notas -->
                <div class="detail-section" v-if="citaDetalle.notas">
                  <div class="section-title">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                      <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                      <polyline points="14 2 14 8 20 8"></polyline>
                      <line x1="16" y1="13" x2="8" y2="13"></line>
                      <line x1="16" y1="17" x2="8" y2="17"></line>
                      <polyline points="10 9 9 9 8 9"></polyline>
                    </svg>
                    <span>Notas</span>
                  </div>
                  <div class="notas-content">
                    {{ citaDetalle.notas }}
                  </div>
                </div>

                <!-- Acciones -->
                <div class="detail-actions">
                  <button 
                    v-if="citaDetalle.estado === 'confirmada'"
                    class="btn-action-secondary"
                    @click="cambiarEstado('en_proceso')"
                  >
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                      <polygon points="5 3 19 12 5 21 5 3"></polygon>
                    </svg>
                    Iniciar Cita
                  </button>
                  <button 
                    v-if="citaDetalle.estado !== 'cancelada' && citaDetalle.estado !== 'completada'"
                    class="btn-action-primary"
                    @click="cambiarEstado('completada')"
                  >
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                      <polyline points="20 6 9 17 4 12"></polyline>
                    </svg>
                    Marcar como Completada
                  </button>
                  <div class="action-buttons-group">
                    <button 
                      v-if="puedeReagendarCita(citaDetalle)"
                      class="btn-action-reagendar"
                      @click="abrirModalReagendar(citaDetalle)"
                    >
                      <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                        <line x1="16" y1="2" x2="16" y2="6"></line>
                        <line x1="8" y1="2" x2="8" y2="6"></line>
                        <line x1="3" y1="10" x2="21" y2="10"></line>
                      </svg>
                      Reagendar
                    </button>
                    <button 
                      v-if="puedeCancelarCita(citaDetalle)"
                      class="btn-action-cancel"
                      @click="confirmarCancelacion(citaDetalle)"
                    >
                      <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="18" y1="6" x2="6" y2="18"></line>
                        <line x1="6" y1="6" x2="18" y2="18"></line>
                      </svg>
                      Cancelar
                    </button>
                  </div>
                  <a 
                    v-if="citaDetalle.cliente?.telefono"
                    :href="`https://wa.me/52${citaDetalle.cliente.telefono}`"
                    target="_blank"
                    class="btn-action-whatsapp"
                  >
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="currentColor">
                      <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413Z"/>
                    </svg>
                    WhatsApp
                  </a>
                </div>
              </div>
            </div>
          </div>
        </div>
      </Transition>
    </Teleport>

    <!-- Modal Nueva Cita -->
    <Teleport to="body">
      <Transition name="modal">
        <div v-if="mostrarModalNuevaCita" class="modal-overlay" @click="cerrarModalNuevaCita">
          <div class="modal-content" @click.stop>
            <div class="modal-header">
              <h3>Nueva Cita</h3>
              <button class="modal-close" @click="cerrarModalNuevaCita">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <line x1="18" y1="6" x2="6" y2="18"></line>
                  <line x1="6" y1="6" x2="18" y2="18"></line>
                </svg>
              </button>
            </div>

            <div class="modal-body">
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
                        @input="onBuscarCliente"
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
                        :class="{ selected: clienteSeleccionado?.id === cliente.id }"
                        @click="seleccionarCliente(cliente)"
                      >
                        <div class="cliente-info">
                          <span class="cliente-nombre">{{ cliente.nombre }}</span>
                          <span class="cliente-telefono" v-if="cliente.telefono">
                            <i class="fa fa-phone"></i> {{ cliente.telefono }}
                          </span>
                        </div>
                        <i v-if="clienteSeleccionado?.id === cliente.id" class="fa fa-check"></i>
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
                  <div v-if="mostrarCamposNuevoCliente && !clienteSeleccionado" class="nuevo-cliente-fields">
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
                          placeholder="Nombre completo"
                        />
                      </div>
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
                        @click="guardarNuevoCliente"
                        :disabled="!puedeGuardarCliente"
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
                    <span>{{ clienteSeleccionado.nombre }}</span>
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

              <!-- Servicios -->
              <div class="form-section">
                <label class="form-label">
                  <i class="fa fa-cut"></i>
                  Servicios <span class="required">*</span>
                </label>
                <div v-if="cargandoServicios" class="servicios-loading">
                  <div class="loading-message">
                    <i class="fa fa-spinner fa-spin"></i>
                    <p>Cargando servicios...</p>
                  </div>
                </div>
                <div v-else-if="misServicios.length === 0" class="servicios-empty">
                  <div class="empty-message">
                    <i class="fa fa-exclamation-triangle"></i>
                    <p>No tienes servicios asignados</p>
                  </div>
                </div>
                <div v-else class="servicios-selector">
                  <div 
                    v-for="servicio in misServicios" 
                    :key="servicio.id"
                    class="servicio-option"
                    :class="{ selected: serviciosSeleccionados.includes(servicio.id) }"
                    @click="toggleServicio(servicio.id)"
                  >
                    <div class="servicio-checkbox">
                      <i class="fa fa-check" v-if="serviciosSeleccionados.includes(servicio.id)"></i>
                    </div>
                    <div class="servicio-info">
                      <span class="servicio-name">{{ servicio.nombre }}</span>
                      <span class="servicio-details" v-if="servicio.duracion > 0 || servicio.precio > 0">
                        {{ servicio.duracion || 0 }} min • 
                        ${{ formatPrecio(servicio.precio) }}
                      </span>
                      <span class="servicio-details" v-else style="color: #999; font-style: italic;">
                        Sin información de precio/duración
                      </span>
                    </div>
                  </div>
                </div>
                <div v-if="serviciosSeleccionados.length === 0 && misServicios.length > 0" class="form-error">
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
                    :disabled="serviciosSeleccionados.length === 0"
                    @change="onFechaChange"
                  />
                  <div class="hora-select-wrapper">
                    <select 
                      v-model="nuevaCitaData.hora" 
                      class="form-select"
                      required
                      :disabled="!nuevaCitaData.fecha || serviciosSeleccionados.length === 0 || cargandoHorarios"
                    >
                      <option value="">
                        {{ getHoraPlaceholder() }}
                      </option>
                      <option 
                        v-for="slot in horariosDisponibles" 
                        :key="slot.hora"
                        :value="slot.hora"
                      >
                        {{ formatHoraAmPm(slot.hora) }}
                      </option>
                    </select>
                    <div v-if="cargandoHorarios" class="loading-horarios">
                      <i class="fa fa-spinner fa-spin"></i>
                    </div>
                  </div>
                </div>
                <div v-if="serviciosSeleccionados.length === 0" class="form-hint">
                  <i class="fa fa-info-circle"></i>
                  Selecciona al menos un servicio para habilitar la fecha
                </div>
                <div v-if="!nuevaCitaData.fecha && serviciosSeleccionados.length > 0" class="form-hint">
                  <i class="fa fa-info-circle"></i>
                  Selecciona una fecha para ver los horarios disponibles
                </div>
                <div v-if="horariosDisponibles.length === 0 && nuevaCitaData.fecha && serviciosSeleccionados.length > 0 && !cargandoHorarios" class="form-error">
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

              <!-- Botones -->
              <div class="form-actions">
                <button type="button" class="btn-cancel" @click="cerrarModalNuevaCita">
                  Cancelar
                </button>
                <button type="submit" class="btn-submit" :disabled="!puedeGuardarCita || guardandoCita">
                  <i class="fa fa-save"></i>
                  {{ guardandoCita ? 'Guardando...' : 'Guardar Cita' }}
                </button>
              </div>
              </form>
            </div>
          </div>
        </div>
      </Transition>
    </Teleport>

    <!-- Botón flotante agregar cita -->
    <button class="btn-fab-add-cita" @click="abrirModalNuevaCita" title="Nueva cita">
      <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
        <line x1="12" y1="5" x2="12" y2="19"></line>
        <line x1="5" y1="12" x2="19" y2="12"></line>
      </svg>
    </button>

    <!-- Modal Reagendar Cita -->
    <Teleport to="body">
      <Transition name="modal">
        <div v-if="showModalReagendar" class="modal-overlay" @click="cerrarModalReagendar">
          <div class="modal-content" @click.stop>
            <div class="modal-header">
              <h3>Reagendar Cita #{{ citaAReagendar?.id }}</h3>
              <button class="modal-close" @click="cerrarModalReagendar">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <line x1="18" y1="6" x2="6" y2="18"></line>
                  <line x1="6" y1="6" x2="18" y2="18"></line>
                </svg>
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
                
                <div class="info-cliente-reagendar">
                  <span><i class="fa fa-user"></i> {{ citaAReagendar?.cliente?.nombre }}</span>
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
                        {{ formatHoraAmPm(slot.hora) }}
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
                  {{ guardandoReagendar ? 'Reagendando...' : 'Confirmar' }}
                </button>
              </div>
            </div>
          </div>
        </div>
      </Transition>
    </Teleport>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, watch } from 'vue'
import { useAuthStore } from '@/stores/auth'
import api from '@/services/api'
import disponibilidadService from '@/services/disponibilidadService'
import type { Cita } from '@/types'
import Swal from 'sweetalert2'

const authStore = useAuthStore()

const loading = ref(true)
const citas = ref<Cita[]>([])
const filtroEstado = ref('')
const filtroFecha = ref('')
const citaDetalle = ref<Cita | null>(null)

// Nueva cita
const mostrarModalNuevaCita = ref(false)
const clientes = ref<any[]>([])
const misServicios = ref<any[]>([])
const busquedaCliente = ref('')
const clienteSeleccionado = ref<any>(null)
const serviciosSeleccionados = ref<number[]>([])
const horariosDisponibles = ref<any[]>([])
const cargandoClientes = ref(false)
const cargandoServicios = ref(false)
const cargandoHorarios = ref(false)
const guardandoCita = ref(false)
const mostrarCamposNuevoCliente = ref(false)

const nuevaCitaData = ref({
  fecha: '',
  hora: '',
  notas: ''
})

const nuevoClienteData = ref({
  nombre: '',
  apellido: '',
  telefono: '',
  email: ''
})

// Reagendar cita
const showModalReagendar = ref(false)
const citaAReagendar = ref<any>(null)
const horariosReagendar = ref<any[]>([])
const cargandoHorariosReagendar = ref(false)
const guardandoReagendar = ref(false)
const reagendarData = ref({
  fecha: '',
  hora: '',
  motivo: '',
})

const empleadoNombre = computed(() => authStore.user?.nombre || 'Empleado')
const empleadoId = computed(() => {
  // Primero intentar obtener del objeto empleado guardado
  if (authStore.empleado?.id) {
    return authStore.empleado.id
  }
  // Fallback al user id
  return authStore.user?.id
})

// Las citas ya vienen filtradas del backend según los parámetros enviados
const citasFiltradas = computed(() => {
  return citas.value
})

const citasPendientes = computed(() => {
  return citas.value.filter(c => ['pendiente', 'confirmada'].includes(c.estado)).length
})

const proximaCita = computed(() => {
  return citasFiltradas.value.find(c => 
    c.estado === 'pendiente' || c.estado === 'confirmada'
  )
})

const otrasCitas = computed(() => {
  if (!proximaCita.value) return citasFiltradas.value
  return citasFiltradas.value.filter(c => c.id !== proximaCita.value?.id)
})

async function cargarCitas() {
  loading.value = true
  try {
    const params: any = {}
    
    // Enviar filtro de estado si está seleccionado
    if (filtroEstado.value) {
      params.estado = filtroEstado.value
    }
    
    // Enviar filtro de fecha si está seleccionado
    if (filtroFecha.value) {
      params.desde = filtroFecha.value + ' 00:00:00'
      params.hasta = filtroFecha.value + ' 23:59:59'
    }
    
    const response = await api.get('/empleado/citas', { params })
    citas.value = response.data.data?.citas || []
  } catch (error) {
    console.error('Error cargando citas:', error)
    citas.value = []
  } finally {
    loading.value = false
  }
}

function verDetalle(cita: Cita) {
  citaDetalle.value = cita
}

function limpiarFiltros() {
  filtroEstado.value = ''
  filtroFecha.value = ''
}

async function cambiarEstado(nuevoEstado: string) {
  if (!citaDetalle.value) return
  
  const confirmText = nuevoEstado === 'completada' 
    ? '¿Marcar esta cita como completada?'
    : nuevoEstado === 'no_show'
    ? '¿Marcar que el cliente no asistió?'
    : '¿Iniciar esta cita?'
  
  const result = await Swal.fire({
    title: '¿Estás seguro?',
    text: confirmText,
    icon: 'question',
    showCancelButton: true,
    confirmButtonColor: '#667eea',
    cancelButtonColor: '#999',
    confirmButtonText: 'Sí, continuar',
    cancelButtonText: 'Cancelar'
  })
  
  if (!result.isConfirmed) return
  
  try {
    await api.put(`/empleado/citas/${citaDetalle.value.id}`, { estado: nuevoEstado })
    const cita = citas.value.find(c => c.id === citaDetalle.value?.id)
    if (cita) {
      cita.estado = nuevoEstado as any
    }
    citaDetalle.value.estado = nuevoEstado as any
    
    await Swal.fire({
      icon: 'success',
      title: '¡Éxito!',
      text: 'Estado actualizado correctamente',
      confirmButtonColor: '#4caf50',
      timer: 2000,
      showConfirmButton: true
    })
    
    await cargarCitas()
  } catch (error) {
    console.error('Error:', error)
    Swal.fire({
      icon: 'error',
      title: 'Error',
      text: 'No se pudo actualizar el estado',
      confirmButtonColor: '#ec407a'
    })
  }
}

// ===== REAGENDAR Y CANCELAR CITA =====

function puedeReagendarCita(cita: any): boolean {
  if (!cita) return false
  // Solo se pueden reagendar citas pendientes o confirmadas
  return ['pendiente', 'confirmada'].includes(cita.estado)
}

function puedeCancelarCita(cita: any): boolean {
  if (!cita) return false
  // No se pueden cancelar citas ya terminadas
  return !['cancelada', 'completada', 'no_show'].includes(cita.estado)
}

function abrirModalReagendar(cita: any) {
  citaAReagendar.value = cita
  reagendarData.value = {
    fecha: '',
    hora: '',
    motivo: '',
  }
  horariosReagendar.value = []
  showModalReagendar.value = true
  citaDetalle.value = null // Cerrar modal de detalle
}

function cerrarModalReagendar() {
  showModalReagendar.value = false
  citaAReagendar.value = null
  reagendarData.value = {
    fecha: '',
    hora: '',
    motivo: '',
  }
  horariosReagendar.value = []
}

async function cargarHorariosReagendar() {
  if (!reagendarData.value.fecha || !citaAReagendar.value) {
    horariosReagendar.value = []
    return
  }

  cargandoHorariosReagendar.value = true
  reagendarData.value.hora = ''

  try {
    // Obtener los IDs de los servicios de la cita
    const serviciosIds = citaAReagendar.value.servicios?.map((s: any) => s.id || s.servicio_id) || []
    
    if (serviciosIds.length === 0) {
      console.warn('La cita no tiene servicios')
      horariosReagendar.value = []
      return
    }

    const response = await disponibilidadService.obtenerSlots(
      empleadoId.value as number,
      reagendarData.value.fecha,
      serviciosIds,
      true // Es empleado, ignorar anticipación mínima
    )

    // Filtrar solo los slots disponibles
    horariosReagendar.value = (response.slots || []).filter((slot: any) => {
      return slot.disponible !== false
    }).map((slot: any) => ({
      hora: slot.hora || slot.hora_inicio,
      hora_fin: slot.hora_fin,
      disponible: true
    }))

  } catch (error) {
    console.error('Error cargando horarios para reagendar:', error)
    horariosReagendar.value = []
  } finally {
    cargandoHorariosReagendar.value = false
  }
}

function getPlaceholderHoraReagendar(): string {
  if (cargandoHorariosReagendar.value) return 'Cargando horarios...'
  if (!reagendarData.value.fecha) return 'Selecciona una fecha primero'
  if (horariosReagendar.value.length === 0) return 'Sin horarios disponibles'
  return 'Seleccionar hora'
}

async function confirmarReagendar() {
  if (!puedeConfirmarReagendar.value || !citaAReagendar.value) return

  const result = await Swal.fire({
    title: '¿Confirmar reagendamiento?',
    html: `
      <div style="text-align: left; padding: 10px 0;">
        <p><strong>Nueva fecha:</strong> ${reagendarData.value.fecha}</p>
        <p><strong>Nueva hora:</strong> ${formatHoraAmPm(reagendarData.value.hora)}</p>
        <p style="color: #666; font-size: 12px; margin-top: 10px;">
          <i>La cita original quedará marcada como "reagendada" y se creará una nueva cita.</i>
        </p>
      </div>
    `,
    icon: 'question',
    showCancelButton: true,
    confirmButtonColor: '#667eea',
    cancelButtonColor: '#999',
    confirmButtonText: 'Sí, reagendar',
    cancelButtonText: 'Cancelar',
    reverseButtons: true
  })

  if (!result.isConfirmed) return

  guardandoReagendar.value = true

  try {
    // Formatear fecha y hora correctamente (Y-m-d H:i:s)
    let hora = reagendarData.value.hora
    // Asegurar formato HH:mm:ss
    if (hora.split(':').length === 2) {
      hora = `${hora}:00`
    }
    const fechaHora = `${reagendarData.value.fecha} ${hora}`
    
    console.log('Enviando fecha_hora:', fechaHora)
    
    // Usar el nuevo endpoint de reagendamiento
    const response = await api.post(`/empleado/citas/${citaAReagendar.value.id}/reagendar`, {
      fecha_hora: fechaHora,
      motivo: reagendarData.value.motivo || null
    })
    
    if (response.data.success) {
      await cargarCitas()
      cerrarModalReagendar()

      await Swal.fire({
        icon: 'success',
        title: '¡Cita reagendada!',
        text: 'Se ha creado una nueva cita con la fecha actualizada',
        confirmButtonColor: '#4caf50',
        timer: 2500,
        showConfirmButton: true
      })
    } else {
      throw new Error(response.data.message || 'Error al reagendar')
    }

  } catch (error: any) {
    console.error('Error reagendando cita:', error)
    console.error('Response data:', error.response?.data)
    console.error('Response status:', error.response?.status)
    
    // Mostrar mensaje más detallado
    let errorMsg = 'No se pudo reagendar la cita'
    if (error.response?.data?.message) {
      errorMsg = error.response.data.message
    } else if (error.response?.data?.errors) {
      // Errores de validación de Laravel
      const errores = Object.values(error.response.data.errors).flat()
      errorMsg = errores.join('\n')
    }
    
    Swal.fire({
      icon: 'error',
      title: 'Error',
      text: errorMsg,
      confirmButtonColor: '#ec407a'
    })
  } finally {
    guardandoReagendar.value = false
  }
}

async function confirmarCancelacion(cita: any) {
  const result = await Swal.fire({
    title: '¿Cancelar esta cita?',
    html: `
      <div style="text-align: left; padding: 10px 0;">
        <p><strong>Cliente:</strong> ${cita.cliente?.nombre || 'Sin cliente'}</p>
        <p><strong>Fecha:</strong> ${formatFechaCompleta(cita.fecha_hora)}</p>
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
  })

  if (result.isConfirmed) {
    await cancelarCitaEmpleado(cita)
  }
}

async function cancelarCitaEmpleado(cita: any) {
  try {
    // Usar el nuevo endpoint de cancelación
    const response = await api.post(`/empleado/citas/${cita.id}/cancelar`, {
      motivo: 'Cancelado por empleado'
    })
    
    if (response.data.success) {
      await cargarCitas()
      citaDetalle.value = null
      
      await Swal.fire({
        icon: 'success',
        title: '¡Cita cancelada!',
        text: 'La cita ha sido cancelada exitosamente',
        confirmButtonColor: '#4caf50',
        timer: 2000,
        showConfirmButton: true
      })
    } else {
      throw new Error(response.data.message || 'Error al cancelar')
    }
  } catch (error: any) {
    console.error('Error cancelando cita:', error)
    Swal.fire({
      icon: 'error',
      title: 'Error',
      text: error.response?.data?.message || error.message || 'No se pudo cancelar la cita',
      confirmButtonColor: '#ec407a'
    })
  }
}

function formatPrecio(precio: any): string {
  return (Number(precio) || 0).toFixed(2)
}

function tiempoRestante(cita: Cita): string {
  const fechaCita = new Date(cita.fecha_hora.replace(' ', 'T'))
  const ahora = new Date()
  const diff = fechaCita.getTime() - ahora.getTime()
  
  if (diff < 0) return 'Ahora'
  
  const minutos = Math.floor(diff / 60000)
  const horas = Math.floor(minutos / 60)
  const dias = Math.floor(horas / 24)
  
  if (dias > 0) return `En ${dias} día${dias > 1 ? 's' : ''}`
  if (horas > 0) return `En ${horas} hora${horas > 1 ? 's' : ''}`
  return `En ${minutos} min`
}

function formatFecha(fecha: string): string {
  if (!fecha) return '--:--'
  const fechaISO = fecha.replace(' ', 'T')
  const date = new Date(fechaISO)
  const hoy = new Date()
  const manana = new Date(hoy)
  manana.setDate(manana.getDate() + 1)
  
  // Si es hoy, mostrar solo la hora
  if (date.toDateString() === hoy.toDateString()) {
    return `Hoy ${date.toLocaleTimeString('es-MX', { hour: '2-digit', minute: '2-digit' })}`
  }
  
  // Si es mañana
  if (date.toDateString() === manana.toDateString()) {
    return `Mañana ${date.toLocaleTimeString('es-MX', { hour: '2-digit', minute: '2-digit' })}`
  }
  
  // Otros días
  return date.toLocaleDateString('es-MX', {
    weekday: 'short',
    day: 'numeric',
    month: 'short',
    hour: '2-digit',
    minute: '2-digit',
  })
}

function formatHora(fecha: string): string {
  const fechaISO = fecha.replace(' ', 'T')
  return new Date(fechaISO).toLocaleTimeString('es-MX', {
    hour: '2-digit',
    minute: '2-digit',
  })
}

function getServiciosNombres(cita: any): string {
  const servicios = getServiciosList(cita)
  if (servicios.length === 0) return 'Sin servicio'
  if (servicios.length === 1) return servicios[0].nombre
  return `${servicios.length} servicios: ${servicios.map((s: any) => s.nombre).join(', ')}`
}

function formatFechaCompleta(fecha: string): string {
  const fechaISO = fecha.replace(' ', 'T')
  return new Date(fechaISO).toLocaleString('es-MX', {
    weekday: 'long',
    day: 'numeric',
    month: 'long',
    hour: '2-digit',
    minute: '2-digit',
  })
}

function estadoTexto(estado: string): string {
  const estados: Record<string, string> = {
    pendiente: 'Pendiente',
    confirmada: 'Confirmada',
    en_proceso: 'En proceso',
    completada: 'Completada',
    cancelada: 'Cancelada',
    no_show: 'No asistió',
    reagendada: 'Reagendada',
  }
  return estados[estado] || estado
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

// ===== NUEVA CITA =====
const clientesFiltrados = computed(() => {
  if (!busquedaCliente.value || busquedaCliente.value.length < 2) return []
  const search = busquedaCliente.value.toLowerCase()
  return clientes.value.filter(c => {
    const nombre = (c.nombre || '').toLowerCase()
    const telefono = (c.telefono || '').toString()
    return nombre.includes(search) || telefono.includes(search)
  }).slice(0, 5)
})

const minDate = computed(() => {
  const today = new Date()
  return today.toISOString().split('T')[0]
})

const minDateReagendar = computed(() => {
  const today = new Date()
  return today.toISOString().split('T')[0]
})

const puedeConfirmarReagendar = computed(() => {
  return reagendarData.value.fecha && reagendarData.value.hora
})

const duracionTotal = computed(() => {
  const total = serviciosSeleccionados.value.reduce((total, id) => {
    const servicio = misServicios.value.find(s => s.id === id)
    const duracion = servicio?.duracion || 0
    return total + (typeof duracion === 'number' ? duracion : 0)
  }, 0)
  return isNaN(total) ? 0 : total
})

const precioTotal = computed(() => {
  const total = serviciosSeleccionados.value.reduce((total, id) => {
    const servicio = misServicios.value.find(s => s.id === id)
    const precio = servicio?.precio || 0
    return total + (typeof precio === 'number' ? precio : 0)
  }, 0)
  return isNaN(total) ? 0 : total
})

const puedeGuardarCliente = computed(() => {
  return nuevoClienteData.value.nombre.trim() && 
         nuevoClienteData.value.telefono.trim().length >= 10
})

const puedeGuardarCita = computed(() => {
  return clienteSeleccionado.value && 
         serviciosSeleccionados.value.length > 0 && 
         nuevaCitaData.value.fecha && 
         nuevaCitaData.value.hora
})

function abrirModalNuevaCita() {
  mostrarModalNuevaCita.value = true
  console.log('Abriendo modal nueva cita')
  console.log('Auth user:', authStore.user)
  console.log('Empleado ID:', empleadoId.value)
  cargarClientes()
  cargarMisServicios()
}

function cerrarModalNuevaCita() {
  mostrarModalNuevaCita.value = false
  resetFormNuevaCita()
}

function resetFormNuevaCita() {
  busquedaCliente.value = ''
  clienteSeleccionado.value = null
  serviciosSeleccionados.value = []
  horariosDisponibles.value = []
  nuevaCitaData.value = { fecha: '', hora: '', notas: '' }
  nuevoClienteData.value = { nombre: '', apellido: '', telefono: '', email: '' }
  mostrarCamposNuevoCliente.value = false
}

async function cargarClientes() {
  cargandoClientes.value = true
  console.log('Cargando clientes...')
  try {
    const response = await api.get('/empleado/clientes')
    console.log('Respuesta clientes:', response.data)
    clientes.value = response.data.data || response.data || []
    console.log('Clientes cargados:', clientes.value.length)
  } catch (error) {
    console.error('Error cargando clientes:', error)
    clientes.value = []
  } finally {
    cargandoClientes.value = false
  }
}

async function cargarMisServicios() {
  cargandoServicios.value = true
  console.log('Cargando mis servicios...')
  console.log('Empleado ID:', empleadoId.value)
  console.log('Auth empleado:', authStore.empleado)
  console.log('Auth user:', authStore.user)
  try {
    const response = await api.get('/empleado/mis-servicios')
    console.log('Respuesta mis-servicios:', response.data)
    const data = response.data.data || response.data || []
    misServicios.value = data.map((s: any) => {
      const precioRaw = s.precio_estandar || s.precio_especial || s.precio || 0
      const precio = typeof precioRaw === 'number' ? precioRaw : (typeof precioRaw === 'string' ? parseFloat(precioRaw) : 0)
      const duracionRaw = s.duracion_minutos || s.duracion || 0
      const duracion = typeof duracionRaw === 'number' ? duracionRaw : (typeof duracionRaw === 'string' ? parseInt(duracionRaw) : 0)
      
      return {
        id: s.id,
        nombre: s.nombre,
        duracion: isNaN(duracion) ? 0 : duracion,
        precio: isNaN(precio) ? 0 : precio
      }
    })
    console.log('Mis servicios mapeados:', misServicios.value)
  } catch (error) {
    console.error('Error cargando servicios:', error)
    misServicios.value = []
  } finally {
    cargandoServicios.value = false
  }
}

async function cargarHorariosDisponibles() {
  console.log('=== Cargar Horarios ===')
  console.log('Fecha:', nuevaCitaData.value.fecha)
  console.log('Servicios seleccionados:', serviciosSeleccionados.value)
  console.log('Empleado ID:', empleadoId.value)
  
  if (!nuevaCitaData.value.fecha || serviciosSeleccionados.value.length === 0) {
    console.log('Falta fecha o servicios, limpiando horarios')
    horariosDisponibles.value = []
    return
  }
  
  if (!empleadoId.value) {
    console.error('No hay empleado ID')
    horariosDisponibles.value = []
    return
  }
  
  cargandoHorarios.value = true
  nuevaCitaData.value.hora = ''
  
  try {
    console.log('Llamando a obtenerSlots con:', {
      empleadoId: empleadoId.value,
      fecha: nuevaCitaData.value.fecha,
      servicios: serviciosSeleccionados.value
    })
    
    // obtenerSlots espera un array de IDs de servicios
    // Pasamos true para indicar que es un empleado (ignora anticipación mínima)
    const response = await disponibilidadService.obtenerSlots(
      empleadoId.value as number,
      nuevaCitaData.value.fecha,
      serviciosSeleccionados.value,
      true // Es empleado, ignorar anticipación mínima
    )
    
    console.log('Respuesta obtenerSlots:', response)
    const responseData = response as any
    console.log('Horario del empleado:', responseData.horario_empleado)
    console.log('Bloqueos:', responseData.bloqueos_count)
    console.log('Citas existentes:', responseData.citas_count)
    console.log('Slots recibidos:', response.slots)
    console.log('Detalle de slots:', response.slots.map((s: any) => `${s.hora} - ${s.hora_fin}`))
    
    // Si los slots no tienen propiedad 'disponible', asumimos que todos están disponibles
    // Si tienen la propiedad, filtramos solo los disponibles
    const slotsDisponibles = (response.slots || []).filter((slot: any) => {
      // Si no tiene propiedad disponible, está disponible
      if (slot.disponible === undefined) {
        return true
      }
      // Si tiene la propiedad, debe ser true
      return slot.disponible === true
    })
    
    console.log('Slots disponibles filtrados:', slotsDisponibles)
    console.log('Horarios disponibles:', slotsDisponibles.map((s: any) => `${s.hora} - ${s.hora_fin}`))
    console.log('Total slots disponibles:', slotsDisponibles.length)
    
    // Si hay horario del empleado pero no hay slots disponibles, mostrar mensaje
    if (responseData.horario_empleado && slotsDisponibles.length === 0) {
      console.warn('No hay slots disponibles aunque el empleado tiene horario:', responseData.horario_empleado)
    }
    
    horariosDisponibles.value = slotsDisponibles
  } catch (error: any) {
    console.error('Error cargando horarios:', error)
    console.error('Error response:', error.response?.data)
    horariosDisponibles.value = []
  } finally {
    cargandoHorarios.value = false
  }
}

function onBuscarCliente() {
  // Trigger reactivity
}

function limpiarBusquedaCliente() {
  busquedaCliente.value = ''
}

function seleccionarCliente(cliente: any) {
  clienteSeleccionado.value = cliente
  busquedaCliente.value = ''
}

function deseleccionarCliente() {
  clienteSeleccionado.value = null
}

function toggleServicio(servicioId: number) {
  const index = serviciosSeleccionados.value.indexOf(servicioId)
  if (index > -1) {
    serviciosSeleccionados.value.splice(index, 1)
  } else {
    serviciosSeleccionados.value.push(servicioId)
  }
  onServiciosChange()
}

function onServiciosChange() {
  console.log('Servicios cambiaron:', serviciosSeleccionados.value)
  // Si ya hay una fecha seleccionada, recargar horarios
  if (nuevaCitaData.value.fecha && serviciosSeleccionados.value.length > 0) {
    nuevaCitaData.value.hora = ''
    cargarHorariosDisponibles()
  } else {
    nuevaCitaData.value.fecha = ''
    nuevaCitaData.value.hora = ''
    horariosDisponibles.value = []
  }
}

function onFechaChange() {
  console.log('Fecha cambió:', nuevaCitaData.value.fecha)
  if (serviciosSeleccionados.value.length > 0) {
    cargarHorariosDisponibles()
  }
}

function getHoraPlaceholder(): string {
  if (!nuevaCitaData.value.fecha) return 'Selecciona fecha primero'
  if (cargandoHorarios.value) return 'Cargando horarios...'
  if (horariosDisponibles.value.length === 0) return 'Sin horarios disponibles'
  return 'Seleccionar hora'
}

function formatHoraAmPm(hora: string): string {
  if (!hora) return ''
  const parts = hora.split(':')
  const h = parts[0] || '0'
  const m = parts[1] || '00'
  const hour = parseInt(h)
  const ampm = hour >= 12 ? 'PM' : 'AM'
  const hour12 = hour % 12 || 12
  return `${hour12}:${m} ${ampm}`
}

function cancelarNuevoCliente() {
  mostrarCamposNuevoCliente.value = false
  nuevoClienteData.value = { nombre: '', apellido: '', telefono: '', email: '' }
}


async function guardarNuevoCliente() {
  console.log('Guardando nuevo cliente:', nuevoClienteData.value)
  console.log('Puede guardar cliente:', puedeGuardarCliente.value)
  
  if (!puedeGuardarCliente.value) {
    console.log('No se puede guardar: falta nombre o teléfono')
    return
  }
  
  try {
    const response = await api.post('/empleado/clientes', nuevoClienteData.value)
    console.log('Respuesta guardar cliente:', response.data)
    const nuevoCliente = response.data.data || response.data
    clientes.value.push(nuevoCliente)
    clienteSeleccionado.value = nuevoCliente
    mostrarCamposNuevoCliente.value = false
    nuevoClienteData.value = { nombre: '', apellido: '', telefono: '', email: '' }
  } catch (error: any) {
    console.error('Error creando cliente:', error)
    console.error('Error response:', error.response?.data)
    Swal.fire({
      icon: 'error',
      title: 'Error',
      text: error.response?.data?.message || 'Error al crear el cliente',
      confirmButtonColor: '#ec407a',
      confirmButtonText: 'Aceptar'
    })
  }
}

async function guardarCita() {
  console.log('Guardando cita...')
  console.log('Puede guardar cita:', puedeGuardarCita.value)
  console.log('Cliente seleccionado:', clienteSeleccionado.value)
  console.log('Servicios seleccionados:', serviciosSeleccionados.value)
  console.log('Fecha:', nuevaCitaData.value.fecha)
  console.log('Hora:', nuevaCitaData.value.hora)
  
  if (!puedeGuardarCita.value || guardandoCita.value) return
  
  guardandoCita.value = true
  
  try {
    const fechaHora = `${nuevaCitaData.value.fecha} ${nuevaCitaData.value.hora}:00`
    
    const payload = {
      cliente_id: clienteSeleccionado.value.id,
      empleado_id: empleadoId.value,
      servicios: serviciosSeleccionados.value.map(id => {
        const servicio = misServicios.value.find(s => s.id === id)
        return { id, duracion: servicio?.duracion || 30 }
      }),
      fecha_hora: fechaHora,
      estado: 'confirmada',
      notas: nuevaCitaData.value.notas || null
    }
    
    console.log('Payload cita:', payload)
    
    const response = await api.post('/empleado/citas', payload)
    console.log('Respuesta guardar cita:', response.data)
    
    await cargarCitas()
    cerrarModalNuevaCita()
    Swal.fire({
      icon: 'success',
      title: '¡Éxito!',
      text: 'Cita creada exitosamente',
      confirmButtonColor: '#4caf50',
      confirmButtonText: 'Aceptar',
      timer: 2000,
      showConfirmButton: true
    })
  } catch (error: any) {
    console.error('Error guardando cita:', error)
    console.error('Error response:', error.response?.data)
    const msg = error.response?.data?.message || 'Error al crear la cita'
    Swal.fire({
      icon: 'error',
      title: 'Error',
      text: msg,
      confirmButtonColor: '#ec407a',
      confirmButtonText: 'Aceptar'
    })
  } finally {
    guardandoCita.value = false
  }
}

onMounted(() => {
  const today = new Date()
  filtroFecha.value = today.toISOString().split('T')[0] || ''
  cargarCitas()
})
</script>

<style scoped>
/* ===== Apple-inspired Citas Empleado View Design ===== */

.citas-view {
  min-height: 100vh;
  background: #f5f5f7;
  padding-bottom: 100px;
  font-family: -apple-system, BlinkMacSystemFont, 'SF Pro Display', 'SF Pro Text', 'Helvetica Neue', sans-serif;
  -webkit-font-smoothing: antialiased;
  -moz-osx-font-smoothing: grayscale;
}

/* ===== HEADER ROW ===== */
.header-row {
  display: flex;
  gap: 12px;
  padding: 16px;
  align-items: stretch;
}

/* ===== HEADER INFO CARD ===== */
.header-info-card {
  flex: 0 0 calc(66.666% - 6px);
  display: flex;
  align-items: center;
  gap: 14px;
  padding: 16px;
  background: linear-gradient(135deg, #1d1d1f 0%, #3a3a3c 100%);
  border-radius: 16px;
  box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.header-icon {
  width: 50px;
  height: 50px;
  border-radius: 14px;
  background: rgba(255, 255, 255, 0.1);
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

.header-icon svg {
  color: white;
}

.header-text {
  flex: 1;
  min-width: 0;
}

.header-text h2 {
  font-size: 20px;
  font-weight: 700;
  color: white;
  margin: 0;
}

.header-text p {
  font-size: 13px;
  color: rgba(255, 255, 255, 0.7);
  margin: 4px 0 0;
}

/* ===== STATS CARD ===== */
.stats-card {
  flex: 0 0 calc(33.333% - 6px);
}

.stat-item {
  height: 100%;
  background: linear-gradient(135deg, #1d1d1f 0%, #3a3a3c 100%);
  padding: 16px 12px;
  border-radius: 16px;
  text-align: center;
  box-shadow: 0 2px 8px rgba(0,0,0,0.1);
  display: flex;
  flex-direction: column;
  justify-content: center;
}

.stat-value {
  display: block;
  font-size: 28px;
  font-weight: 800;
  color: white;
  line-height: 1;
  margin-bottom: 4px;
}

.stat-label {
  font-size: 11px;
  color: rgba(255,255,255,0.7);
  text-transform: uppercase;
  letter-spacing: 0.5px;
  font-weight: 600;
}

/* ===== FILTERS ===== */
.filters-section {
  padding: 0 20px 20px;
}

.filters-row {
  display: flex;
  gap: 12px;
}

.filter-select,
.filter-date {
  flex: 1;
  padding: 14px 16px;
  border: 1px solid #e5e5ea;
  border-radius: 14px;
  font-size: 15px;
  background: white;
  color: #1d1d1f;
  transition: all 0.2s;
  font-family: inherit;
}

.filter-select:focus,
.filter-date:focus {
  outline: none;
  border-color: #007aff;
  box-shadow: 0 0 0 4px rgba(0, 122, 255, 0.1);
}

/* ===== LOADING & EMPTY ===== */
.citas-container {
  padding: 0 20px;
}

.loading-container {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 60px 20px;
  text-align: center;
}

.loader {
  width: 32px;
  height: 32px;
  border: 3px solid #f5f5f7;
  border-top-color: #007aff;
  border-radius: 50%;
  animation: spin 0.8s linear infinite;
  margin-bottom: 16px;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}

.loading-container p {
  color: #86868b;
  font-size: 15px;
  margin: 0;
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
  margin: 0 0 20px;
}

.clear-btn {
  padding: 12px 20px;
  background: #007aff;
  color: white;
  border: none;
  border-radius: 12px;
  font-size: 14px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s;
}

.clear-btn:active {
  transform: scale(0.98);
  background: #0051d5;
}

/* ===== CITAS LIST ===== */
.citas-list {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.cita-card {
  background: #ffffff;
  border-radius: 16px;
  overflow: hidden;
  cursor: pointer;
  transition: all 0.2s ease;
  border: 1px solid #e5e5ea;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
}

.cita-card:active {
  transform: scale(0.99);
  background: #f8f9fa;
}

.cita-card.featured {
  border: 2px solid #ff9500;
  background: linear-gradient(135deg, #fff8e1 0%, #ffffff 100%);
  box-shadow: 0 4px 16px rgba(255, 149, 0, 0.2);
}

.featured-badge {
  display: flex;
  align-items: center;
  gap: 6px;
  padding: 8px 16px;
  background: rgba(255, 149, 0, 0.15);
  font-size: 12px;
  font-weight: 600;
  color: #ff9500;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.featured-badge svg {
  width: 14px;
  height: 14px;
}

.cita-card-body {
  padding: 18px 20px;
}

.cita-time-row {
  display: flex;
  align-items: center;
  gap: 10px;
  margin-bottom: 14px;
}

.cita-time-row svg {
  color: #007aff;
  flex-shrink: 0;
}

.cita-time {
  font-size: 15px;
  font-weight: 600;
  color: #1d1d1f;
  letter-spacing: -0.2px;
  flex: 1;
}

.cita-status {
  font-size: 11px;
  font-weight: 600;
  padding: 5px 11px;
  border-radius: 12px;
  white-space: nowrap;
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

.cita-status.reagendada {
  background: rgba(255, 149, 0, 0.12);
  color: #ff9500;
}

.cita-info-grid {
  display: grid;
  grid-template-columns: 1fr;
  gap: 12px;
  margin-bottom: 14px;
}

.cita-info-item {
  display: flex;
  align-items: center;
  gap: 10px;
}

.cita-info-item svg {
  color: #86868b;
  flex-shrink: 0;
}

.cita-info-label {
  font-size: 12px;
  color: #86868b;
  text-transform: uppercase;
  letter-spacing: 0.3px;
  font-weight: 500;
  min-width: 60px;
}

.cita-info-value {
  font-size: 15px;
  font-weight: 600;
  color: #1d1d1f;
  flex: 1;
}

.cita-servicios-row {
  display: flex;
  align-items: center;
  gap: 8px;
  padding-top: 14px;
  border-top: 1px solid #f0f0f0;
}

.cita-servicios-row svg {
  color: #86868b;
  flex-shrink: 0;
}

.cita-servicios-text {
  font-size: 13px;
  color: #86868b;
  flex: 1;
  line-height: 1.4;
}

.cita-card-footer {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 14px 20px;
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
  color: #86868b;
  text-transform: uppercase;
  letter-spacing: 0.3px;
  font-weight: 500;
}

.price-value {
  font-size: 18px;
  font-weight: 700;
  color: #1d1d1f;
  letter-spacing: -0.3px;
}

/* ===== MODAL ===== */
.modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.4);
  backdrop-filter: blur(8px);
  display: flex;
  align-items: flex-end;
  justify-content: center;
  z-index: 1000;
  padding: 0;
  animation: fadeIn 0.2s ease;
}

@keyframes fadeIn {
  from { opacity: 0; }
  to { opacity: 1; }
}

.modal-content {
  background: #ffffff;
  width: 100%;
  max-height: 90vh;
  border-radius: 28px 28px 0 0;
  overflow: hidden;
  animation: slideUp 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  box-shadow: 0 -4px 24px rgba(0, 0, 0, 0.2);
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
  background: linear-gradient(135deg, #f8f9fa 0%, #ffffff 100%);
}

.modal-header h3 {
  margin: 0;
  font-size: 20px;
  font-weight: 600;
  color: #1d1d1f;
  letter-spacing: -0.3px;
}

.modal-subtitle {
  font-size: 13px;
  color: #86868b;
  margin: 4px 0 0;
  font-weight: 400;
}

.modal-close {
  width: 32px;
  height: 32px;
  border: none;
  border-radius: 8px;
  background: #f5f5f7;
  color: #86868b;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all 0.2s;
}

.modal-close:active {
  background: #ebebed;
  transform: scale(0.95);
}

.modal-body {
  padding: 24px;
  overflow-y: auto;
  max-height: calc(90vh - 80px);
  background: #fafafa;
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

/* Cita Detail */
.cita-detail {
  display: flex;
  flex-direction: column;
  gap: 20px;
}

.detail-status-badge {
  display: flex;
  justify-content: center;
  margin-bottom: 8px;
}

.status-badge-large {
  padding: 10px 18px;
  border-radius: 20px;
  font-size: 13px;
  font-weight: 600;
  text-transform: capitalize;
  letter-spacing: 0.3px;
}

.status-badge-large.pendiente {
  background: rgba(255, 149, 0, 0.12);
  color: #ff9500;
}

.status-badge-large.confirmada {
  background: rgba(52, 199, 89, 0.12);
  color: #34c759;
}

.status-badge-large.en_proceso {
  background: rgba(0, 122, 255, 0.12);
  color: #007aff;
}

.status-badge-large.completada {
  background: rgba(52, 199, 89, 0.12);
  color: #34c759;
}

.status-badge-large.cancelada {
  background: rgba(255, 59, 48, 0.12);
  color: #ff3b30;
}

.status-badge-large.reagendada {
  background: rgba(255, 149, 0, 0.12);
  color: #ff9500;
}

.detail-section {
  background: #ffffff;
  border-radius: 16px;
  padding: 18px;
  border: 1px solid #e5e5ea;
  transition: all 0.2s;
}

.detail-section:hover {
  background: #f8f9fa;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
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

.section-title svg {
  width: 16px;
  height: 16px;
  color: #007aff;
  flex-shrink: 0;
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

.info-content {
  display: flex;
  flex-direction: column;
  flex: 1;
  min-width: 0;
  gap: 4px;
}

.info-label {
  font-size: 10px;
  color: #999;
  text-transform: uppercase;
  letter-spacing: 1px;
  font-weight: 600;
}

.info-value {
  font-size: 16px;
  font-weight: 700;
  color: #333;
  line-height: 1.3;
}

.phone-link {
  color: #667eea;
  text-decoration: none;
}

.phone-link:active {
  opacity: 0.7;
}

.info-item svg {
  width: 16px;
  height: 16px;
  color: #007aff;
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
  background: #f8f9fa;
  border-radius: 12px;
  border: 1px solid #e5e5ea;
  transition: all 0.2s;
}

.servicio-item:hover {
  background: #f0f0f0;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
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
  letter-spacing: -0.2px;
}

.servicio-duracion {
  font-size: 12px;
  color: #86868b;
  font-weight: 500;
}

.servicio-precio {
  font-size: 18px;
  font-weight: 700;
  color: #34c759;
  letter-spacing: -0.3px;
}

.total-section {
  background: linear-gradient(135deg, #007aff 0%, #5856d6 100%);
  color: white;
  box-shadow: 0 4px 16px rgba(0, 122, 255, 0.3);
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
  opacity: 0.95;
}

.total-value {
  font-size: 32px;
  font-weight: 700;
  letter-spacing: -1px;
}

.notas-content {
  padding: 16px;
  background: #f8f9fa;
  border-radius: 12px;
  font-size: 14px;
  color: #666;
  line-height: 1.7;
  border: 1px solid #e5e5ea;
  font-style: italic;
}

.detail-actions {
  display: flex;
  flex-direction: column;
  gap: 12px;
  margin-top: 12px;
}

.action-buttons-group {
  display: flex;
  gap: 10px;
  width: 100%;
}

.btn-action-primary,
.btn-action-secondary,
.btn-action-whatsapp,
.btn-action-reagendar,
.btn-action-cancel {
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
  transition: all 0.2s;
  letter-spacing: 0.2px;
  text-decoration: none;
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

.btn-action-whatsapp {
  background: #25d366;
  color: white;
}

.btn-action-whatsapp:active {
  transform: scale(0.98);
  background: #20ba5a;
}

.btn-action-reagendar {
  flex: 1;
  background: #007aff;
  color: white;
}

.btn-action-reagendar:active {
  transform: scale(0.98);
  background: #0051d5;
}

.btn-action-cancel {
  flex: 1;
  background: #ff3b30;
  color: white;
}

.btn-action-cancel:active {
  transform: scale(0.98);
  background: #d70015;
}

.btn-action-primary svg,
.btn-action-secondary svg,
.btn-action-whatsapp svg,
.btn-action-reagendar svg,
.btn-action-cancel svg {
  flex-shrink: 0;
}

/* Transitions */
.modal-enter-active, .modal-leave-active {
  transition: opacity 0.3s ease;
}
.modal-enter-active .modal-card, .modal-leave-active .modal-card {
  transition: transform 0.3s ease;
}
.modal-enter-from, .modal-leave-to {
  opacity: 0;
}
.modal-enter-from .modal-card, .modal-leave-to .modal-card {
  transform: scale(0.9) translateY(20px);
}

/* ===== BOTÓN FLOTANTE AGREGAR CITA ===== */
.btn-fab-add-cita {
  position: fixed;
  bottom: calc(env(safe-area-inset-bottom, 0px) + 80px);
  right: 20px;
  width: 56px;
  height: 56px;
  border-radius: 50%;
  background: #007aff;
  border: none;
  color: white;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  box-shadow: 0 4px 16px rgba(0, 122, 255, 0.4);
  transition: all 0.2s;
  z-index: 100;
}

.btn-fab-add-cita:active {
  transform: scale(0.95);
  box-shadow: 0 2px 8px rgba(0, 122, 255, 0.3);
}

.btn-fab-add-cita svg {
  width: 24px;
  height: 24px;
}

/* ===== MODAL NUEVA CITA ===== */
.modal-overlay {
  position: fixed;
  inset: 0;
  background: rgba(0, 0, 0, 0.6);
  backdrop-filter: blur(4px);
  display: flex;
  align-items: flex-end;
  justify-content: center;
  z-index: 1000;
  animation: fadeIn 0.3s ease;
}

@keyframes fadeIn {
  from { opacity: 0; }
  to { opacity: 1; }
}

.modal-content {
  background: white;
  width: 100%;
  max-height: 90vh;
  border-radius: 28px 28px 0 0;
  overflow: hidden;
  animation: slideUp 0.4s cubic-bezier(0.4, 0, 0.2, 1);
  box-shadow: 0 -4px 24px rgba(0, 0, 0, 0.2);
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
  background: linear-gradient(135deg, #f8f9fa 0%, #ffffff 100%);
}

.modal-header h3 {
  margin: 0;
  font-size: 20px;
  font-weight: 700;
  color: #1a1a2e;
  letter-spacing: -0.3px;
}

.modal-close {
  width: 40px;
  height: 40px;
  border: none;
  border-radius: 50%;
  background: #f0f0f0;
  color: #666;
  font-size: 16px;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all 0.2s;
}

.modal-close:hover {
  background: #e0e0e0;
  transform: rotate(90deg);
}

.modal-close:active {
  transform: rotate(90deg) scale(0.95);
}

.modal-body {
  padding: 24px;
  overflow-y: auto;
  max-height: calc(90vh - 80px);
  background: #fafafa;
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

/* Form Styles */
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
  font-weight: 700;
  color: #667eea;
  text-transform: uppercase;
  letter-spacing: 0.5px;
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
  border: 2px solid #e9ecef;
  border-radius: 12px;
  font-size: 15px;
  background: white;
  color: #333;
  transition: all 0.2s;
  font-family: inherit;
}

.form-select:focus,
.form-input:focus,
.form-textarea:focus {
  outline: none;
  border-color: #667eea;
  box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
}

.form-textarea {
  resize: vertical;
  min-height: 80px;
}

.form-hint {
  display: flex;
  align-items: center;
  gap: 8px;
  margin-top: 8px;
  padding: 10px 12px;
  background: rgba(102, 126, 234, 0.1);
  border-radius: 8px;
  font-size: 12px;
  color: #667eea;
  font-weight: 600;
}

.form-hint i {
  font-size: 14px;
  flex-shrink: 0;
}

.form-error {
  font-size: 12px;
  color: #fa709a;
  font-weight: 600;
  margin-top: 4px;
  display: flex;
  align-items: center;
  gap: 6px;
}

/* Search Client */
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
  background: linear-gradient(135deg, rgba(102, 126, 234, 0.05) 0%, rgba(118, 75, 162, 0.05) 100%);
}

.cliente-option.selected {
  background: linear-gradient(135deg, rgba(102, 126, 234, 0.1) 0%, rgba(118, 75, 162, 0.1) 100%);
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
  color: #667eea;
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
  border-radius: 10px;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  font-size: 14px;
  font-weight: 700;
  cursor: pointer;
  display: flex;
  align-items: center;
  gap: 8px;
  transition: all 0.2s;
  box-shadow: 0 2px 8px rgba(102, 126, 234, 0.3);
}

.btn-create-client:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
}

.btn-create-client:active {
  transform: translateY(0);
}

.cliente-selected {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 12px 16px;
  background: linear-gradient(135deg, rgba(17, 153, 142, 0.1) 0%, rgba(56, 239, 125, 0.1) 100%);
  border-radius: 12px;
  border: 2px solid rgba(17, 153, 142, 0.2);
  margin-top: 10px;
}

.selected-info {
  display: flex;
  align-items: center;
  gap: 10px;
  flex: 1;
}

.selected-info i {
  color: #11998e;
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
  background: rgba(250, 112, 154, 0.1);
  border-radius: 8px;
  color: #fa709a;
  font-size: 14px;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all 0.2s;
  flex-shrink: 0;
}

.btn-remove-selection:hover {
  background: rgba(250, 112, 154, 0.2);
  transform: scale(1.1);
}

.btn-remove-selection:active {
  transform: scale(0.95);
}

.btn-add-client {
  width: 44px;
  height: 44px;
  border: none;
  border-radius: 12px;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  font-size: 16px;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
  transition: all 0.2s;
  box-shadow: 0 2px 8px rgba(102, 126, 234, 0.3);
}

.btn-add-client:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
}

.btn-add-client:active {
  transform: translateY(0) scale(0.95);
}

.btn-add-client.active {
  background: linear-gradient(135deg, #fa709a 0%, #fee140 100%);
  transform: rotate(45deg);
}

/* Nuevo cliente inline */
.nuevo-cliente-fields {
  margin-top: 16px;
  padding: 20px;
  background: linear-gradient(135deg, rgba(102, 126, 234, 0.05) 0%, rgba(118, 75, 162, 0.05) 100%);
  border-radius: 16px;
  border: 2px solid rgba(102, 126, 234, 0.2);
}

.nuevo-cliente-header {
  margin-bottom: 16px;
  padding-bottom: 12px;
  border-bottom: 2px solid rgba(102, 126, 234, 0.1);
}

.nuevo-cliente-header h4 {
  margin: 0;
  font-size: 16px;
  font-weight: 700;
  color: #667eea;
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
  font-weight: 700;
  color: #667eea;
  margin-bottom: 6px;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.nuevo-cliente-actions {
  display: flex;
  gap: 10px;
  margin-top: 16px;
  padding-top: 16px;
  border-top: 1px solid rgba(102, 126, 234, 0.1);
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
  background: #f0f0f0;
  color: #666;
}

.btn-cancel-small:hover {
  background: #e0e0e0;
}

.btn-submit-small {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  box-shadow: 0 2px 8px rgba(102, 126, 234, 0.3);
}

.btn-submit-small:hover:not(:disabled) {
  box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
  transform: translateY(-2px);
}

.btn-submit-small:disabled {
  opacity: 0.6;
  cursor: not-allowed;
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

/* Servicios */
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
  border-color: #667eea;
  background: #f8f9ff;
  transform: translateX(4px);
}

.servicio-option.selected {
  border-color: #667eea;
  background: linear-gradient(135deg, rgba(102, 126, 234, 0.1) 0%, rgba(118, 75, 162, 0.1) 100%);
  box-shadow: 0 2px 8px rgba(102, 126, 234, 0.15);
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
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  border-color: #667eea;
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

.loading-message i,
.empty-message i {
  font-size: 48px;
}

.loading-message i {
  color: #667eea;
}

.empty-message i {
  color: #fa709a;
}

.loading-message p,
.empty-message p {
  margin: 0;
  color: #999;
  font-size: 14px;
  font-weight: 600;
}

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

/* Total preview */
.total-preview {
  background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
  padding: 18px;
  border-radius: 12px;
  border: 2px solid #e0e0e0;
}

.total-preview .total-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 8px;
}

.total-preview .total-label {
  font-size: 14px;
  font-weight: 700;
  color: #666;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.total-preview .total-value {
  font-size: 24px;
  font-weight: 800;
  background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
}

.total-duration {
  display: flex;
  align-items: center;
  gap: 6px;
  font-size: 12px;
  color: #999;
  font-weight: 600;
}

.total-duration i {
  font-size: 11px;
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
  background: #f0f0f0;
  color: #666;
}

.btn-cancel:hover {
  background: #e0e0e0;
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

.btn-submit {
  background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
  color: white;
  box-shadow: 0 4px 12px rgba(17, 153, 142, 0.3);
}

.btn-submit:hover:not(:disabled) {
  box-shadow: 0 6px 20px rgba(17, 153, 142, 0.4);
  transform: translateY(-2px);
}

.btn-submit:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.btn-cancel:active,
.btn-submit:active:not(:disabled) {
  transform: translateY(0) scale(0.98);
}

/* Reagendar Modal */
.reagendar-info {
  background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
  border-radius: 16px;
  padding: 20px;
  margin-bottom: 24px;
  border: 1px solid #e0e0e0;
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
  background: linear-gradient(135deg, #ec407a 0%, #c2185b 100%);
  color: white;
  border-radius: 10px;
  font-size: 14px;
}

.info-cliente-reagendar {
  display: flex;
  flex-wrap: wrap;
  gap: 16px;
  padding-top: 16px;
  border-top: 1px solid #e0e0e0;
}

.info-cliente-reagendar span {
  display: flex;
  align-items: center;
  gap: 8px;
  font-size: 14px;
  color: #666;
}

.info-cliente-reagendar i {
  color: #ec407a;
  font-size: 14px;
}

.reagendar-form {
  display: flex;
  flex-direction: column;
  gap: 20px;
}
</style>
