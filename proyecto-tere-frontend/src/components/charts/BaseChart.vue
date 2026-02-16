<template>
  <div class="chart-wrapper">
    <canvas ref="chartCanvas"></canvas>
  </div>
</template>

<script>
import { ref, onMounted, onBeforeUnmount, watch } from 'vue';
import Chart from 'chart.js/auto';

export default {
  name: 'BaseChart',
  props: {
    type: {
      type: String,
      default: 'bar',
      validator: (value) => ['bar', 'line', 'pie', 'doughnut', 'radar', 'polarArea', 'bubble', 'scatter'].includes(value)
    },
    data: {
      type: Object,
      default: () => ({
        labels: [],
        datasets: []
      })
    },
    options: {
      type: Object,
      default: () => ({})
    },
    height: {
      type: String,
      default: '400px'
    }
  },
  setup(props) {
    const chartCanvas = ref(null);
    let chartInstance = null;

    const defaultOptions = {
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        legend: {
          position: 'top',
        },
        tooltip: {
          mode: 'index',
          intersect: false,
        }
      },
      scales: props.type === 'bar' || props.type === 'line' ? {
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
      } : {}
    };

    const initChart = () => {
      if (!chartCanvas.value) return;

      if (chartInstance) {
        chartInstance.destroy();
      }

      const ctx = chartCanvas.value.getContext('2d');
      chartInstance = new Chart(ctx, {
        type: props.type,
        data: props.data,
        options: { ...defaultOptions, ...props.options }
      });
    };

    onMounted(() => {
      initChart();
    });

    onBeforeUnmount(() => {
      if (chartInstance) {
        chartInstance.destroy();
      }
    });

    watch(() => [props.type, props.data, props.options], () => {
      initChart();
    }, { deep: true });

    return {
      chartCanvas,
      chartInstance
    };
  }
};
</script>

<style scoped>
.chart-wrapper {
  position: relative;
  height: v-bind(height);
  width: 100%;
}
</style>