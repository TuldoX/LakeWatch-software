<script setup>
  import Search from '../components/dashboard/Search.vue'
  import Device from  '../components/dashboard/Device.vue'
  import Navigation from '@/components/Navigation.vue'
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