import { createRouter, createWebHistory } from 'vue-router'
import HomePage from '../views/HomePage.vue'
import ProbeDetails from '../views/ProbeDetails.vue'

const routes = [
  {
    path: '/app',
    name: 'Home',
    component: HomePage
  },
  {
    path: '/app/probe/:id',
    name: 'ProbeDetails',
    component: ProbeDetails,
    props: true
  }
]

const router = createRouter({
  history: createWebHistory(),
  routes
})

export default router