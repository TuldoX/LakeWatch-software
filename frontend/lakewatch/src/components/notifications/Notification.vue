<script setup>
import { computed } from 'vue'

const props = defineProps({
  heading: String,
  message: String,
  type: String,
  device: String,
  location: String,
  time: String,
  id: String
})

const formattedTime = computed(() => {
  if (!props.time) return '—'

  const now = new Date()
  const past = new Date(props.time)
  const diffMs = now - past
  const diffMin = Math.floor(diffMs / 60000)

  if (diffMin < 1)          return 'just now'
  if (diffMin < 60)         return `${diffMin} min ago`
  
  const diffHr = Math.floor(diffMin / 60)
  if (diffHr < 24)          return `${diffHr} ${diffHr === 1 ? 'hour' : 'hours'} ago`
  
  const diffDays = Math.floor(diffHr / 24)
  if (diffDays < 30)        return `${diffDays} ${diffDays === 1 ? 'day' : 'days'} ago`
  return past.toLocaleDateString()
})
</script>

<template>
  <div class="notification-card">
    <div class="header-row">
      <img
        v-if="type === 'information'"
        class="type-icon"
        src="@/assets/icons/information.png"
        alt="info"
      />
      <img
        v-else-if="type === 'warning'"
        class="type-icon"
        src="@/assets/icons/warning.png"
        alt="warning"
      />
      <img
        v-else-if="type === 'error'"
        class="type-icon"
        src="@/assets/icons/error.png"
        alt="error"
      />

      <h3 class="heading">{{ heading }}</h3>

      <button class="close-btn">
        <img src="@/assets/icons/delete.png" alt="close" />
      </button>
    </div>

    <p class="message">{{ message }}</p>

    <div class="meta-row">
      <div class="device-pill">
        <span class="device-name">{{ device }}</span>
      </div>

      <span class="location">{{ location }}</span>
      <span class="separator-dot"></span>
      <span class="time">{{ formattedTime }}</span>
    </div>
  </div>
</template>

<style scoped>
.notification-card {
  width: 100%;
  max-width: 23.6875rem;
  background: var(--surface, #fff);
  border: 1px solid var(--border, #e0e0e0);
  border-radius: 15px;
  box-shadow: 
    0 5px 10px rgba(0, 0, 0, 0.25),
    0 15px 45px rgba(0, 0, 0, 0.10);
  padding: 0.75rem 1rem 0.9rem;
  box-sizing: border-box;
  margin-bottom: 1rem;
}

.header-row {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  margin-bottom: 0.5rem;
}

.type-icon {
  width: 1.875rem;
  height: 1.875rem;
  flex-shrink: 0;
}

.heading {
  margin: 0;
  flex: 1;
  font-size: 1rem;
  font-weight: 500;
  color: #000;
}

.close-btn {
  background: none;
  border: none;
  padding: 0;
  cursor: pointer;
  opacity: 0.7;
}

.close-btn img {
  width: 0.75rem;
  height: 0.75rem;
}

.message {
  margin: 0 0 0.75rem 0;
  color: var(--text-secondary, #666);
  font-size: 0.875rem;
  line-height: 1.35;
}

.meta-row {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  flex-wrap: wrap;
}

.device-pill {
  display: flex;
  align-items: center;
  justify-content: center;
  min-width: 4.5rem;
  height: 1.375rem;
  background: linear-gradient(to right, #1976d2, #42a5f5);
  border-radius: 10px;
  padding: 0 0.5rem;
}

.device-name {
  color: white;
  font-size: 0.94rem;
  font-weight: 600;
}

.location,
.time {
  font-size: 0.94rem;
  font-weight: 600;
  color: var(--text-secondary, #666);
}

.separator-dot {
  width: 0.313rem;
  height: 0.313rem;
  background: var(--text-secondary, #666);
  border-radius: 50%;
  display: inline-block;
  margin: 0 0.4rem;
}
</style>