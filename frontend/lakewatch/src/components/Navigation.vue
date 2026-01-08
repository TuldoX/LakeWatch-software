<script setup>
import dashboard from '@/assets/icons/dashboard.png'
import dashboardActive from '@/assets/icons/dashboard-active.png'

import notifications from '@/assets/icons/notifications.png'
import notificationsActive from '@/assets/icons/notifications-active.png'

import settings from '@/assets/icons/settings.png'
import settingsActive from '@/assets/icons/settings-active.png'
</script>

<template>
  <nav class="nav">
    <router-link to="/" v-slot="{ isActive }" class="nav-item">
      <img :src="isActive ? dashboardActive : dashboard" class="nav-icon" />
      <span class="nav-label" :class="{ active: isActive }">Devices</span>
    </router-link>

    <router-link to="/notifications" v-slot="{ isActive }" class="nav-item">
      <img :src="isActive ? notificationsActive : notifications" class="nav-icon" />
      <span class="nav-label" :class="{ active: isActive }">Notifications</span>
    </router-link>

    <router-link to="/settings" v-slot="{ isActive }" class="nav-item">
      <img :src="isActive ? settingsActive : settings" class="nav-icon" />
      <span class="nav-label" :class="{ active: isActive }">Settings</span>
    </router-link>
  </nav>
</template>

<style scoped>
.nav {
  position: fixed;
  bottom: 0;
  left: 0;
  right: 0;
  width: 100%;
  display: flex;
  justify-content: space-around;
  align-items: center;
  background-color: var(--surface);
  padding: 0.75rem 0;
  border-top: 1px solid var(--border);
  z-index: 1000;
}

.nav-item {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 0.25rem;
  text-decoration: none;
  cursor: pointer;
}

.nav-icon {
  width: 1.875rem;
  height: 1.875rem;
}

.nav-label {
  font-size: 0.813rem;
  color: var(--text-secondary);
  font-weight: 500;
}

.nav-label.active {
  color: var(--button-main);
}
</style>