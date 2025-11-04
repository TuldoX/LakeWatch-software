<template>
  <div class="probe-details">
    <header class="header">
      <div class="header-content">
        <button class="back-btn" @click="goBack">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M19 12H5M12 19l-7-7 7-7"/>
          </svg>
        </button>
        <div class="header-info">
          <h1>{{ probeName }}</h1>
          <div class="status" :class="statusClass">
            <span class="status-dot"></span>
            <span>{{ statusText }}</span>
          </div>
        </div>
      </div>
    </header>
    
    <main class="main-content">
      <div class="loading" v-if="loading">
        <div class="spinner"></div>
        <p>Loading probe data...</p>
      </div>
      
      <div class="error" v-else-if="error">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <circle cx="12" cy="12" r="10"/>
          <path d="M12 8v4m0 4h.01"/>
        </svg>
        <p>{{ error }}</p>
        <button @click="fetchData">Retry</button>
      </div>
      
      <div class="no-data" v-else-if="!probeData?.values?.length">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <path d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
        </svg>
        <p>No data available for this probe</p>
        <button @click="fetchData">Refresh</button>
      </div>
      
      <div class="charts-grid" v-else>
        <div class="chart-card oxygen">
          <div class="chart-header">
            <div class="chart-title">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <circle cx="12" cy="12" r="10"/>
                <path d="M12 16v-4M12 8h.01"/>
              </svg>
              <h3>Oxygen</h3>
            </div>
            <span class="value">{{ latestValues.oxygen }} <small>mg/L</small></span>
          </div>
          <div class="chart-wrapper">
            <canvas ref="oxygenChart"></canvas>
          </div>
          <div class="chart-footer">
            <span class="range">Optimal: 6.5-8 mg/L</span>
          </div>
        </div>
        
        <div class="chart-card temperature">
          <div class="chart-header">
            <div class="chart-title">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M14 14.76V3.5a2.5 2.5 0 00-5 0v11.26a4.5 4.5 0 105 0z"/>
              </svg>
              <h3>Temperature</h3>
            </div>
            <span class="value">{{ latestValues.temp }} <small>°C</small></span>
          </div>
          <div class="chart-wrapper">
            <canvas ref="temperatureChart"></canvas>
          </div>
          <div class="chart-footer">
            <span class="range">Optimal: 15-20 °C</span>
          </div>
        </div>
        
        <div class="chart-card ph">
          <div class="chart-header">
            <div class="chart-title">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M12 2v20M2 12h20"/>
              </svg>
              <h3>pH Level</h3>
            </div>
            <span class="value">{{ latestValues.ph }} <small>pH</small></span>
          </div>
          <div class="chart-wrapper">
            <canvas ref="phChart"></canvas>
          </div>
          <div class="chart-footer">
            <span class="range">Optimal: 6.5-8.5 pH</span>
          </div>
        </div>
        
        <div class="chart-card tds">
          <div class="chart-header">
            <div class="chart-title">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M12 2.69l5.66 5.66a8 8 0 11-11.31 0z"/>
              </svg>
              <h3>TDS</h3>
            </div>
            <span class="value">{{ latestValues.tds }} <small>mg/L</small></span>
          </div>
          <div class="chart-wrapper">
            <canvas ref="tdsChart"></canvas>
          </div>
          <div class="chart-footer">
            <span class="range">Optimal: 50-250 mg/L</span>
          </div>
        </div>
      </div>
    </main>
  </div>
</template>

<script>
import { Chart, registerables } from 'chart.js'
import { api } from '../services/api'

Chart.register(...registerables)

export default {
  name: 'ProbeDetails',
  props: {
    id: {
      type: String,
      required: true
    }
  },
  data() {
    return {
      loading: true,
      error: null,
      probeData: null,
      charts: {}
    }
  },
  computed: {
    probeName() {
      return `AXIOM V1`
    },
    statusClass() {
      return 'online'
    },
    statusText() {
      return 'Active'
    },
    latestValues() {
      if (!this.probeData?.values?.length) {
        return { oxygen: '--', temp: '--', ph: '--', tds: '--' }
      }
      const latest = this.probeData.values[this.probeData.values.length - 1]
      return {
        oxygen: parseFloat(latest.oxygen || 0).toFixed(1),
        temp: parseFloat(latest.temp || 0).toFixed(1),
        ph: parseFloat(latest.ph || 0).toFixed(1),
        tds: parseFloat(latest.tds || 0).toFixed(0)
      }
    }
  },
  mounted() {
    this.fetchData()
  },
  beforeUnmount() {
    this.destroyCharts()
  },
  methods: {
    async fetchData() {
      this.loading = true
      this.error = null
      
      try {
        const response = await api.getProbeData(this.id)
        
        this.probeData = {
          values: response.values || []
        }
        
        if (this.probeData.values.length > 0) {
          await this.$nextTick()
          setTimeout(() => {
            this.createCharts()
          }, 100)
        }
      } catch (err) {
        this.error = 'Failed to load probe data'
      } finally {
        this.loading = false
      }
    },
    
    createCharts() {
      if (!this.probeData?.values?.length || !this.$refs.oxygenChart) {
        return
      }
      
      this.destroyCharts()
      
      const values = this.probeData.values
      const labels = values.map(v => {
        try {
          const date = new Date(v.time_recieved)
          return date.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit' })
        } catch (e) {
          return '--:--'
        }
      })
      
      const commonOptions = {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          legend: { display: false },
          tooltip: {
            mode: 'index',
            intersect: false,
            backgroundColor: 'rgba(30, 41, 59, 0.95)',
            titleColor: '#f8fafc',
            bodyColor: '#cbd5e1',
            borderColor: 'rgba(59, 130, 246, 0.3)',
            borderWidth: 1,
            padding: 12,
            displayColors: false
          }
        },
        scales: {
          x: {
            grid: { display: false },
            ticks: { color: '#64748b', maxTicksLimit: 6 },
            border: { display: false }
          },
          y: {
            grid: { color: 'rgba(148, 163, 184, 0.1)' },
            ticks: { color: '#64748b' },
            border: { display: false }
          }
        },
        elements: {
          line: { tension: 0.4 },
          point: { radius: 0, hoverRadius: 6 }
        }
      }
      
      // Oxygen Chart
      this.charts.oxygen = new Chart(this.$refs.oxygenChart, {
        type: 'line',
        data: {
          labels,
          datasets: [{
            data: values.map(v => parseFloat(v.oxygen)),
            borderColor: '#3b82f6',
            backgroundColor: 'rgba(59, 130, 246, 0.1)',
            borderWidth: 2,
            fill: true
          }]
        },
        options: {
          ...commonOptions,
          scales: {
            ...commonOptions.scales,
            y: { 
              ...commonOptions.scales.y,
              min: 0,
              max: 20
            }
          }
        }
      })
      
      // Temperature Chart
      this.charts.temperature = new Chart(this.$refs.temperatureChart, {
        type: 'line',
        data: {
          labels,
          datasets: [{
            data: values.map(v => parseFloat(v.temp)),
            borderColor: '#ef4444',
            backgroundColor: 'rgba(239, 68, 68, 0.1)',
            borderWidth: 2,
            fill: true
          }]
        },
        options: {
          ...commonOptions,
          scales: {
            ...commonOptions.scales,
            y: { 
              ...commonOptions.scales.y,
              min: 15,
              max: 30
            }
          }
        }
      })
      
      // pH Chart
      this.charts.ph = new Chart(this.$refs.phChart, {
        type: 'line',
        data: {
          labels,
          datasets: [{
            data: values.map(v => parseFloat(v.ph)),
            borderColor: '#10b981',
            backgroundColor: 'rgba(16, 185, 129, 0.1)',
            borderWidth: 2,
            fill: true
          }]
        },
        options: {
          ...commonOptions,
          scales: {
            ...commonOptions.scales,
            y: { 
              ...commonOptions.scales.y,
              min: 6,
              max: 9
            }
          }
        }
      })
      
      // TDS Chart
      this.charts.tds = new Chart(this.$refs.tdsChart, {
        type: 'line',
        data: {
          labels,
          datasets: [{
            data: values.map(v => parseFloat(v.tds)),
            borderColor: '#f59e0b',
            backgroundColor: 'rgba(245, 158, 11, 0.1)',
            borderWidth: 2,
            fill: true
          }]
        },
        options: {
          ...commonOptions,
          scales: {
            ...commonOptions.scales,
            y: { 
              ...commonOptions.scales.y,
              min: 0,
              max: 500
            }
          }
        }
      })
    },
    
    destroyCharts() {
      Object.values(this.charts).forEach(chart => {
        if (chart && typeof chart.destroy === 'function') {
          chart.destroy()
        }
      })
      this.charts = {}
    },
    
    goBack() {
      this.$router.push({ name: 'Home' })
    }
  }
}
</script>

<style scoped>
.probe-details {
  min-height: 100vh;
  padding: 20px;
}

.header {
  margin-bottom: 32px;
}

.header-content {
  max-width: 1400px;
  margin: 0 auto;
  display: flex;
  align-items: center;
  gap: 16px;
}

.back-btn {
  width: 44px;
  height: 44px;
  background: var(--bg-card);
  border: none;
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  transition: all 0.3s ease;
  flex-shrink: 0;
  box-shadow: var(--shadow);
}

.back-btn svg {
  width: 20px;
  height: 20px;
  color: var(--text-primary);
}

.back-btn:hover {
  background: var(--accent-blue);
  transform: translateX(-4px);
}

.back-btn:hover svg {
  color: white;
}

.header-info {
  flex: 1;
}

.header-info h1 {
  font-size: 28px;
  font-weight: 700;
  color: var(--text-primary);
  margin-bottom: 8px;
}

.status {
  display: flex;
  align-items: center;
  gap: 8px;
  font-size: 13px;
  color: var(--accent-green);
}

.status-dot {
  width: 8px;
  height: 8px;
  border-radius: 50%;
  background: var(--accent-green);
  box-shadow: 0 0 12px var(--accent-green);
  animation: pulse 2s ease-in-out infinite;
}

.main-content {
  max-width: 1400px;
  margin: 0 auto;
}

.loading,
.error,
.no-data {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 60px 20px;
  text-align: center;
  gap: 16px;
}

.loading p,
.error p,
.no-data p {
  color: var(--text-secondary);
  font-size: 16px;
}

.spinner {
  width: 48px;
  height: 48px;
  border: 4px solid rgba(59, 130, 246, 0.2);
  border-top-color: var(--accent-blue);
  border-radius: 50%;
  animation: spin 1s linear infinite;
}

.error svg,
.no-data svg {
  width: 64px;
  height: 64px;
  color: var(--text-secondary);
}

.error button,
.no-data button {
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

.error button:hover,
.no-data button:hover {
  background: #2563eb;
  transform: translateY(-2px);
  box-shadow: var(--shadow);
}

.charts-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
  gap: 20px;
}

.chart-card {
  background: var(--bg-card);
  border-radius: 16px;
  padding: 20px;
  box-shadow: var(--shadow);
  transition: all 0.3s ease;
  position: relative;
  overflow: hidden;
}

.chart-card::before {
  content: '';
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  height: 3px;
  opacity: 0.8;
}

.chart-card.oxygen::before {
  background: var(--accent-blue);
}

.chart-card.temperature::before {
  background: var(--accent-red);
}

.chart-card.ph::before {
  background: var(--accent-green);
}

.chart-card.tds::before {
  background: var(--accent-orange);
}

.chart-card:hover {
  transform: translateY(-4px);
  box-shadow: var(--shadow-lg);
}

.chart-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  margin-bottom: 20px;
}

.chart-title {
  display: flex;
  align-items: center;
  gap: 10px;
}

.chart-title svg {
  width: 20px;
  height: 20px;
  flex-shrink: 0;
}

.chart-card.oxygen .chart-title svg {
  color: var(--accent-blue);
}

.chart-card.temperature .chart-title svg {
  color: var(--accent-red);
}

.chart-card.ph .chart-title svg {
  color: var(--accent-green);
}

.chart-card.tds .chart-title svg {
  color: var(--accent-orange);
}

.chart-title h3 {
  font-size: 16px;
  font-weight: 600;
  color: var(--text-primary);
}

.value {
  font-size: 24px;
  font-weight: 700;
  color: var(--text-primary);
}

.value small {
  font-size: 12px;
  font-weight: 500;
  color: var(--text-secondary);
  margin-left: 4px;
}

.chart-wrapper {
  height: 180px;
  margin-bottom: 16px;
}

.chart-footer {
  padding-top: 12px;
  border-top: 1px solid rgba(148, 163, 184, 0.1);
  display: flex;
  justify-content: center;
}

.range {
  font-size: 12px;
  color: var(--text-secondary);
  display: flex;
  align-items: center;
  gap: 6px;
}

.range::before {
  content: '';
  width: 4px;
  height: 4px;
  border-radius: 50%;
  background: var(--text-secondary);
}

@media (max-width: 1200px) {
  .charts-grid {
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
  }
}

@media (max-width: 768px) {
  .probe-details {
    padding: 16px;
  }
  
  .header {
    margin-bottom: 24px;
  }
  
  .header-info h1 {
    font-size: 20px;
  }
  
  .charts-grid {
    grid-template-columns: 1fr;
    gap: 16px;
  }
  
  .chart-card {
    padding: 16px;
  }
  
  .value {
    font-size: 20px;
  }
  
  .chart-wrapper {
    height: 160px;
  }
}

@media (max-width: 480px) {
  .back-btn {
    width: 40px;
    height: 40px;
  }
  
  .header-info h1 {
    font-size: 18px;
  }
}

@keyframes spin {
  0% { transform: rotate(0deg); }
  100% { transform: rotate(360deg); }
}

@keyframes pulse {
  0%, 100% { opacity: 1; }
  50% { opacity: 0.5; }
}
</style>