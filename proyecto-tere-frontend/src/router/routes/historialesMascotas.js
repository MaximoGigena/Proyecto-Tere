// routes/historialesMascotas.js
import historialLayout from '@/components/carpetaHistoriales.vue'
import propietarios from '@/components/historialDueños.vue'
import vacunas from '@/components/historialVacunas.vue'
import historialMedico from '@/components/historialMedico.vue'

export const historialesMascotas = [
  {
    path: '/revisar',
    component: historialLayout,
    children: [
      { path: '', component: propietarios }, // Cambiar la redirección a un componente
      { path: 'propietarios', name: 'propietarios', component: propietarios },
      { path: 'vacunas', name: 'vacunas', component: vacunas },
      { path: 'historialMedico', name: 'historialMedico', component: historialMedico },
    ]
  }
  
]
