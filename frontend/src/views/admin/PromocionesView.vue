<template>
  <div class="promociones-view">
    <!-- Header -->
    <div class="promociones-header">
      <div class="header-left">
        <div class="header-icon">
          <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path>
            <line x1="8" y1="10" x2="16" y2="10"></line>
            <line x1="8" y1="14" x2="14" y2="14"></line>
          </svg>
        </div>
        <div class="header-text">
          <h1>Promociones</h1>
          <p class="header-subtitle">{{ promocionesActivas }} activas</p>
        </div>
      </div>
      <button class="btn-new-promo" @click="nuevaPromocion" title="Nueva Promoción">
        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <line x1="12" y1="5" x2="12" y2="19"></line>
          <line x1="5" y1="12" x2="19" y2="12"></line>
        </svg>
        <span class="btn-text">Nuevo</span>
      </button>
    </div>

    <!-- Stats -->
    <div class="promo-stats">
      <div class="promo-stat">
        <div class="stat-icon green">
          <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
            <polyline points="22 4 12 14.01 9 11.01"></polyline>
          </svg>
        </div>
        <div class="stat-info">
          <span class="stat-value">{{ promocionesActivas }}</span>
          <span class="stat-label">Activas</span>
        </div>
      </div>
      <div class="promo-stat">
        <div class="stat-icon orange">
          <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path>
            <polyline points="22,6 12,13 2,6"></polyline>
          </svg>
        </div>
        <div class="stat-info">
          <span class="stat-value">{{ totalUsos }}</span>
          <span class="stat-label">Usos Total</span>
        </div>
      </div>
    </div>

    <!-- Promociones List -->
    <div class="promociones-container">
      <div v-if="loading" class="loading-container">
        <div class="loader"></div>
        <p>Cargando promociones...</p>
      </div>
      
      <div v-else-if="promociones.length === 0" class="empty-state">
        <div class="empty-icon">
          <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
            <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path>
            <line x1="8" y1="10" x2="16" y2="10"></line>
            <line x1="8" y1="14" x2="14" y2="14"></line>
          </svg>
        </div>
        <p>No hay promociones creadas</p>
        <button class="btn-create" @click="nuevaPromocion">
          <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <line x1="12" y1="5" x2="12" y2="19"></line>
            <line x1="5" y1="12" x2="19" y2="12"></line>
          </svg>
          Crear primera promoción
        </button>
      </div>
      
      <div v-else class="promociones-list">
        <div 
          v-for="promo in promociones" 
          :key="promo.id" 
          class="promo-card"
          :class="{ inactiva: !promo.activa }"
        >
          <div class="promo-main">
            <div class="promo-badge-container">
              <div class="promo-badge" :class="promo.descuento_porcentaje ? 'porcentaje' : 'fijo'">
                <span class="badge-value">
                  {{ promo.descuento_porcentaje ? `${promo.descuento_porcentaje}%` : `$${promo.descuento_fijo}` }}
                </span>
                <span class="badge-label">{{ promo.descuento_porcentaje ? 'DESC' : 'OFF' }}</span>
              </div>
            </div>
            
            <div class="promo-content">
              <div class="promo-header-info">
                <h3 class="promo-nombre">{{ promo.nombre }}</h3>
                <span :class="['status-badge', promo.activa ? 'activa' : 'inactiva']">
                  {{ promo.activa ? 'Activa' : 'Inactiva' }}
                </span>
              </div>
              <p class="promo-descripcion">{{ promo.descripcion || 'Sin descripción' }}</p>
              
              <div class="promo-dates">
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                  <line x1="16" y1="2" x2="16" y2="6"></line>
                  <line x1="8" y1="2" x2="8" y2="6"></line>
                  <line x1="3" y1="10" x2="21" y2="10"></line>
                </svg>
                <span>{{ formatDate(promo.fecha_inicio) }} - {{ formatDate(promo.fecha_fin) }}</span>
              </div>
              
              <div class="promo-usage">
                <div class="usage-bar">
                  <div 
                    class="usage-fill" 
                    :style="{ width: getUsagePercent(promo) + '%' }"
                  ></div>
                </div>
                <div class="usage-text">
                  <span>{{ promo.usos_actuales || 0 }} usos</span>
                  <span v-if="promo.usos_maximos">/ {{ promo.usos_maximos }} máx</span>
                  <span v-else class="unlimited">ilimitado</span>
                </div>
              </div>
            </div>
          </div>
          
          <div class="promo-actions">
            <button class="action-btn" @click="editarPromocion(promo)" title="Editar">
              <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
              </svg>
            </button>
            <button 
              class="action-btn"
              :class="promo.activa ? 'danger' : 'success'"
              @click="togglePromocion(promo)"
              :title="promo.activa ? 'Pausar' : 'Activar'"
            >
              <svg v-if="promo.activa" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <rect x="6" y="4" width="4" height="16"></rect>
                <rect x="14" y="4" width="4" height="16"></rect>
              </svg>
              <svg v-else xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <polygon points="5 3 19 12 5 21 5 3"></polygon>
              </svg>
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Modal -->
    <Teleport to="body">
      <div v-if="showModal" class="modal-overlay" @click.self="closeModal">
        <div class="modal-content">
          <div class="modal-header">
            <h3>{{ selectedPromo ? 'Editar Promoción' : 'Nueva Promoción' }}</h3>
            <button class="modal-close" @click="closeModal">
              <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <line x1="18" y1="6" x2="6" y2="18"></line>
                <line x1="6" y1="6" x2="18" y2="18"></line>
              </svg>
            </button>
          </div>
          <div class="modal-body">
            <div class="form-container">
              <div class="form-section">
                <label class="form-label">
                  <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z"></path>
                    <line x1="7" y1="7" x2="7.01" y2="7"></line>
                  </svg>
                  Nombre de la promoción <span class="required">*</span>
                </label>
                <input 
                  v-model="formData.nombre" 
                  type="text" 
                  placeholder="Ej: 10% en primera visita" 
                  class="form-input"
                />
              </div>
              
              <div class="form-section">
                <label class="form-label">
                  <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                    <polyline points="14 2 14 8 20 8"></polyline>
                    <line x1="16" y1="13" x2="8" y2="13"></line>
                    <line x1="16" y1="17" x2="8" y2="17"></line>
                    <polyline points="10 9 9 9 8 9"></polyline>
                  </svg>
                  Descripción
                </label>
                <textarea 
                  v-model="formData.descripcion" 
                  rows="2" 
                  placeholder="Descripción breve..."
                  class="form-textarea"
                ></textarea>
              </div>
              
              <div class="form-section">
                <label class="form-label">
                  <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
                    <circle cx="8.5" cy="8.5" r="1.5"></circle>
                    <polyline points="21 15 16 10 5 21"></polyline>
                  </svg>
                  Imagen del banner
                </label>
                <div class="image-upload-container">
                  <input 
                    ref="imagenInput"
                    type="file" 
                    accept="image/*" 
                    @change="handleImageSelect"
                    style="display: none"
                  />
                  <div v-if="formData.imagenPreview" class="image-preview">
                    <img :src="formData.imagenPreview" alt="Preview" />
                    <button type="button" class="remove-image" @click="removeImage">
                      <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="18" y1="6" x2="6" y2="18"></line>
                        <line x1="6" y1="6" x2="18" y2="18"></line>
                      </svg>
                    </button>
                  </div>
                  <button 
                    v-else 
                    type="button" 
                    class="btn-upload-image"
                    @click="imagenInput?.click()"
                  >
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                      <rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
                      <circle cx="8.5" cy="8.5" r="1.5"></circle>
                      <polyline points="21 15 16 10 5 21"></polyline>
                    </svg>
                    Seleccionar imagen
                  </button>
                </div>
                <p class="image-hint">Recomendado: 1200x400px, máximo 5MB</p>
              </div>
              
              <div class="form-section">
                <label class="form-label">
                  <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="12" y1="1" x2="12" y2="23"></line>
                    <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path>
                  </svg>
                  Tipo de descuento
                </label>
                <div class="tipo-selector">
                  <button 
                    :class="['tipo-btn', { active: formData.tipo === 'porcentaje' }]"
                    @click="formData.tipo = 'porcentaje'"
                    type="button"
                  >
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                      <line x1="12" y1="1" x2="12" y2="23"></line>
                      <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path>
                    </svg>
                    Porcentaje
                  </button>
                  <button 
                    :class="['tipo-btn', { active: formData.tipo === 'fijo' }]"
                    @click="formData.tipo = 'fijo'"
                    type="button"
                  >
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                      <line x1="12" y1="1" x2="12" y2="23"></line>
                      <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path>
                    </svg>
                    Monto Fijo
                  </button>
                </div>
              </div>
              
              <div class="form-section">
                <label class="form-label">
                  <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="12" y1="1" x2="12" y2="23"></line>
                    <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path>
                  </svg>
                  Valor del descuento <span class="required">*</span>
                </label>
                <div class="input-prefix">
                  <span class="prefix-symbol">{{ formData.tipo === 'porcentaje' ? '%' : '$' }}</span>
                  <input 
                    v-model.number="formData.valor" 
                    type="number" 
                    placeholder="0" 
                    class="form-input"
                    min="0"
                  />
                </div>
              </div>
              
              <div class="form-row">
                <div class="form-section">
                  <label class="form-label">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                      <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                      <line x1="16" y1="2" x2="16" y2="6"></line>
                      <line x1="8" y1="2" x2="8" y2="6"></line>
                      <line x1="3" y1="10" x2="21" y2="10"></line>
                    </svg>
                    Fecha inicio <span class="required">*</span>
                  </label>
                  <input 
                    v-model="formData.fecha_inicio" 
                    type="date" 
                    class="form-input"
                  />
                </div>
                <div class="form-section">
                  <label class="form-label">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                      <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                      <line x1="16" y1="2" x2="16" y2="6"></line>
                      <line x1="8" y1="2" x2="8" y2="6"></line>
                      <line x1="3" y1="10" x2="21" y2="10"></line>
                    </svg>
                    Fecha fin <span class="required">*</span>
                  </label>
                  <input 
                    v-model="formData.fecha_fin" 
                    type="date" 
                    class="form-input"
                  />
                </div>
              </div>
              
              <div class="form-section">
                <label class="form-label">
                  <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"></path>
                  </svg>
                  Servicios aplicables
                </label>
                <div class="servicios-selector">
                  <div class="servicios-options">
                    <label class="checkbox-option">
                      <input 
                        type="checkbox" 
                        :checked="formData.aplicarATodos"
                        @change="toggleAplicarATodos"
                      />
                      <span>Aplicar a todos los servicios</span>
                    </label>
                  </div>
                  <div v-if="!formData.aplicarATodos" class="servicios-list-select">
                    <div class="servicios-search">
                      <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="11" cy="11" r="8"></circle>
                        <path d="M21 21l-4.35-4.35"></path>
                      </svg>
                      <input 
                        v-model="busquedaServicio"
                        type="text"
                        placeholder="Buscar servicios..."
                        class="search-input"
                      />
                    </div>
                    <div v-if="servicios.length === 0" class="no-servicios">
                      Cargando servicios...
                    </div>
                    <div v-else class="servicios-checkboxes">
                      <label 
                        v-for="servicio in serviciosFiltrados" 
                        :key="servicio.id"
                        class="checkbox-option servicio-option"
                      >
                        <input 
                          type="checkbox" 
                          :value="servicio.id"
                          v-model="formData.serviciosSeleccionados"
                          @change="actualizarInfoServicios"
                        />
                        <div class="servicio-checkbox-info">
                          <span class="servicio-nombre">{{ servicio.nombre }}</span>
                          <span class="servicio-meta">{{ servicio.duracion || 0 }} min • ${{ Number(servicio.precio || 0).toFixed(2) }}</span>
                        </div>
                      </label>
                      <div v-if="serviciosFiltrados.length === 0 && busquedaServicio" class="no-servicios">
                        No se encontraron servicios
                      </div>
                    </div>
                  </div>
                </div>
                <div v-if="!formData.aplicarATodos && formData.serviciosSeleccionados.length > 0" class="servicios-resumen">
                  <div class="resumen-item">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                      <path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"></path>
                    </svg>
                    <span>{{ formData.serviciosSeleccionados.length }} servicio(s) seleccionado(s)</span>
                  </div>
                  <div class="resumen-item">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                      <circle cx="12" cy="12" r="10"></circle>
                      <polyline points="12 6 12 12 16 14"></polyline>
                    </svg>
                    <span>Tiempo total: {{ tiempoTotalServicios }} minutos</span>
                  </div>
                  <div class="resumen-item" v-if="empleadosDisponibles.length > 0">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                      <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                      <circle cx="12" cy="7" r="4"></circle>
                    </svg>
                    <span>{{ empleadosDisponibles.length }} empleado(s) pueden atender esta promoción</span>
                  </div>
                  <div v-if="empleadosDisponibles.length > 0" class="empleados-lista">
                    <span 
                      v-for="emp in empleadosDisponibles" 
                      :key="emp.id"
                      class="empleado-badge"
                    >
                      {{ emp.nombre }}
                    </span>
                  </div>
                </div>
              </div>
              
              <div class="form-section">
                <label class="form-label">
                  <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="22 12 18 12 15 21 9 3 6 12 2 12"></polyline>
                  </svg>
                  Usos máximos (vacío = ilimitado)
                </label>
                <input 
                  v-model.number="formData.usos_maximos" 
                  type="number" 
                  placeholder="Sin límite" 
                  class="form-input"
                  min="0"
                />
              </div>
              
              <div class="form-actions">
                <button type="button" class="btn-cancel" @click="closeModal">
                  Cancelar
                </button>
                <button type="button" class="btn-submit" @click="guardarPromocion" :disabled="!formData.nombre.trim() || !formData.valor || !formData.fecha_inicio || !formData.fecha_fin">
                  <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path>
                    <polyline points="17 21 17 13 7 13 7 21"></polyline>
                    <polyline points="7 3 7 8 15 8"></polyline>
                  </svg>
                  {{ selectedPromo ? 'Actualizar' : 'Crear' }} Promoción
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>
    </Teleport>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, reactive, onMounted } from 'vue';
import { getPromociones, createPromocion, updatePromocion } from '@/services/adminService';
import catalogoService from '@/services/catalogoService';
import Swal from 'sweetalert2';

const promociones = ref<any[]>([]);
const loading = ref(true);
const showModal = ref(false);
const selectedPromo = ref<any>(null);

const imagenInput = ref<HTMLInputElement | null>(null);
const servicios = ref<any[]>([]);
const busquedaServicio = ref('');
const empleadosDisponibles = ref<any[]>([]);
const cargandoEmpleados = ref(false);

const formData = reactive({
  nombre: '',
  descripcion: '',
  imagen: null as File | null,
  imagenPreview: null as string | null,
  tipo: 'porcentaje',
  valor: 10,
  fecha_inicio: '',
  fecha_fin: '',
  usos_maximos: null as number | null,
  aplicarATodos: true,
  serviciosSeleccionados: [] as number[],
});

const promocionesActivas = computed(() => promociones.value.filter(p => p.activa).length);
const totalUsos = computed(() => promociones.value.reduce((sum, p) => sum + (p.usos_actuales || 0), 0));

const serviciosFiltrados = computed(() => {
  if (!servicios.value || servicios.value.length === 0) return [];
  if (!busquedaServicio.value) return servicios.value;
  const search = busquedaServicio.value.toLowerCase();
  return servicios.value.filter(s => 
    s.nombre && s.nombre.toLowerCase().includes(search)
  );
});

const tiempoTotalServicios = computed(() => {
  if (formData.serviciosSeleccionados.length === 0 || !servicios.value || servicios.value.length === 0) return 0;
  const serviciosSeleccionados = servicios.value.filter(s => formData.serviciosSeleccionados.includes(s.id));
  if (serviciosSeleccionados.length === 0) return 0;
  return serviciosSeleccionados.reduce((total, s) => {
    const duracion = Number(s.duracion) || 0;
    return total + duracion;
  }, 0);
});

async function cargarPromociones() {
  loading.value = true;
  try {
    const response = await getPromociones();
    if (response.success) {
      promociones.value = response.data || [];
    }
  } catch (error) {
    console.error('Error cargando promociones:', error);
  } finally {
    loading.value = false;
  }
}

async function cargarServicios() {
  try {
    servicios.value = await catalogoService.obtenerServicios();
  } catch (error) {
    console.error('Error cargando servicios:', error);
  }
}

async function actualizarInfoServicios() {
  if (formData.serviciosSeleccionados.length === 0) {
    empleadosDisponibles.value = [];
    return;
  }

  cargandoEmpleados.value = true;
  try {
    const empleadosPorServicio: Record<number, any[]> = {};
    
    for (const servicioId of formData.serviciosSeleccionados) {
      const emps = await catalogoService.obtenerEmpleados(servicioId);
      empleadosPorServicio[servicioId] = emps.filter(e => e.activo !== false);
    }

    const todosServiciosIds = formData.serviciosSeleccionados;
    const empleadosComunes: any[] = [];

    if (todosServiciosIds.length === 1 && todosServiciosIds[0] !== undefined) {
      empleadosDisponibles.value = empleadosPorServicio[todosServiciosIds[0]] || [];
      return;
    }

    const primerServicioId = todosServiciosIds[0];
    if (primerServicioId === undefined) {
      empleadosDisponibles.value = [];
      return;
    }
    const primerServicioEmpleados = empleadosPorServicio[primerServicioId] || [];
    
    for (const empleado of primerServicioEmpleados) {
      const serviciosEmpleado = empleado.servicios.map((s: any) => s.id);
      const puedeAtenderTodos = todosServiciosIds.every(servicioId => 
        serviciosEmpleado.includes(servicioId)
      );
      
      if (puedeAtenderTodos) {
        empleadosComunes.push(empleado);
      }
    }

    empleadosDisponibles.value = empleadosComunes;
  } catch (error) {
    console.error('Error cargando empleados:', error);
    empleadosDisponibles.value = [];
  } finally {
    cargandoEmpleados.value = false;
  }
}

function toggleAplicarATodos() {
  formData.aplicarATodos = !formData.aplicarATodos;
  if (formData.aplicarATodos) {
    formData.serviciosSeleccionados = [];
    empleadosDisponibles.value = [];
  }
}

function formatDate(date: string): string {
  if (!date) return '--';
  return new Date(date).toLocaleDateString('es-MX', { day: 'numeric', month: 'short' });
}

function getUsagePercent(promo: any): number {
  if (!promo.usos_maximos) return 50;
  return Math.min(((promo.usos_actuales || 0) / promo.usos_maximos) * 100, 100);
}

function handleImageSelect(event: Event) {
  const input = event.target as HTMLInputElement;
  if (input.files && input.files[0]) {
    const file = input.files[0];
    if (file.size > 5 * 1024 * 1024) {
      Swal.fire({
        icon: 'warning',
        title: 'Archivo muy grande',
        text: 'La imagen debe ser menor a 5MB',
        confirmButtonColor: '#ff3b30'
      });
      return;
    }
    formData.imagen = file;
    
    const reader = new FileReader();
    reader.onload = (e) => {
      formData.imagenPreview = e.target?.result as string;
    };
    reader.readAsDataURL(file);
  }
}

function removeImage() {
  formData.imagen = null;
  formData.imagenPreview = null;
  if (imagenInput.value) {
    imagenInput.value.value = '';
  }
}

function nuevaPromocion() { 
  selectedPromo.value = null;
  formData.nombre = '';
  formData.descripcion = '';
  formData.imagen = null;
  formData.imagenPreview = null;
  formData.tipo = 'porcentaje';
  formData.valor = 10;
  formData.fecha_inicio = '';
  formData.fecha_fin = '';
  formData.usos_maximos = null;
  formData.aplicarATodos = true;
  formData.serviciosSeleccionados = [];
  empleadosDisponibles.value = [];
  busquedaServicio.value = '';
  if (imagenInput.value) {
    imagenInput.value.value = '';
  }
  showModal.value = true;
}

function editarPromocion(p: any) { 
  selectedPromo.value = p;
  formData.nombre = p.nombre;
  formData.descripcion = p.descripcion || '';
  formData.imagen = null;
  formData.imagenPreview = p.imagen || null;
  formData.tipo = p.descuento_porcentaje ? 'porcentaje' : 'fijo';
  formData.valor = p.descuento_porcentaje || p.descuento_fijo || 0;
  formData.fecha_inicio = p.fecha_inicio;
  formData.fecha_fin = p.fecha_fin;
  formData.usos_maximos = p.usos_maximos;
  formData.aplicarATodos = !p.servicios_aplicables || p.servicios_aplicables.length === 0;
  formData.serviciosSeleccionados = p.servicios_aplicables || [];
  busquedaServicio.value = '';
  if (imagenInput.value) {
    imagenInput.value.value = '';
  }
  if (!formData.aplicarATodos && formData.serviciosSeleccionados.length > 0) {
    actualizarInfoServicios();
  } else {
    empleadosDisponibles.value = [];
  }
  showModal.value = true;
}

async function togglePromocion(p: any) { 
  try {
    await updatePromocion(p.id, { activa: !p.activa });
    p.activa = !p.activa;
    Swal.fire({
      icon: 'success',
      title: '¡Éxito!',
      text: `Promoción ${p.activa ? 'activada' : 'pausada'} exitosamente`,
      confirmButtonColor: '#34c759',
      timer: 1500,
      showConfirmButton: false
    });
  } catch (error: any) {
    console.error('Error actualizando promoción:', error);
    Swal.fire({
      icon: 'error',
      title: 'Error',
      text: error.response?.data?.message || 'Error al actualizar promoción',
      confirmButtonColor: '#ff3b30'
    });
  }
}

async function guardarPromocion() {
  if (!formData.nombre.trim()) {
    Swal.fire({
      icon: 'warning',
      title: 'Campo requerido',
      text: 'El nombre de la promoción es requerido',
      confirmButtonColor: '#ff3b30'
    });
    return;
  }

  if (!formData.valor || formData.valor <= 0) {
    Swal.fire({
      icon: 'warning',
      title: 'Valor inválido',
      text: 'El valor del descuento debe ser mayor a 0',
      confirmButtonColor: '#ff3b30'
    });
    return;
  }

  if (!formData.fecha_inicio || !formData.fecha_fin) {
    Swal.fire({
      icon: 'warning',
      title: 'Fechas requeridas',
      text: 'Debes seleccionar fecha de inicio y fin',
      confirmButtonColor: '#ff3b30'
    });
    return;
  }

  if (new Date(formData.fecha_inicio) > new Date(formData.fecha_fin)) {
    Swal.fire({
      icon: 'warning',
      title: 'Fechas inválidas',
      text: 'La fecha de inicio debe ser anterior a la fecha de fin',
      confirmButtonColor: '#ff3b30'
    });
    return;
  }

  try {
    const data: any = {
      nombre: formData.nombre.trim(),
      descripcion: formData.descripcion?.trim() || null,
      fecha_inicio: formData.fecha_inicio,
      fecha_fin: formData.fecha_fin,
      usos_maximos: formData.usos_maximos || null,
    };
    
    if (formData.aplicarATodos) {
      data.servicios_aplicables = null;
    } else {
      data.servicios_aplicables = formData.serviciosSeleccionados;
    }
    
    if (formData.imagen) {
      data.imagen = formData.imagen;
    }
    
    if (formData.tipo === 'porcentaje') {
      data.descuento_porcentaje = formData.valor;
      data.descuento_fijo = null;
    } else {
      data.descuento_fijo = formData.valor;
      data.descuento_porcentaje = null;
    }
    
    if (selectedPromo.value) {
      await updatePromocion(selectedPromo.value.id, data);
      Swal.fire({
        icon: 'success',
        title: '¡Éxito!',
        text: 'Promoción actualizada exitosamente',
        confirmButtonColor: '#34c759',
        timer: 1500,
        showConfirmButton: false
      });
    } else {
      await createPromocion(data);
      Swal.fire({
        icon: 'success',
        title: '¡Éxito!',
        text: 'Promoción creada exitosamente',
        confirmButtonColor: '#34c759',
        timer: 1500,
        showConfirmButton: false
      });
    }
    closeModal();
    await cargarPromociones();
  } catch (error: any) {
    console.error('Error guardando promoción:', error);
    Swal.fire({
      icon: 'error',
      title: 'Error',
      text: error.response?.data?.message || 'Error al guardar promoción',
      confirmButtonColor: '#ff3b30'
    });
  }
}

function closeModal() {
  showModal.value = false;
  selectedPromo.value = null;
}

onMounted(() => {
  cargarPromociones();
  cargarServicios();
});
</script>

<style scoped>
/* ===== Apple-inspired Promociones View Design ===== */

.promociones-view {
  min-height: 100vh;
  background: #f5f5f7;
  padding: 24px;
  padding-bottom: 120px;
  font-family: -apple-system, BlinkMacSystemFont, 'SF Pro Display', 'SF Pro Text', 'Helvetica Neue', sans-serif;
  -webkit-font-smoothing: antialiased;
  -moz-osx-font-smoothing: grayscale;
}

/* Header */
.promociones-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 20px;
  padding: 20px;
  background: linear-gradient(135deg, #1d1d1f 0%, #3a3a3c 100%);
  border-radius: 20px;
  color: white;
}

.header-left {
  display: flex;
  align-items: center;
  gap: 14px;
  flex: 1;
  min-width: 0;
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
  flex-shrink: 0;
}

.header-text {
  flex: 1;
  min-width: 0;
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

.btn-new-promo {
  display: flex;
  align-items: center;
  gap: 6px;
  padding: 10px 14px;
  background: rgba(255, 255, 255, 0.15);
  border: 1px solid rgba(255, 255, 255, 0.2);
  border-radius: 12px;
  color: white;
  font-size: 14px;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.2s;
  backdrop-filter: blur(10px);
}

.btn-new-promo .btn-text {
  display: none;
}

.btn-new-promo:active {
  background: rgba(255, 255, 255, 0.25);
  transform: scale(0.98);
}

@media (min-width: 480px) {
  .promociones-header {
    padding: 20px;
    margin-bottom: 24px;
    border-radius: 20px;
  }
  
  .header-icon {
    width: 46px;
    height: 46px;
  }
  
  .header-text h1 {
    font-size: 22px;
  }
  
  .header-subtitle {
    font-size: 13px;
    margin: 4px 0 0;
  }
  
  .btn-new-promo {
    padding: 12px 20px;
    font-size: 15px;
  }
  
  .btn-new-promo .btn-text {
    display: inline;
  }
}

/* Stats */
.promo-stats {
  display: flex;
  gap: 10px;
  margin-bottom: 20px;
}

.promo-stat {
  flex: 1;
  display: flex;
  align-items: center;
  gap: 12px;
  background: #ffffff;
  padding: 14px;
  border-radius: 14px;
  box-shadow: 0 2px 12px rgba(0, 0, 0, 0.08);
  border: 1px solid #e5e5ea;
}

.stat-icon {
  width: 40px;
  height: 40px;
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
  box-shadow: 0 2px 6px rgba(0, 0, 0, 0.1);
}

.stat-icon.green {
  background: linear-gradient(135deg, #34c759 0%, #30d158 100%);
  color: white;
}

.stat-icon.orange {
  background: linear-gradient(135deg, #ff9500 0%, #ffad33 100%);
  color: white;
}

.stat-info {
  display: flex;
  flex-direction: column;
  flex: 1;
  min-width: 0;
}

.stat-value {
  font-size: 20px;
  font-weight: 700;
  color: #1d1d1f;
  letter-spacing: -0.3px;
  line-height: 1.2;
}

.stat-label {
  font-size: 11px;
  color: #86868b;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  margin-top: 2px;
}

/* Loading & Empty */
.loading-container,
.empty-state {
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

.loading-container p,
.empty-state p {
  color: #86868b;
  font-size: 15px;
  margin: 0 0 20px;
}

.empty-icon {
  color: #d1d1d6;
  margin-bottom: 16px;
}

.btn-create {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 12px 20px;
  background: linear-gradient(135deg, #007aff 0%, #5856d6 100%);
  color: white;
  border: none;
  border-radius: 12px;
  font-size: 14px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s;
  box-shadow: 0 4px 12px rgba(0, 122, 255, 0.3);
}

.btn-create:active {
  transform: scale(0.98);
  box-shadow: 0 2px 8px rgba(0, 122, 255, 0.4);
}

/* Promociones List */
.promociones-list {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.promo-card {
  background: #ffffff;
  border-radius: 16px;
  padding: 16px;
  box-shadow: 0 2px 12px rgba(0, 0, 0, 0.08);
  border: 1px solid #e5e5ea;
  transition: all 0.2s;
}

.promo-card.inactiva {
  opacity: 0.7;
}

.promo-main {
  display: flex;
  align-items: flex-start;
  gap: 14px;
  margin-bottom: 12px;
}

.promo-badge-container {
  flex-shrink: 0;
}

.promo-badge {
  display: inline-flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 12px 16px;
  border-radius: 14px;
  color: white;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
  min-width: 70px;
}

.promo-badge.porcentaje {
  background: linear-gradient(135deg, #007aff 0%, #5856d6 100%);
}

.promo-badge.fijo {
  background: linear-gradient(135deg, #34c759 0%, #30d158 100%);
}

.badge-value {
  font-size: 22px;
  font-weight: 800;
  line-height: 1;
  letter-spacing: -0.5px;
}

.badge-label {
  font-size: 10px;
  font-weight: 700;
  opacity: 0.95;
  margin-top: 4px;
  letter-spacing: 0.5px;
}

.promo-content {
  flex: 1;
  min-width: 0;
}

.promo-header-info {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 10px;
  margin-bottom: 6px;
}

.promo-nombre {
  margin: 0;
  font-size: 16px;
  font-weight: 600;
  color: #1d1d1f;
  letter-spacing: -0.2px;
  flex: 1;
  min-width: 0;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.promo-descripcion {
  margin: 0 0 10px;
  font-size: 13px;
  color: #86868b;
  line-height: 1.4;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

.promo-dates {
  display: flex;
  align-items: center;
  gap: 8px;
  font-size: 12px;
  color: #86868b;
  margin-bottom: 12px;
}

.promo-dates svg {
  color: #007aff;
  flex-shrink: 0;
}

.promo-usage {
  background: #f5f5f7;
  border-radius: 12px;
  padding: 10px 12px;
  border: 1px solid #e5e5ea;
}

.usage-bar {
  height: 6px;
  background: #e5e5ea;
  border-radius: 3px;
  overflow: hidden;
  margin-bottom: 8px;
}

.usage-fill {
  height: 100%;
  background: linear-gradient(90deg, #007aff 0%, #5856d6 100%);
  border-radius: 3px;
  transition: width 0.3s ease;
}

.usage-text {
  display: flex;
  justify-content: space-between;
  font-size: 11px;
  color: #86868b;
  font-weight: 500;
}

.usage-text .unlimited {
  color: #34c759;
  font-weight: 600;
}

.status-badge {
  padding: 4px 10px;
  border-radius: 8px;
  font-size: 11px;
  font-weight: 600;
  flex-shrink: 0;
}

.status-badge.activa {
  background: #e8f5e9;
  color: #34c759;
}

.status-badge.inactiva {
  background: #ffebee;
  color: #ff3b30;
}

.promo-actions {
  display: flex;
  gap: 6px;
  padding-top: 12px;
  border-top: 1px solid #e5e5ea;
}

.action-btn {
  flex: 1;
  min-width: 0;
  height: 36px;
  border: none;
  border-radius: 10px;
  background: #f5f5f7;
  color: #1d1d1f;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all 0.2s;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.06);
}

.action-btn:active {
  transform: scale(0.95);
  box-shadow: 0 1px 2px rgba(0, 0, 0, 0.12);
}

.action-btn.danger {
  background: #ffebee;
  color: #ff3b30;
}

.action-btn.success {
  background: #e8f5e9;
  color: #34c759;
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
  background: #ffffff;
  width: 100%;
  max-height: 90vh;
  border-radius: 24px 24px 0 0;
  overflow: hidden;
  animation: slideUp 0.3s cubic-bezier(0.16, 1, 0.3, 1);
  box-shadow: 0 -4px 24px rgba(0, 0, 0, 0.15);
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
  border-bottom: 1px solid #e5e5ea;
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
  color: #1d1d1f;
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
}

/* Form */
.form-container {
  display: flex;
  flex-direction: column;
  gap: 20px;
}

.form-section {
  display: flex;
  flex-direction: column;
  gap: 10px;
}

.form-label {
  display: flex;
  align-items: center;
  gap: 8px;
  font-size: 13px;
  font-weight: 600;
  color: #1d1d1f;
  letter-spacing: -0.1px;
}

.form-label svg {
  color: #007aff;
  flex-shrink: 0;
}

.required {
  color: #ff3b30;
}

.form-input,
.form-textarea {
  width: 100%;
  padding: 14px 16px;
  border: 1px solid #e5e5ea;
  border-radius: 12px;
  font-size: 15px;
  color: #1d1d1f;
  background: #f5f5f7;
  transition: all 0.2s;
  box-sizing: border-box;
  font-family: inherit;
}

.form-input:focus,
.form-textarea:focus {
  outline: none;
  border-color: #007aff;
  background: #ffffff;
  box-shadow: 0 0 0 4px rgba(0, 122, 255, 0.1);
}

.form-input::placeholder,
.form-textarea::placeholder {
  color: #86868b;
}

.form-textarea {
  resize: vertical;
  min-height: 80px;
  line-height: 1.5;
}

.form-row {
  display: flex;
  gap: 12px;
}

.form-row .form-section {
  flex: 1;
}

/* Tipo Selector */
.tipo-selector {
  display: flex;
  gap: 10px;
}

.tipo-btn {
  flex: 1;
  padding: 14px;
  border: 2px solid #e5e5ea;
  border-radius: 12px;
  background: #ffffff;
  font-size: 14px;
  font-weight: 500;
  color: #86868b;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  transition: all 0.2s;
}

.tipo-btn:active {
  transform: scale(0.98);
}

.tipo-btn.active {
  border-color: #007aff;
  background: #e8f4fd;
  color: #007aff;
  box-shadow: 0 0 0 4px rgba(0, 122, 255, 0.1);
}

.tipo-btn svg {
  flex-shrink: 0;
}

/* Input Prefix */
.input-prefix {
  display: flex;
  align-items: center;
  border: 1px solid #e5e5ea;
  border-radius: 12px;
  overflow: hidden;
  background: #f5f5f7;
  transition: all 0.2s;
}

.input-prefix:focus-within {
  border-color: #007aff;
  background: #ffffff;
  box-shadow: 0 0 0 4px rgba(0, 122, 255, 0.1);
}

.prefix-symbol {
  padding: 14px 16px;
  background: transparent;
  color: #86868b;
  font-weight: 600;
  font-size: 15px;
  flex-shrink: 0;
}

.input-prefix .form-input {
  border: none;
  border-radius: 0;
  background: transparent;
  padding-left: 0;
}

.input-prefix:focus-within .form-input {
  background: transparent;
}

/* Image Upload */
.image-upload-container {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.btn-upload-image {
  padding: 16px;
  border: 2px dashed #e5e5ea;
  border-radius: 12px;
  background: #f5f5f7;
  color: #86868b;
  font-size: 14px;
  font-weight: 500;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 10px;
  transition: all 0.2s;
}

.btn-upload-image:active {
  border-color: #007aff;
  background: #e8f4fd;
  color: #007aff;
  transform: scale(0.98);
}

.image-preview {
  position: relative;
  width: 100%;
  max-height: 200px;
  border-radius: 12px;
  overflow: hidden;
  border: 1px solid #e5e5ea;
}

.image-preview img {
  width: 100%;
  height: auto;
  object-fit: cover;
  display: block;
}

.remove-image {
  position: absolute;
  top: 8px;
  right: 8px;
  width: 32px;
  height: 32px;
  border-radius: 50%;
  background: rgba(0, 0, 0, 0.6);
  color: white;
  border: none;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all 0.2s;
  backdrop-filter: blur(4px);
}

.remove-image:active {
  background: rgba(0, 0, 0, 0.8);
  transform: scale(0.95);
}

.image-hint {
  margin: 4px 0 0;
  font-size: 11px;
  color: #86868b;
}

/* Servicios Selector */
.servicios-selector {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.servicios-options {
  margin-bottom: 4px;
}

.checkbox-option {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 12px;
  border-radius: 12px;
  cursor: pointer;
  transition: background 0.2s;
  border: 1px solid transparent;
}

.checkbox-option:active {
  background: #f5f5f7;
}

.checkbox-option input[type="checkbox"] {
  width: 20px;
  height: 20px;
  cursor: pointer;
  accent-color: #007aff;
  flex-shrink: 0;
}

.checkbox-option span {
  font-size: 14px;
  font-weight: 500;
  color: #1d1d1f;
}

.servicios-list-select {
  border: 1px solid #e5e5ea;
  border-radius: 12px;
  padding: 12px;
  max-height: 300px;
  overflow-y: auto;
  background: #f5f5f7;
}

.servicios-search {
  position: relative;
  margin-bottom: 12px;
}

.servicios-search svg {
  position: absolute;
  left: 14px;
  top: 50%;
  transform: translateY(-50%);
  color: #86868b;
  z-index: 1;
}

.servicios-search .search-input {
  width: 100%;
  padding: 12px 12px 12px 40px;
  border: 1px solid #e5e5ea;
  border-radius: 10px;
  font-size: 14px;
  background: #ffffff;
  color: #1d1d1f;
  box-sizing: border-box;
  font-family: inherit;
}

.servicios-search .search-input:focus {
  outline: none;
  border-color: #007aff;
  box-shadow: 0 0 0 4px rgba(0, 122, 255, 0.1);
}

.servicios-checkboxes {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.servicio-option {
  padding: 12px;
  background: #ffffff;
  border-radius: 10px;
  border: 1px solid #e5e5ea;
}

.servicio-option:active {
  border-color: #007aff;
  background: #e8f4fd;
}

.servicio-checkbox-info {
  display: flex;
  flex-direction: column;
  gap: 4px;
  flex: 1;
}

.servicio-nombre {
  font-size: 14px;
  font-weight: 600;
  color: #1d1d1f;
}

.servicio-meta {
  font-size: 12px;
  color: #86868b;
}

.no-servicios {
  text-align: center;
  padding: 20px;
  color: #86868b;
  font-size: 14px;
}

.servicios-resumen {
  margin-top: 12px;
  padding: 14px;
  background: #e8f4fd;
  border-radius: 12px;
  border: 1px solid #b3d9ff;
}

.resumen-item {
  display: flex;
  align-items: center;
  gap: 10px;
  font-size: 13px;
  color: #007aff;
  margin-bottom: 8px;
  font-weight: 500;
}

.resumen-item:last-child {
  margin-bottom: 0;
}

.resumen-item svg {
  flex-shrink: 0;
  color: #007aff;
}

.empleados-lista {
  margin-top: 10px;
  display: flex;
  flex-wrap: wrap;
  gap: 6px;
}

.empleado-badge {
  display: inline-block;
  padding: 4px 12px;
  background: #ffffff;
  color: #007aff;
  border-radius: 12px;
  font-size: 12px;
  font-weight: 600;
  border: 1px solid #b3d9ff;
}

/* Form Actions */
.form-actions {
  display: flex;
  gap: 12px;
  margin-top: 8px;
}

.btn-cancel,
.btn-submit {
  flex: 1;
  padding: 14px 20px;
  border: none;
  border-radius: 12px;
  font-size: 15px;
  font-weight: 600;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  transition: all 0.2s;
}

.btn-cancel {
  background: #f5f5f7;
  color: #1d1d1f;
  border: 1px solid #e5e5ea;
}

.btn-cancel:active {
  background: #e5e5ea;
  transform: scale(0.98);
}

.btn-submit {
  background: linear-gradient(135deg, #007aff 0%, #5856d6 100%);
  color: white;
  box-shadow: 0 4px 12px rgba(0, 122, 255, 0.3);
}

.btn-submit:active:not(:disabled) {
  transform: scale(0.98);
  box-shadow: 0 2px 8px rgba(0, 122, 255, 0.4);
}

.btn-submit:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}
</style>
