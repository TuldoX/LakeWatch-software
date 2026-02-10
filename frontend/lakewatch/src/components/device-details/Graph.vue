<script setup>
import { ref } from 'vue'
import TimeSeriesChart from './TimeSeriesChart.vue'

const props = defineProps({
  title: {
    type: String,
    default: 'Unknown'
  },
  currentValue: {
    type: String,
    default: '0'
  },
  valueType: {
    type: String,
    required: true,
    validator: value => ['temperature', 'tds', 'o2', 'ph'].includes(value)
  },
  chartData: {
    type: Array,
    default: () => []
  },
  timeData: {           // ← new prop
    type: Array,
    default: () => []
  }
})
</script>

<template>
  <div class="container-surface">
    <div class="h2-info-container">
      <h2 id="value-name" class="h2-info">{{ title }}</h2>
    </div>
    <div class="h2-info-container">
      <p class="secondary-bold">{{ currentValue || '—' }}</p>
    </div>
    <TimeSeriesChart 
      :value-type="valueType"
      :data="chartData"
      :time="timeData"
    />
  </div>
</template>

<style scoped>
.container-surface {
  padding: 0.75rem 1rem 0.75rem 1rem;
}
</style>