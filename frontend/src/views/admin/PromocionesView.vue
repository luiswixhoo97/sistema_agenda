<template>
  <div class="admin-view">
    <!-- Header -->
    <div class="view-header">
      <div class="header-info">
        <div class="header-icon">
          <i class="fa fa-percent"></i>
        </div>
        <div class="header-text">
          <h1>Promociones</h1>
          <p class="header-subtitle">{{ promocionesActivas }} activas</p>
        </div>
      </div>
      <button class="btn-action" @click="nuevaPromocion">
        <i class="fa fa-plus"></i>
      </button>
    </div>

    <!-- Stats -->
    <div class="promo-stats">
      <div class="promo-stat">
        <div class="stat-icon green">
          <i class="fa fa-check-circle"></i>
        </div>
        <div class="stat-info">
          <span class="stat-value">{{ promocionesActivas }}</span>
          <span class="stat-label">Activas</span>
        </div>
      </div>
      <div class="promo-stat">
        <div class="stat-icon orange">
          <i class="fa fa-ticket-alt"></i>
        </div>
        <div class="stat-info">
          <span class="stat-value">{{ totalUsos }}</span>
          <span class="stat-label">Usos Total</span>
        </div>
      </div>
    </div>

    <!-- Promociones List -->
    <div class="promociones-container">
      <div v-if="loading" class="loading-state">
        <div class="spinner"></div>
        <p>Cargando promociones...</p>
      </div>
      
      <div v-else-if="promociones.length === 0" class="empty-state">
        <i class="fa fa-tags"></i>
        <p>No hay promociones creadas</p>
        <button class="btn-create" @click="nuevaPromocion">
          <i class="fa fa-plus"></i> Crear primera promoción
        </button>
      </div>
      
      <div v-else class="promociones-list">
        <div 
          v-for="promo in promociones" 
          :key="promo.id" 
          class="promo-card"
          :class="{ inactiva: !promo.activa }"
        >
          <div class="promo-badge-container">
            <div class="promo-badge" :class="promo.descuento_porcentaje ? 'porcentaje' : 'fijo'">
              <span class="badge-value">
                {{ promo.descuento_porcentaje ? `${promo.descuento_porcentaje}%` : `$${promo.descuento_fijo}` }}
              </span>
              <span class="badge-label">{{ promo.descuento_porcentaje ? 'DESC' : 'OFF' }}</span>
            </div>
          </div>
          
          <div class="promo-content">
            <h3 class="promo-nombre">{{ promo.nombre }}</h3>
            <p class="promo-descripcion">{{ promo.descripcion || 'Sin descripción' }}</p>
            
            <div class="promo-dates">
              <i class="fa fa-calendar-alt"></i>
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
          
          <div class="promo-footer">
            <span :class="['status-badge', promo.activa ? 'activa' : 'inactiva']">
              {{ promo.activa ? 'Activa' : 'Inactiva' }}
            </span>
            <div class="promo-actions">
              <button class="btn-icon-sm" @click="editarPromocion(promo)">
                <i class="fa fa-edit"></i>
              </button>
              <button 
                class="btn-icon-sm"
                :class="promo.activa ? 'danger' : 'success'"
                @click="togglePromocion(promo)"
              >
                <i :class="promo.activa ? 'fa fa-pause' : 'fa fa-play'"></i>
              </button>
            </div>
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
              <i class="fa fa-times"></i>
            </button>
          </div>
          <div class="modal-body">
            <div class="form-container">
              <div class="form-group">
                <label>Nombre de la promoción</label>
                <input v-model="formData.nombre" type="text" placeholder="Ej: 10% en primera visita" />
              </div>
              
              <div class="form-group">
                <label>Descripción</label>
                <textarea v-model="formData.descripcion" rows="2" placeholder="Descripción breve..."></textarea>
              </div>
              
              <div class="form-group">
                <label>Tipo de descuento</label>
                <div class="tipo-selector">
                  <button 
                    :class="['tipo-btn', { active: formData.tipo === 'porcentaje' }]"
                    @click="formData.tipo = 'porcentaje'"
                  >
                    <i class="fa fa-percent"></i>
                    Porcentaje
                  </button>
                  <button 
                    :class="['tipo-btn', { active: formData.tipo === 'fijo' }]"
                    @click="formData.tipo = 'fijo'"
                  >
                    <i class="fa fa-dollar-sign"></i>
                    Monto Fijo
                  </button>
                </div>
              </div>
              
              <div class="form-group">
                <label>Valor del descuento</label>
                <div class="input-prefix">
                  <span>{{ formData.tipo === 'porcentaje' ? '%' : '$' }}</span>
                  <input v-model.number="formData.valor" type="number" placeholder="0" />
                </div>
              </div>
              
              <div class="form-row">
                <div class="form-group">
                  <label>Fecha inicio</label>
                  <input v-model="formData.fecha_inicio" type="date" />
                </div>
                <div class="form-group">
                  <label>Fecha fin</label>
                  <input v-model="formData.fecha_fin" type="date" />
                </div>
              </div>
              
              <div class="form-group">
                <label>Usos máximos (vacío = ilimitado)</label>
                <input v-model.number="formData.usos_maximos" type="number" placeholder="Sin límite" />
              </div>
              
              <button class="btn-submit" @click="guardarPromocion">
                <i class="fa fa-save"></i>
                Guardar Promoción
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
import { getPromociones, createPromocion, updatePromocion } from '@/services/adminService';

const promociones = ref<any[]>([]);
const loading = ref(true);
const showModal = ref(false);
const selectedPromo = ref<any>(null);

const formData = reactive({
  nombre: '',
  descripcion: '',
  tipo: 'porcentaje',
  valor: 10,
  fecha_inicio: '',
  fecha_fin: '',
  usos_maximos: null as number | null,
});

const promocionesActivas = computed(() => promociones.value.filter(p => p.activa).length);
const totalUsos = computed(() => promociones.value.reduce((sum, p) => sum + (p.usos_actuales || 0), 0));

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

function formatDate(date: string): string {
  if (!date) return '--';
  return new Date(date).toLocaleDateString('es-MX', { day: 'numeric', month: 'short' });
}

function getUsagePercent(promo: any): number {
  if (!promo.usos_maximos) return 50; // Visual default for unlimited
  return Math.min(((promo.usos_actuales || 0) / promo.usos_maximos) * 100, 100);
}

function nuevaPromocion() { 
  selectedPromo.value = null;
  formData.nombre = '';
  formData.descripcion = '';
  formData.tipo = 'porcentaje';
  formData.valor = 10;
  formData.fecha_inicio = '';
  formData.fecha_fin = '';
  formData.usos_maximos = null;
  showModal.value = true;
}

function editarPromocion(p: any) { 
  selectedPromo.value = p;
  formData.nombre = p.nombre;
  formData.descripcion = p.descripcion || '';
  formData.tipo = p.descuento_porcentaje ? 'porcentaje' : 'fijo';
  formData.valor = p.descuento_porcentaje || p.descuento_fijo || 0;
  formData.fecha_inicio = p.fecha_inicio;
  formData.fecha_fin = p.fecha_fin;
  formData.usos_maximos = p.usos_maximos;
  showModal.value = true;
}

async function togglePromocion(p: any) { 
  try {
    await updatePromocion(p.id, { activa: !p.activa });
    p.activa = !p.activa;
  } catch (error) {
    console.error('Error actualizando promoción:', error);
    alert('Error al actualizar promoción');
  }
}

async function guardarPromocion() {
  try {
    const data: any = {
      nombre: formData.nombre,
      descripcion: formData.descripcion,
      fecha_inicio: formData.fecha_inicio,
      fecha_fin: formData.fecha_fin,
      usos_maximos: formData.usos_maximos,
    };
    
    if (formData.tipo === 'porcentaje') {
      data.descuento_porcentaje = formData.valor;
      data.descuento_fijo = null;
    } else {
      data.descuento_fijo = formData.valor;
      data.descuento_porcentaje = null;
    }
    
    if (selectedPromo.value) {
      await updatePromocion(selectedPromo.value.id, data);
    } else {
      await createPromocion(data);
    }
    closeModal();
    cargarPromociones();
  } catch (error) {
    console.error('Error guardando promoción:', error);
    alert('Error al guardar promoción');
  }
}

function closeModal() {
  showModal.value = false;
  selectedPromo.value = null;
}

onMounted(() => {
  cargarPromociones();
});
</script>

<style scoped>
.admin-view {
  padding: 16px;
  padding-bottom: 100px;
}

/* Header */
.view-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 16px;
  padding: 16px;
  background: linear-gradient(135deg, #fa709a 0%, #fee140 100%);
  border-radius: 16px;
  color: white;
}

.header-info {
  display: flex;
  align-items: center;
  gap: 12px;
}

.header-icon {
  width: 44px;
  height: 44px;
  background: rgba(255, 255, 255, 0.2);
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 20px;
}

.header-text h1 {
  margin: 0;
  font-size: 20px;
  font-weight: 700;
}

.header-subtitle {
  margin: 2px 0 0;
  font-size: 12px;
  opacity: 0.9;
}

.btn-action {
  width: 44px;
  height: 44px;
  background: rgba(255, 255, 255, 0.2);
  border: none;
  border-radius: 12px;
  color: white;
  font-size: 18px;
  cursor: pointer;
}

/* Stats */
.promo-stats {
  display: flex;
  gap: 12px;
  margin-bottom: 16px;
}

.promo-stat {
  flex: 1;
  display: flex;
  align-items: center;
  gap: 12px;
  background: white;
  padding: 14px 16px;
  border-radius: 14px;
  box-shadow: 0 2px 10px rgba(0, 0, 0, 0.06);
}

.stat-icon {
  width: 42px;
  height: 42px;
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 18px;
}

.stat-icon.green {
  background: rgba(56, 239, 125, 0.15);
  color: #11998e;
}

.stat-icon.orange {
  background: rgba(250, 112, 154, 0.15);
  color: #fa709a;
}

.stat-info {
  display: flex;
  flex-direction: column;
}

.stat-value {
  font-size: 22px;
  font-weight: 700;
  color: #1a1a2e;
}

.stat-label {
  font-size: 11px;
  color: #666;
  text-transform: uppercase;
}

/* Loading & Empty */
.loading-state,
.empty-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 60px 20px;
  color: #999;
}

.spinner {
  width: 40px;
  height: 40px;
  border: 3px solid #f0f0f0;
  border-top-color: #fa709a;
  border-radius: 50%;
  animation: spin 0.8s linear infinite;
  margin-bottom: 12px;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}

.empty-state i {
  font-size: 48px;
  margin-bottom: 12px;
  opacity: 0.5;
}

.btn-create {
  margin-top: 16px;
  padding: 12px 20px;
  background: linear-gradient(135deg, #fa709a 0%, #fee140 100%);
  color: white;
  border: none;
  border-radius: 10px;
  font-size: 14px;
  font-weight: 600;
  cursor: pointer;
  display: flex;
  align-items: center;
  gap: 8px;
}

/* Promociones List */
.promociones-list {
  display: flex;
  flex-direction: column;
  gap: 14px;
}

.promo-card {
  background: white;
  border-radius: 18px;
  overflow: hidden;
  box-shadow: 0 2px 12px rgba(0, 0, 0, 0.08);
  transition: opacity 0.2s;
}

.promo-card.inactiva {
  opacity: 0.6;
}

.promo-badge-container {
  padding: 16px 16px 0;
}

.promo-badge {
  display: inline-flex;
  flex-direction: column;
  align-items: center;
  padding: 10px 20px;
  border-radius: 12px;
  background: linear-gradient(135deg, #fa709a 0%, #fee140 100%);
  color: white;
}

.promo-badge.fijo {
  background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
}

.badge-value {
  font-size: 24px;
  font-weight: 800;
  line-height: 1;
}

.badge-label {
  font-size: 10px;
  font-weight: 600;
  opacity: 0.9;
  margin-top: 2px;
}

.promo-content {
  padding: 16px;
}

.promo-nombre {
  margin: 0 0 6px;
  font-size: 17px;
  font-weight: 600;
  color: #333;
}

.promo-descripcion {
  margin: 0 0 12px;
  font-size: 13px;
  color: #666;
  line-height: 1.4;
}

.promo-dates {
  display: flex;
  align-items: center;
  gap: 8px;
  font-size: 12px;
  color: #999;
  margin-bottom: 12px;
}

.promo-dates i {
  color: #fa709a;
}

.promo-usage {
  background: #f8f9fa;
  border-radius: 10px;
  padding: 10px 12px;
}

.usage-bar {
  height: 6px;
  background: #e0e0e0;
  border-radius: 3px;
  overflow: hidden;
  margin-bottom: 6px;
}

.usage-fill {
  height: 100%;
  background: linear-gradient(90deg, #fa709a, #fee140);
  border-radius: 3px;
  transition: width 0.3s ease;
}

.usage-text {
  display: flex;
  justify-content: space-between;
  font-size: 11px;
  color: #666;
}

.usage-text .unlimited {
  color: #11998e;
  font-weight: 500;
}

.promo-footer {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 12px 16px;
  background: #f8f9fa;
  border-top: 1px solid #f0f0f0;
}

.status-badge {
  padding: 5px 12px;
  border-radius: 8px;
  font-size: 11px;
  font-weight: 600;
}

.status-badge.activa {
  background: #e8f5e9;
  color: #2e7d32;
}

.status-badge.inactiva {
  background: #f5f5f5;
  color: #999;
}

.promo-actions {
  display: flex;
  gap: 6px;
}

.btn-icon-sm {
  width: 36px;
  height: 36px;
  border: none;
  border-radius: 10px;
  background: #f0f0f0;
  color: #666;
  font-size: 14px;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
}

.btn-icon-sm.danger {
  background: #ffebee;
  color: #c62828;
}

.btn-icon-sm.success {
  background: #e8f5e9;
  color: #2e7d32;
}

/* Modal */
.modal-overlay {
  position: fixed;
  inset: 0;
  background: rgba(0, 0, 0, 0.5);
  display: flex;
  align-items: flex-end;
  justify-content: center;
  z-index: 1000;
}

.modal-content {
  background: white;
  width: 100%;
  max-height: 90vh;
  border-radius: 24px 24px 0 0;
  overflow: hidden;
  animation: slideUp 0.3s ease;
}

@keyframes slideUp {
  from { transform: translateY(100%); }
  to { transform: translateY(0); }
}

.modal-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 20px;
  border-bottom: 1px solid #f0f0f0;
}

.modal-header h3 {
  margin: 0;
  font-size: 18px;
  font-weight: 600;
  color: #1a1a2e;
}

.modal-close {
  width: 36px;
  height: 36px;
  border: none;
  border-radius: 50%;
  background: #f0f0f0;
  color: #666;
  font-size: 16px;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
}

.modal-body {
  padding: 20px;
  overflow-y: auto;
  max-height: calc(90vh - 80px);
}

/* Form */
.form-container {
  display: flex;
  flex-direction: column;
  gap: 16px;
}

.form-group {
  display: flex;
  flex-direction: column;
  gap: 6px;
}

.form-group label {
  font-size: 13px;
  font-weight: 600;
  color: #333;
}

.form-group input,
.form-group textarea {
  padding: 12px;
  border: 1px solid #e0e0e0;
  border-radius: 10px;
  font-size: 14px;
  transition: border-color 0.2s;
}

.form-group input:focus,
.form-group textarea:focus {
  outline: none;
  border-color: #fa709a;
}

.form-row {
  display: flex;
  gap: 12px;
}

.form-row .form-group {
  flex: 1;
}

.tipo-selector {
  display: flex;
  gap: 10px;
}

.tipo-btn {
  flex: 1;
  padding: 12px;
  border: 2px solid #e0e0e0;
  border-radius: 10px;
  background: white;
  font-size: 13px;
  font-weight: 500;
  color: #666;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  transition: all 0.2s;
}

.tipo-btn.active {
  border-color: #fa709a;
  background: rgba(250, 112, 154, 0.1);
  color: #fa709a;
}

.input-prefix {
  display: flex;
  align-items: center;
  border: 1px solid #e0e0e0;
  border-radius: 10px;
  overflow: hidden;
}

.input-prefix span {
  padding: 12px;
  background: #f8f9fa;
  color: #666;
  font-weight: 600;
}

.input-prefix input {
  border: none;
  border-radius: 0;
  flex: 1;
}

.btn-submit {
  width: 100%;
  padding: 14px;
  background: linear-gradient(135deg, #fa709a 0%, #fee140 100%);
  color: white;
  border: none;
  border-radius: 12px;
  font-size: 15px;
  font-weight: 600;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  margin-top: 8px;
}
</style>
