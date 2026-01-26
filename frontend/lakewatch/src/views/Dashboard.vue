<script setup>
  import { onMounted, ref } from 'vue'
  import Search from '../components/dashboard/Search.vue'
  import Device from '../components/dashboard/Device.vue'
  import Navigation from '@/components/Navigation.vue'
  import { getMe, getProbes } from '@/services/apiService.js'

  const user = ref(null);
  const probes = ref([]);

  onMounted(async () => {
    const authenticatedUser = await getMe();
    if (!authenticatedUser) return;
  
    user.value = authenticatedUser
    
    try {
      const userId = user.value.id
      console.log('Fetching probes for user:', userId);
      
      const probesData = await getProbes(userId);
      console.log('Probes data:', probesData);
      
      if (probesData) {
        probes.value = probesData;
        localStorage.setItem('probes', JSON.stringify(probesData));
        console.log('Probes saved to localStorage');
      }
    } catch (error) {
      console.error('Failed to fetch probes:', error);
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
      <Device/>
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