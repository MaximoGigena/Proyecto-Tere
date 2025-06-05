import { createRouter, createWebHistory } from 'vue-router'
import { homeRoutes } from './routes/homeRoutes.js'
import { encuentrosRoutes } from './routes/encuentroRoutes.js'
import { historialesMascotas, overlayVeterinario } from './routes/historialesMascotas.js'
import { mascotasCerca } from './routes/mascotasCerca.js'
import {perfilUsuario} from './routes/perfilUsuarioRoutes.js'
import {chatRoutes} from './routes/overlayChats.js'
import {chatRoom} from './routes/overlaySalas.js'
import {veterinarioFiltrosOverlay} from './routes/veterinarioFiltrosOverlay.js'
import {adminRoutes} from './routes/adminRoutes.js'
import {rutasUsuario} from './routes/usuarioRouter.js'

const routes = [
  ...homeRoutes,
  ...encuentrosRoutes,
  ...historialesMascotas,
  ...mascotasCerca,
  ...perfilUsuario,
  ...chatRoutes,
  ...overlayVeterinario,
  ...veterinarioFiltrosOverlay,
  ...chatRoom,
  ...adminRoutes,
  ...rutasUsuario,
]

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes,
})

export default router

