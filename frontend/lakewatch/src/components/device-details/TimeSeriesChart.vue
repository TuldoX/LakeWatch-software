<script setup>
import { ref, onMounted, onBeforeUnmount, watch, computed } from 'vue';
import {
  Chart,
  LineController,
  LineElement,
  PointElement,
  LinearScale,
  CategoryScale,
  Filler,
  Tooltip
} from 'chart.js';
import ChartDataLabels from 'chartjs-plugin-datalabels';

// Register Chart.js components
Chart.register(
  LineController,
  LineElement,
  PointElement,
  LinearScale,
  CategoryScale,
  Filler,
  Tooltip,
  ChartDataLabels
);

const props = defineProps({
  valueType: {
    type: String,
    required: true,
    validator: (value) => ['temperature', 'tds', 'o2', 'ph'].includes(value)
  },
  data: {
    type: Array,
    required: true,
    default: () => []
  }
});

const chartCanvas = ref(null);
const chartContainer = ref(null);
let chartInstance = null;

// Configuration for different value types
const chartConfig = {
  temperature: {
    color: '#6366F1',
    label: 'Temperature (°C)',
    min: 0,
    max: 35,
    stepSize: 5
  },
  tds: {
    color: '#10B981',
    label: 'TDS (ppm)',
    min: 0,
    max: 1000,
    stepSize: 200
  },
  o2: {
    color: '#3B82F6',
    label: 'O2 (mg/L)',
    min: 0,
    max: 14,
    stepSize: 2
  },
  ph: {
    color: '#F59E0B',
    label: 'pH',
    min: 0,
    max: 14,
    stepSize: 2
  }
};

// Generate time labels for last 24 hours in 4-hour increments
const generateTimeLabels = () => {
  const labels = [];
  const now = new Date();
  
  // Round to nearest hour
  now.setMinutes(0, 0, 0);
  
  // Generate 7 labels (24 hours / 4 hours + 1 for current)
  for (let i = 6; i >= 0; i--) {
    const time = new Date(now.getTime() - (i * 4 * 60 * 60 * 1000));
    const hours = time.getHours().toString().padStart(2, '0');
    labels.push(`${hours}:00`);
  }
  
  return labels;
};

const timeLabels = computed(() => generateTimeLabels());
const config = computed(() => chartConfig[props.valueType]);
const activePoints = ref(new Set());

const createChart = () => {
  if (!chartCanvas.value) return;

  const ctx = chartCanvas.value.getContext('2d');
  
  // Destroy existing chart if it exists
  if (chartInstance) {
    chartInstance.destroy();
  }

  chartInstance = new Chart(ctx, {
    type: 'line',
    data: {
      labels: timeLabels.value,
      datasets: [{
        data: props.data,
        borderColor: config.value.color,
        backgroundColor: `${config.value.color}00`,
        pointBackgroundColor: config.value.color,
        pointBorderColor: config.value.color,
        pointRadius: 5,
        pointHoverRadius: 5,
        borderWidth: 2,
        tension: 0.4,
        fill: false,
        datalabels: {
          display: (context) => {
            return activePoints.value.has(context.dataIndex);
          },
          color: '#000000',
          backgroundColor: '#FFFFFF',
          borderColor: config.value.color,
          borderWidth: 2,
          borderRadius: 6,
          padding: 6,
          font: {
            size: 12,
            weight: 600,
            family: 'Inter'
          },
          align: 'top',
          offset: 8,
          formatter: (value) => {
            return value;
          }
        }
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      onClick: (event, elements) => {
        if (elements.length > 0) {
          const dataIndex = elements[0].index;
          // Clear all active points and add only the clicked one
          activePoints.value.clear();
          activePoints.value.add(dataIndex);
          chartInstance.update();
        } else {
          // Clicked on empty space, clear all labels
          if (activePoints.value.size > 0) {
            activePoints.value.clear();
            chartInstance.update();
          }
        }
      },
      plugins: {
        legend: {
          display: false
        },
        tooltip: {
          enabled: false
        }
      },
      scales: {
        x: {
          grid: {
            color: '#E0E3E7',
            drawTicks: false
          },
          border: {
            display: false
          },
          ticks: {
            color: '#5A5F66',
            font: {
              size: 12,
              family: 'Inter'
            },
            padding: 8
          }
        },
        y: {
          min: config.value.min,
          max: config.value.max,
          grid: {
            color: '#E0E3E7',
            drawTicks: false
          },
          border: {
            display: false
          },
          ticks: {
            color: '#5A5F66',
            font: {
              size: 12,
              family: 'Inter'
            },
            padding: 8,
            stepSize: config.value.stepSize
          }
        }
      },
      interaction: {
        intersect: false,
        mode: 'index'
      }
    }
  });
};

// Clean up event listener
const handleOutsideClick = (event) => {
  if (chartContainer.value && !chartContainer.value.contains(event.target)) {
    if (activePoints.value.size > 0) {
      activePoints.value.clear();
      if (chartInstance) {
        chartInstance.update();
      }
    }
  }
};

onMounted(() => {
  createChart();
  
  // Add click listener to document to detect clicks outside
  document.addEventListener('click', handleOutsideClick);
});

watch([() => props.data, () => props.valueType], () => {
  createChart();
}, { deep: true });

// Remove listener on unmount
onBeforeUnmount(() => {
  document.removeEventListener('click', handleOutsideClick);
});
</script>

<template>
  <div class="graph" ref="chartContainer">
    <canvas ref="chartCanvas"></canvas>
  </div>
</template>

<style scoped>
.graph {
  width: 100%;
  height: 200px;
  position: relative;
}
</style>