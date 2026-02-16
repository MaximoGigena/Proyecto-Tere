<?php

namespace App\Exports;

use App\Services\UsuariosMetricasService;
use Maatwebsite\Excel\Concerns\FromArray;;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;


class UsuarioMetricasExport implements FromArray, WithHeadings, WithTitle, ShouldAutoSize, WithStyles
{
    protected $filtros;
    protected $metricasService;
    protected $metricas;
    
    public function __construct(array $filtros, UsuariosMetricasService $metricasService)
    {
        $this->filtros = $filtros;
        $this->metricasService = $metricasService;
    }
    
    public function array(): array
    {
        $resultado = $this->metricasService->obtenerMetricas(
            $this->filtros['reporte'],
            $this->filtros['fecha_desde'],
            $this->filtros['fecha_hasta'],
            'mensual',
            $this->filtros['tipo_usuario'] ?? null,
            $this->filtros['estado'] ?? null
        );
        
        $this->metricas = $resultado['metricas'] ?? [];
        
        return $this->metricas;
    }
    
    public function headings(): array
    {
        if (empty($this->metricas)) {
            return [];
        }
        
        return array_keys($this->metricas[0]);
    }
    
    public function title(): string
    {
        return 'Metricas_' . $this->filtros['reporte'];
    }
    
    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}