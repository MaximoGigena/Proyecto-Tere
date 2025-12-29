<?php
// app/Mail/CertificadoRevisionMail.php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use App\Models\Mascota;
use App\Models\ProcedimientosMedicos\Revision;
use App\Models\ContactoUsuario; // ← Agrega esto

class CertificadoRevisionMail extends Mailable
{
    use Queueable, SerializesModels;

    public $pdfPath;
    public $mascota;
    public $revision;
    public $tutor; // ← Agrega esta propiedad

    /**
     * Create a new message instance.
     */
    public function __construct($pdfPath, Mascota $mascota, Revision $revision)
    {
        $this->pdfPath = $pdfPath;
        $this->mascota = $mascota;
        $this->revision = $revision;
        
        // Obtener el tutor desde ContactoUsuario
        $this->tutor = ContactoUsuario::where('usuario_id', $mascota->usuario_id)->first();
    }

    /**
     * Build the message.
     */
    public function build()
    {
        $tipoRevision = $this->revision->tipoRevision->nombre ?? 'Revisión Médica';
        $subject = "Informe de Revisión - {$this->mascota->nombre} - " . now()->format('d/m/Y');

        // Obtener el centro veterinario desde la relación
        $centroVeterinario = optional($this->revision->procesoMedico)->centroVeterinario;

        return $this->subject($subject)
                    ->view('emails.certificado-revision')
                    ->with([
                        'mascota' => $this->mascota,
                        'revision' => $this->revision,
                        'tutor' => $this->tutor, // ← Pasa el tutor a la vista
                        'centroVeterinario' => $centroVeterinario,
                    ])
                    ->attach($this->pdfPath, [
                        'as' => "certificado_revision_{$this->mascota->nombre}_{$this->revision->id}.pdf",
                        'mime' => 'application/pdf',
                    ]);
    }
}