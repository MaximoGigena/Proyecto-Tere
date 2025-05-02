// routes/historialesMascotas.js
import historialLayout from '@/components/carpetaHistoriales.vue'
import dueños from '@/components/historialDueños.vue'
import vacunas from '@/components/historialVacunas.vue'
import historialMedico from '@/components/historialMedico.vue'

export const historialesMascotas = [
  {
    path: '/revisar',
    component: historialLayout,
    children: [
      { path: '', redirect: '/revisar/dueños' },
      { path: 'dueños', component: dueños },
      { path: 'vacunas', component: vacunas },
      { path: 'historialMedico', component: historialMedico },
    ]
  }
]
