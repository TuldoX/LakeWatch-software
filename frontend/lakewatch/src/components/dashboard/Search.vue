<script setup>
</script>
<template>
  <div id="search-container">
    <img src="../../assets/icons/search.png" id="search-icon">
    <p id="search-text">Search for device</p>
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
    }

    #search-container {
        border: 1px solid var(--border);
        background-color: var(--surface);
        width: 100%;
        max-width: 23.6875rem;
        height: 2.625rem;
        border-radius: 10px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        display: flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0 0.5rem;
        box-sizing: border-box;
    }
</style>