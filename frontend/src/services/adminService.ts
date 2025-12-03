import api from './api';

// =====================================================
// DASHBOARD
// =====================================================

export const getDashboard = async () => {
  const response = await api.get('/admin/dashboard');
  return response.data;
};

// =====================================================
// CITAS
// =====================================================

export interface CitaFilters {
  estado?: string;
  empleado_id?: number;
  cliente_id?: number;
  desde?: string;
  hasta?: string;
  per_page?: number;
  page?: number;
}

export const getCitas = async (filters: CitaFilters = {}) => {
  const response = await api.get('/admin/citas', { params: filters });
  return response.data;
};

export const getCita = async (id: number) => {
  const response = await api.get(`/admin/citas/${id}`);
  return response.data;
};

export const createCita = async (data: any) => {
  const response = await api.post('/admin/citas', data);
  return response.data;
};

export const updateCita = async (id: number, data: any) => {
  const response = await api.put(`/admin/citas/${id}`, data);
  return response.data;
};

export const deleteCita = async (id: number) => {
  const response = await api.delete(`/admin/citas/${id}`);
  return response.data;
};

export const reagendarCitaAdmin = async (id: number, data: { fecha_hora: string; motivo?: string }) => {
  const response = await api.post(`/admin/citas/${id}/reagendar`, data);
  return response.data;
};

export const cancelarCitaAdmin = async (id: number, motivo?: string) => {
  const response = await api.post(`/admin/citas/${id}/cancelar`, { motivo });
  return response.data;
};

// =====================================================
// CLIENTES
// =====================================================

export interface ClienteFilters {
  buscar?: string;
  active?: boolean;
  per_page?: number;
  page?: number;
}

export const getClientes = async (filters: ClienteFilters = {}) => {
  const response = await api.get('/admin/clientes', { params: filters });
  return response.data;
};

export const getCliente = async (id: number) => {
  const response = await api.get(`/admin/clientes/${id}`);
  return response.data;
};

export const createCliente = async (data: any) => {
  const response = await api.post('/admin/clientes', data);
  return response.data;
};

export const updateCliente = async (id: number, data: any) => {
  const response = await api.put(`/admin/clientes/${id}`, data);
  return response.data;
};

export const deleteCliente = async (id: number) => {
  const response = await api.delete(`/admin/clientes/${id}`);
  return response.data;
};

export const getCitasCliente = async (id: number) => {
  const response = await api.get(`/admin/clientes/${id}/citas`);
  return response.data;
};

// =====================================================
// EMPLEADOS
// =====================================================

export interface EmpleadoFilters {
  active?: boolean;
}

export const getEmpleados = async (filters: EmpleadoFilters = {}) => {
  const response = await api.get('/admin/empleados', { params: filters });
  return response.data;
};

export const getEmpleado = async (id: number) => {
  const response = await api.get(`/admin/empleados/${id}`);
  return response.data;
};

export const createEmpleado = async (data: any) => {
  const response = await api.post('/admin/empleados', data);
  return response.data;
};

export const updateEmpleado = async (id: number, data: any) => {
  const response = await api.put(`/admin/empleados/${id}`, data);
  return response.data;
};

export const deleteEmpleado = async (id: number) => {
  const response = await api.delete(`/admin/empleados/${id}`);
  return response.data;
};

export const getEmpleadoHorarios = async (id: number) => {
  const response = await api.get(`/admin/empleados/${id}/horarios`);
  return response.data;
};

export const updateEmpleadoHorarios = async (id: number, data: any) => {
  const response = await api.put(`/admin/empleados/${id}/horarios`, data);
  return response.data;
};

export const getEmpleadoServicios = async (id: number) => {
  const response = await api.get(`/admin/empleados/${id}/servicios`);
  return response.data;
};

export const updateEmpleadoServicios = async (id: number, servicios: { id: number; precio_especial?: number | null }[]) => {
  const response = await api.put(`/admin/empleados/${id}/servicios`, { servicios });
  return response.data;
};

// =====================================================
// SERVICIOS
// =====================================================

export const getServicios = async () => {
  const response = await api.get('/admin/servicios');
  return response.data;
};

export const getServicio = async (id: number) => {
  const response = await api.get(`/admin/servicios/${id}`);
  return response.data;
};

export const createServicio = async (data: any) => {
  const response = await api.post('/admin/servicios', data);
  return response.data;
};

export const updateServicio = async (id: number, data: any) => {
  const response = await api.put(`/admin/servicios/${id}`, data);
  return response.data;
};

export const deleteServicio = async (id: number) => {
  const response = await api.delete(`/admin/servicios/${id}`);
  return response.data;
};

// =====================================================
// CATEGORÍAS
// =====================================================

export const getCategorias = async () => {
  const response = await api.get('/admin/categorias');
  return response.data;
};

export const createCategoria = async (data: any) => {
  const response = await api.post('/admin/categorias', data);
  return response.data;
};

export const updateCategoria = async (id: number, data: any) => {
  const response = await api.put(`/admin/categorias/${id}`, data);
  return response.data;
};

export const deleteCategoria = async (id: number) => {
  const response = await api.delete(`/admin/categorias/${id}`);
  return response.data;
};

// =====================================================
// PROMOCIONES
// =====================================================

export const getPromociones = async () => {
  const response = await api.get('/admin/promociones');
  return response.data;
};

export const getPromocion = async (id: number) => {
  const response = await api.get(`/admin/promociones/${id}`);
  return response.data;
};

export const createPromocion = async (data: any) => {
  const formData = new FormData();
  
  // Agregar todos los campos al FormData
  Object.keys(data).forEach(key => {
    if (key === 'imagen' && data[key] instanceof File) {
      formData.append('imagen', data[key]);
    } else if (data[key] !== null && data[key] !== undefined) {
      if (Array.isArray(data[key])) {
        data[key].forEach((item: any, index: number) => {
          formData.append(`${key}[${index}]`, item);
        });
      } else {
        formData.append(key, data[key]);
      }
    }
  });
  
  const response = await api.post('/admin/promociones', formData, {
    headers: {
      'Content-Type': 'multipart/form-data',
    },
  });
  return response.data;
};

export const updatePromocion = async (id: number, data: any) => {
  const formData = new FormData();
  
  // Agregar todos los campos al FormData
  Object.keys(data).forEach(key => {
    if (key === 'imagen' && data[key] instanceof File) {
      formData.append('imagen', data[key]);
    } else if (data[key] !== null && data[key] !== undefined) {
      if (key === 'servicios_aplicables' && Array.isArray(data[key])) {
        // Para servicios_aplicables, enviar cada elemento con índice
        data[key].forEach((item: any, index: number) => {
          formData.append(`servicios_aplicables[${index}]`, item.toString());
        });
      } else if (Array.isArray(data[key])) {
        data[key].forEach((item: any, index: number) => {
          formData.append(`${key}[${index}]`, item);
        });
      } else {
        formData.append(key, data[key].toString());
      }
    }
  });
  
  const response = await api.post(`/admin/promociones/${id}`, formData, {
    headers: {
      'Content-Type': 'multipart/form-data',
    },
    params: { _method: 'PUT' },
  });
  return response.data;
};

export const deletePromocion = async (id: number) => {
  const response = await api.delete(`/admin/promociones/${id}`);
  return response.data;
};

// =====================================================
// CONFIGURACIÓN
// =====================================================

export const getConfiguracion = async () => {
  const response = await api.get('/admin/configuracion');
  return response.data;
};

export const updateConfiguracion = async (configuraciones: { clave: string; valor: any }[]) => {
  const response = await api.put('/admin/configuracion', { configuraciones });
  return response.data;
};

// =====================================================
// REPORTES
// =====================================================

export const getReporteCitas = async (desde: string, hasta: string) => {
  const response = await api.get('/admin/reportes/citas', { params: { desde, hasta } });
  return response.data;
};

export const getReporteIngresos = async (desde: string, hasta: string) => {
  const response = await api.get('/admin/reportes/ingresos', { params: { desde, hasta } });
  return response.data;
};

export const getReporteEmpleados = async (desde: string, hasta: string) => {
  const response = await api.get('/admin/reportes/empleados', { params: { desde, hasta } });
  return response.data;
};

export const getReporteServicios = async (desde: string, hasta: string) => {
  const response = await api.get('/admin/reportes/servicios', { params: { desde, hasta } });
  return response.data;
};

export default {
  // Dashboard
  getDashboard,
  // Citas
  getCitas,
  getCita,
  createCita,
  updateCita,
  deleteCita,
  reagendarCitaAdmin,
  cancelarCitaAdmin,
  // Clientes
  getClientes,
  getCliente,
  createCliente,
  updateCliente,
  deleteCliente,
  getCitasCliente,
  // Empleados
  getEmpleados,
  getEmpleado,
  createEmpleado,
  updateEmpleado,
  deleteEmpleado,
  getEmpleadoHorarios,
  updateEmpleadoHorarios,
  getEmpleadoServicios,
  updateEmpleadoServicios,
  // Servicios
  getServicios,
  getServicio,
  createServicio,
  updateServicio,
  deleteServicio,
  // Categorías
  getCategorias,
  createCategoria,
  updateCategoria,
  deleteCategoria,
  // Promociones
  getPromociones,
  getPromocion,
  createPromocion,
  updatePromocion,
  deletePromocion,
  // Configuración
  getConfiguracion,
  updateConfiguracion,
  // Reportes
  getReporteCitas,
  getReporteIngresos,
  getReporteEmpleados,
  getReporteServicios,
};

