-- =======================================================
-- TABLAS ADICIONALES SUGERIDAS
-- Para completar el sistema de citas de estética
-- =======================================================

-- =======================================================
-- OTP CODES (Códigos de verificación para clientes)
-- =======================================================
CREATE TABLE otp_codes (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    telefono VARCHAR(20) NOT NULL,
    codigo VARCHAR(10) NOT NULL,
    intentos TINYINT DEFAULT 0,
    verificado TINYINT(1) DEFAULT 0,
    expira_at TIMESTAMP NOT NULL,
    created_at TIMESTAMP NULL,
    INDEX idx_otp_telefono (telefono),
    INDEX idx_otp_expira (expira_at)
);

-- =======================================================
-- DISPOSITIVOS (Tokens push para notificaciones)
-- =======================================================
CREATE TABLE dispositivos (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    cliente_id BIGINT UNSIGNED NULL,
    user_id BIGINT UNSIGNED NULL COMMENT 'para empleados/admin',
    token_push VARCHAR(255) NOT NULL,
    plataforma ENUM('android', 'ios', 'web') NOT NULL,
    modelo VARCHAR(100) NULL,
    activo TINYINT(1) DEFAULT 1,
    last_used_at TIMESTAMP NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    FOREIGN KEY (cliente_id) REFERENCES clientes(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_token (token_push),
    INDEX idx_dispositivos_cliente (cliente_id),
    INDEX idx_dispositivos_user (user_id)
);

-- =======================================================
-- CLIENTE_SESSIONS (Sesiones activas de clientes)
-- =======================================================
CREATE TABLE cliente_sessions (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    cliente_id BIGINT UNSIGNED NOT NULL,
    token_id BIGINT UNSIGNED NOT NULL COMMENT 'ID del personal_access_token de Sanctum',
    device_info JSON NULL,
    ip_address VARCHAR(45) NULL,
    last_activity TIMESTAMP NULL,
    created_at TIMESTAMP NULL,
    FOREIGN KEY (cliente_id) REFERENCES clientes(id) ON DELETE CASCADE,
    INDEX idx_sessions_cliente (cliente_id),
    INDEX idx_sessions_last_activity (last_activity)
);

-- =======================================================
-- LOGIN_ATTEMPTS (Intentos de login para seguridad)
-- =======================================================
CREATE TABLE login_attempts (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    identificador VARCHAR(255) NOT NULL COMMENT 'email o teléfono',
    tipo ENUM('empleado', 'cliente', 'admin') NOT NULL,
    ip_address VARCHAR(45) NULL,
    exitoso TINYINT(1) DEFAULT 0,
    user_agent TEXT NULL,
    created_at TIMESTAMP NULL,
    INDEX idx_attempts_identificador (identificador),
    INDEX idx_attempts_ip (ip_address),
    INDEX idx_attempts_created (created_at)
);

-- =======================================================
-- CALIFICACIONES (Opcional - Reseñas de clientes)
-- =======================================================
CREATE TABLE calificaciones (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    cita_id BIGINT UNSIGNED NOT NULL,
    cliente_id BIGINT UNSIGNED NOT NULL,
    empleado_id BIGINT UNSIGNED NOT NULL,
    puntuacion TINYINT NOT NULL COMMENT '1-5 estrellas',
    comentario TEXT NULL,
    visible TINYINT(1) DEFAULT 1 COMMENT 'mostrar públicamente',
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    FOREIGN KEY (cita_id) REFERENCES citas(id),
    FOREIGN KEY (cliente_id) REFERENCES clientes(id),
    FOREIGN KEY (empleado_id) REFERENCES empleados(id),
    UNIQUE KEY unique_cita_calificacion (cita_id),
    INDEX idx_calificaciones_empleado (empleado_id),
    INDEX idx_calificaciones_puntuacion (puntuacion)
);

-- =======================================================
-- PLANTILLAS_NOTIFICACION (Plantillas personalizables)
-- =======================================================
CREATE TABLE plantillas_notificacion (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tipo ENUM('nueva_cita', 'confirmacion', 'recordatorio', 'recordatorio_dia', 
              'cancelacion', 'modificacion', 'promocion', 'otp') NOT NULL,
    medio ENUM('email', 'whatsapp', 'push') NOT NULL,
    asunto VARCHAR(255) NULL COMMENT 'para email',
    contenido TEXT NOT NULL,
    variables JSON NULL COMMENT 'lista de variables disponibles',
    activo TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    UNIQUE KEY unique_tipo_medio (tipo, medio)
);

-- Insertar plantillas por defecto
INSERT INTO plantillas_notificacion (tipo, medio, asunto, contenido, variables) VALUES
-- WhatsApp
('nueva_cita', 'whatsapp', NULL, 
 '¡Hola {{cliente_nombre}}! 👋\n\nTu cita ha sido agendada:\n📅 Fecha: {{fecha}}\n⏰ Hora: {{hora}}\n💇 Servicio: {{servicios}}\n👤 Con: {{empleado_nombre}}\n\n¡Te esperamos!', 
 '["cliente_nombre", "fecha", "hora", "servicios", "empleado_nombre"]'),

('recordatorio', 'whatsapp', NULL,
 '¡Hola {{cliente_nombre}}! 👋\n\nTe recordamos tu cita para mañana:\n📅 Fecha: {{fecha}}\n⏰ Hora: {{hora}}\n💇 Servicio: {{servicios}}\n\n¡Te esperamos! 😊\n\nResponde CANCELAR si no podrás asistir.',
 '["cliente_nombre", "fecha", "hora", "servicios"]'),

('recordatorio_dia', 'whatsapp', NULL,
 '¡Hola {{cliente_nombre}}! 👋\n\nTu cita es en 2 horas:\n⏰ {{hora}}\n💇 {{servicios}}\n\n¡Te esperamos pronto! 🎉',
 '["cliente_nombre", "hora", "servicios"]'),

('cancelacion', 'whatsapp', NULL,
 'Hola {{cliente_nombre}},\n\nTu cita del {{fecha}} a las {{hora}} ha sido cancelada.\n\nSi deseas reagendar, puedes hacerlo desde nuestra app.\n\nDisculpa los inconvenientes.',
 '["cliente_nombre", "fecha", "hora"]'),

('otp', 'whatsapp', NULL,
 'Tu código de verificación es: {{codigo}}\n\nEste código expira en 5 minutos.\nNo compartas este código con nadie.',
 '["codigo"]'),

-- Push
('nueva_cita', 'push', 'Cita Confirmada ✅', 
 'Tu cita para {{servicios}} el {{fecha}} a las {{hora}} ha sido confirmada.',
 '["servicios", "fecha", "hora"]'),

('recordatorio', 'push', 'Recordatorio de Cita 📅',
 'Mañana tienes cita a las {{hora}} para {{servicios}}',
 '["hora", "servicios"]'),

('recordatorio_dia', 'push', '¡Tu cita es pronto! ⏰',
 'Tu cita es en 2 horas. {{servicios}} a las {{hora}}',
 '["servicios", "hora"]'),

('cancelacion', 'push', 'Cita Cancelada',
 'Tu cita del {{fecha}} ha sido cancelada.',
 '["fecha"]');

-- =======================================================
-- DIAS_FESTIVOS (Días no laborables)
-- =======================================================
CREATE TABLE dias_festivos (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    fecha DATE NOT NULL,
    nombre VARCHAR(100) NOT NULL,
    aplica_a_todos TINYINT(1) DEFAULT 1 COMMENT 'si es feriado para todo el negocio',
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    UNIQUE KEY unique_fecha (fecha)
);

-- Insertar días festivos de México (ejemplo)
INSERT INTO dias_festivos (fecha, nombre) VALUES
('2025-01-01', 'Año Nuevo'),
('2025-02-03', 'Día de la Constitución'),
('2025-03-17', 'Natalicio de Benito Juárez'),
('2025-05-01', 'Día del Trabajo'),
('2025-09-16', 'Día de la Independencia'),
('2025-11-17', 'Revolución Mexicana'),
('2025-12-25', 'Navidad');

-- =======================================================
-- TABLAS PARA QUEUE DE LARAVEL
-- =======================================================

-- Jobs pendientes
CREATE TABLE jobs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    queue VARCHAR(255) NOT NULL,
    payload LONGTEXT NOT NULL,
    attempts TINYINT UNSIGNED NOT NULL,
    reserved_at INT UNSIGNED NULL,
    available_at INT UNSIGNED NOT NULL,
    created_at INT UNSIGNED NOT NULL,
    INDEX idx_jobs_queue_reserved (queue, reserved_at)
);

-- Jobs fallidos
CREATE TABLE failed_jobs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    uuid VARCHAR(255) NOT NULL UNIQUE,
    connection TEXT NOT NULL,
    queue TEXT NOT NULL,
    payload LONGTEXT NOT NULL,
    exception LONGTEXT NOT NULL,
    failed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Batches de jobs
CREATE TABLE job_batches (
    id VARCHAR(255) PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    total_jobs INT NOT NULL,
    pending_jobs INT NOT NULL,
    failed_jobs INT NOT NULL,
    failed_job_ids LONGTEXT NOT NULL,
    options MEDIUMTEXT NULL,
    cancelled_at INT NULL,
    created_at INT NOT NULL,
    finished_at INT NULL
);

-- =======================================================
-- ÍNDICES ADICIONALES RECOMENDADOS
-- =======================================================

-- Índice compuesto para consultas de disponibilidad
ALTER TABLE citas ADD INDEX idx_citas_disponibilidad (fecha_hora, estado, empleado_id, deleted_at);

-- Índice para notificaciones pendientes
ALTER TABLE notificaciones ADD INDEX idx_notificaciones_pendientes_medio (estado, medio, created_at);

-- Índice para limpieza de OTPs expirados
ALTER TABLE otp_codes ADD INDEX idx_otp_cleanup (verificado, expira_at);

