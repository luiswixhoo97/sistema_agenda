# Cómo Levantar los Servicios de WhatsApp

Los mensajes de WhatsApp se procesan mediante **colas (queues)** en Laravel. Para que funcionen, necesitas tener el **worker de colas** corriendo.

## Opción 1: Usar el script de desarrollo (Recomendado)

El proyecto incluye un script que levanta todos los servicios necesarios:

```bash
cd backend
composer run dev
```

Este comando levanta automáticamente:
- ✅ Servidor Laravel (`php artisan serve`)
- ✅ Worker de colas (`php artisan queue:listen`)
- ✅ Logs en tiempo real (`php artisan pail`)
- ✅ Servidor Vite para frontend (`npm run dev`)

## Opción 2: Levantar solo el worker de colas

Si solo necesitas el worker de WhatsApp:

```bash
cd backend
php artisan queue:work
```

O si quieres que se reinicie automáticamente cuando hay cambios:

```bash
cd backend
php artisan queue:listen
```

## Opción 3: Worker en producción

Para producción, usa `queue:work` con opciones adicionales:

```bash
php artisan queue:work --tries=3 --timeout=90
```

O mejor aún, usa un supervisor como **Supervisor** o **PM2** para mantener el worker corriendo.

## Verificar que está funcionando

1. **Verifica los logs:**
   ```bash
   php artisan pail
   ```
   O revisa `storage/logs/laravel.log`

2. **Revisa las colas pendientes:**
   ```bash
   php artisan queue:monitor
   ```

3. **Revisa trabajos fallidos:**
   ```bash
   php artisan queue:failed
   ```

## Variables de Entorno Requeridas

Asegúrate de tener estas variables en tu `.env`:

```env
# WasenderAPI
WASENDERAPI_API_KEY=tu_api_key_aqui
WASENDERAPI_BASE_URL=https://www.wasenderapi.com/api

# Colas
QUEUE_CONNECTION=database
```

## Solución de Problemas

### Los mensajes no se envían

1. Verifica que el worker esté corriendo:
   ```bash
   ps aux | grep "queue:work"
   ```

2. Verifica que haya trabajos en la cola:
   ```bash
   php artisan tinker
   >>> DB::table('jobs')->count();
   ```

3. Revisa los trabajos fallidos:
   ```bash
   php artisan queue:failed
   ```

### El worker se detiene

- En desarrollo, usa `queue:listen` que se reinicia automáticamente
- En producción, usa un supervisor como Supervisor o PM2

### Limpiar trabajos fallidos

```bash
php artisan queue:flush
```

## Notas Importantes

- ⚠️ El worker debe estar **siempre corriendo** para que se envíen los mensajes de WhatsApp
- 📝 Los mensajes se procesan de forma asíncrona (en segundo plano)
- 🔄 Si el worker se detiene, los mensajes quedarán en la cola hasta que lo vuelvas a levantar
- 🚀 En producción, configura un supervisor para que el worker se reinicie automáticamente si se cae

