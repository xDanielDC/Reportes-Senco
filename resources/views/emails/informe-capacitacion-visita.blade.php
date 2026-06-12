<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Informe de capacitación</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&display=swap');

        body { margin: 0; padding: 40px 16px; background: #F2F2F2; color: #1a1a1a; font-family: 'DM Sans', Arial, sans-serif; }
        .wrapper { max-width: 680px; margin: 0 auto; }
        .hero { background: #FFFFFF; padding: 30px 28px; border-radius: 10px 10px 0 0; border-bottom: 1px solid #EBEBEB; }
        .eyebrow { margin: 0 0 8px; color: #CC1517; font-size: 11px; font-weight: 700; letter-spacing: 1.5px; text-transform: uppercase; }
        h1 { margin: 0; font-size: 22px; line-height: 1.3; }
        .card { background: #FFFFFF; padding: 28px; border-radius: 0 0 10px 10px; }
        p { margin: 0; color: #444; font-size: 14px; line-height: 1.65; }
        .meta { margin-top: 20px; border: 1px solid #E8E8E8; border-collapse: collapse; width: 100%; }
        .meta td { padding: 11px 16px; border-bottom: 1px solid #EBEBEB; font-size: 13px; }
        .label { width: 36%; background: #F5F5F5; color: #777; font-size: 10.5px; font-weight: 700; letter-spacing: .8px; text-transform: uppercase; }
        .footer { background: #F5F5F5; padding: 16px 28px; text-align: center; border-top: 1px solid #EBEBEB; }
        .footer p { font-size: 11px; color: #666; }
    </style>
</head>
<body>
    <div class="wrapper">
        <div class="hero">
            <p class="eyebrow">Informe técnico</p>
            <h1>Informe de capacitación - Visíta técnica #{{ $visita->ID ?? 'N/A' }}</h1>
        </div>
        <div class="card">
            <p>Hola <b>{{ $visita->rutaTecnica->NombreCliente ?? '—' }}</b>, adjuntamos el informe técnico generado al finalizar la capacitación.</p>
            <table class="meta">
                <tr>
                    <td class="label">Cliente</td>
                    <td>{{ $visita->rutaTecnica->NombreCliente ?? '—' }}</td>
                </tr>
                <tr>
                    <td class="label">NIT</td>
                    <td>{{ $visita->rutaTecnica->Nit ?? '—' }}</td>
                </tr>
                <tr>
                    <td class="label">Tipo de servicio</td>
                    <td>{{ $visita->tipoServicio->TIPO_SERVICIO ?? '—' }}</td>
                </tr>
            </table>
        </div>
        <div class="footer">
            <p>Mensaje automático generado por SENCO 360 — Por favor no responder a este correo.</p>
        </div>
    </div>
</body>
</html>
