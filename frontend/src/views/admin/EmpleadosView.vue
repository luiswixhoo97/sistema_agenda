<template>
  <div class="empleados-view">
    <!-- Header -->
    <div class="empleados-header">
      <div class="header-left">
        <div class="header-icon">
          <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
            <circle cx="9" cy="7" r="4"></circle>
            <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
            <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
          </svg>
        </div>
        <div class="header-text">
          <h1>Empleados</h1>
          <p class="header-subtitle">{{ empleados.length }} registrados</p>
        </div>
      </div>
      <button class="btn-new-empleado" @click="nuevoEmpleado" title="Nuevo Empleado">
        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <line x1="12" y1="5" x2="12" y2="19"></line>
          <line x1="5" y1="12" x2="19" y2="12"></line>
        </svg>
        <span class="btn-text">Nuevo</span>
      </button>
    </div>

    <!-- Stats -->
    <div class="stats-mini">
      <div class="stat-mini">
        <div class="stat-icon activo">
          <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
            <polyline points="22 4 12 14.01 9 11.01"></polyline>
          </svg>
        </div>
        <div class="stat-info">
          <span class="stat-value">{{ empleadosActivos }}</span>
          <span class="stat-label">Activos</span>
        </div>
      </div>
      <div class="stat-mini">
        <div class="stat-icon inactivo">
          <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <circle cx="12" cy="12" r="10"></circle>
            <line x1="12" y1="8" x2="12" y2="12"></line>
            <line x1="12" y1="16" x2="12.01" y2="16"></line>
          </svg>
        </div>
        <div class="stat-info">
          <span class="stat-value">{{ empleadosInactivos }}</span>
          <span class="stat-label">Inactivos</span>
        </div>
      </div>
    </div>

    <!-- Empleados List -->
    <div class="empleados-container">
      <div v-if="loading" class="loading-container">
        <div class="loader"></div>
        <p>Cargando empleados...</p>
      </div>
      
      <div v-else-if="empleados.length === 0" class="empty-state">
        <div class="empty-icon">
          <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
            <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
            <circle cx="9" cy="7" r="4"></circle>
            <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
            <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
          </svg>
        </div>
        <p>No hay empleados registrados</p>
        <button class="btn-create" @click="nuevoEmpleado">
          <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <line x1="12" y1="5" x2="12" y2="19"></line>
            <line x1="5" y1="12" x2="19" y2="12"></line>
          </svg>
          Agregar empleado
        </button>
      </div>
      
      <div v-else class="empleados-grid">
        <div 
          v-for="empleado in empleados" 
          :key="empleado.id" 
          class="empleado-card"
          :class="{ inactivo: !empleado.active }"
        >
          <div class="empleado-main">
            <div class="empleado-avatar">
              {{ getInitial(empleado.nombre) }}
              <span :class="['status-indicator', empleado.active ? 'activo' : 'inactivo']"></span>
            </div>
            
            <div class="empleado-info">
              <div class="empleado-header-info">
                <h3 class="empleado-nombre">{{ empleado.nombre }}</h3>
                <span :class="['status-badge', empleado.active ? 'activo' : 'inactivo']">
                  {{ empleado.active ? 'Activo' : 'Inactivo' }}
                </span>
              </div>
              
              <div class="empleado-contacto">
                <span class="contacto-item">
                  <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path>
                    <polyline points="22,6 12,13 2,6"></polyline>
                  </svg>
                  {{ empleado.email }}
                </span>
                <span v-if="empleado.telefono" class="contacto-item">
                  <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"></path>
                  </svg>
                  {{ empleado.telefono }}
                </span>
              </div>
              
              <div class="empleado-servicios">
                <span 
                  v-for="servicio in (empleado.servicios || []).slice(0, 2)" 
                  :key="servicio.id" 
                  class="servicio-tag"
                >
                  {{ servicio.nombre }}
                </span>
                <span v-if="(empleado.servicios || []).length > 2" class="servicio-more">
                  +{{ empleado.servicios.length - 2 }}
                </span>
                <span v-if="(empleado.servicios || []).length === 0" class="no-servicios">
                  Sin servicios
                </span>
              </div>
            </div>
          </div>
          
          <div class="empleado-actions">
            <button class="action-btn" @click="editarEmpleado(empleado)" title="Editar">
              <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
              </svg>
            </button>
            <button class="action-btn servicios" @click="gestionarServicios(empleado)" title="Servicios">
              <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"></path>
              </svg>
            </button>
            <button class="action-btn" @click="verHorarios(empleado)" title="Horarios">
              <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                <line x1="16" y1="2" x2="16" y2="6"></line>
                <line x1="8" y1="2" x2="8" y2="6"></line>
                <line x1="3" y1="10" x2="21" y2="10"></line>
              </svg>
            </button>
            <button 
              class="action-btn" 
              :class="empleado.active ? 'danger' : 'success'"
              @click="toggleEmpleado(empleado)"
              :title="empleado.active ? 'Desactivar' : 'Activar'"
            >
              <svg v-if="empleado.active" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="12" cy="12" r="10"></circle>
                <line x1="15" y1="9" x2="9" y2="15"></line>
                <line x1="9" y1="9" x2="15" y2="15"></line>
              </svg>
              <svg v-else xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <polyline points="20 6 9 17 4 12"></polyline>
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
            <h3>{{ modalTitle }}</h3>
            <button class="modal-close" @click="closeModal">
              <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <line x1="18" y1="6" x2="6" y2="18"></line>
                <line x1="6" y1="6" x2="18" y2="18"></line>
              </svg>
            </button>
          </div>
          <div class="modal-body">
            <!-- Formulario de empleado -->
            <div v-if="modalType === 'form'" class="form-container">
              <div class="form-section">
                <label class="form-label">
                  <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                    <circle cx="12" cy="7" r="4"></circle>
                  </svg>
                  Nombre completo <span class="required">*</span>
                </label>
                <input v-model="formData.nombre" type="text" placeholder="Ej: Ana López" class="form-input" />
              </div>
              <div class="form-section">
                <label class="form-label">
                  <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path>
                    <polyline points="22,6 12,13 2,6"></polyline>
                  </svg>
                  Email <span class="required">*</span>
                </label>
                <input v-model="formData.email" type="email" placeholder="ana@beautyspa.com" class="form-input" />
              </div>
              <div class="form-section">
                <label class="form-label">
                  <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"></path>
                  </svg>
                  Teléfono
                </label>
                <input v-model="formData.telefono" type="tel" placeholder="5511111111" class="form-input" />
              </div>
              
              <!-- Campo de contraseña -->
              <div class="form-section">
                <label class="form-label">
                  <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
                    <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
                  </svg>
                  {{ selectedEmpleado ? 'Nueva contraseña' : 'Contraseña' }}
                  <span v-if="!selectedEmpleado" class="required">*</span>
                  <span v-else class="optional">(dejar vacío para no cambiar)</span>
                </label>
                <div class="password-input-wrapper">
                  <input 
                    :type="showPassword ? 'text' : 'password'" 
                    v-model="formData.password" 
                    :placeholder="selectedEmpleado ? '••••••••' : 'Mínimo 6 caracteres'"
                    class="form-input password-field"
                  />
                  <button type="button" class="toggle-password-btn" @click.prevent.stop="togglePasswordVisibility">
                    <svg v-if="showPassword" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                      <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path>
                      <line x1="1" y1="1" x2="23" y2="23"></line>
                    </svg>
                    <svg v-else xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                      <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                      <circle cx="12" cy="12" r="3"></circle>
                    </svg>
                  </button>
                </div>
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
                  Biografía / Descripción
                </label>
                <textarea v-model="formData.bio" placeholder="Experiencia, especialidades..." rows="3" class="form-textarea"></textarea>
              </div>

              <div class="form-section">
                <label class="form-label">
                  <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"></path>
                  </svg>
                  Especialidades
                </label>
                <input v-model="formData.especialidades" type="text" placeholder="Cortes, Coloración, Peinados..." class="form-input" />
              </div>

              <!-- Servicios (solo para nuevo empleado) -->
              <div v-if="!selectedEmpleado" class="form-section">
                <label class="form-label">
                  <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"></path>
                  </svg>
                  Servicios que puede realizar
                </label>
                <div class="servicios-selector">
                  <div 
                    v-for="servicio in todosServicios" 
                    :key="servicio.id"
                    class="servicio-checkbox"
                    :class="{ selected: serviciosSeleccionados.includes(servicio.id) }"
                    @click="toggleServicioSeleccion(servicio.id)"
                  >
                    <svg v-if="serviciosSeleccionados.includes(servicio.id)" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                      <polyline points="20 6 9 17 4 12"></polyline>
                    </svg>
                    <svg v-else xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                      <rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
                    </svg>
                    <span>{{ servicio.nombre }}</span>
                    <small>${{ servicio.precio }}</small>
                  </div>
                  <p v-if="todosServicios.length === 0" class="no-servicios-msg">
                    No hay servicios disponibles. Crea servicios primero.
                  </p>
                </div>
              </div>

              <div v-if="formError" class="form-error">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <circle cx="12" cy="12" r="10"></circle>
                  <line x1="12" y1="8" x2="12" y2="12"></line>
                  <line x1="12" y1="16" x2="12.01" y2="16"></line>
                </svg>
                {{ formError }}
              </div>

              <div class="form-actions">
                <button type="button" class="btn-cancel" @click="closeModal">
                  Cancelar
                </button>
                <button type="button" class="btn-submit" @click="guardarEmpleado" :disabled="guardando">
                  <svg v-if="guardando" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="spinning">
                    <line x1="12" y1="2" x2="12" y2="6"></line>
                    <line x1="12" y1="18" x2="12" y2="22"></line>
                    <line x1="4.93" y1="4.93" x2="7.76" y2="7.76"></line>
                    <line x1="16.24" y1="16.24" x2="19.07" y2="19.07"></line>
                    <line x1="2" y1="12" x2="6" y2="12"></line>
                    <line x1="18" y1="12" x2="22" y2="12"></line>
                    <line x1="4.93" y1="19.07" x2="7.76" y2="16.24"></line>
                    <line x1="16.24" y1="7.76" x2="19.07" y2="4.93"></line>
                  </svg>
                  <svg v-else xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path>
                    <polyline points="17 21 17 13 7 13 7 21"></polyline>
                    <polyline points="7 3 7 8 15 8"></polyline>
                  </svg>
                  {{ guardando ? 'Guardando...' : 'Guardar' }}
                </button>
              </div>
            </div>
            
            <!-- Gestión de Servicios -->
            <div v-if="modalType === 'servicios'" class="servicios-container">
              <p class="servicios-description">
                Selecciona los servicios que <strong>{{ selectedEmpleado?.nombre }}</strong> puede realizar:
              </p>
              
              <!-- Buscador de servicios -->
              <div class="servicios-search">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <circle cx="11" cy="11" r="8"></circle>
                  <path d="m21 21-4.35-4.35"></path>
                </svg>
                <input 
                  type="text" 
                  v-model="busquedaServicio" 
                  placeholder="Buscar servicio..."
                />
                <button v-if="busquedaServicio" type="button" class="clear-search" @click="busquedaServicio = ''">
                  <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="18" y1="6" x2="6" y2="18"></line>
                    <line x1="6" y1="6" x2="18" y2="18"></line>
                  </svg>
                </button>
              </div>
              
              <div class="servicios-list">
                <div 
                  v-for="servicio in serviciosFiltrados" 
                  :key="servicio.id"
                  class="servicio-item"
                  :class="{ selected: serviciosDelEmpleado.some(s => s.id === servicio.id) }"
                  @click="toggleServicioEmpleado(servicio)"
                >
                  <div class="servicio-check">
                    <svg v-if="serviciosDelEmpleado.some(s => s.id === servicio.id)" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                      <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                      <polyline points="22 4 12 14.01 9 11.01"></polyline>
                    </svg>
                    <svg v-else xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                      <circle cx="12" cy="12" r="10"></circle>
                    </svg>
                  </div>
                  <div class="servicio-info">
                    <span class="servicio-nombre">{{ servicio.nombre }}</span>
                    <span class="servicio-categoria">{{ servicio.categoria?.nombre || 'Sin categoría' }}</span>
                  </div>
                  <div class="servicio-precio">
                    <span class="precio-label">Precio</span>
                    <span class="precio-valor">${{ servicio.precio }}</span>
                  </div>
                  <div class="servicio-duracion">
                    <span class="duracion-label">Duración</span>
                    <span class="duracion-valor">{{ servicio.duracion || servicio.duracion_minutos }} min</span>
                  </div>
                </div>
                
                <!-- Sin resultados -->
                <div v-if="serviciosFiltrados.length === 0 && busquedaServicio" class="no-results">
                  <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="11" cy="11" r="8"></circle>
                    <path d="m21 21-4.35-4.35"></path>
                  </svg>
                  <p>No se encontraron servicios con "{{ busquedaServicio }}"</p>
                </div>
              </div>

              <div class="servicios-resumen">
                <span>{{ serviciosDelEmpleado.length }} servicio(s) seleccionado(s)</span>
              </div>

              <div class="form-actions">
                <button type="button" class="btn-cancel" @click="closeModal">
                  Cancelar
                </button>
                <button type="button" class="btn-submit" @click="guardarServicios" :disabled="guardando">
                  <svg v-if="guardando" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="spinning">
                    <line x1="12" y1="2" x2="12" y2="6"></line>
                    <line x1="12" y1="18" x2="12" y2="22"></line>
                    <line x1="4.93" y1="4.93" x2="7.76" y2="7.76"></line>
                    <line x1="16.24" y1="16.24" x2="19.07" y2="19.07"></line>
                    <line x1="2" y1="12" x2="6" y2="12"></line>
                    <line x1="18" y1="12" x2="22" y2="12"></line>
                    <line x1="4.93" y1="19.07" x2="7.76" y2="16.24"></line>
                    <line x1="16.24" y1="7.76" x2="19.07" y2="4.93"></line>
                  </svg>
                  <svg v-else xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path>
                    <polyline points="17 21 17 13 7 13 7 21"></polyline>
                    <polyline points="7 3 7 8 15 8"></polyline>
                  </svg>
                  {{ guardando ? 'Guardando...' : 'Guardar Servicios' }}
                </button>
              </div>
            </div>

            <!-- Horarios -->
            <div v-if="modalType === 'horarios'" class="horarios-container">
              <div class="horario-day" v-for="dia in diasSemana" :key="dia.value">
                <div class="day-header">
                  <span class="day-name">{{ dia.label }}</span>
                  <label class="toggle-day">
                    <input type="checkbox" v-model="dia.activo" />
                    <span class="toggle-switch"></span>
                  </label>
                </div>
                <div v-if="dia.activo" class="day-hours">
                  <div class="time-input-group">
                    <label class="time-label">Inicio</label>
                    <input type="time" v-model="dia.inicio" class="time-input" />
                  </div>
                  <span class="time-separator">a</span>
                  <div class="time-input-group">
                    <label class="time-label">Fin</label>
                    <input type="time" v-model="dia.fin" class="time-input" />
                  </div>
                </div>
              </div>
              <div class="form-actions">
                <button type="button" class="btn-cancel" @click="closeModal">
                  Cancelar
                </button>
                <button type="button" class="btn-submit" @click="guardarHorarios" :disabled="guardando">
                  <svg v-if="guardando" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="spinning">
                    <line x1="12" y1="2" x2="12" y2="6"></line>
                    <line x1="12" y1="18" x2="12" y2="22"></line>
                    <line x1="4.93" y1="4.93" x2="7.76" y2="7.76"></line>
                    <line x1="16.24" y1="16.24" x2="19.07" y2="19.07"></line>
                    <line x1="2" y1="12" x2="6" y2="12"></line>
                    <line x1="18" y1="12" x2="22" y2="12"></line>
                    <line x1="4.93" y1="19.07" x2="7.76" y2="16.24"></line>
                    <line x1="16.24" y1="7.76" x2="19.07" y2="4.93"></line>
                  </svg>
                  <svg v-else xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path>
                    <polyline points="17 21 17 13 7 13 7 21"></polyline>
                    <polyline points="7 3 7 8 15 8"></polyline>
                  </svg>
                  {{ guardando ? 'Guardando...' : 'Guardar Horarios' }}
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
import { ref, computed, onMounted, reactive } from 'vue';
import { 
  getEmpleados, 
  createEmpleado,
  updateEmpleado, 
  getEmpleadoHorarios, 
  updateEmpleadoHorarios,
  getEmpleadoServicios,
  updateEmpleadoServicios,
  getServicios
} from '@/services/adminService';
import Swal from 'sweetalert2';

interface Servicio {
  id: number;
  nombre: string;
  precio: number;
  duracion?: number;
  duracion_minutos?: number;
  categoria?: { id: number; nombre: string };
}

const empleados = ref<any[]>([]);
const todosServicios = ref<Servicio[]>([]);
const loading = ref(true);
const guardando = ref(false);
const showModal = ref(false);
const modalType = ref<'form' | 'horarios' | 'servicios'>('form');
const selectedEmpleado = ref<any>(null);
const showPassword = ref(false);
const formError = ref('');
const busquedaServicio = ref('');

const formData = reactive({
  nombre: '',
  email: '',
  telefono: '',
  password: '',
  bio: '',
  especialidades: '',
});

// Servicios seleccionados para nuevo empleado
const serviciosSeleccionados = ref<number[]>([]);

// Servicios del empleado seleccionado (para edición de servicios)
const serviciosDelEmpleado = ref<{ id: number; precio_especial?: number | null }[]>([]);

const diasSemana = ref([
  { value: 1, label: 'Lunes', activo: true, inicio: '09:00', fin: '18:00' },
  { value: 2, label: 'Martes', activo: true, inicio: '09:00', fin: '18:00' },
  { value: 3, label: 'Miércoles', activo: true, inicio: '09:00', fin: '18:00' },
  { value: 4, label: 'Jueves', activo: true, inicio: '09:00', fin: '18:00' },
  { value: 5, label: 'Viernes', activo: true, inicio: '09:00', fin: '18:00' },
  { value: 6, label: 'Sábado', activo: true, inicio: '10:00', fin: '14:00' },
  { value: 0, label: 'Domingo', activo: false, inicio: '', fin: '' },
]);

const empleadosActivos = computed(() => empleados.value.filter(e => e.active).length);
const empleadosInactivos = computed(() => empleados.value.filter(e => !e.active).length);

// Filtrar servicios por búsqueda parcial
const serviciosFiltrados = computed(() => {
  if (!busquedaServicio.value.trim()) {
    return todosServicios.value;
  }
  const busqueda = busquedaServicio.value.toLowerCase().trim();
  return todosServicios.value.filter(servicio => 
    servicio.nombre.toLowerCase().includes(busqueda) ||
    (servicio.categoria?.nombre || '').toLowerCase().includes(busqueda)
  );
});

const modalTitle = computed(() => {
  if (modalType.value === 'horarios') return `Horarios de ${selectedEmpleado.value?.nombre || ''}`;
  if (modalType.value === 'servicios') return `Servicios de ${selectedEmpleado.value?.nombre || ''}`;
  return selectedEmpleado.value ? 'Editar Empleado' : 'Nuevo Empleado';
});

async function cargarEmpleados() {
  loading.value = true;
  try {
    const response = await getEmpleados();
    if (response.success) {
      empleados.value = response.data || [];
    }
  } catch (error) {
    console.error('Error cargando empleados:', error);
  } finally {
    loading.value = false;
  }
}

async function cargarServicios() {
  try {
    const response = await getServicios();
    if (response.success) {
      todosServicios.value = response.data || [];
    }
  } catch (error) {
    console.error('Error cargando servicios:', error);
  }
}

function getInitial(nombre: string): string {
  return nombre?.charAt(0)?.toUpperCase() || '?';
}

function togglePasswordVisibility() {
  showPassword.value = !showPassword.value;
}

function nuevoEmpleado() { 
  selectedEmpleado.value = null;
  formData.nombre = '';
  formData.email = '';
  formData.telefono = '';
  formData.password = '';
  formData.bio = '';
  formData.especialidades = '';
  serviciosSeleccionados.value = [];
  showPassword.value = false;
  formError.value = '';
  modalType.value = 'form';
  showModal.value = true;
}

function editarEmpleado(e: any) { 
  selectedEmpleado.value = e;
  formData.nombre = e.nombre;
  formData.email = e.email;
  formData.telefono = e.telefono || '';
  formData.password = '';
  formData.bio = e.bio || '';
  formData.especialidades = e.especialidades || '';
  showPassword.value = false;
  formError.value = '';
  modalType.value = 'form';
  showModal.value = true;
}

async function gestionarServicios(e: any) {
  selectedEmpleado.value = e;
  modalType.value = 'servicios';
  showModal.value = true;
  
  try {
    const response = await getEmpleadoServicios(e.id);
    if (response.success && response.data) {
      serviciosDelEmpleado.value = response.data.map((s: any) => ({
        id: s.id,
        precio_especial: s.precio_especial,
      }));
    } else {
      serviciosDelEmpleado.value = [];
    }
  } catch (error) {
    console.error('Error cargando servicios del empleado:', error);
    serviciosDelEmpleado.value = [];
  }
}

function toggleServicioEmpleado(servicio: Servicio) {
  const index = serviciosDelEmpleado.value.findIndex(s => s.id === servicio.id);
  if (index >= 0) {
    serviciosDelEmpleado.value.splice(index, 1);
  } else {
    serviciosDelEmpleado.value.push({ id: servicio.id, precio_especial: null });
  }
}

function toggleServicioSeleccion(servicioId: number) {
  const index = serviciosSeleccionados.value.indexOf(servicioId);
  if (index >= 0) {
    serviciosSeleccionados.value.splice(index, 1);
  } else {
    serviciosSeleccionados.value.push(servicioId);
  }
}

async function guardarServicios() {
  if (!selectedEmpleado.value) return;
  
  guardando.value = true;
  try {
    await updateEmpleadoServicios(selectedEmpleado.value.id, serviciosDelEmpleado.value);
    Swal.fire({
      icon: 'success',
      title: '¡Éxito!',
      text: 'Servicios actualizados correctamente',
      confirmButtonColor: '#34c759',
      timer: 1500,
      showConfirmButton: false
    });
    closeModal();
    await cargarEmpleados();
  } catch (error: any) {
    console.error('Error guardando servicios:', error);
    Swal.fire({
      icon: 'error',
      title: 'Error',
      text: error.response?.data?.message || 'Error al guardar servicios',
      confirmButtonColor: '#ff3b30'
    });
  } finally {
    guardando.value = false;
  }
}

async function verHorarios(e: any) { 
  selectedEmpleado.value = e;
  modalType.value = 'horarios';
  showModal.value = true;
  
  try {
    const response = await getEmpleadoHorarios(e.id);
    if (response.success && response.data) {
      diasSemana.value.forEach(dia => {
        const diaBackend = dia.value === 0 ? 7 : dia.value;
        const horario = response.data.find((h: any) => h.dia_semana === diaBackend);
        if (horario) {
          dia.activo = horario.active;
          dia.inicio = horario.hora_inicio;
          dia.fin = horario.hora_fin;
        } else {
          dia.activo = false;
          dia.inicio = '09:00';
          dia.fin = '18:00';
        }
      });
    }
  } catch (error) {
    console.error('Error cargando horarios:', error);
  }
}

async function toggleEmpleado(e: any) {
  try {
    const nuevoEstado = !e.active;
    await updateEmpleado(e.id, { active: nuevoEstado });
    e.active = nuevoEstado;
    await cargarEmpleados();
  } catch (error: any) {
    console.error('Error actualizando empleado:', error);
    Swal.fire({
      icon: 'error',
      title: 'Error',
      text: error.response?.data?.message || 'Error al actualizar empleado',
      confirmButtonColor: '#ff3b30'
    });
  }
}

async function guardarEmpleado() {
  formError.value = '';
  
  // Validaciones
  if (!formData.nombre.trim()) {
    formError.value = 'El nombre es requerido';
    return;
  }
  if (!formData.email.trim()) {
    formError.value = 'El email es requerido';
    return;
  }
  if (!selectedEmpleado.value && !formData.password) {
    formError.value = 'La contraseña es requerida para nuevos empleados';
    return;
  }
  if (formData.password && formData.password.length < 6) {
    formError.value = 'La contraseña debe tener al menos 6 caracteres';
    return;
  }

  guardando.value = true;
  try {
    if (selectedEmpleado.value) {
      // Actualizar empleado existente
      const datos: any = {
        nombre: formData.nombre.trim(),
        email: formData.email.trim(),
        telefono: formData.telefono?.trim() || null,
        bio: formData.bio?.trim() || null,
        especialidades: formData.especialidades?.trim() || null,
      };
      if (formData.password) {
        datos.password = formData.password;
      }
      await updateEmpleado(selectedEmpleado.value.id, datos);
      Swal.fire({
        icon: 'success',
        title: '¡Éxito!',
        text: 'Empleado actualizado correctamente',
        confirmButtonColor: '#34c759',
        timer: 1500,
        showConfirmButton: false
      });
    } else {
      // Crear nuevo empleado
      const datos = {
        nombre: formData.nombre.trim(),
        email: formData.email.trim(),
        telefono: formData.telefono?.trim() || null,
        password: formData.password,
        bio: formData.bio?.trim() || null,
        especialidades: formData.especialidades?.trim() || null,
        servicios: serviciosSeleccionados.value,
      };
      await createEmpleado(datos);
      Swal.fire({
        icon: 'success',
        title: '¡Éxito!',
        text: 'Empleado creado correctamente',
        confirmButtonColor: '#34c759',
        timer: 1500,
        showConfirmButton: false
      });
    }
    closeModal();
    await cargarEmpleados();
  } catch (error: any) {
    console.error('Error guardando empleado:', error);
    if (error.response?.data?.errors) {
      const errores = Object.values(error.response.data.errors).flat();
      formError.value = errores.join('. ');
    } else {
      formError.value = error.response?.data?.message || 'Error al guardar empleado';
    }
  } finally {
    guardando.value = false;
  }
}

async function guardarHorarios() {
  if (!selectedEmpleado.value) return;
  
  guardando.value = true;
  try {
    const horarios = diasSemana.value.map(dia => {
      let horaInicio = dia.activo && dia.inicio ? dia.inicio.trim() : '09:00';
      let horaFin = dia.activo && dia.fin ? dia.fin.trim() : '18:00';
      
      if (!/^([0-1][0-9]|2[0-3]):[0-5][0-9]$/.test(horaInicio)) {
        horaInicio = '09:00';
      }
      if (!/^([0-1][0-9]|2[0-3]):[0-5][0-9]$/.test(horaFin)) {
        horaFin = '18:00';
      }
      
      return {
        dia_semana: dia.value === 0 ? 7 : dia.value,
        active: !!dia.activo,
        hora_inicio: horaInicio,
        hora_fin: horaFin,
      };
    });
    
    await updateEmpleadoHorarios(selectedEmpleado.value.id, { horarios });
    Swal.fire({
      icon: 'success',
      title: '¡Éxito!',
      text: 'Horarios actualizados correctamente',
      confirmButtonColor: '#34c759',
      timer: 1500,
      showConfirmButton: false
    });
    closeModal();
    await cargarEmpleados();
  } catch (error: any) {
    console.error('Error guardando horarios:', error);
    const mensaje = error.response?.data?.message || 'Error al guardar horarios';
    Swal.fire({
      icon: 'error',
      title: 'Error',
      text: mensaje,
      confirmButtonColor: '#ff3b30'
    });
  } finally {
    guardando.value = false;
  }
}

function closeModal() {
  showModal.value = false;
  selectedEmpleado.value = null;
  formError.value = '';
  busquedaServicio.value = '';
  showPassword.value = false;
}

onMounted(async () => {
  await Promise.all([cargarEmpleados(), cargarServicios()]);
});
</script>

<style scoped>
/* ===== Apple-inspired Empleados View Design ===== */

.empleados-view {
  min-height: 100vh;
  background: #f5f5f7;
  padding: 24px;
  padding-bottom: 120px;
  font-family: -apple-system, BlinkMacSystemFont, 'SF Pro Display', 'SF Pro Text', 'Helvetica Neue', sans-serif;
  -webkit-font-smoothing: antialiased;
  -moz-osx-font-smoothing: grayscale;
}

/* Header */
.empleados-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 16px;
  padding: 16px;
  background: linear-gradient(135deg, #1d1d1f 0%, #3a3a3c 100%);
  border-radius: 16px;
  color: white;
}

.header-left {
  display: flex;
  align-items: center;
  gap: 12px;
  flex: 1;
  min-width: 0;
}

.header-icon {
  width: 40px;
  height: 40px;
  background: rgba(255, 255, 255, 0.1);
  border-radius: 12px;
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
  font-size: 18px;
  font-weight: 600;
  margin: 0;
  letter-spacing: -0.3px;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.header-subtitle {
  font-size: 12px;
  opacity: 0.7;
  margin: 2px 0 0;
}

@media (min-width: 480px) {
  .empleados-header {
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
}

.btn-new-empleado {
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

.btn-new-empleado .btn-text {
  display: none;
}

.btn-new-empleado:active {
  background: rgba(255, 255, 255, 0.25);
  transform: scale(0.98);
}

@media (min-width: 480px) {
  .btn-new-empleado {
    padding: 12px 20px;
    font-size: 15px;
  }
  
  .btn-new-empleado .btn-text {
    display: inline;
  }
}

/* Stats Mini */
.stats-mini {
  display: flex;
  gap: 12px;
  margin-bottom: 20px;
}

.stat-mini {
  flex: 1;
  display: flex;
  align-items: center;
  gap: 12px;
  background: #ffffff;
  padding: 16px;
  border-radius: 16px;
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
}

.stat-icon.activo {
  background: #e8f5e9;
  color: #34c759;
}

.stat-icon.inactivo {
  background: #fff3e0;
  color: #ff9500;
}

.stat-info {
  display: flex;
  flex-direction: column;
  gap: 2px;
}

.stat-value {
  font-size: 20px;
  font-weight: 700;
  color: #1d1d1f;
  line-height: 1;
}

.stat-label {
  font-size: 12px;
  color: #86868b;
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
  padding: 12px 24px;
  background: linear-gradient(135deg, #007aff 0%, #5856d6 100%);
  color: white;
  border: none;
  border-radius: 12px;
  font-size: 15px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s;
  box-shadow: 0 4px 12px rgba(0, 122, 255, 0.3);
}

.btn-create:active {
  transform: scale(0.98);
  box-shadow: 0 2px 8px rgba(0, 122, 255, 0.4);
}

/* Empleados Grid */
.empleados-grid {
  display: flex;
  flex-direction: column;
  gap: 10px;
}

.empleado-card {
  background: #ffffff;
  border-radius: 14px;
  box-shadow: 0 2px 12px rgba(0, 0, 0, 0.08);
  border: 1px solid #e5e5ea;
  transition: all 0.2s;
  padding: 14px;
}

.empleado-card.inactivo {
  opacity: 0.7;
}

.empleado-main {
  display: flex;
  align-items: flex-start;
  gap: 14px;
  margin-bottom: 12px;
}

.empleado-avatar {
  position: relative;
  width: 52px;
  height: 52px;
  background: linear-gradient(135deg, #007aff 0%, #5856d6 100%);
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
  font-weight: 700;
  font-size: 20px;
  flex-shrink: 0;
  box-shadow: 0 2px 8px rgba(0, 122, 255, 0.3);
}

.status-indicator {
  position: absolute;
  bottom: -2px;
  right: -2px;
  width: 14px;
  height: 14px;
  border-radius: 50%;
  border: 2px solid white;
  box-shadow: 0 2px 6px rgba(0, 0, 0, 0.2);
}

.status-indicator.activo {
  background: #34c759;
}

.status-indicator.inactivo {
  background: #ff3b30;
}

.empleado-info {
  flex: 1;
  min-width: 0;
}

.empleado-header-info {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 10px;
  margin-bottom: 8px;
}

.empleado-nombre {
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

.status-badge {
  padding: 4px 10px;
  border-radius: 8px;
  font-size: 11px;
  font-weight: 600;
  flex-shrink: 0;
}

.status-badge.activo {
  background: #e8f5e9;
  color: #34c759;
}

.status-badge.inactivo {
  background: #ffebee;
  color: #ff3b30;
}

.empleado-contacto {
  display: flex;
  flex-direction: column;
  gap: 4px;
  margin-bottom: 10px;
}

.contacto-item {
  display: flex;
  align-items: center;
  gap: 6px;
  font-size: 12px;
  color: #86868b;
}

.contacto-item svg {
  color: #007aff;
  flex-shrink: 0;
}

.empleado-servicios {
  display: flex;
  flex-wrap: wrap;
  gap: 5px;
}

.servicio-tag {
  background: #e8f4fd;
  color: #007aff;
  padding: 4px 10px;
  border-radius: 8px;
  font-size: 11px;
  font-weight: 500;
}

.servicio-more {
  background: #f5f5f7;
  color: #86868b;
  padding: 4px 10px;
  border-radius: 8px;
  font-size: 11px;
}

.no-servicios {
  font-size: 11px;
  color: #86868b;
  font-style: italic;
}

.empleado-actions {
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

.action-btn.servicios {
  background: #fff3e0;
  color: #ff9500;
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

.optional {
  font-weight: 400;
  font-size: 11px;
  color: #86868b;
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
  min-height: 100px;
}

/* Password Input */
.password-input-wrapper {
  position: relative;
  display: flex;
  align-items: center;
}

.password-field {
  padding-right: 50px !important;
}

.toggle-password-btn {
  position: absolute;
  right: 8px;
  top: 50%;
  transform: translateY(-50%);
  width: 36px;
  height: 36px;
  border: none;
  background: transparent;
  color: #86868b;
  cursor: pointer;
  border-radius: 8px;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all 0.2s;
  z-index: 10;
}

.toggle-password-btn:active {
  background: #e5e5ea;
  transform: translateY(-50%) scale(0.95);
}

/* Servicios Selector */
.servicios-selector {
  max-height: 200px;
  overflow-y: auto;
  border: 1px solid #e5e5ea;
  border-radius: 12px;
  padding: 8px;
  background: #f5f5f7;
}

.servicio-checkbox {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 12px;
  border-radius: 10px;
  cursor: pointer;
  transition: all 0.2s;
}

.servicio-checkbox:hover {
  background: #ffffff;
}

.servicio-checkbox.selected {
  background: #e8f4fd;
}

.servicio-checkbox svg {
  color: #86868b;
  flex-shrink: 0;
}

.servicio-checkbox.selected svg {
  color: #007aff;
}

.servicio-checkbox span {
  flex: 1;
  font-size: 14px;
  color: #1d1d1f;
}

.servicio-checkbox small {
  font-size: 13px;
  color: #007aff;
  font-weight: 600;
}

.no-servicios-msg {
  text-align: center;
  color: #86868b;
  font-size: 14px;
  padding: 20px;
}

/* Form Error */
.form-error {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 14px 16px;
  background: #ffebee;
  color: #ff3b30;
  border-radius: 12px;
  font-size: 14px;
}

.form-error svg {
  flex-shrink: 0;
}

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

.spinning {
  animation: spin 0.8s linear infinite;
}

/* Servicios Container */
.servicios-container {
  display: flex;
  flex-direction: column;
  gap: 20px;
}

.servicios-description {
  font-size: 14px;
  color: #86868b;
  margin: 0;
}

.servicios-description strong {
  color: #1d1d1f;
}

/* Buscador de servicios */
.servicios-search {
  position: relative;
  display: flex;
  align-items: center;
  gap: 12px;
  background: #f5f5f7;
  border: 1px solid #e5e5ea;
  border-radius: 12px;
  padding: 0 16px;
  transition: all 0.2s;
}

.servicios-search:focus-within {
  border-color: #007aff;
  background: #ffffff;
  box-shadow: 0 0 0 4px rgba(0, 122, 255, 0.1);
}

.servicios-search svg {
  color: #86868b;
  flex-shrink: 0;
}

.servicios-search input {
  flex: 1;
  border: none;
  background: transparent;
  padding: 14px 0;
  font-size: 15px;
  color: #1d1d1f;
  outline: none;
}

.servicios-search input::placeholder {
  color: #86868b;
}

.clear-search {
  width: 32px;
  height: 32px;
  border: none;
  background: transparent;
  color: #86868b;
  cursor: pointer;
  border-radius: 8px;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all 0.2s;
}

.clear-search:active {
  background: #e5e5ea;
  transform: scale(0.95);
}

.no-results {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 40px 20px;
  color: #86868b;
  text-align: center;
}

.no-results svg {
  margin-bottom: 12px;
  opacity: 0.5;
}

.no-results p {
  margin: 0;
  font-size: 14px;
}

.servicios-list {
  display: flex;
  flex-direction: column;
  gap: 10px;
  max-height: 400px;
  overflow-y: auto;
}

.servicio-item {
  display: flex;
  align-items: center;
  gap: 14px;
  padding: 14px;
  border: 2px solid #e5e5ea;
  border-radius: 12px;
  cursor: pointer;
  transition: all 0.2s;
  background: #ffffff;
}

.servicio-item:hover {
  border-color: #007aff;
  box-shadow: 0 2px 8px rgba(0, 122, 255, 0.1);
}

.servicio-item.selected {
  border-color: #007aff;
  background: #e8f4fd;
}

.servicio-check {
  color: #d1d1d6;
  flex-shrink: 0;
}

.servicio-item.selected .servicio-check {
  color: #007aff;
}

.servicio-info {
  flex: 1;
  min-width: 0;
}

.servicio-nombre {
  display: block;
  font-weight: 600;
  color: #1d1d1f;
  font-size: 15px;
  margin-bottom: 4px;
}

.servicio-categoria {
  display: block;
  font-size: 12px;
  color: #86868b;
}

.servicio-precio,
.servicio-duracion {
  text-align: center;
  flex-shrink: 0;
}

.precio-label,
.duracion-label {
  display: block;
  font-size: 10px;
  color: #86868b;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  margin-bottom: 4px;
}

.precio-valor {
  display: block;
  font-weight: 700;
  color: #007aff;
  font-size: 15px;
}

.duracion-valor {
  display: block;
  font-weight: 600;
  color: #1d1d1f;
  font-size: 14px;
}

.servicios-resumen {
  text-align: center;
  padding: 12px;
  background: #f5f5f7;
  border-radius: 12px;
  font-size: 14px;
  color: #86868b;
}

/* Horarios */
.horarios-container {
  display: flex;
  flex-direction: column;
  gap: 14px;
}

.horario-day {
  background: #ffffff;
  border: 2px solid #e5e5ea;
  border-radius: 14px;
  padding: 18px;
  transition: all 0.2s;
}

.horario-day:hover {
  border-color: #007aff;
  box-shadow: 0 2px 8px rgba(0, 122, 255, 0.1);
}

.day-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 14px;
}

.day-name {
  font-weight: 600;
  font-size: 16px;
  color: #1d1d1f;
  flex: 1;
}

.toggle-day {
  display: flex;
  align-items: center;
  cursor: pointer;
  flex-shrink: 0;
}

.toggle-day input {
  display: none;
}

.toggle-switch {
  width: 50px;
  height: 30px;
  background: #e5e5ea;
  border-radius: 15px;
  position: relative;
  transition: background 0.3s;
}

.toggle-switch::after {
  content: '';
  position: absolute;
  width: 26px;
  height: 26px;
  background: white;
  border-radius: 50%;
  top: 2px;
  left: 2px;
  transition: transform 0.3s;
  box-shadow: 0 2px 6px rgba(0, 0, 0, 0.2);
}

.toggle-day input:checked + .toggle-switch {
  background: linear-gradient(135deg, #007aff 0%, #5856d6 100%);
}

.toggle-day input:checked + .toggle-switch::after {
  transform: translateX(20px);
}

.day-hours {
  display: flex;
  align-items: flex-end;
  gap: 14px;
  margin-top: 14px;
  padding-top: 14px;
  border-top: 1px solid #e5e5ea;
}

.time-input-group {
  flex: 1;
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.time-label {
  font-size: 11px;
  font-weight: 600;
  color: #86868b;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.time-input {
  width: 100%;
  padding: 12px 14px;
  border: 2px solid #e5e5ea;
  border-radius: 10px;
  font-size: 15px;
  font-weight: 600;
  color: #1d1d1f;
  transition: all 0.2s;
  background: #f5f5f7;
  font-family: inherit;
}

.time-input:focus {
  outline: none;
  border-color: #007aff;
  background: #ffffff;
  box-shadow: 0 0 0 4px rgba(0, 122, 255, 0.1);
}

.time-separator {
  color: #86868b;
  font-weight: 500;
  font-size: 15px;
  padding-bottom: 10px;
  flex-shrink: 0;
}
</style>
