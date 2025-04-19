import { createRouter, createWebHistory } from 'vue-router'
import { homeRoutes } from './routes/homeRoutes.js'
import { encuentrosRoutes } from './routes/encuentroRoutes.js'

const routes = [
  ...homeRoutes,
  ...encuentrosRoutes,
]

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes,
})

export default router

