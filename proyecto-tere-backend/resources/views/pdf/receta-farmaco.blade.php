<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Receta Médica - {{ $mascota->nombre }}</title>
    <style>
        /* Estilos corregidos para PDF */
        body { 
            font-family: Arial, sans-serif; 
            margin: 0;
            padding: 15px;
            width: 100%;
            color: #333;
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
            color: #4F46E5;
        }
        
        .header-title p {
            margin: 3px 0 0 0;
            font-size: 14px;
        }
        
        .header-title .document-number {
            font-size: 11px;
            color: #888;
            margin-top: 5px;
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
        
        .info-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 8px 15px;
        }
        
        .label { 
            font-weight: bold; 
            color: #333;
            width: 140px;
            display: inline-block;
        }
        
        .grid-item {
            margin-bottom: 4px;
        }
        
        .grid-item .label {
            width: 110px;
        }
        
        .value { 
            margin-left: 5px; 
        }
        
        .warning-box {
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            padding: 10px;
            margin: 10px 0;
            border-radius: 4px;
            font-size: 12px;
        }
        
        .recommendations-box {
            background: #f8f9fa;
            padding: 10px;
            border-radius: 4px;
            margin-top: 8px;
            font-size: 12px;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 8px 0;
            font-size: 12px;
        }
        
        table th {
            background: #f3f4f6;
            text-align: left;
            padding: 6px;
            border: 1px solid #ddd;
            font-weight: bold;
        }
        
        table td {
            padding: 6px;
            border: 1px solid #ddd;
        }
        
        .signature { 
            margin-top: 20px; 
            border-top: 1px solid #333; 
            padding-top: 15px; 
            font-size: 11px;
            page-break-inside: avoid;
        }
        
        .signature-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
            margin-top: 10px;
        }
        
        .signature-line {
            text-align: center;
        }
        
        .signature-line p {
            margin: 5px 0;
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
        
        .watermark {
            position: fixed;
            bottom: 100px;
            left: 0;
            right: 0;
            text-align: center;
            opacity: 0.15;
            font-size: 60px;
            color: #4F46E5;
            transform: rotate(-30deg);
            pointer-events: none;
            z-index: -1;
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
            position: relative;
            z-index: 1;
        }
        
        .legal-warning {
            background: #f8f9fa;
            border-left: 3px solid #4F46E5;
            padding: 10px;
            margin-top: 15px;
            font-size: 10px;
            color: #666;
        }
        
        .legal-warning strong {
            color: #4F46E5;
        }
        
        .badge {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 3px;
            font-size: 11px;
            font-weight: normal;
        }
        
        .badge-info {
            background: #e3f2fd;
            color: #1565c0;
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
                <h1>Receta Médica</h1>
                <p>Sistema Veterinario TERE</p>
                <div class="document-number">N° RF-{{ $farmaco->id }}-{{ now()->format('Ymd') }}</div>
            </div>
        </div>

        <!-- SECCIÓN 1: Mascota -->
        <div class="section">
            <h2>Información de la Mascota</h2>
            <div class="info-grid">
                <div class="grid-item">
                    <span class="label">Nombre:</span>
                    <span class="value">{{ $mascota->nombre }}</span>
                </div>
                <div class="grid-item">
                    <span class="label">ID Mascota:</span>
                    <span class="value">#{{ $mascota->id }}</span>
                </div>
                <div class="grid-item">
                    <span class="label">Especie:</span>
                    <span class="value">{{ $mascota->especie }}</span>
                </div>
                <div class="grid-item">
                    <span class="label">Raza:</span>
                    <span class="value">{{ $mascota->raza ?? 'No especificada' }}</span>
                </div>
                <div class="grid-item">
                    <span class="label">Fecha nacimiento:</span>
                    <span class="value">
                        @if($mascota->fecha_nacimiento)
                            {{ \Carbon\Carbon::parse($mascota->fecha_nacimiento)->format('d/m/Y') }}
                            ({{ \Carbon\Carbon::parse($mascota->fecha_nacimiento)->age }} años)
                        @else
                            No registrada
                        @endif
                    </span>
                </div>
                <div class="grid-item">
                    <span class="label">Peso:</span>
                    <span class="value">
                        @if($mascota->peso)
                            {{ $mascota->peso }} kg
                        @else
                            No registrado
                        @endif
                    </span>
                </div>
            </div>
        </div>

        <!-- SECCIÓN 2: Prescripción -->
        <div class="section">
            <h2>Prescripción Médica</h2>
            <table>
                <thead>
                    <tr>
                        <th>Fármaco</th>
                        <th>Dosis</th>
                        <th>Frecuencia</th>
                        <th>Duración</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>
                            {{ $farmaco->tipoFarmaco->nombre_comercial ?? $farmaco->tipoFarmaco->nombre ?? 'No especificado' }}
                            @if(!empty($farmaco->tipoFarmaco->nombre_generico))
                                <br><small>({{ $farmaco->tipoFarmaco->nombre_generico }})</small>
                            @endif
                        </td>
                        <td>{{ $farmaco->dosis }} {{ $farmaco->unidad_dosis }}</td>
                        <td>
                            @if($farmaco->frecuencia_valor && $farmaco->frecuencia_unidad)
                                Cada {{ $farmaco->frecuencia_valor }} {{ $farmaco->frecuencia_unidad }}
                            @else
                                {{ $farmaco->frecuencia ?? 'Según indicación' }}
                            @endif
                        </td>
                        <td>
                            @if($farmaco->duracion_valor && $farmaco->duracion_unidad)
                                {{ $farmaco->duracion_valor }} {{ $farmaco->duracion_unidad }}
                            @else
                                {{ $farmaco->duracion_tratamiento ?? 'Según indicación' }}
                            @endif
                        </td>
                    </tr>
                </tbody>
            </table>

            <div style="margin-top: 10px;">
                <p><span class="label">Vía de administración:</span> 
                    <span class="value">{{ $farmaco->tipoFarmaco->via_administracion ?? 'No especificada' }}</span>
                </p>
                <p><span class="label">Fecha de administración:</span> 
                    <span class="value">{{ \Carbon\Carbon::parse($farmaco->fecha_administracion)->format('d/m/Y H:i') }}</span>
                </p>
                @if($farmaco->proxima_dosis)
                <p><span class="label">Próxima dosis:</span> 
                    <span class="value">{{ \Carbon\Carbon::parse($farmaco->proxima_dosis)->format('d/m/Y H:i') }}</span>
                </p>
                @endif
            </div>
        </div>

        <!-- SECCIÓN 3: Observaciones y Recomendaciones -->
        <div class="section">
            <h2>Observaciones y Recomendaciones</h2>
            
            @if($farmaco->reacciones_adversas)
            <div class="warning-box">
                <strong>⚠️ REACCIONES ADVERSAS OBSERVADAS:</strong><br>
                {{ $farmaco->reacciones_adversas }}
            </div>
            @endif
            
            @if($farmaco->recomendaciones_tutor)
            <div class="recommendations-box">
                <strong>📌 RECOMENDACIONES PARA EL TUTOR:</strong><br>
                {{ nl2br(e($farmaco->recomendaciones_tutor)) }}
            </div>
            @else
            <div class="recommendations-box">
                <strong>📌 RECOMENDACIONES GENERALES:</strong><br>
                1. Administre el medicamento exactamente como se ha prescrito.<br>
                2. Complete el tratamiento completo incluso si los síntomas mejoran.<br>
                3. Observe posibles efectos secundarios o reacciones adversas.<br>
                4. Mantenga el medicamento fuera del alcance de niños y otras mascotas.<br>
                5. Consulte inmediatamente si aparecen síntomas graves.
            </div>
            @endif
        </div>

        <!-- SECCIÓN 4: Veterinario -->
        <div class="section">
            <h2>Datos del Veterinario</h2>
            <p><span class="label">Nombre:</span> 
                <span class="value">{{ $veterinario->name ?? auth()->user()->name ?? 'No especificado' }}</span>
            </p>
            <p><span class="label">Matrícula/Colegiado:</span> 
                <span class="value">{{ $veterinario->matricula ?? auth()->user()->numero_colegiado ?? 'No especificada' }}</span>
            </p>
        </div>

        <!-- SECCIÓN 5: Centro Veterinario (condicional) -->
        @if($centroVeterinario)
        <div class="section">
            <h2>Centro Veterinario</h2>
            <div class="info-grid">
                <div class="grid-item">
                    <span class="label">Nombre:</span>
                    <span class="value">{{ $centroVeterinario->nombre }}</span>
                </div>
                <div class="grid-item">
                    <span class="label">Teléfono:</span>
                    <span class="value">{{ $centroVeterinario->telefono ?? 'No disponible' }}</span>
                </div>
                <div class="grid-item">
                    <span class="label">Dirección:</span>
                    <span class="value">{{ $centroVeterinario->direccion }}</span>
                </div>
                <div class="grid-item">
                    <span class="label">Horario:</span>
                    <span class="value">{{ $centroVeterinario->horario_atencion ?? 'No disponible' }}</span>
                </div>
            </div>
        </div>
        @endif

        <!-- SECCIÓN 6: Tutor -->
        <div class="section">
            <h2>Información del Tutor</h2>
            <div class="info-grid">
                <div class="grid-item">
                    <span class="label">Nombre:</span>
                    <span class="value">{{ $tutor->nombre_completo }}</span>
                </div>
                <div class="grid-item">
                    <span class="label">Email:</span>
                    <span class="value">{{ $tutor->email }}</span>
                </div>
                <div class="grid-item">
                    <span class="label">Teléfono:</span>
                    <span class="value">{{ $tutor->telefono }}</span>
                </div>
                <div class="grid-item">
                    <span class="label">Dirección:</span>
                    <span class="value">{{ $tutor->direccion ?? 'No registrada' }}</span>
                </div>
            </div>
        </div>

        <!-- Firma y validación -->
        <div class="signature">
            <div class="signature-grid">
                <div class="signature-line">
                    <p>_________________________________</p>
                    <p><strong>Veterinario responsable</strong></p>
                    <p>{{ $veterinario->name ?? auth()->user()->name ?? 'Dr. Veterinario' }}</p>
                    <p>Matrícula: {{ $veterinario->matricula ?? auth()->user()->numero_colegiado ?? 'N/A' }}</p>
                </div>
                <div class="signature-line">
                    <p>_________________________________</p>
                    <p><strong>Firma del tutor</strong></p>
                    <p>{{ $tutor->nombre_completo }}</p>
                    <p>Fecha: {{ now()->format('d/m/Y') }}</p>
                </div>
            </div>
            <p style="text-align: center; margin-top: 15px; font-size: 10px; color: #666;">
                Documento generado automáticamente el {{ $fecha_emision }}
            </p>
        </div>

        <!-- Información legal -->
        <div class="legal-warning">
            <strong>⚠️ ADVERTENCIAS LEGALES:</strong><br>
            1. Esta receta es válida solo para la mascota indicada.<br>
            2. No comparta este medicamento con otras mascotas.<br>
            3. Almacene según las indicaciones del fabricante.<br>
            4. Consulte a su veterinario ante cualquier duda.<br>
            5. Este documento es confidencial y propiedad del Sistema TERE.
        </div>
    </div>

    <!-- Marca de agua -->
    <div class="watermark">
        RECETA MÉDICA
    </div>

    <div class="footer">
        <span>Página 1 de 1 • ID: RF-{{ $farmaco->id }}-{{ now()->format('YmdHis') }} • © {{ date('Y') }} TERE</span>
    </div>
</body>
</html>