<?php

namespace App\Services;

use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;

class PdfService
{
    public function generarCertificadoVacuna($vacuna, $mascota, $tutor, $centroVeterinario = null)
    {
        $data = [
            'vacuna' => $vacuna,
            'mascota' => $mascota,
            'tutor' => $tutor,
            'centroVeterinario' => $centroVeterinario,
            'fecha_emision' => now()->format('d/m/Y'),
        ];

        $pdf = Pdf::loadView('pdf.certificado-vacuna', $data);
        
        // Guardar el PDF
        $filename = 'certificado_vacuna_' . $vacuna->id . '_' . time() . '.pdf';
        $path = 'certificados/' . $filename;
        
        Storage::put($path, $pdf->output());
        
        return [
            'path' => $path,
            'filename' => $filename,
            'full_path' => Storage::path($path)
        ];
    }
}