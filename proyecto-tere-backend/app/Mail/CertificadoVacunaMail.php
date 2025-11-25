<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use App\Models\Mascota;
use App\Models\ProcedimientosMedicos\Vacuna;

class CertificadoVacunaMail extends Mailable
{
    use Queueable, SerializesModels;

    public $pdfPath;
    public $mascota;
    public $vacuna;

    public function __construct($pdfPath, $mascota, $vacuna = null)
    {
        $this->pdfPath = $pdfPath;
        $this->mascota = $mascota;
        $this->vacuna = $vacuna;
    }

    public function build()
    {
        return $this->subject('Certificado de Vacunación - ' . $this->mascota->nombre)
                    ->view('emails.certificado-vacuna')
                    ->attach($this->pdfPath, [
                        'as' => 'certificado_vacunacion_' . $this->mascota->nombre . '.pdf',
                        'mime' => 'application/pdf',
                    ]);
    }
}
