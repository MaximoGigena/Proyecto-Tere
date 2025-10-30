// adminRoutes.js
import navBarVeterinario from '@/components/módulo_veterinario/navBarVeterinario.vue'
import Busqueda from '@/components/módulo_veterinario/sideBarMascotas.vue'
import Perfil from '@/components/módulo_veterinario/perfilVeterinario.vue'
import Notificaciones from '@/components/módulo_veterinario/bandejaNotificaciones.vue'
import Historial from '@/components/módulo_veterinario/historialTratamientos.vue'
import Rendimiento from '@/components/módulo_veterinario/rendimientoVeterinario.vue'
import CentroVeterinario from '@/components/módulo_veterinario/gestionarVeterinaria.vue'

// Layout catálogo y sus hijos
import CatalogoLayout from '@/components/módulo_veterinario/gestionarTipos.vue'
import TipoVacunas from '@/components/módulo_veterinario/catalogo_tipos/tipoVacunas.vue'
import TipoDesparasitaciones from '@/components/módulo_veterinario/catalogo_tipos/tipoDesparasitaciones.vue'
import TipoRevisiones from '@/components/módulo_veterinario/catalogo_tipos/tipoRevisiones.vue'
import TipoAlergias from '@/components/módulo_veterinario/catalogo_tipos/tipoAlergias.vue'
import TipoCirugias from '@/components/módulo_veterinario/catalogo_tipos/tipoCirugias.vue'
import TipoTerapias from '@/components/módulo_veterinario/catalogo_tipos/tipoTerapias.vue'
import TipoFarmacos from '@/components/módulo_veterinario/catalogo_tipos/tipoFarmacos.vue'
import TipoDiagnosticos from '@/components/módulo_veterinario/catalogo_tipos/tipoDiagnostico.vue'
import TipoPaliativos from '@/components/módulo_veterinario/catalogo_tipos/tipoPaliativos.vue'

export const veterinarioRoutes = [
  {
    path: '/veterinarios',
    component: navBarVeterinario, // padre con navbar
    children: [
      {
        path: 'busqueda',
        component: Busqueda,
        name: 'veterinarioBusqueda',
      },
      {
        path: 'historial',
        component: Historial,
        name: 'veterinarioHistorial',
      },
      {
        path: 'rendimiento',
        component: Rendimiento,
        name: 'veterinarioRendimiento',
      },
      {
        path: 'tipos',
        component: CatalogoLayout, // layout para los tipos
        children: [
          { path: '', redirect: { name: 'tipoVacunas' } },
          { path: 'vacunas', name: 'tipoVacunas', component: TipoVacunas },
          { path: 'desparasitaciones', name: 'tipoDesparasitaciones', component: TipoDesparasitaciones },
          { path: 'revisiones', name: 'tipoRevisiones', component: TipoRevisiones },
          { path: 'alergias', name: 'tipoAlergias', component: TipoAlergias },
          { path: 'cirugias', name: 'tipoCirugias', component: TipoCirugias },
          { path: 'terapias', name: 'tipoTerapias', component: TipoTerapias },
          { path: 'farmacos', name: 'tipoFarmacos', component: TipoFarmacos },
          { path: 'diagnosticos', name: 'tipoDiagnosticos', component: TipoDiagnosticos },
          { path: 'paliativos', name: 'tipoPaliativos', component: TipoPaliativos },
        ]
      },
      {
        path: 'perfil',
        component: Perfil,
        name: 'veterinarioPerfil',
      },
      {
        path: 'notificaciones',
        component: Notificaciones,
        name: 'veterinarioNotificaciones',
      },
      {
        path: 'centroVeterinario',
        component: CentroVeterinario,
        name: 'veterinarioCentroVeterinario',
      },
      {
        path: '',
        redirect: 'busqueda', // ruta por defecto
      }
    ],
  },
]
