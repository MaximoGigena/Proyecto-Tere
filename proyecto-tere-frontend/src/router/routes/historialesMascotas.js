// routes/historialesMascotas.js
import historialLayout from '@/components/módulo_mascotas/carpetaHistoriales.vue'
import propietarios from '@/components/módulo_mascotas/historialDueños.vue'
import vacunas from '@/components/módulo_mascotas/historialVacunas.vue'
import HistorialMedicoLayout from '@/components/módulo_mascotas/historialMedico.vue'
import Procedimientos from '@/components/módulo_mascotas/historiaMedica/Procedimientos.vue' 
import Tratamientos from '@/components/módulo_mascotas/historiaMedica/Tratamientos.vue'
import Medicamentos from '@/components/módulo_mascotas/historiaMedica/Medicamentos.vue'
import Terapias from '@/components/módulo_mascotas/historiaMedica/Terapias.vue'

// Rutas comunes con nombres base
const baseRoutes = [
  { 
     path: 'propietarios', 
     name: 'propietarios', 
     component: propietarios,
     meta: { overlay: false },
     props: (route) => ({
      id: route.params.id || route.query.id,
      isOverlay: route.meta?.overlay || false,
      ...route.query
  })
  },
  { 
    path: 'vacunas', 
    name: 'vacunas', 
    component: vacunas,
    meta: { overlay: false },
    props: (route) => ({
      id: route.params.id || route.query.id,
      isOverlay: route.meta?.overlay || false,
      ...route.query
    })
  },
  { 
    path: 'historialMedico',
    component: HistorialMedicoLayout,
    children: [
      { path: '',
        name: 'historialMedico',
        redirect: { name: 'cirugias' } },
      { 
        path: 'cirugias',
        name: 'cirugias',
        component: Procedimientos,
        props: (route) => ({
        id: route.params.id || route.query.id,
        isOverlay: route.meta?.overlay || false,
        ...route.query
      })
      },
      { 
        path: 'tratamientos',
        name: 'tratamientos',
        component: Tratamientos,
        props: (route) => ({
        id: route.params.id || route.query.id,
        isOverlay: route.meta?.overlay || false,
        ...route.query
      })
      },
      { 
        path: 'medicamentos',
        name: 'medicamentos',
        component: Medicamentos,
        props: (route) => ({
        id: route.params.id || route.query.id,
        isOverlay: route.meta?.overlay || false,
        ...route.query
      })
      },
      { 
        path: 'terapias',
        name: 'terapias',
        component: Terapias,
        props: (route) => ({
        id: route.params.id || route.query.id,
        isOverlay: route.meta?.overlay || false,
        ...route.query
      })
      }
    ]
  }
];

// Rutas normales
export const historialesMascotas = [
  {
    path: '/revisar',
    component: historialLayout,
    children: [
      { path: '', redirect: { name: 'propietarios' } },
      ...baseRoutes
    ]
  }
];

// Rutas overlay para veterinarios
export const overlayVeterinario = [
  {
    path: '/veterinarios',
    component: () => import('@/components/módulo_veterinario/veterinarioLayout.vue'),
    children: [
      {
        path: '',
        components: {
          right: {
            template: '<div class="h-full flex items-center justify-center text-red-400">Selecciona una mascota para ver el historial</div>',
          }
        }
      },
      {
        path: 'mascota/:id',
        components: {
          right: historialLayout,
        },
        props: { right: true },
        children: [
          { path: '', redirect: { name: 'veterinario-propietarios' } },
          ...baseRoutes.map(route => {
            const newRoute = {
              ...route,
              name: `veterinario-${route.name}`,
              meta: { ...route.meta, overlay: true },
              props: (route) => ({
                id: route.params.id,
                isOverlay: true,
                ...route.query
              })
            };
            
            if (route.children) {
              newRoute.children = route.children.map(childRoute => ({
                ...childRoute,
                name: `veterinario-${childRoute.name}`,
                meta: { ...childRoute.meta, overlay: true },
                props: (route) => ({
                  id: route.params.id,
                  isOverlay: true,
                  ...route.query,
                  // Mantener el ID en todos los niveles
                  mascotaId: route.params.id 
                })
              }));
            }
            
            return newRoute;
          })
        ]
      }
    ]
  }
];