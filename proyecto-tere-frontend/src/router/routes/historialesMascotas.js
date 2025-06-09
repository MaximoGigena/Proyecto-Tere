// routes/historialesMascotas.js
import historialLayout from '@/components/módulo_mascotas/carpetaHistoriales.vue'
import propietarios from '@/components/módulo_mascotas/historialDueños.vue'
import vacunas from '@/components/módulo_mascotas/historialVacunas.vue'
import HistorialMedicoLayout from '@/components/módulo_mascotas/historialMedico.vue'
import Procedimientos from '@/components/módulo_mascotas/historiaMedica/cirugías.vue' 
import Tratamientos from '@/components/módulo_mascotas/historiaMedica/Tratamientos.vue'
import Medicamentos from '@/components/módulo_mascotas/historiaMedica/Medicamentos.vue'
import Terapias from '@/components/módulo_mascotas/historiaMedica/Terapias.vue'
import Diagnosticos from '@/components/módulo_mascotas/historiaMedica/Diagnosticos.vue'
import Obligatorias from '@/components/módulo_mascotas/cartillaVacunación/vacunasObligatorias.vue'
import Opcionales from '@/components/módulo_mascotas/cartillaVacunación/vacunasOpcionales.vue'

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
    path: 'historialVacunas',  
    component: vacunas,
     children: [
      { 
        path: '', // Ruta base para historialVacunas
        name: 'historialVacunas',
        redirect: { name: 'obligatorias' } 
      },
      { 
        path: 'obligatorias',
        name: 'obligatorias',
        component: Obligatorias,
        props: (route) => ({
          id: route.params.id || route.query.id,
          isOverlay: route.meta?.overlay || false,
          ...route.query
        })
      },
      { 
        path: 'opcionales',
        name: 'opcionales',
        component: Opcionales,
        props: (route) => ({
          id: route.params.id || route.query.id,
          isOverlay: route.meta?.overlay || false,
          ...route.query
        })
      },
    ]
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
      },
      { 
        path: 'diagnosticos',
        name: 'diagnosticos',
        component: Diagnosticos,
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
          // Propietarios
          { 
            path: 'propietarios',
            name: 'veterinario-propietarios',
            component: propietarios,
            meta: { overlay: true },
            props: (route) => ({
              id: route.params.id,
              isOverlay: true,
              ...route.query
            })
          },
          // Vacunas
          { 
            path: 'historialVacunas',
            component: vacunas,
            children: [
              { 
                path: '',
                name: 'veterinario-historialVacunas',
                redirect: { name: 'veterinario-obligatorias' }
              },
              { 
                path: 'obligatorias',
                name: 'veterinario-obligatorias',
                component: Obligatorias,
                meta: { overlay: true },
                props: (route) => ({
                  id: route.params.id,
                  isOverlay: true,
                  ...route.query
                })
              },
              { 
                path: 'opcionales',
                name: 'veterinario-opcionales',
                component: Opcionales,
                meta: { overlay: true },
                props: (route) => ({
                  id: route.params.id,
                  isOverlay: true,
                  ...route.query
                })
              }
            ]
          },
          // Historial Médico
          { 
            path: 'historialMedico',
            component: HistorialMedicoLayout,
            children: [
              { 
                path: '',
                name: 'veterinario-historialMedico',
                redirect: { name: 'veterinario-cirugias' }
              },
              { 
                path: 'cirugias',
                name: 'veterinario-cirugias',
                component: Procedimientos,
                meta: { overlay: true },
                props: (route) => ({
                  id: route.params.id,
                  isOverlay: true,
                  ...route.query
                })
              },
              { 
                path: 'tratamientos',
                name: 'veterinario-tratamientos',
                component: Tratamientos,
                meta: { overlay: true },
                props: (route) => ({
                  id: route.params.id,
                  isOverlay: true,
                  ...route.query
                })
              },
              { 
                path: 'medicamentos',
                name: 'veterinario-medicamentos',
                component: Medicamentos,
                meta: { overlay: true },
                props: (route) => ({
                  id: route.params.id,
                  isOverlay: true,
                  ...route.query
                })
              },
              { 
                path: 'terapias',
                name: 'veterinario-terapias',
                component: Terapias,
                meta: { overlay: true },
                props: (route) => ({
                  id: route.params.id,
                  isOverlay: true,
                  ...route.query
                })
              },
              { 
                path: 'diagnosticos',
                name: 'veterinario-diagnosticos',
                component: Diagnosticos,
                meta: { overlay: true },
                props: (route) => ({
                  id: route.params.id,
                  isOverlay: true,
                  ...route.query
                })
              },
            ]
          }
        ]
      }
    ]
  }
];