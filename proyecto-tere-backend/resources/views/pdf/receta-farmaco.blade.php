<!-- resources/views/pdf/receta-farmaco.blade.php -->
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Receta Médica - {{ $mascota->nombre }}</title>
    <style>
        @page {
            margin: 30px;
        }
        body { 
            font-family: 'DejaVu Sans', 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; 
            margin: 0; 
            padding: 0; 
            color: #333;
            font-size: 12px;
        }
        .header { 
            text-align: center; 
            border-bottom: 2px solid #4F46E5; 
            padding-bottom: 15px; 
            margin-bottom: 25px;
        }
        .header h1 { 
            color: #4F46E5; 
            margin: 0; 
            font-size: 24px; 
        }
        .header p { 
            color: #666; 
            margin: 5px 0; 
            font-size: 14px;
        }
        .section { 
            margin-bottom: 20px; 
            page-break-inside: avoid;
        }
        .section-title { 
            background: #f3f4f6; 
            padding: 8px 12px; 
            border-left: 4px solid #4F46E5; 
            font-weight: bold; 
            margin-bottom: 12px;
            font-size: 14px;
        }
        .info-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 15px;
        }
        .info-item {
            margin-bottom: 8px;
        }
        .label { 
            font-weight: bold; 
            color: #555; 
            display: inline-block;
            width: 200px;
        }
        .value { 
            color: #333;
        }
        .warning-box {
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            padding: 12px;
            margin: 15px 0;
            border-radius: 5px;
            font-size: 11px;
        }
        .footer { 
            margin-top: 40px; 
            text-align: center; 
            font-size: 10px; 
            color: #666; 
            border-top: 1px solid #ddd;
            padding-top: 15px;
        }
        .signature-area {
            margin-top: 60px;
            padding-top: 20px;
            border-top: 1px solid #333;
        }
        .signature-line {
            width: 300px;
            margin: 30px auto 0;
            text-align: center;
        }
        .logo {
            text-align: center;
            margin-bottom: 20px;
        }
        .logo img {
            max-width: 150px;
            height: auto;
        }
        .watermark {
            position: fixed;
            bottom: 50px;
            left: 0;
            right: 0;
            text-align: center;
            opacity: 0.3;
            font-size: 72px;
            color: #ddd;
            transform: rotate(-45deg);
            pointer-events: none;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 15px 0;
        }
        table th {
            background: #f3f4f6;
            text-align: left;
            padding: 8px;
            border: 1px solid #ddd;
            font-weight: bold;
        }
        table td {
            padding: 8px;
            border: 1px solid #ddd;
        }
        .qrcode {
            float: right;
            margin: 20px;
            width: 100px;
            height: 100px;
            background: #f5f5f5;
            text-align: center;
            line-height: 100px;
            color: #999;
            border: 1px solid #ddd;
        }
        .clearfix {
            clear: both;
        }
    </style>
</head>
<body>
    <!-- Logo del sistema -->
    <div class="logo">
        <h1 style="color: #4F46E5; margin: 0;">TERE</h1>
        <p style="color: #666; margin: 0;">Sistema Veterinario Integral</p>
    </div>

    <div class="header">
        <h1>RECETA MÉDICA</h1>
        <p>Documento oficial de prescripción farmacológica</p>
        <p style="font-size: 11px; color: #888;">Número: RF-{{ $farmaco->id }}-{{ now()->format('Ymd') }}</p>
    </div>

    <!-- Información de la mascota -->
    <div class="section">
        <div class="section-title">📋 INFORMACIÓN DE LA MASCOTA</div>
        <div class="info-grid">
            <div class="info-item">
                <span class="label">Nombre:</span>
                <span class="value">{{ $mascota->nombre }}</span>
            </div>
            <div class="info-item">
                <span class="label">Especie:</span>
                <span class="value">{{ $mascota->especie }}</span>
            </div>
            <div class="info-item">
                <span class="label">Raza:</span>
                <span class="value">{{ $mascota->raza ?? 'No especificada' }}</span>
            </div>
            <div class="info-item">
                <span class="label">Fecha de nacimiento:</span>
                <span class="value">
                    @if($mascota->fecha_nacimiento)
                        {{ \Carbon\Carbon::parse($mascota->fecha_nacimiento)->format('d/m/Y') }}
                        ({{ \Carbon\Carbon::parse($mascota->fecha_nacimiento)->age }} años)
                    @else
                        No registrada
                    @endif
                </span>
            </div>
            <div class="info-item">
                <span class="label">Peso:</span>
                <span class="value">
                    @if($mascota->peso)
                        {{ $mascota->peso }} kg
                    @else
                        No registrado
                    @endif
                </span>
            </div>
            <div class="info-item">
                <span class="label">Identificación:</span>
                <span class="value">{{ $mascota->id }}</span>
            </div>
        </div>
    </div>

    <!-- Información del tratamiento -->
    <div class="section">
        <div class="section-title">💊 PRESCRIPCIÓN MÉDICA</div>
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
                    <td>{{ $farmaco->tipoFarmaco->nombre ?? 'No especificado' }}</td>
                    <td>{{ $farmaco->dosis }} {{ $farmaco->unidad_dosis }}</td>
                    <td>{{ $farmaco->frecuencia }}</td>
                    <td>{{ $farmaco->duracion_tratamiento }}</td>
                </tr>
            </tbody>
        </table>
    </div>

    <!-- Detalles adicionales -->
    <div class="section">
        <div class="section-title">📅 DETALLES DE ADMINISTRACIÓN</div>
        <div class="info-grid">
            <div class="info-item">
                <span class="label">Fecha de administración:</span>
                <span class="value">{{ $farmaco->fecha_administracion->format('d/m/Y H:i') }}</span>
            </div>
            @if($farmaco->proxima_dosis)
            <div class="info-item">
                <span class="label">Próxima dosis:</span>
                <span class="value">{{ $farmaco->proxima_dosis->format('d/m/Y H:i') }}</span>
            </div>
            @endif
            <div class="info-item">
                <span class="label">Vía de administración:</span>
                <span class="value">{{ $farmaco->tipoFarmaco->via_administracion ?? 'No especificada' }}</span>
            </div>
        </div>
    </div>

    <!-- Observaciones -->
    <div class="section">
        <div class="section-title">📝 OBSERVACIONES Y RECOMENDACIONES</div>
        
        @if($farmaco->reacciones_adversas)
        <div class="warning-box">
            <strong>⚠️ REACCIONES ADVERSAS OBSERVADAS:</strong><br>
            {{ $farmaco->reacciones_adversas }}
        </div>
        @endif
        
        @if($farmaco->recomendaciones_tutor)
        <div style="background: #f8f9fa; padding: 12px; border-radius: 5px; margin-top: 10px;">
            <strong>📌 RECOMENDACIONES PARA EL TUTOR:</strong><br>
            {{ $farmaco->recomendaciones_tutor }}
        </div>
        @else
        <div style="background: #f8f9fa; padding: 12px; border-radius: 5px; margin-top: 10px;">
            <strong>📌 RECOMENDACIONES GENERALES:</strong><br>
            1. Administre el medicamento exactamente como se ha prescrito.<br>
            2. Complete el tratamiento completo incluso si los síntomas mejoran.<br>
            3. Observe posibles efectos secundarios o reacciones adversas.<br>
            4. Mantenga el medicamento fuera del alcance de niños y otras mascotas.<br>
            5. Consulte inmediatamente si aparecen síntomas graves.
        </div>
        @endif
    </div>

    <!-- Información del tutor -->
    <div class="section">
        <div class="section-title">👤 INFORMACIÓN DEL TUTOR</div>
        <div class="info-grid">
            <div class="info-item">
                <span class="label">Nombre completo:</span>
                <span class="value">{{ $tutor->nombre_completo }}</span>
            </div>
            <div class="info-item">
                <span class="label">Email:</span>
                <span class="value">{{ $tutor->email }}</span>
            </div>
            <div class="info-item">
                <span class="label">Teléfono:</span>
                <span class="value">{{ $tutor->telefono }}</span>
            </div>
            <div class="info-item">
                <span class="label">Dirección:</span>
                <span class="value">{{ $tutor->direccion ?? 'No registrada' }}</span>
            </div>
        </div>
    </div>

    <!-- Centro veterinario -->
    @if($centroVeterinario)
    <div class="section">
        <div class="section-title">🏥 CENTRO VETERINARIO</div>
        <div class="info-grid">
            <div class="info-item">
                <span class="label">Nombre:</span>
                <span class="value">{{ $centroVeterinario->nombre }}</span>
            </div>
            <div class="info-item">
                <span class="label">Dirección:</span>
                <span class="value">{{ $centroVeterinario->direccion }}</span>
            </div>
            <div class="info-item">
                <span class="label">Teléfono:</span>
                <span class="value">{{ $centroVeterinario->telefono ?? 'No disponible' }}</span>
            </div>
            <div class="info-item">
                <span class="label">Horario:</span>
                <span class="value">{{ $centroVeterinario->horario_atencion ?? 'No disponible' }}</span>
            </div>
        </div>
    </div>
    @endif

    <!-- Firma y validación -->
    <div class="signature-area">
        <div class="info-grid">
            <div>
                <div class="signature-line">
                    <p>_________________________________</p>
                    <p>Veterinario responsable</p>
                    <p>{{ auth()->user()->name ?? 'Dr. Veterinario' }}</p>
                    <p>Colegiado: {{ auth()->user()->numero_colegiado ?? 'N/A' }}</p>
                </div>
            </div>
            <div>
                <div class="signature-line">
                    <p>_________________________________</p>
                    <p>Firma del tutor</p>
                    <p>{{ $tutor->nombre_completo }}</p>
                    <p>Fecha: {{ now()->format('d/m/Y') }}</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Información legal -->
    <div class="warning-box" style="margin-top: 30px;">
        <strong>⚠️ ADVERTENCIAS LEGALES:</strong><br>
        1. Esta receta es válida solo para la mascota indicada.<br>
        2. No comparta este medicamento con otras mascotas.<br>
        3. Almacene según las indicaciones del fabricante.<br>
        4. Consulte a su veterinario ante cualquier duda.<br>
        5. Este documento es confidencial y propiedad del Sistema TERE.
    </div>

    <div class="footer">
        <p><strong>Documento generado automáticamente por el Sistema Veterinario TERE</strong></p>
        <p>Fecha de emisión: {{ $fecha_emision }}</p>
        <p>ID del documento: RF-{{ $farmaco->id }}-{{ now()->format('YmdHis') }}</p>
        <p>© {{ date('Y') }} TERE - Sistema Veterinario Integral. Todos los derechos reservados.</p>
        <p>Este es un documento oficial con validez médica.</p>
    </div>

    <!-- Marca de agua -->
    <div class="watermark">
        RECETA VÁLIDA
    </div>

    <div class="clearfix"></div>
</body>
</html>