{{-- resources/views/reportes/pdf-con-graficos.blade.php --}}
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ htmlspecialchars($titulo, ENT_QUOTES, 'UTF-8') }}</title>
    <style>
        /* Reset básico */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: DejaVu Sans, Arial, sans-serif;
            font-size: 11px;
            line-height: 1.4;
            color: #333;
            margin: 0;
            padding: 15px;
        }
        
        /* Encabezado */
        .header {
            text-align: center;
            margin-bottom: 25px;
            border-bottom: 3px solid #3498db;
            padding-bottom: 15px;
        }
        
        .header h1 {
            color: #2c3e50;
            font-size: 22px;
            margin-bottom: 8px;
            font-weight: bold;
        }
        
        .header .subtitle {
            color: #7f8c8d;
            font-size: 12px;
        }
        
        /* Información de generación */
        .generation-info {
            background-color: #f8f9fa;
            border: 1px solid #e9ecef;
            border-radius: 4px;
            padding: 12px;
            margin-bottom: 20px;
            font-size: 10px;
            color: #6c757d;
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
        }
        
        .generation-info .info-item {
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        .generation-info .label {
            font-weight: bold;
            color: #495057;
        }
        
        /* Secciones */
        .section {
            margin-bottom: 25px;
            page-break-inside: avoid;
        }
        
        .section-title {
            background-color: #3498db;
            color: white;
            padding: 6px 10px;
            border-radius: 3px;
            font-size: 13px;
            font-weight: bold;
            margin-bottom: 12px;
        }
        
        /* Gráfico Principal */
        .chart-main-container {
            text-align: center;
            margin: 15px 0 25px 0;
            padding: 12px;
            border: 1px solid #dee2e6;
            border-radius: 6px;
            background-color: #fff;
            page-break-inside: avoid;
        }
        
        .chart-title-main {
            font-size: 15px;
            font-weight: bold;
            color: #2c3e50;
            margin-bottom: 8px;
            text-align: center;
        }
        
        .chart-type-info {
            font-size: 11px;
            color: #6c757d;
            margin-bottom: 10px;
            text-align: center;
            font-style: italic;
        }
        
        .chart-image-container {
            text-align: center;
            margin: 10px auto;
            max-width: 100%;
        }
        
        .chart-image {
            max-width: 100%;
            height: auto;
            display: block;
            margin: 0 auto;
            border: 1px solid #eee;
        }
        
        .chart-metrics-info {
            margin-top: 10px;
            font-size: 10px;
            color: #7f8c8d;
            text-align: center;
        }
        
        /* Métricas resumen */
        .metrics-summary {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 12px;
            margin-bottom: 18px;
        }
        
        @media (min-width: 768px) {
            .metrics-summary {
                grid-template-columns: repeat(4, 1fr);
            }
        }
        
        .metric-card {
            border: 1px solid #dee2e6;
            border-radius: 6px;
            padding: 12px;
            text-align: center;
            background-color: #fff;
            transition: all 0.2s ease;
        }
        
        .metric-card:hover {
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        
        .metric-card .label {
            font-size: 10px;
            color: #6c757d;
            margin-bottom: 6px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-weight: 600;
        }
        
        .metric-card .value {
            font-size: 18px;
            font-weight: bold;
            color: #2c3e50;
            margin-bottom: 4px;
        }
        
        .metric-card .change {
            font-size: 9px;
            font-weight: bold;
            padding: 2px 6px;
            border-radius: 10px;
            display: inline-block;
        }
        
        .change.up { 
            color: #27ae60;
            background-color: rgba(39, 174, 96, 0.1);
        }
        .change.down { 
            color: #e74c3c;
            background-color: rgba(231, 76, 60, 0.1);
        }
        .change.stable { 
            color: #f39c12;
            background-color: rgba(243, 156, 18, 0.1);
        }
        
        /* Tabla de métricas detalladas */
        .table-container {
            overflow-x: auto;
            margin-bottom: 15px;
        }
        
        .data-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 10px;
            margin-bottom: 5px;
        }
        
        .data-table th {
            background-color: #f8f9fa;
            color: #495057;
            font-weight: bold;
            text-align: left;
            padding: 8px 10px;
            border: 1px solid #dee2e6;
        }
        
        .data-table td {
            padding: 8px 10px;
            border: 1px solid #dee2e6;
        }
        
        .data-table tr:nth-child(even) {
            background-color: #f8f9fa;
        }
        
        .data-table tr:hover {
            background-color: #e9ecef;
        }
        
        /* Segmentación */
        .segmentation-info {
            background-color: #f8f9fa;
            border-left: 4px solid #3498db;
            padding: 12px;
            margin-bottom: 15px;
            border-radius: 4px;
        }
        
        .segmentation-label {
            font-weight: bold;
            color: #495057;
            margin-bottom: 5px;
        }
        
        .segmentation-value {
            color: #6c757d;
        }
        
        /* Footer */
        .footer {
            margin-top: 30px;
            padding-top: 12px;
            border-top: 2px solid #dee2e6;
            text-align: center;
            font-size: 9px;
            color: #6c757d;
        }
        
        .footer .page-info {
            margin-top: 4px;
            font-style: italic;
        }
        
        .footer .copyright {
            margin-bottom: 3px;
        }
        
        /* Utilidades */
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .text-left { text-align: left; }
        .mb-8 { margin-bottom: 8px; }
        .mb-12 { margin-bottom: 12px; }
        .mb-16 { margin-bottom: 16px; }
        .mt-8 { margin-top: 8px; }
        .mt-12 { margin-top: 12px; }
        .mt-16 { margin-top: 16px; }
        .bold { font-weight: bold; }
        .italic { font-style: italic; }
        
        /* Líneas divisorias */
        .divider {
            height: 1px;
            background-color: #dee2e6;
            margin: 15px 0;
            border: none;
        }
        
        /* Badges para tipos de gráficos */
        .chart-badge {
            display: inline-block;
            padding: 2px 8px;
            background-color: #e3f2fd;
            color: #1565c0;
            border-radius: 12px;
            font-size: 9px;
            font-weight: bold;
            margin-left: 5px;
        }
        
        /* Estado del reporte */
        .report-status {
            display: inline-block;
            padding: 2px 8px;
            background-color: #e8f5e9;
            color: #2e7d32;
            border-radius: 12px;
            font-size: 9px;
            font-weight: bold;
        }
        
        /* Logo o marca */
        .brand {
            position: absolute;
            top: 15px;
            right: 15px;
            font-size: 10px;
            color: #7f8c8d;
        }
    </style>
</head>
<body>
    <!-- Marca del sistema -->
    <div class="brand">
        {{ config('app.name', 'Sistema de Reportes') }}
    </div>
    
    <!-- Encabezado -->
    <div class="header">
        <h1>{{ htmlspecialchars($titulo, ENT_QUOTES, 'UTF-8') }}</h1>
        <div class="subtitle">Reporte Generado Automáticamente</div>
        <div class="report-status mt-8">
            ✅ Generado correctamente
        </div>
    </div>
    
    <!-- Información de generación -->
    <div class="generation-info">
        <div class="info-item">
            <span class="label">Fecha de generación:</span>
            <span>{{ $fechaGeneracion }}</span>
        </div>
        
        @if(isset($configGrafico) && isset($configGrafico['tipo']))
        <div class="info-item">
            <span class="label">Tipo de gráfico:</span>
            <span>{{ ucfirst($configGrafico['tipo']) }} <span class="chart-badge">{{ strtoupper(substr($configGrafico['tipo'], 0, 1)) }}</span></span>
        </div>
        @endif
        
        @if(isset($configGrafico) && isset($configGrafico['selectedMetrics']))
        <div class="info-item">
            <span class="label">Métricas incluidas:</span>
            <span>{{ count($configGrafico['selectedMetrics']) }}</span>
        </div>
        @endif
        
        @if(isset($datos['metricas']) && count($datos['metricas']) > 0)
        <div class="info-item">
            <span class="label">Total métricas:</span>
            <span>{{ count($datos['metricas']) }}</span>
        </div>
        @endif
    </div>
    
    <hr class="divider">
    
    <!-- Gráfico Principal -->
    @if(isset($imagenGrafico) && $imagenGrafico)
    <div class="section">
        <div class="section-title">📊 Visualización Gráfica</div>
        <div class="chart-main-container">
            <div class="chart-title-main">
                {{ htmlspecialchars($configGrafico['titulo'] ?? $titulo, ENT_QUOTES, 'UTF-8') }}
            </div>
            
            @if(isset($configGrafico) && isset($configGrafico['tipo']))
            <div class="chart-type-info">
                Gráfico de tipo: <span class="bold">{{ ucfirst($configGrafico['tipo']) }}</span>
            </div>
            @endif
            
            <div class="chart-image-container">
                <img src="{{ $imagenGrafico }}" 
                     alt="Gráfico: {{ htmlspecialchars($configGrafico['titulo'] ?? $titulo, ENT_QUOTES, 'UTF-8') }}" 
                     class="chart-image">
            </div>
            
            @if(isset($configGrafico) && isset($configGrafico['selectedMetrics']))
            <div class="chart-metrics-info">
                Métricas representadas: 
                @foreach($configGrafico['selectedMetrics'] as $index => $metric)
                    @if($index < 3)
                    <span class="italic">{{ $metric }}</span>@if($index < count($configGrafico['selectedMetrics']) - 1 && $index < 2), @endif
                    @endif
                @endforeach
                @if(count($configGrafico['selectedMetrics']) > 3)
                <span> y {{ count($configGrafico['selectedMetrics']) - 3 }} más...</span>
                @endif
            </div>
            @endif
        </div>
    </div>
    @else
    <div class="section">
        <div class="section-title">📊 Visualización Gráfica</div>
        <div class="chart-main-container">
            <div class="chart-title-main">Información Gráfica</div>
            <div style="color: #95a5a6; font-style: italic; padding: 20px; text-align: center;">
                No hay gráfico disponible para este reporte.
                <div class="mt-8">Se muestran únicamente los datos tabulares.</div>
            </div>
        </div>
    </div>
    @endif
    
    <hr class="divider">
    
    <!-- Resumen de Métricas -->
    @if(isset($datos['summary']) && count($datos['summary']) > 0)
    <div class="section">
        <div class="section-title">📈 Resumen de Métricas</div>
        <div class="metrics-summary">
            @foreach($datos['summary'] as $index => $item)
            <div class="metric-card">
                <div class="label">{{ htmlspecialchars($item['label'] ?? 'Métrica ' . ($index + 1), ENT_QUOTES, 'UTF-8') }}</div>
                <div class="value">{{ htmlspecialchars($item['value'] ?? 'N/A', ENT_QUOTES, 'UTF-8') }}</div>
                @if(isset($item['change']) && isset($item['change']['direction']))
                <div class="change {{ $item['change']['direction'] }}">
                    @if($item['change']['direction'] == 'up')
                    ▲ Incremento: 
                    @elseif($item['change']['direction'] == 'down')
                    ▼ Decremento: 
                    @else
                    ● Estable: 
                    @endif
                    {{ htmlspecialchars($item['change']['value'] ?? '', ENT_QUOTES, 'UTF-8') }}
                </div>
                @endif
            </div>
            @endforeach
        </div>
    </div>
    @endif
    
    <!-- Métricas Detalladas -->
    @if(isset($datos['metricas']) && count($datos['metricas']) > 0)
    <div class="section">
        <div class="section-title">📋 Métricas Detalladas</div>
        <div class="table-container">
            <table class="data-table">
                <thead>
                    <tr>
                        <th width="5%">#</th>
                        <th width="35%">Nombre de la Métrica</th>
                        <th width="20%">Valor</th>
                        <th width="15%">Tipo</th>
                        @if(isset($datos['metricas'][0]['descripcion']))
                        <th width="25%">Descripción</th>
                        @endif
                    </tr>
                </thead>
                <tbody>
                    @foreach($datos['metricas'] as $index => $metrica)
                    <tr>
                        <td class="text-center">{{ $index + 1 }}</td>
                        <td>{{ htmlspecialchars($metrica['nombre'] ?? 'Métrica sin nombre', ENT_QUOTES, 'UTF-8') }}</td>
                        <td class="bold">{{ htmlspecialchars($metrica['valor'] ?? 'N/A', ENT_QUOTES, 'UTF-8') }}</td>
                        <td>
                            <span class="chart-badge" style="background-color: #e3f2fd; color: #1565c0;">
                                {{ htmlspecialchars($metrica['tipo'] ?? 'General', ENT_QUOTES, 'UTF-8') }}
                            </span>
                        </td>
                        @if(isset($metrica['descripcion']))
                        <td class="italic">{{ htmlspecialchars($metrica['descripcion'], ENT_QUOTES, 'UTF-8') }}</td>
                        @endif
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="text-right mt-8" style="font-size: 9px; color: #6c757d;">
            Total: {{ count($datos['metricas']) }} métricas listadas
        </div>
    </div>
    @endif
    
    <!-- Segmentación (si existe) -->
    @if(isset($datos['segmentacion']) && $datos['segmentacion'])
    <div class="section">
        <div class="section-title">🔍 Segmentación</div>
        <div class="segmentation-info">
            <div class="mb-8">
                <div class="segmentation-label">Tipo de segmentación:</div>
                <div class="segmentation-value">{{ htmlspecialchars($datos['segmentacion']['tipo'] ?? 'No especificado', ENT_QUOTES, 'UTF-8') }}</div>
            </div>
            <div>
                <div class="segmentation-label">Etiqueta de segmentación:</div>
                <div class="segmentation-value">{{ htmlspecialchars($datos['segmentacion']['label'] ?? 'Sin etiqueta', ENT_QUOTES, 'UTF-8') }}</div>
            </div>
        </div>
    </div>
    @endif
    
    <!-- Notas adicionales -->
    @if(isset($datos['notas']) || isset($configGrafico['notas']))
    <div class="section">
        <div class="section-title">📝 Notas Adicionales</div>
        <div style="background-color: #fff9e6; border-left: 4px solid #ffd54f; padding: 12px; border-radius: 4px; font-size: 10px;">
            @if(isset($datos['notas']))
                <p>{{ htmlspecialchars($datos['notas'], ENT_QUOTES, 'UTF-8') }}</p>
            @endif
            @if(isset($configGrafico['notas']))
                <p class="mt-8">{{ htmlspecialchars($configGrafico['notas'], ENT_QUOTES, 'UTF-8') }}</p>
            @endif
        </div>
    </div>
    @endif
    
    <!-- Pie de página -->
    <div class="footer">
        <div class="copyright">© {{ date('Y') }} {{ config('app.name', 'Sistema de Reportes') }}. Todos los derechos reservados.</div>
        <div>ID del reporte: {{ md5($titulo . $fechaGeneracion) }}</div>
        <div class="page-info">Página <span class="page-number"></span> de <span class="page-count"></span></div>
        <div class="mt-8">Documento generado automáticamente - No requiere firma</div>
        <div style="font-size: 8px; color: #adb5bd; margin-top: 4px;">
            Generado con tecnología PHP Laravel + mPDF
        </div>
    </div>
    
    <!-- Script para numeración de páginas -->
    <script type="text/javascript">
        // Script compatible con mPDF para numeración de páginas
        document.addEventListener('DOMContentLoaded', function() {
            // mPDF pasa variables por URL
            var query = window.location.search.substring(1);
            var vars = query.split('&');
            var pageInfo = { page: '1', topage: '1' };
            
            for (var i = 0; i < vars.length; i++) {
                var pair = vars[i].split('=');
                if (pair[0] === 'page') pageInfo.page = pair[1];
                if (pair[0] === 'topage') pageInfo.topage = pair[1];
            }
            
            // Actualizar números de página
            var pageElements = document.getElementsByClassName('page-number');
            var countElements = document.getElementsByClassName('page-count');
            
            for (var i = 0; i < pageElements.length; i++) {
                pageElements[i].textContent = pageInfo.page;
            }
            
            for (var i = 0; i < countElements.length; i++) {
                countElements[i].textContent = pageInfo.topage;
            }
        });
    </script>
</body>
</html>