<!DOCTYPE html>
<!-- resources/views/pdf/certificado-terapia.blade.php -->
<html>
<head>
    <meta charset="utf-8">
    <title>Certificado de Terapia - {{ $mascota->nombre }}</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 0; padding: 20px; }
        .header { text-align: center; border-bottom: 3px solid #7C3AED; padding-bottom: 15px; margin-bottom: 25px; }
        .section { margin-bottom: 20px; }
        .section-title { background: #F3F4F6; padding: 8px 12px; border-left: 4px solid #7C3AED; font-weight: bold; margin-bottom: 10px; }
        .info-row { margin-bottom: 8px; display: flex; }
        .label { font-weight: bold; color: #4B5563; min-width: 200px; }
        .value { color: #111827; }
        .footer { margin-top: 50px; text-align: center; font-size: 11px; color: #6B7280; border-top: 1px solid #E5E7EB; padding-top: 15px; }
        .status-badge { display: inline-block; padding: 4px 12px; border-radius: 20px; font-size: 12px; font-weight: bold; }
        .status-active { background: #D1FAE5; color: #065F46; }
        .status-inactive { background: #FEE2E2; color: #991B1B; }
        .signature-area { margin-top: 60px; }
        .signature-line { width: 300px; border-top: 1px solid #000; margin: 40px auto 0; }
        .stamp { text-align: center; margin-top: 30px; color: #7C3AED; font-weight: bold; }
        .watermark { position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%) rotate(-45deg); font-size: 80px; color: rgba(124, 58, 237, 0.1); z-index: -1; }
    </style>
</head>
<body>
    <div class="watermark">TERE VETERINARIA</div>
    
    <div class="header">
        <h1 style="color: #7C3AED; margin-bottom: 5px;">🏥 CERTIFICADO DE TERAPIA</h1>
        <h3 style="color: #6B7280; margin-top: 5px;">Sistema Veterinario TERE</h3>
        <p style="color: #9CA3AF;">Documento Oficial - {{ $fecha_emision }}</p>
    </div>

    <!-- Información de la Mascota -->
    <div class="section">
        <div class="section-title">INFORMACIÓN DE LA MASCOTA</div>
        <div class="info-row">
            <div class="label">Nombre:</div>
            <div class="value">{{ $mascota->nombre }}</div>
        </div>
        <div class="info-row">
            <div class="label">Especie:</div>
            <div class="value">{{ $mascota->especie }}</div>
        </div>
        <div class="info-row">
            <div class="label">Raza:</div>
            <div class="value">{{ $mascota->raza }}</div>
        </div>
        <div class="info-row">
            <div class="label">Fecha de Nacimiento:</div>
            <div class="value">{{ \Carbon\Carbon::parse($mascota->fecha_nacimiento)->format('d/m/Y') }}</div>
        </div>
    </div>

    <!-- Información de la Terapia -->
    <div class="section">
        <div class="section-title">DETALLES DE LA TERAPIA</div>
        <div class="info-row">
            <div class="label">Tipo de Terapia:</div>
            <div class="value">{{ $terapia->tipoTerapia->nombre ?? 'No especificado' }}</div>
        </div>
        <div class="info-row">
            <div class="label">Fecha de Inicio:</div>
            <div class="value">{{ \Carbon\Carbon::parse($terapia->fecha_inicio)->format('d/m/Y') }}</div>
        </div>
        <div class="info-row">
            <div class="label">Fecha de Finalización:</div>
            <div class="value">
                @if($terapia->fecha_fin)
                    {{ \Carbon\Carbon::parse($terapia->fecha_fin)->format('d/m/Y') }}
                @else
                    En curso
                @endif
            </div>
        </div>
        <div class="info-row">
            <div class="label">Frecuencia:</div>
            <div class="value">{{ ucfirst($terapia->frecuencia) }}</div>
        </div>
        <div class="info-row">
            <div class="label">Duración Estimada:</div>
            <div class="value">{{ $terapia->duracion_tratamiento }}</div>
        </div>
        <div class="info-row">
            <div class="label">Evolución:</div>
            <div class="value">
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
            </div>
        </div>
        <div class="info-row">
            <div class="label">Estado:</div>
            <div class="value">
                <span class="status-badge {{ $terapia->estaActiva() ? 'status-active' : 'status-inactive' }}">
                    {{ $terapia->estaActiva() ? 'ACTIVA' : 'FINALIZADA' }}
                </span>
            </div>
        </div>
    </div>

    <!-- Observaciones y Recomendaciones -->
    @if($terapia->observaciones || $terapia->recomendaciones_tutor)
    <div class="section">
        <div class="section-title">INFORMACIÓN ADICIONAL</div>
        
        @if($terapia->observaciones)
        <div style="margin-bottom: 15px;">
            <div style="font-weight: bold; color: #4B5563; margin-bottom: 5px;">Observaciones:</div>
            <div style="background: #F9FAFB; padding: 10px; border-radius: 5px; border: 1px solid #E5E7EB;">
                {{ $terapia->observaciones }}
            </div>
        </div>
        @endif
        
        @if($terapia->recomendaciones_tutor)
        <div>
            <div style="font-weight: bold; color: #4B5563; margin-bottom: 5px;">Recomendaciones para el Tutor:</div>
            <div style="background: #F0F9FF; padding: 10px; border-radius: 5px; border: 1px solid #BAE6FD;">
                {{ $terapia->recomendaciones_tutor }}
            </div>
        </div>
        @endif
    </div>
    @endif

    <!-- Centro Veterinario -->
    @if($centroVeterinario)
    <div class="section">
        <div class="section-title">CENTRO VETERINARIO</div>
        <div class="info-row">
            <div class="label">Nombre:</div>
            <div class="value">{{ $centroVeterinario->nombre }}</div>
        </div>
        <div class="info-row">
            <div class="label">Dirección:</div>
            <div class="value">{{ $centroVeterinario->direccion }}</div>
        </div>
        @if($centroVeterinario->telefono)
        <div class="info-row">
            <div class="label">Teléfono:</div>
            <div class="value">{{ $centroVeterinario->telefono }}</div>
        </div>
        @endif
    </div>
    @endif

    <!-- Información del Veterinario -->
    @if($terapia->procesoMedico && $terapia->procesoMedico->veterinario)
    <div class="section">
        <div class="section-title">VETERINARIO RESPONSABLE</div>
        <div class="info-row">
            <div class="label">Nombre:</div>
            <div class="value">{{ $terapia->procesoMedico->veterinario->name }}</div>
        </div>
        <div class="info-row">
            <div class="label">Fecha de Registro:</div>
            <div class="value">{{ \Carbon\Carbon::parse($terapia->created_at)->format('d/m/Y H:i') }}</div>
        </div>
    </div>
    @endif

    <!-- Información del Tutor -->
    <div class="section">
        <div class="section-title">INFORMACIÓN DEL TUTOR</div>
        <div class="info-row">
            <div class="label">Nombre:</div>
            <div class="value">{{ $tutor->nombre_completo }}</div>
        </div>
        @if($tutor->email)
        <div class="info-row">
            <div class="label">Email:</div>
            <div class="value">{{ $tutor->email }}</div>
        </div>
        @endif
        @if($tutor->telefono)
        <div class="info-row">
            <div class="label">Teléfono:</div>
            <div class="value">{{ $tutor->telefono }}</div>
        </div>
        @endif
    </div>

    <!-- Firma y Sello -->
    <div class="signature-area">
        <div class="signature-line"></div>
        <div style="text-align: center; margin-top: 5px;">
            Firma del Veterinario Responsable
        </div>
        
        <div class="stamp">
            <div style="margin-bottom: 10px;">________________________________</div>
            <div>Sello y Firma del Centro Veterinario</div>
            <div style="font-size: 12px; color: #6B7280; margin-top: 5px;">
                {{ $centroVeterinario->nombre ?? 'Centro Veterinario' }}
            </div>
        </div>
    </div>

    <div class="footer">
        <p><strong>Documento generado automáticamente por el Sistema Veterinario TERE</strong></p>
        <p>Este certificado tiene validez oficial y debe ser conservado en el historial médico de la mascota.</p>
        <p>ID del Documento: TER-{{ $terapia->id }}-{{ now()->format('YmdHis') }}</p>
    </div>
</body>
</html>