<?php
// app/Services/Reportes/PdfExportService.php

namespace App\Services\Reportes;

use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\View;
use Mpdf\Mpdf;
use setasign\Fpdi\Fpdi;

class PdfExportService
{
    /**
     * Generar PDF con datos estructurados y gráficos
     */
    public function generarPdfConGraficos(array $reporteData, string $titulo, array $chartData = null): string
    {
        Log::info('Generando PDF con título: ' . $titulo);
        
        try {
            // Validar datos básicos
            if (empty($reporteData)) {
                throw new \Exception('Los datos del reporte están vacíos');
            }
            
            // Generar imagen del gráfico si hay datos
            $imagenGrafico = null;
            if ($chartData && !empty($chartData)) {
                $imagenGrafico = $this->generarImagenDeGrafico($chartData);
            }
            
            // Preparar datos para la vista
            $data = [
                'titulo' => $titulo,
                'fechaGeneracion' => now()->format('d/m/Y H:i:s'),
                'datos' => $this->estructurarDatosParaVista($reporteData),
                'imagenGrafico' => $imagenGrafico,
                'configGrafico' => $chartData['config'] ?? null
            ];
            
            Log::info('Datos preparados para PDF', ['tiene_grafico' => !empty($imagenGrafico)]);
            
            // Generar HTML
            $html = View::make('reportes.pdf-con-graficos', $data)->render();
            
            // Configurar PDF con mPDF para mejor soporte de imágenes
            return $this->generarConMPdf($html, $titulo);
            
        } catch (\Exception $e) {
            Log::error('Error crítico al generar PDF: ' . $e->getMessage());
            return $this->generarPdfDeError($titulo, $e->getMessage());
        }
    }
    
    /**
     * Generar imagen del gráfico usando GD
     */
    private function generarImagenDeGrafico(array $chartData): ?string
    {
        try {
            if (!extension_loaded('gd')) {
                Log::warning('GD no está disponible, usando placeholder');
                return $this->generarPlaceholderGrafico($chartData);
            }
            
            // Si ya tenemos una imagen del frontend
            if (isset($chartData['config']['chartImage']) && 
                !empty($chartData['config']['chartImage'])) {
                return $this->procesarImagenFrontend($chartData['config']['chartImage']);
            }
            
            // Generar gráfico basado en los datos
            return $this->crearGraficoDesdeDatos($chartData);
            
        } catch (\Exception $e) {
            Log::error('Error generando imagen de gráfico: ' . $e->getMessage());
            return $this->generarPlaceholderGrafico($chartData);
        }
    }
    
    /**
     * Procesar imagen del frontend (base64)
     */
    private function procesarImagenFrontend(string $base64Image): string
    {
        // Eliminar el prefijo data:image/png;base64,
        $base64Image = str_replace('data:image/png;base64,', '', $base64Image);
        $base64Image = str_replace('data:image/jpeg;base64,', '', $base64Image);
        
        // Decodificar
        $imageData = base64_decode($base64Image);
        
        // Guardar temporalmente
        $tempFile = tempnam(sys_get_temp_dir(), 'chart_') . '.png';
        file_put_contents($tempFile, $imageData);
        
        return $tempFile;
    }
    
    /**
     * Crear gráfico desde datos usando GD
     */
    private function crearGraficoDesdeDatos(array $chartData): string
    {
        $tipo = $chartData['type'] ?? 'bar';
        $titulo = $chartData['config']['titulo'] ?? 'Gráfico';
        $width = 800;
        $height = 400;
        
        // Crear imagen
        $image = imagecreatetruecolor($width, $height);
        
        // Colores
        $backgroundColor = imagecolorallocate($image, 248, 249, 250); // #f8f9fa
        $white = imagecolorallocate($image, 255, 255, 255);
        $borderColor = imagecolorallocate($image, 222, 226, 230); // #dee2e6
        $titleColor = imagecolorallocate($image, 44, 62, 80); // #2c3e50
        $textColor = imagecolorallocate($image, 108, 117, 125); // #6c757d
        
        // Fondo
        imagefilledrectangle($image, 0, 0, $width, $height, $backgroundColor);
        imagefilledrectangle($image, 50, 50, $width - 50, $height - 50, $white);
        imagerectangle($image, 50, 50, $width - 50, $height - 50, $borderColor);
        
        // Título
        imagettftext($image, 16, 0, $width/2 - 100, 40, $titleColor, 
            $this->obtenerFuente(), $titulo);
        
        // Si tenemos datos del gráfico
        if (isset($chartData['data']['metricas']) || isset($chartData['data']['labels'])) {
            $this->dibujarDatosEnGrafico($image, $chartData['data'], $tipo, $width, $height);
        } else {
            // Mensaje si no hay datos
            $text = "Datos del gráfico disponibles";
            $bbox = imagettfbbox(12, 0, $this->obtenerFuente(), $text);
            $textWidth = $bbox[2] - $bbox[0];
            $x = ($width - $textWidth) / 2;
            imagettftext($image, 12, 0, $x, $height/2, $textColor, 
                $this->obtenerFuente(), $text);
        }
        
        // Guardar temporalmente
        $tempFile = tempnam(sys_get_temp_dir(), 'chart_') . '.png';
        imagepng($image, $tempFile);
        imagedestroy($image);
        
        return $tempFile;
    }
    
    /**
     * Dibujar datos en el gráfico
     */
    private function dibujarDatosEnGrafico($image, $data, $tipo, $width, $height): void
    {
        $colors = [
            imagecolorallocate($image, 52, 152, 219), // Azul
            imagecolorallocate($image, 46, 204, 113), // Verde
            imagecolorallocate($image, 155, 89, 182), // Púrpura
            imagecolorallocate($image, 241, 196, 15), // Amarillo
            imagecolorallocate($image, 230, 126, 34), // Naranja
            imagecolorallocate($image, 231, 76, 60),  // Rojo
        ];
        
        // Para gráfico de barras
        if ($tipo === 'bar' && isset($data['metricas'])) {
            $metricas = $data['metricas'];
            $numBarras = count($metricas);
            $barWidth = min(60, ($width - 200) / max($numBarras, 1));
            $spacing = (($width - 200) / max($numBarras, 1)) - $barWidth;
            $startX = 100;
            $baseY = $height - 100;
            
            $maxVal = 0;
            $labels = [];
            $values = [];
            
            foreach ($metricas as $key => $metrica) {
                if (is_array($metrica) && isset($metrica['valor'])) {
                    $val = is_numeric($metrica['valor']) ? $metrica['valor'] : 0;
                    $values[] = $val;
                    $labels[] = $metrica['etiqueta'] ?? $key;
                    $maxVal = max($maxVal, $val);
                }
            }
            
            if ($maxVal === 0) $maxVal = 1;
            
            for ($i = 0; $i < count($values); $i++) {
                $barHeight = ($values[$i] / $maxVal) * ($height - 200) * 0.8;
                $x = $startX + ($i * ($barWidth + $spacing));
                $y = $baseY - $barHeight;
                
                // Dibujar barra
                imagefilledrectangle($image, $x, $y, $x + $barWidth, $baseY, 
                    $colors[$i % count($colors)]);
                
                // Valor
                imagettftext($image, 10, 0, $x + $barWidth/2 - 10, $y - 10, 
                    imagecolorallocate($image, 44, 62, 80), $this->obtenerFuente(), 
                    number_format($values[$i]));
                
                // Etiqueta
                if (isset($labels[$i])) {
                    $label = substr($labels[$i], 0, 15) . (strlen($labels[$i]) > 15 ? '...' : '');
                    imagettftext($image, 10, 0, $x + $barWidth/2 - 15, $baseY + 20, 
                        imagecolorallocate($image, 108, 117, 125), $this->obtenerFuente(), 
                        $label);
                }
            }
        }
    }
    
    /**
     * Generar placeholder si no hay gráfico
     */
    private function generarPlaceholderGrafico(array $chartData): string
    {
        $titulo = $chartData['config']['titulo'] ?? 'Gráfico del Reporte';
        $tipo = $chartData['type'] ?? 'bar';
        
        $svg = '<?xml version="1.0" encoding="UTF-8"?>
        <svg width="800" height="400" xmlns="http://www.w3.org/2000/svg">
            <rect width="800" height="400" fill="#f8f9fa"/>
            <rect x="50" y="50" width="700" height="300" fill="white" stroke="#dee2e6" stroke-width="1"/>
            
            <text x="400" y="40" text-anchor="middle" fill="#2c3e50" font-family="Arial" font-size="18" font-weight="bold">
                ' . htmlspecialchars($titulo) . '
            </text>
            
            <text x="400" y="80" text-anchor="middle" fill="#3498db" font-family="Arial" font-size="14">
                Tipo: ' . htmlspecialchars(ucfirst($tipo)) . '
            </text>';
        
        // Dibujar gráfico de ejemplo según el tipo
        if ($tipo === 'bar') {
            $svg .= '
            <rect x="150" y="250" width="100" height="100" fill="#3498db" opacity="0.8"/>
            <rect x="300" y="200" width="100" height="150" fill="#2ecc71" opacity="0.8"/>
            <rect x="450" y="150" width="100" height="200" fill="#9b59b6" opacity="0.8"/>
            <rect x="600" y="100" width="100" height="250" fill="#f1c40f" opacity="0.8"/>
            ';
        } elseif ($tipo === 'line') {
            $svg .= '
            <polyline points="100,300 250,200 400,150 550,100 700,150" fill="none" stroke="#3498db" stroke-width="3"/>
            <circle cx="100" cy="300" r="4" fill="#3498db"/>
            <circle cx="250" cy="200" r="4" fill="#3498db"/>
            <circle cx="400" cy="150" r="4" fill="#3498db"/>
            <circle cx="550" cy="100" r="4" fill="#3498db"/>
            <circle cx="700" cy="150" r="4" fill="#3498db"/>
            ';
        } elseif ($tipo === 'pie') {
            $svg .= '
            <circle cx="400" cy="200" r="100" fill="#3498db" opacity="0.6"/>
            <path d="M400,100 A100,100 0 0,1 500,200 L400,200 Z" fill="#2ecc71" opacity="0.8"/>
            <path d="M400,100 A100,100 0 0,1 300,200 L400,200 Z" fill="#9b59b6" opacity="0.8"/>
            ';
        }
        
        $svg .= '
            <text x="400" y="360" text-anchor="middle" fill="#7f8c8d" font-family="Arial" font-size="12">
                Visualización del gráfico generada automáticamente
            </text>
        </svg>';
        
        $tempFile = tempnam(sys_get_temp_dir(), 'chart_') . '.svg';
        file_put_contents($tempFile, $svg);
        
        return $tempFile;
    }
    
    /**
     * Generar PDF usando mPDF (mejor para imágenes)
     */
    private function generarConMPdf(string $html, string $titulo): string
    {
        $mpdf = new Mpdf([
            'mode' => 'utf-8',
            'format' => 'A4',
            'default_font' => 'dejavusans',
            'margin_left' => 15,
            'margin_right' => 15,
            'margin_top' => 25,
            'margin_bottom' => 25,
            'margin_header' => 10,
            'margin_footer' => 10,
            'tempDir' => sys_get_temp_dir()
        ]);
        
        $mpdf->SetTitle($titulo);
        $mpdf->SetAuthor('Sistema de Reportes');
        $mpdf->SetCreator('Sistema de Reportes');
        
        $mpdf->WriteHTML($html);
        
        return $mpdf->Output('', 'S');
    }
    
    /**
     * Obtener fuente TrueType
     */
    private function obtenerFuente(): string
    {
        // Windows
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            $font = 'C:\Windows\Fonts\arial.ttf';
            if (file_exists($font)) return $font;
        }
        
        // Linux/Unix comunes
        $fonts = [
            '/usr/share/fonts/truetype/dejavu/DejaVuSans.ttf',
            '/usr/share/fonts/truetype/liberation/LiberationSans-Regular.ttf',
            '/usr/share/fonts/truetype/ubuntu/Ubuntu-R.ttf',
            '/usr/share/fonts/TTF/DejaVuSans.ttf'
        ];
        
        foreach ($fonts as $font) {
            if (file_exists($font)) {
                return $font;
            }
        }
        
        return 'arial';
    }
    
    /**
     * Método de compatibilidad
     */
    public function generarPdf(array $reporteData, string $titulo): string
    {
        return $this->generarPdfConGraficos($reporteData, $titulo, null);
    }
    
    /**
     * Generar PDF de error
     */
    private function generarPdfDeError(string $titulo, string $mensajeError): string
    {
        $html = '
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <title>Error en Reporte</title>
            <style>
                body { font-family: DejaVu Sans, sans-serif; padding: 40px; }
                .error-container { text-align: center; margin-top: 100px; }
                h1 { color: #e74c3c; }
                p { color: #7f8c8d; }
            </style>
        </head>
        <body>
            <div class="error-container">
                <h1>Error al generar el reporte</h1>
                <h2>' . htmlspecialchars($titulo, ENT_QUOTES, 'UTF-8') . '</h2>
                <p>' . htmlspecialchars($mensajeError, ENT_QUOTES, 'UTF-8') . '</p>
                <p>Por favor, contacte al administrador del sistema.</p>
            </div>
        </body>
        </html>';
        
        $mpdf = new Mpdf([
            'mode' => 'utf-8',
            'format' => 'A4',
            'default_font' => 'dejavusans'
        ]);
        
        $mpdf->WriteHTML($html);
        return $mpdf->Output('', 'S');
    }
    
    /**
     * Estructurar datos para la vista
     */
    private function estructurarDatosParaVista(array $datos): array
    {
        $estructurados = [
            'summary' => [],
            'metricas' => []
        ];
        
        // Procesar summary
        if (isset($datos['summary']) && is_array($datos['summary'])) {
            foreach ($datos['summary'] as $item) {
                if (is_array($item)) {
                    $estructurados['summary'][] = [
                        'label' => $item['label'] ?? 'Sin etiqueta',
                        'value' => $item['value'] ?? 'N/A',
                        'change' => isset($item['change']) ? [
                            'direction' => $item['change']['direction'] ?? 'stable',
                            'value' => $item['change']['value'] ?? ''
                        ] : null
                    ];
                }
            }
        }
        
        // Procesar métricas
        if (isset($datos['metricas']) && is_array($datos['metricas'])) {
            foreach ($datos['metricas'] as $key => $metrica) {
                if (is_array($metrica)) {
                    $estructurados['metricas'][] = [
                        'nombre' => $metrica['etiqueta'] ?? $key,
                        'valor' => $metrica['valor'] ?? 'N/A',
                        'tipo' => $metrica['tipo'] ?? 'General',
                        'descripcion' => $metrica['descripcion'] ?? ''
                    ];
                }
            }
        }
        
        return $estructurados;
    }
}