<!DOCTYPE html>
<!-- resources/views/certificado-vacuna.blade.php -->
<html>
<head>
    <meta charset="utf-8">
    <title>Certificado de Vacunación</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .header { text-align: center; border-bottom: 2px solid #333; padding-bottom: 10px; margin-bottom: 20px; }
        .section { margin-bottom: 15px; }
        .label { font-weight: bold; color: #333; }
        .value { margin-left: 10px; }
        .footer { margin-top: 30px; text-align: center; font-size: 12px; color: #666; }
        .signature { margin-top: 50px; border-top: 1px solid #333; padding-top: 10px; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Certificado de Vacunación</h1>
        <p>Sistema Veterinario TERE</p>
    </div>

    <div class="section">
        <h2>Información de la Mascota</h2>
        <p><span class="label">Nombre:</span> <span class="value">{{ $mascota->nombre }}</span></p>
        <p><span class="label">Especie:</span> <span class="value">{{ $mascota->especie }}</span></p>
        <p><span class="label">Raza:</span> <span class="value">{{ $mascota->raza }}</span></p>
    </div>

    <div class="section">
        <h2>Información de la Vacuna</h2>
        <p><span class="label">Tipo de Vacuna:</span> 
            <span class="value">
                @if($vacuna->tipo && $vacuna->tipo->nombre)
                    {{ $vacuna->tipo->nombre }}
                @else
                    Tipo de vacuna no especificado
                @endif
            </span>
        </p>
        <p><span class="label">Fecha de Aplicación:</span> <span class="value">{{ \Carbon\Carbon::parse($vacuna->fecha_aplicacion)->format('d/m/Y') }}</span></p>
        <p><span class="label">Número de Dosis:</span> <span class="value">{{ $vacuna->numero_dosis }}</span></p>
        <p><span class="label">Lote/Serie:</span> <span class="value">{{ $vacuna->lote_serie }}</span></p>
        @if($vacuna->fecha_proxima_dosis)
        <p><span class="label">Próxima Dosis:</span> <span class="value">{{ \Carbon\Carbon::parse($vacuna->fecha_proxima_dosis)->format('d/m/Y') }}</span></p>
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

    <div class="signature">
        <p>Documento generado automáticamente el {{ $fecha_emision }}</p>
    </div>

    <div class="footer">
        <p>Este es un documento oficial del Sistema Veterinario TERE</p>
    </div>
</body>
</html>