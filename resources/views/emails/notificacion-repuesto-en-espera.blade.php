<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Repuesto en espera</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&display=swap');

        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'DM Sans', Arial, sans-serif;
            background-color: #F2F2F2;
            color: #1a1a1a;
            padding: 40px 16px;
            -webkit-font-smoothing: antialiased;
        }

        .wrapper { max-width: 700px; margin: 0 auto; }

        .hero {
            background-color: #FFFFFF;
            padding: 32px 28px 28px;
            border-radius: 10px 10px 0 0;
            border-bottom: 1px solid #EBEBEB;
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
        }

        .hero-content { flex: 1; }

        .hero-logo {
            flex-shrink: 0;
            align-self: flex-start;
            margin-left: 18px;
            text-align: right;
        }

        .hero-logo img {
            height: 22px;
            width: auto;
            display: block;
        }

        .hero-eyebrow {
            font-size: 11px;
            font-weight: 600;
            letter-spacing: 1.5px;
            text-transform: uppercase;
            color: #CC1517;
            margin-bottom: 8px;
        }

        .hero-title {
            font-size: 22px;
            font-weight: 700;
            color: #1a1a1a;
            line-height: 1.3;
        }

        .hero-title .visit-num { color: #CC1517; }

        .card { background: #FFFFFF; padding: 32px 28px; border-radius: 0 0 10px 10px; }

        .greeting {
            font-size: 14.5px;
            color: #444;
            margin-bottom: 24px;
            line-height: 1.65;
            padding-bottom: 20px;
            border-bottom: 1px solid #EBEBEB;
        }

        .greeting strong { color: #1a1a1a; }

        .meta-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 28px;
            border: 1px solid #E8E8E8;
        }

        .meta-label {
            padding: 11px 16px;
            font-size: 10.5px;
            font-weight: 700;
            letter-spacing: 0.8px;
            text-transform: uppercase;
            color: #999;
            background: #F5F5F5;
            border-bottom: 1px solid #EBEBEB;
            border-right: 1px solid #EBEBEB;
        }

        .meta-value {
            padding: 11px 16px;
            font-size: 14px;
            font-weight: 500;
            color: #1a1a1a;
            border-bottom: 1px solid #EBEBEB;
        }

        .section-heading {
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 1px;
            text-transform: uppercase;
            color: #CC1517;
            margin-bottom: 12px;
            padding-bottom: 8px;
            border-bottom: 2px solid #CC1517;
            display: inline-block;
        }

        .parts-table {
            width: 100%;
            table-layout: fixed;
            border-collapse: collapse;
            margin-bottom: 28px;
            border: 1px solid #E8E8E8;
        }

        .parts-table thead th {
            padding: 10px 8px;
            text-align: left;
            font-size: 9.5px;
            font-weight: 700;
            letter-spacing: 0.4px;
            text-transform: uppercase;
            color: #1a1a1a;
            background: #F5F5F5;
            border-bottom: 1px solid #EBEBEB;
            border-right: 1px solid #EBEBEB;
        }

        .parts-table tbody td {
            padding: 10px 8px;
            border-bottom: 1px solid #EBEBEB;
            color: #333;
            font-size: 12.5px;
            line-height: 1.35;
            border-right: 1px solid #EBEBEB;
            overflow-wrap: anywhere;
            word-break: break-word;
        }

        .parts-table tbody td:last-child { border-right: none; }

        .empty-state {
            text-align: center;
            padding: 24px;
            background: #FAFAFA;
            color: #999;
            font-size: 14px;
        }

        .cta-block { text-align: center; margin: 8px 0 4px; }

        .cta-button {
            display: inline-block;
            background-color: #CC1517;
            color: #FFFFFF;
            text-decoration: none;
            font-size: 13px;
            font-weight: 700;
            letter-spacing: 0.8px;
            text-transform: uppercase;
            padding: 14px 36px;
            border-radius: 6px;
        }

        .footer {
            background-color: #F5F5F5;
            padding: 16px 28px;
            text-align: center;
            border-top: 1px solid #EBEBEB;
        }

        .footer p { font-size: 11px; color: #666; letter-spacing: 0.3px; }

        @media only screen and (max-width: 520px) {
            body { padding: 18px 8px; }
            .hero { padding: 24px 20px 22px; }
            .card { padding: 26px 20px; }
            .parts-table thead th,
            .parts-table tbody td { padding: 8px 5px; font-size: 11px; }
        }
    </style>
</head>
<body>
    <div class="wrapper">
        <div class="hero">
            <table role="presentation" width="100%" cellspacing="0" cellpadding="0">
                <tr>
                    <td class="hero-content" valign="top">
                        <p class="hero-eyebrow">Notificación de repuestos</p>
                        <h1 class="hero-title">Repuesto en espera - Visita <span class="visit-num">#{{ $visita->ID ?? 'N/A' }}</span></h1>
                    </td>
                    <td class="hero-logo" valign="top" align="right" width="92">
                        <img src="{{ $message->embed(public_path('images/logo-black.png')) }}" alt="SENCO" width="78" style="width: 78px; max-width: 78px; height: auto; display: block;" />
                    </td>
                </tr>
            </table>
        </div>

        <div class="card">
            <p class="greeting">
                Hola <strong>{{ $tecnico->name ?? 'Técnico' }}</strong>.<br> Uno o más repuestos de esta visita pasaron a estado en espera. Revisa la observación registrada para continuar con la gestión correspondiente.
            </p>

            @if(!empty($visita))
                <table class="meta-table">
                    <tr class="meta-row">
                        <td class="meta-label">Cliente</td>
                        <td class="meta-value">{{ $visita->rutaTecnica->NombreCliente ?? '—' }}</td>
                    </tr>
                    <tr class="meta-row">
                        <td class="meta-label">NIT</td>
                        <td class="meta-value">{{ $visita->rutaTecnica->Nit ?? '—' }}</td>
                    </tr>
                    <tr class="meta-row">
                        <td class="meta-label">Asesor</td>
                        <td class="meta-value">{{ $asesor->name ?? $asesor->username ?? $visita->rutaTecnica?->CodVendedor ?? '—' }}</td>
                    </tr>
                </table>
            @endif

            <h2 class="section-heading">Repuestos en espera</h2>

            <table class="parts-table">
                <colgroup>
                    <col style="width: 32%;">
                    <col style="width: 16%;">
                    <col style="width: 18%;">
                    <col style="width: 10%;">
                    <col style="width: 24%;">
                </colgroup>
                <thead>
                    <tr>
                        <th>Nombre</th>
                        <th>Cód. Max</th>
                        <th>Cód. Comodidad</th>
                        <th>Cant.</th>
                        <th>Observ.</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($repuestos as $repuesto)
                        <tr>
                            <td>{{ $repuesto['nombre'] ?? '—' }}</td>
                            <td>{{ $repuesto['codigo_max'] ?? '—' }}</td>
                            <td>{{ $repuesto['codigo_comodidad'] ?? '—' }}</td>
                            <td>{{ $repuesto['cantidad'] ?? '—' }}</td>
                            <td>{{ $repuesto['observacion'] ?? '—' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="empty-state">No hay repuestos asociados.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>

            <div class="cta-block">
                <a href="{{ $url }}" class="cta-button">VER DETALLES EN SS360 →</a>
            </div>
        </div>

        <div class="footer">
            <p>Mensaje automático generado por SENCO 360 — Por favor no responder a este correo.</p>
        </div>
    </div>
</body>
</html>
