<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Registro de Alergia - {{ $mascota->nombre }}</title>
    <style>
        body { font-family: 'DejaVu Sans', sans-serif; margin: 20px; }
        .header { text-align: center; border-bottom: 3px solid #ed8936; padding-bottom: 15px; margin-bottom: 30px; }
        .header h1 { color: #ed8936; margin: 0; }
        .header h2 { color: #4a5568; font-size: 18px; margin: 5px 0; }
        .section { margin-bottom: 25px; page-break-inside: avoid; }
        .section-title { color: #2d3748; font-size: 16px; font-weight: bold; margin-bottom: 10px; padding-bottom: 5px; border-bottom: 1px solid #e2e8f0; }
        .info-table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        .info-table td { padding: 8px 10px; border-bottom: 1px solid #e2e8f0; }
        .info-table .label { width: 40%; font-weight: bold; color: #4a5568; }
        .info-table .value { width: 60%; }
        .gravedad-badge { display: inline-block; padding: 4px 12px; border-radius: 4px; font-size: 12px; font-weight: bold; margin-left: 10px; }
        .leve { background-color: #c6f6d5; color: #22543d; }
        .moderada { background-color: #feebc8; color: #744210; }
        .grave { background-color: #fed7d7; color: #742a2a; }
        .estado-badge { display: inline-block; padding: 4px 12px; border-radius: 4px; font-size: 12px; font-weight: bold; margin-left: 5px; }
        .activa { background-color: #fed7d7; color: #742a2a; }
        .superada { background-color: #c6f6d5; color: #22543d; }
        .seguimiento { background-color: #bee3f8; color: #2c5282; }
        .content-box { background: #f7fafc; padding: 15px; border-radius: 5px; margin: 10px 0; border-left: 4px solid #ed8936; }
        .warning-box { background: #fffaf0; padding: 15px; border-radius: 5px; margin: 20px 0; border: 1px solid #fbd38d; }
        .warning-title { color: #dd6b20; font-weight: bold; margin-bottom: 10px; }
        .footer { margin-top: 50px; text-align: center; font-size: 11px; color: #718096; border-top: 1px solid #e2e8f0; padding-top: 10px; }
        .signature-line { width: 200px; border-top: 1px solid #2d3748; margin: 30px auto 0; padding-top: 5px; }
        .qrcode { text-align: center; margin: 20px 0; }
    </style>
</head>
<body>
    <div class="header">
        <h1>⚠️ REGISTRO OFICIAL DE ALERGIA/SENSIBILIDAD</h1>
        <h2>Sistema Veterinario TERE</h2>
        <p style="color: #718096; font-size: 14px;">
            Generado el {{ $fecha_emision }}
        </p>
    </div>

    <!-- Datos de la mascota -->
    <div class="section">
        <div class="section-title">🐾 DATOS DE LA MASCOTA</div>
        <table class="info-table">
            <tr>
                <td class="label">Nombre:</td>
                <td class="value">{{ $mascota->nombre }}</td>
            </tr>
            <tr>
                <td class="label">Especie:</td>
                <td class="value">{{ $mascota->especie }}</td>
            </tr>
            <tr>
                <td class="label">Raza:</td>
                <td class="value">{{ $mascota->raza }}</td>
            </tr>
            <tr>
                <td class="label">Edad:</td>
                <td class="value">{{ $mascota->edad }} años</td>
            </tr>
            <tr>
                <td class="label">Propietario:</td>
                <td class="value">{{ $tutor->nombre_completo }}</td>
            </tr>
        </table>
    </div>

    <!-- Información de la alergia -->
    <div class="section">
        <div class="section-title">
            🤧 INFORMACIÓN DE LA ALERGIA
            <span class="gravedad-badge {{ $alergia->gravedad }}">
                {{ $gravedadLabels[$alergia->gravedad] ?? strtoupper($alergia->gravedad) }}
            </span>
            <span class="estado-badge {{ $alergia->estado }}">
                {{ $estadoLabels[$alergia->estado] ?? strtoupper($alergia->estado) }}
            </span>
        </div>
        <table class="info-table">
            <tr>
                <td class="label">Tipo de Alergia:</td>
                <td class="value">{{ $alergia->tipoAlergia->nombre ?? 'No especificado' }}</td>
            </tr>
            <tr>
                <td class="label">Fecha de Detección:</td>
                <td class="value">{{ \Carbon\Carbon::parse($alergia->fecha_deteccion)->format('d/m/Y') }}</td>
            </tr>
            <tr>
                <td class="label">Reacción Común:</td>
                <td class="value">{{ $alergia->reaccion_comun }}</td>
            </tr>
            @if($alergia->desencadenante)
            <tr>
                <td class="label">Desencadenante:</td>
                <td class="value">{{ $alergia->desencadenante }}</td>
            </tr>
            @endif
        </table>
    </div>

    <!-- Centro veterinario -->
    @if($centroVeterinario)
    <div class="section">
        <div class="section-title">🏥 CENTRO VETERINARIO</div>
        <table class="info-table">
            <tr>
                <td class="label">Nombre:</td>
                <td class="value">{{ $centroVeterinario->nombre }}</td>
            </tr>
            <tr>
                <td class="label">Dirección:</td>
                <td class="value">{{ $centroVeterinario->direccion }}</td>
            </tr>
            <tr>
                <td class="label">Teléfono:</td>
                <td class="value">{{ $centroVeterinario->telefono ?? 'No disponible' }}</td>
            </tr>
        </table>
    </div>
    @endif

    <!-- Conducta recomendada -->
    @if($alergia->conducta_recomendada)
    <div class="section">
        <div class="section-title">💡 CONDUCTA RECOMENDADA</div>
        <div class="content-box">{{ $alergia->conducta_recomendada }}</div>
    </div>
    @endif

    <!-- Recomendaciones al tutor -->
    @if($alergia->recomendaciones_tutor)
    <div class="section">
        <div class="section-title">👨‍⚕️ RECOMENDACIONES AL TUTOR</div>
        <div class="content-box">{{ $alergia->recomendaciones_tutor }}</div>
    </div>
    @endif

    <!-- Observaciones -->
    @if($alergia->observaciones)
    <div class="section">
        <div class="section-title">📝 OBSERVACIONES</div>
        <div class="content-box">{{ $alergia->observaciones }}</div>
    </div>
    @endif

    <!-- Advertencias importantes -->
    <div class="warning-box">
        <div class="warning-title">⚠️ INSTRUCCIONES IMPORTANTES</div>
        <ul style="margin-left: 20px;">
            <li>Este documento debe ser presentado en todas las visitas veterinarias.</li>
            <li>Informe a cualquier profesional que atienda a su mascota sobre esta alergia.</li>
            <li>Mantenga un registro de posibles reacciones o cambios.</li>
            <li>En caso de reacción alérgica grave, acuda inmediatamente al veterinario.</li>
            <li>Siga estrictamente las recomendaciones médicas proporcionadas.</li>
        </ul>
    </div>

    <div class="footer">
        <p>Este es un documento oficial del Sistema Veterinario TERE</p>
        <p>ID de Alergia: {{ $alergia->id }} | ID de Mascota: {{ $mascota->id }}</p>
        <p>© {{ date('Y') }} TERE - Sistema Veterinario Integral</p>
    </div>
</body>
</html>