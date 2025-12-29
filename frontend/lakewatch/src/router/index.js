import { createRouter, createWebHistory } from 'vue-router'
import Dashboard from '@/views/Dashboard.vue'
import Device from '@/views/Device.vue'
import Notifications from '@/views/Notifications.vue'
import Settings from '@/views/Settings.vue'

const routes = [
  { path: '/', name: 'Dashboard', component: Dashboard },
  { path: '/device', name: 'Device_details', component: Device},
  { path: '/notifications', name: 'Notifications', component: Notifications},
  { path: '/settings', name: 'Settings', component: Settings}
]

const router = createRouter({
  history: createWebHistory(),
  routes
})

export default router
