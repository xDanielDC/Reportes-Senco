<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nueva solicitud de cotización</title>
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

        /* ── HERO ── */
        .hero {
            background-color: #FFFFFF;
            padding: 32px 28px 28px;
            border-radius: 10px 10px 0 0;
            border-bottom: 1px solid #EBEBEB;
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
        }

        .hero-content {
            flex: 1;
        }

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

        /* ── CARD ── */
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

        /* ── META TABLE ── */
        .meta-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 28px;
            border: 1px solid #E8E8E8;
        }

        .meta-row { }

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

        .meta-row:last-child .meta-label,
        .meta-row:last-child .meta-value { border-bottom: none; }

        /* ── SECTION HEADING ── */
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

        /* ── PARTS TABLE ── */
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

        .parts-table thead th:last-child {
            border-right: none;
            text-align: center;
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

        .parts-table tbody td:last-child {
            border-right: none;
            text-align: center;
        }

        .parts-table tbody tr:last-child td { border-bottom: none; }

        .empty-state {
            text-align: center;
            padding: 24px;
            background: #FAFAFA;
            border-radius: 8px;
            color: #999;
            font-size: 14px;
            margin-bottom: 28px;
            border: 1px dashed #DDD;
        }

        .divider { border: none; border-top: 1px solid #EBEBEB; margin: 24px 0; }

        /* ── CTA ── */
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

        /* ── FOOTER ── */
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
            .parts-table tbody td {
                padding: 8px 5px;
                font-size: 11px;
                letter-spacing: 0;
            }
        }
    </style>
</head>
<body>
<div class="wrapper">

    <!-- Hero -->
    <div class="hero">
        <table role="presentation" width="100%" cellspacing="0" cellpadding="0">
            <tr>
                <td class="hero-content" valign="top">
                    <p class="hero-eyebrow">Solicitud de cotización</p>
                    <h1 class="hero-title">Visita técnica <span class="visit-num">#{{ $visita->ID }}</span></h1>
                </td>
                <td class="hero-logo" valign="top" align="right" width="92">
                    <img src="{{ $message->embed(public_path('images/logo-black.png')) }}" alt="SENCO" width="78" style="width: 78px; max-width: 78px; height: auto; display: block;" />
                </td>
            </tr>
        </table>
    </div>

    <!-- Card -->
    <div class="card">

        <p class="greeting">
            Hola, <strong>{{ $asesor->name }}</strong>.<br>
            Se ha generado una nueva solicitud de cotización para la visita técnica indicada. A continuación encontrarás el detalle completo.
        </p>

        <!-- Meta -->
        <table class="meta-table">
            <tr class="meta-row">
                <td class="meta-label">Visita</td>
                <td class="meta-value">#{{ $visita->ID }}</td>
            </tr>
            <tr class="meta-row">
                <td class="meta-label">Cliente</td>
                <td class="meta-value">{{ $visita->rutaTecnica?->NombreCliente ?? $visita->CLIENTE ?? 'No disponible' }}</td>
            </tr>
            <tr class="meta-row">
                <td class="meta-label">NIT</td>
                <td class="meta-value">{{ $visita->rutaTecnica?->Nit ?? 'No disponible' }}</td>
            </tr>
            <tr class="meta-row">
                <td class="meta-label">Técnico</td>
                <td class="meta-value">{{ $tecnicoNombre ?? $visita->rutaTecnica?->CodTecnico ?? 'No disponible' }}</td>
            </tr>
        </table>

        <!-- Parts -->
        <p class="section-heading">Repuestos a cotizar</p>

        @if(!empty($repuestos))
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
                    @foreach($repuestos as $repuesto)
                        <tr>
                            <td>{{ $repuesto['nombre'] ?? 'Sin nombre disponible' }}</td>
                            <td>{{ $repuesto['codigo_max'] }}</td>
                            <td>{{ $repuesto['codigo_comodidad'] ?? '—' }}</td>
                            <td>{{ $repuesto['cantidad'] }}</td>
                            <td>{{ $repuesto['observacion'] ?? '—' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @else
            <div class="empty-state">
                No se encontraron repuestos cotizados para esta visita.
            </div>
        @endif

        <hr class="divider">

        <div class="cta-block">
            <a href="{{ $url }}" class="cta-button">GESTIONAR COTIZACION EN SS360 &rarr;</a>
        </div>

    </div>

    <!-- Footer -->
    <div class="footer">
        <p>Mensaje automático generado por SENCO 360 &mdash; Por favor no responder a este correo.</p>
    </div>

</div>
</body>
</html>
