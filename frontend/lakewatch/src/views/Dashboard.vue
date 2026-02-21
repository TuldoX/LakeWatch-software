<script setup>
import { onMounted, ref } from 'vue'
import Search from '../components/dashboard/Search.vue'
import Device from '../components/dashboard/Device.vue'
import Navigation from '@/components/Navigation.vue'
import { getMe, getProbes } from '@/services/apiService.js'

const user = ref(null)
const probes = ref([])

onMounted(async () => {
  const cached = localStorage.getItem('user')
  if (cached) {
    try {
      const parsed = JSON.parse(cached)
      user.value = parsed
    } catch (err) {
      localStorage.removeItem('user')
    }
  }

  if (!user.value) {
    try {
      const authenticatedUser = await getMe()
      if (authenticatedUser) {
        user.value = authenticatedUser
      } else {
        return
      }
    } catch (err) {
      console.error('Critical error during initial auth fetch', err)
      return
    }
  }

  if (user.value?.user?.sub) {
    try {
      const userId = user.value.user.sub
      const probesData = await getProbes(userId)
      if (probesData) {
        probes.value = probesData
      }
    } catch (err) {
      console.error('Failed to fetch probes:', err)
    }
  }
})
</script>

<template>
  <div class="page-container">
    <div class="page-header">
      <h1 class="page-title">Probes</h1>
      <div class="search-wrapper">
        <Search />
      </div>
    </div>

    <div class="device-wrapper">
      <Device
        v-for="probe in probes"
        :key="probe.probe_id"
        :probeId="probe.probe_id"
        :battery="probe.battery_life"
        :deviceName="probe.probe_name"
        :location="probe.location_name"
        :time="probe.lst_data"
      />
    </div>

    <Navigation/>
  </div>
</template>
<style scoped>
  .search-wrapper {
    display: flex;
    justify-content: center;
    width: 100%;
  }

  .device-wrapper {
    display: flex;
    flex-direction: column;
    justify-content: center;
    width: 100%;
    margin-top: 0.8rem;
    align-items: center;
  }
</style>