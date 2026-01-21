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

    /**
     * Generar certificado de diagnóstico
     */
    public function generarCertificadoDiagnostico($diagnostico, $mascota, $tutor, $centroVeterinario = null)
    {
        $data = [
            'diagnostico' => $diagnostico,
            'mascota' => $mascota,
            'tutor' => $tutor,
            'centroVeterinario' => $centroVeterinario,
            'fecha_emision' => now()->format('d/m/Y'),
            'estadoLabels' => [
                'activo' => 'Activo',
                'resuelto' => 'Resuelto',
                'cronico' => 'Crónico',
                'seguimiento' => 'En seguimiento',
                'sospecha' => 'Sospecha'
            ]
        ];

        $pdf = Pdf::loadView('pdf.certificado-diagnostico', $data);
        
        // Guardar el PDF
        $filename = 'diagnostico_' . $diagnostico->id . '_' . time() . '.pdf';
        $path = 'diagnosticos/' . $filename;
        
        Storage::put($path, $pdf->output());
        
        return [
            'path' => $path,
            'filename' => $filename,
            'full_path' => Storage::path($path)
        ];
    }
    
    // Agrega este método al PdfService
    public function generarCertificadoTerapia($terapia, $mascota, $tutor, $centroVeterinario = null)
    {
        $evolucionLabels = [
            'mejoria' => 'Mejoría',
            'estable' => 'Estable',
            'empeoramiento' => 'Empeoramiento'
        ];

        $data = [
            'terapia' => $terapia,
            'mascota' => $mascota,
            'tutor' => $tutor,
            'centroVeterinario' => $centroVeterinario,
            'fecha_emision' => now()->format('d/m/Y H:i'),
            'evolucionLabels' => $evolucionLabels
        ];

        $pdf = Pdf::loadView('pdf.certificado-terapia', $data);
        
        // Guardar el PDF
        $filename = 'certificado_terapia_' . $terapia->id . '_' . time() . '.pdf';
        $path = 'terapias/' . $filename;
        
        Storage::put($path, $pdf->output());
        
        return [
            'path' => $path,
            'filename' => $filename,
            'full_path' => Storage::path($path)
        ];
    }

    /**
     * Generar receta de fármaco
     */
    public function generarRecetaFarmaco($farmaco, $mascota, $tutor, $centroVeterinario = null)
    {
        $data = [
            'farmaco' => $farmaco,
            'mascota' => $mascota,
            'tutor' => $tutor,
            'centroVeterinario' => $centroVeterinario,
            'fecha_emision' => now()->format('d/m/Y H:i'),
        ];

        $pdf = Pdf::loadView('pdf.receta-farmaco', $data);
        
        // Guardar el PDF
        $filename = 'receta_farmaco_' . $farmaco->id . '_' . time() . '.pdf';
        $path = 'recetas/' . $filename;
        
        Storage::put($path, $pdf->output());
        
        return [
            'path' => $path,
            'filename' => $filename,
            'full_path' => Storage::path($path)
        ];
    }

    /**
     * Generar certificado de cirugía
     */
    public function generarCertificadoCirugia($cirugia, $mascota, $tutor, $centroVeterinario = null)
    {
        $resultadoLabels = [
            'satisfactorio' => 'Satisfactorio',
            'complicaciones' => 'Complicaciones',
            'estable' => 'Estable',
            'critico' => 'Crítico'
        ];
        
        $estadoLabels = [
            'recuperacion' => 'En recuperación',
            'alta' => 'Alta postoperatoria',
            'seguimiento' => 'Bajo seguimiento',
            'hospitalizado' => 'Hospitalizado'
        ];
        
        $etapaLabels = [
            'prequirurgica' => 'Prequirúrgica',
            'transquirurgica' => 'Transquirúrgica',
            'postquirurgica_inmediata' => 'Postquirúrgica inmediata',
            'postquirurgica_tardia' => 'Postquirúrgica tardía'
        ];

        $data = [
            'cirugia' => $cirugia,
            'mascota' => $mascota,
            'tutor' => $tutor,
            'centroVeterinario' => $centroVeterinario,
            'fecha_emision' => now()->format('d/m/Y H:i'),
            'resultadoLabels' => $resultadoLabels,
            'estadoLabels' => $estadoLabels,
            'etapaLabels' => $etapaLabels
        ];

        $pdf = Pdf::loadView('pdf.certificado-cirugia', $data);
        
        // Guardar el PDF
        $filename = 'certificado_cirugia_' . $cirugia->id . '_' . time() . '.pdf';
        $path = 'cirugias/' . $filename;
        
        Storage::put($path, $pdf->output());
        
        return [
            'path' => $path,
            'filename' => $filename,
            'full_path' => Storage::path($path)
        ];
    }

    /**
     * Generar certificado de procedimiento paliativo
     */
    public function generarCertificadoPaliativo($paliativo, $mascota, $tutor, $centroVeterinario = null)
    {
        $resultadoLabels = [
            'mejoria' => 'Mejoría evidente',
            'alivio' => 'Alivio parcial',
            'estabilizacion' => 'Estabilización',
            'sin_cambio' => 'Sin cambios',
            'empeoramiento' => 'Empeoramiento'
        ];
        
        $estadoLabels = [
            'estable' => 'Estable',
            'dolor_controlado' => 'Con dolor controlado',
            'dolor_parcial' => 'Con dolor parcialmente controlado',
            'deterioro' => 'En deterioro',
            'critico' => 'Crítico'
        ];
        
        $momentoLabels = [
            'inicio' => 'Inicio',
            'mantenimiento' => 'Mantenimiento',
            'rescue' => 'Rescate',
            'final' => 'Final'
        ];
        
        $frecuenciaUnidadLabels = [
            'horas' => 'horas',
            'dias' => 'días',
            'semanas' => 'semanas',
            'meses' => 'meses'
        ];

        $data = [
            'paliativo' => $paliativo,
            'mascota' => $mascota,
            'tutor' => $tutor,
            'centroVeterinario' => $centroVeterinario,
            'fecha_emision' => now()->format('d/m/Y H:i'),
            'resultadoLabels' => $resultadoLabels,
            'estadoLabels' => $estadoLabels,
            'momentoLabels' => $momentoLabels,
            'frecuenciaUnidadLabels' => $frecuenciaUnidadLabels
        ];

        $pdf = Pdf::loadView('pdf.certificado-paliativo', $data);
        
        // Guardar el PDF
        $filename = 'certificado_paliativo_' . $paliativo->id . '_' . time() . '.pdf';
        $path = 'paliativos/' . $filename;
        
        Storage::put($path, $pdf->output());
        
        return [
            'path' => $path,
            'filename' => $filename,
            'full_path' => Storage::path($path)
        ];
    }
}