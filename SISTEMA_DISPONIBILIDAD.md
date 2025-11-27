# Sistema de Disponibilidad de Citas

## 📋 Descripción General

El sistema de disponibilidad es el **corazón del agendamiento**. Calcula qué horarios están disponibles para que un cliente pueda agendar una cita, considerando múltiples factores.

---

## 🎯 Objetivo

Dado un **empleado**, **fecha** y **lista de servicios**, devolver los **slots de tiempo disponibles** para agendar.

---

## 📊 Factores a Considerar

### 1. Horario del Negocio
- Hora de apertura (ej: 09:00)
- Hora de cierre (ej: 20:00)
- Días de operación (L-S)

**Fuente**: Tabla `configuracion`

### 2. Horario del Empleado
- Horarios por día de semana
- Algunos empleados trabajan medio tiempo
- Horarios variables

**Fuente**: Tabla `horarios_empleados`

### 3. Bloqueos Temporales
- Almuerzo
- Reuniones
- Días libres
- Limpieza
- Vacaciones

**Fuente**: Tabla `bloqueos_tiempo`

### 4. Citas Existentes
- No solapar con citas ya agendadas
- Considerar duración de cada cita

**Fuente**: Tabla `citas`

### 5. Duración del Servicio
- Cada servicio tiene duración
- Múltiples servicios = suma de duraciones

**Fuente**: Tabla `servicios`

### 6. Configuración de Anticipación
- Anticipación mínima (ej: 2 horas antes)
- Anticipación máxima (ej: 60 días)

**Fuente**: Tabla `configuracion`

### 7. Buffer entre Citas (Opcional)
- Tiempo de preparación entre citas (ej: 10 minutos)

---

## 🔄 Algoritmo de Disponibilidad

### Paso 1: Obtener Parámetros

```
Entrada:
  - empleado_id: ID del empleado
  - fecha: Fecha a consultar (YYYY-MM-DD)
  - servicios[]: Array de IDs de servicios
  
Calcular:
  - duracion_total: Suma de duraciones de servicios
```

### Paso 2: Obtener Configuración

```
Configuración:
  - horario_apertura: "09:00"
  - horario_cierre: "20:00"
  - duracion_slot: 30 (minutos)
  - anticipacion_minima: 2 (horas)
  - anticipacion_maxima: 60 (días)
  - buffer_entre_citas: 0 (minutos, opcional)
```

### Paso 3: Validar Fecha

```
Validaciones:
  1. Fecha no en el pasado
  2. Fecha dentro de rango permitido
  3. Día de semana válido para el negocio
```

### Paso 4: Obtener Horario del Empleado

```
Consulta: horarios_empleados WHERE empleado_id = X AND dia_semana = Y

Resultado:
  - hora_inicio: "09:00"
  - hora_fin: "18:00"
  
Si no existe: empleado no trabaja ese día
```

### Paso 5: Obtener Bloqueos del Día

```
Consulta: bloqueos_tiempo WHERE 
  (empleado_id = X OR empleado_id IS NULL) 
  AND fecha = Y

Resultado: Array de bloqueos
  [
    { hora_inicio: "13:00", hora_fin: "14:00", motivo: "almuerzo" },
    { hora_inicio: "16:00", hora_fin: "16:30", motivo: "limpieza" }
  ]
```

### Paso 6: Obtener Citas del Día

```
Consulta: citas WHERE 
  empleado_id = X 
  AND DATE(fecha_hora) = Y
  AND estado NOT IN ('cancelada', 'no_show')

Resultado: Array de citas
  [
    { hora_inicio: "10:00", hora_fin: "11:30" },
    { hora_inicio: "14:00", hora_fin: "15:00" }
  ]
```

### Paso 7: Generar Slots Base

```
slots_base = []
hora_actual = hora_inicio_empleado

MIENTRAS hora_actual + duracion_total <= hora_fin_empleado:
  slots_base.push(hora_actual)
  hora_actual = hora_actual + duracion_slot
```

### Paso 8: Filtrar Slots No Disponibles

```
slots_disponibles = []

PARA CADA slot EN slots_base:
  slot_inicio = slot
  slot_fin = slot + duracion_total
  
  disponible = TRUE
  
  // Verificar bloqueos
  PARA CADA bloqueo EN bloqueos:
    SI hay_solapamiento(slot_inicio, slot_fin, bloqueo.inicio, bloqueo.fin):
      disponible = FALSE
      BREAK
  
  // Verificar citas existentes
  PARA CADA cita EN citas:
    SI hay_solapamiento(slot_inicio, slot_fin, cita.inicio, cita.fin):
      disponible = FALSE
      BREAK
  
  // Verificar anticipación mínima
  SI slot_inicio < ahora + anticipacion_minima:
    disponible = FALSE
  
  SI disponible:
    slots_disponibles.push(slot)
```

### Paso 9: Aplicar Buffer (Opcional)

```
SI buffer_entre_citas > 0:
  PARA CADA slot EN slots_disponibles:
    // Verificar que hay buffer antes y después
    slot_con_buffer_inicio = slot - buffer
    slot_con_buffer_fin = slot + duracion_total + buffer
    
    // Re-verificar disponibilidad con buffer
```

### Paso 10: Retornar Resultado

```
Salida:
{
  fecha: "2025-11-27",
  empleado_id: 1,
  duracion_total: 90,
  slots: [
    { hora: "09:00", disponible: true },
    { hora: "09:30", disponible: true },
    { hora: "11:30", disponible: true },
    { hora: "12:00", disponible: true },
    { hora: "15:00", disponible: true },
    ...
  ]
}
```

---

## 🔍 Función de Solapamiento

```
hay_solapamiento(inicio1, fin1, inicio2, fin2):
  RETORNAR inicio1 < fin2 AND fin1 > inicio2
```

**Ejemplos**:
- Slot 10:00-11:00, Cita 10:30-11:30 → HAY solapamiento
- Slot 10:00-11:00, Cita 11:00-12:00 → NO hay solapamiento
- Slot 10:00-11:00, Cita 09:00-10:00 → NO hay solapamiento
- Slot 10:00-11:00, Cita 09:00-12:00 → HAY solapamiento

---

## 📐 Casos Especiales

### Caso 1: Múltiples Servicios
```
Cliente quiere: Corte (30 min) + Tinte (60 min) + Peinado (30 min)
Duración total: 120 minutos

El slot debe tener 2 horas consecutivas disponibles.
```

### Caso 2: Cita que Cruza Bloqueo
```
Slot 12:00, Duración 90 min → Termina 13:30
Bloqueo almuerzo: 13:00-14:00

El slot 12:00 NO está disponible porque cruza el almuerzo.
```

### Caso 3: Empleado con Horario Parcial
```
Empleado trabaja: 09:00-13:00 y 15:00-20:00
(No trabaja de 13:00 a 15:00)

Esto se maneja como un bloqueo o dos registros de horario.
```

### Caso 4: Consulta para el Mismo Día
```
Son las 10:30, anticipación mínima = 2 horas
Slots antes de 12:30 no están disponibles.
```

### Caso 5: Empleado Sin Horario Definido
```
Si el empleado no tiene registro en horarios_empleados para ese día:
- Opción A: No trabaja ese día
- Opción B: Usar horario del negocio como default
```

### Caso 6: Bloqueo Global
```
Bloqueo con empleado_id = NULL aplica a TODOS los empleados.
Útil para días festivos o cierres del negocio.
```

---

## 📊 Optimizaciones

### 1. Caché de Disponibilidad

```
Cachéar resultado por:
  - empleado_id
  - fecha
  - lista de servicios (hash)
  
TTL: 5 minutos
Invalidar cuando:
  - Se crea/modifica/cancela una cita
  - Se modifica horario del empleado
  - Se crea/modifica bloqueo
```

### 2. Carga Anticipada

```
Al cargar calendario mensual:
  - Pre-calcular días con disponibilidad
  - Marcar días sin disponibilidad en gris
  - No calcular slots hasta que seleccione día
```

### 3. Consulta Optimizada

```sql
-- Una sola consulta para obtener ocupación del día
SELECT 
  TIME(fecha_hora) as hora_inicio,
  TIME(DATE_ADD(fecha_hora, INTERVAL duracion_total MINUTE)) as hora_fin
FROM citas
WHERE empleado_id = ?
  AND DATE(fecha_hora) = ?
  AND estado NOT IN ('cancelada', 'no_show')
  AND deleted_at IS NULL
ORDER BY fecha_hora;
```

### 4. Índices Necesarios

```sql
-- Ya definidos en tu BD
INDEX idx_citas_fecha_empleado (fecha_hora, empleado_id)
INDEX idx_bloqueos_fecha_empleado (fecha, empleado_id)
INDEX idx_horarios_empleado (empleado_id)
```

---

## 🔐 Validaciones de Seguridad

### Al Consultar Disponibilidad
1. Empleado existe y está activo
2. Servicios existen y están activos
3. Empleado ofrece esos servicios (tabla `empleado_servicio`)
4. Fecha válida

### Al Crear Cita
1. **Re-validar disponibilidad** (puede haber cambiado)
2. Verificar que no hay conflictos
3. Bloquear slot (transaction)

---

## 🔄 Race Condition

### Problema
Dos clientes consultan disponibilidad al mismo tiempo, ven el mismo slot disponible, ambos intentan agendar.

### Solución
```
1. Usar transacciones de BD
2. Bloqueo pesimista (SELECT FOR UPDATE)
3. Re-validar disponibilidad dentro de la transacción
4. Si ya no está disponible, rechazar y mostrar error
```

---

## 📱 API Endpoints

### 1. Obtener Días con Disponibilidad

```
GET /api/disponibilidad/dias
Query params:
  - empleado_id: int
  - mes: int (1-12)
  - año: int
  - servicios[]: array de IDs

Response:
{
  "dias": [
    { "fecha": "2025-11-27", "tiene_disponibilidad": true },
    { "fecha": "2025-11-28", "tiene_disponibilidad": true },
    { "fecha": "2025-11-29", "tiene_disponibilidad": false },
    ...
  ]
}
```

### 2. Obtener Slots de un Día

```
GET /api/disponibilidad/slots
Query params:
  - empleado_id: int
  - fecha: string (YYYY-MM-DD)
  - servicios[]: array de IDs

Response:
{
  "fecha": "2025-11-27",
  "empleado_id": 1,
  "duracion_total": 90,
  "slots": [
    { "hora": "09:00", "hora_fin": "10:30" },
    { "hora": "09:30", "hora_fin": "11:00" },
    { "hora": "11:30", "hora_fin": "13:00" },
    ...
  ]
}
```

### 3. Verificar Disponibilidad Específica

```
POST /api/disponibilidad/verificar
Body:
{
  "empleado_id": 1,
  "fecha_hora": "2025-11-27 10:00:00",
  "servicios": [1, 2, 3]
}

Response:
{
  "disponible": true,
  "duracion_total": 90,
  "hora_fin": "11:30",
  "mensaje": null
}

// O si no está disponible:
{
  "disponible": false,
  "duracion_total": 90,
  "hora_fin": "11:30",
  "mensaje": "El empleado tiene una cita a las 10:30"
}
```

---

## 📋 Estructura de Clases (Laravel)

### DisponibilidadService

```
Métodos:
  - obtenerDiasConDisponibilidad(empleadoId, mes, año, servicios)
  - obtenerSlotsDisponibles(empleadoId, fecha, servicios)
  - verificarDisponibilidad(empleadoId, fechaHora, servicios)
  - calcularDuracionTotal(servicios)
  
Dependencias:
  - HorarioRepository
  - BloqueoRepository
  - CitaRepository
  - ConfiguracionService
  - Cache
```

### Flujo de Datos

```
Controller
    ↓
DisponibilidadService
    ↓
┌───────────────────────────────────────┐
│  1. HorarioRepository                 │
│  2. BloqueoRepository                 │
│  3. CitaRepository                    │
│  4. ConfiguracionService              │
└───────────────────────────────────────┘
    ↓
Algoritmo de cálculo
    ↓
Cache (opcional)
    ↓
Response
```

---

## ✅ Checklist de Implementación

- [ ] Crear DisponibilidadService
- [ ] Implementar algoritmo de slots
- [ ] Implementar función de solapamiento
- [ ] Crear endpoints API
- [ ] Implementar caché
- [ ] Manejar race conditions
- [ ] Tests unitarios
- [ ] Tests de integración
- [ ] Documentar API

---

## 🎯 Siguiente Paso

Una vez documentado el sistema de autenticación y las integraciones con APIs externas, podremos empezar la implementación del código.

