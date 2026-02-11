<script setup>
function handleLogout() {
  if (window.confirm("Are you sure you want to log out?")) {
    localStorage.clear();
    sessionStorage.clear();
    window.location.href = '/bff/auth/logout';
  }
}
</script>

<template>
  <div id="logout-container" @click="handleLogout">
    <span class="logout-text">Logout</span>
    <img 
      id="logout-icon" 
      src="../../assets/icons/logout.png" 
      alt="Logout icon" 
    />
  </div>
</template>

<style scoped>
#logout-container {
  height: 3.5rem;
  display: flex;
  align-items: center;
  cursor: pointer;
  margin-left: 1rem;
}

.logout-text {
  color: var(--text-secondary);
  font-weight: 500;
  font-size: 1.5rem;
  margin-right: 1.2rem;
}

#logout-icon {
  height: 1.2rem;
  width: 1.2rem;
}
</style>