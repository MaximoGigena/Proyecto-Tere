<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use App\Models\Mascota;

class CertificadoCirugiaMail extends Mailable
{
    use Queueable, SerializesModels;

    public $pdfPath;
    public $mascota;
    public $cirugia;

    public function __construct($pdfPath, $mascota, $cirugia = null)
    {
        $this->pdfPath = $pdfPath;
        $this->mascota = $mascota;
        $this->cirugia = $cirugia;
    }

    public function build()
    {
        return $this->subject('Informe Quirúrgico - ' . $this->mascota->nombre)
                    ->view('emails.certificado-cirugia')
                    ->attach($this->pdfPath, [
                        'as' => 'informe_cirugia_' . $this->mascota->nombre . '_' . now()->format('Y-m-d') . '.pdf',
                        'mime' => 'application/pdf',
                    ]);
    }
}