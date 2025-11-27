<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Confirmación de Cita</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f5f5f5;
        }
        .container {
            background-color: #ffffff;
            border-radius: 12px;
            padding: 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .header {
            text-align: center;
            border-bottom: 2px solid #e91e63;
            padding-bottom: 20px;
            margin-bottom: 25px;
        }
        .header h1 {
            color: #e91e63;
            margin: 0;
            font-size: 24px;
        }
        .check-icon {
            font-size: 48px;
            margin-bottom: 10px;
        }
        .info-box {
            background-color: #fce4ec;
            border-radius: 8px;
            padding: 20px;
            margin: 20px 0;
        }
        .info-row {
            display: flex;
            margin-bottom: 12px;
        }
        .info-label {
            font-weight: bold;
            color: #c2185b;
            width: 120px;
        }
        .info-value {
            color: #333;
        }
        .footer {
            text-align: center;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #eee;
            color: #666;
            font-size: 14px;
        }
        .btn {
            display: inline-block;
            background-color: #e91e63;
            color: white;
            padding: 12px 30px;
            text-decoration: none;
            border-radius: 25px;
            margin-top: 15px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="check-icon">✅</div>
            <h1>¡Cita Confirmada!</h1>
        </div>

        <p>Hola <strong>{{ $cliente_nombre }}</strong>,</p>
        
        <p>Tu cita ha sido confirmada exitosamente. Aquí están los detalles:</p>

        <div class="info-box">
            <div class="info-row">
                <span class="info-label">📅 Fecha:</span>
                <span class="info-value">{{ $fecha }}</span>
            </div>
            <div class="info-row">
                <span class="info-label">🕐 Hora:</span>
                <span class="info-value">{{ $hora }}</span>
            </div>
            <div class="info-row">
                <span class="info-label">💇 Servicios:</span>
                <span class="info-value">{{ $servicios }}</span>
            </div>
            <div class="info-row">
                <span class="info-label">👩‍💼 Atendido por:</span>
                <span class="info-value">{{ $empleado_nombre }}</span>
            </div>
            <div class="info-row">
                <span class="info-label">⏱️ Duración:</span>
                <span class="info-value">{{ $duracion_total }} minutos</span>
            </div>
            <div class="info-row">
                <span class="info-label">💰 Total:</span>
                <span class="info-value">${{ $precio_total }}</span>
            </div>
        </div>

        @if(!empty($notas))
        <p><strong>Notas:</strong> {{ $notas }}</p>
        @endif

        <p style="text-align: center;">
            <a href="{{ config('app.url') }}/mis-citas" class="btn">Ver mis citas</a>
        </p>

        <div class="footer">
            <p><strong>{{ $negocio_nombre }}</strong></p>
            @if(!empty($negocio_direccion))
            <p>📍 {{ $negocio_direccion }}</p>
            @endif
            @if(!empty($negocio_telefono))
            <p>📞 {{ $negocio_telefono }}</p>
            @endif
            <p style="font-size: 12px; color: #999; margin-top: 20px;">
                Si necesitas modificar o cancelar tu cita, por favor hazlo con al menos 2 horas de anticipación.
            </p>
        </div>
    </div>
</body>
</html>

