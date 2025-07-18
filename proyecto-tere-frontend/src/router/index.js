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
import { rutasGaleria } from './routes/galeriaMascota.js'
import { registroMascota } from './routes/overlayRegistroMascota.js'
import { registrarVacuna } from './routes/registroVacuna.js'
import { registrarDesparasitacion } from './routes/registroDesparasitación.js'
import { registrarRevisión } from './routes/registroRevisiones.js'
import { registrarAlergia } from './routes/registrarAlergia.js'
import { registrarCirugia } from './routes/registrarCirugia.js'
import { registrarDiagnostico } from './routes/registrarDiagnostico.js'
import { registrarFarmaco } from './routes/registroFarmaco.js'
import { registrarPaliativo } from './routes/registrarPaliativos.js'
import { registrarTerapia } from './routes/registrarTerapia.js'

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
  ...rutasGaleria,
  ...registroMascota,
  ...registrarVacuna, 
  ...registrarDesparasitacion,
  ...registrarRevisión,
  ...registrarAlergia,
  ...registrarCirugia,
  ...registrarDiagnostico, 
  ...registrarFarmaco,
  ...registrarPaliativo,
  ...registrarTerapia,
]

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes,
})

export default router

