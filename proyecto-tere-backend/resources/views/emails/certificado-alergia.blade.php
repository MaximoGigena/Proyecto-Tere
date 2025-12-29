<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Registro de Alergia</title>
    <style>
        body { font-family: 'Arial', sans-serif; line-height: 1.6; color: #333; margin: 0; padding: 0; background-color: #f5f5f5; }
        .container { max-width: 600px; margin: 0 auto; background-color: white; }
        .header { background: linear-gradient(135deg, #ed8936 0%, #dd6b20 100%); color: white; padding: 30px; text-align: center; border-radius: 10px 10px 0 0; }
        .content { padding: 30px; }
        .section { margin-bottom: 25px; background: #f9f9f9; padding: 20px; border-radius: 8px; border-left: 4px solid #ed8936; }
        .section-title { color: #ed8936; font-size: 18px; font-weight: bold; margin-bottom: 15px; display: flex; align-items: center; }
        .section-title i { margin-right: 10px; }
        .info-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 15px; margin-top: 10px; }
        .info-item { margin-bottom: 10px; }
        .label { font-weight: bold; color: #555; display: inline-block; min-width: 180px; }
        .value { color: #333; }
        .grave { background-color: #fed7d7; border-left-color: #e53e3e; }
        .grave .section-title { color: #e53e3e; }
        .moderada { background-color: #feebc8; border-left-color: #dd6b20; }
        .moderada .section-title { color: #dd6b20; }
        .leve { background-color: #c6f6d5; border-left-color: #38a169; }
        .leve .section-title { color: #38a169; }
        .footer { text-align: center; padding: 20px; background-color: #2d3748; color: white; border-radius: 0 0 10px 10px; font-size: 12px; }
        .badge { display: inline-block; padding: 4px 12px; border-radius: 20px; font-size: 12px; font-weight: bold; margin-left: 10px; }
        .badge-leve { background-color: #68d391; color: white; }
        .badge-moderada { background-color: #ed8936; color: white; }
        .badge-grave { background-color: #f56565; color: white; }
        .badge-activa { background-color: #f56565; color: white; }
        .badge-superada { background-color: #68d391; color: white; }
        .badge-seguimiento { background-color: #4299e1; color: white; }
        .warning-box { background-color: #fffaf0; border: 1px solid #fbd38d; padding: 15px; border-radius: 5px; margin: 20px 0; }
        .warning-title { color: #dd6b20; font-weight: bold; display: flex; align-items: center; margin-bottom: 10px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>⚠️ Registro de Alergia/Sensibilidad</h1>
            <p>Sistema Veterinario TERE</p>
            <p style="margin-top: 10px; font-size: 14px; opacity: 0.9;">
                Generado el {{ \Carbon\Carbon::now()->format('d/m/Y H:i') }}
            </p>
        </div>
        
        <div class="content">
            <p>Hola <strong>{{ $tutor->nombre_completo ?? 'Tutor' }}</strong>,</p>
            
            <p>Te enviamos el registro oficial de la alergia/sensibilidad diagnosticada a tu mascota <strong>{{ $mascota->nombre }}</strong>.</p>
            
            <!-- Datos de la mascota -->
            <div class="section">
                <div class="section-title">
                    <span>🐾 Datos de la Mascota</span>
                </div>
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
                        <span class="value">{{ $mascota->raza }}</span>
                    </div>
                    <div class="info-item">
                        <span class="label">Edad:</span>
                        <span class="value">{{ $mascota->edad }} años</span>
                    </div>
                </div>
            </div>
            
            <!-- Información de la alergia -->
            <div class="section {{ $alergia->gravedad }}">
                <div class="section-title">
                    <span>🤧 Información de la Alergia</span>
                    <span class="badge badge-{{ $alergia->gravedad }}">
                        {{ ucfirst($alergia->gravedad) }}
                    </span>
                    <span class="badge badge-{{ $alergia->estado }}">
                        {{ ucfirst($alergia->estado) }}
                    </span>
                </div>
                
                <div class="info-grid">
                    <div class="info-item">
                        <span class="label">Tipo de Alergia:</span>
                        <span class="value">{{ $alergia->tipoAlergia->nombre ?? 'No especificada' }}</span>
                    </div>
                    <div class="info-item">
                        <span class="label">Fecha de Detección:</span>
                        <span class="value">{{ \Carbon\Carbon::parse($alergia->fecha_deteccion)->format('d/m/Y') }}</span>
                    </div>
                    <div class="info-item">
                        <span class="label">Reacción Común:</span>
                        <span class="value">{{ $alergia->reaccion_comun }}</span>
                    </div>
                    @if($alergia->desencadenante)
                    <div class="info-item">
                        <span class="label">Desencadenante:</span>
                        <span class="value">{{ $alergia->desencadenante }}</span>
                    </div>
                    @endif
                </div>
            </div>
            
            <!-- Centro veterinario -->
            @if($centroVeterinario)
            <div class="section">
                <div class="section-title">🏥 Centro Veterinario</div>
                <div class="info-grid">
                    <div class="info-item">
                        <span class="label">Nombre:</span>
                        <span class="value">{{ $centroVeterinario->nombre }}</span>
                    </div>
                    <div class="info-item">
                        <span class="label">Dirección:</span>
                        <span class="value">{{ $centroVeterinario->direccion }}</span>
                    </div>
                </div>
            </div>
            @endif
            
            <!-- Conducta recomendada -->
            @if($alergia->conducta_recomendada)
            <div class="section">
                <div class="section-title">💡 Conducta Recomendada</div>
                <p>{{ $alergia->conducta_recomendada }}</p>
            </div>
            @endif
            
            <!-- Recomendaciones al tutor -->
            @if($alergia->recomendaciones_tutor)
            <div class="section">
                <div class="section-title">👨‍⚕️ Recomendaciones al Tutor</div>
                <p>{{ $alergia->recomendaciones_tutor }}</p>
            </div>
            @endif
            
            <!-- Observaciones -->
            @if($alergia->observaciones)
            <div class="section">
                <div class="section-title">📝 Observaciones</div>
                <p>{{ $alergia->observaciones }}</p>
            </div>
            @endif
            
            <!-- Advertencias importantes -->
            <div class="warning-box">
                <div class="warning-title">⚠️ IMPORTANTE</div>
                <ul style="margin-left: 20px;">
                    <li>Mantén este registro en un lugar accesible.</li>
                    <li>Informa sobre esta alergia en futuras visitas veterinarias.</li>
                    <li>Sigue las recomendaciones médicas al pie de la letra.</li>
                    <li>En caso de reacción grave, acude inmediatamente al veterinario.</li>
                </ul>
            </div>
            
            <p style="margin-top: 20px;">
                El documento oficial adjunto contiene toda la información detallada de este registro.
            </p>
            
            <p style="margin-top: 20px;">
                ¡Gracias por confiar en el Sistema Veterinario TERE para el cuidado de tu mascota! 🐶🐱
            </p>
        </div>
        
        <div class="footer">
            <p>Este email fue generado automáticamente por el Sistema Veterinario TERE</p>
            <p>© {{ date('Y') }} TERE - Todos los derechos reservados</p>
            <p style="margin-top: 10px; font-size: 11px; opacity: 0.8;">
                ID de Alergia: {{ $alergia->id }} | ID de Mascota: {{ $mascota->id }}
            </p>
        </div>
    </div>
</body>
</html>