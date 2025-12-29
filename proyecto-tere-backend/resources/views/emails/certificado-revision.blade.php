<!DOCTYPE html>
<!-- resources/views/emails/certificado-revision.blade.php -->
<html>
<head>
    <meta charset="utf-8">
    <title>Informe de Revisión Médica</title>
    <style>
        body { font-family: 'Arial', sans-serif; line-height: 1.6; color: #333; margin: 0; padding: 0; background-color: #f5f5f5; }
        .container { max-width: 600px; margin: 0 auto; background-color: white; }
        .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 30px; text-align: center; border-radius: 10px 10px 0 0; }
        .content { padding: 30px; }
        .section { margin-bottom: 25px; background: #f9f9f9; padding: 20px; border-radius: 8px; border-left: 4px solid #667eea; }
        .section-title { color: #667eea; font-size: 18px; font-weight: bold; margin-bottom: 15px; display: flex; align-items: center; }
        .section-title i { margin-right: 10px; }
        .info-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 15px; margin-top: 10px; }
        .info-item { margin-bottom: 10px; }
        .label { font-weight: bold; color: #555; display: inline-block; min-width: 180px; }
        .value { color: #333; }
        .urgent { background-color: #fff5f5; border-left-color: #fc8181; }
        .urgent .section-title { color: #fc8181; }
        .emergency { background-color: #fed7d7; border-left-color: #e53e3e; }
        .emergency .section-title { color: #e53e3e; }
        .footer { text-align: center; padding: 20px; background-color: #2d3748; color: white; border-radius: 0 0 10px 10px; font-size: 12px; }
        .logo { max-width: 150px; margin-bottom: 20px; }
        .badge { display: inline-block; padding: 4px 12px; border-radius: 20px; font-size: 12px; font-weight: bold; margin-left: 10px; }
        .badge-rutinaria { background-color: #68d391; color: white; }
        .badge-preventiva { background-color: #4299e1; color: white; }
        .badge-urgencia { background-color: #ed8936; color: white; }
        .badge-emergencia { background-color: #f56565; color: white; }
        .attachments { background-color: #e6fffa; border-left-color: #38b2ac; }
        .attachments .section-title { color: #38b2ac; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🏥 Informe de Revisión Médica</h1>
            <p>Sistema Veterinario TERE</p>
            <p style="margin-top: 10px; font-size: 14px; opacity: 0.9;">
                Generado el {{ \Carbon\Carbon::now()->format('d/m/Y H:i') }}
            </p>
        </div>
        
        <div class="content">
            <p>Hola <strong>{{ $tutor->nombre_completo }}</strong>,</p>
            
            <p>Te enviamos el informe detallado de la revisión médica realizada a tu mascota <strong>{{ $mascota->nombre }}</strong>.</p>
            
            <!-- Sección de datos de la mascota -->
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
            
            <!-- Sección de revisión médica -->
            <div class="section @if($revision->nivel_urgencia === 'urgencia') urgent @elseif($revision->nivel_urgencia === 'emergencia') emergency @endif">
                <div class="section-title">
                    <span>📋 Detalles de la Revisión</span>
                    <span class="badge badge-{{ $revision->nivel_urgencia }}">
                        {{ ucfirst($revision->nivel_urgencia) }}
                    </span>
                </div>
                
                <div class="info-grid">
                    <div class="info-item">
                        <span class="label">Tipo de Revisión:</span>
                        <span class="value">{{ $revision->tipoRevision->nombre ?? 'No especificado' }}</span>
                    </div>
                    <div class="info-item">
                        <span class="label">Fecha y Hora:</span>
                        <span class="value">{{ \Carbon\Carbon::parse($revision->fecha_revision)->format('d/m/Y H:i') }}</span>
                    </div>
                    
                    @if($centroVeterinario)  <!-- CAMBIO AQUÍ: usar $centroVeterinario -->
                    <div class="info-item">
                        <span class="label">Centro Veterinario:</span>
                        <span class="value">{{ $centroVeterinario->nombre }}</span>
                    </div>
                    <div class="info-item">
                        <span class="label">Dirección:</span>
                        <span class="value">{{ $centroVeterinario->direccion }}</span>  <!-- CAMBIO AQUÍ -->
                    </div>
                    @endif
                </div>
            </div>
            
            <!-- Motivo y diagnóstico -->
            @if($revision->motivo_consulta)
            <div class="section">
                <div class="section-title">📝 Motivo de la Consulta</div>
                <p>{{ $revision->motivo_consulta }}</p>
            </div>
            @endif
            
            @if($revision->diagnostico)
            <div class="section">
                <div class="section-title">🔍 Diagnóstico</div>
                <p>{{ $revision->diagnostico }}</p>
            </div>
            @endif
            
            <!-- Indicaciones médicas -->
            @if($revision->indicaciones_medicas)
            <div class="section">
                <div class="section-title">💊 Indicaciones Médicas</div>
                <p>{{ $revision->indicaciones_medicas }}</p>
            </div>
            @endif
            
            <!-- Recomendaciones al tutor -->
            @if($revision->recomendaciones_tutor)
            <div class="section">
                <div class="section-title">💡 Recomendaciones al Tutor</div>
                <p>{{ $revision->recomendaciones_tutor }}</p>
            </div>
            @endif
            
            <!-- Próxima revisión -->
            @if($revision->fecha_proxima_revision)
            <div class="section">
                <div class="section-title">📅 Próxima Revisión Sugerida</div>
                <div class="info-item">
                    <span class="label">Fecha sugerida:</span>
                    <span class="value" style="font-weight: bold; color: #2b6cb0;">
                        {{ \Carbon\Carbon::parse($revision->fecha_proxima_revision)->format('d/m/Y') }}
                    </span>
                </div>
                <p style="margin-top: 10px; font-size: 14px; color: #718096;">
                    Te recomendamos agendar esta fecha para el seguimiento de tu mascota.
                </p>
            </div>
            @endif
            
            <!-- Archivos adjuntos -->
            @if(isset($archivos) && count($archivos) > 0)
            <div class="section attachments">
                <div class="section-title">📎 Documentos Adjuntos</div>
                <p>Se han adjuntado los siguientes archivos a este email:</p>
                <ul style="margin-left: 20px;">
                    @foreach($archivos as $archivo)
                    <li>{{ $archivo->getClientOriginalName() }}</li>
                    @endforeach
                </ul>
            </div>
            @endif
            
            
            <p style="margin-top: 30px; padding: 15px; background-color: #ebf8ff; border-radius: 5px; color: #2c5282;">
                <strong>📞 Contacto:</strong> Para cualquier consulta sobre esta revisión, 
                por favor comuníquese con el centro veterinario donde se realizó la atención.
            </p>
            
            <p style="margin-top: 20px;">
                ¡Gracias por confiar en el Sistema Veterinario TERE para el cuidado de tu mascota! 🐶🐱
            </p>
        </div>
        
        <div class="footer">
            <p>Este email fue generado automáticamente por el Sistema Veterinario TERE</p>
            <p>© {{ date('Y') }} TERE - Todos los derechos reservados</p>
            <p style="margin-top: 10px; font-size: 11px; opacity: 0.8;">
                ID de Revisión: {{ $revision->id }} | ID de Mascota: {{ $mascota->id }}
            </p>
        </div>
    </div>
</body>
</html>