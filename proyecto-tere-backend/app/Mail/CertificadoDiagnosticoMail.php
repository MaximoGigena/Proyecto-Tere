<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use App\Models\Mascota;
use App\Models\Diagnostico;

class CertificadoDiagnosticoMail extends Mailable
{
    use Queueable, SerializesModels;

    public $pdfPath;
    public $mascota;
    public $diagnostico;

    public function __construct($pdfPath, $mascota, $diagnostico = null)
    {
        $this->pdfPath = $pdfPath;
        $this->mascota = $mascota;
        $this->diagnostico = $diagnostico;
    }

    public function build()
    {
        return $this->subject('Diagnóstico - ' . $this->mascota->nombre)
                    ->view('emails.certificado-diagnostico')
                    ->attach($this->pdfPath, [
                        'as' => 'diagnostico_' . $this->mascota->nombre . '_' . now()->format('Y-m-d') . '.pdf',
                        'mime' => 'application/pdf',
                    ]);
    }
}