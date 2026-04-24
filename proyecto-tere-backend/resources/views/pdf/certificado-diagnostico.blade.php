<!DOCTYPE html>
<!-- resources/views/pdf/certificado-diagnostico.blade.php -->
<html>
<head>
    <meta charset="utf-8">
    <title>Diagnóstico Médico - {{ $mascota->nombre }}</title>
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
        
        /* Badges de estado - adaptados al estándar */
        .status-badge { 
            display: inline-block; 
            padding: 3px 10px; 
            border-radius: 20px; 
            font-size: 11px; 
            font-weight: bold; 
            margin-left: 5px;
        }
        
        .status-activo { 
            background: #ffebee; 
            color: #c62828; 
        }
        
        .status-resuelto { 
            background: #e8f5e9; 
            color: #2e7d32; 
        }
        
        .status-cronico { 
            background: #fff3e0; 
            color: #ef6c00; 
        }
        
        .status-seguimiento { 
            background: #e3f2fd; 
            color: #1565c0; 
        }
        
        .status-sospecha { 
            background: #f3e5f5; 
            color: #7b1fa2; 
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
                <h1>Diagnóstico Médico</h1>
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
            <p><span class="label">Fecha de Nacimiento:</span> <span class="value">{{ \Carbon\Carbon::parse($mascota->fecha_nacimiento)->format('d/m/Y') }}</span></p>
            <p><span class="label">Edad:</span> <span class="value">{{ $mascota->edad ?? 'No especificada' }}</span></p>
        </div>

        <!-- SECCIÓN 2: Diagnóstico -->
        <div class="section">
            <h2>Información del Diagnóstico</h2>
            <!-- ID del Procedimiento Médico (como en los otros certificados) -->
            <p><span class="label">ID Procedimiento:</span> 
                <span class="value">#{{ $diagnostico->procesoMedico->id ?? 'No disponible' }}</span>
            </p>
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
                    <span class="status-badge status-{{ $diagnostico->estado }}">
                        {{ $estadoLabels[$diagnostico->estado] ?? $diagnostico->estado }}
                    </span>
                </span>
            </p>
        </div>

        <!-- SECCIÓN 3: Veterinario (como en los otros certificados) -->
        <div class="section">
            <h2>Datos del Veterinario</h2>
            <p><span class="label">Nombre:</span> 
                <span class="value">
                    @if($diagnostico->procesoMedico && $diagnostico->procesoMedico->veterinario)
                        {{ $diagnostico->procesoMedico->veterinario->name }}
                    @else
                        No especificado
                    @endif
                </span>
            </p>
            <p><span class="label">Matrícula:</span> 
                <span class="value">
                    @if($diagnostico->procesoMedico && $diagnostico->procesoMedico->veterinario)
                        {{ $diagnostico->procesoMedico->veterinario->matricula ?? 'No especificada' }}
                    @else
                        No especificada
                    @endif
                </span>
            </p>
            <p><span class="label">Fecha de Registro:</span> 
                <span class="value">{{ \Carbon\Carbon::parse($diagnostico->created_at)->format('d/m/Y H:i') }}</span>
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

        <!-- SECCIÓN 6: Diagnósticos Diferenciales (si existe) -->
        @if($diagnostico->diagnosticos_diferenciales)
        <div class="section">
            <h2>Diagnósticos Diferenciales</h2>
            <div class="content-box">
                {{ nl2br(e($diagnostico->diagnosticos_diferenciales)) }}
            </div>
        </div>
        @endif

        <!-- SECCIÓN 7: Exámenes Complementarios (si existe) -->
        @if($diagnostico->examenes_complementarios)
        <div class="section">
            <h2>Exámenes Complementarios</h2>
            <div class="content-box">
                {{ nl2br(e($diagnostico->examenes_complementarios)) }}
            </div>
        </div>
        @endif

        <!-- SECCIÓN 8: Conducta Terapéutica (si existe) -->
        @if($diagnostico->conducta_terapeutica)
        <div class="section">
            <h2>Conducta Terapéutica Sugerida</h2>
            <div class="content-box">
                {{ nl2br(e($diagnostico->conducta_terapeutica)) }}
            </div>
        </div>
        @endif

        <!-- SECCIÓN 9: Observaciones (si existe) -->
        @if($diagnostico->observaciones)
        <div class="section">
            <h2>Observaciones</h2>
            <div class="content-box">
                {{ nl2br(e($diagnostico->observaciones)) }}
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
        <span>Página 1 de 1 • ID: DIA-{{ $diagnostico->id }}-{{ now()->format('YmdHis') }}</span>
    </div>
</body>
</html>