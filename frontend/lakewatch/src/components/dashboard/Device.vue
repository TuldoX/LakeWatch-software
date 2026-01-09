<script setup>
  import { ref } from 'vue'

  const progress = ref(75) // percentage (0–100)
</script>
<template>
  <router-link to="/device">
  <div class="container-surface">
    <div class="top-row">
      <div class="device-name">
        <div id="device-image-wrapper">
          <img src="../../assets/icons/device.png" id="device-image">
        </div>
        <h2 class="text-secondary">AXIOM V1</h2>
      </div>
      <div class="connected"></div>
    </div>

    <div id="location">
      <img src="../../assets/icons/location.png" id="location-image">
      <p id="location-name">Test location</p>
    </div>

    <hr class="solid">

    <div id="battery-life">
        <div class="helper">
          <img src="../../assets/icons/battery.png" id="battery-image">
          <p id="battery-percentage">75%</p>
        </div>
        <div class="progress-bar">
          <div
            class="progress-fill"
            :style="{ width: progress + '%' }"
          ></div>
        </div>
    </div>

    <div id="time">
      <div class="helper helper-extender">
        <img src="../../assets/icons/time.png" id="time-image">
        <p id="time-text">Aug 31</p>
      </div>
    </div>
  </div>
</router-link>
</template>
<style scoped>
    #device-image {
      height: 1.688rem;
      width: 1.688rem;
    }

    #device-image-wrapper {
      height: 2.313rem;
      width: 2.313rem;
      background: linear-gradient(135deg, #1976D2 0%, #42A5F5 100%);
      display: flex;
      align-items: center;
      justify-content: center;
      border-radius: 10px;
    }

    .device-name {
      width: 100%;
      display: flex;
      display: flex;
      align-items: center;
      gap: 0.7rem;
    }

    .connected {
      box-shadow: 0px 0px 7px 3px rgba(var(--success-rgb), 0.5);
      background-color: rgb(var(--success-rgb));
      width: 0.75rem;
      height: 0.75rem;
      border-radius: 50%;
      margin-right: 1rem;
    }

    .top-row {
      display: flex;
      justify-content: space-between;
      align-items: center;
      width: 100%;
      margin-bottom: 0.4rem;
    }

    #location-image {
      height: 1.25rem;
      width: 1.25rem;
    }

    #location-name {
      font-size: clamp(0.875rem, 1.5vw, 0.9375rem);
      color: var(--text-secondary);
      font-weight: 500;
      margin: 0;
    }

    #location {
      flex-direction: row;
      display: flex;
      align-items: center;
      gap: 0.5rem;
      margin: 0 0 0.3rem 0;
      width: 100%;
    }

    hr.solid {
      border-top: 1px solid var(--border);
      width: calc(100% - 0.5rem);
      margin-bottom: 0;
    }

    .progress-bar {
      width: 16.563rem;
      height: 0.5rem; 
      background-color: rgba(var(--backroud-progress-rgb), 0.5);
      border-radius: 5px;
      overflow: hidden;
    }

    .progress-fill {
      height: 100%;
      background-color: var(--success);
      border-radius: 5px;
      transition: width 0.25s ease;
    }

    #battery-image {
      width: 1.375rem;
      height: 1.375rem;
    }

    #battery-percentage {
      font-size: clamp(0.8125rem, 1.5vw, 0.875rem);
      font-weight: 500;
      color: var(--text-secondary);
      margin:0;
      padding: 0;
    }

    .helper {
      flex-direction: row;
      display: flex;
      align-items: center;
      gap: 0.8rem;
    }

    #battery-life {
      flex-direction: row;
      display: flex;
      align-items:center;
      justify-content: space-between;
      margin: 0.5rem 0 0 0;
      width: calc(100% - 0.5rem);
      box-sizing: border-box;
      gap: 1rem;
    }

    #time-image {
      width: 1.125rem;
      height: 1.125rem;
    }

    #time-text {
      font-size: clamp(0.8125rem, 1.5vw, 0.875rem);
      font-weight: 500;
      color: var(--text-secondary);
      margin:0;
      padding: 0;
    }

    #time {
      flex-direction: row;
      display: flex;
      align-items:center;
      margin: 0.3rem 0 0 0;
      width: calc(100% - 0.5rem);
      box-sizing: border-box;
    }

    .helper-extender {
      gap: 1.05rem;
    }

    a {
      text-decoration: none;
      display: block;
      max-width: 23.6875rem;
      width: 100%;
    }
</style>