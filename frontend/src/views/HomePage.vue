<template>
  <div class="home-page">
    <header class="header">
      <div class="header-content">
        <h1>
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"/>
          </svg>
          Probes
        </h1>
        <p>{{ probes.length }} active</p>
      </div>
    </header>
    
    <main class="main-content">
      <div class="loading" v-if="loading">
        <div class="spinner"></div>
      </div>
      
      <div class="error" v-else-if="error">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <circle cx="12" cy="12" r="10"/>
          <path d="M12 8v4m0 4h.01"/>
        </svg>
        <p>{{ error }}</p>
        <button @click="fetchProbes">Retry</button>
      </div>
      
      <div class="probes-grid" v-else>
        <ProbeCard 
          v-for="probe in probes" 
          :key="probe.probe_id" 
          :probe="probe" 
        />
        
        <div v-if="probes.length === 0" class="empty-state">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/>
            <circle cx="12" cy="7" r="4"/>
          </svg>
          <p>No probes found</p>
        </div>
      </div>
    </main>
  </div>
</template>

<script>
import ProbeCard from '../components/ProbeCard.vue'
import { api } from '../services/api'

export default {
  name: 'HomePage',
  components: {
    ProbeCard
  },
  data() {
    return {
      probes: [],
      loading: true,
      error: null,
      userId: 1
    }
  },
  mounted() {
    this.fetchProbes()
  },
  methods: {
    async fetchProbes() {
      this.loading = true
      this.error = null
      
      try {
        const data = await api.getUserProbes(this.userId)
        this.probes = data.probes || []
      } catch (err) {
        this.error = 'Failed to load probes'
        console.error('Error:', err)
      } finally {
        this.loading = false
      }
    }
  }
}
</script>

<style scoped>
.home-page {
  min-height: 100vh;
  padding: 20px;
}

.header {
  margin-bottom: 32px;
}

.header-content {
  max-width: 1200px;
  margin: 0 auto;
}

.header h1 {
  font-size: 32px;
  font-weight: 700;
  color: var(--text-primary);
  margin-bottom: 8px;
  display: flex;
  align-items: center;
  gap: 12px;
}

.header h1 svg {
  width: 32px;
  height: 32px;
  color: var(--accent-blue);
}

.header p {
  color: var(--text-secondary);
  font-size: 14px;
  margin-left: 44px;
}

.main-content {
  max-width: 1200px;
  margin: 0 auto;
}

.loading,
.error,
.empty-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 60px 20px;
  text-align: center;
}

.spinner {
  width: 48px;
  height: 48px;
  border: 4px solid rgba(59, 130, 246, 0.2);
  border-top-color: var(--accent-blue);
  border-radius: 50%;
  animation: spin 1s linear infinite;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}

.error svg,
.empty-state svg {
  width: 64px;
  height: 64px;
  color: var(--text-secondary);
  margin-bottom: 16px;
}

.error p,
.empty-state p {
  color: var(--text-secondary);
  font-size: 16px;
  margin-bottom: 20px;
}

.error button {
  padding: 12px 24px;
  background: var(--accent-blue);
  color: white;
  border: none;
  border-radius: 8px;
  cursor: pointer;
  font-size: 14px;
  font-weight: 600;
  transition: all 0.3s ease;
}

.error button:hover {
  background: #2563eb;
  transform: translateY(-2px);
  box-shadow: var(--shadow);
}

.probes-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
  gap: 20px;
}

@media (max-width: 768px) {
  .home-page {
    padding: 16px;
  }
  
  .header {
    margin-bottom: 24px;
  }
  
  .header h1 {
    font-size: 24px;
  }
  
  .header h1 svg {
    width: 24px;
    height: 24px;
  }
  
  .probes-grid {
    grid-template-columns: 1fr;
    gap: 16px;
  }
}
</style>