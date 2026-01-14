<script setup>
  import DeviceInfo from '@/components/device-details/DeviceInfo.vue';
  import DeviceName from '../components/device-details/DeviceName.vue';
  import Graph from '@/components/device-details/Graph.vue';

  const temperatureData = [15, 19, 16, 22, 16, 19, 22];
  const tdsData = [300, 350, 320, 400, 380, 420, 450];
  const o2Data = [6, 7, 6.5, 8, 7.5, 8.5, 9];
  const phData = [6.5, 7, 6.8, 7.2, 7, 7.5, 7.3];

</script>
<template>
  <div class="page-container">
    <router-link to="/">
    <div class="back-link">
      <img src="../assets/icons/back.png" id="back-icon">
      <h1 class="page-title-back">Back to dashboard</h1>
    </div>
    </router-link>
    <DeviceName/>
    <div class="info-wrapper">
      <DeviceInfo/>
      <Graph
      title="Temperature"
      current-value="26.0°C"
      value-type="temperature"
      :chart-data="temperatureData"
    />
    <Graph
      title="TDS"
      current-value="220 PPM"
      value-type="tds"
      :chart-data="tdsData"
    />
    <Graph
      title="pH"
      current-value="7.5 pH"
      value-type="ph"
      :chart-data="phData"
    />
    <Graph
      title="O2"
      current-value="7 mg/L"
      value-type="o2"
      :chart-data="o2Data"
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