<script setup>
import { ref, onMounted } from 'vue'
import { useRoute } from 'vue-router'

import dashboard from '@/assets/icons/dashboard.png'
import dashboardActive from '@/assets/icons/dashboard-active.png'

import notifications from '@/assets/icons/notifications.png'
import notificationsNew from '@/assets/icons/notifications-new.png'
import notificationsActive from '@/assets/icons/notifications-active.png'

import settings from '@/assets/icons/settings.png'
import settingsActive from '@/assets/icons/settings-active.png'

import { getNews } from '@/services/apiService'

const route = useRoute()
const hasNewNotifications = ref(false)
const userId = ref(null)

onMounted(() => {
  try {
    const storedUser = localStorage.getItem('user')
    if (storedUser) {
      const userData = JSON.parse(storedUser)
      userId.value =
        userData?.sub ||
        userData?.user?.sub ||
        userData?.id ||
        userData?.userId ||
        null

      if (userId.value) {
        loadNotificationStatus()
      }
    }
  } catch (err) {}
})

async function loadNotificationStatus() {
  try {
    const news = await getNews(userId.value)
    if (Array.isArray(news) && news.length > 0) {
      hasNewNotifications.value = !!news[0]?.exists
    }
  } catch (err) {
    hasNewNotifications.value = false
  }
}

const notificationIcon = (isActive) => {
  if (isActive) return notificationsActive
  if (hasNewNotifications.value) return notificationsNew
  return notifications
}
</script>

<template>
  <nav class="nav">
    <router-link to="/" v-slot="{ isActive }" class="nav-item">
      <img :src="isActive ? dashboardActive : dashboard" class="nav-icon" />
      <span class="nav-label" :class="{ active: isActive }">Devices</span>
    </router-link>

    <router-link to="/notifications" v-slot="{ isActive }" class="nav-item">
      <img :src="notificationIcon(isActive)" class="nav-icon" />
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
  font-size: clamp(0.75rem, 1vw, 0.813rem);
  color: var(--text-secondary);
  font-weight: 500;
}

.nav-label.active {
  color: var(--button-main);
}
</style>