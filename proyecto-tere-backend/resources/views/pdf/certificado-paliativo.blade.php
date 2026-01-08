<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Certificado de Procedimiento Paliativo</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .header { text-align: center; border-bottom: 2px solid #8B4513; padding-bottom: 10px; margin-bottom: 20px; }
        .header h1 { color: #8B4513; margin-bottom: 5px; }
        .section { margin-bottom: 15px; padding: 15px; background: #f9f9f9; border-radius: 5px; }
        .section h2 { color: #333; border-bottom: 1px solid #ddd; padding-bottom: 5px; margin-top: 0; }
        .label { font-weight: bold; color: #333; }
        .value { margin-left: 10px; }
        .footer { margin-top: 30px; text-align: center; font-size: 12px; color: #666; }
        .signature { margin-top: 50px; border-top: 1px solid #333; padding-top: 10px; text-align: center; }
        .status { display: inline-block; padding: 3px 10px; border-radius: 3px; font-weight: bold; margin-left: 10px; }
        .resultado-mejoria { background: #e8f5e9; color: #2e7d32; }
        .resultado-alivio { background: #fff3e0; color: #ef6c00; }
        .resultado-estabilizacion { background: #e3f2fd; color: #1565c0; }
        .resultado-sin_cambio { background: #f5f5f5; color: #666; }
        .resultado-empeoramiento { background: #ffebee; color: #c62828; }
        .estado-estable { background: #e8f5e9; color: #2e7d32; }
        .estado-dolor_controlado { background: #fff3e0; color: #ef6c00; }
        .estado-dolor_parcial { background: #ffebee; color: #c62828; }
        .estado-deterioro { background: #fce4ec; color: #c2185b; }
        .estado-critico { background: #ffebee; color: #c62828; }
        .momento-badge { display: inline-block; padding: 2px 8px; border-radius: 3px; font-size: 12px; margin-left: 10px; }
        .momento-inicio { background: #c5e1a5; color: #33691e; }
        .momento-mantenimiento { background: #80deea; color: #006064; }
        .momento-rescue { background: #ffcc80; color: #e65100; }
        .momento-final { background: #e1bee7; color: #4a148c; }
        .farmaco-table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        .farmaco-table th, .farmaco-table td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        .farmaco-table th { background-color: #f2f2f2; }
        .logo { text-align: center; margin-bottom: 10px; }
        .logo-text { font-size: 24px; font-weight: bold; color: #8B4513; }
        .watermark { opacity: 0.05; font-size: 72px; position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%); z-index: -1; }
    </style>
</head>
<body>
    <div class="watermark">PALIATIVO</div>
    
    <div class="logo">
        <div class="logo-text">TERE</div>
        <div>Sistema Veterinario</div>
    </div>

    <div class="header">
        <h1>🩺 CERTIFICADO DE PROCEDIMIENTO PALIATIVO</h1>
        <p>Documento Oficial - Cuidados Paliativos Veterinarios</p>
    </div>

    <div class="section">
        <h2>📋 Información de la Mascota</h2>
        <p><span class="label">Nombre:</span> <span class="value">{{ $mascota->nombre }}</span></p>
        <p><span class="label">Especie:</span> <span class="value">{{ $mascota->especie }}</span></p>
        <p><span class="label">Raza:</span> <span class="value">{{ $mascota->raza }}</span></p>
        <p><span class="label">Sexo:</span> <span class="value">{{ $mascota->sexo }}</span></p>
        <p><span class="label">Edad:</span> <span class="value">{{ $mascota->edad ?? 'No especificada' }}</span></p>
        @if($mascota->peso)
        <p><span class="label">Peso:</span> <span class="value">{{ $mascota->peso }} kg</span></p>
        @endif
    </div>

    <div class="section">
        <h2>🩺 Información del Procedimiento</h2>
        <p><span class="label">Tipo de procedimiento:</span> 
            <span class="value">
                @if($paliativo->tipoPaliativo && $paliativo->tipoPaliativo->nombre)
                    {{ $paliativo->tipoPaliativo->nombre }}
                @else
                    Tipo no especificado
                @endif
            </span>
        </p>
        <p><span class="label">Fecha y hora de inicio:</span> 
            <span class="value">{{ \Carbon\Carbon::parse($paliativo->fecha_inicio)->format('d/m/Y H:i') }}</span>
        </p>
        <p><span class="label">Resultado observado:</span> 
            <span class="value">
                @php
                    $resultadoClass = 'resultado-' . $paliativo->resultado;
                @endphp
                <span class="status {{ $resultadoClass }}">
                    {{ $resultadoLabels[$paliativo->resultado] ?? $paliativo->resultado }}
                </span>
            </span>
        </p>
        <p><span class="label">Estado de la mascota:</span> 
            <span class="value">
                @php
                    $estadoClass = 'estado-' . $paliativo->estado_mascota;
                @endphp
                <span class="status {{ $estadoClass }}">
                    {{ $estadoLabels[$paliativo->estado_mascota] ?? $paliativo->estado_mascota }}
                </span>
            </span>
        </p>
        @if($paliativo->diagnostico_base && $paliativo->diagnostico_base != 'Sin diagnóstico específico')
        <p><span class="label">Diagnóstico base:</span> 
            <span class="value">{{ $paliativo->diagnostico_base }}</span>
        </p>
        @endif
        @if($paliativo->frecuencia_valor && $paliativo->frecuencia_unidad)
        <p><span class="label">Frecuencia de seguimiento:</span> 
            <span class="value">Cada {{ $paliativo->frecuencia_valor }} {{ $frecuenciaUnidadLabels[$paliativo->frecuencia_unidad] ?? $paliativo->frecuencia_unidad }}</span>
        </p>
        @endif
        @if($paliativo->fecha_control)
        <p><span class="label">Fecha de control:</span> 
            <span class="value">{{ \Carbon\Carbon::parse($paliativo->fecha_control)->format('d/m/Y') }}</span>
        </p>
        @endif
    </div>

    @if($paliativo->observaciones)
    <div class="section">
        <h2>📝 Descripción del Procedimiento</h2>
        <p>{{ nl2br(e($paliativo->observaciones)) }}</p>
    </div>
    @endif

    @if($paliativo->farmacosAsociados && $paliativo->farmacosAsociados->count() > 0)
    <div class="section">
        <h2>💉 Fármacos Asociados</h2>
        <table class="farmaco-table">
            <thead>
                <tr>
                    <th>Fármaco</th>
                    <th>Dosis</th>
                    <th>Momento</th>
                    <th>Frecuencia/Duración</th>
                    <th>Observaciones</th>
                </tr>
            </thead>
            <tbody>
                @foreach($paliativo->farmacosAsociados as $farmaco)
                <tr>
                    <td>
                        {{ $farmaco->tipoFarmaco->nombre_comercial ?? 'No especificado' }}
                        @if($farmaco->tipoFarmaco->nombre_generico)
                            <br><small>({{ $farmaco->tipoFarmaco->nombre_generico }})</small>
                        @endif
                    </td>
                    <td>
                        {{ $farmaco->dosis_prescrita }} {{ $farmaco->unidad_dosis }}
                    </td>
                    <td>
                        @if($farmaco->momento_aplicacion)
                            @php
                                $momentoClass = 'momento-badge momento-' . $farmaco->momento_aplicacion;
                            @endphp
                            <span class="{{ $momentoClass }}">
                                {{ $momentoLabels[$farmaco->momento_aplicacion] ?? $farmaco->momento_aplicacion }}
                            </span>
                        @endif
                    </td>
                    <td>
                        @if(!$farmaco->es_dosis_unica)
                            @if($farmaco->frecuencia_valor)
                                Cada {{ $farmaco->frecuencia_valor }} {{ $farmaco->frecuencia_unidad }}<br>
                            @endif
                            @if($farmaco->duracion_valor)
                                Durante {{ $farmaco->duracion_valor }} {{ $farmaco->duracion_unidad }}
                            @endif
                        @else
                            Dosis única
                        @endif
                    </td>
                    <td>{{ $farmaco->observaciones ?? '-' }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif

    @if($paliativo->medicacion_complementaria)
    <div class="section">
        <h2>💊 Medicación Complementaria</h2>
        <p>{{ nl2br(e($paliativo->medicacion_complementaria)) }}</p>
    </div>
    @endif

    @if($paliativo->recomendaciones_tutor)
    <div class="section">
        <h2>📋 Recomendaciones al Tutor</h2>
        <p>{{ nl2br(e($paliativo->recomendaciones_tutor)) }}</p>
    </div>
    @endif

    @if($centroVeterinario)
    <div class="section">
        <h2>🏥 Centro Veterinario</h2>
        <p><span class="label">Nombre:</span> <span class="value">{{ $centroVeterinario->nombre }}</span></p>
        <p><span class="label">Dirección:</span> <span class="value">{{ $centroVeterinario->direccion }}</span></p>
        @if($centroVeterinario->telefono)
        <p><span class="label">Teléfono:</span> <span class="value">{{ $centroVeterinario->telefono }}</span></p>
        @endif
    </div>
    @endif

    <div class="section">
        <h2>👤 Información del Tutor</h2>
        <p><span class="label">Nombre:</span> <span class="value">{{ $tutor->nombre_completo }}</span></p>
        <p><span class="label">Email:</span> <span class="value">{{ $tutor->email }}</span></p>
        <p><span class="label">Teléfono:</span> <span class="value">{{ $tutor->telefono }}</span></p>
    </div>

    <div class="signature">
        <p><strong>Documento oficial emitido el:</strong> {{ $fecha_emision }}</p>
        <p><strong>Veterinario responsable:</strong> {{ $paliativo->procesoMedico->veterinario->name ?? 'No especificado' }}</p>
        <p>________________________________________</p>
        <p>Firma del veterinario</p>
    </div>

    <div class="footer">
        <p>Este documento ha sido generado automáticamente por el Sistema Veterinario TERE</p>
        <p>Todos los derechos reservados © {{ date('Y') }}</p>
    </div>
</body>
</html>