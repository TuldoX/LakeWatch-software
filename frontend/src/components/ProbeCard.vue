<template>
  <div class="probe-card" @click="navigateToDetails">
    <div class="card-header">
      <div class="probe-name">
        <div class="icon-wrapper">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"/>
          </svg>
        </div>
        <h3>{{ probe.name }}</h3>
      </div>
      <div class="status" :class="statusClass">
        <span class="status-dot"></span>
      </div>
    </div>
    
    <div class="card-body">
      <div class="info-item">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <path d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
          <path d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
        </svg>
        <span>{{ probe.location }}</span>
      </div>
      
      <div class="card-footer">
        <div class="battery">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <rect x="1" y="6" width="18" height="12" rx="2" ry="2"/>
            <path d="M23 13v-2"/>
          </svg>
          <span>{{ probe.btr_life }}%</span>
          <div class="battery-bar">
            <div class="battery-fill" :style="{ width: probe.btr_life + '%' }" :class="batteryClass"></div>
          </div>
        </div>
        
        <div class="time">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <circle cx="12" cy="12" r="10"/>
            <path d="M12 6v6l4 2"/>
          </svg>
          <span>{{ formattedTime }}</span>
        </div>
      </div>
    </div>
    
    <div class="card-action">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <path d="M9 5l7 7-7 7"/>
      </svg>
    </div>
  </div>
</template>

<script>
export default {
  name: 'ProbeCard',
  props: {
    probe: {
      type: Object,
      required: true
    }
  },
  computed: {
    statusClass() {
      return this.isOnline ? 'online' : 'offline'
    },
    isOnline() {
      const lastData = new Date(this.probe.lst_data)
      const now = new Date()
      const diffHours = (now - lastData) / (1000 * 60 * 60)
      return diffHours < 24
    },
    batteryClass() {
      const battery = parseInt(this.probe.btr_life)
      if (battery > 50) return 'high'
      if (battery > 20) return 'medium'
      return 'low'
    },
    formattedTime() {
      const date = new Date(this.probe.lst_data)
      const now = new Date()
      const diff = now - date
      const hours = Math.floor(diff / (1000 * 60 * 60))
      const minutes = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60))
      
      if (hours === 0) return `${minutes}m ago`
      if (hours < 24) return `${hours}h ago`
      return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric' })
    }
  },
  methods: {
    navigateToDetails() {
      this.$router.push({ name: 'ProbeDetails', params: { id: this.probe.probe_id } })
    }
  }
}
</script>

<style scoped>
.probe-card {
  background: var(--bg-card);
  border-radius: 16px;
  padding: 20px;
  cursor: pointer;
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  box-shadow: var(--shadow);
  position: relative;
  overflow: hidden;
}

.probe-card::before {
  content: '';
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  height: 3px;
  background: linear-gradient(90deg, var(--accent-blue), var(--accent-purple));
  opacity: 0;
  transition: opacity 0.3s ease;
}

.probe-card:hover {
  transform: translateY(-4px);
  box-shadow: var(--shadow-lg);
}

.probe-card:hover::before {
  opacity: 1;
}

.card-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 16px;
}

.probe-name {
  display: flex;
  align-items: center;
  gap: 12px;
}

.icon-wrapper {
  width: 40px;
  height: 40px;
  background: linear-gradient(135deg, var(--accent-blue), var(--accent-purple));
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

.icon-wrapper svg {
  width: 20px;
  height: 20px;
  color: white;
}

.probe-name h3 {
  font-size: 18px;
  font-weight: 600;
  color: var(--text-primary);
}

.status {
  display: flex;
  align-items: center;
  gap: 8px;
}

.status-dot {
  width: 8px;
  height: 8px;
  border-radius: 50%;
  background: var(--accent-green);
  box-shadow: 0 0 12px var(--accent-green);
  animation: pulse 2s ease-in-out infinite;
}

.status.offline .status-dot {
  background: var(--accent-red);
  box-shadow: 0 0 12px var(--accent-red);
  animation: none;
}

@keyframes pulse {
  0%, 100% { opacity: 1; }
  50% { opacity: 0.5; }
}

.card-body {
  display: flex;
  flex-direction: column;
  gap: 16px;
}

.info-item {
  display: flex;
  align-items: center;
  gap: 10px;
  color: var(--text-secondary);
  font-size: 14px;
}

.info-item svg {
  width: 18px;
  height: 18px;
  flex-shrink: 0;
  color: var(--accent-blue);
}

.card-footer {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 16px;
  padding-top: 12px;
  border-top: 1px solid rgba(148, 163, 184, 0.1);
}

.battery,
.time {
  display: flex;
  align-items: center;
  gap: 8px;
  font-size: 13px;
  color: var(--text-secondary);
}

.battery svg,
.time svg {
  width: 16px;
  height: 16px;
  flex-shrink: 0;
}

.battery-bar {
  flex: 1;
  height: 4px;
  background: rgba(148, 163, 184, 0.2);
  border-radius: 2px;
  overflow: hidden;
}

.battery-fill {
  height: 100%;
  border-radius: 2px;
  transition: width 0.3s ease;
}

.battery-fill.high {
  background: var(--accent-green);
}

.battery-fill.medium {
  background: var(--accent-orange);
}

.battery-fill.low {
  background: var(--accent-red);
}

.card-action {
  position: absolute;
  right: 20px;
  top: 50%;
  transform: translateY(-50%);
  opacity: 0;
  transition: all 0.3s ease;
}

.card-action svg {
  width: 20px;
  height: 20px;
  color: var(--accent-blue);
}

.probe-card:hover .card-action {
  opacity: 1;
  right: 16px;
}

@media (max-width: 640px) {
  .probe-card {
    padding: 16px;
  }
  
  .card-footer {
    grid-template-columns: 1fr;
    gap: 12px;
  }
  
  .probe-name h3 {
    font-size: 16px;
  }
}
</style>