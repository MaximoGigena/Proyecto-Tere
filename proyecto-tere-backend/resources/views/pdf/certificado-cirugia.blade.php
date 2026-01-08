<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Informe Quirúrgico</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .header { text-align: center; border-bottom: 2px solid #8B0000; padding-bottom: 10px; margin-bottom: 20px; }
        .header h1 { color: #8B0000; margin-bottom: 5px; }
        .section { margin-bottom: 15px; padding: 15px; background: #f9f9f9; border-radius: 5px; }
        .section h2 { color: #333; border-bottom: 1px solid #ddd; padding-bottom: 5px; margin-top: 0; }
        .label { font-weight: bold; color: #333; }
        .value { margin-left: 10px; }
        .footer { margin-top: 30px; text-align: center; font-size: 12px; color: #666; }
        .signature { margin-top: 50px; border-top: 1px solid #333; padding-top: 10px; text-align: center; }
        .status { display: inline-block; padding: 3px 10px; border-radius: 3px; font-weight: bold; margin-left: 10px; }
        .resultado-satisfactorio { background: #e8f5e9; color: #2e7d32; }
        .resultado-complicaciones { background: #ffebee; color: #c62828; }
        .resultado-estable { background: #e3f2fd; color: #1565c0; }
        .resultado-critico { background: #fff3e0; color: #ef6c00; }
        .estado-recuperacion { background: #e3f2fd; color: #1565c0; }
        .estado-alta { background: #e8f5e9; color: #2e7d32; }
        .estado-seguimiento { background: #f3e5f5; color: #7b1fa2; }
        .estado-hospitalizado { background: #ffebee; color: #c62828; }
        .etapa-badge { display: inline-block; padding: 2px 8px; border-radius: 3px; font-size: 12px; margin-left: 10px; }
        .etapa-prequirurgica { background: #ffcc80; color: #e65100; }
        .etapa-transquirurgica { background: #80deea; color: #006064; }
        .etapa-postquirurgica_inmediata { background: #c5e1a5; color: #33691e; }
        .etapa-postquirurgica_tardia { background: #e1bee7; color: #4a148c; }
        .farmaco-table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        .farmaco-table th, .farmaco-table td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        .farmaco-table th { background-color: #f2f2f2; }
        .logo { text-align: center; margin-bottom: 10px; }
        .logo-text { font-size: 24px; font-weight: bold; color: #8B0000; }
    </style>
</head>
<body>
    <div class="logo">
        <div class="logo-text">TERE</div>
        <div>Sistema Veterinario</div>
    </div>

    <div class="header">
        <h1>🏥 INFORME QUIRÚRGICO</h1>
        <p>Documento Oficial - Procedimiento Quirúrgico</p>
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
        <h2>🔪 Información del Procedimiento</h2>
        <p><span class="label">Tipo de cirugía:</span> 
            <span class="value">
                @if($cirugia->tipoCirugia && $cirugia->tipoCirugia->nombre)
                    {{ $cirugia->tipoCirugia->nombre }}
                @else
                    Tipo no especificado
                @endif
            </span>
        </p>
        <p><span class="label">Fecha y hora:</span> 
            <span class="value">{{ \Carbon\Carbon::parse($cirugia->fecha_cirugia)->format('d/m/Y H:i') }}</span>
        </p>
        <p><span class="label">Resultado inmediato:</span> 
            <span class="value">
                @php
                    $resultadoClass = 'resultado-' . $cirugia->resultado;
                @endphp
                <span class="status {{ $resultadoClass }}">
                    {{ $resultadoLabels[$cirugia->resultado] ?? $cirugia->resultado }}
                </span>
            </span>
        </p>
        <p><span class="label">Estado actual:</span> 
            <span class="value">
                @php
                    $estadoClass = 'estado-' . $cirugia->estado_actual;
                @endphp
                <span class="status {{ $estadoClass }}">
                    {{ $estadoLabels[$cirugia->estado_actual] ?? $cirugia->estado_actual }}
                </span>
            </span>
        </p>
        <p><span class="label">Diagnóstico/Causa:</span> 
            <span class="value">{{ $cirugia->diagnostico_causa }}</span>
        </p>
        @if($cirugia->fecha_control_estimada)
        <p><span class="label">Fecha estimada de control:</span> 
            <span class="value">{{ \Carbon\Carbon::parse($cirugia->fecha_control_estimada)->format('d/m/Y') }}</span>
        </p>
        @endif
    </div>

    @if($cirugia->descripcion_procedimiento)
    <div class="section">
        <h2>📝 Descripción del Procedimiento</h2>
        <p>{{ nl2br(e($cirugia->descripcion_procedimiento)) }}</p>
    </div>
    @endif

    @if($cirugia->farmacosAsociados && $cirugia->farmacosAsociados->count() > 0)
    <div class="section">
        <h2>💉 Fármacos Asociados</h2>
        <table class="farmaco-table">
            <thead>
                <tr>
                    <th>Fármaco</th>
                    <th>Dosis</th>
                    <th>Etapa</th>
                    <th>Observaciones</th>
                </tr>
            </thead>
            <tbody>
                @foreach($cirugia->farmacosAsociados as $farmaco)
                <tr>
                    <td>
                        {{ $farmaco->tipoFarmaco->nombre_comercial ?? 'No especificado' }}
                        @if($farmaco->tipoFarmaco->nombre_generico)
                            <br><small>({{ $farmaco->tipoFarmaco->nombre_generico }})</small>
                        @endif
                    </td>
                    <td>
                        {{ $farmaco->dosis_prescrita }} {{ $farmaco->unidad_dosis }}
                        @if(!$farmaco->es_dosis_unica && $farmaco->frecuencia_valor)
                            <br>Cada {{ $farmaco->frecuencia_valor }} {{ $farmaco->frecuencia_unidad }}
                        @endif
                        @if(!$farmaco->es_dosis_unica && $farmaco->duracion_valor)
                            <br>Durante {{ $farmaco->duracion_valor }} {{ $farmaco->duracion_unidad }}
                        @endif
                    </td>
                    <td>
                        @if($farmaco->etapa_aplicacion)
                            @php
                                $etapaClass = 'etapa-badge etapa-' . $farmaco->etapa_aplicacion;
                            @endphp
                            <span class="{{ $etapaClass }}">
                                {{ $etapaLabels[$farmaco->etapa_aplicacion] ?? $farmaco->etapa_aplicacion }}
                            </span>
                        @endif
                    </td>
                    <td>{{ $farmaco->observaciones ?? '-' }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif

    @if($cirugia->medicacion_postquirurgica)
    <div class="section">
        <h2>💊 Medicación Postquirúrgica</h2>
        <p>{{ nl2br(e($cirugia->medicacion_postquirurgica)) }}</p>
    </div>
    @endif

    @if($cirugia->recomendaciones_tutor)
    <div class="section">
        <h2>📋 Recomendaciones al Tutor</h2>
        <p>{{ nl2br(e($cirugia->recomendaciones_tutor)) }}</p>
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
        <p><strong>Veterinario responsable:</strong> {{ $cirugia->procesoMedico->veterinario->name ?? 'No especificado' }}</p>
        <p>________________________________________</p>
        <p>Firma del veterinario</p>
    </div>

    <div class="footer">
        <p>Este documento ha sido generado automáticamente por el Sistema Veterinario TERE</p>
        <p>Todos los derechos reservados © {{ date('Y') }}</p>
    </div>
</body>
</html>