import api from './api';

// =====================================================
// CATEGORÍAS DE PRODUCTOS
// =====================================================

export interface CategoriaProductoFilters {
  active?: boolean;
  buscar?: string;
  per_page?: number;
  page?: number;
}

export const getCategoriasProductos = async (filters: CategoriaProductoFilters = {}) => {
  const response = await api.get('/admin/categorias-productos', { params: filters });
  return response.data;
};

export const getCategoriaProducto = async (id: number) => {
  const response = await api.get(`/admin/categorias-productos/${id}`);
  return response.data;
};

export const createCategoriaProducto = async (data: any) => {
  const response = await api.post('/admin/categorias-productos', data);
  return response.data;
};

export const updateCategoriaProducto = async (id: number, data: any) => {
  const response = await api.put(`/admin/categorias-productos/${id}`, data);
  return response.data;
};

export const deleteCategoriaProducto = async (id: number) => {
  const response = await api.delete(`/admin/categorias-productos/${id}`);
  return response.data;
};

// =====================================================
// PRODUCTOS
// =====================================================

export interface ProductoFilters {
  categoria_id?: number;
  active?: boolean;
  buscar?: string;
  per_page?: number;
  page?: number;
}

export const getProductos = async (filters: ProductoFilters = {}) => {
  const response = await api.get('/admin/productos', { params: filters });
  return response.data;
};

export const getProducto = async (id: number) => {
  const response = await api.get(`/admin/productos/${id}`);
  return response.data;
};

export const buscarProductos = async (termino: string) => {
  const response = await api.get('/admin/productos/buscar', { params: { q: termino } });
  return response.data;
};

export const createProducto = async (data: any) => {
  const formData = new FormData();
  
  Object.keys(data).forEach(key => {
    if (key === 'foto' && data[key] instanceof File) {
      formData.append('foto', data[key]);
    } else if (data[key] !== null && data[key] !== undefined) {
      formData.append(key, data[key].toString());
    }
  });
  
  const response = await api.post('/admin/productos', formData, {
    headers: {
      'Content-Type': 'multipart/form-data',
    },
  });
  return response.data;
};

export const updateProducto = async (id: number, data: any) => {
  const formData = new FormData();
  
  Object.keys(data).forEach(key => {
    if (key === 'foto' && data[key] instanceof File) {
      formData.append('foto', data[key]);
    } else if (data[key] !== null && data[key] !== undefined) {
      formData.append(key, data[key].toString());
    }
  });
  
  const response = await api.post(`/admin/productos/${id}`, formData, {
    headers: {
      'Content-Type': 'multipart/form-data',
    },
    params: { _method: 'PUT' },
  });
  return response.data;
};

export const deleteProducto = async (id: number) => {
  const response = await api.delete(`/admin/productos/${id}`);
  return response.data;
};

// =====================================================
// INVENTARIO
// =====================================================

export interface MovimientoFilters {
  producto_id?: number;
  tipo?: 'entrada_manual' | 'salida_manual' | 'venta';
  fecha_inicio?: string;
  fecha_fin?: string;
  user_id?: number;
  per_page?: number;
  page?: number;
}

export const getMovimientos = async (filters: MovimientoFilters = {}) => {
  const response = await api.get('/admin/inventario/movimientos', { params: filters });
  return response.data;
};

export const getKardex = async (productoId: number) => {
  const response = await api.get(`/admin/inventario/kardex/${productoId}`);
  return response.data;
};

export const registrarEntrada = async (data: {
  producto_id: number;
  cantidad: number;
  motivo?: string;
  notas?: string;
}) => {
  const response = await api.post('/admin/inventario/entrada', data);
  return response.data;
};

export const registrarSalida = async (data: {
  producto_id: number;
  cantidad: number;
  motivo?: string;
  notas?: string;
}) => {
  const response = await api.post('/admin/inventario/salida', data);
  return response.data;
};

export const ajustarInventario = async (data: {
  producto_id: number;
  nuevo_stock: number;
  motivo: string;
  notas?: string;
}) => {
  const response = await api.post('/admin/inventario/ajuste', data);
  return response.data;
};

export const getProductosBajoStock = async () => {
  const response = await api.get('/admin/inventario/bajo-stock');
  return response.data;
};

// =====================================================
// VENTAS
// =====================================================

export interface VentaFilters {
  cliente_id?: number;
  estado?: 'pendiente_pago' | 'parcial' | 'completada' | 'cancelada';
  fecha_inicio?: string;
  fecha_fin?: string;
  per_page?: number;
  page?: number;
}

export const getVentas = async (filters: VentaFilters = {}) => {
  const response = await api.get('/admin/ventas', { params: filters });
  return response.data;
};

export const getVenta = async (id: number) => {
  const response = await api.get(`/admin/ventas/${id}`);
  return response.data;
};

export const createVenta = async (data: {
  cliente_id?: number;
  detalles: Array<{
    tipo: 'producto' | 'servicio';
    producto_id?: number;
    servicio_id?: number;
    cantidad: number;
    precio_unitario?: number;
  }>;
  notas?: string;
}) => {
  const response = await api.post('/admin/ventas', data);
  return response.data;
};

export const calcularTotales = async (detalles: Array<{
  tipo: 'producto' | 'servicio';
  producto_id?: number;
  servicio_id?: number;
  cantidad: number;
}>) => {
  const response = await api.post('/admin/ventas/calcular-totales', { detalles });
  return response.data;
};

export const buscarProductosVenta = async (termino: string) => {
  const response = await api.get('/admin/ventas/productos/buscar', { params: { q: termino } });
  return response.data;
};

export const updateVenta = async (id: number, data: any) => {
  const response = await api.put(`/admin/ventas/${id}`, data);
  return response.data;
};

export const cancelarVenta = async (id: number, motivo?: string) => {
  const response = await api.post(`/admin/ventas/${id}/cancelar`, { motivo });
  return response.data;
};

// =====================================================
// VENTAS DESDE CITAS
// =====================================================

export const crearVentaDesdeCita = async (citaId: number) => {
  const response = await api.post(`/admin/ventas-citas/crear-desde-cita/${citaId}`);
  return response.data;
};

export const agregarProductosVentaCita = async (ventaId: number, productos: Array<{
  producto_id: number;
  cantidad: number;
}>) => {
  const response = await api.post(`/admin/ventas-citas/${ventaId}/productos`, { productos });
  return response.data;
};

export const finalizarVentaCita = async (ventaId: number) => {
  const response = await api.post(`/admin/ventas-citas/${ventaId}/finalizar`);
  return response.data;
};

// =====================================================
// MÉTODOS DE PAGO
// =====================================================

export const getMetodosPago = async () => {
  const response = await api.get('/admin/metodos-pago');
  return response.data;
};

export const createMetodoPago = async (data: {
  nombre: string;
  codigo: string;
  es_efectivo?: boolean;
  activo?: boolean;
  orden?: number;
}) => {
  const response = await api.post('/admin/metodos-pago', data);
  return response.data;
};

export const updateMetodoPago = async (id: number, data: any) => {
  const response = await api.put(`/admin/metodos-pago/${id}`, data);
  return response.data;
};

export const deleteMetodoPago = async (id: number) => {
  const response = await api.delete(`/admin/metodos-pago/${id}`);
  return response.data;
};

// =====================================================
// PAGOS
// =====================================================

export const registrarPago = async (data: {
  venta_id: number;
  metodo_pago_id: number;
  monto: number;
  monto_recibido?: number;
  notas?: string;
}) => {
  const response = await api.post('/admin/pagos/registrar', data);
  return response.data;
};

export const getPagosVenta = async (ventaId: number) => {
  const response = await api.get(`/admin/pagos/venta/${ventaId}`);
  return response.data;
};

export const reembolsarPago = async (pagoId: number, motivo?: string) => {
  const response = await api.post(`/admin/pagos/${pagoId}/reembolsar`, { motivo });
  return response.data;
};

// =====================================================
// REGLAS DE ANTICIPO
// =====================================================

export const getReglasAnticipo = async () => {
  const response = await api.get('/admin/anticipos/reglas');
  return response.data;
};

export const getReglaAnticipo = async (id: number) => {
  const response = await api.get(`/admin/anticipos/reglas/${id}`);
  return response.data;
};

export const createReglaAnticipo = async (data: any) => {
  const response = await api.post('/admin/anticipos/reglas', data);
  return response.data;
};

export const updateReglaAnticipo = async (id: number, data: any) => {
  const response = await api.put(`/admin/anticipos/reglas/${id}`, data);
  return response.data;
};

export const deleteReglaAnticipo = async (id: number) => {
  const response = await api.delete(`/admin/anticipos/reglas/${id}`);
  return response.data;
};

export const calcularAnticipo = async (data: {
  fecha_venta: string;
  total: number;
  servicios: number[];
}) => {
  const response = await api.post('/admin/anticipos/calcular', data);
  return response.data;
};

export const evaluarAnticipo = async (data: {
  fecha_venta: string;
  total: number;
  servicios: number[];
}) => {
  const response = await api.post('/admin/anticipos/evaluar', data);
  return response.data;
};

export default {
  // Categorías de Productos
  getCategoriasProductos,
  getCategoriaProducto,
  createCategoriaProducto,
  updateCategoriaProducto,
  deleteCategoriaProducto,
  // Productos
  getProductos,
  getProducto,
  buscarProductos,
  createProducto,
  updateProducto,
  deleteProducto,
  // Inventario
  getMovimientos,
  getKardex,
  registrarEntrada,
  registrarSalida,
  ajustarInventario,
  getProductosBajoStock,
  // Ventas
  getVentas,
  getVenta,
  createVenta,
  calcularTotales,
  buscarProductosVenta,
  updateVenta,
  cancelarVenta,
  // Ventas desde citas
  crearVentaDesdeCita,
  agregarProductosVentaCita,
  finalizarVentaCita,
  // Métodos de pago
  getMetodosPago,
  createMetodoPago,
  updateMetodoPago,
  deleteMetodoPago,
  // Pagos
  registrarPago,
  getPagosVenta,
  reembolsarPago,
  // Reglas de anticipo
  getReglasAnticipo,
  getReglaAnticipo,
  createReglaAnticipo,
  updateReglaAnticipo,
  deleteReglaAnticipo,
  calcularAnticipo,
  evaluarAnticipo,
};


