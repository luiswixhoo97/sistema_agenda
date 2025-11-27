<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recordatorio de Cita</title>
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
            border-bottom: 2px solid #ff9800;
            padding-bottom: 20px;
            margin-bottom: 25px;
        }
        .header h1 {
            color: #ff9800;
            margin: 0;
            font-size: 24px;
        }
        .bell-icon {
            font-size: 48px;
            margin-bottom: 10px;
        }
        .info-box {
            background-color: #fff3e0;
            border-radius: 8px;
            padding: 20px;
            margin: 20px 0;
            border-left: 4px solid #ff9800;
        }
        .info-row {
            display: flex;
            margin-bottom: 12px;
        }
        .info-label {
            font-weight: bold;
            color: #e65100;
            width: 120px;
        }
        .info-value {
            color: #333;
        }
        .highlight {
            background-color: #ff9800;
            color: white;
            padding: 15px;
            border-radius: 8px;
            text-align: center;
            margin: 20px 0;
        }
        .highlight h2 {
            margin: 0;
            font-size: 20px;
        }
        .footer {
            text-align: center;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #eee;
            color: #666;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="bell-icon">⏰</div>
            <h1>Recordatorio de Cita</h1>
        </div>

        <p>Hola <strong>{{ $cliente_nombre }}</strong>,</p>
        
        <div class="highlight">
            <h2>¡Tu cita es mañana!</h2>
        </div>

        <p>Te recordamos los detalles de tu cita:</p>

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
        </div>

        <p>Por favor llega <strong>10 minutos antes</strong> de la hora programada.</p>

        <div class="footer">
            <p><strong>{{ $negocio_nombre }}</strong></p>
            @if(!empty($negocio_direccion))
            <p>📍 {{ $negocio_direccion }}</p>
            @endif
            @if(!empty($negocio_telefono))
            <p>📞 {{ $negocio_telefono }}</p>
            @endif
            <p style="font-size: 12px; color: #999; margin-top: 20px;">
                Si no puedes asistir, por favor cancela tu cita lo antes posible.
            </p>
        </div>
    </div>
</body>
</html>

