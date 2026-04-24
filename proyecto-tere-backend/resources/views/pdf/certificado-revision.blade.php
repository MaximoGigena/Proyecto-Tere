<!DOCTYPE html>
<!-- resources/views/pdf/certificado-revision.blade.php -->
<html>
<head>
    <meta charset="utf-8">
    <title>Certificado de Revisión Médica - {{ $mascota->nombre }}</title>
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
            border-bottom: 2px solid #667eea; 
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
        }
        
        .header-title p {
            margin: 3px 0 0 0;
            font-size: 14px;
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
            color: #2d3748;
            padding-bottom: 5px;
            border-bottom: 1px solid #e2e8f0;
        }
        
        .section p {
            margin: 6px 0;
            font-size: 13px;
        }
        
        .label { 
            font-weight: bold; 
            color: #4a5568;
            width: 140px;
            display: inline-block;
        }
        
        .value { 
            margin-left: 5px; 
        }
        
        /* Estilo para información destacada */
        .content-box { 
            background: #f7fafc; 
            padding: 15px; 
            border-radius: 5px; 
            margin: 10px 0; 
            border-left: 4px solid #667eea;
            font-size: 13px;
        }
        
        /* Badge de urgencia */
        .urgency-badge { 
            display: inline-block;
            padding: 4px 12px; 
            border-radius: 4px; 
            font-size: 12px; 
            font-weight: bold; 
            margin-left: 10px;
            float: right;
        }
        
        .rutinaria { 
            background-color: #c6f6d5; 
            color: #22543d; 
        }
        
        .preventiva { 
            background-color: #bee3f8; 
            color: #2c5282; 
        }
        
        .urgencia { 
            background-color: #fed7d7; 
            color: #742a2a; 
        }
        
        .emergencia { 
            background-color: #fc8181; 
            color: #fff; 
        }
        
        .signature { 
            margin-top: 20px; 
            border-top: 1px solid #333; 
            padding-top: 10px; 
            font-size: 11px;
            text-align: right;
            page-break-inside: avoid;
        }
        
        /* Pie de página corregido */
        .footer {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            text-align: right;
            font-size: 11px;
            color: #718096;
            padding: 8px 15px;
            border-top: 1px solid #e2e8f0;
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
        
        /* Estilos adicionales para tablas */
        .info-table { 
            width: 100%; 
            border-collapse: collapse; 
            margin-top: 5px; 
            font-size: 13px;
        }
        
        .info-table td { 
            padding: 8px 5px; 
            border-bottom: 1px solid #e2e8f0; 
        }
        
        .info-table .label { 
            width: 40%; 
            font-weight: bold; 
            color: #4a5568; 
        }
        
        .info-table .value { 
            width: 60%; 
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
                <h1>Certificado de Revisión Médica</h1>
                <p>Sistema Veterinario TERE</p>
            </div>
        </div>

        <!-- SECCIÓN 1: Mascota -->
        <div class="section">
            <h2>Información de la Mascota</h2>
            <p><span class="label">Nombre:</span> <span class="value">{{ $mascota->nombre }}</span></p>
            <p><span class="label">Especie:</span> <span class="value">{{ $mascota->especie }}</span></p>
            <p><span class="label">Raza:</span> <span class="value">{{ $mascota->raza }}</span></p>
            <p><span class="label">Edad:</span> <span class="value">{{ $mascota->edad }} años</span></p>
            <p><span class="label">Propietario:</span> <span class="value">{{ $tutor->nombre_completo }}</span></p>
        </div>

        <!-- SECCIÓN 2: Revisión Médica -->
        <div class="section">
            <h2>Información de la Revisión</h2>
            <!-- ID del Procedimiento Médico -->
            @if(isset($revision->procesoMedico) && $revision->procesoMedico)
            <p><span class="label">ID Procedimiento:</span> 
                <span class="value">#{{ $revision->procesoMedico->id }}</span>
            </p>
            @endif
            
            <p><span class="label">Tipo de Revisión:</span> 
                <span class="value">
                    @if($revision->tipoRevision && $revision->tipoRevision->nombre)
                        {{ $revision->tipoRevision->nombre }}
                    @else
                        No especificado
                    @endif
                </span>
                <span class="urgency-badge {{ $revision->nivel_urgencia ?? 'rutinaria' }}">
                    {{ $urgenciaLabels[$revision->nivel_urgencia] ?? strtoupper($revision->nivel_urgencia ?? 'RUTINARIA') }}
                </span>
            </p>
            <p><span class="label">Fecha y Hora:</span> <span class="value">{{ \Carbon\Carbon::parse($revision->fecha_revision)->format('d/m/Y H:i') }}</span></p>
            
            @if($revision->fecha_proxima_revision)
            <p><span class="label">Próxima Revisión:</span> <span class="value">{{ \Carbon\Carbon::parse($revision->fecha_proxima_revision)->format('d/m/Y') }}</span></p>
            @endif
        </div>

        <!-- SECCIÓN 3: Veterinario -->
        <div class="section">
            <h2>Datos del Veterinario</h2>
            <p><span class="label">Nombre:</span> 
                <span class="value">
                    @if(isset($veterinario) && $veterinario)
                        {{ $veterinario->name ?? $veterinario->nombre ?? 'No especificado' }}
                    @else
                        No especificado
                    @endif
                </span>
            </p>
            <p><span class="label">Matrícula:</span> 
                <span class="value">
                    @if(isset($veterinario) && $veterinario)
                        {{ $veterinario->matricula ?? 'No especificada' }}
                    @else
                        No especificada
                    @endif
                </span>
            </p>
        </div>

        <!-- SECCIÓN 4: Centro Veterinario -->
        @if(isset($centroVeterinario) && $centroVeterinario)
        <div class="section">
            <h2>Centro Veterinario</h2>
            <p><span class="label">Nombre:</span> <span class="value">{{ $centroVeterinario->nombre }}</span></p>
            <p><span class="label">Dirección:</span> <span class="value">{{ $centroVeterinario->direccion }}</span></p>
        </div>
        @endif

        <!-- SECCIÓN 5: Motivo de Consulta -->
        @if($revision->motivo_consulta)
        <div class="section">
            <h2>Motivo de la Consulta</h2>
            <div class="content-box">{{ $revision->motivo_consulta }}</div>
        </div>
        @endif

        <!-- SECCIÓN 6: Diagnóstico -->
        @if($revision->diagnostico)
        <div class="section">
            <h2>Diagnóstico</h2>
            <div class="content-box">{{ $revision->diagnostico }}</div>
        </div>
        @endif

        <!-- SECCIÓN 7: Indicaciones Médicas -->
        @if($revision->indicaciones_medicas)
        <div class="section">
            <h2>Indicaciones Médicas</h2>
            <div class="content-box">{{ $revision->indicaciones_medicas }}</div>
        </div>
        @endif

        <!-- SECCIÓN 8: Recomendaciones al Tutor -->
        @if($revision->recomendaciones_tutor)
        <div class="section">
            <h2>Recomendaciones al Tutor</h2>
            <div class="content-box">{{ $revision->recomendaciones_tutor }}</div>
        </div>
        @endif

        <!-- SECCIÓN 9: Próxima Revisión (destacada) -->
        @if($revision->fecha_proxima_revision)
        <div class="section">
            <h2>Próxima Revisión Sugerida</h2>
            <div class="content-box" style="border-left-color: #10B981;">
                <strong>{{ \Carbon\Carbon::parse($revision->fecha_proxima_revision)->format('d/m/Y') }}</strong>
            </div>
        </div>
        @endif

        <!-- Firma y fecha de emisión -->
        <div class="signature">
            <p>Documento generado automáticamente el {{ \Carbon\Carbon::now()->format('d/m/Y H:i') }}</p>
            <p style="font-size: 10px; color: #718096;">ID de Revisión: {{ $revision->id }} | ID de Mascota: {{ $mascota->id }}</p>
        </div>
    </div>

    <!-- Pie de página con número de página -->
    <div class="footer">
        <span>Página 1 de 1</span>
    </div>
</body>
</html>