<?php
// app/Services/Reportes/GraficoService.php

namespace App\Services\Reportes;

use Illuminate\Support\Facades\Log;
use Exception;
use TCPDF;  // Usamos TCPDF en lugar de ChartPDF
use Mpdf\Mpdf;

class GraficoService
{
    /**
     * Generar gráfico de barras como imagen base64 usando TCPDF
     */
    public function generarGraficoBarras(array $datos, array $config = []): string
    {
        try {
            // Convertir datos al formato para TCPDF
            $chartData = $this->convertirDatosParaGrafico($datos, $config);
            
            // Generar imagen del gráfico
            $imagenBase64 = $this->generarImagenGraficoTCPDF($chartData);
            
            // Crear PDF con la imagen
            $pdfContent = $this->crearPDFConImagen($imagenBase64, $chartData['titulo']);
            
            // Convertir a base64
            return 'data:application/pdf;base64,' . base64_encode($pdfContent);
            
        } catch (\Exception $e) {
            Log::error('Error generando gráfico con TCPDF: ' . $e->getMessage());
            return $this->generarPlaceholderSimple($datos);
        }
    }
    
    /**
     * Convertir datos al formato para gráfico TCPDF
     */
    private function convertirDatosParaGrafico(array $datos, array $config): array
    {
        $tipo = $config['tipo'] ?? 'bar';
        $titulo = $config['titulo'] ?? 'Gráfico';
        $ancho = $config['ancho'] ?? 800;
        $alto = $config['alto'] ?? 400;
        
        $chartConfig = [
            'titulo' => $titulo,
            'tipo' => $tipo,
            'ancho' => $ancho,
            'alto' => $alto,
            'labels' => [],
            'valores' => [],
            'colores' => []
        ];
        
        // Si tenemos datos estructurados del frontend
        if (isset($datos['labels']) && isset($datos['datasets'])) {
            $chartConfig['labels'] = $datos['labels'];
            $chartConfig['valores'] = $datos['datasets'][0]['data'] ?? [];
            $chartConfig['colores'] = $datos['datasets'][0]['backgroundColor'] ?? [];
        }
        // Si tenemos métricas simples
        elseif (isset($datos['metricas'])) {
            $labels = [];
            $valores = [];
            $colores = [];
            
            foreach ($datos['metricas'] as $key => $metrica) {
                $labels[] = $metrica['etiqueta'] ?? $key;
                $valores[] = $metrica['valor'] ?? 0;
                $colores[] = $metrica['color'] ?? $this->generarColorAleatorio();
            }
            
            $chartConfig['labels'] = $labels;
            $chartConfig['valores'] = $valores;
            $chartConfig['colores'] = $colores;
        }
        // Datos en formato simple
        elseif (isset($datos['valores']) && isset($datos['labels'])) {
            $chartConfig['labels'] = $datos['labels'];
            $chartConfig['valores'] = $datos['valores'];
            $chartConfig['colores'] = $datos['colores'] ?? array_map(function() {
                return $this->generarColorAleatorio();
            }, $datos['valores']);
        }
        
        return $chartConfig;
    }
    
    /**
     * Generar imagen del gráfico usando TCPDF
     */
    private function generarImagenGraficoTCPDF(array $chartData): string
    {
        // Si GD está disponible, usarlo
        if (extension_loaded('gd')) {
            return $this->generarGraficoComoImagen($chartData);
        }
        
        // Si no, generar SVG con TCPDF
        return $this->generarSVGGraficoTCPDF($chartData);
    }
    
    /**
     * Generar gráfico como SVG usando TCPDF
     */
    private function generarSVGGraficoTCPDF(array $chartData): string
    {
        $labels = $chartData['labels'];
        $valores = $chartData['valores'];
        $colores = $chartData['colores'];
        $titulo = $chartData['titulo'];
        $ancho = $chartData['ancho'];
        $alto = $chartData['alto'];
        
        // Crear SVG
        $svg = '<?xml version="1.0" encoding="UTF-8"?>
        <svg width="' . $ancho . '" height="' . $alto . '" xmlns="http://www.w3.org/2000/svg">
            <rect width="' . $ancho . '" height="' . $alto . '" fill="#f8fafc"/>
            <rect x="50" y="50" width="' . ($ancho - 100) . '" height="' . ($alto - 100) . '" fill="white" stroke="#e2e8f0" stroke-width="1"/>
            
            <!-- Título -->
            <text x="' . ($ancho / 2) . '" y="30" text-anchor="middle" fill="#2c5282" font-family="Arial" font-size="18" font-weight="bold">
                ' . htmlspecialchars($titulo) . '
            </text>';
        
        // Calcular dimensiones para barras
        $numBarras = count($valores);
        $anchoBarra = min(80, (($ancho - 200) / max($numBarras, 1)) * 0.6);
        $espacio = (($ancho - 200) / max($numBarras, 1)) * 0.4;
        $xInicio = 100;
        $yBase = $alto - 100;
        $maxValor = max($valores) ?: 1;
        
        // Dibujar barras
        foreach ($valores as $i => $valor) {
            $altura = ($valor / $maxValor) * ($alto - 200) * 0.8;
            $x = $xInicio + ($i * ($anchoBarra + $espacio));
            $y = $yBase - $altura;
            $color = $colores[$i] ?? $this->generarColorAleatorio();
            
            $svg .= '
            <!-- Barra ' . ($i + 1) . ' -->
            <rect x="' . $x . '" y="' . $y . '" width="' . $anchoBarra . '" height="' . $altura . '" fill="' . $color . '" opacity="0.7"/>
            
            <!-- Valor -->
            <text x="' . ($x + $anchoBarra / 2) . '" y="' . ($y - 10) . '" text-anchor="middle" fill="#4a5568" font-family="Arial" font-size="12">
                ' . $valor . '
            </text>';
            
            // Etiqueta
            if (isset($labels[$i])) {
                $svg .= '
                <text x="' . ($x + $anchoBarra / 2) . '" y="' . ($yBase + 30) . '" text-anchor="middle" fill="#4a5568" font-family="Arial" font-size="12">
                    ' . htmlspecialchars($labels[$i]) . '
                </text>';
            }
        }
        
        // Eje Y
        $svg .= '
            <!-- Eje Y -->
            <line x1="80" y1="50" x2="80" y2="' . $yBase . '" stroke="#4a5568" stroke-width="2"/>
            
            <!-- Eje X -->
            <line x1="80" y1="' . $yBase . '" x2="' . ($ancho - 50) . '" y2="' . $yBase . '" stroke="#4a5568" stroke-width="2"/>
            
            <!-- Etiqueta eje Y -->
            <text x="40" y="' . ($alto / 2) . '" text-anchor="middle" fill="#4a5568" font-family="Arial" font-size="12" transform="rotate(-90 40 ' . ($alto / 2) . ')">
                Valores
            </text>
            
            <!-- Etiqueta eje X -->
            <text x="' . ($ancho / 2) . '" y="' . ($alto - 50) . '" text-anchor="middle" fill="#4a5568" font-family="Arial" font-size="12">
                Categorías
            </text>
        </svg>';
        
        return 'data:image/svg+xml;base64,' . base64_encode($svg);
    }
    
    /**
     * Crear PDF con imagen usando mPDF
     */
    private function crearPDFConImagen(string $imagenBase64, string $titulo): string
    {
        // Extraer solo los datos base64 de la imagen
        if (strpos($imagenBase64, 'base64,') !== false) {
            $parts = explode('base64,', $imagenBase64);
            $imagenData = base64_decode($parts[1]);
        } else {
            $imagenData = base64_decode($imagenBase64);
        }
        
        // Guardar imagen temporalmente
        $tempFile = tempnam(sys_get_temp_dir(), 'grafico_');
        file_put_contents($tempFile, $imagenData);
        
        // Crear HTML para PDF
        $html = '
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <title>' . htmlspecialchars($titulo) . '</title>
            <style>
                body { font-family: dejavusans; }
                .titulo { text-align: center; font-size: 24px; margin-bottom: 30px; }
                .grafico { text-align: center; margin: 20px 0; }
                .fecha { text-align: right; font-size: 12px; color: #666; margin-top: 30px; }
            </style>
        </head>
        <body>
            <div class="titulo">' . htmlspecialchars($titulo) . '</div>
            <div class="grafico">
                <img src="' . $tempFile . '" style="max-width: 100%; height: auto;">
            </div>
            <div class="fecha">
                Generado el: ' . date('d/m/Y H:i:s') . '
            </div>
        </body>
        </html>';
        
        // Crear PDF con mPDF
        $mpdf = new Mpdf([
            'mode' => 'utf-8',
            'format' => 'A4',
            'default_font' => 'dejavusans',
            'margin_left' => 15,
            'margin_right' => 15,
            'margin_top' => 25,
            'margin_bottom' => 25,
            'margin_header' => 10,
            'margin_footer' => 10
        ]);
        
        $mpdf->SetTitle($titulo);
        $mpdf->WriteHTML($html);
        
        // Eliminar archivo temporal
        unlink($tempFile);
        
        return $mpdf->Output('', 'S');
    }
    
    /**
     * Generar color aleatorio en formato RGB
     */
    private function generarColorAleatorio(): string
    {
        $colores = [
            'rgba(255, 99, 132, 0.7)',
            'rgba(54, 162, 235, 0.7)',
            'rgba(255, 206, 86, 0.7)',
            'rgba(75, 192, 192, 0.7)',
            'rgba(153, 102, 255, 0.7)',
            'rgba(255, 159, 64, 0.7)',
            'rgba(199, 199, 199, 0.7)',
            'rgba(83, 102, 255, 0.7)',
            'rgba(40, 159, 64, 0.7)',
            'rgba(210, 199, 199, 0.7)'
        ];
        
        return $colores[array_rand($colores)];
    }
    
    /**
     * Generar placeholder simple como SVG
     */
    private function generarPlaceholderSimple(array $datos): string
    {
        $titulo = isset($datos['titulo']) ? htmlspecialchars($datos['titulo']) : 'Gráfico';
        
        $svg = '<?xml version="1.0" encoding="UTF-8"?>
        <svg width="800" height="400" xmlns="http://www.w3.org/2000/svg">
            <rect width="800" height="400" fill="#f8fafc"/>
            <rect x="50" y="50" width="700" height="300" fill="white" stroke="#e2e8f0" stroke-width="1"/>
            
            <!-- Título -->
            <text x="400" y="30" text-anchor="middle" fill="#2c5282" font-family="Arial" font-size="18" font-weight="bold">
                ' . $titulo . '
            </text>
            
            <!-- Mensaje -->
            <text x="400" y="200" text-anchor="middle" fill="#718096" font-family="Arial" font-size="14">
                Gráfico generado por el sistema
            </text>
            
            <!-- Barra de ejemplo -->
            <rect x="150" y="250" width="100" height="100" fill="#4299e1" opacity="0.7"/>
            <rect x="300" y="200" width="100" height="150" fill="#48bb78" opacity="0.7"/>
            <rect x="450" y="150" width="100" height="200" fill="#ed8936" opacity="0.7"/>
            <rect x="600" y="100" width="100" height="250" fill="#9f7aea" opacity="0.7"/>
            
            <!-- Etiquetas -->
            <text x="200" y="370" text-anchor="middle" fill="#4a5568" font-family="Arial" font-size="12">Métrica 1</text>
            <text x="350" y="370" text-anchor="middle" fill="#4a5568" font-family="Arial" font-size="12">Métrica 2</text>
            <text x="500" y="370" text-anchor="middle" fill="#4a5568" font-family="Arial" font-size="12">Métrica 3</text>
            <text x="650" y="370" text-anchor="middle" fill="#4a5568" font-family="Arial" font-size="12">Métrica 4</text>
        </svg>';
        
        return 'data:image/svg+xml;base64,' . base64_encode($svg);
    }
    
    /**
     * Método alternativo: Generar gráfico como imagen usando GD (si está disponible)
     */
    public function generarGraficoComoImagen(array $chartData): string
    {
        if (!extension_loaded('gd')) {
            return $this->generarSVGGraficoTCPDF($chartData);
        }
        
        $labels = $chartData['labels'];
        $valores = $chartData['valores'];
        $titulo = $chartData['titulo'];
        $ancho = $chartData['ancho'];
        $alto = $chartData['alto'];
        
        // Crear imagen
        $imagen = imagecreatetruecolor($ancho, $alto);
        
        // Colores
        $blanco = imagecolorallocate($imagen, 255, 255, 255);
        $fondo = imagecolorallocate($imagen, 248, 250, 252); // #f8fafc
        $borde = imagecolorallocate($imagen, 226, 232, 240); // #e2e8f0
        $texto = imagecolorallocate($imagen, 74, 85, 104);    // #4a5568
        $tituloColor = imagecolorallocate($imagen, 44, 82, 130); // #2c5282
        $eje = imagecolorallocate($imagen, 74, 85, 104);     // #4a5568
        
        // Fondo
        imagefilledrectangle($imagen, 0, 0, $ancho, $alto, $fondo);
        imagefilledrectangle($imagen, 50, 50, $ancho - 50, $alto - 50, $blanco);
        imagerectangle($imagen, 49, 49, $ancho - 51, $alto - 51, $borde);
        
        // Título
        imagettftext($imagen, 18, 0, $ancho / 2 - 50, 40, $tituloColor, $this->obtenerFuente(), $titulo);
        
        // Calcular dimensiones
        $numBarras = count($valores);
        $maxValor = max($valores) ?: 1;
        $anchoBarra = min(80, (($ancho - 200) / max($numBarras, 1)) * 0.6);
        $espacio = (($ancho - 200) / max($numBarras, 1)) * 0.4;
        $xInicio = 100;
        $yBase = $alto - 100;
        
        // Dibujar barras
        foreach ($valores as $i => $valor) {
            $altura = ($valor / $maxValor) * ($alto - 200) * 0.8;
            $x = $xInicio + ($i * ($anchoBarra + $espacio));
            $y = $yBase - $altura;
            
            // Color de la barra
            $colorBarra = imagecolorallocatealpha(
                $imagen,
                rand(50, 200),
                rand(50, 200),
                rand(50, 200),
                70 // 70/127 = ~55% transparencia
            );
            
            // Dibujar barra
            imagefilledrectangle($imagen, $x, $y, $x + $anchoBarra, $yBase, $colorBarra);
            
            // Valor
            imagettftext($imagen, 10, 0, $x + $anchoBarra/2 - 10, $y - 5, $texto, $this->obtenerFuente(), $valor);
            
            // Etiqueta
            if (isset($labels[$i])) {
                imagettftext($imagen, 10, 0, $x + $anchoBarra/2 - 20, $yBase + 20, $texto, $this->obtenerFuente(), $labels[$i]);
            }
        }
        
        // Ejes
        imageline($imagen, 80, 50, 80, $yBase, $eje);
        imageline($imagen, 80, $yBase, $ancho - 50, $yBase, $eje);
        
        // Guardar como PNG
        ob_start();
        imagepng($imagen);
        $imagenData = ob_get_clean();
        imagedestroy($imagen);
        
        return 'data:image/png;base64,' . base64_encode($imagenData);
    }
    
    /**
     * Obtener ruta de fuente TTF
     */
    private function obtenerFuente(): string
    {
        // Windows
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            return 'C:\Windows\Fonts\arial.ttf';
        }
        
        // Linux/Unix
        return '/usr/share/fonts/truetype/dejavu/DejaVuSans.ttf';
    }
    
    /**
     * Método para generar PDF directamente con TCPDF (opcional)
     */
    public function generarPDFDirectoTCPDF(array $datos, array $config = []): string
    {
        try {
            $titulo = $config['titulo'] ?? 'Reporte de Gráfico';
            
            // Crear instancia de TCPDF
            $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
            
            // Configurar documento
            $pdf->SetCreator(PDF_CREATOR);
            $pdf->SetAuthor('Sistema');
            $pdf->SetTitle($titulo);
            $pdf->SetSubject($titulo);
            
            // Eliminar header y footer por defecto
            $pdf->setPrintHeader(false);
            $pdf->setPrintFooter(false);
            
            // Añadir página
            $pdf->AddPage();
            
            // Generar gráfico como imagen
            $imagenData = $this->generarGraficoComoImagen(
                $this->convertirDatosParaGrafico($datos, $config)
            );
            
            // Extraer datos de la imagen
            $imagenData = str_replace('data:image/png;base64,', '', $imagenData);
            $imagenData = base64_decode($imagenData);
            
            // Guardar temporalmente
            $tempFile = tempnam(sys_get_temp_dir(), 'grafico_');
            file_put_contents($tempFile, $imagenData);
            
            // Agregar imagen al PDF
            $pdf->Image($tempFile, 15, 25, 180, 0, 'PNG', '', '', false, 300, '', false, false, 0);
            
            // Agregar título
            $pdf->SetFont('helvetica', 'B', 16);
            $pdf->Cell(0, 10, $titulo, 0, 1, 'C');
            
            // Agregar fecha
            $pdf->SetFont('helvetica', '', 10);
            $pdf->Cell(0, 10, 'Generado el: ' . date('d/m/Y H:i:s'), 0, 1, 'R');
            
            // Eliminar archivo temporal
            unlink($tempFile);
            
            // Devolver PDF como string
            return $pdf->Output('', 'S');
            
        } catch (Exception $e) {
            Log::error('Error generando PDF con TCPDF: ' . $e->getMessage());
            throw $e;
        }
    }
}