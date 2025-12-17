# Estructura de Base de Datos - Sistema de Agenda

Este documento contiene la estructura completa de la base de datos en formato DBML (Database Markup Language) compatible con [dbdiagram.io](https://dbdiagram.io).

## Cómo usar

1. Copia el contenido del bloque de código DBML
2. Ve a [dbdiagram.io](https://dbdiagram.io)
3. Pega el contenido en el editor
4. El diagrama se generará automáticamente

---

```dbml
// ============================================
// SISTEMA DE AGENDA - ESTRUCTURA DE BASE DE DATOS
// ============================================

// ============================================
// AUTENTICACIÓN Y USUARIOS
// ============================================

Table roles {
  id bigint [pk, increment]
  nombre varchar(50) [unique, not null]
  created_at timestamp
  updated_at timestamp
  
  Note: 'Roles del sistema: admin, empleado'
}

Table users {
  id bigint [pk, increment]
  role_id bigint [ref: > roles.id, not null]
  nombre varchar(255) [not null]
  telefono varchar(20)
  email varchar(255) [unique, not null]
  email_verified_at timestamp
  password varchar(255) [not null]
  remember_token varchar(100)
  active boolean [default: true]
  deleted_at timestamp
  created_at timestamp
  updated_at timestamp
  
  indexes {
    role_id [name: 'idx_users_role']
  }
  
  Note: 'Usuarios del sistema (admin y empleados)'
}

Table empleados {
  id bigint [pk, increment]
  user_id bigint [ref: > users.id, not null]
  foto varchar(255)
  bio text
  especialidades text
  active boolean [default: true]
  deleted_at timestamp
  created_at timestamp
  updated_at timestamp
  
  indexes {
    user_id [name: 'idx_empleados_user']
  }
  
  Note: 'Perfil público de empleados'
}

Table clientes {
  id bigint [pk, increment]
  nombre varchar(100) [not null]
  telefono varchar(20) [not null]
  email varchar(150)
  fecha_nacimiento date
  notas text
  preferencia_contacto enum [default: 'whatsapp', note: 'whatsapp, sms, email, llamada']
  notificaciones_push boolean [default: true]
  notificaciones_email boolean [default: true]
  notificaciones_whatsapp boolean [default: true]
  active boolean [default: true]
  deleted_at timestamp
  created_at timestamp
  updated_at timestamp
  
  indexes {
    telefono [name: 'idx_clientes_telefono']
    email [name: 'idx_clientes_email']
  }
  
  Note: 'Clientes del negocio'
}

// ============================================
// CATÁLOGO DE SERVICIOS
// ============================================

Table categorias {
  id bigint [pk, increment]
  nombre varchar(100) [not null]
  descripcion text
  active boolean [default: true]
  deleted_at timestamp
  created_at timestamp
  updated_at timestamp
  
  Note: 'Categorías de servicios'
}

Table servicios {
  id bigint [pk, increment]
  categoria_id bigint [ref: > categorias.id, not null]
  nombre varchar(100) [not null]
  descripcion text
  precio decimal(10,2) [not null]
  duracion integer [not null, note: 'duración en minutos']
  active boolean [default: true]
  deleted_at timestamp
  created_at timestamp
  updated_at timestamp
  
  indexes {
    categoria_id [name: 'idx_servicios_categoria']
  }
  
  Note: 'Servicios ofrecidos'
}

Table empleado_servicio {
  id bigint [pk, increment]
  empleado_id bigint [ref: > empleados.id, not null]
  servicio_id bigint [ref: > servicios.id, not null]
  precio_especial decimal(10,2) [note: 'precio específico del empleado, NULL = precio estándar']
  created_at timestamp
  updated_at timestamp
  
  indexes {
    (empleado_id, servicio_id) [unique, name: 'unique_empleado_servicio']
    empleado_id [name: 'idx_empleado_servicio_empleado']
    servicio_id [name: 'idx_empleado_servicio_servicio']
  }
  
  Note: 'Relación muchos a muchos: empleados y servicios con precios especiales'
}

// ============================================
// PROMOCIONES
// ============================================

Table promociones {
  id bigint [pk, increment]
  nombre varchar(100) [not null]
  descripcion text
  imagen varchar(255)
  tipo_aplicable enum [default: 'servicios', note: 'servicios, productos, ambos']
  descuento_porcentaje integer [note: 'descuento en porcentaje']
  descuento_fijo decimal(10,2) [note: 'descuento fijo en pesos']
  fecha_inicio date [not null]
  fecha_fin date [not null]
  servicios_aplicables json [note: 'array de servicio_ids, NULL = todos']
  productos_aplicables json [note: 'array de producto_ids, NULL = todos los productos']
  usos_maximos integer [note: 'NULL = ilimitado']
  usos_actuales integer [default: 0]
  active boolean [default: true]
  deleted_at timestamp
  created_at timestamp
  updated_at timestamp
  
  indexes {
    (fecha_inicio, fecha_fin) [name: 'idx_promociones_fechas']
    tipo_aplicable [name: 'idx_promociones_tipo_aplicable']
  }
  
  Note: 'Promociones y descuentos (aplicables a servicios y/o productos)'
}

// ============================================
// CITAS
// ============================================

Table citas {
  id bigint [pk, increment]
  cliente_id bigint [ref: > clientes.id, not null]
  empleado_id bigint [ref: > empleados.id, not null]
  servicio_id bigint [ref: > servicios.id, note: 'nullable para citas con múltiples servicios']
  promocion_id bigint [ref: > promociones.id]
  fecha_hora datetime [not null]
  duracion_total integer [not null, note: 'duración total en minutos']
  estado enum [default: 'pendiente', note: 'pendiente, confirmada, en_proceso, completada, cancelada, no_show, reagendada']
  token_qr varchar(64) [note: 'token para QR, puede repetirse en citas coordinadas']
  precio_final decimal(10,2) [note: 'precio con descuento aplicado']
  metodo_pago enum [default: 'pendiente', note: 'efectivo, tarjeta, transferencia, pendiente']
  notas text
  active boolean [default: true]
  deleted_at timestamp
  created_at timestamp
  updated_at timestamp
  
  indexes {
    (fecha_hora, empleado_id) [name: 'idx_citas_fecha_empleado']
    (cliente_id, estado) [name: 'idx_citas_cliente_estado']
    (fecha_hora, estado) [name: 'idx_citas_fecha_estado']
    empleado_id [name: 'idx_citas_empleado']
    (fecha_hora, estado, empleado_id, deleted_at) [name: 'idx_citas_disponibilidad']
    token_qr [name: 'idx_citas_token_qr']
  }
  
  Note: 'Citas agendadas'
}

Table citas_servicios {
  id bigint [pk, increment]
  cita_id bigint [ref: > citas.id, not null]
  servicio_id bigint [ref: > servicios.id, not null]
  precio_aplicado decimal(10,2) [not null]
  orden tinyint [default: 1, note: 'orden de ejecución del servicio']
  created_at timestamp
  updated_at timestamp
  
  indexes {
    cita_id [name: 'idx_citas_servicios_cita']
    servicio_id [name: 'idx_citas_servicios_servicio']
  }
  
  Note: 'Servicios por cita (para citas con múltiples servicios)'
}

Table fotos_citas {
  id bigint [pk, increment]
  cita_id bigint [ref: > citas.id, not null]
  tipo enum [not null, note: 'antes, despues, proceso']
  ruta_archivo varchar(255) [not null]
  descripcion text
  created_at timestamp
  updated_at timestamp
  
  indexes {
    cita_id [name: 'idx_fotos_cita']
  }
  
  Note: 'Fotos antes/después/proceso de las citas'
}

Table calificaciones {
  id bigint [pk, increment]
  cita_id bigint [ref: > citas.id, not null, unique]
  cliente_id bigint [ref: > clientes.id, not null]
  empleado_id bigint [ref: > empleados.id, not null]
  puntuacion tinyint [not null, note: '1-5 estrellas']
  comentario text
  visible boolean [default: true, note: 'mostrar públicamente']
  created_at timestamp
  updated_at timestamp
  
  indexes {
    cita_id [unique, name: 'unique_cita_calificacion']
    empleado_id [name: 'idx_calificaciones_empleado']
    puntuacion [name: 'idx_calificaciones_puntuacion']
  }
  
  Note: 'Calificaciones y comentarios de clientes'
}

// ============================================
// GESTIÓN DE TIEMPO Y DISPONIBILIDAD
// ============================================

Table horarios_empleados {
  id bigint [pk, increment]
  empleado_id bigint [ref: > empleados.id, not null]
  dia_semana tinyint [not null, note: '1=lunes, 7=domingo']
  hora_inicio time [not null]
  hora_fin time [not null]
  active boolean [default: true]
  created_at timestamp
  updated_at timestamp
  
  indexes {
    (empleado_id, dia_semana) [unique, name: 'unique_empleado_dia']
    empleado_id [name: 'idx_horarios_empleado']
  }
  
  Note: 'Horarios de trabajo por día de semana'
}

Table bloqueos_tiempo {
  id bigint [pk, increment]
  empleado_id bigint [ref: > empleados.id, note: 'NULL = bloqueo para todos los empleados']
  fecha date [not null]
  hora_inicio time [not null]
  hora_fin time [not null]
  motivo varchar(255)
  tipo enum [not null, note: 'almuerzo, limpieza, libre, reunion, otro']
  created_at timestamp
  updated_at timestamp
  
  indexes {
    (fecha, empleado_id) [name: 'idx_bloqueos_fecha_empleado']
    empleado_id [name: 'idx_bloqueos_empleado']
  }
  
  Note: 'Bloqueos temporales de tiempo'
}

Table dias_festivos {
  id bigint [pk, increment]
  fecha date [unique, not null]
  nombre varchar(100) [not null]
  aplica_a_todos boolean [default: true, note: 'si es feriado para todo el negocio']
  created_at timestamp
  updated_at timestamp
  
  Note: 'Días festivos del año'
}

Table reservas_temporales {
  id bigint [pk, increment]
  token varchar(64) [unique, not null, note: 'Token único para identificar la reserva']
  empleado_id bigint [ref: > empleados.id, not null]
  fecha date [not null]
  hora_inicio time [not null]
  hora_fin time [not null]
  duracion_total integer [not null, note: 'Duración total en minutos']
  telefono_cliente varchar(20) [note: 'Para identificar al usuario']
  session_id varchar(100) [note: 'ID de sesión del navegador']
  servicios_ids json [note: 'IDs de servicios reservados']
  datos_adicionales json [note: 'Datos extra para slots coordinados']
  expira_at timestamp [not null, note: 'Cuándo expira la reserva temporal']
  created_at timestamp
  updated_at timestamp
  
  indexes {
    (empleado_id, fecha, hora_inicio) [name: 'idx_reservas_empleado_fecha_hora']
    expira_at [name: 'idx_reservas_expira']
    token [name: 'idx_reservas_token']
  }
  
  Note: 'Reservas temporales de slots para prevenir conflictos'
}

// ============================================
// NOTIFICACIONES Y COMUNICACIÓN
// ============================================

Table notificaciones {
  id bigint [pk, increment]
  cita_id bigint [ref: > citas.id, not null]
  cliente_id bigint [ref: > clientes.id, not null]
  tipo enum [not null, note: 'nueva_cita, confirmacion, recordatorio, recordatorio_dia, cancelacion, modificacion, recordatorio_empleado, promocion, reagendamiento']
  medio enum [not null, note: 'push, email, whatsapp']
  estado enum [default: 'pendiente', note: 'pendiente, enviada, fallida, leida']
  enviado_at timestamp
  leido_at timestamp
  intentos tinyint [default: 0]
  error_message text
  metadata json [note: 'IDs externos, tokens push, message IDs de WhatsApp, etc.']
  created_at timestamp
  updated_at timestamp
  
  indexes {
    cita_id [name: 'idx_notificaciones_cita']
    cliente_id [name: 'idx_notificaciones_cliente']
    (estado, medio) [name: 'idx_notificaciones_estado']
    (estado, created_at) [name: 'idx_notificaciones_pendientes']
    (estado, medio, created_at) [name: 'idx_notificaciones_pendientes_medio']
  }
  
  Note: 'Registro de notificaciones enviadas'
}

Table plantillas_notificacion {
  id bigint [pk, increment]
  tipo enum [not null, note: 'nueva_cita, confirmacion_cita, recordatorio_cita, recordatorio_dia, cancelacion_cita, modificacion_cita, promocion, otp']
  medio enum [not null, note: 'email, whatsapp, push']
  asunto varchar(255) [note: 'para email']
  contenido text [not null]
  variables json [note: 'lista de variables disponibles']
  activo boolean [default: true]
  created_at timestamp
  updated_at timestamp
  
  indexes {
    (tipo, medio) [unique, name: 'unique_tipo_medio']
  }
  
  Note: 'Plantillas de notificaciones por tipo y medio'
}

// ============================================
// AUTENTICACIÓN Y SESIONES
// ============================================

Table otp_codes {
  id bigint [pk, increment]
  telefono varchar(20) [not null]
  tipo varchar(20) [default: 'login', not null]
  codigo varchar(10) [not null]
  intentos tinyint [default: 0]
  verificado boolean [default: false]
  expira_at timestamp [not null]
  created_at timestamp
  
  indexes {
    (telefono, tipo) [name: 'idx_otp_telefono_tipo']
    expira_at [name: 'idx_otp_expira']
    (verificado, expira_at) [name: 'idx_otp_cleanup']
  }
  
  Note: 'Códigos OTP para autenticación de clientes'
}

Table dispositivos {
  id bigint [pk, increment]
  cliente_id bigint [ref: > clientes.id, note: 'para clientes']
  user_id bigint [ref: > users.id, note: 'para empleados/admin']
  token_push varchar(255) [unique, not null]
  plataforma enum [not null, note: 'android, ios, web']
  modelo varchar(100)
  activo boolean [default: true]
  last_used_at timestamp
  created_at timestamp
  updated_at timestamp
  
  indexes {
    cliente_id [name: 'idx_dispositivos_cliente']
    user_id [name: 'idx_dispositivos_user']
  }
  
  Note: 'Dispositivos para notificaciones push'
}

Table cliente_sessions {
  id bigint [pk, increment]
  cliente_id bigint [ref: > clientes.id, not null]
  token_id bigint [not null, note: 'ID del personal_access_token de Sanctum']
  device_info json
  ip_address varchar(45)
  last_activity timestamp
  created_at timestamp
  
  indexes {
    cliente_id [name: 'idx_sessions_cliente']
    last_activity [name: 'idx_sessions_last_activity']
  }
  
  Note: 'Sesiones de clientes'
}

Table login_attempts {
  id bigint [pk, increment]
  identificador varchar(255) [not null, note: 'email o teléfono']
  tipo enum [not null, note: 'empleado, cliente, admin']
  ip_address varchar(45)
  exitoso boolean [default: false]
  user_agent text
  created_at timestamp
  
  indexes {
    identificador [name: 'idx_attempts_identificador']
    ip_address [name: 'idx_attempts_ip']
    created_at [name: 'idx_attempts_created']
  }
  
  Note: 'Intentos de login para seguridad'
}

// ============================================
// SISTEMA Y CONFIGURACIÓN
// ============================================

Table configuracion {
  id bigint [pk, increment]
  clave varchar(100) [unique, not null]
  valor text [not null]
  tipo varchar(50) [default: 'string', note: 'string, int, float, json, boolean']
  descripcion text
  created_at timestamp
  updated_at timestamp
  
  Note: 'Configuración del sistema'
}

Table auditoria {
  id bigint [pk, increment]
  usuario_id bigint [ref: > users.id]
  accion text [not null]
  tabla varchar(100) [not null]
  registro_id bigint [not null]
  datos_anteriores json
  datos_nuevos json
  ip_address varchar(45)
  user_agent text
  created_at timestamp
  
  indexes {
    usuario_id [name: 'idx_auditoria_usuario']
    (tabla, registro_id) [name: 'idx_auditoria_tabla_registro']
    created_at [name: 'idx_auditoria_fecha']
  }
  
  Note: 'Log de auditoría del sistema'
}

// ============================================
// VENTAS E INVENTARIO
// ============================================

Table categorias_productos {
  id bigint [pk, increment]
  nombre varchar(100) [not null]
  descripcion text
  active boolean [default: true]
  deleted_at timestamp
  created_at timestamp
  updated_at timestamp
  
  indexes {
    active [name: 'idx_categorias_productos_active']
  }
  
  Note: 'Categorías de productos (medicamentos, productos de belleza, etc.)'
}

Table productos {
  id bigint [pk, increment]
  codigo varchar(100) [unique, not null]
  nombre varchar(255) [not null]
  foto varchar(255)
  categoria_id bigint [ref: > categorias_productos.id]
  inventario_minimo integer [default: 0]
  inventario_actual integer [default: 0]
  costo decimal(10,2) [default: 0]
  precio decimal(10,2) [not null]
  active boolean [default: true]
  deleted_at timestamp
  created_at timestamp
  updated_at timestamp
  
  indexes {
    codigo [name: 'idx_productos_codigo']
    categoria_id [name: 'idx_productos_categoria']
    active [name: 'idx_productos_active']
  }
  
  Note: 'Productos para venta (medicamentos, productos de belleza)'
}

Table movimientos_inventario {
  id bigint [pk, increment]
  producto_id bigint [ref: > productos.id, not null]
  tipo enum [not null, note: 'entrada_manual, salida_manual, venta']
  cantidad integer [not null]
  motivo varchar(255)
  referencia_id bigint [note: 'ID de venta relacionada']
  referencia_tipo varchar(50) [note: 'tipo de referencia: venta, etc.']
  user_id bigint [ref: > users.id]
  notas text
  created_at timestamp
  
  indexes {
    producto_id [name: 'idx_movimientos_producto']
    tipo [name: 'idx_movimientos_tipo']
    (referencia_id, referencia_tipo) [name: 'idx_movimientos_referencia']
    created_at [name: 'idx_movimientos_fecha']
    user_id [name: 'idx_movimientos_user']
  }
  
  Note: 'Registro de movimientos de inventario'
}

Table metodos_pago {
  id bigint [pk, increment]
  nombre varchar(100) [not null]
  codigo varchar(50) [unique, not null]
  es_efectivo boolean [default: false]
  activo boolean [default: true]
  orden integer [default: 0]
  created_at timestamp
  updated_at timestamp
  
  indexes {
    codigo [name: 'idx_metodos_pago_codigo']
    activo [name: 'idx_metodos_pago_activo']
  }
  
  Note: 'Catálogo de métodos de pago disponibles'
}

Table ventas {
  id bigint [pk, increment]
  cliente_id bigint [ref: > clientes.id]
  fecha_venta datetime [not null]
  subtotal decimal(10,2) [not null]
  descuento_general decimal(10,2) [default: 0]
  impuesto_total decimal(10,2) [default: 0]
  total decimal(10,2) [not null]
  total_pagado decimal(10,2) [default: 0, note: 'Total de pagos acumulados']
  saldo_pendiente decimal(10,2) [default: 0, note: 'Saldo pendiente (total - total_pagado)']
  estado enum [default: 'pendiente_pago', note: 'pendiente_pago, parcial, completada, cancelada']
  requiere_anticipo boolean [default: false]
  monto_anticipo_requerido decimal(10,2) [note: 'Monto de anticipo requerido']
  monto_anticipo_pagado decimal(10,2) [default: 0, note: 'Monto de anticipo pagado']
  notas text
  deleted_at timestamp
  created_at timestamp
  updated_at timestamp
  
  indexes {
    cliente_id [name: 'idx_ventas_cliente']
    fecha_venta [name: 'idx_ventas_fecha']
    estado [name: 'idx_ventas_estado']
    requiere_anticipo [name: 'idx_ventas_requiere_anticipo']
  }
  
  Note: 'Encabezado de ventas (resumen/recibo que puede incluir múltiples servicios y productos)'
}

Table venta_detalle {
  id bigint [pk, increment]
  venta_id bigint [ref: > ventas.id, not null]
  tipo enum [default: 'producto', note: 'servicio, producto']
  producto_id bigint [ref: > productos.id, note: 'NULL si tipo=servicio']
  servicio_id bigint [ref: > servicios.id, note: 'NULL si tipo=producto']
  cita_id bigint [ref: > citas.id, note: 'Cita asociada al servicio (NULL si tipo=producto)']
  promocion_id bigint [ref: > promociones.id, note: 'Promoción aplicada a esta línea']
  cantidad integer [not null]
  precio_unitario decimal(10,2) [not null]
  descuento decimal(10,2) [default: 0, note: 'Descuento aplicado a esta línea']
  impuesto decimal(10,2) [default: 0, note: 'Impuesto aplicado a esta línea']
  subtotal_linea decimal(10,2) [not null, note: 'Subtotal de la línea (cantidad * precio_unitario - descuento + impuesto)']
  created_at timestamp
  updated_at timestamp
  
  indexes {
    venta_id [name: 'idx_venta_detalle_venta']
    tipo [name: 'idx_venta_detalle_tipo']
    producto_id [name: 'idx_venta_detalle_producto']
    servicio_id [name: 'idx_venta_detalle_servicio']
    cita_id [name: 'idx_venta_detalle_cita']
    promocion_id [name: 'idx_venta_detalle_promocion']
  }
  
  Note: 'Detalle de venta (servicios y/o productos). Cada servicio tiene su cita_id para trazabilidad'
}

Table venta_pagos {
  id bigint [pk, increment]
  venta_id bigint [ref: > ventas.id, not null]
  metodo_pago_id bigint [ref: > metodos_pago.id, not null]
  monto decimal(10,2) [not null, note: 'Monto del pago']
  monto_recibido decimal(10,2) [note: 'Para efectivo, monto recibido']
  cambio decimal(10,2) [note: 'Cambio devuelto']
  es_efectivo boolean [default: false]
  transaccion_id varchar(255) [note: 'ID de transacción del proveedor de pago']
  proveedor_pago enum [note: 'mercadopago, stripe']
  estado_pago enum [default: 'pendiente', note: 'pendiente, aprobado, rechazado, reembolsado']
  metadata_pago json [note: 'Datos adicionales del pago']
  notas text
  user_id bigint [ref: > users.id, not null, note: 'Usuario que registró el pago']
  created_at timestamp
  updated_at timestamp
  
  indexes {
    venta_id [name: 'idx_venta_pagos_venta']
    metodo_pago_id [name: 'idx_venta_pagos_metodo_pago']
    estado_pago [name: 'idx_venta_pagos_estado_pago']
    transaccion_id [name: 'idx_venta_pagos_transaccion']
    user_id [name: 'idx_venta_pagos_user']
    created_at [name: 'idx_venta_pagos_fecha']
  }
  
  Note: 'Pagos parciales de una venta (permite múltiples pagos)'
}

Table webhooks_pagos {
  id bigint [pk, increment]
  venta_id bigint [ref: > ventas.id]
  proveedor enum [not null, note: 'mercadopago, stripe']
  evento_tipo varchar(100) [not null]
  payload json [not null]
  procesado boolean [default: false]
  respuesta json [note: 'Respuesta enviada al proveedor']
  error_message text
  created_at timestamp
  
  indexes {
    venta_id [name: 'idx_webhooks_venta']
    proveedor [name: 'idx_webhooks_proveedor']
    procesado [name: 'idx_webhooks_procesado']
    created_at [name: 'idx_webhooks_fecha']
  }
  
  Note: 'Registro de webhooks recibidos de Mercado Pago/Stripe'
}

// ============================================
// SISTEMA DE REGLAS DE ANTICIPO
// ============================================

Table reglas_anticipo {
  id bigint [pk, increment]
  nombre varchar(100) [not null]
  descripcion text
  tipo_regla enum [not null, note: 'fecha, monto, servicio']
  tipo_calculo enum [not null, note: 'porcentaje, monto_fijo']
  valor_calculo decimal(10,2) [not null, note: 'Porcentaje (0-100) o monto fijo según tipo_calculo']
  prioridad integer [default: 0, note: 'Mayor número = mayor prioridad']
  activo boolean [default: true]
  created_at timestamp
  updated_at timestamp
  
  indexes {
    tipo_regla [name: 'idx_reglas_anticipo_tipo']
    activo [name: 'idx_reglas_anticipo_activo']
    prioridad [name: 'idx_reglas_anticipo_prioridad']
  }
  
  Note: 'Reglas para determinar si una venta requiere anticipo'
}

Table reglas_anticipo_fecha {
  id bigint [pk, increment]
  regla_anticipo_id bigint [ref: > reglas_anticipo.id, not null]
  fecha_inicio date [not null]
  fecha_fin date [not null]
  aplica_todos_dias boolean [default: true, note: 'Si false, usar dias_semana']
  dias_semana json [note: 'Array de días [1=lunes, 7=domingo], NULL si aplica_todos_dias=true']
  created_at timestamp
  updated_at timestamp
  
  indexes {
    regla_anticipo_id [name: 'idx_reglas_anticipo_fecha_regla']
    (fecha_inicio, fecha_fin) [name: 'idx_reglas_anticipo_fecha_rango']
  }
  
  Note: 'Configuración de reglas de anticipo por rango de fechas'
}

Table reglas_anticipo_monto {
  id bigint [pk, increment]
  regla_anticipo_id bigint [ref: > reglas_anticipo.id, not null]
  monto_minimo decimal(10,2) [not null, note: 'Ventas >= este monto requieren anticipo']
  monto_maximo decimal(10,2) [note: 'Ventas <= este monto (NULL = sin límite)']
  created_at timestamp
  updated_at timestamp
  
  indexes {
    regla_anticipo_id [name: 'idx_reglas_anticipo_monto_regla']
    monto_minimo [name: 'idx_reglas_anticipo_monto_minimo']
  }
  
  Note: 'Configuración de reglas de anticipo por monto de venta'
}

Table reglas_anticipo_servicio {
  id bigint [pk, increment]
  regla_anticipo_id bigint [ref: > reglas_anticipo.id, not null]
  servicio_id bigint [ref: > servicios.id, not null]
  created_at timestamp
  updated_at timestamp
  
  indexes {
    regla_anticipo_id [name: 'idx_reglas_anticipo_servicio_regla']
    servicio_id [name: 'idx_reglas_anticipo_servicio_servicio']
  }
  
  Note: 'Configuración de reglas de anticipo por servicio específico'
}

// ============================================
// RELACIONES ADICIONALES
// ============================================

Ref: users.role_id > roles.id
Ref: empleados.user_id > users.id
Ref: servicios.categoria_id > categorias.id
Ref: empleado_servicio.empleado_id > empleados.id
Ref: empleado_servicio.servicio_id > servicios.id
Ref: citas.cliente_id > clientes.id
Ref: citas.empleado_id > empleados.id
Ref: citas.servicio_id > servicios.id
Ref: citas.promocion_id > promociones.id
Ref: citas_servicios.cita_id > citas.id
Ref: citas_servicios.servicio_id > servicios.id
Ref: fotos_citas.cita_id > citas.id
Ref: calificaciones.cita_id > citas.id
Ref: calificaciones.cliente_id > clientes.id
Ref: calificaciones.empleado_id > empleados.id
Ref: horarios_empleados.empleado_id > empleados.id
Ref: bloqueos_tiempo.empleado_id > empleados.id
Ref: reservas_temporales.empleado_id > empleados.id
Ref: notificaciones.cita_id > citas.id
Ref: notificaciones.cliente_id > clientes.id
Ref: dispositivos.cliente_id > clientes.id
Ref: dispositivos.user_id > users.id
Ref: cliente_sessions.cliente_id > clientes.id
Ref: auditoria.usuario_id > users.id
Ref: productos.categoria_id > categorias_productos.id
Ref: movimientos_inventario.producto_id > productos.id
Ref: movimientos_inventario.user_id > users.id
Ref: ventas.cliente_id > clientes.id
Ref: venta_detalle.venta_id > ventas.id
Ref: venta_detalle.producto_id > productos.id
Ref: venta_detalle.servicio_id > servicios.id
Ref: venta_detalle.cita_id > citas.id
Ref: venta_detalle.promocion_id > promociones.id
Ref: venta_pagos.venta_id > ventas.id
Ref: venta_pagos.metodo_pago_id > metodos_pago.id
Ref: venta_pagos.user_id > users.id
Ref: webhooks_pagos.venta_id > ventas.id
Ref: reglas_anticipo_fecha.regla_anticipo_id > reglas_anticipo.id
Ref: reglas_anticipo_monto.regla_anticipo_id > reglas_anticipo.id
Ref: reglas_anticipo_servicio.regla_anticipo_id > reglas_anticipo.id
Ref: reglas_anticipo_servicio.servicio_id > servicios.id
```

---

## Notas sobre la estructura

### Características principales

1. **Soft Deletes**: Las tablas `users`, `empleados`, `categorias`, `servicios`, `clientes`, `promociones`, `citas`, `categorias_productos`, `productos` y `ventas` utilizan soft deletes (`deleted_at`).

2. **Índices**: Se han definido índices estratégicos para optimizar consultas frecuentes, especialmente en:
   - Búsquedas de disponibilidad de citas
   - Filtros por fecha y empleado
   - Búsquedas de clientes por teléfono/email
   - Notificaciones pendientes
   - Búsquedas de productos por código
   - Movimientos de inventario por producto y tipo
   - Ventas por cliente, fecha y estado
   - Webhooks de pagos por proveedor y estado

3. **Relaciones**:
   - Un usuario puede tener un rol
   - Un usuario puede ser un empleado (relación 1:1)
   - Un empleado puede tener múltiples servicios (muchos a muchos)
   - Una cita puede tener múltiples servicios (a través de `citas_servicios`)
   - Un cliente puede tener múltiples citas
   - Un empleado puede tener múltiples citas
   - Una categoría de producto puede tener múltiples productos
   - Un producto puede tener múltiples movimientos de inventario
   - Una venta puede tener múltiples servicios y/o productos (a través de `venta_detalle`)
   - Una venta puede tener múltiples pagos parciales (a través de `venta_pagos`)
   - Una venta puede estar relacionada con una cita (opcional)
   - Un cliente puede tener múltiples ventas
   - Una venta puede tener múltiples webhooks de pago

4. **Enums**: Se utilizan enums para estados, tipos y medios de comunicación para mantener la integridad de datos.

5. **JSON**: Se utilizan campos JSON para datos flexibles como:
   - `servicios_aplicables` y `productos_aplicables` en promociones
   - `metadata` en notificaciones
   - `device_info` en sesiones
   - `servicios_ids` y `datos_adicionales` en reservas temporales
   - `metadata_pago` en ventas y venta_pagos (datos adicionales de pagos online)
   - `payload` y `respuesta` en webhooks_pagos (datos de webhooks)

6. **Sistema de Ventas Integrado con Citas**:
   - Las ventas funcionan como resumen/recibo que se crea al agendar servicios
   - Una venta puede tener múltiples citas (cada servicio en `venta_detalle` tiene su `cita_id`)
   - Puede incluir servicios (de citas) y productos en el mismo documento
   - Soporta pagos parciales múltiples a través de `venta_pagos`
   - Las promociones pueden aplicarse tanto a servicios como a productos
   - Solo los productos afectan el inventario, los servicios no
   - Los pagos online se registran en `venta_pagos` con información del proveedor (Mercado Pago/Stripe)

7. **Sistema de Reglas de Anticipo**:
   - Configuración flexible de anticipos mediante reglas
   - Tres tipos de reglas: por fecha (rangos de fechas), por monto (mínimo/máximo), por servicio
   - Cada regla puede calcular anticipo como porcentaje o monto fijo
   - Sistema de prioridades para determinar qué regla aplicar cuando múltiples coinciden
   - Se selecciona la regla con mayor prioridad que requiera el mayor anticipo
   - Los anticipos se almacenan en `ventas` para referencia histórica

### Tablas del sistema Laravel

Este documento no incluye las tablas estándar de Laravel como:
- `personal_access_tokens` (Laravel Sanctum)
- `password_reset_tokens`
- `sessions`
- `cache`
- `jobs`
- `failed_jobs`

Estas tablas son generadas automáticamente por Laravel y pueden agregarse al diagrama si es necesario.

