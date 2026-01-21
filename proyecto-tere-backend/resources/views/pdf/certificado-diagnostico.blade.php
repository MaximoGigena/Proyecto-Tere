<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Diagnóstico Médico</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .header { text-align: center; border-bottom: 2px solid #333; padding-bottom: 10px; margin-bottom: 20px; }
        .section { margin-bottom: 15px; padding: 10px; background: #f9f9f9; border-radius: 5px; }
        .label { font-weight: bold; color: #333; }
        .value { margin-left: 10px; }
        .footer { margin-top: 30px; text-align: center; font-size: 12px; color: #666; }
        .signature { margin-top: 50px; border-top: 1px solid #333; padding-top: 10px; }
        .status { display: inline-block; padding: 3px 10px; border-radius: 3px; font-weight: bold; }
        .status-activo { background: #ffebee; color: #c62828; }
        .status-resuelto { background: #e8f5e9; color: #2e7d32; }
        .status-cronico { background: #fff3e0; color: #ef6c00; }
        .status-seguimiento { background: #e3f2fd; color: #1565c0; }
        .status-sospecha { background: #f3e5f5; color: #7b1fa2; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Diagnóstico Médico</h1>
        <p>Sistema Veterinario TERE</p>
    </div>

    <div class="section">
        <h2>Información de la Mascota</h2>
        <p><span class="label">Nombre:</span> <span class="value">{{ $mascota->nombre }}</span></p>
        <p><span class="label">Especie:</span> <span class="value">{{ $mascota->especie }}</span></p>
        <p><span class="label">Raza:</span> <span class="value">{{ $mascota->raza }}</span></p>
        <p><span class="label">Edad:</span> <span class="value">{{ $mascota->edad ?? 'No especificada' }}</span></p>
    </div>

    <div class="section">
        <h2>Información del Diagnóstico</h2>
        <p><span class="label">Nombre del diagnóstico:</span> <span class="value">{{ $diagnostico->nombre }}</span></p>
        <p><span class="label">Tipo:</span> 
            <span class="value">
                @if($diagnostico->tipoDiagnostico && $diagnostico->tipoDiagnostico->nombre)
                    {{ $diagnostico->tipoDiagnostico->nombre }}
                @else
                    Tipo no especificado
                @endif
            </span>
        </p>
        <p><span class="label">Fecha de diagnóstico:</span> <span class="value">{{ \Carbon\Carbon::parse($diagnostico->fecha_diagnostico)->format('d/m/Y') }}</span></p>
        <p><span class="label">Estado:</span> 
            <span class="value">
                @php
                    $statusClass = 'status-' . $diagnostico->estado;
                @endphp
                <span class="status {{ $statusClass }}">
                    {{ $estadoLabels[$diagnostico->estado] ?? $diagnostico->estado }}
                </span>
            </span>
        </p>
    </div>

    @if($diagnostico->diagnosticos_diferenciales)
    <div class="section">
        <h2>Diagnósticos Diferenciales</h2>
        <p>{{ nl2br(e($diagnostico->diagnosticos_diferenciales)) }}</p>
    </div>
    @endif

    @if($diagnostico->examenes_complementarios)
    <div class="section">
        <h2>Exámenes Complementarios</h2>
        <p>{{ nl2br(e($diagnostico->examenes_complementarios)) }}</p>
    </div>
    @endif

    @if($diagnostico->conducta_terapeutica)
    <div class="section">
        <h2>Conducta Terapéutica Sugerida</h2>
        <p>{{ nl2br(e($diagnostico->conducta_terapeutica)) }}</p>
    </div>
    @endif

    @if($diagnostico->observaciones)
    <div class="section">
        <h2>Observaciones</h2>
        <p>{{ nl2br(e($diagnostico->observaciones)) }}</p>
    </div>
    @endif

    @if($centroVeterinario)
    <div class="section">
        <h2>Centro Veterinario</h2>
        <p><span class="label">Nombre:</span> <span class="value">{{ $centroVeterinario->nombre }}</span></p>
        <p><span class="label">Dirección:</span> <span class="value">{{ $centroVeterinario->direccion }}</span></p>
    </div>
    @endif

    <div class="section">
        <h2>Información del Tutor</h2>
        <p><span class="label">Nombre:</span> <span class="value">{{ $tutor->nombre_completo }}</span></p>
        <p><span class="label">Email:</span> <span class="value">{{ $tutor->email }}</span></p>
        <p><span class="label">Teléfono:</span> <span class="value">{{ $tutor->telefono }}</span></p>
    </div>

    <div class="signature">
        <p>Documento generado automáticamente el {{ $fecha_emision }}</p>
    </div>

    <div class="footer">
        <p>Este es un documento oficial del Sistema Veterinario TERE</p>
    </div>
</body>
</html>