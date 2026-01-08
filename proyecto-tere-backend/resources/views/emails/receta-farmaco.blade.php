<!-- resources/views/emails/receta-farmaco.blade.php -->
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Receta Médica</title>
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { 
            text-align: center; 
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white; 
            padding: 25px; 
            border-radius: 10px 10px 0 0; 
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        .content { 
            background: #f8f9fa; 
            padding: 30px; 
            border-radius: 0 0 10px 10px; 
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }
        .footer { 
            text-align: center; 
            margin-top: 30px; 
            padding: 20px; 
            color: #6c757d; 
            font-size: 12px; 
            border-top: 1px solid #dee2e6;
        }
        .info-box { 
            background: white; 
            padding: 20px; 
            margin: 15px 0; 
            border-radius: 8px; 
            border-left: 4px solid #667eea;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }
        .warning-box {
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            border-left: 4px solid #fdcb6e;
            padding: 15px;
            margin: 15px 0;
            border-radius: 5px;
        }
        .title { font-size: 24px; margin-bottom: 10px; }
        .subtitle { font-size: 18px; color: #495057; margin-bottom: 20px; }
        .section-title { 
            color: #495057; 
            font-size: 16px; 
            font-weight: 600; 
            margin-top: 20px;
            border-bottom: 2px solid #e9ecef;
            padding-bottom: 8px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1 class="title">💊 Receta Médica</h1>
            <p class="subtitle">Sistema Veterinario TERE</p>
        </div>
        
        <div class="content">
            <p>Hola <strong>{{ $mascota->usuario->nombre_completo ?? 'Tutor' }}</strong>,</p>
            
            <p>Te enviamos la receta médica para el tratamiento de tu mascota <strong>{{ $mascota->nombre }}</strong>.</p>
            
            <div class="info-box">
                <h3>📋 Detalles del Tratamiento</h3>
                <p><strong>🐾 Mascota:</strong> {{ $mascota->nombre }} ({{ $mascota->especie }})</p>
                <p><strong>💊 Fármaco:</strong> {{ $farmaco->tipoFarmaco->nombre ?? 'No especificado' }}</p>
                <p><strong>📅 Fecha de administración:</strong> {{ $farmaco->fecha_administracion->format('d/m/Y H:i') }}</p>
                <p><strong>🔄 Frecuencia:</strong> {{ $farmaco->frecuencia }}</p>
                <p><strong>⏱️ Duración del tratamiento:</strong> {{ $farmaco->duracion_tratamiento }}</p>
                <p><strong>📏 Dosis:</strong> {{ $farmaco->dosis }} {{ $farmaco->unidad_dosis }}</p>
                
                @if($farmaco->proxima_dosis)
                <p><strong>📅 Próxima dosis:</strong> {{ $farmaco->proxima_dosis->format('d/m/Y H:i') }}</p>
                @endif
                
                @if($farmaco->reacciones_adversas)
                <p><strong>⚠️ Reacciones adversas observadas:</strong><br>
                   {{ $farmaco->reacciones_adversas }}
                </p>
                @endif
            </div>
            
            <div class="warning-box">
                <h4>📌 Recomendaciones importantes:</h4>
                @if($farmaco->recomendaciones_tutor)
                    <p>{{ $farmaco->recomendaciones_tutor }}</p>
                @else
                    <p>• Siga las indicaciones de su veterinario</p>
                    <p>• Complete el tratamiento según lo prescrito</p>
                    <p>• Observe posibles reacciones adversas</p>
                @endif
            </div>
            
            <p>La receta médica en formato PDF está adjunta a este email. Guárdela para sus registros y presente cuando sea necesario.</p>
            
            <div class="section-title">ℹ️ Información importante</div>
            <p>Este documento tiene validez médica. Consulte con su veterinario ante cualquier duda o modificación del tratamiento.</p>
            
            <p>Si tienes alguna pregunta, no dudes en contactarnos.</p>
            
            <p>¡Gracias por confiar en nosotros para el cuidado de tu mascota! 🐶🐱</p>
        </div>
        
        <div class="footer">
            <p>Este email fue generado automáticamente por el Sistema Veterinario TERE</p>
            <p>© {{ date('Y') }} TERE - Todos los derechos reservados</p>
        </div>
    </div>
</body>
</html>