<script setup>
import { ref, onMounted, onBeforeUnmount, watch } from 'vue'
import {
  Chart,
  LineController,
  LineElement,
  PointElement,
  LinearScale,
  TimeScale,
  Filler,
  Tooltip
} from 'chart.js'
import 'chartjs-adapter-date-fns'          // ← required!
import ChartDataLabels from 'chartjs-plugin-datalabels'

Chart.register(
  LineController,
  LineElement,
  PointElement,
  LinearScale,
  TimeScale,
  Filler,
  Tooltip,
  ChartDataLabels
)

const props = defineProps({
  valueType: {
    type: String,
    required: true,
    validator: (v) => ['temperature', 'tds', 'o2', 'ph'].includes(v)
  },
  data: {
    type: Array,
    required: true,
    default: () => []
  },
  time: {                     // ← new prop – array of timestamp strings
    type: Array,
    default: () => []
  }
})

const chartCanvas = ref(null)
const chartContainer = ref(null)
let chartInstance = null

const chartConfig = {
  temperature: { color: '#6366F1', label: 'Temperature (°C)', min: 0,  max: 35,  stepSize: 5  },
  tds:        { color: '#10B981', label: 'TDS (ppm)',         min: 0,  max: 1000, stepSize: 200},
  o2:         { color: '#3B82F6', label: 'O2 (mg/L)',         min: 0,  max: 14,   stepSize: 2  },
  ph:         { color: '#F59E0B', label: 'pH',                min: 0,  max: 14,   stepSize: 2  }
}

const activePoints = ref(new Set())

const createChart = () => {
  if (!chartCanvas.value) return
  if (props.data.length === 0 || props.time.length === 0) return

  const ctx = chartCanvas.value.getContext('2d')

  if (chartInstance) {
    chartInstance.destroy()
  }

  const config = chartConfig[props.valueType]

  // Prepare real data points with Date objects
  const chartPoints = props.data.map((value, index) => ({
    x: new Date(props.time[index]),
    y: value
  }))

  chartInstance = new Chart(ctx, {
    type: 'line',
    data: {
      datasets: [{
        data: chartPoints,
        borderColor: config.color,
        backgroundColor: `${config.color}00`,
        pointBackgroundColor: config.color,
        pointBorderColor: config.color,
        pointRadius: 5,
        pointHoverRadius: 5,
        borderWidth: 2,
        tension: 0.4,
        fill: false,
        datalabels: {
          display: (context) => activePoints.value.has(context.dataIndex),
          color: '#000000',
          backgroundColor: '#FFFFFF',
          borderColor: config.color,
          borderWidth: 2,
          borderRadius: 6,
          padding: 6,
          font: { size: 12, weight: 600, family: 'Inter' },
          align: 'top',
          offset: 8,
          formatter: (value) => value.y
        }
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,

      onClick: (event, elements) => {
        if (elements.length > 0) {
          const idx = elements[0].index
          activePoints.value.clear()
          activePoints.value.add(idx)
          chartInstance.update()
        } else {
          if (activePoints.value.size > 0) {
            activePoints.value.clear()
            chartInstance.update()
          }
        }
      },

      plugins: {
        legend: { display: false },
        tooltip: { enabled: false },
      },

      scales: {
        x: {
          type: 'time',
          time: {
            unit: 'hour',
            displayFormats: {
              hour: 'HH:mm'
            },
            tooltipFormat: 'yyyy-MM-dd HH:mm:ss'
          },
          min: new Date(Date.now() - 25 * 60 * 60 * 1000), // slightly more than 24h
          max: new Date(),
          ticks: {
            color: '#5A5F66',
            font: { size: 12, family: 'Inter' },
            maxTicksLimit: 13,
            source: 'auto'
          },
          grid: { color: '#E0E3E7', drawTicks: false },
          border: { display: false }
        },
        y: {
          min: config.min,
          max: config.max,
          ticks: {
            stepSize: config.stepSize,
            color: '#5A5F66',
            font: { size: 12, family: 'Inter' },
            padding: 8
          },
          grid: { color: '#E0E3E7', drawTicks: false },
          border: { display: false }
        }
      },

      interaction: {
        intersect: false,
        mode: 'index'
      }
    }
  })
}

const handleOutsideClick = (event) => {
  if (chartContainer.value && !chartContainer.value.contains(event.target)) {
    if (activePoints.value.size > 0) {
      activePoints.value.clear()
      if (chartInstance) chartInstance.update()
    }
  }
}

onMounted(() => {
  createChart()
  document.addEventListener('click', handleOutsideClick)
})

watch([() => props.data, () => props.time, () => props.valueType], () => {
  createChart()
}, { deep: true })

onBeforeUnmount(() => {
  document.removeEventListener('click', handleOutsideClick)
  if (chartInstance) chartInstance.destroy()
})
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