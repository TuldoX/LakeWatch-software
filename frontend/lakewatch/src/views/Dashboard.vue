<script setup>
  import Search from '../components/dashboard/Search.vue'
</script>

<template>
  <div class="page-container">
    <h1 class="page-title">Probes</h1>
    <div class="search-wrapper">
      <Search />
    </div>
  </div>
</template>

<style scoped>
  .search-wrapper {
    display: flex;
    justify-content: center;
    width: 100%;
  }
</style>