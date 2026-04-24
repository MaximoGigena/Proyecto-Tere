<!DOCTYPE html>
<!-- resources/views/pdf/certificado-paliativo.blade.php -->
<html>
<head>
    <meta charset="utf-8">
    <title>Certificado de Procedimiento Paliativo - {{ $mascota->nombre }}</title>
    <style>
        /* Estilos corregidos para PDF - SIGUIENDO EL ESTÁNDAR DEL SISTEMA */
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
            color: #7C3AED;
        }
        
        .header-title p {
            margin: 3px 0 0 0;
            font-size: 14px;
            color: #6B7280;
        }
        
        /* Línea divisoria entre secciones */
        .section { 
            margin-bottom: 15px; 
            border-bottom: 1px dashed #999;
            padding-bottom: 12px;
            page-break-inside: avoid;
        }
        
        /* La última sección no necesita línea divisoria */
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
            background: #F3F4F6;
            padding: 8px 12px;
            border-left: 4px solid #7C3AED;
        }
        
        .section p {
            margin: 6px 0;
            font-size: 13px;
        }
        
        .label { 
            font-weight: bold; 
            color: #333;
            width: 180px;
            display: inline-block;
        }
        
        .value { 
            margin-left: 5px; 
            color: #111827;
        }
        
        /* Estilos para el contenido de texto largo */
        .content-box {
            background: #F9FAFB; 
            padding: 10px; 
            border-radius: 5px; 
            border: 1px solid #E5E7EB;
            margin-top: 5px;
            margin-bottom: 10px;
            font-size: 12px;
            line-height: 1.5;
        }
        
        /* Badges de estado - adaptados con los colores originales */
        .status-badge { 
            display: inline-block; 
            padding: 3px 10px; 
            border-radius: 20px; 
            font-size: 11px; 
            font-weight: bold; 
            margin-left: 5px;
        }
        
        .resultado-mejoria { 
            background: #e8f5e9; 
            color: #2e7d32; 
        }
        
        .resultado-alivio { 
            background: #fff3e0; 
            color: #ef6c00; 
        }
        
        .resultado-estabilizacion { 
            background: #e3f2fd; 
            color: #1565c0; 
        }
        
        .resultado-sin_cambio { 
            background: #f5f5f5; 
            color: #666; 
        }
        
        .resultado-empeoramiento { 
            background: #ffebee; 
            color: #c62828; 
        }
        
        .estado-estable { 
            background: #e8f5e9; 
            color: #2e7d32; 
        }
        
        .estado-dolor_controlado { 
            background: #fff3e0; 
            color: #ef6c00; 
        }
        
        .estado-dolor_parcial { 
            background: #ffebee; 
            color: #c62828; 
        }
        
        .estado-deterioro { 
            background: #fce4ec; 
            color: #c2185b; 
        }
        
        .estado-critico { 
            background: #ffebee; 
            color: #c62828; 
        }
        
        /* Badges para momentos de aplicación */
        .momento-badge { 
            display: inline-block; 
            padding: 2px 8px; 
            border-radius: 12px; 
            font-size: 10px; 
            font-weight: bold;
        }
        
        .momento-inicio { 
            background: #c5e1a5; 
            color: #33691e; 
        }
        
        .momento-mantenimiento { 
            background: #80deea; 
            color: #006064; 
        }
        
        .momento-rescue { 
            background: #ffcc80; 
            color: #e65100; 
        }
        
        .momento-final { 
            background: #e1bee7; 
            color: #4a148c; 
        }
        
        /* Tabla de fármacos */
        .farmaco-table { 
            width: 100%; 
            border-collapse: collapse; 
            margin-top: 10px; 
            font-size: 11px;
        }
        
        .farmaco-table th { 
            border: 1px solid #ddd; 
            padding: 8px; 
            text-align: left; 
            background-color: #F3F4F6;
            font-weight: bold;
        }
        
        .farmaco-table td { 
            border: 1px solid #ddd; 
            padding: 8px; 
            text-align: left; 
            vertical-align: top;
        }
        
        .farmaco-table tr:nth-child(even) {
            background-color: #F9FAFB;
        }
        
        .farmaco-table small {
            color: #6B7280;
            font-size: 10px;
        }
        
        /* Firma */
        .signature-area { 
            margin-top: 25px; 
            page-break-inside: avoid;
        }
        
        .signature-line { 
            width: 300px; 
            border-top: 1px solid #333; 
            margin: 20px auto 5px; 
        }
        
        .stamp { 
            text-align: center; 
            margin-top: 25px; 
        }
        
        .stamp div {
            font-size: 12px;
            color: #4B5563;
        }
        
        .stamp .center-name {
            font-weight: bold;
            color: #7C3AED;
            margin-top: 5px;
        }
        
        /* Pie de página corregido */
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
        
        /* Configuración de página corregida */
        @page {
            margin: 20mm 15mm 15mm 15mm;
            size: A4;
        }
        
        /* Aseguramos que todo el contenido sea visible */
        html, body {
            height: auto;
            min-height: 100%;
            overflow: visible;
        }
        
        /* Espaciado general */
        .content-wrapper {
            width: 100%;
            margin-bottom: 30px;
        }
        
        /* Texto pequeño para detalles adicionales */
        .text-small {
            font-size: 11px;
            color: #6B7280;
        }
    </style>
</head>
<body>
    <div class="content-wrapper">
        <div class="header">
            <div class="logo-container">
                <!-- Misma ruta de logo que en los otros certificados -->
                <img src="{{ public_path('storage/images/logo_para_documentacion-removebg-preview.png') }}" alt="Logo Sistema Veterinario TERE" class="logo">
            </div>
            <div class="header-title">
                <h1>Procedimiento Paliativo</h1>
                <p>Sistema Veterinario TERE</p>
                <p class="text-small">{{ $fecha_emision }}</p>
            </div>
        </div>

        <!-- SECCIÓN 1: Mascota -->
        <div class="section">
            <h2>Información de la Mascota</h2>
            <p><span class="label">Nombre:</span> <span class="value">{{ $mascota->nombre }}</span></p>
            <p><span class="label">Especie:</span> <span class="value">{{ $mascota->especie }}</span></p>
            <p><span class="label">Raza:</span> <span class="value">{{ $mascota->raza }}</span></p>
            <p><span class="label">Sexo:</span> <span class="value">{{ $mascota->sexo }}</span></p>
            <p><span class="label">Fecha de Nacimiento:</span> <span class="value">{{ \Carbon\Carbon::parse($mascota->fecha_nacimiento)->format('d/m/Y') }}</span></p>
            <p><span class="label">Edad:</span> <span class="value">{{ $mascota->edad ?? 'No especificada' }}</span></p>
            @if($mascota->peso)
            <p><span class="label">Peso:</span> <span class="value">{{ $mascota->peso }} kg</span></p>
            @endif
        </div>

        <!-- SECCIÓN 2: Procedimiento Paliativo -->
        <div class="section">
            <h2>Información del Procedimiento</h2>
            <!-- ID del Procedimiento Médico (como en los otros certificados) -->
            <p><span class="label">ID Procedimiento:</span> 
                <span class="value">#{{ $paliativo->procesoMedico->id ?? 'No disponible' }}</span>
            </p>
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
                    <span class="status-badge resultado-{{ $paliativo->resultado }}">
                        {{ $resultadoLabels[$paliativo->resultado] ?? $paliativo->resultado }}
                    </span>
                </span>
            </p>
            <p><span class="label">Estado de la mascota:</span> 
                <span class="value">
                    <span class="status-badge estado-{{ $paliativo->estado_mascota }}">
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

        <!-- SECCIÓN 3: Veterinario -->
        <div class="section">
            <h2>Datos del Veterinario</h2>
            <p><span class="label">Nombre:</span> 
                <span class="value">
                    @if($paliativo->procesoMedico && $paliativo->procesoMedico->veterinario)
                        {{ $paliativo->procesoMedico->veterinario->name }}
                    @else
                        No especificado
                    @endif
                </span>
            </p>
            <p><span class="label">Matrícula:</span> 
                <span class="value">
                    @if($paliativo->procesoMedico && $paliativo->procesoMedico->veterinario)
                        {{ $paliativo->procesoMedico->veterinario->matricula ?? 'No especificada' }}
                    @else
                        No especificada
                    @endif
                </span>
            </p>
            <p><span class="label">Fecha de Registro:</span> 
                <span class="value">{{ \Carbon\Carbon::parse($paliativo->created_at)->format('d/m/Y H:i') }}</span>
            </p>
        </div>

        <!-- SECCIÓN 4: Centro Veterinario (condicional) -->
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

        <!-- SECCIÓN 5: Tutor -->
        <div class="section">
            <h2>Información del Tutor</h2>
            <p><span class="label">Nombre:</span> <span class="value">{{ $tutor->nombre_completo }}</span></p>
            @if($tutor->email)
            <p><span class="label">Email:</span> <span class="value">{{ $tutor->email }}</span></p>
            @endif
            @if($tutor->telefono)
            <p><span class="label">Teléfono:</span> <span class="value">{{ $tutor->telefono }}</span></p>
            @endif
        </div>

        <!-- SECCIÓN 6: Descripción del Procedimiento (si existe) -->
        @if($paliativo->observaciones)
        <div class="section">
            <h2>Descripción del Procedimiento</h2>
            <div class="content-box">
                {{ nl2br(e($paliativo->observaciones)) }}
            </div>
        </div>
        @endif

        <!-- SECCIÓN 7: Fármacos Asociados (si existen) -->
        @if($paliativo->farmacosAsociados && $paliativo->farmacosAsociados->count() > 0)
        <div class="section">
            <h2>Fármacos Asociados</h2>
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
                                <span class="momento-badge momento-{{ $farmaco->momento_aplicacion }}">
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

        <!-- SECCIÓN 8: Medicación Complementaria (si existe) -->
        @if($paliativo->medicacion_complementaria)
        <div class="section">
            <h2>Medicación Complementaria</h2>
            <div class="content-box">
                {{ nl2br(e($paliativo->medicacion_complementaria)) }}
            </div>
        </div>
        @endif

        <!-- SECCIÓN 9: Recomendaciones al Tutor (si existe) -->
        @if($paliativo->recomendaciones_tutor)
        <div class="section">
            <h2>Recomendaciones al Tutor</h2>
            <div class="content-box" style="background: #F0F9FF; border-color: #BAE6FD;">
                {{ nl2br(e($paliativo->recomendaciones_tutor)) }}
            </div>
        </div>
        @endif

        <!-- Firma y Sello -->
        <div class="signature-area">
            <div class="signature-line"></div>
            <div style="text-align: center; margin-top: 5px; font-size: 11px;">
                Firma del Veterinario Responsable
            </div>
            
            <div class="stamp">
                <div>________________________________</div>
                <div style="margin-top: 5px;">Sello y Firma del Centro Veterinario</div>
                <div class="center-name">
                    {{ $centroVeterinario->nombre ?? 'Centro Veterinario' }}
                </div>
            </div>
        </div>
    </div> <!-- Cierre de content-wrapper -->

    <!-- Pie de página con número de página -->
    <div class="footer">
        <span>Página 1 de 1 • ID: PAL-{{ $paliativo->id }}-{{ now()->format('YmdHis') }}</span>
    </div>
</body>
</html>