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
import { registrarTipoAlergia } from './routes/registrarTipoAlergia.js'
import { registrarTipoVacuna } from './routes/registrarTipoVacuna.js'
import { registrarTipoDesparasitacion } from './routes/registrarTipoDesparasitacion.js'
import { registrarTipoRevision } from './routes/registrarTipoRevision.js'
import { registrarTipoCirugia } from './routes/registrarTipoCirugia.js'
import { registrarTipoFarmaco } from './routes/registrarTipoFarmaco.js'
import { registrarTipoTerapia } from './routes/registrarTipoTerapia.js'
import { registrarTipoDiagnostico } from './routes/registrarTipoDiagnostico.js'
import { registrarTipoPaliativo } from './routes/registrarTipoPaliativo.js'
import { veterinarioRoutes } from './routes/veterinarioRoutes.js'
import { SeleccionarRegistro } from './routes/selecciónRegistro.js'
import { ConfiguracionesUsuario } from './routes/rutasConfiguraciónUsuario.js'
import { overlayProcedimiento } from './routes/carpetaProcedimiento.js'
import { RutasLoginRegistro } from './routes/rutasLoginRegistro.js'
import { registrarVeterinaria } from './routes/registrarCentroVeterinario.js'
import { editarMascotaRoutes } from './routes/editarMascota.js'
import { motivosBajaMascota } from './routes/overlayMotivosMascota.js'
import { esperaVeterinarios } from './routes/esperaVeterinaria.js'
import { UsuarioSuspendido } from './routes/cuentaSuspendida.js'
 
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
  ...registrarTipoAlergia,
  ...registrarTipoVacuna,
  ...registrarTipoDesparasitacion,
  ...registrarTipoRevision,
  ...registrarTipoCirugia,
  ...registrarTipoFarmaco,
  ...registrarTipoTerapia,
  ...registrarTipoDiagnostico,
  ...registrarTipoPaliativo,
  ...veterinarioRoutes,
  ...SeleccionarRegistro,
  ...ConfiguracionesUsuario,
  ...overlayProcedimiento,
  ...RutasLoginRegistro,
  ...registrarVeterinaria,
  ...editarMascotaRoutes,
  ...motivosBajaMascota,
  ...esperaVeterinarios,
  ...UsuarioSuspendido,
]

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes,
})

// Después de crear el router
router.beforeEach(async (to, from, next) => {
  const token = localStorage.getItem('token') || localStorage.getItem('auth_token');
  
  // Si no hay token y la ruta requiere autenticación
  if (to.meta.requiresAuth && !token) {
    next('/login');
    return;
  }
  
  // Si hay token, verificar si el usuario está suspendido
  if (token && to.name !== 'cuenta-suspendida') {
    try {
      // Verificar en localStorage primero (más rápido)
      const suspensionData = localStorage.getItem('suspension_data');
      if (suspensionData) {
        const data = JSON.parse(suspensionData);
        if (data.estado === 'suspendido' || data.esta_suspendido) {
          next('/cuenta-suspendida');
          return;
        }
      }
      
      // Si no hay datos en localStorage pero la ruta no es login, 
      // podrías hacer una petición para verificar el estado
      // (esto es opcional, ya que el middleware de Laravel lo hará)
      
    } catch (error) {
      console.error('Error verificando estado de usuario:', error);
    }
  }
  
  next();
});

export default router

