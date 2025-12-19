import { createRouter, createWebHistory } from 'vue-router'
import Dashboard from '@/views/Dashboard.vue'
import Device from '@/views/Device.vue'

const routes = [
  { path: '/', name: 'Dashboard', component: Dashboard },
  { path: '/device', name: 'Device_details', component: Device},
]

const router = createRouter({
  history: createWebHistory(),
  routes
})

export default router
