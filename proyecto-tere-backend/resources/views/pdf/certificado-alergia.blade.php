<!DOCTYPE html>
<!-- resources/views/pdf/registro-alergia.blade.php -->
<html>
<head>
    <meta charset="utf-8">
    <title>Registro de Alergia - {{ $mascota->nombre }}</title>
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
            border-bottom: 2px solid #ed8936; 
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
        
        /* Badges para gravedad y estado */
        .gravedad-badge, .estado-badge { 
            display: inline-block;
            padding: 4px 12px; 
            border-radius: 4px; 
            font-size: 12px; 
            font-weight: bold; 
            margin-left: 10px;
            float: right;
        }
        
        .leve { 
            background-color: #c6f6d5; 
            color: #22543d; 
        }
        
        .moderada { 
            background-color: #feebc8; 
            color: #744210; 
        }
        
        .grave { 
            background-color: #fed7d7; 
            color: #742a2a; 
        }
        
        .activa { 
            background-color: #fed7d7; 
            color: #742a2a; 
        }
        
        .superada { 
            background-color: #c6f6d5; 
            color: #22543d; 
        }
        
        .seguimiento { 
            background-color: #bee3f8; 
            color: #2c5282; 
        }
        
        /* Estilo para información destacada */
        .content-box { 
            background: #f7fafc; 
            padding: 15px; 
            border-radius: 5px; 
            margin: 10px 0; 
            border-left: 4px solid #ed8936;
            font-size: 13px;
        }
        
        /* Caja de advertencia */
        .warning-box { 
            background: #fffaf0; 
            padding: 15px; 
            border-radius: 5px; 
            margin: 20px 0; 
            border: 1px solid #fbd38d;
            page-break-inside: avoid;
        }
        
        .warning-title { 
            color: #dd6b20; 
            font-weight: bold; 
            margin-bottom: 10px; 
            font-size: 14px;
        }
        
        .warning-box ul {
            margin-left: 20px;
            font-size: 13px;
            color: #4a5568;
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
    </style>
</head>
<body>
    <div class="content-wrapper">
        <div class="header">
            <div class="logo-container">
                <img src="{{ public_path('storage/images/logo_para_documentacion-removebg-preview.png') }}" alt="Logo Sistema Veterinario TERE" class="logo">
            </div>
            <div class="header-title">
                <h1>Registro Oficial de Alergia/Sensibilidad</h1>
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

        <!-- SECCIÓN 2: Alergia -->
        <div class="section">
            <h2>Información de la Alergia</h2>
            <!-- ID del Procedimiento Médico (si existe relación) -->
            @if(isset($alergia->procesoMedico) && $alergia->procesoMedico)
            <p><span class="label">ID Procedimiento:</span> 
                <span class="value">#{{ $alergia->procesoMedico->id }}</span>
            </p>
            @endif
            
            <p><span class="label">Tipo de Alergia:</span> 
                <span class="value">
                    @if($alergia->tipoAlergia && $alergia->tipoAlergia->nombre)
                        {{ $alergia->tipoAlergia->nombre }}
                    @else
                        No especificado
                    @endif
                </span>
                <span class="gravedad-badge {{ $alergia->gravedad ?? 'leve' }}">
                    {{ $gravedadLabels[$alergia->gravedad] ?? strtoupper($alergia->gravedad ?? 'LEVE') }}
                </span>
                <span class="estado-badge {{ $alergia->estado ?? 'activa' }}">
                    {{ $estadoLabels[$alergia->estado] ?? strtoupper($alergia->estado ?? 'ACTIVA') }}
                </span>
            </p>
            <p><span class="label">Fecha de Detección:</span> <span class="value">{{ \Carbon\Carbon::parse($alergia->fecha_deteccion)->format('d/m/Y') }}</span></p>
            <p><span class="label">Reacción Común:</span> <span class="value">{{ $alergia->reaccion_comun }}</span></p>
            
            @if($alergia->desencadenante)
            <p><span class="label">Desencadenante:</span> <span class="value">{{ $alergia->desencadenante }}</span></p>
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
            @if($centroVeterinario->telefono)
            <p><span class="label">Teléfono:</span> <span class="value">{{ $centroVeterinario->telefono }}</span></p>
            @endif
        </div>
        @endif

        <!-- SECCIÓN 5: Conducta Recomendada -->
        @if($alergia->conducta_recomendada)
        <div class="section">
            <h2>Conducta Recomendada</h2>
            <div class="content-box">{{ $alergia->conducta_recomendada }}</div>
        </div>
        @endif

        <!-- SECCIÓN 6: Recomendaciones al Tutor -->
        @if($alergia->recomendaciones_tutor)
        <div class="section">
            <h2>Recomendaciones al Tutor</h2>
            <div class="content-box">{{ $alergia->recomendaciones_tutor }}</div>
        </div>
        @endif

        <!-- SECCIÓN 7: Observaciones -->
        @if($alergia->observaciones)
        <div class="section">
            <h2>Observaciones</h2>
            <div class="content-box">{{ $alergia->observaciones }}</div>
        </div>
        @endif

        <!-- SECCIÓN 8: Instrucciones Importantes -->
        <div class="section">
            <h2>Instrucciones Importantes</h2>
            <div class="warning-box">
                <div class="warning-title">RECOMENDACIONES DE SEGURIDAD</div>
                <ul>
                    <li>Este documento debe ser presentado en todas las visitas veterinarias.</li>
                    <li>Informe a cualquier profesional que atienda a su mascota sobre esta alergia.</li>
                    <li>Mantenga un registro de posibles reacciones o cambios.</li>
                    <li>En caso de reacción alérgica grave, acuda inmediatamente al veterinario.</li>
                    <li>Siga estrictamente las recomendaciones médicas proporcionadas.</li>
                </ul>
            </div>
        </div>

        <!-- Firma y fecha de emisión -->
        <div class="signature">
            <p>Documento generado automáticamente el {{ $fecha_emision }}</p>
            <p style="font-size: 10px; color: #718096;">ID de Alergia: {{ $alergia->id }} | ID de Mascota: {{ $mascota->id }}</p>
        </div>
    </div>

    <!-- Pie de página con número de página -->
    <div class="footer">
        <span>Página 1 de 1</span>
    </div>
</body>
</html>