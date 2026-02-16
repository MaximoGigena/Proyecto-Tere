<template>
  <BaseChart
    :type="chartType"
    :data="chartData"
    :options="chartOptions"
    :height="height"
  />
</template>

<script>
import { computed } from 'vue';
import BaseChart from './BaseChart.vue';

export default {
  name: 'ReportChart',
  components: {
    BaseChart
  },
  props: {
    reportData: {
      type: Object,
      default: () => ({})
    },
    chartType: {
      type: String,
      default: 'bar'
    },
    selectedMetrics: {
      type: Array,
      default: () => []
    },
    height: {
      type: String,
      default: '320px'
    }
  },
  setup(props) {
    const chartData = computed(() => {
      if (!props.reportData?.metricas) {
        return getEmptyData();
      }

      const metricas = props.reportData.metricas;
      const metricLabels = Object.keys(metricas);
      
      // Colores para los gráficos
      const backgroundColors = [
        'rgba(59, 130, 246, 0.7)',  // blue
        'rgba(16, 185, 129, 0.7)',  // green
        'rgba(245, 158, 11, 0.7)',  // yellow
        'rgba(239, 68, 68, 0.7)',   // red
        'rgba(139, 92, 246, 0.7)',  // purple
        'rgba(14, 165, 233, 0.7)',  // sky
        'rgba(20, 184, 166, 0.7)',  // teal
        'rgba(244, 63, 94, 0.7)',   // rose
      ];

      const borderColors = backgroundColors.map(color => 
        color.replace('0.7', '1')
      );

      if (['pie', 'doughnut', 'polarArea'].includes(props.chartType)) {
        // Para gráficos circulares
        const dataset = {
          labels: metricLabels.map(label => {
            const metrica = metricas[label];
            return metrica.etiqueta || label;
          }),
          datasets: [{
            data: metricLabels.map(label => {
              const metrica = metricas[label];
              return typeof metrica.valor === 'number' ? metrica.valor : 0;
            }),
            backgroundColor: backgroundColors.slice(0, metricLabels.length),
            borderColor: borderColors.slice(0, metricLabels.length),
            borderWidth: 2
          }]
        };
        return dataset;
      } else {
        // Para gráficos de barras/líneas
        const datasets = props.selectedMetrics.map((metricId, index) => {
          const metrica = metricas[metricId];
          if (!metrica) return null;

          return {
            label: metrica.etiqueta || metricId,
            data: Array.isArray(metrica.datos) 
              ? metrica.datos.map(item => item.total || item.valor || 0)
              : [metrica.valor || 0],
            backgroundColor: backgroundColors[index % backgroundColors.length],
            borderColor: borderColors[index % borderColors.length],
            borderWidth: 2,
            fill: props.chartType === 'line',
            tension: 0.4
          };
        }).filter(Boolean);

        return {
          labels: props.selectedMetrics.map(id => {
            const metrica = metricas[id];
            return metrica?.etiqueta || id;
          }),
          datasets
        };
      }
    });

    const chartOptions = computed(() => {
      const baseOptions = {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          legend: {
            position: 'top',
            labels: {
              padding: 20,
              usePointStyle: true,
            }
          },
          tooltip: {
            mode: 'index',
            intersect: false,
            callbacks: {
              label: function(context) {
                let label = context.dataset.label || '';
                if (label) {
                  label += ': ';
                }
                const value = context.parsed.y !== undefined ? context.parsed.y : context.parsed;
                label += new Intl.NumberFormat('es-ES').format(value);
                return label;
              }
            }
          }
        },
        scales: {}
      };

      // Configuraciones específicas por tipo de gráfico
      switch (props.chartType) {
        case 'bar':
          baseOptions.scales = {
            y: {
              beginAtZero: true,
              grid: {
                drawBorder: false,
              },
              ticks: {
                callback: function(value) {
                  return new Intl.NumberFormat('es-ES').format(value);
                }
              }
            },
            x: {
              grid: {
                display: false,
              }
            }
          };
          break;

        case 'line':
          baseOptions.scales = {
            y: {
              beginAtZero: true,
              grid: {
                drawBorder: false,
              }
            },
            x: {
              grid: {
                display: false,
              }
            }
          };
          break;

        case 'pie':
        case 'doughnut':
          baseOptions.plugins.tooltip = {
            callbacks: {
              label: function(context) {
                const label = context.label || '';
                const value = context.parsed;
                const total = context.dataset.data.reduce((a, b) => a + b, 0);
                const percentage = Math.round((value / total) * 100);
                return `${label}: ${new Intl.NumberFormat('es-ES').format(value)} (${percentage}%)`;
              }
            }
          };
          break;
      }

      return baseOptions;
    });

    const getEmptyData = () => ({
      labels: ['Sin datos'],
      datasets: [{
        label: 'No hay datos disponibles',
        data: [1],
        backgroundColor: 'rgba(200, 200, 200, 0.5)',
        borderColor: 'rgba(200, 200, 200, 1)',
      }]
    });

    return {
      chartData,
      chartOptions
    };
  }
};
</script>