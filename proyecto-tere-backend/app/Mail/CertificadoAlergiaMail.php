<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;
use App\Models\Mascota;
use App\Models\ProcedimientosMedicos\Alergia;

class CertificadoAlergiaMail extends Mailable
{
    use Queueable, SerializesModels;

    public $mascota;
    public $alergia;
    public $pdfPath;

    /**
     * Create a new message instance.
     */
    public function __construct($pdfPath, Mascota $mascota, Alergia $alergia)
    {
        $this->pdfPath = $pdfPath;
        $this->mascota = $mascota;
        $this->alergia = $alergia;
    }

    /**
     * Build the message.
     */
    public function build()
    {
        $gravedadLabels = [
            'leve' => 'Leve',
            'moderada' => 'Moderada', 
            'grave' => 'Grave'
        ];
        
        $estadoLabels = [
            'activa' => 'Activa',
            'superada' => 'Superada', 
            'seguimiento' => 'Bajo seguimiento'
        ];

        $gravedad = $gravedadLabels[$this->alergia->gravedad] ?? $this->alergia->gravedad;
        $estado = $estadoLabels[$this->alergia->estado] ?? $this->alergia->estado;

        return $this->subject('⚠️ Registro de Alergia - ' . $this->mascota->nombre)
                    ->view('emails.certificado-alergia')
                    ->with([
                        'tutor' => $this->alergia->procesoMedico->mascota->usuario->contacto ?? null,
                        'mascota' => $this->mascota,
                        'alergia' => $this->alergia,
                        'gravedad' => $gravedad,
                        'estado' => $estado,
                        'centroVeterinario' => $this->alergia->procesoMedico->centroVeterinario
                    ])
                    ->attach($this->pdfPath, [
                        'as' => 'registro_alergia_' . $this->alergia->id . '.pdf',
                        'mime' => 'application/pdf',
                    ]);
    }
}