<?php
// app/Mail/CertificadoTerapiaMail.php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use App\Models\Mascota;
use App\Models\Terapia;

class CertificadoTerapiaMail extends Mailable
{
    use Queueable, SerializesModels;

    public $pdfPath;
    public $mascota;
    public $terapia;

    public function __construct($pdfPath, $mascota, $terapia = null)
    {
        $this->pdfPath = $pdfPath;
        $this->mascota = $mascota;
        $this->terapia = $terapia;
    }

    public function build()
    {
        return $this->subject('Certificado de Terapia - ' . $this->mascota->nombre)
                    ->view('emails.certificado-terapia')
                    ->attach($this->pdfPath, [
                        'as' => 'certificado_terapia_' . $this->mascota->nombre . '_' . now()->format('Ymd') . '.pdf',
                        'mime' => 'application/pdf',
                    ]);
    }
}