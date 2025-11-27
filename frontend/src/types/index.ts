// =====================================================
// Tipos de Entidades del Sistema
// =====================================================

// Usuario (empleado/admin)
export interface User {
  id: number
  role_id: number
  nombre: string
  telefono?: string
  email: string
  active: boolean
  role?: Role
  empleado?: Empleado
}

// Rol
export interface Role {
  id: number
  nombre: 'admin' | 'empleado'
}

// Empleado
export interface Empleado {
  id: number
  user_id: number
  foto?: string
  bio?: string
  especialidades?: string
  active: boolean
  user?: User
  servicios?: Servicio[]
  horarios?: HorarioEmpleado[]
}

// Cliente
export interface Cliente {
  id: number
  nombre: string
  telefono: string
  email?: string
  fecha_nacimiento?: string
  notas?: string
  preferencia_contacto: 'whatsapp' | 'sms' | 'email' | 'llamada'
  notificaciones_push: boolean
  notificaciones_email: boolean
  notificaciones_whatsapp: boolean
  active: boolean
}

// Categoría
export interface Categoria {
  id: number
  nombre: string
  descripcion?: string
  active: boolean
  servicios?: Servicio[]
}

// Servicio
export interface Servicio {
  id: number
  categoria_id: number
  nombre: string
  descripcion?: string
  precio: number
  duracion: number // en minutos
  active: boolean
  categoria?: Categoria
  empleados?: Empleado[]
}

// Cita
export interface Cita {
  id: number
  cliente_id: number
  empleado_id: number
  servicio_id: number
  promocion_id?: number
  fecha_hora: string // ISO datetime
  duracion_total: number
  estado: EstadoCita
  precio_final: number
  metodo_pago: MetodoPago
  notas?: string
  active: boolean
  cliente?: Cliente
  empleado?: Empleado
  servicio?: Servicio
  promocion?: Promocion
  servicios?: CitaServicio[]
  fotos?: FotoCita[]
}

export type EstadoCita = 'pendiente' | 'confirmada' | 'en_proceso' | 'completada' | 'cancelada' | 'no_show'
export type MetodoPago = 'efectivo' | 'tarjeta' | 'transferencia' | 'pendiente'

// Cita-Servicio (pivot)
export interface CitaServicio {
  id: number
  cita_id: number
  servicio_id: number
  precio_aplicado: number
  orden: number
  servicio?: Servicio
}

// Horario de Empleado
export interface HorarioEmpleado {
  id: number
  empleado_id: number
  dia_semana: DiaSemana
  hora_inicio: string // HH:mm
  hora_fin: string // HH:mm
  active: boolean
}

export type DiaSemana = 1 | 2 | 3 | 4 | 5 | 6 | 7

export const DIAS_SEMANA: Record<DiaSemana, string> = {
  1: 'Lunes',
  2: 'Martes',
  3: 'Miércoles',
  4: 'Jueves',
  5: 'Viernes',
  6: 'Sábado',
  7: 'Domingo',
}

// Bloqueo de Tiempo
export interface BloqueoTiempo {
  id: number
  empleado_id?: number
  fecha: string // YYYY-MM-DD
  hora_inicio: string
  hora_fin: string
  motivo?: string
  tipo: TipoBloqueo
}

export type TipoBloqueo = 'almuerzo' | 'limpieza' | 'libre' | 'reunion' | 'otro'

// Promoción
export interface Promocion {
  id: number
  nombre: string
  descripcion?: string
  descuento_porcentaje?: number
  descuento_fijo?: number
  fecha_inicio: string
  fecha_fin: string
  servicios_aplicables?: number[]
  usos_maximos?: number
  usos_actuales: number
  active: boolean
}

// Foto de Cita
export interface FotoCita {
  id: number
  cita_id: number
  tipo: TipoFoto
  ruta_archivo: string
  descripcion?: string
  url?: string
}

export type TipoFoto = 'antes' | 'despues' | 'proceso'

// Notificación
export interface Notificacion {
  id: number
  cita_id: number
  cliente_id: number
  tipo: TipoNotificacion
  medio: MedioNotificacion
  estado: EstadoNotificacion
  enviado_at?: string
  leido_at?: string
  intentos: number
  error_message?: string
}

export type TipoNotificacion = 
  | 'nueva_cita' 
  | 'confirmacion' 
  | 'recordatorio' 
  | 'recordatorio_dia'
  | 'cancelacion' 
  | 'modificacion' 
  | 'recordatorio_empleado' 
  | 'promocion'

export type MedioNotificacion = 'push' | 'email' | 'whatsapp'
export type EstadoNotificacion = 'pendiente' | 'enviada' | 'fallida' | 'leida'

// Calificación
export interface Calificacion {
  id: number
  cita_id: number
  cliente_id: number
  empleado_id: number
  puntuacion: 1 | 2 | 3 | 4 | 5
  comentario?: string
  visible: boolean
  created_at: string
}

// Configuración
export interface Configuracion {
  clave: string
  valor: string | number | boolean
  tipo: 'string' | 'int' | 'float' | 'boolean' | 'json'
  descripcion?: string
}

// Día Festivo
export interface DiaFestivo {
  id: number
  fecha: string
  nombre: string
  aplica_a_todos: boolean
}

// =====================================================
// Tipos de Disponibilidad
// =====================================================

export interface SlotDisponible {
  hora: string // HH:mm
  hora_fin: string // HH:mm
  disponible: boolean
}

export interface DiaDisponible {
  fecha: string // YYYY-MM-DD
  tiene_disponibilidad: boolean
}

export interface DisponibilidadRequest {
  empleado_id: number
  fecha: string
  servicios: number[]
}

export interface DisponibilidadResponse {
  fecha: string
  empleado_id: number
  duracion_total: number
  slots: SlotDisponible[]
}

// =====================================================
// Tipos de Formularios
// =====================================================

export interface AgendarCitaForm {
  cliente_id?: number
  empleado_id: number
  servicios: number[]
  fecha_hora: string
  promocion_id?: number
  notas?: string
}

export interface ActualizarPerfilClienteForm {
  nombre: string
  email?: string
  fecha_nacimiento?: string
  preferencia_contacto: 'whatsapp' | 'sms' | 'email' | 'llamada'
}

export interface ActualizarNotificacionesForm {
  notificaciones_push: boolean
  notificaciones_email: boolean
  notificaciones_whatsapp: boolean
}

// =====================================================
// Tipos de Respuestas API
// =====================================================

export interface ApiResponse<T> {
  success: boolean
  message?: string
  data?: T
}

export interface PaginatedResponse<T> {
  data: T[]
  current_page: number
  last_page: number
  per_page: number
  total: number
  from: number
  to: number
}

// Dashboard
export interface DashboardData {
  citas_hoy: number
  citas_pendientes: number
  ingresos_hoy: number
  ingresos_mes: number
  servicios_populares: { nombre: string; cantidad: number }[]
  empleados_ocupados: { nombre: string; citas: number }[]
}

