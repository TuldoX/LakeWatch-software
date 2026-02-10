<script setup>
import { computed } from "vue"

const props = defineProps({
  deviceName: String,
  lastUpdated: String
});

const isOnline = computed(() => {
  if (!props.lastUpdated || props.lastUpdated === '—') {
    return false;
  }

  const text = props.lastUpdated.toLowerCase();

  // Consider online if: just now, X min ago (any minutes), or maybe up to 59 min
  if (text.includes('just now') || text.includes('min ago')) {
    return true;
  }

  // Optional: allow up to 65 min ago still "online" (grace period)
  // if (text.match(/(\d+)\s*min\s*ago/) && parseInt(RegExp.$1, 10) <= 65) {
  //   return true;
  // }

  return false; // hours, days, dates → offline
});

const statusText = computed(() => isOnline.value ? 'online' : 'offline');
const statusClass = computed(() => isOnline.value ? 'online' : 'offline');
</script>

<template>
    <div id="device-name" class="container-surface-no-style">
      <img src="../../assets/icons/drop.png" id="drop">
      <div class="helper">
        <h2 id="device-name-text">{{ deviceName }}</h2>
        <div id="status" :class="statusClass">
          <p id="status-text">{{ statusText }}</p>
        </div>
      </div>
    </div>
    <div class="divider"></div>
</template>
<style scoped>
    #drop{
    width: 4.75rem;
    height: 4.75rem;
  }

  #device-name-text {
    margin: 0;
    padding: 0;
    font-size: clamp(1.25rem, 1.25vw, 1.5rem);
    font-weight: 500;
  }

  .online {
    display: flex;
    flex-direction: column;
    justify-content: center;
    align-items: center;
    width: 4.875rem;
    height: 1.438rem;
    border-radius: 10px;
    background: linear-gradient(to right, #1976D2 0%, #42A5F5 100%);
  }

  .offline {
    display: flex;
    flex-direction: column;
    justify-content: center;
    align-items: center;
    width: 4.875rem;
    height: 1.438rem;
    border-radius: 10px;
    background: var(--text-secondary);
  }

  #status-text{
    color: white;
  }

  .helper {
    display: flex;
    flex-direction: column;
    justify-content: center;
    gap: 0.3rem;
  }

  .divider {
    width: 100vw;
    height: 1px;
    background-color: var(--border);
    margin-left: -1rem;
    margin-top: 0.4rem;
    margin-bottom: 1rem;
  }
</style>