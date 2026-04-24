<?php

namespace App\Services\Reportes;

use Mpdf\Mpdf;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\View;

class PdfExportService
{
    /**
     * Generar PDF con gráficos - VERSIÓN CORREGIDA
     * Ahora recibe los datos correctamente tipados desde el frontend
     */
    public function generarPdfConGraficos(array $reporteData, string $titulo, array $chartData = null): string
    {
        Log::info('📄 Generando PDF con título: ' . $titulo);
        Log::info('📊 Datos recibidos:', [
            'tiene_kpis' => isset($reporteData['kpis']),
            'tiene_metricas' => isset($reporteData['metricas']),
            'tiene_grafico' => !empty($chartData),
            'grafico_type' => $chartData['type'] ?? 'none'
        ]);
        
        try {
            if (empty($reporteData)) {
                throw new \Exception('Los datos del reporte están vacíos');
            }
            
            // 🔥 CORRECCIÓN 1: Procesar imagen del gráfico si viene del frontend
            $imagenGrafico = null;
            $configGraficoProcesado = null;
            
            if ($chartData && !empty($chartData)) {
                $imagenGrafico = $this->procesarImagenDesdeFrontend($chartData);
                $configGraficoProcesado = [
                    'tipo' => $chartData['type'] ?? 'bar',
                    'titulo' => $chartData['config']['titulo'] ?? $titulo,
                    'selectedMetrics' => $chartData['config']['selectedMetrics'] ?? [],
                    'notas' => $chartData['config']['notas'] ?? null
                ];
            }
            
            // 🔥 CORRECCIÓN 2: Estructurar datos correctamente según tipo
            $datosEstructurados = $this->estructurarDatosTipados($reporteData);
            
            // Preparar datos para la vista
            $data = [
                'titulo' => $titulo,
                'fechaGeneracion' => now()->format('d/m/Y H:i:s'),
                'datos' => $datosEstructurados,
                'imagenGrafico' => $imagenGrafico,
                'configGrafico' => $configGraficoProcesado
            ];
            
            Log::info('✅ Datos preparados para PDF', [
                'summary_count' => count($datosEstructurados['summary']),
                'metricas_count' => count($datosEstructurados['metricas']),
                'tiene_imagen' => !empty($imagenGrafico)
            ]);
            
            // Generar HTML
            $html = View::make('reportes.pdf-con-graficos', $data)->render();
            
            // Configurar PDF con mPDF
            return $this->generarConMPdf($html, $titulo);
            
        } catch (\Exception $e) {
            Log::error('❌ Error crítico al generar PDF: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            return $this->generarPdfDeError($titulo, $e->getMessage());
        }
    }
    
    /**
     * 🔥 NUEVO: Procesar imagen que viene del frontend (ya renderizada)
     */
    private function procesarImagenDesdeFrontend(array $chartData): ?string
    {
        try {
            // Caso 1: El frontend ya envió la imagen en base64
            if (isset($chartData['config']['chartImage']) && !empty($chartData['config']['chartImage'])) {
                Log::info('📸 Procesando imagen del frontend en base64');
                return $this->guardarImagenBase64($chartData['config']['chartImage']);
            }
            
            // Caso 2: El frontend envió los datos para generar el gráfico
            if (isset($chartData['data']) && !empty($chartData['data'])) {
                Log::info('📊 Generando gráfico desde datos estructurados');
                return $this->generarGraficoDesdeDatos($chartData);
            }
            
            // Caso 3: Usar placeholder
            Log::warning('⚠️ No se encontró imagen ni datos de gráfico, usando placeholder');
            return $this->generarPlaceholderGrafico($chartData);
            
        } catch (\Exception $e) {
            Log::error('❌ Error procesando imagen: ' . $e->getMessage());
            return $this->generarPlaceholderGrafico($chartData);
        }
    }
    
    /**
     * 🔥 NUEVO: Guardar imagen base64 a archivo temporal
     */
    private function guardarImagenBase64(string $base64Image): string
    {
        // Limpiar el prefijo data:image/...
        $base64Image = preg_replace('/^data:image\/\w+;base64,/', '', $base64Image);
        $base64Image = str_replace(' ', '+', $base64Image);
        
        // Decodificar
        $imageData = base64_decode($base64Image);
        
        if ($imageData === false) {
            throw new \Exception('No se pudo decodificar la imagen base64');
        }
        
        // Guardar temporalmente
        $tempFile = tempnam(sys_get_temp_dir(), 'chart_') . '.png';
        file_put_contents($tempFile, $imageData);
        
        Log::info('✅ Imagen guardada temporalmente en: ' . $tempFile);
        
        return $tempFile;
    }
    
    /**
     * 🔥 NUEVO: Generar gráfico desde datos estructurados (para casos sin imagen frontend)
     */
    private function generarGraficoDesdeDatos(array $chartData): string
    {
        $tipo = $chartData['type'] ?? 'bar';
        $data = $chartData['data'] ?? [];
        $width = 800;
        $height = 400;
        
        // Crear imagen con GD
        if (!extension_loaded('gd')) {
            return $this->generarPlaceholderGrafico($chartData);
        }
        
        $image = imagecreatetruecolor($width, $height);
        
        // Colores
        $backgroundColor = imagecolorallocate($image, 248, 249, 250);
        $white = imagecolorallocate($image, 255, 255, 255);
        $borderColor = imagecolorallocate($image, 222, 226, 230);
        $titleColor = imagecolorallocate($image, 44, 62, 80);
        
        // Fondo
        imagefilledrectangle($image, 0, 0, $width, $height, $backgroundColor);
        imagefilledrectangle($image, 50, 50, $width - 50, $height - 50, $white);
        imagerectangle($image, 50, 50, $width - 50, $height - 50, $borderColor);
        
        // Título
        $titulo = $chartData['config']['titulo'] ?? 'Gráfico del Reporte';
        imagettftext($image, 16, 0, $width/2 - 100, 40, $titleColor, 
            $this->obtenerFuente(), $titulo);
        
        // Dibujar según tipo de gráfico
        if ($tipo === 'bar' && isset($data['metricas'])) {
            $this->dibujarGraficoBarras($image, $data, $width, $height);
        } elseif ($tipo === 'pie' && isset($data['metricas'])) {
            $this->dibujarGraficoTorta($image, $data, $width, $height);
        } elseif ($tipo === 'line' && isset($data['metricas'])) {
            $this->dibujarGraficoLineas($image, $data, $width, $height);
        }
        
        // Guardar
        $tempFile = tempnam(sys_get_temp_dir(), 'chart_') . '.png';
        imagepng($image, $tempFile);
        imagedestroy($image);
        
        return $tempFile;
    }
    
    /**
     * 🔥 NUEVO: Estructurar datos correctamente según su tipo
     */
    private function estructurarDatosTipados(array $datos): array
    {
        $estructurados = [
            'summary' => [],     // KPIs tipo contador/porcentaje
            'metricas' => []     // Métricas detalladas y distribuciones
        ];

        // 🔥 PROCESAR KPIs (RESUMEN)
        if (isset($datos['kpis']) && is_array($datos['kpis'])) {
            foreach ($datos['kpis'] as $kpi) {
                $tipo = $kpi['tipo'] ?? 'contador';
                
                // Distribuciones NO van al summary
                if ($tipo === 'distribucion' && isset($kpi['datos_distribucion'])) {
                    $estructurados['metricas'][] = [
                        'nombre' => $kpi['titulo'] ?? $kpi['nombre'],
                        'valor' => $this->formatearDistribucion($kpi['datos_distribucion']),
                        'tipo' => 'distribucion',
                        'descripcion' => $kpi['descripcion'] ?? '',
                        'distribucion' => $kpi['datos_distribucion']
                    ];
                } 
                // Contadores y porcentajes van al summary
                else {
                    $estructurados['summary'][] = [
                        'label' => $kpi['titulo'] ?? $kpi['nombre'],
                        'value' => $this->formatearValor($kpi['valor'], $tipo),
                        'change' => isset($kpi['tendencia']) && $kpi['tendencia'] !== null 
                            ? [
                                'direction' => $kpi['tendencia'] > 0 ? 'up' : ($kpi['tendencia'] < 0 ? 'down' : 'stable'),
                                'value' => abs($kpi['tendencia']) . '%'
                            ]
                            : null
                    ];
                }
            }
        }

        // 🔥 PROCESAR MÉTRICAS DETALLADAS (del servicio ReporteUsuariosService)
        if (isset($datos['metricas']) && is_array($datos['metricas'])) {
            foreach ($datos['metricas'] as $key => $metrica) {
                // Caso: métrica viene con estructura ['total_usuarios' => [...]]
                if (is_array($metrica) && isset($metrica['valor'])) {
                    $tipo = $metrica['tipo'] ?? 'contador';
                    
                    if ($tipo === 'distribucion' && isset($metrica['datos'])) {
                        // Distribución con múltiples valores
                        $estructurados['metricas'][] = [
                            'nombre' => $metrica['etiqueta'] ?? $key,
                            'valor' => $this->formatearDistribucionArray($metrica['datos']),
                            'tipo' => 'distribucion',
                            'descripcion' => $metrica['descripcion'] ?? $this->getDescripcionMetrica($metrica),
                            'detalle' => $metrica['datos']  // Guardar detalle para tabla
                        ];
                    } 
                    elseif ($tipo === 'geografico' && isset($metrica['datos'])) {
                        $estructurados['metricas'][] = [
                            'nombre' => $metrica['etiqueta'] ?? $key,
                            'valor' => $this->formatearDistribucionArray($metrica['datos']),
                            'tipo' => 'geografico',
                            'descripcion' => $metrica['descripcion'] ?? 'Distribución geográfica de usuarios',
                            'detalle' => $metrica['datos']
                        ];
                    }
                    else {
                        // Contador simple
                        $estructurados['metricas'][] = [
                            'nombre' => $metrica['etiqueta'] ?? $key,
                            'valor' => $this->formatearValor($metrica['valor'] ?? 0, $tipo),
                            'tipo' => $tipo,
                            'descripcion' => $metrica['descripcion'] ?? ''
                        ];
                    }
                } 
                // Caso: métrica viene como ['tipo_usuario' => [...]]
                elseif (isset($metrica['tipo_metrica'])) {
                    $tipo = $metrica['tipo_metrica'];
                    
                    if ($tipo === 'distribucion' && isset($metrica['categoria'])) {
                        $estructurados['metricas'][] = [
                            'nombre' => $metrica['categoria'] ?? $key,
                            'valor' => $this->formatearValor($metrica['cantidad'] ?? $metrica['valor'] ?? 0, 'contador'),
                            'tipo' => 'distribucion',
                            'descripcion' => $metrica['descripcion'] ?? "{$metrica['categoria']}: " . ($metrica['porcentaje'] ?? 0) . "% del total"
                        ];
                    } 
                    elseif ($tipo === 'serie_temporal') {
                        $estructurados['metricas'][] = [
                            'nombre' => $metrica['fecha'] ?? $metrica['periodo'] ?? $key,
                            'valor' => $this->formatearValor($metrica['total_usuarios'] ?? $metrica['nuevos_usuarios'] ?? 0, 'contador'),
                            'tipo' => 'serie_temporal',
                            'descripcion' => isset($metrica['tasa_crecimiento']) ? "Crecimiento: {$metrica['tasa_crecimiento']}%" : ''
                        ];
                    }
                    else {
                        $estructurados['metricas'][] = [
                            'nombre' => $this->getNombreMetrica($metrica),
                            'valor' => $this->getValorMetricaFormateado($metrica),
                            'tipo' => $tipo,
                            'descripcion' => $this->getDescripcionMetrica($metrica)
                        ];
                    }
                }
            }
        }

        return $estructurados;
    }

    /**
     * 🔥 NUEVO: Formatear distribución array para mostrar en una línea
     */
    private function formatearDistribucionArray(array $distribucion): string
    {
        $items = [];
        foreach ($distribucion as $item) {
            if (isset($item['label']) && isset($item['value'])) {
                $items[] = "{$item['label']}: " . number_format($item['value']);
            } elseif (isset($item['tipo']) && isset($item['total'])) {
                $items[] = "{$item['tipo']}: " . number_format($item['total']);
            } elseif (isset($item['city']) && isset($item['total_usuarios'])) {
                $items[] = "{$item['city']}: " . number_format($item['total_usuarios']);
            }
        }
        
        if (empty($items)) {
            return json_encode($distribucion);
        }
        
        return implode(' | ', array_slice($items, 0, 5)) . (count($items) > 5 ? '...' : '');
    }

    /**
     * 🔥 Formatear distribución para mostrar en tabla (versión mejorada)
     */
    private function formatearDistribucion(array $distribucion): string
    {
        if (isset($distribucion['labels']) && isset($distribucion['datasets'][0]['data'])) {
            $items = [];
            foreach ($distribucion['labels'] as $index => $label) {
                $valor = $distribucion['datasets'][0]['data'][$index] ?? 0;
                $items[] = "{$label}: " . number_format($valor);
            }
            return implode(' | ', $items);
        }
        
        return $this->formatearDistribucionArray($distribucion);
    }
    
    /**
     * Formatear valor según tipo
     */
    private function formatearValor($valor, string $tipo): string
    {
        if ($tipo === 'porcentaje') {
            return number_format($valor, 1) . '%';
        }
        
        if (is_numeric($valor)) {
            return number_format($valor);
        }
        
        return (string) $valor;
    }
    
    
    /**
     * Obtener nombre de métrica según su estructura
     */
    private function getNombreMetrica(array $metrica): string
    {
        if (isset($metrica['categoria'])) return $metrica['categoria'];
        if (isset($metrica['tipo_usuario'])) return "Tipo: " . $metrica['tipo_usuario'];
        if (isset($metrica['ubicacion'])) return "Ubicación: " . $metrica['ubicacion'];
        if (isset($metrica['periodo'])) return "Período: " . $metrica['periodo'];
        if (isset($metrica['fecha'])) return $metrica['fecha'];
        return $metrica['nombre'] ?? 'Métrica';
    }
    
    /**
     * Obtener valor formateado de métrica
     */
    private function getValorMetricaFormateado(array $metrica): string
    {
        if (isset($metrica['total_usuarios'])) return number_format($metrica['total_usuarios']);
        if (isset($metrica['nuevos_usuarios'])) return number_format($metrica['nuevos_usuarios']);
        if (isset($metrica['activos'])) return number_format($metrica['activos']);
        if (isset($metrica['cantidad'])) return number_format($metrica['cantidad']);
        if (isset($metrica['porcentaje'])) return number_format($metrica['porcentaje'], 1) . '%';
        if (isset($metrica['valor'])) return $this->formatearValor($metrica['valor'], $metrica['tipo'] ?? 'contador');
        
        return 'N/A';
    }
    
    /**
     * Obtener descripción de métrica
     */
    private function getDescripcionMetrica(array $metrica): string
    {
        if (isset($metrica['descripcion'])) return $metrica['descripcion'];
        if (isset($metrica['porcentaje'])) return "Representa el {$metrica['porcentaje']}% del total";
        if (isset($metrica['categoria']) && isset($metrica['porcentaje'])) {
            return "{$metrica['categoria']}: {$metrica['porcentaje']}% del total";
        }
        return '';
    }
    
    /**
     * Dibujar gráfico de barras
     */
    private function dibujarGraficoBarras($image, array $data, int $width, int $height): void
    {
        $colors = [
            imagecolorallocate($image, 52, 152, 219),
            imagecolorallocate($image, 46, 204, 113),
            imagecolorallocate($image, 155, 89, 182),
            imagecolorallocate($image, 241, 196, 15),
            imagecolorallocate($image, 230, 126, 34),
            imagecolorallocate($image, 231, 76, 60),
        ];
        
        $metricas = $data['metricas'];
        $numBarras = count($metricas);
        $barWidth = min(60, ($width - 200) / max($numBarras, 1));
        $spacing = (($width - 200) / max($numBarras, 1)) - $barWidth;
        $startX = 100;
        $baseY = $height - 100;
        
        // Encontrar valor máximo
        $maxVal = 0;
        $values = [];
        $labels = [];
        
        foreach ($metricas as $key => $metrica) {
            if (is_array($metrica)) {
                $val = $this->extraerValorNumerico($metrica);
                $values[] = $val;
                $labels[] = $metrica['categoria'] ?? $metrica['nombre'] ?? $key;
                $maxVal = max($maxVal, $val);
            }
        }
        
        if ($maxVal === 0) $maxVal = 1;
        
        for ($i = 0; $i < count($values); $i++) {
            $barHeight = ($values[$i] / $maxVal) * ($height - 200) * 0.8;
            $x = $startX + ($i * ($barWidth + $spacing));
            $y = $baseY - $barHeight;
            
            imagefilledrectangle($image, $x, $y, $x + $barWidth, $baseY, 
                $colors[$i % count($colors)]);
            
            // Valor
            imagettftext($image, 10, 0, $x + $barWidth/2 - 15, $y - 10, 
                imagecolorallocate($image, 44, 62, 80), $this->obtenerFuente(), 
                number_format($values[$i]));
            
            // Etiqueta
            if (isset($labels[$i])) {
                $label = substr($labels[$i], 0, 15);
                imagettftext($image, 9, 0, $x + $barWidth/2 - 20, $baseY + 20, 
                    imagecolorallocate($image, 108, 117, 125), $this->obtenerFuente(), 
                    $label);
            }
        }
    }
    
    /**
     * Dibujar gráfico de torta (distribuciones)
     */
    private function dibujarGraficoTorta($image, array $data, int $width, int $height): void
    {
        $centerX = $width / 2;
        $centerY = $height / 2;
        $radius = 120;
        
        $colors = [
            imagecolorallocate($image, 52, 152, 219),
            imagecolorallocate($image, 46, 204, 113),
            imagecolorallocate($image, 155, 89, 182),
            imagecolorallocate($image, 241, 196, 15),
            imagecolorallocate($image, 230, 126, 34),
            imagecolorallocate($image, 231, 76, 60),
        ];
        
        $metricas = $data['metricas'];
        $values = [];
        
        foreach ($metricas as $metrica) {
            if (is_array($metrica) && isset($metrica['porcentaje'])) {
                $values[] = $metrica['porcentaje'];
            } elseif (is_array($metrica) && isset($metrica['valor']) && is_numeric($metrica['valor'])) {
                $values[] = $metrica['valor'];
            }
        }
        
        $total = array_sum($values);
        if ($total === 0) $total = 1;
        
        $startAngle = 0;
        foreach ($values as $index => $value) {
            $angle = ($value / $total) * 360;
            $endAngle = $startAngle + $angle;
            
            imagefilledarc($image, $centerX, $centerY, $radius * 2, $radius * 2, 
                $startAngle, $endAngle, $colors[$index % count($colors)], IMG_ARC_PIE);
            
            $startAngle = $endAngle;
        }
        
        imageellipse($image, $centerX, $centerY, $radius * 2, $radius * 2, 
            imagecolorallocate($image, 0, 0, 0));
    }
    
    /**
     * Dibujar gráfico de líneas
     */
    private function dibujarGraficoLineas($image, array $data, int $width, int $height): void
    {
        $colorLinea = imagecolorallocate($image, 52, 152, 219);
        
        $metricas = $data['metricas'];
        $points = [];
        $maxVal = 0;
        
        foreach ($metricas as $index => $metrica) {
            $val = $this->extraerValorNumerico($metrica);
            $points[] = $val;
            $maxVal = max($maxVal, $val);
        }
        
        if ($maxVal === 0) $maxVal = 1;
        
        $startX = 80;
        $endX = $width - 80;
        $baseY = $height - 80;
        $stepX = ($endX - $startX) / max(count($points) - 1, 1);
        
        $coords = [];
        foreach ($points as $i => $val) {
            $x = $startX + ($i * $stepX);
            $y = $baseY - (($val / $maxVal) * ($height - 160));
            $coords[] = ['x' => $x, 'y' => $y];
        }
        
        // Dibujar líneas
        for ($i = 0; $i < count($coords) - 1; $i++) {
            imageline($image, $coords[$i]['x'], $coords[$i]['y'], 
                $coords[$i + 1]['x'], $coords[$i + 1]['y'], $colorLinea);
        }
        
        // Dibujar puntos
        foreach ($coords as $coord) {
            imagefilledellipse($image, $coord['x'], $coord['y'], 6, 6, $colorLinea);
        }
    }
    
    /**
     * Extraer valor numérico de una métrica
     */
    private function extraerValorNumerico(array $metrica): float
    {
        if (isset($metrica['total_usuarios'])) return (float) $metrica['total_usuarios'];
        if (isset($metrica['nuevos_usuarios'])) return (float) $metrica['nuevos_usuarios'];
        if (isset($metrica['activos'])) return (float) $metrica['activos'];
        if (isset($metrica['cantidad'])) return (float) $metrica['cantidad'];
        if (isset($metrica['valor'])) return (float) $metrica['valor'];
        if (isset($metrica['porcentaje'])) return (float) $metrica['porcentaje'];
        return 0;
    }
    
    /**
     * Generar placeholder de gráfico
     */
    private function generarPlaceholderGrafico(array $chartData): string
    {
        $titulo = $chartData['config']['titulo'] ?? 'Gráfico del Reporte';
        $tipo = $chartData['type'] ?? 'bar';
        
        $svg = '<?xml version="1.0" encoding="UTF-8"?>
        <svg width="800" height="400" xmlns="http://www.w3.org/2000/svg">
            <rect width="800" height="400" fill="#f8f9fa"/>
            <rect x="50" y="50" width="700" height="300" fill="white" stroke="#dee2e6" stroke-width="1"/>
            <text x="400" y="40" text-anchor="middle" fill="#2c3e50" font-family="Arial" font-size="18">
                ' . htmlspecialchars($titulo) . '
            </text>
            <text x="400" y="200" text-anchor="middle" fill="#7f8c8d" font-family="Arial" font-size="12">
                Visualización generada automáticamente
            </text>
            <text x="400" y="230" text-anchor="middle" fill="#7f8c8d" font-family="Arial" font-size="11">
                Tipo: ' . htmlspecialchars(ucfirst($tipo)) . '
            </text>
        </svg>';
        
        $tempFile = tempnam(sys_get_temp_dir(), 'chart_') . '.svg';
        file_put_contents($tempFile, $svg);
        
        return $tempFile;
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
        
        // Linux/Unix
        $fonts = [
            '/usr/share/fonts/truetype/dejavu/DejaVuSans.ttf',
            '/usr/share/fonts/truetype/liberation/LiberationSans-Regular.ttf',
            '/usr/share/fonts/truetype/ubuntu/Ubuntu-R.ttf'
        ];
        
        foreach ($fonts as $font) {
            if (file_exists($font)) return $font;
        }
        
        return 'arial';
    }
    
    /**
     * Generar PDF con mPDF
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
            'tempDir' => sys_get_temp_dir()
        ]);
        
        $mpdf->SetTitle($titulo);
        $mpdf->SetAuthor('Sistema de Reportes');
        $mpdf->SetCreator('Sistema de Reportes');
        $mpdf->WriteHTML($html);
        
        return $mpdf->Output('', 'S');
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
                .error-message { color: #7f8c8d; margin-top: 20px; padding: 15px; background: #f8f9fa; border-radius: 5px; }
            </style>
        </head>
        <body>
            <div class="error-container">
                <h1>⚠️ Error al generar el reporte</h1>
                <h2>' . htmlspecialchars($titulo, ENT_QUOTES, 'UTF-8') . '</h2>
                <div class="error-message">
                    <strong>Detalle técnico:</strong><br>
                    ' . htmlspecialchars($mensajeError, ENT_QUOTES, 'UTF-8') . '
                </div>
                <p style="margin-top: 30px;">Por favor, contacte al administrador del sistema.</p>
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
     * Método de compatibilidad con versión anterior
     */
    public function generarPdf(array $reporteData, string $titulo): string
    {
        return $this->generarPdfConGraficos($reporteData, $titulo, null);
    }
}