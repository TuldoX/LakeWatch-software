<script></script>
<template>
    <div class="container-surface">
        <div class="h2-info-container">
            <h2 class="h2-info">Device information</h2>
        </div>
        <hr class="solid">
        <div class="info-grid">
            <div class="info-row">
                <img src="../../assets/icons/calendar.png" class="info-icon">
                <p class="secondary">Last sync</p>
            </div>
            <p class="secondary-bold">2 min ago</p>
            <div class="info-row">
                <img src="../../assets/icons/location-small.png" class="info-icon">
                <p class="secondary">Location</p>
            </div>
            <p class="secondary-bold">Test location</p>
        </div>
    </div>
</template>
<style scoped>
    .container-surface {
      padding: 0.75rem 1rem 0.75rem 1rem;
    }

    hr.solid {
      border-top: 1px solid var(--border);
      width: 100%;
      margin: 0 0 0.75rem 0;
    }

    .info-grid {
        display: grid;
        grid-template-columns: auto 1fr;
        gap: 0.5rem 1rem;
        width: 100%;
        align-items: center;
    }

    .info-row {
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .info-icon {
      width: 0.938rem;
      height: 0.938rem;
      flex-shrink: 0;
    }

    .secondary {
      color: rgba(90, 95, 102, 0.75);
      font-size: clamp(0.75rem, 0.875rem + 0.25vw, 1rem);
      margin: 0;
      white-space: nowrap;
    }
</style>