<script setup>
import DeviceInfo from '@/components/device-details/DeviceInfo.vue'
import DeviceName from '@/components/device-details/DeviceName.vue'
import Graph from '@/components/device-details/Graph.vue'
import { onMounted, ref, computed } from 'vue'
import { useRoute } from 'vue-router'
import { getData } from '@/services/apiService'

const route = useRoute()
const probeId = route.query.probe_id
const deviceName = route.query.device_name
const location = route.query.location

const temperatureData = ref([])
const tdsData = ref([])
const o2Data = ref([])
const phData = ref([])
const time = ref([])                    // ← we keep this as array of strings
const latestTimestamp = ref(null)

onMounted(async () => {
  try {
    const response = await getData(probeId)

    const sorted = [...response].sort((a, b) => 
      new Date(a.time_recieved) - new Date(b.time_recieved)
    )

    temperatureData.value = sorted.map(item => Number(item.temp))
    tdsData.value        = sorted.map(item => Number(item.tds))
    o2Data.value         = sorted.map(item => Number(item.oxygen))
    phData.value         = sorted.map(item => Number(item.ph))
    time.value           = sorted.map(item => item.time_recieved)   // keep original string

    if (sorted.length > 0) {
      latestTimestamp.value = sorted[sorted.length - 1].time_recieved
    }

  } catch (error) {
    console.error('Failed to fetch data:', error)
  }
})

const timeAgo = computed(() => {
  if (!latestTimestamp.value) return '—'

  const now = new Date()
  const past = new Date(latestTimestamp.value)
  const diffMs = now - past
  const diffMin = Math.floor(diffMs / 60000)

  if (diffMin < 1)          return 'just now'
  if (diffMin < 60)         return `${diffMin} min ago`
  
  const diffHr = Math.floor(diffMin / 60)
  if (diffHr < 24)          return `${diffHr} ${diffHr === 1 ? 'hour' : 'hours'} ago`
  
  const diffDays = Math.floor(diffHr / 24)
  if (diffDays < 30)        return `${diffDays} ${diffDays === 1 ? 'day' : 'days'} ago`
  return past.toLocaleDateString()
})

const lst_temp = computed(() => {
  const data = temperatureData.value
  return data.length ? `${data[data.length - 1]}°C` : null
})

const lst_tds = computed(() => {
  const data = tdsData.value
  return data.length ? `${data[data.length - 1]} PPM` : null
})

const lst_oxygen = computed(() => {
  const data = o2Data.value
  return data.length ? `${data[data.length - 1]} mg/L` : null
})

const lst_ph = computed(() => {
  const data = phData.value
  return data.length ? `${data[data.length - 1]} pH` : null
})
</script>

<template>
  <div class="page-container">
    <router-link to="/">
      <div class="back-link">
        <img src="../assets/icons/back.png" id="back-icon">
        <h1 class="page-title-back">Back to dashboard</h1>
      </div>
    </router-link>

    <DeviceName 
      :deviceName="deviceName" 
      :lastUpdated="timeAgo"  
    />

    <div class="info-wrapper">
      <DeviceInfo
        :location="location"
        :lastUpdated="timeAgo"
      />

      <Graph
        title="Oxygen"
        :current-value="lst_oxygen"
        value-type="o2"
        :chart-data="o2Data"
        :time-data="time"
      />

      <Graph
        title="Temperature"
        :current-value="lst_temp"
        value-type="temperature"
        :chart-data="temperatureData"
        :time-data="time"
      />

      <Graph
        title="pH"
        :current-value="lst_ph"
        value-type="ph"
        :chart-data="phData"
        :time-data="time"
      />

      <Graph
        title="TDS"
        :current-value="lst_tds"
        value-type="tds"
        :chart-data="tdsData"
        :time-data="time"
      />
    </div>
  </div>
</template>
<style scoped>
  .page-title-back {
    font-weight: 500;
    color: var(--text-secondary);
    font-size: clamp(1.25rem, 2vw, 1.5rem);
  }

  #back-icon {  
    width: 1.875rem;
    height: 1.875rem;
  }

  a {
    text-decoration: none;
  }

  .back-link {
    display: flex;
    flex-direction: row;
    align-items: center;
    gap: 0.5rem;
    margin-top: 0.8rem;
    margin-bottom: 0.5rem; 
  }

  .info-wrapper {
    display: flex;
    flex-direction: column;
    justify-content: center;
    width: 100%;
    align-items: center;
  }
</style>