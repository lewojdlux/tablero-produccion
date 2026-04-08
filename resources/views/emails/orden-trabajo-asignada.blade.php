<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: Arial, sans-serif; background: #f4f4f4; margin: 0; padding: 0; }
        .container { max-width: 600px; margin: 30px auto; background: #fff; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
        .header { background: #1a73e8; color: white; padding: 24px 32px; }
        .header h1 { margin: 0; font-size: 22px; }
        .body { padding: 32px; color: #333; }
        .field { margin-bottom: 12px; }
        .label { font-weight: bold; color: #555; font-size: 13px; text-transform: uppercase; }
        .value { font-size: 15px; margin-top: 2px; }
        .badge { display: inline-block; background: #e8f0fe; color: #1a73e8; padding: 4px 12px; border-radius: 20px; font-size: 13px; font-weight: bold; }
        .footer { background: #f9f9f9; padding: 16px 32px; font-size: 12px; color: #888; border-top: 1px solid #eee; }
    </style>
</head>
<body>
<div class="container">
    <div class="header">
        <h1>📋 Nueva Orden de Trabajo</h1>
        <p style="margin:4px 0 0; opacity:.85;">Se te ha asignado una nueva OT</p>
    </div>
    <div class="body">
        <p>Hola, te informamos que se ha registrado una nueva Orden de Trabajo a tu nombre:</p>

        <div class="field">
            <div class="label">N° Documento / OT</div>
            <div class="value"><span class="badge"># {{ $ordenData['n_documento'] }}</span></div>
        </div>
        <div class="field">
            <div class="label">Cliente</div>
            <div class="value">{{ $ordenData['tercero'] }}</div>
        </div>
        <div class="field">
            <div class="label">Asesor / Vendedor</div>
            <div class="value">{{ $ordenData['vendedor'] }} ({{ $ordenData['vendedor_username'] ?? $ordenData['codigo_asesor'] ?? '-' }})</div>
        </div>
        <div class="field">
            <div class="label">N° Factura</div>
            <div class="value">{{ $ordenData['n_factura'] ?? '-' }}</div>
        </div>
        <div class="field">
            <div class="label">Período</div>
            <div class="value">{{ $ordenData['periodo'] ?? '-' }} / {{ $ordenData['ano'] ?? '-' }}</div>
        </div>
        <div class="field">
            <div class="label">Estado</div>
            <div class="value">{{ $ordenData['status'] ?? '-' }}</div>
        </div>
        @if(!empty($ordenData['description']))
        <div class="field">
            <div class="label">Observaciones</div>
            <div class="value">{{ $ordenData['description'] }}</div>
        </div>
        @endif

        <p style="margin-top:24px; color:#555;">Por favor revisa el sistema para más detalles y coordina la instalación a la brevedad posible.</p>
    </div>
    <div class="footer">
        Este correo fue generado automáticamente. Por favor no respondas a este mensaje.
    </div>
</div>
</body>
</html>
