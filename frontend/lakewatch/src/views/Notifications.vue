<script setup>
  import Navigation from '@/components/Navigation.vue';
  import Notification from '@/components/notifications/Notification.vue';
</script>
<template>
  <div class="page-container">
    <h1 class="page-title">Notifications</h1>
    <Notification
      type="information"
      heading="Temperature sensor disconnected"
      message="The temperature sensor has disconnected. Check the device."
      device="Axiom V1"
      location="Test location"
      time="2026-02-14 16:05:32.123456+00"
    />
  </div>
  <Navigation/>
</template>
<style scoped>
</style>