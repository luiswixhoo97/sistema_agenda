<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cita Cancelada</title>
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
            border-bottom: 2px solid #f44336;
            padding-bottom: 20px;
            margin-bottom: 25px;
        }
        .header h1 {
            color: #f44336;
            margin: 0;
            font-size: 24px;
        }
        .cancel-icon {
            font-size: 48px;
            margin-bottom: 10px;
        }
        .info-box {
            background-color: #ffebee;
            border-radius: 8px;
            padding: 20px;
            margin: 20px 0;
            border-left: 4px solid #f44336;
        }
        .info-row {
            display: flex;
            margin-bottom: 12px;
        }
        .info-label {
            font-weight: bold;
            color: #c62828;
            width: 120px;
        }
        .info-value {
            color: #333;
        }
        .motivo-box {
            background-color: #fff;
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 15px;
            margin: 20px 0;
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
            <div class="cancel-icon">❌</div>
            <h1>Cita Cancelada</h1>
        </div>

        <p>Hola <strong>{{ $cliente_nombre }}</strong>,</p>
        
        <p>Te informamos que tu cita ha sido cancelada.</p>

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
        </div>

        @if(!empty($motivo_cancelacion))
        <div class="motivo-box">
            <strong>Motivo:</strong> {{ $motivo_cancelacion }}
        </div>
        @endif

        <p>Puedes agendar una nueva cita en cualquier momento.</p>

        <p style="text-align: center;">
            <a href="{{ config('app.url') }}/agendar" class="btn">Agendar nueva cita</a>
        </p>

        <div class="footer">
            <p><strong>{{ $negocio_nombre }}</strong></p>
            @if(!empty($negocio_telefono))
            <p>📞 {{ $negocio_telefono }}</p>
            @endif
            <p style="font-size: 12px; color: #999; margin-top: 20px;">
                Lamentamos cualquier inconveniente que esto pueda causar.
            </p>
        </div>
    </div>
</body>
</html>

