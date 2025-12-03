# 📋 Funcionalidad: Múltiples Citas y Servicios Coordinados

## 📖 Descripción General

Esta funcionalidad permite a los clientes agendar **múltiples servicios con diferentes empleados** en una sola sesión, donde cada servicio se ejecuta secuencialmente y el sistema valida automáticamente que todos los horarios encajen antes de mostrar opciones al usuario.

### 🎯 Problema que Resuelve

**Escenario Real:**
Un cliente quiere agendar:
- **Corte de pelo** (30 min) con **María**
- **Manicura** (45 min) con **Juan**
- **Pedicure** (60 min) con **Ana**

**Sin esta funcionalidad:**
- El usuario selecciona un horario (ej: 10:00 AM)
- El sistema intenta crear las citas
- **Error:** Juan no está disponible a las 10:30 AM (cuando termina el corte)
- El usuario tiene que probar múltiples horarios manualmente

**Con esta funcionalidad:**
- El sistema **pre-valida** todos los horarios
- Solo muestra horarios donde **TODOS** los servicios encajan
- El usuario selecciona un horario válido
- Las citas se crean automáticamente con horarios coordinados

---

## 🏗️ Arquitectura

### Modelo de Datos

El sistema mantiene la estructura existente donde cada cita es independiente:

```
┌─────────────────────────────────────────────────────────┐
│ Tabla: citas                                            │
│ - id                                                    │
│ - cliente_id                                            │
│ - empleado_id (empleado del primer servicio)           │
│ - fecha_hora (inicio del primer servicio)              │
│ - duracion_total                                        │
│ - estado                                                │
│ - precio_final                                          │
└─────────────────────────────────────────────────────────┘
                    ↓
┌─────────────────────────────────────────────────────────┐
│ Tabla: citas_servicios                                   │
│ - cita_id                                               │
│ - servicio_id                                           │
│ - precio_aplicado                                       │
│ - orden                                                  │
└─────────────────────────────────────────────────────────┘
```

**Enfoque:** Cada servicio con diferente empleado se crea como una **cita independiente**, pero con horarios coordinados secuencialmente.

### Flujo de Datos

```
Usuario selecciona:
├── Servicio 1: Corte (30 min) → Empleado: María
├── Servicio 2: Manicura (45 min) → Empleado: Juan
└── Servicio 3: Pedicure (60 min) → Empleado: Ana

Sistema crea:
├── Cita 1: María - Corte (10:00-10:30)
├── Cita 2: Juan - Manicura (10:30-11:15)
└── Cita 3: Ana - Pedicure (11:15-12:15)
```

---

## 🔄 Flujo de Usuario

### Paso 1: Selección de Servicios

El usuario selecciona múltiples servicios del catálogo:

```
┌─────────────────────────────────────┐
│ Servicios Seleccionados:            │
│ ✓ Corte de pelo (30 min)            │
│ ✓ Manicura (45 min)                 │
│ ✓ Pedicure (60 min)                 │
└─────────────────────────────────────┘
```

### Paso 2: Selección de Empleados

Para cada servicio, el usuario selecciona un empleado:

```
┌─────────────────────────────────────┐
│ Asignar Empleados:                 │
│                                     │
│ Corte de pelo:                      │
│ ○ María  ○ Juan  ○ Ana             │
│   ✓                                │
│                                     │
│ Manicura:                           │
│ ○ María  ○ Juan  ○ Ana             │
│           ✓                        │
│                                     │
│ Pedicure:                           │
│ ○ María  ○ Juan  ○ Ana             │
│                     ✓              │
└─────────────────────────────────────┘
```

### Paso 3: Selección de Fecha

El usuario selecciona una fecha del calendario:

```
┌─────────────────────────────────────┐
│ Calendario                          │
│                                     │
│  D  L  M  M  J  V  S               │
│           1  2  3  4               │
│  5  6  7  8  9 10 11               │
│ 12 13 14 15 16 17 18               │
│ 19 20 21 22 23 24 25               │
│ 26 27 28 29 30 31                  │
│                                     │
│ ✓ = Disponible                     │
└─────────────────────────────────────┘
```

### Paso 4: Validación Coordinada (Automática)

El sistema valida automáticamente qué horarios funcionan:

```
┌─────────────────────────────────────────────────────────┐
│ Validando horarios coordinados...                      │
│                                                         │
│ Para cada horario de MARÍA (primer servicio):          │
│                                                         │
│ 09:00 AM:                                               │
│   ✓ María disponible 09:00-09:30                       │
│   → Verificar Juan 09:30-10:15: ✗ No disponible        │
│   ❌ Descartado                                         │
│                                                         │
│ 10:00 AM:                                               │
│   ✓ María disponible 10:00-10:30                       │
│   → Verificar Juan 10:30-11:15: ✓ Disponible           │
│   → Verificar Ana 11:15-12:15: ✓ Disponible            │
│   ✅ VÁLIDO                                             │
│                                                         │
│ 11:00 AM:                                               │
│   ✓ María disponible 11:00-11:30                       │
│   → Verificar Juan 11:30-12:15: ✗ No disponible        │
│   ❌ Descartado                                         │
└─────────────────────────────────────────────────────────┘
```

### Paso 5: Mostrar Solo Horarios Válidos

El usuario solo ve horarios donde todos los servicios encajan:

```
┌─────────────────────────────────────┐
│ Horarios Disponibles:               │
│                                     │
│ ┌─────────────────────────────┐    │
│ │ 10:00 AM ✓                  │    │
│ │ María → Juan → Ana          │    │
│ │ 10:00  10:30  11:15  12:15  │    │
│ └─────────────────────────────┘    │
│                                     │
│ ┌─────────────────────────────┐    │
│ │ 12:00 PM ✓                  │    │
│ │ María → Juan → Ana          │    │
│ │ 12:00  12:30  13:15  14:15  │    │
│ └─────────────────────────────┘    │
│                                     │
│ ┌─────────────────────────────┐    │
│ │ 02:00 PM ✓                  │    │
│ │ María → Juan → Ana          │    │
│ │ 14:00  14:30  15:15  16:15  │    │
│ └─────────────────────────────┘    │
└─────────────────────────────────────┘
```

### Paso 6: Confirmación y Creación

Al confirmar, el sistema crea automáticamente todas las citas:

```
┌─────────────────────────────────────┐
│ ✅ Citas Creadas Exitosamente       │
│                                     │
│ Cita 1:                              │
│ 📅 2025-12-05 10:00 AM              │
│ 👩‍💼 María - Corte de pelo          │
│ ⏱️ 30 minutos                        │
│                                     │
│ Cita 2:                              │
│ 📅 2025-12-05 10:30 AM              │
│ 👨‍💼 Juan - Manicura                 │
│ ⏱️ 45 minutos                        │
│                                     │
│ Cita 3:                              │
│ 📅 2025-12-05 11:15 AM              │
│ 👩‍💼 Ana - Pedicure                  │
│ ⏱️ 60 minutos                        │
└─────────────────────────────────────┘
```

---

## 🔧 Implementación Técnica

### Backend

#### 1. Nuevo Método: `obtenerSlotsCoordinados()`

**Ubicación:** `backend/app/Services/DisponibilidadService.php`

```php
/**
 * Obtener slots coordinados para múltiples servicios con diferentes empleados
 * 
 * @param array $serviciosConEmpleados [
 *   ['servicio_id' => 1, 'empleado_id' => 5, 'duracion' => 30],
 *   ['servicio_id' => 2, 'empleado_id' => 8, 'duracion' => 45],
 * ]
 * @param string $fecha "2025-12-05"
 * @return array
 */
public function obtenerSlotsCoordinados(
    array $serviciosConEmpleados,
    string $fecha
): array
```

**Lógica:**
1. Obtener slots disponibles del primer empleado
2. Para cada slot:
   - Calcular fin del primer servicio
   - Verificar disponibilidad del segundo empleado en ese momento
   - Si disponible, verificar el tercero, y así sucesivamente
   - Si todos encajan, agregar a slots válidos
3. Retornar solo slots válidos

#### 2. Nuevo Endpoint

**Ruta:** `POST /api/publico/disponibilidad/slots-coordinados`

**Request:**
```json
{
  "fecha": "2025-12-05",
  "servicios": [
    {
      "servicio_id": 1,
      "empleado_id": 5
    },
    {
      "servicio_id": 2,
      "empleado_id": 8
    }
  ]
}
```

**Response:**
```json
{
  "success": true,
  "data": {
    "fecha": "2025-12-05",
    "slots_validos": [
      {
        "hora": "10:00",
        "servicios": [
          {
            "empleado_id": 5,
            "servicio_id": 1,
            "inicio": "2025-12-05 10:00:00",
            "fin": "2025-12-05 10:30:00"
          },
          {
            "empleado_id": 8,
            "servicio_id": 2,
            "inicio": "2025-12-05 10:30:00",
            "fin": "2025-12-05 11:15:00"
          }
        ]
      }
    ],
    "total_slots": 1
  }
}
```

#### 3. Creación de Múltiples Citas

**Método:** `agendarMultiplesCitas()`

**Ubicación:** `backend/app/Services/CitaService.php`

```php
/**
 * Agendar múltiples citas coordinadas
 * 
 * @param array $datos {
 *   "cliente_id": 1,
 *   "servicios": [
 *     {"servicio_id": 1, "empleado_id": 5, "fecha_hora": "2025-12-05 10:00:00"},
 *     {"servicio_id": 2, "empleado_id": 8, "fecha_hora": "2025-12-05 10:30:00"}
 *   ],
 *   "notas": "Citas coordinadas"
 * }
 */
public function agendarMultiplesCitas(array $datos): array
```

**Proceso:**
1. Validar que todos los horarios sean válidos
2. Crear cada cita independientemente
3. Vincular las citas con un campo `cita_grupo_id` (opcional)
4. Retornar todas las citas creadas

### Frontend

#### 1. Nuevo Servicio

**Ubicación:** `frontend/src/services/disponibilidadService.ts`

```typescript
interface ServicioConEmpleado {
  servicio_id: number
  empleado_id: number
}

interface SlotCoordinado {
  hora: string
  servicios: Array<{
    empleado_id: number
    servicio_id: number
    inicio: string
    fin: string
  }>
}

async obtenerSlotsCoordinados(
  fecha: string,
  servicios: ServicioConEmpleado[]
): Promise<SlotsCoordinadosResponse>
```

#### 2. Modificación del Store

**Ubicación:** `frontend/src/stores/citas.ts`

**Cambios:**
- Cambiar `empleadoSeleccionado` (único) por `serviciosConEmpleados` (array)
- Agregar método `cargarSlotsCoordinados()`
- Modificar `agendarCita()` para crear múltiples citas

#### 3. Componentes Modificados

**`PasoEmpleado.vue`:**
- Permitir seleccionar empleado por servicio
- Mostrar lista de servicios y empleados disponibles

**`PasoFechaHora.vue`:**
- Llamar a `obtenerSlotsCoordinados()` en lugar de `obtenerSlots()`
- Mostrar información de la cadena de servicios en cada slot

---

## 📊 Ejemplo Completo

### Escenario

**Cliente:** Juan Pérez  
**Servicios:**
1. Corte de pelo (30 min) - Empleado: María
2. Manicura (45 min) - Empleado: Juan
3. Pedicure (60 min) - Empleado: Ana

**Fecha:** 2025-12-05

### Disponibilidad Real

**María (Corte):**
- Horario: 09:00 - 17:00
- Citas existentes: 14:00-15:00

**Juan (Manicura):**
- Horario: 10:00 - 16:00
- Citas existentes: 11:00-12:00

**Ana (Pedicure):**
- Horario: 09:00 - 15:00
- Sin citas

### Proceso de Validación

#### Slot 09:00 AM
```
María: 09:00-09:30 ✓ Disponible
Juan:  09:30-10:15 ✗ Fuera de horario (empieza a las 10:00)
Resultado: ❌ Descartado
```

#### Slot 10:00 AM
```
María: 10:00-10:30 ✓ Disponible
Juan:  10:30-11:15 ✓ Disponible
Ana:   11:15-12:15 ✓ Disponible
Resultado: ✅ VÁLIDO
```

#### Slot 11:00 AM
```
María: 11:00-11:30 ✓ Disponible
Juan:  11:30-12:15 ✗ Tiene cita 11:00-12:00
Resultado: ❌ Descartado
```

#### Slot 12:00 PM
```
María: 12:00-12:30 ✓ Disponible
Juan:  12:30-13:15 ✓ Disponible
Ana:   13:15-14:15 ✓ Disponible
Resultado: ✅ VÁLIDO
```

#### Slot 13:00 PM
```
María: 13:00-13:30 ✓ Disponible
Juan:  13:30-14:15 ✓ Disponible
Ana:   14:15-15:15 ✓ Disponible
Resultado: ✅ VÁLIDO
```

#### Slot 14:00 PM
```
María: 14:00-14:30 ✗ Tiene cita 14:00-15:00
Resultado: ❌ Descartado
```

### Resultado Final

**Slots válidos mostrados al usuario:**
- 10:00 AM
- 12:00 PM
- 01:00 PM

**Citas creadas (si selecciona 10:00 AM):**

```sql
-- Cita 1
INSERT INTO citas (cliente_id, empleado_id, fecha_hora, duracion_total, estado)
VALUES (1, 5, '2025-12-05 10:00:00', 30, 'pendiente');

-- Cita 2
INSERT INTO citas (cliente_id, empleado_id, fecha_hora, duracion_total, estado)
VALUES (1, 8, '2025-12-05 10:30:00', 45, 'pendiente');

-- Cita 3
INSERT INTO citas (cliente_id, empleado_id, fecha_hora, duracion_total, estado)
VALUES (1, 12, '2025-12-05 11:15:00', 60, 'pendiente');
```

---

## 🔌 API Endpoints

### 1. Obtener Slots Coordinados

```http
POST /api/publico/disponibilidad/slots-coordinados
Content-Type: application/json

{
  "fecha": "2025-12-05",
  "servicios": [
    {"servicio_id": 1, "empleado_id": 5},
    {"servicio_id": 2, "empleado_id": 8}
  ]
}
```

**Response:**
```json
{
  "success": true,
  "data": {
    "fecha": "2025-12-05",
    "slots_validos": [
      {
        "hora": "10:00",
        "servicios": [
          {
            "empleado_id": 5,
            "servicio_id": 1,
            "inicio": "2025-12-05 10:00:00",
            "fin": "2025-12-05 10:30:00"
          },
          {
            "empleado_id": 8,
            "servicio_id": 2,
            "inicio": "2025-12-05 10:30:00",
            "fin": "2025-12-05 11:15:00"
          }
        ]
      }
    ],
    "total_slots": 1
  }
}
```

### 2. Agendar Múltiples Citas

```http
POST /api/publico/citas/multiples
Content-Type: application/json

{
  "cliente_nombre": "Juan Pérez",
  "cliente_telefono": "+1234567890",
  "cliente_email": "juan@example.com",
  "codigo_otp": "123456",
  "servicios": [
    {
      "servicio_id": 1,
      "empleado_id": 5,
      "fecha_hora": "2025-12-05 10:00:00"
    },
    {
      "servicio_id": 2,
      "empleado_id": 8,
      "fecha_hora": "2025-12-05 10:30:00"
    }
  ],
  "notas": "Citas coordinadas"
}
```

**Response:**
```json
{
  "success": true,
  "message": "Citas agendadas exitosamente",
  "citas": [
    {
      "id": 101,
      "empleado": {"id": 5, "nombre": "María"},
      "servicios": [{"id": 1, "nombre": "Corte de pelo"}],
      "fecha_hora": "2025-12-05 10:00:00",
      "duracion_total": 30
    },
    {
      "id": 102,
      "empleado": {"id": 8, "nombre": "Juan"},
      "servicios": [{"id": 2, "nombre": "Manicura"}],
      "fecha_hora": "2025-12-05 10:30:00",
      "duracion_total": 45
    }
  ]
}
```

---

## 🎨 Interfaz de Usuario

### Vista: Selección de Empleados por Servicio

```
┌─────────────────────────────────────────────┐
│ Selecciona Empleados                        │
├─────────────────────────────────────────────┤
│                                             │
│ Servicio 1: Corte de pelo                   │
│ ┌─────────────────────────────────────┐   │
│ │ 👩‍💼 María                           │   │
│ │ ⭐ 4.8 | 30 min | $50               │   │
│ │ [Seleccionar]                        │   │
│ └─────────────────────────────────────┘   │
│                                             │
│ Servicio 2: Manicura                        │
│ ┌─────────────────────────────────────┐   │
│ │ 👨‍💼 Juan                            │   │
│ │ ⭐ 4.9 | 45 min | $60               │   │
│ │ [Seleccionar]                        │   │
│ └─────────────────────────────────────┘   │
│                                             │
│ Servicio 3: Pedicure                        │
│ ┌─────────────────────────────────────┐   │
│ │ 👩‍💼 Ana                             │   │
│ │ ⭐ 5.0 | 60 min | $70               │   │
│ │ [Seleccionar]                        │   │
│ └─────────────────────────────────────┘   │
│                                             │
│ [Continuar]                                 │
└─────────────────────────────────────────────┘
```

### Vista: Horarios Coordinados

```
┌─────────────────────────────────────────────┐
│ Horarios Disponibles                        │
│ 2025-12-05                                  │
├─────────────────────────────────────────────┤
│                                             │
│ ┌─────────────────────────────────────┐   │
│ │ 10:00 AM                            │   │
│ │                                     │   │
│ │ Corte → Manicura → Pedicure        │   │
│ │ María  Juan    Ana                 │   │
│ │ 10:00  10:30   11:15  12:15         │   │
│ │                                     │   │
│ │ [Seleccionar]                       │   │
│ └─────────────────────────────────────┘   │
│                                             │
│ ┌─────────────────────────────────────┐   │
│ │ 12:00 PM                            │   │
│ │                                     │   │
│ │ Corte → Manicura → Pedicure        │   │
│ │ María  Juan    Ana                 │   │
│ │ 12:00  12:30   13:15  14:15         │   │
│ │                                     │   │
│ │ [Seleccionar]                       │   │
│ └─────────────────────────────────────┘   │
│                                             │
└─────────────────────────────────────────────┘
```

---

## ⚙️ Configuraciones

### Buffer entre Servicios (Opcional)

Permite agregar tiempo de transición entre servicios:

```php
// En DisponibilidadService.php
private int $bufferEntreServicios = 5; // minutos

// Al calcular horario del siguiente servicio:
$inicioSiguiente = $finActual->copy()->addMinutes($this->bufferEntreServicios);
```

### Tolerancia en Horarios

Configurar si se permite espacio entre servicios:

```php
// Opción 1: Disponibilidad inmediata (estricto)
$inicioSiguiente = $finActual; // Sin espacio

// Opción 2: Permitir espacio (flexible)
$inicioSiguiente = $finActual->copy()->addMinutes(5); // 5 min de buffer
```

---

## 🧪 Casos de Prueba

### Caso 1: Todos los Servicios Encajan

**Input:**
- Servicio 1: Corte (30 min) - María
- Servicio 2: Manicura (45 min) - Juan
- Fecha: 2025-12-05
- Horario seleccionado: 10:00 AM

**Resultado Esperado:**
- ✅ Cita 1 creada: María - 10:00-10:30
- ✅ Cita 2 creada: Juan - 10:30-11:15

### Caso 2: Segundo Servicio No Disponible

**Input:**
- Servicio 1: Corte (30 min) - María
- Servicio 2: Manicura (45 min) - Juan (tiene cita 10:30-11:15)
- Fecha: 2025-12-05
- Horario seleccionado: 10:00 AM

**Resultado Esperado:**
- ❌ Slot 10:00 AM no aparece en la lista
- Mensaje: "No hay horarios disponibles donde todos los servicios encajen"

### Caso 3: Múltiples Servicios (3+)

**Input:**
- Servicio 1: Corte (30 min) - María
- Servicio 2: Manicura (45 min) - Juan
- Servicio 3: Pedicure (60 min) - Ana
- Fecha: 2025-12-05

**Resultado Esperado:**
- Sistema valida los 3 servicios secuencialmente
- Solo muestra horarios donde los 3 encajan

---

## 📝 Notas de Implementación

### Consideraciones

1. **Rendimiento:**
   - Validar múltiples empleados puede ser costoso
   - Considerar cachear resultados por fecha
   - Optimizar consultas de disponibilidad

2. **Concurrencia:**
   - Usar transacciones al crear múltiples citas
   - Verificar disponibilidad justo antes de crear
   - Manejar race conditions

3. **UX:**
   - Mostrar loading mientras valida
   - Mensajes claros cuando no hay horarios
   - Sugerir fechas alternativas

4. **Notificaciones:**
   - Enviar notificación por cada cita creada
   - O una notificación consolidada con todas las citas

### Mejoras Futuras

1. **Servicios Simultáneos:**
   - Permitir servicios que se ejecutan al mismo tiempo
   - Ejemplo: Masaje + Facial (diferentes empleados, mismo horario)

2. **Reagendamiento Coordinado:**
   - Al reagendar una cita, ofrecer reagendar todo el grupo

3. **Cancelación en Cascada:**
   - Opción de cancelar todas las citas relacionadas

4. **Vista Consolidada:**
   - Mostrar todas las citas coordinadas como un "paquete"

---

## 🔗 Archivos Relacionados

### Backend
- `app/Services/DisponibilidadService.php` - Lógica de validación
- `app/Services/CitaService.php` - Creación de citas
- `app/Http/Controllers/Api/DisponibilidadController.php` - Endpoint de slots
- `app/Http/Controllers/Api/AgendamientoPublicoController.php` - Endpoint de creación

### Frontend
- `src/services/disponibilidadService.ts` - Servicio de disponibilidad
- `src/services/citaService.ts` - Servicio de citas
- `src/stores/citas.ts` - Store de estado
- `src/components/agendamiento/PasoEmpleado.vue` - Selección de empleados
- `src/components/agendamiento/PasoFechaHora.vue` - Selección de fecha/hora

---

## 📚 Referencias

- [Documentación de Disponibilidad](./SISTEMA_DISPONIBILIDAD.md)
- [Documentación de Citas](./SISTEMA_CITAS.md)
- [API Documentation](../backend/routes/api.php)

---

**Última actualización:** Diciembre 2025  
**Versión:** 1.0.0

