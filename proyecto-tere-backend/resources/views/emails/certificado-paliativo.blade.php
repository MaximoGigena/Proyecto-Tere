<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Certificado de Procedimiento Paliativo</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { text-align: center; background: #8B4513; color: white; padding: 20px; border-radius: 10px 10px 0 0; }
        .content { background: #f9f9f9; padding: 20px; border-radius: 0 0 10px 10px; }
        .footer { text-align: center; margin-top: 20px; padding: 20px; color: #666; font-size: 12px; }
        .info-box { background: white; padding: 15px; margin: 10px 0; border-radius: 5px; border-left: 4px solid #8B4513; }
        .status-badge { display: inline-block; padding: 3px 10px; border-radius: 3px; font-weight: bold; font-size: 14px; }
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
        .farmaco-list { margin-left: 20px; }
        .farmaco-item { margin-bottom: 10px; padding: 10px; background: #f5f5f5; border-radius: 4px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🩺 Certificado de Procedimiento Paliativo</h1>
            <p>Sistema Veterinario TERE</p>
        </div>
        
        <div class="content">
            <p>Hola <strong>{{ $tutor->nombre_completo ?? 'Tutor' }}</strong>,</p>
            
            <p>Te enviamos el certificado del procedimiento paliativo aplicado a tu mascota <strong>{{ $mascota->nombre }}</strong>.</p>
            
            <div class="info-box">
                <h3>📋 Información del Procedimiento</h3>
                <p><strong>Mascota:</strong> {{ $mascota->nombre }} ({{ $mascota->especie }})</p>
                <p><strong>Tipo de procedimiento:</strong> {{ $paliativo->tipoPaliativo->nombre ?? 'No especificado' }}</p>
                <p><strong>Fecha de inicio:</strong> {{ \Carbon\Carbon::parse($paliativo->fecha_inicio)->format('d/m/Y H:i') }}</p>
                <p><strong>Resultado observado:</strong> 
                    @php
                        $resultadoClass = 'resultado-' . $paliativo->resultado;
                        $resultadoLabels = [
                            'mejoria' => 'Mejoría evidente',
                            'alivio' => 'Alivio parcial',
                            'estabilizacion' => 'Estabilización',
                            'sin_cambio' => 'Sin cambios',
                            'empeoramiento' => 'Empeoramiento'
                        ];
                    @endphp
                    <span class="status-badge {{ $resultadoClass }}">
                        {{ $resultadoLabels[$paliativo->resultado] ?? $paliativo->resultado }}
                    </span>
                </p>
                <p><strong>Estado de la mascota:</strong> 
                    @php
                        $estadoClass = 'estado-' . $paliativo->estado_mascota;
                        $estadoLabels = [
                            'estable' => 'Estable',
                            'dolor_controlado' => 'Con dolor controlado',
                            'dolor_parcial' => 'Con dolor parcialmente controlado',
                            'deterioro' => 'En deterioro',
                            'critico' => 'Crítico'
                        ];
                    @endphp
                    <span class="status-badge {{ $estadoClass }}">
                        {{ $estadoLabels[$paliativo->estado_mascota] ?? $paliativo->estado_mascota }}
                    </span>
                </p>
                @if($paliativo->frecuencia_valor && $paliativo->frecuencia_unidad)
                <p><strong>Frecuencia de seguimiento:</strong> Cada {{ $paliativo->frecuencia_valor }} {{ $paliativo->frecuencia_unidad }}</p>
                @endif
                @if($paliativo->fecha_control)
                <p><strong>Fecha de control:</strong> {{ \Carbon\Carbon::parse($paliativo->fecha_control)->format('d/m/Y') }}</p>
                @endif
            </div>
            
            @if($paliativo->diagnostico_base && $paliativo->diagnostico_base != 'Sin diagnóstico específico')
            <div class="info-box">
                <h3>🏥 Diagnóstico Base</h3>
                <p>{{ $paliativo->diagnostico_base }}</p>
            </div>
            @endif
            
            @if($paliativo->observaciones)
            <div class="info-box">
                <h3>📝 Descripción del Procedimiento</h3>
                <p>{{ Str::limit($paliativo->observaciones, 300) }}</p>
            </div>
            @endif
            
            @if($paliativo->medicacion_complementaria)
            <div class="info-box">
                <h3>💊 Medicación Complementaria</h3>
                <p>{{ Str::limit($paliativo->medicacion_complementaria, 300) }}</p>
            </div>
            @endif
            
            @if($paliativo->farmacosAsociados && $paliativo->farmacosAsociados->count() > 0)
            <div class="info-box">
                <h3>💉 Fármacos Asociados</h3>
                <div class="farmaco-list">
                    @foreach($paliativo->farmacosAsociados as $farmaco)
                    <div class="farmaco-item">
                        <strong>{{ $farmaco->tipoFarmaco->nombre_comercial ?? 'Fármaco' }}</strong> 
                        @if($farmaco->momento_aplicacion)
                            @php
                                $momentoClass = 'momento-badge momento-' . $farmaco->momento_aplicacion;
                                $momentoLabels = [
                                    'inicio' => 'Inicio',
                                    'mantenimiento' => 'Mantenimiento',
                                    'rescue' => 'Rescate',
                                    'final' => 'Final'
                                ];
                            @endphp
                            <span class="{{ $momentoClass }}">
                                {{ $momentoLabels[$farmaco->momento_aplicacion] ?? $farmaco->momento_aplicacion }}
                            </span>
                        @endif
                        <br>
                        Dosis: {{ $farmaco->dosis_prescrita }} {{ $farmaco->unidad_dosis }}
                        @if(!$farmaco->es_dosis_unica && $farmaco->frecuencia_valor)
                            • Frecuencia: Cada {{ $farmaco->frecuencia_valor }} {{ $farmaco->frecuencia_unidad }}
                        @endif
                        @if(!$farmaco->es_dosis_unica && $farmaco->duracion_valor)
                            • Duración: {{ $farmaco->duracion_valor }} {{ $farmaco->duracion_unidad }}
                        @endif
                        @if($farmaco->observaciones)
                            <br><small>{{ $farmaco->observaciones }}</small>
                        @endif
                    </div>
                    @endforeach
                </div>
            </div>
            @endif
            
            @if($paliativo->recomendaciones_tutor)
            <div class="info-box">
                <h3>📋 Recomendaciones</h3>
                <p>{{ Str::limit($paliativo->recomendaciones_tutor, 300) }}</p>
            </div>
            @endif
            
            <p>El certificado completo en PDF está adjunto a este email. Guárdalo para tus registros médicos.</p>
            
            <p>Si notas algún cambio en el estado de tu mascota, contacta inmediatamente con tu veterinario.</p>
            
            <p>¡Cuidamos juntos a {{ $mascota->nombre }}! ❤️</p>
        </div>
        
        <div class="footer">
            <p>Este email fue generado automáticamente por el Sistema Veterinario TERE</p>
            <p>© {{ date('Y') }} TERE - Todos los derechos reservados</p>
        </div>
    </div>
</body>
</html>