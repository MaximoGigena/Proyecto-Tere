<!DOCTYPE html>
<!-- resources/views/emails/certificado-terapia.blade.php -->
<html>
<head>
    <meta charset="utf-8">
    <title>Certificado de Terapia</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { text-align: center; background: #7C3AED; color: white; padding: 20px; border-radius: 10px 10px 0 0; }
        .content { background: #f9f9f9; padding: 20px; border-radius: 0 0 10px 10px; }
        .footer { text-align: center; margin-top: 20px; padding: 20px; color: #666; font-size: 12px; }
        .info-box { background: white; padding: 15px; margin: 10px 0; border-radius: 5px; border-left: 4px solid #7C3AED; }
        .status-active { color: #10B981; font-weight: bold; }
        .status-inactive { color: #EF4444; font-weight: bold; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🏥 Certificado de Terapia</h1>
            <p>Sistema Veterinario TERE</p>
        </div>
        
        <div class="content">
            <p>Hola <strong>{{ $mascota->usuario->nombre_completo }}</strong>,</p>
            
            <p>Te enviamos el certificado de terapia aplicada a tu mascota <strong>{{ $mascota->nombre }}</strong>.</p>
            
            <div class="info-box">
                <h3>📋 Resumen de la Terapia</h3>
                <p><strong>Mascota:</strong> {{ $mascota->nombre }} ({{ $mascota->especie }})</p>
                <p><strong>Tipo de Terapia:</strong> {{ $terapia->tipoTerapia->nombre ?? 'No especificado' }}</p>
                <p><strong>Fecha de Inicio:</strong> {{ \Carbon\Carbon::parse($terapia->fecha_inicio)->format('d/m/Y') }}</p>
                <p><strong>Frecuencia:</strong> {{ ucfirst($terapia->frecuencia) }}</p>
                <p><strong>Duración Estimada:</strong> {{ $terapia->duracion_tratamiento }}</p>
                
                @if($terapia->fecha_fin)
                    <p><strong>Fecha de Finalización:</strong> {{ \Carbon\Carbon::parse($terapia->fecha_fin)->format('d/m/Y') }}</p>
                @endif
                
                @if($terapia->evolucion)
                    @php
                        $evolucionLabels = [
                            'mejoria' => 'Mejoría',
                            'estable' => 'Estable',
                            'empeoramiento' => 'Empeoramiento'
                        ];
                    @endphp
                    <p><strong>Evolución:</strong> {{ $evolucionLabels[$terapia->evolucion] ?? $terapia->evolucion }}</p>
                @endif
                
                <p><strong>Estado:</strong> 
                    <span class="{{ $terapia->estaActiva() ? 'status-active' : 'status-inactive' }}">
                        {{ $terapia->estaActiva() ? 'Activa' : 'Finalizada' }}
                    </span>
                </p>
            </div>
            
            @if($terapia->recomendaciones_tutor)
            <div class="info-box">
                <h3>📝 Recomendaciones</h3>
                <p>{{ $terapia->recomendaciones_tutor }}</p>
            </div>
            @endif
            
            <p>El certificado en PDF está adjunto a este email. Guárdalo para tus registros médicos.</p>
            
            <p>Si tienes alguna pregunta sobre el tratamiento, no dudes en contactarnos.</p>
            
            <p>¡Cuida mucho a {{ $mascota->nombre }}! 💜</p>
        </div>
        
        <div class="footer">
            <p>Este email fue generado automáticamente por el Sistema Veterinario TERE</p>
            <p>© {{ date('Y') }} TERE - Todos los derechos reservados</p>
        </div>
    </div>
</body>
</html>