<?php
// app/Mail/CertificadoPaliativoMail.php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use App\Models\Mascota;

class CertificadoPaliativoMail extends Mailable
{
    use Queueable, SerializesModels;

    public $pdfPath;
    public $mascota;
    public $paliativo;

    public function __construct($pdfPath, $mascota, $paliativo = null)
    {
        $this->pdfPath = $pdfPath;
        $this->mascota = $mascota;
        $this->paliativo = $paliativo;
    }

    public function build()
    {
        return $this->subject('Certificado de Procedimiento Paliativo - ' . $this->mascota->nombre)
                    ->view('emails.certificado-paliativo')
                    ->attach($this->pdfPath, [
                        'as' => 'certificado_paliativo_' . $this->mascota->nombre . '_' . now()->format('Y-m-d') . '.pdf',
                        'mime' => 'application/pdf',
                    ]);
    }
}