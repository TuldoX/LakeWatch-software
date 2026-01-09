<script setup>
</script>
<template>
  <div class="page-container">
    <router-link to="/">
    <div class="back-link">
      <img src="../assets/icons/back.png" id="back-icon">
      <h1 class="page-title-back">Back to dashboard</h1>
    </div>
    </router-link>
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
    margin-bottom: 16px; 
  }
</style>