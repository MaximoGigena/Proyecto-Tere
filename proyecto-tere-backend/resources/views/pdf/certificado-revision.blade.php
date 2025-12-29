<!DOCTYPE html>
<!-- resources/views/pdf/certificado-revision.blade.php -->
<html>
<head>
    <meta charset="utf-8">
    <title>Certificado de Revisión Médica - {{ $mascota->nombre }}</title>
    <style>
        body { font-family: 'DejaVu Sans', sans-serif; margin: 20px; }
        .header { text-align: center; border-bottom: 3px solid #667eea; padding-bottom: 15px; margin-bottom: 30px; }
        .header h1 { color: #667eea; margin: 0; }
        .header h2 { color: #4a5568; font-size: 18px; margin: 5px 0; }
        .section { margin-bottom: 25px; page-break-inside: avoid; }
        .section-title { color: #2d3748; font-size: 16px; font-weight: bold; margin-bottom: 10px; padding-bottom: 5px; border-bottom: 1px solid #e2e8f0; }
        .info-table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        .info-table td { padding: 8px 10px; border-bottom: 1px solid #e2e8f0; }
        .info-table .label { width: 40%; font-weight: bold; color: #4a5568; }
        .info-table .value { width: 60%; }
        .urgency-badge { display: inline-block; padding: 4px 12px; border-radius: 4px; font-size: 12px; font-weight: bold; margin-left: 10px; }
        .rutinaria { background-color: #c6f6d5; color: #22543d; }
        .preventiva { background-color: #bee3f8; color: #2c5282; }
        .urgencia { background-color: #fed7d7; color: #742a2a; }
        .emergencia { background-color: #fc8181; color: #fff; }
        .content-box { background: #f7fafc; padding: 15px; border-radius: 5px; margin: 10px 0; border-left: 4px solid #667eea; }
        .footer { margin-top: 50px; text-align: center; font-size: 11px; color: #718096; border-top: 1px solid #e2e8f0; padding-top: 10px; }
        .signature { margin-top: 40px; padding-top: 20px; border-top: 1px solid #2d3748; }
        .signature-line { width: 200px; border-top: 1px solid #2d3748; margin: 30px auto 0; padding-top: 5px; }
    </style>
</head>
<body>
    <div class="header">
        <h1>🏥 CERTIFICADO DE REVISIÓN MÉDICA</h1>
        <h2>Sistema Veterinario TERE</h2>
        <p style="color: #718096; font-size: 14px;">
            Generado el {{ \Carbon\Carbon::now()->format('d/m/Y H:i') }}
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

    <!-- Datos de la revisión -->
    <div class="section">
        <div class="section-title">
            📋 DETALLES DE LA REVISIÓN
            <span class="urgency-badge {{ $revision->nivel_urgencia }}">
                {{ $urgenciaLabels[$revision->nivel_urgencia] ?? strtoupper($revision->nivel_urgencia) }}
            </span>
        </div>
        <table class="info-table">
            <tr>
                <td class="label">Tipo de Revisión:</td>
                <td class="value">{{ $revision->tipoRevision->nombre ?? 'No especificado' }}</td>
            </tr>
            <tr>
                <td class="label">Fecha y Hora:</td>
                <td class="value">{{ \Carbon\Carbon::parse($revision->fecha_revision)->format('d/m/Y H:i') }}</td>
            </tr>
            @if($centroVeterinario)
            <tr>
                <td class="label">Centro Veterinario:</td>
                <td class="value">{{ $centroVeterinario->nombre }}</td>
            </tr>
            <tr>
                <td class="label">Dirección:</td>
                <!-- CAMBIO AQUÍ: usar $centroVeterinario en lugar de $revision->centroVeterinario -->
                <td class="value">{{ $centroVeterinario->direccion }}</td>
            </tr>
            @endif
        </table>
    </div>

    <!-- Motivo y diagnóstico -->
    @if($revision->motivo_consulta)
    <div class="section">
        <div class="section-title">📝 MOTIVO DE LA CONSULTA</div>
        <div class="content-box">{{ $revision->motivo_consulta }}</div>
    </div>
    @endif

    @if($revision->diagnostico)
    <div class="section">
        <div class="section-title">🔍 DIAGNÓSTICO</div>
        <div class="content-box">{{ $revision->diagnostico }}</div>
    </div>
    @endif

    <!-- Indicaciones médicas -->
    @if($revision->indicaciones_medicas)
    <div class="section">
        <div class="section-title">💊 INDICACIONES MÉDICAS</div>
        <div class="content-box">{{ $revision->indicaciones_medicas }}</div>
    </div>
    @endif

    <!-- Recomendaciones -->
    @if($revision->recomendaciones_tutor)
    <div class="section">
        <div class="section-title">💡 RECOMENDACIONES AL TUTOR</div>
        <div class="content-box">{{ $revision->recomendaciones_tutor }}</div>
    </div>
    @endif

    <!-- Próxima revisión -->
    @if($revision->fecha_proxima_revision)
    <div class="section">
        <div class="section-title">📅 PRÓXIMA REVISIÓN SUGERIDA</div>
        <table class="info-table">
            <tr>
                <td class="label">Fecha sugerida:</td>
                <td class="value" style="font-weight: bold;">
                    {{ \Carbon\Carbon::parse($revision->fecha_proxima_revision)->format('d/m/Y') }}
                </td>
            </tr>
        </table>
    </div>
    @endif

    <div class="footer">
        <p>Este es un documento oficial del Sistema Veterinario TERE</p>
        <p>ID de Revisión: {{ $revision->id }} | ID de Mascota: {{ $mascota->id }}</p>
        <p>© {{ date('Y') }} TERE - Sistema Veterinario Integral</p>
    </div>
</body>
</html>