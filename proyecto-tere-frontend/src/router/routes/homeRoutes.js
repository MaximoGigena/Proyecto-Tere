// routes/homeRoutes.js
import Home from '@/components/home.vue'
import EncuentrosView from '@/components/módulo_usuario/ExplorarEncuentros.vue'
import ProyectoTere from '@/components/navBar_Home/sobreNosotros/proyectoTere.vue'
import contacto from '@/components/navBar_Home/sobreNosotros/contactanos.vue'
import politicas from '@/components/navBar_Home/Seguridad/politicasTere.vue'
import protecciónDatos from '@/components/navBar_Home/Seguridad/protecciónDatos.vue'
import donaciones from '@/components/navBar_Home/apoyanos/donaciones.vue'
import colaboradores from '@/components/navBar_Home/apoyanos/colaboradores.vue'
import veterinarios from '@/components/módulo_veterinario/sideBarMascotas.vue'
import administradores from '@/components/módulo_administrador/navBarAdmin.vue'
import registroUsuario from '@/components/módulo_usuario/registrarUsuario.vue'
import registroVeterinario  from '@/components/módulo_veterinario/registrarVeterinario.vue'
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
  { path: '/veterinarios', name: 'veterinarios', component: veterinarios },
  { path: '/administradores', name: 'administradores', component: administradores },
  { path: '/registroUsuarios', name: 'registroUsuarios', component: registroUsuario },
  { path: '/registroVeterinario', name: 'registroVeterinario', component: registroVeterinario },
]
