# Cambios en Vista de Citas del Empleado

**Fecha:** 16 de Enero 2026

## Archivos Modificados

### Frontend

| Archivo | Descripción |
|---------|-------------|
| `frontend/src/views/empleado/CitasEmpleadoView.vue` | Vista principal de citas para empleados |

### Backend

| Archivo | Descripción |
|---------|-------------|
| `backend/app/Http/Controllers/Api/DisponibilidadController.php` | Controlador de disponibilidad |
| `backend/app/Services/DisponibilidadService.php` | Servicio de cálculo de disponibilidad |

---

## Detalle de Cambios

### 1. CitasEmpleadoView.vue

#### 1.1 Eliminación del botón "Iniciar Cita"
- **Ubicación:** Modal de detalle de cita, sección de acciones
- **Cambio:** Se removió el botón que cambiaba el estado de la cita a "en_proceso"
- **Motivo:** Solicitud del usuario

#### 1.2 Corrección de bug en Reagendar Cita
- **Problema:** Al reagendar una cita, el sistema usaba el `empleado_id` del usuario logueado en vez del empleado asignado a la cita
- **Solución:** Se modificó `cargarHorariosReagendar()` para extraer el `empleado_id` directamente de la cita:
  ```javascript
  const citaEmpleadoId = citaAReagendar.value.empleado_id || 
                         citaAReagendar.value.empleado?.id || 
                         empleadoId.value
  ```

#### 1.3 Mejora en extracción de IDs de servicios
- **Problema:** Los servicios pueden venir en diferentes formatos según la estructura de datos
- **Solución:** Se implementó extracción compatible con múltiples formatos:
  ```javascript
  const serviciosIds = citaAReagendar.value.servicios?.map((s) => {
    return s.servicio_id || s.id || s.servicio?.id || null
  }).filter((id) => id !== null && id !== undefined) || []
  ```

#### 1.4 Logging de depuración agregado
Se agregaron logs detallados en:
- `cargarHorariosDisponibles()` - Para crear nueva cita
- `cargarHorariosReagendar()` - Para reagendar cita existente

**Información logueada:**
- empleado_id usado
- IDs de servicios extraídos
- Fecha seleccionada
- Respuesta del backend (slots, horario del empleado, mensaje)

---

### 2. DisponibilidadController.php

#### Logging en endpoint de empleado
- **Método:** `slotsDisponiblesEmpleado()`
- **Endpoint:** `GET /api/empleado/disponibilidad/slots`

**Logs agregados:**
```php
// Al recibir request
Log::info('🔍 slotsDisponiblesEmpleado - Request recibido', [...]);

// Al obtener resultado
Log::info('✅ slotsDisponiblesEmpleado - Resultado', [...]);

// En caso de error
Log::error('❌ slotsDisponiblesEmpleado - Error', [...]);
```

---

### 3. DisponibilidadService.php

#### Logging en cálculo de duración
- **Método:** `calcularDuracionTotal()`

**Logs agregados:**
```php
Log::info('calcularDuracionTotal', [
    'servicioIds_solicitados' => $servicioIds,
    'servicios_encontrados' => $servicios->count(),
    'servicios_detalle' => [...],
    'duracion_total' => $duracionTotal,
]);
```

---

## Cómo Depurar

### Ver logs del Frontend (Android)

1. Conectar dispositivo Android con USB debugging habilitado
2. Abrir Chrome en la PC
3. Navegar a `chrome://inspect`
4. Encontrar la app y hacer clic en "Inspect"
5. Ver pestaña "Console"

### Ver logs del Backend (Laravel)

```bash
# Desde la carpeta del proyecto
tail -f backend/storage/logs/laravel.log

# O filtrar solo logs de disponibilidad
tail -f backend/storage/logs/laravel.log | grep -E "(slotsDisponibles|calcularDuracion)"
```

---

## Posibles Causas del Problema "Sin horarios disponibles"

1. **El empleado no tiene horario configurado para ese día de la semana**
   - Verificar en la tabla `horarios_empleados`

2. **Los servicios no tienen duración configurada**
   - Verificar columna `duracion` en tabla `servicios`

3. **El empleado_id es incorrecto**
   - Revisar logs para ver qué ID se está enviando

4. **El empleado no tiene los servicios asignados**
   - Verificar tabla pivot `empleado_servicio`

---

## APK Generada

```
frontend/android/app/build/outputs/apk/debug/app-debug.apk
```
