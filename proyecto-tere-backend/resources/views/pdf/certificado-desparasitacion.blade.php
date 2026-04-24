<!DOCTYPE html>
<!-- resources/views/pdf/certificado-desparasitacion.blade.php -->
<html>
<head>
    <meta charset="utf-8">
    <title>Certificado de Desparasitación</title>
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
            color: #2c3e50;
        }
        
        .section p {
            margin: 6px 0;
            font-size: 13px;
        }
        
        .label { 
            font-weight: bold; 
            color: #333;
            width: 140px;
            display: inline-block;
        }
        
        .value { 
            margin-left: 5px; 
        }
        
        /* Estilo para destacar información importante */
        .highlight { 
            background-color: #F0FDF4; 
            padding: 10px; 
            border-radius: 5px; 
            margin: 10px 0;
            border-left: 3px solid #10B981;
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
    </style>
</head>
<body>
    <div class="content-wrapper">
        <div class="header">
            <div class="logo-container">
                <img src="{{ public_path('storage/images/logo_para_documentacion-removebg-preview.png') }}" alt="Logo Sistema Veterinario TERE" class="logo">
            </div>
            <div class="header-title">
                <h1>Certificado de Desparasitación</h1>
                <p>Sistema Veterinario TERE</p>
            </div>
        </div>

        <!-- SECCIÓN 1: Mascota -->
        <div class="section">
            <h2>Información de la Mascota</h2>
            <p><span class="label">Nombre:</span> <span class="value">{{ $mascota->nombre }}</span></p>
            <p><span class="label">Especie:</span> <span class="value">{{ $mascota->especie }}</span></p>
            @if($desparasitacion->peso)
            <p><span class="label">Peso:</span> <span class="value">{{ $desparasitacion->peso }} kg</span></p>
            @endif
        </div>

        <!-- SECCIÓN 2: Desparasitación -->
        <div class="section">
            <h2>Información de la Desparasitación</h2>
            <!-- ID del Procedimiento Médico (si existe relación con proceso médico) -->
            @if(isset($desparasitacion->procesoMedico) && $desparasitacion->procesoMedico)
            <p><span class="label">ID Procedimiento:</span> 
                <span class="value">#{{ $desparasitacion->procesoMedico->id }}</span>
            </p>
            @endif
            
            <p><span class="label">Tipo de Desparasitación:</span> 
                <span class="value">
                    @if($desparasitacion->tipoDesparasitacion && $desparasitacion->tipoDesparasitacion->nombre)
                        {{ $desparasitacion->tipoDesparasitacion->nombre }}
                    @else
                        Tipo no especificado
                    @endif
                </span>
            </p>
            <p><span class="label">Fecha de Aplicación:</span> <span class="value">{{ \Carbon\Carbon::parse($desparasitacion->fecha)->format('d/m/Y') }}</span></p>
            <p><span class="label">Producto:</span> <span class="value">{{ $desparasitacion->nombre_producto }}</span></p>
            <p><span class="label">Dosis:</span> <span class="value">{{ $desparasitacion->dosis }}</span></p>
            <p><span class="label">Frecuencia:</span> <span class="value">Cada {{ $desparasitacion->frecuencia_valor }} {{ $desparasitacion->frecuencia_unidad }}</span></p>
            
            @if($desparasitacion->proxima_fecha)
            <div class="highlight">
                <p><span class="label">Próxima Aplicación Sugerida:</span> <span class="value">{{ \Carbon\Carbon::parse($desparasitacion->proxima_fecha)->format('d/m/Y') }}</span></p>
            </div>
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

        <!-- SECCIÓN 4: Centro Veterinario (condicional) -->
        @if(isset($centroVeterinario) && $centroVeterinario)
        <div class="section">
            <h2>Centro Veterinario</h2>
            <p><span class="label">Nombre:</span> <span class="value">{{ $centroVeterinario->nombre }}</span></p>
            <p><span class="label">Dirección:</span> <span class="value">{{ $centroVeterinario->direccion }}</span></p>
        </div>
        @endif

        <!-- SECCIÓN 5: Tutor -->
        <div class="section">
            <h2>Información del Tutor</h2>
            <p><span class="label">Nombre:</span> <span class="value">{{ $tutor->nombre_completo }}</span></p>
            <p><span class="label">Email:</span> <span class="value">{{ $tutor->email }}</span></p>
            <p><span class="label">Teléfono:</span> <span class="value">{{ $tutor->telefono }}</span></p>
        </div>

        <!-- SECCIÓN 6: Observaciones (condicional) -->
        @if($desparasitacion->observaciones)
        <div class="section">
            <h2>Observaciones</h2>
            <p>{{ $desparasitacion->observaciones }}</p>
        </div>
        @endif

        <!-- Firma y fecha de emisión -->
        <div class="signature">
            <p>Documento generado automáticamente el {{ $fecha_emision }}</p>
        </div>
    </div>

    <!-- Pie de página con número de página -->
    <div class="footer">
        <span>Página 1 de 1</span>
    </div>
</body>
</html>