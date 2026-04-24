<!DOCTYPE html>
<!-- resources/views/pdf/certificado-terapia.blade.php -->
<html>
<head>
    <meta charset="utf-8">
    <title>Certificado de Terapia - {{ $mascota->nombre }}</title>
    <style>
        /* Estilos corregidos para PDF - SIGUIENDO EL ESTÁNDAR DEL CERTIFICADO DE VACUNA */
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
        
        /* Línea divisoria entre secciones - IGUAL QUE EN VACUNA */
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
        
        .status-badge { 
            display: inline-block; 
            padding: 3px 10px; 
            border-radius: 20px; 
            font-size: 11px; 
            font-weight: bold; 
            margin-left: 5px;
        }
        
        .status-active { 
            background: #D1FAE5; 
            color: #065F46; 
        }
        
        .status-inactive { 
            background: #FEE2E2; 
            color: #991B1B; 
        }
        
        .observaciones-box {
            background: #F9FAFB; 
            padding: 10px; 
            border-radius: 5px; 
            border: 1px solid #E5E7EB;
            margin-top: 5px;
            margin-bottom: 10px;
            font-size: 12px;
        }
        
        .recomendaciones-box {
            background: #F0F9FF; 
            padding: 10px; 
            border-radius: 5px; 
            border: 1px solid #BAE6FD;
            margin-top: 5px;
            font-size: 12px;
        }
        
        /* Firma y sello - adaptados al estándar */
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
        
        /* Pie de página corregido - IGUAL QUE EN VACUNA */
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
        
        /* Eliminamos la marca de agua que no está en el estándar */
        /* .watermark queda eliminado */
    </style>
</head>
<body>
    <div class="content-wrapper">
        <div class="header">
            <div class="logo-container">
                <!-- Misma ruta de logo que en certificado de vacuna -->
                <img src="{{ public_path('storage/images/logo_para_documentacion-removebg-preview.png') }}" alt="Logo Sistema Veterinario TERE" class="logo">
            </div>
            <div class="header-title">
                <h1>Certificado de Terapia</h1>
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
        </div>

        <!-- SECCIÓN 2: Terapia -->
        <div class="section">
            <h2>Detalles de la Terapia</h2>
            <!-- ID del Procedimiento Médico (como en vacuna) -->
            <p><span class="label">ID Procedimiento:</span> 
                <span class="value">#{{ $terapia->procesoMedico->id ?? 'No disponible' }}</span>
            </p>
            <p><span class="label">Tipo de Terapia:</span> 
                <span class="value">{{ $terapia->tipoTerapia->nombre ?? 'No especificado' }}</span>
            </p>
            <p><span class="label">Fecha de Inicio:</span> <span class="value">{{ \Carbon\Carbon::parse($terapia->fecha_inicio)->format('d/m/Y') }}</span></p>
            <p><span class="label">Fecha de Finalización:</span> 
                <span class="value">
                    @if($terapia->fecha_fin)
                        {{ \Carbon\Carbon::parse($terapia->fecha_fin)->format('d/m/Y') }}
                    @else
                        En curso
                    @endif
                </span>
            </p>
            <p><span class="label">Frecuencia:</span> <span class="value">{{ ucfirst($terapia->frecuencia) }}</span></p>
            <p><span class="label">Duración Estimada:</span> <span class="value">{{ $terapia->duracion_tratamiento }}</span></p>
            <p><span class="label">Evolución:</span> 
                <span class="value">
                    @if($terapia->evolucion)
                        @php
                            $evolucionLabels = [
                                'mejoria' => 'Mejoría',
                                'estable' => 'Estable',
                                'empeoramiento' => 'Empeoramiento'
                            ];
                        @endphp
                        {{ $evolucionLabels[$terapia->evolucion] ?? $terapia->evolucion }}
                    @else
                        No registrada
                    @endif
                </span>
            </p>
            <p><span class="label">Estado:</span> 
                <span class="value">
                    <span class="status-badge {{ $terapia->estaActiva() ? 'status-active' : 'status-inactive' }}">
                        {{ $terapia->estaActiva() ? 'ACTIVA' : 'FINALIZADA' }}
                    </span>
                </span>
            </p>
        </div>

        <!-- SECCIÓN 3: Veterinario (como en vacuna) -->
        <div class="section">
            <h2>Datos del Veterinario</h2>
            <p><span class="label">Nombre:</span> 
                <span class="value">
                    @if($terapia->procesoMedico && $terapia->procesoMedico->veterinario)
                        {{ $terapia->procesoMedico->veterinario->name }}
                    @else
                        No especificado
                    @endif
                </span>
            </p>
            <p><span class="label">Matrícula:</span> 
                <span class="value">
                    @if($terapia->procesoMedico && $terapia->procesoMedico->veterinario)
                        {{ $terapia->procesoMedico->veterinario->matricula ?? 'No especificada' }}
                    @else
                        No especificada
                    @endif
                </span>
            </p>
            <p><span class="label">Fecha de Registro:</span> 
                <span class="value">{{ \Carbon\Carbon::parse($terapia->created_at)->format('d/m/Y H:i') }}</span>
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

        <!-- SECCIÓN 6: Observaciones y Recomendaciones (solo si existen) -->
        @if($terapia->observaciones || $terapia->recomendaciones_tutor)
        <div class="section">
            <h2>Información Adicional</h2>
            
            @if($terapia->observaciones)
            <p><span class="label">Observaciones:</span></p>
            <div class="observaciones-box">
                {{ $terapia->observaciones }}
            </div>
            @endif
            
            @if($terapia->recomendaciones_tutor)
            <p><span class="label">Recomendaciones para el Tutor:</span></p>
            <div class="recomendaciones-box">
                {{ $terapia->recomendaciones_tutor }}
            </div>
            @endif
        </div>
        @endif

        <!-- Firma y Sello - adaptados al estilo estándar -->
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

    <!-- Pie de página con número de página - IGUAL QUE EN VACUNA -->
    <div class="footer">
        <span>Página 1 de 1 • ID: TER-{{ $terapia->id }}-{{ now()->format('YmdHis') }}</span>
    </div>
</body>
</html>