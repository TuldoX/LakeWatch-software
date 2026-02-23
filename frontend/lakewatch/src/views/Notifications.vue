<script setup>
  import Navigation from '@/components/Navigation.vue';
  import { ref, onMounted } from 'vue'
  import Notification from '@/components/notifications/Notification.vue'
  import { getNotifications } from '@/services/apiService'

  const notifications = ref([])
  const user = JSON.parse(localStorage.getItem('user'))

  async function loadNotifications() {
    try {
      notifications.value = await getNotifications(user.user.sub)
    } catch (error) {
      console.error('Failed to load notifications:', error)
    }
  }

  onMounted(() => {
    loadNotifications()
  })

</script>
<template>
  <div class="page-container">
    <h1 class="page-title">Notifications</h1>
    <Notification
        v-for="notification in notifications"
        :key="notification.id"
        :id="notification.id"
        :heading="notification.heading"
        :message="notification.message"
        :type="notification.type"
        :device="notification.probe"          
        :location="notification.location"
        :time="notification.time"
        @deleted="loadNotifications"
    />
  </div>
  <Navigation/>
</template>
<style scoped>
</style>