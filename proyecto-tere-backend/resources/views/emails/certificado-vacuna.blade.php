<!DOCTYPE html>
<!-- resources/views/emails/certificado-vacuna.blade.php -->
<html>
<head>
    <meta charset="utf-8">
    <title>Certificado de Vacunación</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { text-align: center; background: #4F46E5; color: white; padding: 20px; border-radius: 10px 10px 0 0; }
        .content { background: #f9f9f9; padding: 20px; border-radius: 0 0 10px 10px; }
        .footer { text-align: center; margin-top: 20px; padding: 20px; color: #666; font-size: 12px; }
        .info-box { background: white; padding: 15px; margin: 10px 0; border-radius: 5px; border-left: 4px solid #4F46E5; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🐾 Certificado de Vacunación</h1>
            <p>Sistema Veterinario TERE</p>
        </div>
        
        <div class="content">
            <p>Hola <strong>{{ $mascota->usuario->nombre_completo }}</strong>,</p>
            
            <p>Te enviamos el certificado de vacunación de tu mascota <strong>{{ $mascota->nombre }}</strong>.</p>
            
            <div class="info-box">
                <h3>📋 Resumen de la Vacunación</h3>
                <p><strong>Mascota:</strong> {{ $mascota->nombre }} ({{ $mascota->especie }})</p>
                <p><strong>Vacuna:</strong> {{ $vacuna->tipo->nombre ?? 'No especificado' }}</p>
                <p><strong>Fecha:</strong> {{ \Carbon\Carbon::parse($vacuna->fecha_aplicacion)->format('d/m/Y') }}</p>
                <p><strong>Dosis:</strong> {{ $vacuna->numero_dosis }}</p>
            </div>
            
            <p>El certificado en PDF está adjunto a este email. Guárdalo para tus registros.</p>
            
            <p>Si tienes alguna pregunta, no dudes en contactarnos.</p>
            
            <p>¡Gracias por confiar en nosotros! 🐶🐱</p>
        </div>
        
        <div class="footer">
            <p>Este email fue generado automáticamente por el Sistema Veterinario TERE</p>
            <p>© {{ date('Y') }} TERE - Todos los derechos reservados</p>
        </div>
    </div>
</body>
</html>