<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Diagnóstico Médico</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { text-align: center; background: #4F46E5; color: white; padding: 20px; border-radius: 10px 10px 0 0; }
        .content { background: #f9f9f9; padding: 20px; border-radius: 0 0 10px 10px; }
        .footer { text-align: center; margin-top: 20px; padding: 20px; color: #666; font-size: 12px; }
        .info-box { background: white; padding: 15px; margin: 10px 0; border-radius: 5px; border-left: 4px solid #4F46E5; }
        .status-badge { display: inline-block; padding: 3px 10px; border-radius: 3px; font-weight: bold; font-size: 14px; }
        .status-activo { background: #ffebee; color: #c62828; }
        .status-resuelto { background: #e8f5e9; color: #2e7d32; }
        .status-cronico { background: #fff3e0; color: #ef6c00; }
        .status-seguimiento { background: #e3f2fd; color: #1565c0; }
        .status-sospecha { background: #f3e5f5; color: #7b1fa2; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🏥 Diagnóstico Médico</h1>
            <p>Sistema Veterinario TERE</p>
        </div>
        
        <div class="content">
            <p>Hola <strong>{{ $tutor->nombre_completo ?? 'Tutor' }}</strong>,</p>
            
            <p>Te enviamos el informe de diagnóstico de tu mascota <strong>{{ $mascota->nombre }}</strong>.</p>
            
            <div class="info-box">
                <h3>📋 Resumen del Diagnóstico</h3>
                <p><strong>Mascota:</strong> {{ $mascota->nombre }} ({{ $mascota->especie }})</p>
                <p><strong>Diagnóstico:</strong> {{ $diagnostico->nombre }}</p>
                <p><strong>Tipo:</strong> {{ $diagnostico->tipoDiagnostico->nombre ?? 'No especificado' }}</p>
                <p><strong>Fecha:</strong> {{ \Carbon\Carbon::parse($diagnostico->fecha_diagnostico)->format('d/m/Y') }}</p>
                <p><strong>Estado:</strong> 
                    @php
                        $estadoLabels = [
                            'activo' => 'Activo',
                            'resuelto' => 'Resuelto',
                            'cronico' => 'Crónico',
                            'seguimiento' => 'En seguimiento',
                            'sospecha' => 'Sospecha'
                        ];
                        $statusClass = 'status-' . $diagnostico->estado;
                    @endphp
                    <span class="status-badge {{ $statusClass }}">
                        {{ $estadoLabels[$diagnostico->estado] ?? $diagnostico->estado }}
                    </span>
                </p>
            </div>
            
            @if($diagnostico->conducta_terapeutica)
            <div class="info-box">
                <h3>💊 Tratamiento Sugerido</h3>
                <p>{{ Str::limit($diagnostico->conducta_terapeutica, 200) }}</p>
            </div>
            @endif
            
            <p>El informe completo en PDF está adjunto a este email. Guárdalo para tus registros médicos.</p>
            
            <p>Si tienes alguna pregunta o necesitas más información sobre el diagnóstico, no dudes en contactarnos.</p>
            
            <p>¡Cuida mucho a {{ $mascota->nombre }}! 🐶🐱</p>
        </div>
        
        <div class="footer">
            <p>Este email fue generado automáticamente por el Sistema Veterinario TERE</p>
            <p>© {{ date('Y') }} TERE - Todos los derechos reservados</p>
        </div>
    </div>
</body>
</html>