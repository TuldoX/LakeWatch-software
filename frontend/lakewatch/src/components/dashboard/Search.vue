<script setup>
</script>
<template>
  <div class="container-search">
    <img src="../../assets/icons/search.png" id="search-icon">
    <input type="text" id="search-text" placeholder="Search for device">
  </div>
</template>

<style scoped>
    #search-icon {
        width: 1.75rem;
        height: 1.75rem;
    }

    #search-text {
    margin: 0;
    font-size: 1rem;
    font-weight: 400;
    color: var(--text-secondary);
    border: none;
    outline: none;
    background: transparent;
    box-shadow: none;
  }
</style>