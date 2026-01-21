<script setup lang="ts">
import { ref, onMounted, watch } from 'vue';
import adminService from '@/services/adminService';

interface Cliente {
  id: number;
  nombre: string;
  telefono?: string;
}

const props = defineProps<{
  modelValue: number | null;
}>();

const emit = defineEmits(['update:modelValue', 'change']);

const clientes = ref<Cliente[]>([]);
const loading = ref(false);
const searchTerm = ref('');
const showDropdown = ref(false);
const selectedCliente = ref<Cliente | null>(null);

const fetchClientes = async () => {
  loading.ref = true;
  try {
    const response = await adminService.getClientes();
    clientes.value = response.data || [];
    
    // Si hay un modelValue, buscar el objeto cliente correspondiente
    if (props.modelValue) {
      selectedCliente.value = clientes.value.find(c => c.id === props.modelValue) || null;
    } else if (clientes.value.length > 0) {
      // Por defecto seleccionar el primero (Público General) si no hay valor
      const publicoGeneral = clientes.value.find(c => c.nombre.includes('Público General')) || clientes.value[0];
      selectCliente(publicoGeneral);
    }
  } catch (error) {
    console.error('Error al obtener clientes:', error);
  } finally {
    loading.value = false;
  }
};

const selectCliente = (cliente: Cliente) => {
  selectedCliente.value = cliente;
  searchTerm.value = cliente.nombre;
  showDropdown.value = false;
  emit('update:modelValue', cliente.id);
  emit('change', cliente);
};

const clearSelection = () => {
  selectedCliente.value = null;
  searchTerm.value = '';
  emit('update:modelValue', null);
  emit('change', null);
};

onMounted(fetchClientes);

watch(() => props.modelValue, (newVal) => {
  if (newVal && (!selectedCliente.value || selectedCliente.value.id !== newVal)) {
    selectedCliente.value = clientes.value.find(c => c.id === newVal) || null;
    if (selectedCliente.value) searchTerm.value = selectedCliente.value.nombre;
  }
});

const filteredClientes = ref<Cliente[]>([]);
watch(searchTerm, (newVal) => {
  if (!newVal) {
    filteredClientes.value = clientes.value;
    return;
  }
  const term = newVal.toLowerCase();
  filteredClientes.value = clientes.value.filter(c => 
    c.nombre.toLowerCase().includes(term) || 
    (c.telefono && c.telefono.includes(term))
  );
});

</script>

<template>
  <div class="cliente-selector">
    <label class="label">Cliente</label>
    <div class="input-container">
      <div class="search-box">
        <i class="fas fa-user search-icon"></i>
        <input
          type="text"
          v-model="searchTerm"
          placeholder="Buscar cliente por nombre o teléfono..."
          @focus="showDropdown = true"
          class="search-input"
        />
        <button v-if="selectedCliente" @click="clearSelection" class="clear-btn">
          <i class="fas fa-times"></i>
        </button>
      </div>

      <div v-if="showDropdown" class="dropdown">
        <div v-if="loading" class="dropdown-item loading">Cargando...</div>
        <template v-else>
          <div
            v-for="cliente in filteredClientes"
            :key="cliente.id"
            class="dropdown-item"
            @click="selectCliente(cliente)"
          >
            <div class="cliente-info">
              <span class="cliente-nombre">{{ cliente.nombre }}</span>
              <span v-if="cliente.telefono" class="cliente-telefono">{{ cliente.telefono }}</span>
            </div>
          </div>
          <div v-if="filteredClientes.length === 0" class="dropdown-item no-results">
            No se encontraron clientes
          </div>
        </template>
      </div>
    </div>
  </div>
</template>

<style scoped>
.cliente-selector {
  display: flex;
  flex-direction: column;
  gap: 8px;
  position: relative;
  width: 100%;
}

.label {
  font-size: 0.9rem;
  font-weight: 600;
  color: #4b5563;
}

.input-container {
  position: relative;
}

.search-box {
  display: flex;
  align-items: center;
  background: white;
  border: 1px solid #e5e7eb;
  border-radius: 12px;
  padding: 0 12px;
  transition: all 0.3s ease;
}

.search-box:focus-within {
  border-color: #3b82f6;
  box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

.search-icon {
  color: #9ca3af;
  margin-right: 10px;
}

.search-input {
  flex: 1;
  border: none;
  padding: 12px 0;
  font-size: 0.95rem;
  outline: none;
  background: transparent;
}

.clear-btn {
  background: none;
  border: none;
  color: #9ca3af;
  cursor: pointer;
  padding: 4px;
}

.clear-btn:hover {
  color: #ef4444;
}

.dropdown {
  position: absolute;
  top: calc(100% + 4px);
  left: 0;
  right: 0;
  background: white;
  border: 1px solid #e5e7eb;
  border-radius: 12px;
  box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
  z-index: 50;
  max-height: 250px;
  overflow-y: auto;
}

.dropdown-item {
  padding: 12px;
  cursor: pointer;
  transition: background 0.2s;
  display: flex;
  flex-direction: column;
}

.dropdown-item:hover {
  background: #f9fafb;
}

.cliente-nombre {
  font-weight: 500;
  color: #111827;
}

.cliente-telefono {
  font-size: 0.8rem;
  color: #6b7280;
}

.loading, .no-results {
  text-align: center;
  color: #6b7280;
  cursor: default;
}
</style>
