<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Certificado Quirúrgico</title>
    <style>
        /* Estilos corregidos para PDF */
        body { 
            font-family: Arial, sans-serif; 
            margin: 0;
            padding: 15px;
            width: 100%;
        }
        
        .header { 
            display: flex;
            align-items: center;
            justify-content: space-between;
            border-bottom: 2px solid #333; 
            padding-bottom: 10px; 
            margin-bottom: 15px; 
            width: 100%;
        }
        
        .logo {
            max-width: 260px;
            max-height: 140px;
        }
        
        .header-title {
            text-align: right;
        }
        
        .header-title h1 {
            margin: 0;
            font-size: 24px;
            color: #8B0000;
        }
        
        .header-title p {
            margin: 3px 0 0 0;
            font-size: 14px;
        }
        
        .section { 
            margin-bottom: 15px; 
            border-bottom: 1px dashed #999;
            padding-bottom: 12px;
            page-break-inside: avoid;
        }
        
        .section:last-of-type {
            border-bottom: none;
            margin-bottom: 5px;
            padding-bottom: 0;
        }
        
        .section h2 {
            margin-top: 0;
            margin-bottom: 10px;
            font-size: 16px;
            color: #2c3e50;
        }
        
        .section p {
            margin: 6px 0;
            font-size: 13px;
        }
        
        .label { 
            font-weight: bold; 
            color: #333;
            width: 160px;
            display: inline-block;
        }
        
        .value { 
            margin-left: 5px; 
        }
        
        .status { 
            display: inline-block; 
            padding: 3px 10px; 
            border-radius: 3px; 
            font-weight: bold; 
            margin-left: 5px;
            font-size: 12px;
        }
        
        .resultado-satisfactorio { background: #e8f5e9; color: #2e7d32; }
        .resultado-complicaciones { background: #ffebee; color: #c62828; }
        .resultado-estable { background: #e3f2fd; color: #1565c0; }
        .resultado-critico { background: #fff3e0; color: #ef6c00; }
        
        .estado-recuperacion { background: #e3f2fd; color: #1565c0; }
        .estado-alta { background: #e8f5e9; color: #2e7d32; }
        .estado-seguimiento { background: #f3e5f5; color: #7b1fa2; }
        .estado-hospitalizado { background: #ffebee; color: #c62828; }
        
        .etapa-badge { 
            display: inline-block; 
            padding: 2px 8px; 
            border-radius: 3px; 
            font-size: 11px; 
        }
        .etapa-prequirurgica { background: #ffcc80; color: #e65100; }
        .etapa-transquirurgica { background: #80deea; color: #006064; }
        .etapa-postquirurgica_inmediata { background: #c5e1a5; color: #33691e; }
        .etapa-postquirurgica_tardia { background: #e1bee7; color: #4a148c; }
        
        .farmaco-table { 
            width: 100%; 
            border-collapse: collapse; 
            margin-top: 10px; 
            font-size: 12px;
        }
        
        .farmaco-table th, .farmaco-table td { 
            border: 1px solid #ddd; 
            padding: 6px; 
            text-align: left; 
        }
        
        .farmaco-table th { 
            background-color: #f2f2f2; 
        }
        
        .farmaco-table td small {
            font-size: 10px;
            color: #666;
        }
        
        .signature { 
            margin-top: 20px; 
            border-top: 1px solid #333; 
            padding-top: 10px; 
            font-size: 11px;
            text-align: right;
            page-break-inside: avoid;
        }
        
        .footer {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            text-align: right;
            font-size: 11px;
            color: #666;
            padding: 8px 15px;
            border-top: 1px solid #ccc;
            background-color: white;
            width: 100%;
            margin-top: 20px;
        }
        
        @page {
            margin: 20mm 15mm 15mm 15mm;
            size: A4;
        }
        
        html, body {
            height: auto;
            min-height: 100%;
            overflow: visible;
        }
        
        .content-wrapper {
            width: 100%;
            margin-bottom: 30px;
        }
    </style>
</head>
<body>
    <div class="content-wrapper">
        <div class="header">
            <div class="logo-container">
                <img src="{{ public_path('storage/images/logo_para_documentacion-removebg-preview.png') }}" alt="Logo Sistema Veterinario TERE" class="logo">
            </div>
            <div class="header-title">
                <h1>Certificado Quirúrgico</h1>
                <p>Sistema Veterinario TERE</p>
            </div>
        </div>

        <!-- SECCIÓN 1: Mascota -->
        <div class="section">
            <h2>Información de la Mascota</h2>
            <p><span class="label">ID Procedimiento:</span> 
                <span class="value">#{{ $cirugia->procesoMedico->id ?? 'No disponible' }}</span>
            </p>
            <p><span class="label">Nombre:</span> <span class="value">{{ $mascota->nombre }}</span></p>
            <p><span class="label">Especie:</span> <span class="value">{{ $mascota->especie }}</span></p>
            <p><span class="label">Raza:</span> <span class="value">{{ $mascota->raza }}</span></p>
            <p><span class="label">Sexo:</span> <span class="value">{{ $mascota->sexo }}</span></p>
            <p><span class="label">Edad:</span> <span class="value">{{ $mascota->edad ?? 'No especificada' }}</span></p>
            @if($mascota->peso)
            <p><span class="label">Peso:</span> <span class="value">{{ $mascota->peso }} kg</span></p>
            @endif
        </div>

        <!-- SECCIÓN 2: Procedimiento -->
        <div class="section">
            <h2>Información del Procedimiento</h2>
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
            <p><span class="label">Diagnóstico/Causa:</span> 
                <span class="value">{{ $cirugia->diagnostico_causa }}</span>
            </p>
            @if($cirugia->fecha_control_estimada)
            <p><span class="label">Fecha control estimada:</span> 
                <span class="value">{{ \Carbon\Carbon::parse($cirugia->fecha_control_estimada)->format('d/m/Y') }}</span>
            </p>
            @endif
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
        </div>

        <!-- SECCIÓN 3: Descripción (si existe) -->
        @if($cirugia->descripcion_procedimiento)
        <div class="section">
            <h2>Descripción del Procedimiento</h2>
            <p>{{ nl2br(e($cirugia->descripcion_procedimiento)) }}</p>
        </div>
        @endif

        <!-- SECCIÓN 4: Fármacos Asociados (si existen) -->
        @if($cirugia->farmacosAsociados && $cirugia->farmacosAsociados->count() > 0)
        <div class="section">
            <h2>Fármacos Asociados</h2>
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
                                <br><small>Cada {{ $farmaco->frecuencia_valor }} {{ $farmaco->frecuencia_unidad }}</small>
                            @endif
                            @if(!$farmaco->es_dosis_unica && $farmaco->duracion_valor)
                                <br><small>Durante {{ $farmaco->duracion_valor }} {{ $farmaco->duracion_unidad }}</small>
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

        <!-- SECCIÓN 5: Medicación Postquirúrgica (si existe) -->
        @if($cirugia->medicacion_postquirurgica)
        <div class="section">
            <h2>Medicación Postquirúrgica</h2>
            <p>{{ nl2br(e($cirugia->medicacion_postquirurgica)) }}</p>
        </div>
        @endif

        <!-- SECCIÓN 6: Recomendaciones al Tutor (si existen) -->
        @if($cirugia->recomendaciones_tutor)
        <div class="section">
            <h2>Recomendaciones al Tutor</h2>
            <p>{{ nl2br(e($cirugia->recomendaciones_tutor)) }}</p>
        </div>
        @endif

        <!-- SECCIÓN 7: Veterinario -->
        <div class="section">
            <h2>Datos del Veterinario</h2>
            <p><span class="label">Nombre:</span> 
                <span class="value">{{ $cirugia->procesoMedico->veterinario->name ?? 'No especificado' }}</span>
            </p>
            <p><span class="label">Matrícula:</span> 
                <span class="value">
                    {{ $cirugia->procesoMedico->veterinario->matricula ?? 'No especificada' }}
                </span>
            </p>
        </div>

        <!-- SECCIÓN 8: Centro Veterinario (condicional) -->
        @if($centroVeterinario)
        <div class="section">
            <h2>Centro Veterinario</h2>
            <p><span class="label">Nombre:</span> <span class="value">{{ $centroVeterinario->nombre }}</span></p>
            <p><span class="label">Dirección:</span> <span class="value">{{ $centroVeterinario->direccion }}</span></p>
            @if($centroVeterinario->telefono)
            <p><span class="label">Teléfono:</span> <span class="value">{{ $centroVeterinario->telefono }}</span></p>
            @endif
        </div>
        @endif

        <!-- SECCIÓN 9: Tutor -->
        <div class="section">
            <h2>Información del Tutor</h2>
            <p><span class="label">Nombre:</span> <span class="value">{{ $tutor->nombre_completo }}</span></p>
            <p><span class="label">Email:</span> <span class="value">{{ $tutor->email }}</span></p>
            <p><span class="label">Teléfono:</span> <span class="value">{{ $tutor->telefono }}</span></p>
        </div>

        <!-- Firma y fecha de emisión -->
        <div class="signature">
            <p>Documento generado automáticamente el {{ $fecha_emision }}</p>
        </div>
    </div>

    <div class="footer">
        <span>Página 1 de 1</span>
    </div>
</body>
</html>