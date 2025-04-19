// routes/homeRoutes.js
import Home from '@/components/home.vue'
import EncuentrosView from '@/components/ExplorarEncuentros.vue'

export const homeRoutes = [
  { path: '/', name: 'Home', component: Home },
  { path: '/encuentros', name: 'Encuentros', component: EncuentrosView },
]
