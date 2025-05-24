import { createRouter, createWebHistory } from 'vue-router'
import { homeRoutes } from './routes/homeRoutes.js'
import { encuentrosRoutes } from './routes/encuentroRoutes.js'
import { historialesMascotas } from './routes/historialesMascotas.js'
import { mascotasCerca } from './routes/mascotasCerca.js'
import {perfilUsuario} from './routes/perfilUsuarioRoutes.js'
import {chatRoutes} from './routes/overlayChats.js'


const routes = [
  ...homeRoutes,
  ...encuentrosRoutes,
  ...historialesMascotas,
  ...mascotasCerca,
  ...perfilUsuario,
  ...chatRoutes,
]

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes,
})

export default router

