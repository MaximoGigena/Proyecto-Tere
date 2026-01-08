<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Informe Quirúrgico</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { text-align: center; background: #8B0000; color: white; padding: 20px; border-radius: 10px 10px 0 0; }
        .content { background: #f9f9f9; padding: 20px; border-radius: 0 0 10px 10px; }
        .footer { text-align: center; margin-top: 20px; padding: 20px; color: #666; font-size: 12px; }
        .info-box { background: white; padding: 15px; margin: 10px 0; border-radius: 5px; border-left: 4px solid #8B0000; }
        .status-badge { display: inline-block; padding: 3px 10px; border-radius: 3px; font-weight: bold; font-size: 14px; }
        .resultado-satisfactorio { background: #e8f5e9; color: #2e7d32; }
        .resultado-complicaciones { background: #ffebee; color: #c62828; }
        .resultado-estable { background: #e3f2fd; color: #1565c0; }
        .resultado-critico { background: #fff3e0; color: #ef6c00; }
        .estado-recuperacion { background: #e3f2fd; color: #1565c0; }
        .estado-alta { background: #e8f5e9; color: #2e7d32; }
        .estado-seguimiento { background: #f3e5f5; color: #7b1fa2; }
        .estado-hospitalizado { background: #ffebee; color: #c62828; }
        .farmaco-list { margin-left: 20px; }
        .farmaco-item { margin-bottom: 10px; padding: 10px; background: #f5f5f5; border-radius: 4px; }
        .etapa-badge { display: inline-block; padding: 2px 8px; border-radius: 3px; font-size: 12px; margin-left: 10px; }
        .etapa-prequirurgica { background: #ffcc80; color: #e65100; }
        .etapa-transquirurgica { background: #80deea; color: #006064; }
        .etapa-postquirurgica_inmediata { background: #c5e1a5; color: #33691e; }
        .etapa-postquirurgica_tardia { background: #e1bee7; color: #4a148c; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🏥 Informe Quirúrgico</h1>
            <p>Sistema Veterinario TERE</p>
        </div>
        
        <div class="content">
            <p>Hola <strong>{{ $tutor->nombre_completo ?? 'Tutor' }}</strong>,</p>
            
            <p>Te enviamos el informe quirúrgico de tu mascota <strong>{{ $mascota->nombre }}</strong>.</p>
            
            <div class="info-box">
                <h3>🔪 Resumen del Procedimiento</h3>
                <p><strong>Mascota:</strong> {{ $mascota->nombre }} ({{ $mascota->especie }})</p>
                <p><strong>Tipo de cirugía:</strong> {{ $cirugia->tipoCirugia->nombre ?? 'No especificado' }}</p>
                <p><strong>Fecha:</strong> {{ \Carbon\Carbon::parse($cirugia->fecha_cirugia)->format('d/m/Y H:i') }}</p>
                <p><strong>Resultado inmediato:</strong> 
                    @php
                        $resultadoClass = 'resultado-' . $cirugia->resultado;
                        $resultadoLabels = [
                            'satisfactorio' => 'Satisfactorio',
                            'complicaciones' => 'Complicaciones',
                            'estable' => 'Estable',
                            'critico' => 'Crítico'
                        ];
                    @endphp
                    <span class="status-badge {{ $resultadoClass }}">
                        {{ $resultadoLabels[$cirugia->resultado] ?? $cirugia->resultado }}
                    </span>
                </p>
                <p><strong>Estado actual:</strong> 
                    @php
                        $estadoClass = 'estado-' . $cirugia->estado_actual;
                        $estadoLabels = [
                            'recuperacion' => 'En recuperación',
                            'alta' => 'Alta postoperatoria',
                            'seguimiento' => 'Bajo seguimiento',
                            'hospitalizado' => 'Hospitalizado'
                        ];
                    @endphp
                    <span class="status-badge {{ $estadoClass }}">
                        {{ $estadoLabels[$cirugia->estado_actual] ?? $cirugia->estado_actual }}
                    </span>
                </p>
                @if($cirugia->fecha_control_estimada)
                <p><strong>Fecha estimada de control:</strong> {{ \Carbon\Carbon::parse($cirugia->fecha_control_estimada)->format('d/m/Y') }}</p>
                @endif
            </div>
            
            @if($cirugia->descripcion_procedimiento)
            <div class="info-box">
                <h3>📝 Descripción del Procedimiento</h3>
                <p>{{ Str::limit($cirugia->descripcion_procedimiento, 300) }}</p>
            </div>
            @endif
            
            @if($cirugia->medicacion_postquirurgica)
            <div class="info-box">
                <h3>💊 Medicación Postquirúrgica</h3>
                <p>{{ Str::limit($cirugia->medicacion_postquirurgica, 300) }}</p>
            </div>
            @endif
            
            @if($cirugia->farmacosAsociados && $cirugia->farmacosAsociados->count() > 0)
            <div class="info-box">
                <h3>💉 Fármacos Asociados</h3>
                <div class="farmaco-list">
                    @foreach($cirugia->farmacosAsociados as $farmaco)
                    <div class="farmaco-item">
                        <strong>{{ $farmaco->tipoFarmaco->nombre_comercial ?? 'Fármaco' }}</strong> 
                        @if($farmaco->etapa_aplicacion)
                            @php
                                $etapaClass = 'etapa-' . $farmaco->etapa_aplicacion;
                                $etapaLabels = [
                                    'prequirurgica' => 'Pre',
                                    'transquirurgica' => 'Trans',
                                    'postquirurgica_inmediata' => 'Post Inm',
                                    'postquirurgica_tardia' => 'Post Tar'
                                ];
                            @endphp
                            <span class="etapa-badge {{ $etapaClass }}">
                                {{ $etapaLabels[$farmaco->etapa_aplicacion] ?? $farmaco->etapa_aplicacion }}
                            </span>
                        @endif
                        <br>
                        Dosis: {{ $farmaco->dosis_prescrita }} {{ $farmaco->unidad_dosis }}
                        @if(!$farmaco->es_dosis_unica && $farmaco->frecuencia_valor)
                            • Frecuencia: {{ $farmaco->frecuencia_valor }} {{ $farmaco->frecuencia_unidad }}
                        @endif
                        @if($farmaco->observaciones)
                            <br><small>{{ $farmaco->observaciones }}</small>
                        @endif
                    </div>
                    @endforeach
                </div>
            </div>
            @endif
            
            @if($cirugia->recomendaciones_tutor)
            <div class="info-box">
                <h3>📋 Recomendaciones</h3>
                <p>{{ Str::limit($cirugia->recomendaciones_tutor, 300) }}</p>
            </div>
            @endif
            
            <p>El informe completo en PDF está adjunto a este email. Guárdalo para tus registros médicos.</p>
            
            <p>Sigue todas las recomendaciones postquirúrgicas y asiste a los controles programados.</p>
            
            <p>¡Pronta recuperación para {{ $mascota->nombre }}! ❤️</p>
        </div>
        
        <div class="footer">
            <p>Este email fue generado automáticamente por el Sistema Veterinario TERE</p>
            <p>© {{ date('Y') }} TERE - Todos los derechos reservados</p>
        </div>
    </div>
</body>
</html>