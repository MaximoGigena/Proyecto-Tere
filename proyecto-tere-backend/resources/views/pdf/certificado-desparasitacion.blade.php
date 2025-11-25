<!DOCTYPE html>
<!-- resources/views/pdf/certificado-desparasitacion.blade.php -->
<html>
<head>
    <meta charset="utf-8">
    <title>Certificado de Desparasitación</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .header { text-align: center; border-bottom: 2px solid #10B981; padding-bottom: 10px; margin-bottom: 20px; }
        .section { margin-bottom: 15px; }
        .label { font-weight: bold; color: #333; }
        .value { margin-left: 10px; }
        .footer { margin-top: 30px; text-align: center; font-size: 12px; color: #666; }
        .signature { margin-top: 50px; border-top: 1px solid #333; padding-top: 10px; }
        .highlight { background-color: #F0FDF4; padding: 10px; border-radius: 5px; margin: 10px 0; }
    </style>
</head>
<body>
    <div class="header">
        <h1>💊 Certificado de Desparasitación</h1>
        <p>Sistema Veterinario TERE</p>
    </div>

    <div class="section">
        <h2>Información de la Mascota</h2>
        <p><span class="label">Nombre:</span> <span class="value">{{ $mascota->nombre }}</span></p>
        <p><span class="label">Especie:</span> <span class="value">{{ $mascota->especie }}</span></p>
        <p><span class="label">Raza:</span> <span class="value">{{ $mascota->raza }}</span></p>
        @if($desparasitacion->peso)
        <p><span class="label">Peso:</span> <span class="value">{{ $desparasitacion->peso }} kg</span></p>
        @endif
    </div>

    <div class="section">
        <h2>Información de la Desparasitación</h2>
        <p><span class="label">Tipo:</span> 
            <span class="value">
                @if($desparasitacion->tipoDesparasitacion && $desparasitacion->tipoDesparasitacion->nombre)
                    {{ $desparasitacion->tipoDesparasitacion->nombre }}
                @else
                    Tipo no especificado
                @endif
            </span>
        </p>
        <p><span class="label">Fecha de Aplicación:</span> <span class="value">{{ \Carbon\Carbon::parse($desparasitacion->fecha)->format('d/m/Y') }}</span></p>
        <p><span class="label">Producto:</span> <span class="value">{{ $desparasitacion->nombre_producto }}</span></p>
        <p><span class="label">Dosis:</span> <span class="value">{{ $desparasitacion->dosis }}</span></p>
        <p><span class="label">Frecuencia:</span> <span class="value">Cada {{ $desparasitacion->frecuencia_valor }} {{ $desparasitacion->frecuencia_unidad }}</span></p>
        
        @if($desparasitacion->proxima_fecha)
        <div class="highlight">
            <p><span class="label">Próxima Aplicación Sugerida:</span> <span class="value">{{ \Carbon\Carbon::parse($desparasitacion->proxima_fecha)->format('d/m/Y') }}</span></p>
        </div>
        @endif
    </div>

    @if($centroVeterinario)
    <div class="section">
        <h2>Centro Veterinario</h2>
        <p><span class="label">Nombre:</span> <span class="value">{{ $centroVeterinario->nombre }}</span></p>
        <p><span class="label">Dirección:</span> <span class="value">{{ $centroVeterinario->direccion }}</span></p>
    </div>
    @endif

    <div class="section">
        <h2>Información del Tutor</h2>
        <p><span class="label">Nombre:</span> <span class="value">{{ $tutor->nombre_completo }}</span></p>
        <p><span class="label">Email:</span> <span class="value">{{ $tutor->email }}</span></p>
        <p><span class="label">Teléfono:</span> <span class="value">{{ $tutor->telefono }}</span></p>
    </div>

    @if($desparasitacion->observaciones)
    <div class="section">
        <h2>Observaciones</h2>
        <p>{{ $desparasitacion->observaciones }}</p>
    </div>
    @endif

    <div class="signature">
        <p>Documento generado automáticamente el {{ $fecha_emision }}</p>
    </div>

    <div class="footer">
        <p>Este es un documento oficial del Sistema Veterinario TERE</p>
    </div>
</body>
</html>