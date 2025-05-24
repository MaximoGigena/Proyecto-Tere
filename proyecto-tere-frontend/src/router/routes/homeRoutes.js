// routes/homeRoutes.js
import Home from '@/components/home.vue'
import EncuentrosView from '@/components/ExplorarEncuentros.vue'
import ProyectoTere from '@/components/sobreNosotros/proyectoTere.vue'
import contacto from '@/components/sobreNosotros/contactanos.vue'
import politicas from '@/components/Seguridad/politicasTere.vue'
import protecciónDatos from '@/components/Seguridad/protecciónDatos.vue'
import donaciones from '@/components/apoyanos/donaciones.vue'
import colaboradores from '@/components/apoyanos/colaboradores.vue'
//import Denuncias from '@/views/Denuncias.vue'

export const homeRoutes = [
  { path: '/', name: 'Home', component: Home },
  { path: '/encuentros', name: 'Encuentros', component: EncuentrosView },
  { path: '/ProyectoTere', name: 'ProyectoTere', component: ProyectoTere },
  { path: '/Contactanos', name: 'contacto', component: contacto },
  { path: '/Politicas', name: 'politicas', component: politicas },
  { path: '/ProtecciónDatos', name: 'protecciónDatos', component: protecciónDatos },
  { path: '/Donaciones', name: 'donaciones', component: donaciones },
  { path: '/Colaboradores', name: 'colaboradores', component: colaboradores },
]
