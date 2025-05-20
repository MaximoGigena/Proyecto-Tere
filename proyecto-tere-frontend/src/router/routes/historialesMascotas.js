// routes/historialesMascotas.js
import historialLayout from '@/components/carpetaHistoriales.vue'
import propietarios from '@/components/historialDueños.vue'
import vacunas from '@/components/historialVacunas.vue'
import HistorialMedicoLayout from '@/components/historialMedico.vue'
import Procedimientos from '@/components/historiaMedica/Procedimientos.vue' 
import Diagnosticos from '@/components/historiaMedica/Diagnosticos.vue' 
import Medicamentos from '@/components/historiaMedica/Medicamentos.vue'
import Terapias from '@/components/historiaMedica/Terapias.vue'

export const historialesMascotas = [
  {
    path: '/revisar',
    component: historialLayout,
    children: [
      { path: '', redirect: { name: 'propietarios' } },
      { path: 'propietarios', name: 'propietarios', component: propietarios },
      { path: 'vacunas', name: 'vacunas', component: vacunas },
      { 
        path: 'historialMedico', // Usar siempre guiones
        component: HistorialMedicoLayout,
        children: [
          { path: '', redirect: { name: 'procedimientos' } },
          { path: 'procedimientos', name: 'procedimientos', component: Procedimientos },
          { path: 'diagnosticos', name: 'diagnosticos', component: Diagnosticos },
          { path: 'medicamentos', name: 'medicamentos', component: Medicamentos },
          { path: 'terapias', name: 'terapias', component: Terapias }
        ]
      }
    ]
  }
]