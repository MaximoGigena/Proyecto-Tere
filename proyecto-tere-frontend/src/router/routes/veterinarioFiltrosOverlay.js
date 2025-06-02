import VeterinarioBuscarMascotas from '@/components/módulo_veterinario/veterinarioFiltros.vue'
import VeterinarioFiltroOverlay from '@/components/módulo_veterinario/veterinarioFiltrosOverlay.vue'
import sideBar from '@/components/módulo_veterinario/sideBarMascotas.vue'

export const veterinarioFiltrosOverlay = [
{
  path: '/veterinarios/buscar',
  components: {
    default: VeterinarioBuscarMascotas,
    overlay: null, // sin overlay por defecto
  },
  name: 'buscarMascotas',
},
{
  path: '/veterinarios/buscar/filtros',
  components: {
    default: sideBar,
    overlay: VeterinarioFiltroOverlay,
  },
  name: 'buscarMascotasConFiltros',
 }
]