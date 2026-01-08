<?php
// app/Mail/RecetaFarmacoMail.php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use App\Models\Mascota;
use App\Models\ProcedimientosMedicos\Farmaco;

class RecetaFarmacoMail extends Mailable
{
    use Queueable, SerializesModels;

    public $pdfPath;
    public $mascota;
    public $farmaco;

    public function __construct($pdfPath, $mascota, $farmaco = null)
    {
        $this->pdfPath = $pdfPath;
        $this->mascota = $mascota;
        $this->farmaco = $farmaco;
    }

    public function build()
    {
        return $this->subject('Receta Médica - ' . $this->mascota->nombre . ' - Sistema TERE')
                    ->view('emails.receta-farmaco')
                    ->attach($this->pdfPath, [
                        'as' => 'receta_medica_' . $this->mascota->nombre . '_' . now()->format('Ymd_His') . '.pdf',
                        'mime' => 'application/pdf',
                    ]);
    }
}