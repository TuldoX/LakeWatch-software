<script setup>
  import DeviceInfo from '@/components/device-details/DeviceInfo.vue';
  import DeviceName from '../components/device-details/DeviceName.vue';
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