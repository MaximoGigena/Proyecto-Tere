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

     /**
     * Generar informe de revisión médica
     */
    public function generarInformeRevision($revision, $mascota, $tutor, $centroVeterinario = null)
    {
        $data = [
            'revision' => $revision,
            'mascota' => $mascota,
            'tutor' => $tutor,
            'centroVeterinario' => $centroVeterinario,
            'fecha_emision' => now()->format('d/m/Y H:i'),
            'urgenciaLabels' => [
                'rutinaria' => 'Rutinaria',
                'preventiva' => 'Preventiva', 
                'urgencia' => 'Urgencia',
                'emergencia' => 'Emergencia'
            ]
        ];

        $pdf = Pdf::loadView('pdf.certificado-revision', $data);
        
        // Guardar el PDF
        $filename = 'informe_revision_' . $revision->id . '_' . time() . '.pdf';
        $path = 'informes/' . $filename;
        
        Storage::put($path, $pdf->output());
        
        return [
            'path' => $path,
            'filename' => $filename,
            'full_path' => Storage::path($path)
        ];
    }

    /**
     * Generar registro de alergia
     */
    public function generarRegistroAlergia($alergia, $mascota, $tutor, $centroVeterinario = null)
    {
        $data = [
            'alergia' => $alergia,
            'mascota' => $mascota,
            'tutor' => $tutor,
            'centroVeterinario' => $centroVeterinario,
            'fecha_emision' => now()->format('d/m/Y'),
            'gravedadLabels' => [
                'leve' => 'Leve',
                'moderada' => 'Moderada', 
                'grave' => 'Grave'
            ],
            'estadoLabels' => [
                'activa' => 'Activa',
                'superada' => 'Superada', 
                'seguimiento' => 'Bajo seguimiento'
            ]
        ];

        $pdf = Pdf::loadView('pdf.certificado-alergia', $data);
        
        // Guardar el PDF
        $filename = 'certificado_alergia_' . $alergia->id . '_' . time() . '.pdf';
        $path = 'registros/' . $filename;
        
        Storage::put($path, $pdf->output());
        
        return [
            'path' => $path,
            'filename' => $filename,
            'full_path' => Storage::path($path)
        ];
    }
}