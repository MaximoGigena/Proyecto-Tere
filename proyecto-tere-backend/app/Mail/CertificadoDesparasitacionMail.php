<?php
// app/Mail/CertificadoDesparasitacionMail.php

namespace App\Mail;

use App\Models\Mascota;
use App\Models\ProcedimientosMedicos\Desparasitacion;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;

class CertificadoDesparasitacionMail extends Mailable
{
    use Queueable, SerializesModels;

    public $pdfPath;
    public $mascota;
    public $desparasitacion;

    public function __construct(string $pdfPath, Mascota $mascota, Desparasitacion $desparasitacion)
    {
        $this->pdfPath = $pdfPath;
        $this->mascota = $mascota;
        $this->desparasitacion = $desparasitacion;
    }

    public function build()
    {
        return $this->subject('🐾 Certificado de Desparasitación - ' . $this->mascota->nombre)
                    ->view('emails.certificado-desparasitacion')
                    ->attach($this->pdfPath, [
                        'as' => 'certificado_desparasitacion_' . $this->mascota->nombre . '.pdf',
                        'mime' => 'application/pdf',
                    ]);
    }
}