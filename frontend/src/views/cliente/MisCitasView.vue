<template>
  <div class="mis-citas">
    <!-- Header -->
    <header class="page-header">
      <h1>Mis Citas</h1>
      <router-link to="/agendar" class="btn-nueva-cita">
        <span>+</span> Nueva Cita
      </router-link>
    </header>

    <!-- Tabs de filtro -->
    <div class="filter-tabs">
      <button
        v-for="tab in tabs"
        :key="tab.value"
        :class="['tab-btn', { active: filtroActual === tab.value }]"
        @click="filtroActual = tab.value"
      >
        {{ tab.label }}
        <span v-if="tab.count" class="tab-count">{{ tab.count }}</span>
      </button>
    </div>

    <!-- Loading -->
    <div v-if="loading" class="loading-state">
      <div class="spinner-large"></div>
      <p>Cargando tus citas...</p>
    </div>

    <!-- Lista vacía -->
    <div v-else-if="citasFiltradas.length === 0" class="empty-state">
      <div class="empty-icon">📅</div>
      <h3>No tienes citas {{ filtroActual !== 'todas' ? filtroActual + 's' : '' }}</h3>
      <p>Agenda tu próxima cita y disfruta de nuestros servicios</p>
      <router-link to="/agendar" class="btn-primary">
        Agendar cita
      </router-link>
    </div>

    <!-- Lista de citas -->
    <div v-else class="citas-list">
      <TransitionGroup name="list">
        <div
          v-for="cita in citasFiltradas"
          :key="cita.id"
          class="cita-card"
          @click="verDetalle(cita)"
        >
          <div class="cita-fecha">
            <span class="dia">{{ formatDia(cita.fecha_hora) }}</span>
            <span class="mes">{{ formatMes(cita.fecha_hora) }}</span>
          </div>

          <div class="cita-info">
            <div class="cita-header">
              <span :class="['estado-badge', cita.estado]">
                {{ estadoTexto(cita.estado) }}
              </span>
              <span class="cita-hora">{{ formatHora(cita.fecha_hora) }}</span>
            </div>

            <h3 class="servicios-nombre">
              {{ cita.servicios?.map((s: any) => s.nombre).join(', ') }}
            </h3>

            <div class="cita-meta">
              <span class="empleado">
                👩‍💼 {{ cita.empleado?.nombre || 'Sin asignar' }}
              </span>
              <span class="duracion">
                ⏱️ {{ cita.duracion_total }} min
              </span>
            </div>

            <div class="cita-precio">
              ${{ formatPrecio(cita.precio_total) }}
            </div>
          </div>

          <div class="cita-actions">
            <button
              v-if="puedeCancelar(cita)"
              class="btn-icon btn-cancel"
              @click.stop="confirmarCancelacion(cita)"
              title="Cancelar"
            >
              ✕
            </button>
            <button
              v-if="puedeModificar(cita)"
              class="btn-icon btn-edit"
              @click.stop="modificarCita(cita)"
              title="Modificar"
            >
              ✏️
            </button>
          </div>
        </div>
      </TransitionGroup>
    </div>

    <!-- Modal de detalle -->
    <Teleport to="body">
      <Transition name="modal">
        <div v-if="citaSeleccionada" class="modal-overlay" @click="citaSeleccionada = null">
          <div class="modal-content" @click.stop>
            <button class="modal-close" @click="citaSeleccionada = null">✕</button>
            
            <div class="modal-header">
              <span :class="['estado-badge large', citaSeleccionada.estado]">
                {{ estadoTexto(citaSeleccionada.estado) }}
              </span>
            </div>

            <div class="modal-body">
              <div class="detalle-fecha">
                <span class="fecha-icon">📅</span>
                <div>
                  <p class="fecha-principal">
                    {{ formatFechaCompleta(citaSeleccionada.fecha_hora) }}
                  </p>
                  <p class="hora-principal">{{ formatHora(citaSeleccionada.fecha_hora) }}</p>
                </div>
              </div>

              <div class="detalle-seccion">
                <h4>Servicios</h4>
                <ul class="servicios-lista">
                  <li v-for="servicio in citaSeleccionada.servicios" :key="servicio.id">
                    <span class="servicio-nombre">{{ servicio.nombre }}</span>
                    <span class="servicio-precio">${{ formatPrecio(servicio.pivot?.precio_aplicado || servicio.precio) }}</span>
                  </li>
                </ul>
              </div>

              <div class="detalle-seccion">
                <h4>Atendido por</h4>
                <p class="empleado-nombre">{{ citaSeleccionada.empleado?.nombre }}</p>
              </div>

              <div class="detalle-seccion">
                <h4>Duración estimada</h4>
                <p>{{ citaSeleccionada.duracion_total }} minutos</p>
              </div>

              <div class="detalle-total">
                <span>Total</span>
                <span class="total-precio">${{ formatPrecio(citaSeleccionada.precio_total) }}</span>
              </div>

              <div v-if="citaSeleccionada.notas" class="detalle-seccion">
                <h4>Notas</h4>
                <p>{{ citaSeleccionada.notas }}</p>
              </div>
            </div>

            <div class="modal-actions" v-if="puedeCancelar(citaSeleccionada) || puedeModificar(citaSeleccionada)">
              <button
                v-if="puedeModificar(citaSeleccionada)"
                class="btn-secondary"
                @click="modificarCita(citaSeleccionada)"
              >
                Modificar cita
              </button>
              <button
                v-if="puedeCancelar(citaSeleccionada)"
                class="btn-danger"
                @click="confirmarCancelacion(citaSeleccionada)"
              >
                Cancelar cita
              </button>
            </div>
          </div>
        </div>
      </Transition>
    </Teleport>

    <!-- Modal de confirmación de cancelación -->
    <Teleport to="body">
      <Transition name="modal">
        <div v-if="citaACancelar" class="modal-overlay" @click="citaACancelar = null">
          <div class="modal-content modal-small" @click.stop>
            <div class="confirm-icon">⚠️</div>
            <h3>¿Cancelar esta cita?</h3>
            <p>
              Cita del {{ formatFechaCompleta(citaACancelar.fecha_hora) }} 
              a las {{ formatHora(citaACancelar.fecha_hora) }}
            </p>
            <p class="confirm-warning">Esta acción no se puede deshacer.</p>
            
            <div class="modal-actions">
              <button class="btn-secondary" @click="citaACancelar = null">
                No, mantener
              </button>
              <button class="btn-danger" @click="cancelarCita" :disabled="cancelando">
                {{ cancelando ? 'Cancelando...' : 'Sí, cancelar' }}
              </button>
            </div>
          </div>
        </div>
      </Transition>
    </Teleport>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import { citaService } from '@/services/citaService';
import type { Cita } from '@/types';

const router = useRouter();

// Estado
const citas = ref<Cita[]>([]);
const loading = ref(true);
const filtroActual = ref('proximas');
const citaSeleccionada = ref<Cita | null>(null);
const citaACancelar = ref<Cita | null>(null);
const cancelando = ref(false);

// Tabs
const tabs = computed(() => [
  { label: 'Próximas', value: 'proximas', count: citasProximas.value.length },
  { label: 'Pasadas', value: 'pasadas', count: citasPasadas.value.length },
  { label: 'Canceladas', value: 'canceladas', count: citasCanceladas.value.length },
  { label: 'Todas', value: 'todas', count: citas.value.length },
]);

// Computed para filtrar
const citasProximas = computed(() => 
  citas.value.filter(c => 
    new Date(c.fecha_hora) >= new Date() && 
    !['cancelada', 'no_show'].includes(c.estado)
  )
);

const citasPasadas = computed(() => 
  citas.value.filter(c => 
    new Date(c.fecha_hora) < new Date() || 
    c.estado === 'completada'
  )
);

const citasCanceladas = computed(() => 
  citas.value.filter(c => ['cancelada', 'no_show'].includes(c.estado))
);

const citasFiltradas = computed(() => {
  switch (filtroActual.value) {
    case 'proximas': return citasProximas.value;
    case 'pasadas': return citasPasadas.value;
    case 'canceladas': return citasCanceladas.value;
    default: return citas.value;
  }
});

// Métodos
async function cargarCitas() {
  loading.value = true;
  try {
    const response = await citaService.misCitas();
    citas.value = response.citas;
  } catch (error) {
    console.error('Error cargando citas:', error);
  } finally {
    loading.value = false;
  }
}

function verDetalle(cita: Cita) {
  citaSeleccionada.value = cita;
}

function modificarCita(cita: Cita) {
  router.push(`/agendar?modificar=${cita.id}`);
}

function confirmarCancelacion(cita: Cita) {
  citaSeleccionada.value = null;
  citaACancelar.value = cita;
}

async function cancelarCita() {
  if (!citaACancelar.value) return;
  
  cancelando.value = true;
  try {
    await citaService.cancelar(citaACancelar.value.id);
    
    // Actualizar estado local
    const index = citas.value.findIndex(c => c.id === citaACancelar.value?.id);
    if (index !== -1) {
      citas.value[index].estado = 'cancelada';
    }
    
    citaACancelar.value = null;
  } catch (error) {
    console.error('Error cancelando cita:', error);
  } finally {
    cancelando.value = false;
  }
}

function puedeCancelar(cita: Cita): boolean {
  if (['cancelada', 'completada', 'no_show', 'en_proceso'].includes(cita.estado)) {
    return false;
  }
  // Solo puede cancelar si faltan más de 2 horas
  const horasRestantes = (new Date(cita.fecha_hora).getTime() - Date.now()) / (1000 * 60 * 60);
  return horasRestantes > 2;
}

function puedeModificar(cita: Cita): boolean {
  if (['cancelada', 'completada', 'no_show', 'en_proceso'].includes(cita.estado)) {
    return false;
  }
  const horasRestantes = (new Date(cita.fecha_hora).getTime() - Date.now()) / (1000 * 60 * 60);
  return horasRestantes > 24;
}

function estadoTexto(estado: string): string {
  const estados: Record<string, string> = {
    pendiente: 'Pendiente',
    confirmada: 'Confirmada',
    en_proceso: 'En proceso',
    completada: 'Completada',
    cancelada: 'Cancelada',
    no_show: 'No asistió',
  };
  return estados[estado] || estado;
}

function formatDia(fecha: string): string {
  return new Date(fecha).getDate().toString();
}

function formatMes(fecha: string): string {
  return new Date(fecha).toLocaleDateString('es-MX', { month: 'short' }).toUpperCase();
}

function formatHora(fecha: string): string {
  return new Date(fecha).toLocaleTimeString('es-MX', { hour: '2-digit', minute: '2-digit' });
}

function formatFechaCompleta(fecha: string): string {
  return new Date(fecha).toLocaleDateString('es-MX', {
    weekday: 'long',
    day: 'numeric',
    month: 'long',
  });
}

function formatPrecio(precio: number): string {
  return precio?.toFixed(2) || '0.00';
}

onMounted(() => {
  cargarCitas();
});
</script>

<style scoped>
.mis-citas {
  min-height: 100vh;
  background: #f8f9fa;
  padding-bottom: 80px;
}

.page-header {
  background: linear-gradient(135deg, #ec407a 0%, #d81b60 100%);
  color: white;
  padding: 24px 20px;
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.page-header h1 {
  margin: 0;
  font-size: 24px;
  font-weight: 600;
}

.btn-nueva-cita {
  display: flex;
  align-items: center;
  gap: 6px;
  background: white;
  color: #d81b60;
  padding: 10px 16px;
  border-radius: 20px;
  text-decoration: none;
  font-weight: 600;
  font-size: 14px;
}

.filter-tabs {
  display: flex;
  background: white;
  padding: 8px;
  gap: 4px;
  overflow-x: auto;
  border-bottom: 1px solid #eee;
}

.tab-btn {
  flex: 1;
  min-width: fit-content;
  padding: 10px 16px;
  background: transparent;
  border: none;
  border-radius: 8px;
  font-size: 14px;
  color: #666;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 6px;
  transition: all 0.2s;
}

.tab-btn.active {
  background: #fce4ec;
  color: #d81b60;
  font-weight: 600;
}

.tab-count {
  background: #e0e0e0;
  padding: 2px 8px;
  border-radius: 10px;
  font-size: 12px;
}

.tab-btn.active .tab-count {
  background: #d81b60;
  color: white;
}

.loading-state,
.empty-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 60px 20px;
  text-align: center;
}

.spinner-large {
  width: 48px;
  height: 48px;
  border: 3px solid #fce4ec;
  border-top-color: #d81b60;
  border-radius: 50%;
  animation: spin 0.8s linear infinite;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}

.empty-icon {
  font-size: 64px;
  margin-bottom: 16px;
}

.empty-state h3 {
  margin: 0 0 8px;
  color: #333;
}

.empty-state p {
  color: #666;
  margin: 0 0 24px;
}

.citas-list {
  padding: 16px;
}

.cita-card {
  background: white;
  border-radius: 16px;
  padding: 16px;
  margin-bottom: 12px;
  display: flex;
  gap: 16px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
  cursor: pointer;
  transition: transform 0.2s, box-shadow 0.2s;
}

.cita-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 16px rgba(0, 0, 0, 0.1);
}

.cita-fecha {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  background: #fce4ec;
  border-radius: 12px;
  padding: 12px 16px;
  min-width: 60px;
}

.cita-fecha .dia {
  font-size: 24px;
  font-weight: 700;
  color: #d81b60;
}

.cita-fecha .mes {
  font-size: 12px;
  color: #ad1457;
  font-weight: 600;
}

.cita-info {
  flex: 1;
  min-width: 0;
}

.cita-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 8px;
}

.estado-badge {
  padding: 4px 10px;
  border-radius: 12px;
  font-size: 11px;
  font-weight: 600;
  text-transform: uppercase;
}

.estado-badge.pendiente { background: #fff3e0; color: #e65100; }
.estado-badge.confirmada { background: #e8f5e9; color: #2e7d32; }
.estado-badge.en_proceso { background: #e3f2fd; color: #1565c0; }
.estado-badge.completada { background: #f3e5f5; color: #7b1fa2; }
.estado-badge.cancelada { background: #ffebee; color: #c62828; }
.estado-badge.no_show { background: #fafafa; color: #616161; }

.estado-badge.large {
  padding: 8px 16px;
  font-size: 13px;
}

.cita-hora {
  color: #666;
  font-size: 14px;
}

.servicios-nombre {
  margin: 0 0 8px;
  font-size: 16px;
  font-weight: 600;
  color: #333;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.cita-meta {
  display: flex;
  gap: 16px;
  font-size: 13px;
  color: #666;
  margin-bottom: 8px;
}

.cita-precio {
  font-size: 18px;
  font-weight: 700;
  color: #d81b60;
}

.cita-actions {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.btn-icon {
  width: 36px;
  height: 36px;
  border-radius: 50%;
  border: none;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 14px;
  transition: transform 0.2s;
}

.btn-icon:hover {
  transform: scale(1.1);
}

.btn-cancel {
  background: #ffebee;
  color: #c62828;
}

.btn-edit {
  background: #e3f2fd;
  color: #1565c0;
}

/* Modal styles */
.modal-overlay {
  position: fixed;
  inset: 0;
  background: rgba(0, 0, 0, 0.5);
  display: flex;
  align-items: flex-end;
  justify-content: center;
  z-index: 1000;
  padding: 20px;
}

.modal-content {
  background: white;
  border-radius: 24px 24px 0 0;
  width: 100%;
  max-width: 500px;
  max-height: 90vh;
  overflow-y: auto;
  padding: 24px;
  position: relative;
}

.modal-small {
  border-radius: 24px;
  text-align: center;
  padding: 32px 24px;
}

.modal-close {
  position: absolute;
  top: 16px;
  right: 16px;
  background: #f5f5f5;
  border: none;
  width: 32px;
  height: 32px;
  border-radius: 50%;
  cursor: pointer;
  font-size: 16px;
}

.modal-header {
  margin-bottom: 24px;
}

.confirm-icon {
  font-size: 48px;
  margin-bottom: 16px;
}

.confirm-warning {
  color: #c62828;
  font-size: 14px;
}

.detalle-fecha {
  display: flex;
  gap: 16px;
  align-items: center;
  background: #fce4ec;
  padding: 16px;
  border-radius: 12px;
  margin-bottom: 24px;
}

.fecha-icon {
  font-size: 32px;
}

.fecha-principal {
  margin: 0;
  font-weight: 600;
  color: #333;
  text-transform: capitalize;
}

.hora-principal {
  margin: 4px 0 0;
  font-size: 20px;
  font-weight: 700;
  color: #d81b60;
}

.detalle-seccion {
  margin-bottom: 20px;
}

.detalle-seccion h4 {
  margin: 0 0 8px;
  font-size: 12px;
  color: #999;
  text-transform: uppercase;
}

.servicios-lista {
  list-style: none;
  padding: 0;
  margin: 0;
}

.servicios-lista li {
  display: flex;
  justify-content: space-between;
  padding: 8px 0;
  border-bottom: 1px solid #f0f0f0;
}

.servicio-nombre {
  color: #333;
}

.servicio-precio {
  color: #666;
}

.empleado-nombre {
  margin: 0;
  font-weight: 500;
  color: #333;
}

.detalle-total {
  display: flex;
  justify-content: space-between;
  padding: 16px 0;
  border-top: 2px solid #eee;
  margin-bottom: 24px;
  font-size: 18px;
  font-weight: 600;
}

.total-precio {
  color: #d81b60;
}

.modal-actions {
  display: flex;
  gap: 12px;
}

.btn-primary {
  flex: 1;
  padding: 14px 24px;
  background: linear-gradient(135deg, #ec407a 0%, #d81b60 100%);
  color: white;
  border: none;
  border-radius: 12px;
  font-size: 16px;
  font-weight: 600;
  cursor: pointer;
  text-decoration: none;
  text-align: center;
}

.btn-secondary {
  flex: 1;
  padding: 14px 24px;
  background: #f5f5f5;
  color: #333;
  border: none;
  border-radius: 12px;
  font-size: 16px;
  font-weight: 600;
  cursor: pointer;
}

.btn-danger {
  flex: 1;
  padding: 14px 24px;
  background: #ffebee;
  color: #c62828;
  border: none;
  border-radius: 12px;
  font-size: 16px;
  font-weight: 600;
  cursor: pointer;
}

/* Animations */
.list-enter-active,
.list-leave-active {
  transition: all 0.3s ease;
}

.list-enter-from,
.list-leave-to {
  opacity: 0;
  transform: translateX(-30px);
}

.modal-enter-active,
.modal-leave-active {
  transition: opacity 0.3s ease;
}

.modal-enter-active .modal-content,
.modal-leave-active .modal-content {
  transition: transform 0.3s ease;
}

.modal-enter-from,
.modal-leave-to {
  opacity: 0;
}

.modal-enter-from .modal-content,
.modal-leave-to .modal-content {
  transform: translateY(100%);
}
</style>

